window.morphdom = require('morphdom').default;

$p.widget.template = function (widget) {
    return this.init(widget);
};

$p.widget.template.prototype = {
    guid: null,
    id: null,
    widget: null,
    view: null,
    snippets: null,
    events: [],
    bindings: [],
    bindingsMap: [],
    init: function (widget) {
        this.widget = widget;
        return this;
    },
    triggerIndex: function (name, index, data) {
        return this.trigger(name, data, index);
    },
    trigger: function (name, data = null, index = null) {

        var bindings = this.bindings[name];

        if (typeof bindings === 'undefined') {
            name = this.guid + '_' + name;
            bindings = this.bindings[name];
        }

        if (typeof bindings === 'undefined') {
            throw 'Failed to find binding: ' + name;
        }

        if (index !== null) {
            var bindingIndex = bindings.findIndex((b => b.index === index));
            if (bindingIndex === -1) {
                throw 'Failed to find binding: ' + name;
            }

            bindings = [this.bindings[name][bindingIndex]];
        }

        var output = '';
        var binding = null;

        for (var _i in bindings) {
            binding = bindings[_i];

            var eventData = binding.data;

            // Use custom data and store on binding
            if (data !== null) {
                eventData = data;
                binding.data = eventData;
            }

            // Remove view triggers
            output += binding.callback(eventData);

            this.triggerEvent(name, eventData);

            if (binding.persist === false && $(this.widget.container).find('[data-id=' + binding.el + ']').length === 0) {
                this.bindings[name].splice(_i, 1);
            }
        }

        return output;
    },
    triggerAll: function () {
        var self = this;

        for (var name in this.bindings) {

            if (this.bindings.hasOwnProperty(name) && $.inArray(name, self.bindingsMap) === -1) {

                for (var binding in this.bindings[name]) {
                    if (this.bindings[name][binding].hidden === false) {
                        this.trigger(name);
                    }
                }

                self.bindingsMap.push(name);
                this.triggerAll();
            }
        }
    },
    triggerEvent: async function (name, data) {
        var shortName = '';
        if (name.indexOf('.') > -1) {
            shortName = name.split('.')[0];
        }

        for (var eventName in this.events) {
            if (eventName === name || eventName === shortName) {
                if (typeof this.events[eventName] !== 'undefined' && this.events[eventName].length > 0) {
                    for (var i = 0; i < this.events[eventName].length; i++) {
                        this.events[eventName][i](data);
                    }
                }
            }
        }
    },
    on: function (name, callback) {

        name = this.guid + '_' + name;
        if (typeof this.events[name] === 'undefined') {
            this.events[name] = [];
        }

        this.events[name].push(callback);
    },
    off: function (name) {
        delete this.events[name];
    },
    clear: function () {

        //this.view = null;
        this.events = [];
        this.bindingsMap = [];
        this.widget = null;
        this.snippets = null;
        this.bindings = [];
        return this;
    },
    binding: function (object) {

        if (typeof this.bindings[object.id] === 'undefined') {
            this.bindings[object.id] = [object];
        } else {

            var existingIndex = this.bindings[object.id].findIndex((b => b.index === object.index && (b.id === object.id && b.hash === object.hash)));

            if (existingIndex === -1) {
                this.bindings[object.id].push(object);
            } else {
                this.bindings[object.id][existingIndex] = object;
            }
        }

        return object.callback(object.data, false);
    }
};

$p.Widget = function (template, container) {

    //this.guid = this.utils.generateGuid();
    this.clear();
    template.clear();
    this.template = $.extend({}, template);
    //this.template.guid = this.guid;

    this.container = container;
    this.template.guid = this.guid;

    this.init();
    this.template.init(this);
    return this;
};

$p.Widget.windows = [];

$p.Widget.extend = function (object) {
    for (var key in object) {
        this[key] = object[key];
    }
    return this;
};

$p.getWidget = function (g) {
    return site.Widget.windows[g];
};

$p.Widget.prototype = {
    windows: [],
    triggersCount: 0,
    newDate: new Date(),
    guid: null,
    template: null,
    container: null,
    data: {},
    events: [],
    w: null,
    init: function (template, container) {
        this.w = this;
        this.template.widget = this;
        //site.Widget.windows[this.guid] = this;
        if (typeof window.triggers === 'undefined') {
            window.triggers = [];
        }

        window.trigger = function (key, context) {
            var eventKey = window.triggers.findIndex((e => e.key === key));
            if (eventKey > -1) {
                return window.triggers[eventKey].callback(context);
            }

            return false;
        };

        var triggerCleanTimer = null;
        var self = this;

        this.bind('rendered', function (view) {
            clearTimeout(triggerCleanTimer);
            setTimeout(function () {
                self.utils.arrayAsync(window.triggers, function (trigger, index, array) {
                    if ($('[data-' + trigger.event + '="' + trigger.id + '"]').length === 0) {
                        array.splice(index, 1);
                    }
                });
            }, 100);
        });

        return this;
    },
    clear: function () {
        this.template = null;
        this.triggers = [];
        this.guid = this.utils.generateGuid();
        this.container = null;
        this.data = {};
        this.events = [];
        this.triggersCount = 0;
    },
    extend: function (object) {
        for (var key in object) {
            this[key] = object[key];
        }
        return this;
    },
    getWidget: function (g) {
        return site.Widget.windows[g];
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

        this.trigger('preRender');
        this.template.bindings = [];

        morphdom($(this.container).get(0), $(this.container).clone(true).html(this.template.view(this.data, this.guid, this)).get(0));

        this.template.triggerAll();
        this.template.bindingsMap = [];

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
    t: function (event) {
        event.key = 't_' + this.guid + this.triggersCount;

        window.triggers.push(event);
        this.triggersCount++;
        return 'window.trigger(\'' + event.key + '\', this);';
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