class PeceeTemplate {
    guid = null
    widget = null
    events = []

    constructor(widget) {
        this.widget = widget;
        return this;
    }

    triggerIndex(name, index, data) {
        return this.trigger(name, data, index);
    }

    triggerAppend(name, data = null, index = null) {
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
    }

    trigger(name, data = null, index = null, morph = true) {
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
    }

    triggerEvent(name, data) {
        this.events.filter(e => e.name === name || e.name.split('.')[0] === name).forEach((e, i) => e.callback(data, this, i));
    }

    on(name, callback) {
        name.split(' ').filter(event => event.trim() !== '').forEach(event => this.events.push({
            name: event,
            callback: callback
        }));
        return this;
    }

    off(name) {
        Object.keys(this.events).filter(key => this.events[key].name === name || this.events[key].name.split('.')[0] === name).forEach(key => this.events.splice(parseInt(key), 1));
        return this;
    }

    one(name, callback, mergeData = null) {
        this.events.push({
            name: name + '.one',
            callback: (data = {}, context, index) => {
                this.off(name + '.one');
                if (mergeData !== null) {
                    data = Object.assign(data, mergeData);
                }
                callback(data, context, index);
            }
        });

        return this;
    }

    clear() {
        this.events = [];
        this.widget = null;
        return this;
    }

    setDefaultView() {
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
    }

    addView(object) {

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
                        morphdom(item, clone, {childrenOnly: true});
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
    }

    e(type, callback, viewId = null, view = null, index = null) {
        return this.addEvent(type, callback, viewId, view, index);
    }

    addEvent(type, callback, viewId = null, view = null, index = null) {

        const event = {
            //event: type,
            callback: callback,
            //viewId: viewId,
            //view: view,
            //index: (typeof index === 'undefined') ? null : index,
            id: this.guid + ((viewId === null) ? "" : viewId) + "" + this.widget._tid,
        };

        this.widget._tid++;

        if (event.index === null && event.view !== null) {
            event.view.triggers.push(event);
        } else {
            window.widgets.getView(this.guid, viewId, index).triggers.push(event);
        }

        return "t('" + event.id + "', this);";
    }
}

window.PeceeTemplate = PeceeTemplate;