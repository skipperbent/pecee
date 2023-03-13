require('script-loader!jquery-morphdom/jquery.morphdom');

$p.widget.template = function (widget) {
    this.prototype = $.extend(this, $p.widget.template.class);
    return this.init(widget);
};

$p.widget.template.class = {
    widget: null,
    view: null,
    snippets: null,
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
        binding.callback(data);
    },
    triggerAll: function () {
        var self = this;
        for (var name in this.bindings) {
            if (this.bindings.hasOwnProperty(name) && $.inArray(name, self.bindingsMap) === -1) {
                this.trigger(name);
                self.bindingsMap.push(name);
                this.triggerAll();
            }
        }
    },
    clear: function () {
        this.bindingsMap = [];
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
        $p.Widget.windows[this.guid] = this;

        this.trigger('preRender');

        $(this.container).morphdom(
            $(this.container).clone(true).html(this.template.view(this.data, this.guid, this))
        );
        //$(this.container).html(this.template.view(this.data, this.guid));

        this.template.triggerAll();
        this.template.clear();

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
            if (i == (parts.length - 1))
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
    t: function (callback) {
        var key = " + " + callback + "";
        var name = 't_' + this.utils.hash(key);

        if (this.triggers.indexOf(key) > -1) {
            return name + '(this);';
        }

        this.triggers.push(name);
        window[name] = callback;
        return name + '(this);';
    },
    removeTriggers: function () {
        $.each(this.triggers, function () {
            delete window[this];
        });

        this.triggers = [];
    },
    utils: {
        generateGuid: function () {
            return 'xxxxxxxxxxxx4xxxyxxxxxxxxxxxxxxx'.replace(/[xy]/g, function (c) {
                var r = Math.random() * 16 | 0, v = c === 'x' ? r : (r & 0x3 | 0x8);
                return v.toString(16);
            });
        },
        hash: function (str) {
            var hash = 0;
            for (var i = 0; i < str.length; i++) {
                var code = str.toString().charCodeAt(i);
                hash = ((hash << 5) - hash) + code;
                hash = hash & hash; // Convert to 32bit integer
            }
            return Math.abs(hash);
        }
    }
};