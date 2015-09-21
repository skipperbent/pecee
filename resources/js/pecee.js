$p={
	triggers:[],
	doPostback: function(el) {
		var tag=(el.tagName==null) ? el.prop('tagName') : el.tagName;
		var f=(tag.toLowerCase()=='form') ? el : $(el).parents('form');
		f.prepend('<input type="hidden" value="1" name="postback" />');
		if(this.trigger('onPostback', this)===false) {
			return false;
		}
		f.submit();
	},
	trigger: function(name,data) {
		var self=this;
		var result=null;
		$.each(this.triggers, function() {
			if(this!=null && this.fn != null && this.name==name) {
				var f=this.fn;
				result=f(data,self);
			}
		});
		return result;
	},
	bind: function(name, fn) {
		this.triggers.push({'name': name, 'fn': fn});
	},
	live: function(name, fn) {
		var found=false;
		$.each(this.triggers, function() {
			if(this.name==name) {
				this.fn.prototype=fn;
				found=true;
			}
		});
		if(!found) {
			this.triggers.push({'name': name, 'fn': fn, 'live': true});
		}
	}
};