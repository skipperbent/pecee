window.morphdom = require('morphdom').default;

window.widgets = {
    getViews: function (guid, viewId, index = null) {
        if (typeof this[guid] === 'undefined') {
            throw 'Widget not found';
        }

        if (viewId === null) {
            viewId = guid;
        }

        var views = this[guid].views[viewId];

        if (index !== null) {
            views = views.filter((v => v.index !== null && index !== null && v.index.toString() === index.toString()));
        }

        if (typeof views === 'undefined') {
            throw 'View [' + guid + '][' + viewId + '] not found';
        }

        return views;
    },
    getView: function (guid, viewId = null, index = null) {

        var views = this.getViews(guid, viewId);

        var view = views.find((v => (v.id === viewId || v.id === guid) && (v.index !== null && index !== null && v.index.toString() === index.toString() || index === null)));

        if (typeof view === 'undefined') {
            throw 'View ' + viewId + ' not found ' + index;
        }

        return view;
    },
    removeView(guid, viewId, el) {
        if (typeof this[guid] === 'undefined') {
            throw 'Widget not found';
        }

        var viewIndex = this[guid].views[viewId].findIndex((v => v.el === el));
        this[guid].views[viewId].splice(viewIndex, 1);
    },
    trigger: function (guid, viewId, index, id, data) {

        var trigger;
        if (index === null) {
            trigger = this.getViews(guid, viewId).find((v => v.triggers.find((t => t.id === id)))).triggers.find((t => t.id === id));
        } else {
            trigger = this.getView(guid, viewId, index).triggers.find((t => t.id === id));
        }

        if (typeof trigger !== 'undefined') {
            return trigger.callback(data);
        }

        throw 'Trigger [' + id + '] not found in [g:' + guid + '][v:' + viewId + '][index: ' + index + ']';
    }
};

$p.widget.template = function (widget) {
    return this.init(widget);
};

$p.widget.template.prototype = {
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

        var views = window.widgets.getViews(this.guid, name, index);

        var output = '';

        for (var i = 0; i < views.length; i++) {
            var view = views[i];

            view.triggers = [];

            var eventData = view.data;

            // Use custom data and store on view
            if (data !== null) {
                eventData = data;
                view.data = eventData;
            }

            // Remove view triggers
            output += view.callback(eventData);

            this.triggerEvent(name, eventData);
        }

        return output;
    },
    triggerEvent: function (name, data) {
        var shortName = '';
        if (name.indexOf('.') > -1) {
            shortName = name.split('.')[0];
        }

        var events = typeof this.events[name] !== 'undefined' ? this.events[name] : this.events[shortName];

        if (typeof events !== 'undefined' && events.length > 0) {
            for (var i = 0; i < events.length; i++) {
                events[i](data);
            }
        }
    },
    on: function (name, callback) {
        if (typeof this.events[name] === 'undefined') {
            this.events[name] = [];
        }

        this.events[name].push(callback);
    },
    off: function (name) {
        delete this.events[name];
    },
    clear: function () {

        this.events = [];
        this.widget = null;

        return this;
    },
    setDefaultView: function () {
        // Remove widgets with no association
        for (var guid in window.widgets) {
            if (window.widgets[guid].container === this.widget.container && guid !== this.guid) {
                delete window.widgets[guid];
            }
        }

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
            var existingIndex = window.widgets[this.guid].views[object.id].findIndex((b => b.index === object.index && (b.id === object.id && b.hash === object.hash)));

            if (existingIndex === -1) {
                window.widgets[this.guid].views[object.id].push(object);
            } else {
                window.widgets[this.guid].views[object.id][existingIndex] = object;
            }
        }

        return object.callback(object.data, object.id, object, false);
    },
    addEvent: function (event) {

        if (event.index === null && event.view !== null) {
            event.view.triggers.push(event);
        } else {
            window.widgets.getView(this.guid, event.viewId, event.index).triggers.push(event);
        }

        var viewIdArg = "'" + event.viewId + "'";
        var indexArg = "'" + event.index + "'";
        if (event.viewId === null) {
            viewIdArg = "null";
        }

        if (event.index === null) {
            indexArg = null;
        }

        return "window.widgets.trigger('" + this.guid + "', " + viewIdArg + ", " + indexArg + ", '" + event.id + "', this);";
    },
};

$p.Widget = function (template, container) {

    this.clear();
    template.clear();
    this.template = $.extend({}, template);

    this.container = container;
    this.template.guid = this.guid;

    this.init();
    this.template.init(this);
    return this;
};

$p.Widget.clean = function () {
    for (var guid in window.widgets) {
        var widget = window.widgets[guid];

        if (typeof widget.widget !== 'undefined' && widget.widget.persist === false && $(widget.widget.container).length === 0) {
            delete window.widgets[guid];
        }
    }
};

$p.Widget.prototype = {
    guid: null,
    template: null,
    container: null,
    persist: false,
    data: {},
    events: [],
    init: function (template, container) {
        this.template.widget = this;
        this.template.guid = this.guid;
        this.template.id = this.guid;
        this.template.setDefaultView();

        return this;
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
        for (var key in object) {
            this[key] = object[key];
        }
        return this;
    },
    setData: function (data) {
        this.data = data;
    },
    ajax: function (url, settings) {
        var self = this;
        settings = (typeof settings === 'undefined') ? {} : settings;
        return $.ajax($.extend({
            type: 'GET',
            url: url,
            dataType: 'json',
            success: function (d) {
                self.setData(d);
                self.render();
            }
        }, settings));
    },
    render: function () {

        if ($(this.container).length === 0) {
            return;
        }

        this.template.setDefaultView();

        this.trigger('preRender');

        morphdom($(this.container).get(0), $(this.container).clone(true).html(this.template.view(this.data, this.guid, this, this.guid)).get(0));

        this.trigger('render');
    },
    getData: function () {
        return this.data;
    },
    getRows: function () {
        return this.rows;
    },
    trigger: function (name, data) {
        var self = this;
        $.each(this.events, function () {
            if (this.name === name) {
                data = (data == null) ? self.data : data;
                return this.fn(data, self);
            }
        });
        return null;
    },
    setTemplate: function (template) {
        this.template = template;
        this.template.clear();
        this.template.guid = this.guid;
        this.template.init(this);
    },
    bind: function (name, fn) {
        var self = this;
        var exists = false;

        $.each(this.events, function (i) {
            if (this.name === name) {
                self.events[i].fn = fn;
                exists = true;
                return false;
            }
        });

        if (!exists) {
            this.events.push({'name': name, 'fn': fn});
        }
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

            var x = (a[column] === null) ? '' : a[column];
            var y = (b[column] === null) ? '' : b[column];

            // Guess type
            var typeA = $.type(x);
            var typeB = $.type(y);

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
        var parts = path.split('/');
        var d = (data) ? data : this.data;
        if (!data)
            return null;
        var last = false;
        for (var i = 0; i < parts.length; i++) {
            if (i === (parts.length - 1))
                last = true;
            var p = parts[i];
            var ix = 0;
            if (p.indexOf("[") > -1) {
                var nameIndex = p.split('[');
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
    utils: {
        generateGuid: function () {
            return 'xxxxxxxxxxxx4xxxyxxxxxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
                var r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8);
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
            var index = 0;

            function doChunk() {
                var cnt = chunk;
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