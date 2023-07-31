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
                template: 'Paynow_PaymentGateway/payment/paynow_blik_gateway'
            },
            blikCodeValue: ko.observable(''),
            getCode: function () {
                return 'paynow_blik_gateway';
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
                        self.blikCodeValue('');
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
                if(this.isWhiteLabelEnabled()){
                    window.location.replace(window.checkoutConfig.payment.paynow_blik_gateway.blikConfirmUrl);
                } else {
                    window.location.replace(window.checkoutConfig.payment.paynow_blik_gateway.redirectUrl);
                }
            },
            getLogoPath: function () {
                return window.checkoutConfig.payment.paynow_blik_gateway.logoPath;
            },
            isPaymentMethodActive:function () {
                return this.getCode() === this.isChecked();
                },
            isButtonActive: function () {
                return this.isWhiteLabelEnabled() ? this.blikCodeValue().length === 6 && !isNaN(this.blikCodeValue()) && parseInt(this.blikCodeValue()) : true},
            getGDPRNotices: function () {
                return window.checkoutConfig.payment.paynow_blik_gateway.GDPRNotices;
            },
            isWhiteLabelEnabled: function () {
                return window.checkoutConfig.payment.paynow_blik_gateway.isWhiteLabel
            },
            getData: function () {
                const blikCode = $('#paynow_blik_code').val();
                const paymentMethodId = window.checkoutConfig.payment.paynow_blik_gateway.paymentMethodId
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'payment_method_id': paymentMethodId,
                        'blik_code': blikCode
                    }
                };
            }
        });
    }
);
