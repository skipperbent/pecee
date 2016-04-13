if(typeof($p)=='undefined') {
    var $p={};
}

$p.utils = {
    generateGuid: function(length) {
        var c=(length==null)?8:length;
        var text = "";
        var possible = "ABCDEFGHIJKLMNOPQRSTUVWXYZabcdefghijklmnopqrstuvwxyz0123456789";
        for(var i=0;i<c;i++) {
            text += possible.charAt(Math.floor(Math.random() * possible.length));
        }
        return text;
    }
};

$p.template = function() {
    return this;
};

$p.template.prototype = {
    widget: null,
    view: null,
    snippets: null,
    bindings: {},
    bindingsMap: [],
    init: function(widget) {

        var self = this;
        this.widget = widget;
    },
    trigger: function(name, data) {
        var binding = this.bindings[name];
        if(binding == null) {
            throw 'Failed to find binding: ' + name;
        }
        data = (data == null) ? binding.data : data;
        binding.callback(data);
    },
    triggerAll: function() {
        var self = this;
        for (var name in this.bindings) {
            if (this.bindings.hasOwnProperty(name) && $.inArray(name, self.bindingsMap) == -1) {
                this.trigger(name);
                self.bindingsMap.push(name);
                this.triggerAll();
            }
        }
    },
    clear: function() {
        this.bindingsMap = [];
    }
};

$p.Widget=function(template, container) {

    this.guid = $p.utils.generateGuid();
    this.template = template;

    this.container = container;
    $p.Widget.windows[this.guid] = this;
    this.data = null;
    this.events = [];

    this.template.init(this);
    return this;
};

$p.Widget.windows = {};

$p.getWidget = function(g) {
    return $p.Widget.windows[g];
};

$p.Widget.prototype = {
    newDate: new Date,
    guid: null,
    template: null,
    container: null,
    data: {},
    events: [],
    setData: function(data) {
        this.data = data;
    },
    setJSON: function(url) {
        var c = this;
        $.ajax({
            type: 'GET',
            url: url,
            dataType: 'json',
            success: function(d) {
                c.setData(d);
            },
            async: false
        });
    },
    render: function() {
        this.trigger('preRender');
        $(this.container).html(this.template.view(this.data, this.guid));

        this.template.triggerAll();
        this.template.clear();

        this.trigger('render');
    },
    getData: function() {
        return this.data;
    },
    getRows: function() {
        return this.rows;
    },
    trigger: function(name, data) {
        var self = this;
        $.each(this.events, function() {
            if(this.name==name) {
                data = (data == null) ? self.data : data;
                return this.fn(data, this);
            }
        });
        return null;
    },
    bind: function(name, fn) {
        var self = this;
        var exists = false;

        $.each(this.events, function(i) {
            if(this.name == name) {
                self.events[i].fn = fn;
                exists = true;
                return;
            }
        });

        if(!exists) {
            this.events.push({'name': name, 'fn': fn});
        }
    }
};

$p.WidgetList = $p.Widget;

$p.WidgetList.prototype = $.extend($p.Widget.prototype, {
    setData: function(data) {
        this.data = data;
        if(this.data.rows != null) {
            this.rows = this.data.rows;
            /* Preset variables */
            this.data.currentPageIndex = (this.data.currentPageIndex!=null) ? parseInt(this.data.currentPageIndex) : 0;
            this.data.totalRows = (this.data.numRows!=null) ? parseInt(this.data.numRows) : this.data.rows.length;
            this.data.rowsPerPage = (this.data.rowsPerPage==null) ? this.data.totalRows : parseInt(this.data.rowsPerPage);
            this.data.totalPages = (this.data.maxRows && this.data.rowsPerPage) ? Math.ceil(parseInt(this.data.maxRows)/this.data.rowsPerPage) : 0;
        }
    },
    getRow:function(path,value) {
        for(var i = 0; i < this.data.rows.length;i++) {
            var v = this.getDataByPath(path, this.data.rows[i]);
            if (value == v) return this.data.rows[i];
        }
        return null;
    },
    removeRow:function(path,value) {
        for(var i = 0; i < this.data.rows.length;i++) {
            var v = this.getDataByPath(path, this.data.rows[i]);
            if (value == v) {
                this.data.rows.splice(i,1);
            }
        }
        return null;
    },
    addRow:function(row) {
        this.data.rows.push(row);
    },
    setPaging: function(rowsPerPage) {
        this.data.totalPages = Math.ceil(this.data.maxRows/rowsPerPage);
        this.data.rowsPerPage = rowsPerPage;
        this.data.totalRows = (this.data.maxRows > rowsPerPage) ? rowsPerPage : this.data.maxRows;
        this.data.currentPageIndex = 0;
    },
    getPage: function(pageIndex, fn) {
        this.setPage(pageIndex);
        this.render(fn);
        return false;
    },
    setPage: function(pageIndex) {
        var start = this.data.totalRows*pageIndex;
        var end = ((start+this.data.rowsPerPage) > this.data.maxRows) ? this.data.maxRows : (start+this.data.rowsPerPage);
        var newRows = [];
        for(var i=start;i<end;i++) {
            newRows.push(this.rows[i]);
        }
        this.data.totalRows = newRows.length;
        this.data.currentPageIndex = parseInt(pageIndex);
        this.data.rows = newRows;
    },
    getDataByPath: function(path,data) {
        var parts = path.split('/');
        var d = (data) ? data : this.data;
        if (!data)
            return null;
        var last = false;
        for(var i = 0;i < parts.length; i++) {
            if (i == (parts.length-1))
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
    sortArray: function(fieldPath, sortOrder) {
        var first = null;
        var array = this.rows;
        if (array.length > 0) {
            first = this.getDataByPath(fieldPath,array[0]);
        } else {
            return;
        }
        var self = this;
        var type = $.type(first);
        function isValidDate(s) {
            return (!isNaN(new Date(s)));
        }
        if(isValidDate(first)) {
            type='date';
        }
        array.sort(function(x,y) {
            var xValue = self.getDataByPath(fieldPath,x);
            var yValue = self.getDataByPath(fieldPath,y);
            switch(type) {
                default:
                    var out = 1;
                    if(xValue==yValue)
                        out = 0;
                    else {
                        if (sortOrder.toLowerCase() == 'asc') {
                            if(xValue<yValue){ out = -1;}
                        } else {
                            if(xValue>yValue){ out = -1;}
                        }
                    }

                    return out;
                    break;
                case 'number':
                    if(sortOrder.toLowerCase() == 'asc') {
                        return xValue - yValue;
                    }
                    return yValue - xValue;
                    break;
                case 'date':
                    xValue=new Date(xValue).getTime();
                    yValue=new Date(yValue).getTime();
                    if(sortOrder.toLowerCase() == 'asc') {
                        return xValue - yValue;
                    }
                    return yValue - xValue;
                    break;
            }
        });
        this.rows=array;
    },
    setSort: function(fieldPath, sortOrder) {
        this.data.sortOrder = sortOrder.toLowerCase();
        this.sortArray(fieldPath,sortOrder);
        this.getPage(0);
        this.trigger('sort', { fieldPath: fieldPath, sortOrder: sortOrder });
    },
    reset:function() {
        this.data = {
            pageIndex:0,
            totalPages:1,
            rows:[]
        };
    },
    getPageIndex: function() {
        return this.data.currentPageIndex;
    }
});