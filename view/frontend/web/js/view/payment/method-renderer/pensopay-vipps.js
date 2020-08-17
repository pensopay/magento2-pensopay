define(
    [
        'PensoPay_Payment/js/view/payment/method-renderer/pensopay',
        'jquery'
    ],
    function (Component, $) {
        'use strict';
        return Component.extend({
            getCode: function() {
                return 'pensopay_vipps';
            },
            getPaymentMethodExtra: function() {
                return $('.checkout-vipps-logos').html();
            }
        });
    }
);