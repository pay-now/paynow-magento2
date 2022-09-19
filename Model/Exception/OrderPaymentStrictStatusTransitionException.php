<?php

namespace Paynow\PaymentGateway\Model\Exception;

use Exception;

/**
 * Class OrderPaymentStrictStatusTransitionException
 *
 * @package Paynow\PaymentGateway\Model\Exception
 */
class OrderPaymentStrictStatusTransitionException extends Exception
{
    const EXCEPTION_MESSAGE = 'Order status transition from %s to %s is incorrect - strict to paymentId %s';

    public function __construct($orderPaymentStatus, $paymentStatus, $paymentId)
    {
        parent::__construct(sprintf(self::EXCEPTION_MESSAGE, $orderPaymentStatus, $paymentStatus,$paymentId));
    }
}
