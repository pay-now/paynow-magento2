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
            paynow_blik = 'paynow_blik_gateway',
            paynow_pbl = 'paynow_pbl_gateway',
            paynow_digital_wallet = 'paynow_digital_wallet_gateway',
            paynow_card = 'paynow_card_gateway';


        if (config[paynow_blik].isActive) {
            rendererList.push(
                {
                    type: 'paynow_blik_gateway',
                    component: 'Paynow_PaymentGateway/js/view/payment/paynow_blik_gateway'
                }
            );
        }

        if (config[paynow_pbl].isActive) {
            rendererList.push(
                {
                    type: 'paynow_pbl_gateway',
                    component: 'Paynow_PaymentGateway/js/view/payment/paynow_pbl_gateway'
                }
            );
        }

        if (config[paynow_card].isActive) {
            rendererList.push(
                {
                    type: 'paynow_card_gateway',
                    component: 'Paynow_PaymentGateway/js/view/payment/paynow_card_gateway'
                }
            );
        }

        if (config[paynow_digital_wallet].isActive) {
            rendererList.push(
                {
                    type: 'paynow_digital_wallet_gateway',
                    component: 'Paynow_PaymentGateway/js/view/payment/paynow_digital_wallet_gateway'
                }
            );
        }

        if (config[paynow_main].isActive) {
            rendererList.push(
                {
                    type: 'paynow_gateway',
                    component: 'Paynow_PaymentGateway/js/view/payment/method-renderer/paynow_gateway'
                }
            );
        }

        /** Add view logic here if needed */
        return Component.extend({
            initialize: function () {
                this._super();
                let applePayEnabled = false;

                if (window.ApplePaySession) {
                    applePayEnabled = window.ApplePaySession.canMakePayments();
                }

                document.cookie = 'applePayEnabled=' + (applePayEnabled ? '1' : '0');
            }
        });
    }
);