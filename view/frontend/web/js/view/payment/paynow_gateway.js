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
        const config = window.checkoutConfig.payment,
            paynow_main = 'paynow_gateway',
            paynow_blik = 'paynow_blik_gateway';

        if (config[paynow_main].isActive) {
            rendererList.push(
                {
                    type: 'paynow_gateway',
                    component: 'Paynow_PaymentGateway/js/view/payment/method-renderer/paynow_gateway'
                }
            );
        }

        if (config[paynow_blik].isActive) {
            rendererList.push(
                {
                    type: 'paynow_blik_gateway',
                    component: 'Paynow_PaymentGateway/js/view/payment/paynow_blik_gateway'
                }
            );
        }
        /** Add view logic here if needed */
        return Component.extend({});
    }
);