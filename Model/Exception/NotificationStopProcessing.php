<?php

namespace Paynow\PaymentGateway\Model\Exception;

use Exception;

/**
 * Class NotificationStopProcessing
 *
 * @package Paynow\PaymentGateway\Helper\Exception
 */
class NotificationStopProcessing extends Exception
{
    public $logMessage;
    public $logContext;

    /**
     * @param string $message
     * @param array $context
     */
    public function __construct($message, $context)
    {
        $this->logMessage = $message;
        $this->logContext = $context;

        parent::__construct($message);
    }
}
