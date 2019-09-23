define(
    [
        'mage/url'
    ],
    function (url) {
        'use strict';

        return {
            /**
             * Provide redirect to page
             */
            execute: function (method) {
                var redirectUrl = window.checkoutConfig.payment[method].redirectUrl;
                window.location.replace(url.build(redirectUrl));
            }
        };
    }
);
