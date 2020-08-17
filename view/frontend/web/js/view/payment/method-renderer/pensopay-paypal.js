define(
    [
        'PensoPay_Payment/js/view/payment/method-renderer/pensopay'
    ],
    function (Component) {
        'use strict';
        return Component.extend({
            getCode: function() {
                return 'pensopay_paypal';
            }
        });
    }
);