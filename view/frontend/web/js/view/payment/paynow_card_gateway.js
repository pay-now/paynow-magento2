define(
    [
        'jquery',
        'ko',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/url-builder',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/payment/additional-validators',
        'mage/url'
    ],
    function (
        $,
        ko,
        Component,
        urlBuilder,
        placeOrderAction,
        selectPaymentMethodAction,
        customer,
        checkoutData,
        additionalValidators
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Paynow_PaymentGateway/payment/paynow_card_gateway'
            },
            getCode: function () {
                return 'paynow_card_gateway';
            },
            placeOrder: function (data, event) {
                if (event) {
                    event.preventDefault();
                }
                var self = this,
                    placeOrder,
                    emailValidationResult = customer.isLoggedIn(),
                    loginFormSelector = 'form[data-role=email-with-possible-login]';
                if (!customer.isLoggedIn()) {
                    $(loginFormSelector).validation();
                    emailValidationResult = Boolean($(loginFormSelector + ' input[name=username]').valid());
                }
                if (emailValidationResult && this.validate() && additionalValidators.validate()) {
                    this.isPlaceOrderActionAllowed(false);
                    placeOrder = placeOrderAction(this.getData(), false, this.messageContainer);
                    $.when(placeOrder).fail(function () {
                        self.isPlaceOrderActionAllowed(true);
                    }).done(this.afterPlaceOrder.bind(this));
                    return true;
                }
                return false;
            },
            selectPaymentMethod: function () {
                selectPaymentMethodAction(this.getData());
                checkoutData.setSelectedPaymentMethod(this.item.method);
                return true;
            },
            afterPlaceOrder: function () {
                window.location.replace(window.checkoutConfig.payment.paynow_card_gateway.redirectUrl);

            },
            getLogoPath: function () {
                return window.checkoutConfig.payment.paynow_card_gateway.logoPath;
            },
            isPaymentMethodActive:function () {
                return this.getCode() === this.isChecked();
            },
            isButtonActive: function () {
                return true
            },
            getGDPRNotices: function () {
                return window.checkoutConfig.payment.paynow_card_gateway.GDPRNotices;
            },
            getData: function () {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'payment_method_id': window.checkoutConfig.payment.paynow_card_gateway.paymentMethodId,
                    }
                };
            }
        });
    }
);
