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
                type: 'pensopay_anyday',
                component: 'PensoPay_Payment/js/view/payment/method-renderer/pensopay-anyday'
            },
            {
                type: 'pensopay_mobilepay',
                component: 'PensoPay_Payment/js/view/payment/method-renderer/pensopay-mobilepay'
            },
            {
                type: 'pensopay_dankort',
                component: 'PensoPay_Payment/js/view/payment/method-renderer/pensopay-dankort'
            },
            {
                type: 'pensopay_klarnapayments',
                component: 'PensoPay_Payment/js/view/payment/method-renderer/pensopay-klarnapayments'
            },
            {
                type: 'pensopay_paypal',
                component: 'PensoPay_Payment/js/view/payment/method-renderer/pensopay-paypal'
            },
            {
                type: 'pensopay_vipps',
                component: 'PensoPay_Payment/js/view/payment/method-renderer/pensopay-vipps'
            }
        );
        return Component.extend({});
    }
);