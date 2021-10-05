<?php

namespace Paynow\PaymentGateway\Helper;

/**
 * Class PaymentField
 *
 * @package Paynow\PaymentGateway\Helper
 */
class PaymentField
{
    const AMOUNT_FIELD_NAME = 'amount';
    const BUYER_EMAIL_FIELD_NAME = 'email';
    const BUYER_FIELD_NAME = 'buyer';
    const BUYER_FIRSTNAME_FIELD_NAME = 'firstName';
    const BUYER_LASTNAME_FIELD_NAME = 'lastName';
    const BUYER_LOCALE = 'locale';
    const CONTINUE_URL_FIELD_NAME = 'continueUrl';
    const CURRENCY_FIELD_NAME = 'currency';
    const DESCRIPTION_FIELD_NAME = 'description';
    const EXTERNAL_ID_FIELD_NAME = 'externalId';
    const IDEMPOTENCY_KEY_FIELD_NAME = 'Idempotency-Key';
    const PAYMENT_ID_FIELD_NAME = 'paymentId';
    const PAYMENT_METHOD_ID = 'paymentMethodId';
    const REDIRECT_URL_FIELD_NAME = 'redirectUrl';
    const STATUS_FIELD_NAME = 'status';
    const ORDER_ITEMS = 'orderItems';
    const VALIDITY_TIME = 'validityTime';
}
