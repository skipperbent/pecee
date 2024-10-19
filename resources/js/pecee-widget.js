window.morphdom = require('morphdom').default;

window.t = function (id, data) {
    return window.widgets.trigger(id, data);
};

window.widgets = {
    getViews: function (guid, viewId, index = null) {
        const widget = this[guid]; // Store reference for potential reuse

        if (!widget) {
            throw new Error('Widget not found');
        }

        viewId = viewId ?? guid; // Use nullish coalescing for efficient default value
        let views = widget.views[viewId];

        if (index !== null) {
            views = views.filter(v => v.index !== null && v.index === index); // Simplified condition
        }

        if (!views) {
            throw new Error(`View [${guid}][${viewId}] not found`); // Template literal for clearer error message
        }

        return views;
    },
    getView: function (guid, viewId = null, index = null) {

        let views = this.getViews(guid, viewId);
        let view = views.find((v => (v.id === viewId || v.id === guid) && (v.index !== null && index !== null && v.index.toString() === index.toString() || index === null)));

        if (typeof view === 'undefined') {
            throw Error(`View [${viewId}] not found [${index}]`);
        }

        return view;
    },
    removeView(guid, viewId, el) {
        if (typeof this[guid] === 'undefined') {
            throw 'Widget not found';
        }

        let viewIndex = this[guid].views[viewId].findIndex((v => v.el === el));
        this[guid].views[viewId].splice(viewIndex, 1);
    },
    trigger: function (id, data) {
        const trigger = this.findTrigger(id);

        if (trigger !== null) {
            try {
                return trigger.callback(data);
            } catch (ex) {
                console.error(ex);
            }
        }

        throw 'Trigger [' + id + '] not found';
    },
    findTrigger: function (id) {
        for (const [wk, widget] of Object.entries(widgets)) {
            if (widget.views) {
                for (const [vk, view] of Object.entries(widget.views)) {
                    for (const v of view) {
                        if (v.triggers) {
                            const trigger = v.triggers.find(t => t.id === id);
                            if (trigger) {
                                return trigger;
                            }
                        }
                    }
                }
            }
        }
        return null;
    },
    clean: function () {
        for (const [guid, widget] of Object.entries(window.widgets)) {
            if (widget?.widget?.persist === false && $(widget.widget.container).length === 0) {
                // Remove attached events
                widget.widget.elementEvents.forEach(({element, event}) => {
                    if (typeof element === 'string') {
                        $(widget.widget.container).find(element).off(event);
                    } else {
                        $(element).off(event);
                    }
                });
                delete window.widgets[guid];
            }
        }
    }
};

window.widget.template = function (widget) {
    return this.init(widget);
};

