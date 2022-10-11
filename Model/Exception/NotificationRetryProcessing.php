<?php

namespace Paynow\PaymentGateway\Model\Exception;

use Exception;

/**
 * Class NotificationRetryProcessing
 *
 * @package Paynow\PaymentGateway\Helper\Exception
 */
class NotificationRetryProcessing extends Exception
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
