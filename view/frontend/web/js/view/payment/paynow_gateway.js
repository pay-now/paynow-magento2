/*browser:true*/
/*global define*/
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
                type: 'paynow_gateway',
                component: 'Paynow_PaymentGateway/js/view/payment/method-renderer/paynow_gateway'
            }
        );
        rendererList.push(
            {
                type: 'paynow_blik_gateway',
                component: 'Paynow_PaymentGateway/js/view/payment/method-renderer/paynow_blik_gateway'
            }
        );
        /** Add view logic here if needed */
        return Component.extend({});
    }
);