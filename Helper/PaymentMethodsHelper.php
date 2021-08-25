<?php

namespace Paynow\PaymentGateway\Helper;

use Paynow\Exception\PaynowException;
use Paynow\Model\PaymentMethods\PaymentMethod;
use Paynow\Model\PaymentMethods\Type;
use Paynow\PaymentGateway\Model\Logger\Logger;
use Paynow\Service\Payment;

/**
 * Class PaymentMethodsHelper
 *
 * @package Paynow\PaymentGateway\Helper
 */
class PaymentMethodsHelper
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

    /**
     * Returns payment methods array
     * @return array
     */
    public function getAvailable()
    {
        $paymentMethodsArray = [];
        try {
            $payment        = new Payment($this->paymentHelper->initializePaynowClient());
            $paymentMethods = $payment->getPaymentMethods()->getAll();
            $isBlikActive   = $this->configHelper->isBlikActive();

            foreach ($paymentMethods as $key => $paymentMethod) {
                if (! (Type::BLIK === $paymentMethod->getType() && $isBlikActive)) {
                    $paymentMethodsArray[] = [
                        'id'          => $paymentMethod->getId(),
                        'name'        => $paymentMethod->getName(),
                        'description' => $paymentMethod->getDescription(),
                        'image'       => $paymentMethod->getImage(),
                        'enabled'     => $paymentMethod->isEnabled()
                    ];
                }
            }
        } catch (PaynowException $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $paymentMethodsArray;
    }

    /**
     * Returns payment methods array
     * @return PaymentMethod
     */
    public function getBlikPaymentMethod()
    {
        try {
            $payment        = new Payment($this->paymentHelper->initializePaynowClient());
            $paymentMethods = $payment->getPaymentMethods()->getOnlyBlik();

            if (! empty($paymentMethods)) {
                return $paymentMethods[0];
            }
        } catch (PaynowException $exception) {
            $this->logger->error($exception->getMessage());
        }

        return null;
    }
}
