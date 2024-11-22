class PeceeInfiniteList extends PeceeWidget {

    data = {
        reference: [],
        itemsPerPage: 20,
        boundaries: [],
        items: [],
        offset: 0,
        containerHeight: 0,
        top: 0,
        minHeight: null,
        loading: true,
        offsetStartPosition: 0,
        offsetEndPosition: 0,
    };

    options = {
        containerEl: '.vl-ctn',
        scrollElement: window, // window or nodeElement whatever you like
        preloadBufferPixels: 0, // amount of pixels before preload happens
        scrollTimeoutMs: 40,
        renderTemplate: null,
        maxOffsets: -1,
    };

    scrollTimer = null;
    context = null; // Widget context

    constructor(renderTemplate, options, container) {
        super(null, container);

        this.options = Object.assign(this.options, options);

        if (renderTemplate === null) {
            throw 'Render template not defined';
        }

        const events = options.events;
        if (events) {
            for (let eventKey in events) {
                if (eventKey === 'init') {
                    this.one('render', (data) => events[eventKey]({list: this, data: data}));
                } else {
                    this.bind(eventKey, (data) => events[eventKey]({list: this, data: data}));
                }
            }
        }

        this.options.renderTemplate = renderTemplate;

        this.one('render', () => {

            const scrollElement = (this.options.scrollElement === window) ? window : this.options.scrollElement;

            document.querySelector(scrollElement).on('scroll.list', (event) => {

                if (document.querySelector(this.container) === null) {
                    return;
                }

                // Unbind when container is gone
                if (this.context && document.querySelector(this.context.container) === null) {
                    document.querySelector(scrollElement).off('scroll.list');
                    return;
                }

                clearTimeout(this.scrollTimer);
                this.scrollTimer = setTimeout(() => {

                    const scroll = (this.options.scrollElement === window) ? window : document.querySelector(this.options.scrollElement);

                    if (this.options.scrollElement === window) {
                        this.data.offsetStartPosition = scroll.scrollY;
                        this.data.offsetEndPosition = scroll.scrollY + scroll.innerHeight + this.options.preloadBufferPixels;
                    } else {
                        this.data.offsetStartPosition = scroll.scrollTop;
                        this.data.offsetEndPosition = scroll.scrollTop + scroll.clientHeight + this.options.preloadBufferPixels;
                    }

                    this.render();
                }, this.options.scrollTimeoutMs);
            }).trigger('scroll.list');

        });
    }

    async refresh() {

        const d = this.data.boundaries.find(b => b.end > this.data.offsetEndPosition);
        this.data.offset = d ? d.offset : this.data.offset;

        if (!d) {

            // No more items exist.
            if (this.options.maxOffsets > -1 && this.data.offset > this.options.maxOffsets) {
                return;
            }

            // Filter by data items that are currently in view
            const existingBoundary = this.data.boundaries.find(b => b.offset === this.data.offset + 1);

            let nextItems = null;
            if (existingBoundary) {
                nextItems = existingBoundary.data;
            } else {
                try {
                    this.data.loading = true;
                    nextItems = await this.getItems(this.data.offset, this.data.offsetStartPosition, this.data.offsetEndPositions);
                    this.data.loading = false;

                    if (nextItems.length === 0) {
                        this.trigger('load', this.data);
                        return;
                    }

                } catch (ex) {
                    console.debug(ex.message);
                }
            }

            const previous = this.data.boundaries.find(b => b.offset === this.data.offset);

            if (previous) {
                this.data.items = previous.data.concat(nextItems);
            } else {
                this.data.items = nextItems;
            }

            this.build(nextItems, this.data.offset + 1);
        } else {

            this.data.items = [];

            if (this.data.offset > 0) {
                const previousB = this.data.boundaries.find(b => b.offset === this.data.offset - 1);
                if (previousB) {
                    this.data.items = [...previousB.data];
                }
            }

            this.data.items = this.data.items.concat(d.data);
            this.build(d.data, this.data.offset);
        }
    }

    isLoading() {
        return this.data.loading;
    }

    setItems(items) {
        this.data.items = items
        return this;
    }

    getItems(offset, startPosition = 0, endPosition = 0) {
        return [...this.data.reference].splice(offset * this.data.itemsPerPage, this.data.itemsPerPage) ?? []
    }

    setMaxOffset(maxOffsets) {
        this.options.maxOffsets = maxOffsets;
        return this;
    }

    async render(morph = true, triggerEvents = true) {

        await this.refresh();

        this._tid = 0;
        this._aid = 1;

        this.trigger('preRender');

        const list = document.createElement('div');
        list.classList.add(this.options.containerEl.replace('.', '').replace('#', ''));

        if (this.data.minHeight) {
            list.style.minHeight = `${this.data.minHeight}px`;
        }

        if (this.data.top) {
            list.style.paddingTop = `${this.data.top}px`;
        }

        list.innerHTML = this.renderTemplate(this.options.renderTemplate, this.data, this.context);

        let output = list.outerHTML;
        let container = document.querySelectorAll(this.container);

        if (container === null) {
            return;
        }

        if (this.containerNodeClone === null) {
            this.cloneContainer();
        }

        const clone = this.containerNodeClone;

        if (clone === null) {
            return output;
        }

        clone.innerHTML = output;
        container.forEach((node) => morphdom(node, clone, {childrenOnly: true}));

        if (triggerEvents) {
            this.trigger('render');
        }

        return output;
    }

    build(items, offset = 0) {

        // Set padding-top depending on offset
        if (offset > 1) {
            const b = this.data.boundaries.find(b => b.offset === (offset - 1));
            if (b && b.end > 0) {
                this.data.top = b.start;
            }
        } else {
            // Remove if at top
            this.data.top = 0;
        }

        this.one('render', () => {
            const container = document.querySelector(this.container + ' ' + this.options.containerEl);

            if (this.data.boundaries.findIndex(b => b.offset === offset) === -1 && container) {

                this.data.boundaries.push({
                    offset: offset,
                    start: this.data.containerHeight,
                    end: container.clientHeight,
                    data: items,
                });
            }

            // Keep height so the scrollbar never jumps when going backwards
            if (container) {
                this.data.containerHeight = Math.max(container.clientHeight, this.data.containerHeight);
            }

            this.data.minHeight = (this.data.containerHeight);

            if (container) {
                container.style.minHeight = this.data.minHeight + 'px';
            }
        });
    }

    setContext(widget) {
        this.context = widget;
        return this;
    }

    reset() {
        this.data.reference = [];
        this.data.itemsPerPage = 20;
        this.data.boundaries = [];
        this.data.items = [];
        this.data.loading = true;
        this.data.top = 0;
        this.data.offset = 0;
        this.data.containerHeight = 0;
        this.data.minHeight = null;
        this.data.offsetStartPosition = 0;
        this.data.offsetEndPosition = 0;

        return this;
    }

    setData(data, itemsPerPage = 20) {
        this.data.reference = data;
        this.data.itemsPerPage = itemsPerPage;
        return this;
    }

    getData() {
        return this.data.reference
    }

}

window.PeceeInfiniteList = PeceeInfiniteList;