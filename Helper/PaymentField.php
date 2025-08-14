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
    const BUYER_DEVICE_FINGERPRINT = 'deviceFingerprint';
    const BUYER_EMAIL_FIELD_NAME = 'email';
    const BUYER_EXTERNAL_ID = 'externalId';
    const BUYER_FIELD_NAME = 'buyer';
    const BUYER_FIRSTNAME_FIELD_NAME = 'firstName';
    const BUYER_LASTNAME_FIELD_NAME = 'lastName';
    const BUYER_LOCALE = 'locale';
    const BUYER_ADDRESS_KEY = 'address';
    const BUYER_SHIPPING_ADDRESS_KEY = 'shipping';
    const BUYER_SHIPPING_ADDRESS_STREET = 'street';
    const BUYER_SHIPPING_ADDRESS_HOUSE_NUMBER = 'houseNumber';
    const BUYER_SHIPPING_ADDRESS_APARTMENT_NUMBER = 'apartmentNumber';
    const BUYER_SHIPPING_ADDRESS_ZIPCODE = 'zipcode';
    const BUYER_SHIPPING_ADDRESS_CITY = 'city';
    const BUYER_SHIPPING_ADDRESS_COUNTY= 'county';
    const BUYER_SHIPPING_ADDRESS_COUNTRY = 'country';
    const BUYER_BILLING_ADDRESS_KEY = 'billing';
    const BUYER_BILLING_ADDRESS_STREET = 'street';
    const BUYER_BILLING_ADDRESS_HOUSE_NUMBER = 'houseNumber';
    const BUYER_BILLING_ADDRESS_APARTMENT_NUMBER = 'apartmentNumber';
    const BUYER_BILLING_ADDRESS_ZIPCODE = 'zipcode';
    const BUYER_BILLING_ADDRESS_CITY = 'city';
    const BUYER_BILLING_ADDRESS_COUNTY= 'county';
    const BUYER_BILLING_ADDRESS_COUNTRY = 'country';
    const CONTINUE_URL_FIELD_NAME = 'continueUrl';
    const CURRENCY_FIELD_NAME = 'currency';
	const CART_ID_FIELD_NAME = 'cartId';
    const DESCRIPTION_FIELD_NAME = 'description';
    const EXTERNAL_ID_FIELD_NAME = 'externalId';
    const IDEMPOTENCY_KEY_FIELD_NAME = 'Idempotency-Key';
    const IS_PAYMENT_RETRY_FIELD_NAME = 'isRetry';
    const PAYMENT_ID_FIELD_NAME = 'paymentId';
    const PAYMENT_METHOD_ID = 'paymentMethodId';
    const PAYMENT_METHOD_TOKEN = 'paymentMethodToken';
    const REDIRECT_URL_FIELD_NAME = 'redirectUrl';
    const STATUS_FIELD_NAME = 'status';
    const ORDER_ITEMS = 'orderItems';
    const VALIDITY_TIME = 'validityTime';
    const AUTHORIZATION_CODE = 'authorizationCode';
    const MODIFIED_AT = 'modifiedAt';
    const NOTIFICATION_HISTORY = 'notificationHistory';
}
