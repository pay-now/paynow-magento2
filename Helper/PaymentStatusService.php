<?php

namespace Paynow\PaymentGateway\Helper;

use Paynow\PaymentGateway\Model\Logger\Logger;
use Paynow\Service\Payment;

/**
 * Class PaymentStatusService
 *
 * @package Paynow\PaymentGateway\Helper
 */
class PaymentStatusService
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    public function __construct(
        Logger $logger,
        PaymentHelper $paymentHelper
    ) {
        $this->logger = $logger;
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * @param $paymentId
     * @return string|void
     */
    public function getStatus($paymentId)
    {
        $loggerContext = [PaymentField::PAYMENT_ID_FIELD_NAME => $paymentId];

        try {
            $paymentStatusObject  = (new Payment($this->paymentHelper->initializePaynowClient()))->status($paymentId);
            $status = $paymentStatusObject->getStatus();
            $this->logger->debug(
                "Retrieved status response",
                array_merge($loggerContext, ['status' => $status])
            );

            return $status;
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage(), $loggerContext);
        }
    }
}