window.widget.template.prototype = {
    guid: null,
    widget: null,
    events: [],
    init: function (widget) {
        this.widget = widget;
        return this;
    },
    triggerIndex: function (name, index, data) {
        return this.trigger(name, data, index);
    },
    triggerAppend: function (name, data = null, index = null) {
        try {
            this.triggerEvent('preRender', data);

            const views = window.widgets.getViews(this.guid, name, index);

            views.forEach((view) => {
                //view.triggers = [];

                let eventData = view.data;

                // Use custom data and store on view
                if (data !== null) {
                    eventData = data;
                    view.data = eventData;
                }

                document.querySelector(`${this.widget.container} [data-id="${view.el}"]`).innerHTML += view.callback(eventData, false);

                // Remove view triggers
                this.triggerEvent(name, eventData);
            });

            this.widget.trigger('render');
            this.triggerEvent('render', data);
        } catch (ex) {
            console.error(ex);
        }
    },
    trigger: function (name, data = null, index = null, morph = true) {
        try {
            this.triggerEvent('preRender', data);

            const views = window.widgets.getViews(this.guid, name, index);

            let output = '';

            views.forEach((view) => {
                view.triggers = [];

                let eventData = view.data;

                // Use custom data and store on view
                if (data !== null) {
                    eventData = data;
                    view.data = eventData;
                }

                // Remove view triggers
                output += view.callback(eventData, morph);
                this.triggerEvent(name, eventData);
            });

            this.triggerEvent('render', data);

            return output;
        } catch (ex) {
            console.error(ex);
        }

        return '';
    },
    triggerEvent: function (name, data) {
        this.events.filter(e => e.name === name || e.name.split('.')[0] === name).forEach((e, i) => e.callback(data, this, i));
    },
    on: function (name, callback) {
        name.split(' ').filter(event => event.trim() !== '').forEach(event => this.events.push({
            name: event,
            callback: callback
        }));
        return this;
    },
    off: function (name) {
        Object.keys(this.events).filter(key => this.events[key].name === name || this.events[key].name.split('.')[0] === name).forEach(key => this.events.splice(parseInt(key), 1));
        return this;
    },
    one: function (name, callback, mergeData = null) {
        this.events.push({
            name: name + '.one',
            callback: (data, context, index) => {
                this.off(name + '.one');
                if (mergeData !== null) {
                    data = (data === null) ? {} : data;
                    $.extend(data, mergeData);
                }
                callback(data, context, index);
            }
        });

        return this;
    },
    clear: function () {
        this.events = [];
        this.widget = null;
        return this;
    },
    setDefaultView: function () {
        // Remove widgets with no association
        Object.keys(window.widgets).forEach((guid) => {
            if (window.widgets[guid].container === this.widget.container && guid !== this.guid) {
                delete window.widgets[guid];
            }
        });

        window.widgets[this.guid] = {
            container: this.widget.container,
            widget: this.widget,
            views: {}
        };

        if (typeof window.widgets[this.guid].views[this.guid] === 'undefined') {
            window.widgets[this.guid].views[this.guid] = [{
                id: this.guid,
                index: null,
                triggers: [],
            }];
        }
    },
    addView: function (object) {

        object.triggers = [];

        if (typeof window.widgets[this.guid].views[object.id] === 'undefined') {
            window.widgets[this.guid].views[object.id] = [object];
        } else {
            const existingIndex = window.widgets[this.guid].views[object.id].findIndex((b => b.index === object.index && (b.id === object.id && b.hash === object.hash)));

            if (existingIndex === -1) {
                window.widgets[this.guid].views[object.id].push(object);
            } else {
                window.widgets[this.guid].views[object.id][existingIndex] = object;
            }
        }

        // Build view-callback
        object.callback = (as, morph = true, viewId = object.viewId, view = object, replace = true) => {

            if (replace === false) {
                widgets.getViews(this.guid, viewId).find((v => view.index !== null && v.index === view.index || v.index === null && v.hash === view.hash)).triggers = [];
            }

            const o = view.output(as, viewId, view);

            if (morph === false) {
                return o;
            }

            if (replace === true) {
                const nodeSelector = `${this.widget.container} [data-id="${view.el}"]`;
                if (view.morph) {
                    document.querySelectorAll(nodeSelector).forEach((item) => {
                        const clone = item.cloneNode(true);
                        clone.innerHTML = o;
                        morphdom(item, clone);
                    });
                } else {
                    document.querySelectorAll(nodeSelector).forEach(i => i.innerHTML = o);
                }

            } else {

                if (typeof viewId === "undefined") {
                    this.widget.one("render", () => this.widget.template.triggerEvent(viewId, as));
                }

                this.widget.template.one("render", () => this.widget.template.triggerEvent(viewId, as));
            }

            return o;
        };

        return object.callback(object.data, false, object.id, object, false);
    },
    e: function (type, callback, viewId = null, view = null, index = null) {
        return this.addEvent(type, callback, viewId, view, index);
    },
    addEvent: function (type, callback, viewId = null, view = null, index = null) {

        let event = {
            event: type,
            callback: callback,
            viewId: viewId,
            view: view,
            index: (typeof index === 'undefined') ? null : index,
            id: this.guid + ((viewId === null) ? "" : viewId) + "" + this.widget._tid,
        };

        this.widget._tid++;

        if (event.index === null && event.view !== null) {
            event.view.triggers.push(event);
        } else {
            window.widgets.getView(this.guid, event.viewId, event.index).triggers.push(event);
        }

        return "t('" + event.id + "', this);";
    },
};

