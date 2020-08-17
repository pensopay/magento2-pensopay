define(
    [
        'PensoPay_Payment/js/view/payment/method-renderer/pensopay',
        'jquery'
    ],
    function (Component, $) {
        'use strict';
        return Component.extend({
            getCode: function() {
                return 'pensopay_viabill';
            },
            getPaymentMethodExtra: function() {
                return $('.checkout-viabill-logos').html() + $('.checkout-viabill').html();
            }
        });
    }
);