if(typeof($p) == 'undefined') {
	var $p = {};
}

$p.facebook = {
	settings: {
		txt:
		{
			permissionsRequired_title: 'Error',
			permissionsRequired_msg: 'We need your Facebook permissions in order to do this action!'
		},
		appId: null,
		onSuccess: null,
		onError: null,
		redirectAuth: false,
		scope: 'publish_stream'
	},
	init: function (options) {
		$.extend(this.settings, options);
		var self = this;
		$(document).ready(function () {
			window.fbAsyncInit = function () {
				if (self.settings.appId == null) {
					throw 'Facebook ApplicationId must be specified!';
				}
				FB.init({ appId: self.settings.appId, cookie: true, xfbml: true, oauth: true });
				FB.Canvas.setAutoGrow();
				/* jQuery event */
				if (jQuery) {
					jQuery(document).trigger('fbAsyncInit');
				}
			};

			(function () {
				var e = document.createElement('script');
				e.async = true;
				e.src = document.location.protocol + '//connect.facebook.net/en_US/all.js';
				document.getElementById('fb-root').appendChild(e);
			} ());
		});
	},
	permissions: {
		check: function (settings, callback) {
			var self = this;
			FB.api('/me/permissions', function (response) {
				var permsArray = response.data[0];
				var permsToPrompt = [];
				var perms = settings.scope.split(',');
				$.each(perms, function () {
					if (permsArray[this] == null) {
						permsToPrompt.push(this);
					}
				});
				if (permsToPrompt.length > 0) {
					if (callback != null) {
						callback(permsToPrompt);
					} else {
						self.prompt(permsToPrompt.join(','), settings);
					}
				} else {
					settings.onSuccess(response);
				}
			});
		},
		prompt: function (perms, settings) {
			var self = this;
			if (settings.redirectAuth) {
				top.location.href = encodeURI('http://www.facebook.com/dialog/oauth/?scope=' + settings.scope + '&client_id=' + settings.appId + '&redirect_uri=' + encodeURI(top.location.href) + '&response_type=code');
				return false;
			}
			FB.login(function (response) {
				if (response.authResponse) {
					self.check(settings, function () {
						settings.onError(response);
					});
				} else {
					settings.onError(response);
				}
			}, { scope: perms });
		}
	},
	login: function (options) {
		var self = this;
		var settings = $.extend(this.settings, options);
		FB.getLoginStatus(function (response) {
			if (response.status === 'connected') {
				self.permissions.check(settings);
			} else {
				self.permissions.prompt(settings.scope, settings);
			}
		}, { scope: settings.scope });
	},
	isAuthenticated: function () {
		return (typeof (FB) != 'undefined' && FB.getAuthResponse() != null);
	},
	setScope: function(scope) {
		this.settings.scope = scope;
	}
};