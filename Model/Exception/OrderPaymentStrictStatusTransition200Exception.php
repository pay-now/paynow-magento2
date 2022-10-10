<?php

namespace Paynow\PaymentGateway\Model\Exception;

use Exception;

/**
 * Class OrderPaymentStrictStatusTransitionException
 *
 * @package Paynow\PaymentGateway\Model\Exception
 */
class OrderPaymentStrictStatusTransition200Exception extends Exception
{
    const EXCEPTION_MESSAGE = 'Order status transition from %s to %s is incorrect - strict to paymentId %s - attempt [%s/%s]';

    public function __construct(
        $orderPaymentStatus,
        $paymentStatus,
        $paymentId,
        $attempt,
        $maxAttempts
    ) {
        parent::__construct(
            sprintf(
                self::EXCEPTION_MESSAGE,
                $orderPaymentStatus,
                $paymentStatus,
                $paymentId,
                $attempt,
                $maxAttempts
            )
        );
    }
}
