if (typeof($p) == 'undefined') {
    var $p = {};
}

$p.tablist = {
    init: function () {

        var visible = $('div.pecee-tablist[data-visible*=true]');
        if (visible.length > 0) {
            visible.each(function() {
                var id = $(this).attr('data-id');
                $('a.pecee-tablist[data-id*=' + id + ']').addClass('active');
                $(this).addClass('active').show();
            });
        }

        $('div.pecee-tablist[data-visible*=false]').hide();

        $(document).on('click.tablist', 'a.pecee-tablist', function (e) {
            e.preventDefault();
            var id = $(this).data('id');
            $('div.pecee-tablist.active').removeClass('active').hide();
            $('div.pecee-tablist[data-id*=' + id + ']').addClass('active').show();
            $('a.pecee-tablist.active').removeClass('active');
            $('a.pecee-tablist[data-id*=' + id + ']').addClass('active');
        });
    }
};

$(document).ready(function () {
    $p.tablist.init();
});