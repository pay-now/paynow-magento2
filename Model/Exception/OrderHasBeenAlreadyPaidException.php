<?php

namespace Paynow\PaymentGateway\Model\Exception;

use Exception;

/**
 * Class OrderAlreadyPaidException
 *
 * @package Paynow\PaymentGateway\Model\Exception
 */
class OrderHasBeenAlreadyPaidException extends Exception
{
    const EXCEPTION_MESSAGE = 'An order %s has been already paid in %s.';

    public function __construct($orderId, $paymentId)
    {
        parent::__construct(sprintf(self::EXCEPTION_MESSAGE, $orderId, $paymentId));
    }
}
