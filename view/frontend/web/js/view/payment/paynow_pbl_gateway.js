define(
    [
        'jquery',
        'Magento_Checkout/js/view/payment/default',
        'Magento_Checkout/js/model/url-builder',
        'Magento_Checkout/js/action/place-order',
        'Magento_Checkout/js/action/select-payment-method',
        'Magento_Customer/js/model/customer',
        'Magento_Checkout/js/checkout-data',
        'Magento_Checkout/js/model/payment/additional-validators',
        'ko',
    ],
    function (
        $,
        Component,
        urlBuilder,
        placeOrderAction,
        selectPaymentMethodAction,
        customer,
        checkoutData,
        additionalValidators,
        ko
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Paynow_PaymentGateway/payment/paynow_pbl_gateway',
                methods: window.checkoutConfig.payment.paynow_pbl_gateway.paymentMethods,
                paymentMethodId: null
            },
            getCode: function () {
                return 'paynow_pbl_gateway';
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
                window.location.replace(window.checkoutConfig.payment.paynow_pbl_gateway.redirectUrl);
            },
            getLogoPath: function () {
                return window.checkoutConfig.payment.paynow_pbl_gateway.logoPath;
            },
            isButtonActive: function () {
                return this.getCode() === this.isChecked();
            },
            setPaymentMethod: function (paymentMethod) {
                if (paymentMethod.enabled) {
                    this.paymentMethodId = paymentMethod.id;
                    $('.paynow-payment-option').removeClass('active');
                    $('#payment_method_' + paymentMethod.id).addClass('active');
                }
            },
            getGDPRNotices: function () {
                return window.checkoutConfig.payment.paynow_pbl_gateway.GDPRNotices;
            },
            getData: function () {
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'payment_method_id': this.paymentMethodId
                    }
                };
            },
        });
    }
);
