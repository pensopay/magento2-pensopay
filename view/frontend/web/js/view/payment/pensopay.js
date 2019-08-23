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
            }
        );
        return Component.extend({});
    }
);