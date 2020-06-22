<?php

namespace Paynow\PaymentGateway\Model\Exception;

use Exception;

/**
 * Class OrderNotFound
 *
 * @package Paynow\PaymentGateway\Model\Exception
 */
class OrderNotFound extends Exception
{
    const EXCEPTION_MESSAGE = 'Order for payment not exists %s';

    public function __construct($orderId)
    {
        parent::__construct(sprintf(self::EXCEPTION_MESSAGE, $orderId));
    }
}
