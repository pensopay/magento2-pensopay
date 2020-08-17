define(
    [
        'PensoPay_Payment/js/view/payment/method-renderer/pensopay',
        'jquery'
    ],
    function (Component, $) {
        'use strict';
        return Component.extend({
            getCode: function() {
                return 'pensopay_mobilepay';
            },
            getPaymentMethodExtra: function() {
                return $('.checkout-mobilepay-logos').html();
            }
        });
    }
);