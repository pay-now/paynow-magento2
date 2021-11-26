<?php

namespace Paynow\PaymentGateway\Helper;

use Magento\Framework\Exception\NoSuchEntityException;
use Paynow\Exception\PaynowException;
use Paynow\Model\PaymentMethods\PaymentMethod;
use Paynow\Model\PaymentMethods\Type;
use Paynow\PaymentGateway\Model\Logger\Logger;
use Paynow\Service\DataProcessing;
use Paynow\Service\Payment;

/**
 * Class DataProcessingNotesHelper
 *
 * @package Paynow\PaymentGateway\Helper
 */
class DataProcessingNotesHelper
{
    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ConfigHelper
     */
    private $configHelper;

    public function __construct(PaymentHelper $paymentHelper, Logger $logger, ConfigHelper $configHelper)
    {
        $this->paymentHelper = $paymentHelper;
        $this->logger        = $logger;
        $this->configHelper  = $configHelper;
    }

    public function getNotes()
    {
        $notes = [];
        try {
            $notes  = new DataProcessing($this->paymentHelper->getStoreLocale());
        } catch (PaynowException $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $notes;
    }
}
