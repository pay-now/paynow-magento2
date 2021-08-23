<?php

namespace Paynow\PaymentGateway\Helper;

use Paynow\Exception\PaynowException;
use Paynow\Model\PaymentMethods\Type;
use Paynow\PaymentGateway\Model\Logger\Logger;
use Paynow\Service\Payment;

class PaymentMethodsHelper
{
    /**
     *      * @var PaymentHelper
     *           */
    private $paymentHelper;

    /**
     *      * @var Logger
     *           */
    private $logger;

    public function __construct(PaymentHelper $paymentHelper, Logger $logger)
    {
        $this->paymentHelper = $paymentHelper;
        $this->logger        = $logger;
    }

    public function getAvailable()
    {
        $paymentMethodsArray = [];
        try {
            $payment        = new Payment($this->paymentHelper->initializePaynowClient());
            $paymentMethods = $payment->getPaymentMethods()->getAll();

            foreach ($paymentMethods as $key => $paymentMethod) {
                if (! (Type::BLIK === $paymentMethod->getType() && $this->paymentHelper->isBlikActive())) {
                    $paymentMethodsArray[] = [
                        'id'          => $paymentMethod->getId(),
                        'name'        => $paymentMethod->getName(),
                        'description' => $paymentMethod->getDescription(),
                        'image'       => $paymentMethod->getImage(),
                        'enabled'   => $paymentMethod->isEnabled()
                    ];
                }
            }
        } catch (PaynowException $exception) {
            $this->logger->error($exception->getMessage());
        }

        return $paymentMethodsArray;
    }
}
