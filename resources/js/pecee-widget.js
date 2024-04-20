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
    trigger: function (name, data = null, index = null) {
        try {
            let views = window.widgets.getViews(this.guid, name, index);
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
                output += view.callback(eventData);
                this.triggerEvent(name, eventData);
            });

            this.triggerEvent('render', data);

            return output;
        } catch (ex) {

        }

        return '';
    },
    triggerEvent: function (name, data) {
        this.events.filter(e => e.name === name || e.name.split('.')[0] === name).forEach(e => e.callback(data, this));
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
    one: function (name, callback) {
        this.events.push({
            name: name + '.one',
            callback: (data) => {
                this.off(name + '.one');
                callback(data);
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
            let existingIndex = window.widgets[this.guid].views[object.id].findIndex((b => b.index === object.index && (b.id === object.id && b.hash === object.hash)));

            if (existingIndex === -1) {
                window.widgets[this.guid].views[object.id].push(object);
            } else {
                window.widgets[this.guid].views[object.id][existingIndex] = object;
            }
        }

        return object.callback(object.data, object.id, object, false);
    },
    e: function (type, callback, viewId = null, view = null, index = null) {
        return this.addEvent(type, callback, viewId = null, view = null, index = null);
    },
    addEvent: function (type, callback, viewId = null, view = null, index = null) {

        let event = {
            event: type,
            callback: callback,
            viewId: viewId,
            view: view,
            index: (typeof index === 'undefined') ? null : index,
            id: this.guid + viewId + "" + this.widget._tid,
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

window.Widget = function (template, container = null) {

    this.clear();
    template.clear();
    this.template = $.extend({}, template);

    this.container = container;
    this.template.guid = this.guid;
    this.template.widget = this;

    this.init();
    this.template.init(this);
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
    _tid: 0,
    init: function () {
        //this.template.widget = this;
        this.template.guid = this.guid;
        this.template.id = this.guid;

        if (this.container !== null) {
            this.template.setDefaultView();
            this.cloneContainer();
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
        this.template.widget = this;
        this.template.guid = this.guid;
        //this.template.setDefaultView();
    },
    clear: function () {
        this.template = null;
        this.triggers = [];
        this.guid = this.utils.generateGuid();
        this.container = null;
        this.data = {};
        this.events = [];
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

            container.forEach(function (node) {
                clone.innerHTML = output;
                morphdom(node, clone, {childrenOnly: true});
            });

        } else {
            output = this.renderTemplate(this.template, this.data);
        }

        if (triggerEvents) {
            this.trigger('render');
        }

        return output;
    },
    renderTemplate: function (template, data = {}) {
        return template.view(data, this.guid, this, this.guid);
    },
    renderWidget: function (widget, view = null, classes = []) {

        let classHtml = '';

        if (classes.length > 0) {
            classHtml += ' class=' + classes.join(' ') + '"';
        }

        let out = `<div data-id="iw_${widget.guid}"${classHtml}>`;

        widget.setContainer("div[data-id=iw_" + widget.guid + "]");

        if (view !== null) {
            widget.template.one(view.id, () => {
                widget.render();
                widgets.clean();
            });
        } else {
            widget.one("render", () => {
                widget.trigger("render");
                widgets.clean();
            });
        }

        out += widget.render(false, false);
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
        this.events.filter(e => e.name === name || e.name.split('.')[0] === name).forEach(e => {
            e.callback(data, this)
        });
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
    one: function (name, callback) {
        this.events.push({
            name: name + '.one',
            callback: (data) => {
                this.unbind(name + '.one');
                callback(data, this);
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
    onElement: function (element, event, callback) {

        event.split(' ').forEach((e) => {

            e += '.' + this.guid;

            this.elementEvents.push({
                element: element,
                event: e,
            });

            if (typeof element === 'string') {
                $(this.container).find(element).on(e, callback);
            } else {
                $(element).on(e, callback);
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