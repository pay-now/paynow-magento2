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
        additionalValidators,
        url
    ) {
        'use strict';

        return Component.extend({
            defaults: {
                template: 'Paynow_PaymentGateway/payment/paynow_card_gateway',
                instruments: window.checkoutConfig.payment.paynow_card_gateway.instruments,
                paymentMethodToken: null,
                paymentMethodFingerprint: null,
            },
            initialize: function (config) {
                this._super();
                url.setBaseUrl(BASE_URL);

                $(document).on('click', function (e) {
                    if (!$(e.target).is('.paynow-payment-card-remove') && !$(e.target).is('.paynow-payment-card-menu-button')) {
                        $('.paynow-payment-card-remove').addClass('--hidden')
                    }
                });

                this.fetchDeviceFingerprint();
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
            getDefaultCardImagePath: function () {
                return window.checkoutConfig.payment.paynow_card_gateway.defaultCartImage;
            },
            getLogoPath: function () {
                return window.checkoutConfig.payment.paynow_card_gateway.logoPath;
            },
            getRemoveCardErrorMessage: function () {
                return window.checkoutConfig.payment.paynow_card_gateway.removeCardErrorMessage;
            },
            hasInstruments: function () {
                return window.checkoutConfig.payment.paynow_card_gateway.hasInstruments;
            },
            isPaymentMethodActive:function () {
                return this.getCode() === this.isChecked();
            },
            isButtonActive: function () {
                return this.getCode() === this.isChecked();
            },
            getGDPRNotices: function () {
                return window.checkoutConfig.payment.paynow_card_gateway.GDPRNotices;
            },
            setPaymentMethodToken: function (instrument) {
                if (typeof instrument === 'object' && !instrument.isExpired) {
                    this.paymentMethodToken = instrument.token;
                    $('.paynow-payment-option-card').removeClass('active');
                    $('#' + instrument.token).addClass('active');
                } else if (typeof instrument === 'string') {
                    this.paymentMethodToken = null;
                    $('.paynow-payment-option-card').removeClass('active');
                    $('#' + instrument).addClass('active');
                }
            },
            removeInstrument: function (instrument) {
                const cardMethodOption = $('#' + instrument.token);

                cardMethodOption.addClass('loading');
                $.ajax({
                    url: url.build('paynow/payment/removeInstrument'),
                    dataType: 'json',
                    data: {
                        'savedInstrumentToken': instrument.token
                    },
                    success: function (data) {
                        if (data.success === true) {
                            cardMethodOption.remove();
                        } else {
                            cardMethodOption.removeClass('loading');
                            this.showRemoveSavedInstrumentErrorMessage(instrument.token);
                        }
                    },
                    error: function () {
                        cardMethodOption.removeClass('loading');
                        this.showRemoveSavedInstrumentErrorMessage(instrument.token);
                    }
                });
            },
            fetchDeviceFingerprint: function () {
                try {
                    const fpPromise = import('https://static.paynow.pl/scripts/PyG5QjFDUI.min.js')
                        .then(FingerprintJS => FingerprintJS.load())

                    fpPromise
                        .then(fp => fp.get())
                        .then(result => {
                            this.paymentMethodFingerprint = result.visitorId;
                        })
                } catch (e) {
                    console.error('Cannot get fingerprint');
                }
            },
            showRemoveSavedInstrumentErrorMessage: function (instrumentToken) {
                const errorMessageWrapper = $('#wrapper-' + instrumentToken + ' .paynow-payment-card-error');

                errorMessageWrapper.text(this.getRemoveCardErrorMessage());

                setTimeout(() => {
                    errorMessageWrapper.text('');
                }, 5000)
            },
            toggleMiniMenu: function (instrument) {
                $('#' + instrument.token + ' .paynow-payment-card-remove').toggleClass('--hidden');
            },
            getData: function () {
                const paymentMethodId = window.checkoutConfig.payment.paynow_card_gateway.paymentMethodId
                return {
                    'method': this.item.method,
                    'additional_data': {
                        'payment_method_id': paymentMethodId,
                        'payment_method_token': this.paymentMethodToken,
                        'payment_method_fingerprint': this.paymentMethodFingerprint,
                    }
                };
            }
        });
    }
);
