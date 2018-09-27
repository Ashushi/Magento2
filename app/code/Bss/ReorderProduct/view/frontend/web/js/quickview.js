define(["jquery"], function ($) {
    $(document).ready(function () {
        var documentPadding = 50;
        var firstAttempt = true;
        var lastHeight = 0, curHeight = 0;
        var parentBody = window.parent.document.body;
        $('.mfp-preloader', parentBody).css('display', 'none');
        $('.mfp-iframe-holder .mfp-content', parentBody).css('width', '100%');

        $('.mfp-iframe-scaler iframe', parentBody).animate({'opacity': 1}, 2000);
        $('.reviews-actions a').attr('target', '_parent');
        $('.product-social-links a').attr('target', '_parent');
        $('body').css('overflow', 'hidden');

        setInterval(function () {
            if (firstAttempt) {
                curHeight =  $('.page-wrapper').outerHeight(true) + documentPadding;
            } else {
                curHeight =  $('.page-wrapper').outerHeight(true);
            }
            var documentHeight = curHeight + "px";
            if (curHeight != lastHeight) {
                $('.mfp-iframe-holder .mfp-content', parentBody).animate({
                    'height': documentHeight
                }, 500);
                lastHeight = curHeight;
                firstAttempt = false;
            }
        }, 500);
    });
});