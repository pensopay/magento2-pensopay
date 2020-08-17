define(
    [
        'PensoPay_Payment/js/view/payment/method-renderer/pensopay',
        'jquery'
    ],
    function (Component, $) {
        'use strict';
        return Component.extend({
            getCode: function() {
                return 'pensopay_klarnapayments';
            },
            getPaymentMethodExtra: function() {
                return $('.checkout-klarnapayments-logos').html();
            }
        });
    }
);