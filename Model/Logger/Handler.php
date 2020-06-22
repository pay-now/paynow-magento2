<?php

namespace Paynow\PaymentGateway\Model\Logger;

use Magento\Framework\Logger\Handler\Base;

/**
 * Class Handler
 *
 * @package Paynow\PaymentGateway\Model\Logger
 */
class Handler extends Base
{
    /**
     * File name
     *
     * @var string
     */
    protected $fileName = '/var/log/paynow.log';

    /**
     * Logging level
     *
     * @var int
     */
    protected $loggerType = Logger::DEBUG;
}
