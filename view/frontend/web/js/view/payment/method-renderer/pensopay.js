define(
    [
        'Magento_Checkout/js/view/payment/default',
        'PensoPay_Payment/js/action/redirect-on-success',
        'jquery'
    ],
    function (Component, pensopayRedirect, $) {
        'use strict';
        return Component.extend({
            defaults: {
                template: 'PensoPay_Payment/payment/form',
                paymentReady: false
            },
            redirectAfterPlaceOrder: false,

            /**
             * @return {exports}
             */
            initObservable: function () {
                this._super()
                    .observe('paymentReady');

                return this;
            },

            /**
             * @return {*}
             */
            isPaymentReady: function () {
                return this.paymentReady();
            },

            getCode: function() {
                return 'pensopay';
            },
            getData: function() {
                return {
                    'method': this.item.method,
                };
            },
            afterPlaceOrder: function() {
                pensopayRedirect.execute(this.getCode());
            },
            getPaymentMethodExtra: function() {
                return $('.checkout-pensopay-logos').html();
            }
        });
    }
);