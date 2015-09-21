var peceeTablist = {
	init: function() {
		
		var visible=$('div.pecee-tablist[data-visible*=true]');
		if(visible.length > 0) {
			$('a.pecee-tablist[data-id="'+visible.data('id')+'"]').addClass('active');
			$('div.pecee-tablist[data-visible*=false]').hide();
		}
		
		$('a.pecee-tablist').live('click',function(e) {
			e.preventDefault();
			var id=$(this).data('id');
			$('div.pecee-tablist').hide();
			$('div.pecee-tablist[data-id="'+id+'"]').show();
			$('a.pecee-tablist.active').removeClass('active');
			$('a.pecee-tablist[data-id="'+id+'"]').addClass('active');
		});
	}	
};

$(document).ready(function() {
	peceeTablist.init();
});