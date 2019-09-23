define(
    [
        'uiComponent',
        'Magento_Checkout/js/model/payment/renderer-list'
    ],
    function (
        Component,
        rendererList
    ) {
        'use strict';
        rendererList.push(
            {
                type: 'pensopay',
                component: 'PensoPay_Payment/js/view/payment/method-renderer/pensopay'
            },
            {
                type: 'pensopay_viabill',
                component: 'PensoPay_Payment/js/view/payment/method-renderer/pensopay-viabill'
            },
            {
                type: 'pensopay_mobilepay',
                component: 'PensoPay_Payment/js/view/payment/method-renderer/pensopay-mobilepay'
            }
        );
        return Component.extend({});
    }
);