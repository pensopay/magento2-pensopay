define(
    [
        'PensoPay_Payment/js/view/payment/method-renderer/pensopay',
        'jquery'
    ],
    function (Component, $) {
        'use strict';
        return Component.extend({
            getCode: function() {
                return 'pensopay_dankort';
            },
            getPaymentMethodExtra: function() {
                return $('.checkout-dankort-logos').html();
            }
        });
    }
);