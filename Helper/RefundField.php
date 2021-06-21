<?php

namespace Paynow\PaymentGateway\Helper;

/**
 * Class PaymentField
 *
 * @package Paynow\PaymentGateway\Helper
 */
class RefundField
{
    const AMOUNT_FIELD_NAME = 'amount';
    const IDEMPOTENCY_KEY_FIELD_NAME = 'Idempotency-Key';
    const REFUND_ID_FIELD_NAME = 'refundId';
    const STATUS_FIELD_NAME = 'status';
}
