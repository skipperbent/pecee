require('script-loader!jquery-morphdom/jquery.morphdom');

$p.widget.template = function (widget) {
    return this.clear().init(widget);
};

$p.widget.template.prototype = {
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
    trigger: function (name, data) {
        var binding = this.bindings[name];
        if (typeof binding === 'undefined') {
            throw 'Failed to find binding: ' + name;
        }
        data = (typeof data === 'undefined') ? binding.data : data;

        // Remove view triggers
        for(var key in window.triggers) {
            var trigger =  window.triggers[key];
            if(trigger.id === name) {
                delete window.triggers[key];
            }
        }

        var output = binding.callback(data);
        this.triggerEvent(name, data);
        return output;
    },
    triggerAll: function () {
        var self = this;
        for (var name in this.bindings) {
            if (this.bindings.hasOwnProperty(name) && $.inArray(name, self.bindingsMap) === -1) {

                if (this.bindings[name].hidden === false) {
                    this.trigger(name);
                }

                self.bindingsMap.push(name);
                this.triggerAll();
            }
        }
    },
    triggerEvent: function(name, data) {
        var shortName = '';
        if(name.indexOf('.') > -1) {
            shortName = name.split('.')[0];
        }

        for(var eventName in this.events) {
            if(eventName === name || eventName === shortName) {
                if(typeof this.events[eventName] !== 'undefined' && this.events[eventName].length > 0) {
                    for(var i = 0; i < this.events[eventName].length; i++) {
                        this.events[eventName][i](data);
                    }
                }
            }
        }
    },
    on: function(name, callback) {
        if(typeof this.events[name] === 'undefined') {
            this.events[name] = [];
        }

        this.events[name].push(callback);
    },
    off: function(name) {
        delete this.events[name];
    },
    clear: function () {
        this.bindingsMap = [];
        this.widget = null;
        this.snippets = null;
        this.bindings = [];
        return this;
    }
};

$p.Widget = function (template, container) {

    this.guid = this.utils.generateGuid();
    this.template = template;

    this.container = container;

    this.reset();
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
    return $p.Widget.windows[g];
};

$p.Widget.prototype = {
    windows: [],
    triggers: [],
    newDate: new Date(),
    guid: null,
    template: null,
    container: null,
    data: {},
    events: [],
    init: function (template, container) {
        return this;
    },
    reset: function () {
        this.data = {};
        this.events = [];
        this.removeTriggers();
    },
    extend: function (object) {
        for (var key in object) {
            this[key] = object[key];
        }
        return this;
    },
    getWidget: function (g) {
        return $p.Widget.windows[g];
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
        this.template.widget = this;
        site.Widget.windows[this.guid] = this;

        // Remove old triggers
        this.removeTriggers();

        this.trigger('preRender');

        this.template.bindings = [];

        $(this.container).morphdom(
            $(this.container).clone(true).html(this.template.view(this.data, this.guid, this))
        );
        //$(this.container).html(this.template.view(this.data, this.guid, this));

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
        this.removeTriggers();
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
    t: function (id, callback) {

        var key = 't_' + this.guid + this.triggers.length;

        var event = {
            id: id,
            key: key,
            callback: callback
        };

        if (window.triggers.findIndex((t => t.key === key && t.id === id)) > -1) {
            return 'window.triggers[\''+ key +'\'].callback(this);';
        }

        this.triggers.push(event);
        window.triggers[key] = event;
        return 'window.triggers[\''+ key +'\'].callback(this);';
    },
    removeTriggers: function () {

        for(var index in this.triggers) {
            var key = this.triggers[index].key;
            delete window.triggers[key];
        }

        this.triggers = [];
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
        }
    }
};