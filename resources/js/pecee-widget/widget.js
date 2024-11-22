window.morphdom = require('morphdom').default;
require('./pecee-helpers');

if (typeof $p === 'undefined') {
    $p = {};
}

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
            if (widget?.widget?.persist === false && document.querySelector(widget.widget.container) === null) {
                // Remove attached events
                widget.widget.elementEvents.forEach(({element, event}) => {
                    if (typeof element === 'string') {
                        document.querySelector(widget.widget.container + ' ' + element).off(event);
                    } else {
                        document.querySelector(element).off(event);
                    }
                });
                delete window.widgets[guid];
            }
        }
    }
};

class PeceeWidget {

    guid = null
    template = null
    container = null
    containerNodeClone = null
    persist = false
    data = {}
    events = []
    elementEvents = []
    _lists = []
    _tid = 0
    _aid = 1

    utils = {
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

    constructor(template, container = null) {
        this.clear();

        if (template) {
            template.clear();
            this.template = template;
            this.template.guid = this.guid;
            this.template.widget = this;
            this.container = container;

            //this.template.init(this);

            if (container !== null) {
                this.template.setDefaultView();
                this.cloneContainer();
            }
        }

        this.container = container;
    }

    cloneContainer() {
        if (this.container !== null) {
            const container = document.querySelector(this.container);
            if (container !== null) {
                this.containerNodeClone = container.cloneNode(true)
            }
        }
    }

    setContainer(container) {
        this.container = container;
        this.cloneContainer();

        if (this.template !== null) {
            this.template.widget = this;
            this.template.guid = this.guid;
            this.template.setDefaultView();
        }
    }

    clear() {
        this.template = null;
        this.triggers = [];
        this.guid = this.utils.generateGuid();
        this.container = null;
        this.data = {};
        this.events = [];
        this._lists = [];
    }

    extend(object) {
        Object.keys(object).forEach((k => this[k] = object[k]));
        return this;
    }

    setData(data) {
        this.data = data;
    }

    render(morph = true, triggerEvents = true) {

        //this.template.widget = this;

        this._tid = 0;
        this._aid = 1;
        let output = '';

        window.widgets[this.guid].views[this.guid] = [{
            id: this.guid,
            index: null,
            triggers: [],
        }]

        this.trigger('preRender');

        if (morph === true) {
            const container = document.querySelector(this.container);

            if (container === null) {
                return;
            }

            output = this.renderTemplate(this.template, this.data);

            if (this.containerNodeClone === null) {
                return output;
            }

            this.containerNodeClone.innerHTML = output;

            morphdom(container, this.containerNodeClone, {childrenOnly: true});
        } else {
            output = this.renderTemplate(this.template, this.data);
        }

        if (triggerEvents) {
            this.trigger('render');
        }

        return output;
    }

    renderTemplate(template, data = {}, widget = this) {
        return template.view(data, widget.guid, widget, widget.guid);
    }

    renderWidget(widget = null, view = null, classes = []) {

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
    }

    getData() {
        return this.data;
    }

    getRows() {
        return this.rows;
    }

    trigger(name, data) {
        return Promise.all(this.events.filter(e => e.name === name || e.name.split('.')[0] === name).map((e, i) => e.callback(data, this, i)));
    }

    setTemplate(template) {
        this.template = template;
        this.template.clear();
        this.template.guid = this.guid;
        //this.template.init(this);
    }

    bind(name, callback) {
        name.split(' ').filter(event => event.trim() !== '').forEach(event => this.events.push({
            name: event,
            callback: callback
        }));
        return this;
    }

    unbind(name) {
        Object.keys(this.events).filter(key => this.events[key].name === name || this.events[key].name.split('.')[0] === name).forEach(key => this.events.splice(parseInt(key), 1));
        return this;
    }

    one(name, callback, mergeData = null) {
        this.events.push({
            name: name + '.one',
            callback: (data = {}, context, index) => {
                this.unbind(name + '.one');
                if (mergeData !== null) {
                    data = Object.assign(data, mergeData);
                }
                callback(data, context, index);
            }
        });

        return this;
    }

    remove() {
        const ctn = document.querySelector(this.container);
        if (ctn) {
            ctn.innerHTML = '';
        }

        this.events = [];
        this.data = {};
        return this;
    }

    sortArray(column, data, direction) {
        if (column === null || column.trim() === '') {
            return;
        }

        data.sort(function (a, b) {

            let x = (a[column] === null) ? '' : a[column];
            let y = (b[column] === null) ? '' : b[column];

            // Guess type
            let typeA = typeof (x);
            let typeB = typeof (y);

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
    }

    _addList(name, template, options = {}) {
        if (typeof this._lists[name] === 'undefined') {
            this._lists[name] = new PeceeInfiniteList(template, options, '#' + name);
            this._lists[name].setContext(this);
        }

        return this._lists[name];
    }

    _getList(name) {
        return this._lists[name] ?? null;
    }

    getDataByPath(path, data) {
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
            switch (typeof (d[p])) {
                default:
                    d = d[p];
                    break;
                case 'object':
                    if (!last) {
                        d = d[p][ix];
                        break;
                    }
            }
        }
        return d;
    }

    addEvent(eventType, callback) {
        return this.template.addEvent(eventType, callback);
    }

    on(element, event, callback) {
        event.split(' ').forEach((_event) => {
            document.querySelector(element).off(_event).on(_event, (e) => {
                if (document.querySelector(this.container) === null) {
                    document.querySelector(element).off(_event);
                    return;
                }

                callback(e);
            });
        });

        return this;
    }

    onElement(element, event, callback) {

        event.split(' ').forEach((e) => {

            e += '.' + this.guid;

            this.elementEvents.push({
                element: element,
                event: e,
            });

            if (typeof element === 'string') {
                document.querySelector(this.container + ' ' + element).off(e).on(e, callback);
            } else {
                document.querySelector(element).off(e).on(e, callback);
            }
        });

        return this;
    }
}

window.PeceeWidget = PeceeWidget;