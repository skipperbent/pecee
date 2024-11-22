class PeceeHelper {

    static on(event, callback, selector = null) {

        // Contains space (multiple)
        if (event.indexOf(' ').length > 0) {
            event.split(' ').forEach((event) => this.on(event, callback));
            return this;
        }

        const eventObject = {
            event: event,
            callback: callback,
        };

        if (selector !== null) {
            eventObject.callback = (e) => {
                if (e.target && e.target.closest(selector)) {
                    return callback(e);
                }
            };
        }

        this.events.push(eventObject);
        this.addEventListener(event.split('.')[0], eventObject.callback);
        return this;
    }

    static once(event, callback, selector = null) {

        event += '.' + Math.random().toString().split('.')[1];

        return this.on(event, (e) => {
            this.off(event);
            callback(e);
        }, selector);

    }

    static off(event) {

        // Contains space (multiple)
        if (event.indexOf(' ').length > 0) {
            event.split(' ').forEach((event) => this.off(event));
            return this;
        }

        this.events.filter(e => e.event === event || e.event.split('.')[0] === event).forEach((e) => this.removeEventListener(e.event.split('.')[0], e.callback));
        this.events = this.events.filter(e => e.event !== event && e.event.split('.')[0] !== event);

        return this;
    }

    static trigger(event, data = {}) {

        // Contains space (multiple)
        if (event.indexOf(' ').length > 0) {
            return event.split(' ').map((event) => this.trigger(event));
        }

        let eventData = new CustomEvent(event, {
            detail: data
        });

        switch (event) {
            case 'click':
                eventData = new PointerEvent(event);
                break;
        }

        this.dispatchEvent(eventData);

        if (event.indexOf('.') > -1) {
            const events = this.events.filter(e => e.event === event || e.event.split('.')[0] === event);
            if (events.length > 0) {
                return events.map(e => Object.assign({data: e.callback(data) ?? null}, e));
            }
        }
    }

    /**
     * Replace and execute scripts
     * @param html
     */
    static html(html) {
        this.innerHTML = html;

        this.querySelectorAll('script')
            .forEach(oldScriptEl => {
                const newScriptEl = document.createElement('script');

                Array.from(oldScriptEl.attributes).forEach(attr => {
                    newScriptEl.setAttribute(attr.name, attr.value);
                });

                const scriptText = document.createTextNode(oldScriptEl.innerHTML);
                newScriptEl.appendChild(scriptText);
                oldScriptEl.parentNode.replaceChild(newScriptEl, oldScriptEl);
            });
    }
}

/* Add event helpers */
Element.prototype.events = [];
Element.prototype.on = PeceeHelper.on;
Element.prototype.once = PeceeHelper.once;
Element.prototype.off = PeceeHelper.off;
Element.prototype.trigger = PeceeHelper.trigger;
Element.prototype.html = PeceeHelper.html;

Window.prototype.events = [];
Window.prototype.on = PeceeHelper.on;
Window.prototype.once = PeceeHelper.once;
Window.prototype.off = PeceeHelper.off;
Window.prototype.trigger = PeceeHelper.trigger;