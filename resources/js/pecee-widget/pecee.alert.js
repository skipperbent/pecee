class PeceeAlert {
    // Default settings
    static settings = {
        container: 'body',
        type: 'danger',
        timeout: 8000,
        animationTimeout: 150,
        containerTemplate: '<div class="alert-container"></div>',
        alertTemplate: '<div class="alert alert-{{type}} fadein" role="alert">{{message}}</div>',
        stickyClass: 'alert-dismissible',
        showClass: 'show',
        sticky: false,
        closeButtonTemplate: ''
    }

    static close(alert) {

        if (!alert) {
            const alerts = document.querySelectorAll('.js-alert');
            if (alerts) {
                alert = alerts[alerts.length - 1];
            }
        }

        //alert = alert ?? document.querySelector('.js-alert:last-child');
        alert.classList.remove('show');

        setTimeout(() => {
            alert.remove();

            if (document.querySelector('.js-alert') === null) {
                this.closeAll();
            }
        }, this.settings.animationTimeout);
    }

    static closeAll() {
        const ctn = document.querySelector('.js-alert-container');
        if (ctn !== null) {
            ctn.remove();
        }
        return this;
    }

    static render(settings) {
        const alertTemplate = settings.alertTemplate
            .replaceAll('{{type}}', settings.type)
            .replaceAll('{{message}}', settings.message);

        const alert = new DOMParser().parseFromString(alertTemplate, 'text/html').body.firstElementChild;
        alert.classList.add('js-alert');

        let containerTemplate = document.querySelector('.js-alert-container');
        if (containerTemplate === null) {

            containerTemplate = new DOMParser().parseFromString(settings.containerTemplate, 'text/html').body.firstElementChild;
            containerTemplate.classList.add('js-alert-container');

            document.querySelector(settings.container).appendChild(containerTemplate);
        }

        // Click to dismiss
        alert.on('click', () => this.close(alert));

        // Calculate offset
        const drawer = document.querySelector('.column-drawer');
        if (drawer !== null) {
            containerTemplate.setAttribute('style', `bottom: ${drawer.clientHeight + 13}`);
        }

        containerTemplate.appendChild(alert).append(settings.closeButtonTemplate);

        if (settings.sticky) {
            alert.classList.add(settings.stickyClass);
        } else {
            setTimeout(() => this.close(alert), settings.timeout);
        }

        setTimeout(() => alert.classList.add(settings.showClass), settings.animationTimeout);
    }

    static show(message, type = null, settings = {}) {
        settings.message = message;

        if (type !== null) {
            settings.type = type;
        }

        this.render(Object.assign({}, this.settings, settings));
    }

    static showSticky(message, type = null, settings = {}) {
        this.show(message, type, Object.assign({
            sticky: true
        }, settings));
    }
}

export default PeceeAlert;