define(['jquery'], function($) {
    function resizeIframe() {
        var $iframe = $('iframe.collabora-iframe');
        if (!$iframe.length) {
            return;
        }
        var viewheight = $(window).height();
        var frametop = $iframe.offset().top;
        var height = viewheight - frametop - 30;
        if (height < 300) {
            height = 300;
        }
        $iframe.attr('height', height);
    }

    return {
        init: function() {
            $(window).on('resize', resizeIframe);
            resizeIframe();
        }
    };
});