window.widget.infiniteList = function (renderTemplate, options, container) {
    this.prototype = $.extend(this, new window.Widget(null, container), {
        data: {
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
        },
        options: {
            containerEl: '.vl-ctn',
            scrollElement: window, // window or nodeElement whatever you like
            preloadBufferPixels: 0, // amount of pixels before preload happens
            scrollTimeoutMs: 40,
            renderTemplate: null,
            maxOffsets: -1,
        },
        scrollTimer: null,
        context: null, // Widget context
        init: (options, renderTemplate = null) => {
            $.extend(this.options, options);

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

                $(scrollElement).bind('scroll.list', (event) => {

                    if ($(this.container).length === 0) {
                        return;
                    }

                    // Unbind when container is gone
                    if (this.context && $(this.context.container).length === 0) {
                        $(scrollElement).unbind('scroll.list');
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
        },
        refresh: async () => {

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
        },
        isLoading: () => this.data.loading,
        setItems: (items) => this.data.items = items,
        getItems: (offset, startPosition = 0, endPosition = 0) => {
            return [...this.data.reference].splice(offset * this.data.itemsPerPage, this.data.itemsPerPage) ?? []
        },
        setMaxOffset: (maxOffsets) => this.options.maxOffsets = maxOffsets,
        render: async (morph = true, triggerEvents = true) => {

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
        },
        build: (items, offset = 0) => {

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
        },
        setContext: (widget) => this.context = widget,
        reset: () => {
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
        },
        setData: (data, itemsPerPage = 20) => {
            this.data.reference = data;
            this.data.itemsPerPage = itemsPerPage;
            return this;
        },
        getData: () => this.data.reference,

    });

    this.init(options, renderTemplate);
};

window.Widget = function (template, container = null) {

    this.clear();

    if (template) {
        template.clear();
        this.template = $.extend({}, template);
        this.template.guid = this.guid;
        this.template.widget = this;

        this.template.init(this);
    }

    this.container = container;

    this.init();
    return this;
};

window.Widget.prototype = {
    guid: null,
    template: null,
    container: null,
    containerNodeClone: null,
    persist: false,
    data: {},
    events: [],
    elementEvents: [],
    _lists: [],
    _tid: 0,
    _aid: 1,
    init: function () {
        //this.template.widget = this;
        if (this.template) {
            this.template.guid = this.guid;
            this.template.id = this.guid;

            if (this.container !== null) {
                this.template.setDefaultView();
                this.cloneContainer();
            }
        }

        return this;
    },
    cloneContainer: function () {
        if (this.container !== null) {
            const container = document.querySelector(this.container);
            if (container !== null) {
                this.containerNodeClone = container.cloneNode(true)
            }
        }
    },
    setContainer: function (container) {
        this.container = container;
        this.cloneContainer();

        if (this.template !== null) {
            this.template.widget = this;
            this.template.guid = this.guid;
            //this.template.setDefaultView();
        }
    },
    clear: function () {
        this.template = null;
        this.triggers = [];
        this.guid = this.utils.generateGuid();
        this.container = null;
        this.data = {};
        this.events = [];
        this._lists = [];
    },
    extend: function (object) {
        Object.keys(object).forEach((k => this[k] = object[k]));
        return this;
    },
    setData: function (data) {
        this.data = data;
    },
    ajax: function (url, settings = {}) {
        return $.ajax($.extend({
            type: 'GET',
            url: url,
            dataType: 'json',
            success: (d) => {
                this.setData(d);
                this.render();
            }
        }, settings));
    },
    render: function (morph = true, triggerEvents = true) {

        this.template.widget = this;

        this._tid = 0;
        this._aid = 1;
        let output = '';

        this.template.setDefaultView();
        this.trigger('preRender');

        if (morph === true) {
            let container = document.querySelectorAll(this.container);

            if (container === null) {
                return;
            }

            output = this.renderTemplate(this.template, this.data);

            if (this.containerNodeClone === null) {
                this.cloneContainer();
            }

            const clone = this.containerNodeClone;

            if (clone === null) {
                return output;
            }

            clone.innerHTML = output;
            container.forEach((node) => morphdom(node, clone, {childrenOnly: true}));
        } else {
            output = this.renderTemplate(this.template, this.data);
        }

        if (triggerEvents) {
            this.trigger('render');
        }

        return output;
    },
    renderTemplate: function (template, data = {}, widget = this) {
        return template.view(data, widget.guid, widget, widget.guid);
    },
    renderWidget: function (widget = null, view = null, classes = []) {

        if (widget === null) {
            return '';
        }

        let classHtml = '';

        if (classes.length > 0) {
            classHtml += ' class="' + classes.join(' ') + '"';
        }

        let out = `<div data-id="iw_${widget.guid}"${classHtml}>`;

        widget.setContainer("div[data-id=iw_" + widget.guid + "]");

        if (view !== null && view.morph) {
            this.template.one(view.id, () => {
                widget.render();
                //widgets.clean();
            });
        } else {
            this.one("render", () => {
                widget.render();
                //widgets.clean();
            });
        }

        //out += widget.render(false, false);
        out += '</div>';
        return out;
    },
    getData: function () {
        return this.data;
    },
    getRows: function () {
        return this.rows;
    },
    trigger: function (name, data) {
        return Promise.all(this.events.filter(e => e.name === name || e.name.split('.')[0] === name).map((e, i) => {
            const callback = e.callback(data, this, i);
            if (callback) {
                return callback;
            }
        }));
    },
    setTemplate: function (template) {
        this.template = template;
        this.template.clear();
        this.template.guid = this.guid;
        this.template.init(this);
    },
    bind: function (name, callback) {
        name.split(' ').filter(event => event.trim() !== '').forEach(event => this.events.push({
            name: event,
            callback: callback
        }));
        return this;
    },
    unbind: function (name) {
        Object.keys(this.events).filter(key => this.events[key].name === name || this.events[key].name.split('.')[0] === name).forEach(key => this.events.splice(parseInt(key), 1));
        return this;
    },
    one: function (name, callback, mergeData = null) {
        this.events.push({
            name: name + '.one',
            callback: (data = null, context, index) => {
                this.unbind(name + '.one');
                if (mergeData !== null) {
                    data = (data === null) ? {} : data;
                    $.extend(data, mergeData);
                }
                callback(data, context, index);
            }
        });

        return this;
    },
    remove: function () {
        $(this.container).html('');
        this.events = [];
        this.data = {};
        return this;
    },
    sortArray: function (column, data, direction) {
        if (column === null || column.trim() === '') {
            return;
        }

        data.sort(function (a, b) {

            let x = (a[column] === null) ? '' : a[column];
            let y = (b[column] === null) ? '' : b[column];

            // Guess type
            let typeA = $.type(x);
            let typeB = $.type(y);

            if (typeA === 'number' && typeB === 'number') {
                if (direction === 'asc') {
                    return x - y;
                }
                return y - x;
            }

            if (direction === 'desc') {
                return y.toString().localeCompare(x, 'en', {'sensitivity': 'base'});
            } else {
                return x.toString().localeCompare(y, 'en', {'sensitivity': 'base'});
            }
        });
    },
    _addList: function (name, template, options = {}) {
        if (typeof this._lists[name] === 'undefined') {
            this._lists[name] = new window.widget.infiniteList(template, options, '#' + name);
            this._lists[name].setContext(this);
        }

        return this._lists[name];
    },
    _getList: function (name) {
        return this._lists[name] ?? null;
    },
    getDataByPath: function (path, data) {
        let parts = path.split('/');
        let d = (data) ? data : this.data;
        if (!data)
            return null;
        let last = false;
        for (let i = 0; i < parts.length; i++) {
            if (i === (parts.length - 1))
                last = true;
            let p = parts[i];
            let ix = 0;
            if (p.indexOf("[") > -1) {
                let nameIndex = p.split('[');
                p = nameIndex[0];
                ix = parseInt(nameIndex);
            }
            switch ($.type(d[p])) {
                default:
                    d = d[p];
                    break;
                case 'array':
                    if (!last) {
                        d = d[p][ix];
                        break;
                    }
            }
        }
        return d;
    },
    addEvent: function (eventType, callback) {
        return this.template.addEvent(eventType, callback);
    },
    on: function (element, event, callback) {
        event.split(' ').forEach((_event) => {
            $(element).off(_event).on(_event, (e) => {
                if ($(this.container).length === 0) {
                    $(element).off(_event);
                    return;
                }

                callback(e);
            });
        });

        return this;
    },
    onElement: function (element, event, callback) {

        event.split(' ').forEach((e) => {

            e += '.' + this.guid;

            this.elementEvents.push({
                element: element,
                event: e,
            });

            if (typeof element === 'string') {
                $(this.container).find(element).off(e).on(e, callback);
            } else {
                $(element).off(e).on(e, callback);
            }
        });

        return this;
    },
    utils: {
        generateGuid: function () {
            return 'xxxxxxxxxxxx4xxxyxxxxxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
                let r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        },
        hash: (str, seed = 0) => {
            let h1 = 0xdeadbeef ^ seed,
                h2 = 0x41c6ce57 ^ seed;
            for (let i = 0, ch; i < str.length; i++) {
                ch = str.charCodeAt(i);
                h1 = Math.imul(h1 ^ ch, 2654435761);
                h2 = Math.imul(h2 ^ ch, 1597334677);
            }

            h1 = Math.imul(h1 ^ (h1 >>> 16), 2246822507) ^ Math.imul(h2 ^ (h2 >>> 13), 3266489909);
            h2 = Math.imul(h2 ^ (h2 >>> 16), 2246822507) ^ Math.imul(h1 ^ (h1 >>> 13), 3266489909);

            return 4294967296 * (2097151 & h2) + (h1 >>> 0);
        },
        arrayAsync: function (array, fn, chunk = 100, context) {
            context = context || window;
            let index = 0;

            function doChunk() {
                let cnt = chunk;
                while (cnt-- && index < array.length) {
                    // callback called with args (value, index, array)
                    fn.call(context, array[index], index, array);
                    ++index;
                }
                if (index < array.length) {
                    // set Timeout for async iteration
                    setTimeout(doChunk, 1);
                }
            }

            doChunk();
        }
    }
};