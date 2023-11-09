<?php

namespace Paynow\PaymentGateway\Helper;

use Magento\Framework\Exception\NoSuchEntityException;
use Paynow\Exception\PaynowException;
use Paynow\Model\PaymentMethods\PaymentMethod;
use Paynow\PaymentGateway\Model\Config\Source\PaymentMethodsToHide;
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
     *
     * @param string|null $currency
     * @param float|null $amount
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getAvailable(?string $currency = null, ?float $amount = null): array
    {
        $paymentMethodsArray = [];
        if (!$this->configHelper->isConfigured()) {
            return $paymentMethodsArray;
        }

        try {
            $payment      = new Payment($this->paymentHelper->initializePaynowClient());
            $amount       = $this->paymentHelper->formatAmount($amount);
            $applePayEnabled = htmlspecialchars($_COOKIE['applePayEnabled'] ?? '0') === '1';
            $methods      = $payment->getPaymentMethods($currency, $amount, $applePayEnabled)->getAll();
            $hiddenPaymentMethods = $this->configHelper->getPaymentMethodsToHide();

            foreach ($methods ?? [] as $paymentMethod) {
                if (in_array(PaymentMethodsToHide::PAYMENT_TYPE_TO_CONFIG_MAP[$paymentMethod->getType()] ?? '', $hiddenPaymentMethods, true)) {
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
     * Returns payment method for Blik
     *
     * @param string|null $currency
     * @param float|null $amount
     *
     * @return PaymentMethod
     * @throws NoSuchEntityException
     */
    public function getBlikPaymentMethod(?string $currency = null, ?float $amount = null)
    {
        if (!$this->configHelper->isConfigured()) {
            return null;
        }

        try {
            $payment        = new Payment($this->paymentHelper->initializePaynowClient());
            $amount         = $this->paymentHelper->formatAmount($amount);
            $paymentMethods = $payment->getPaymentMethods($currency, $amount)->getOnlyBlik();

            if (! empty($paymentMethods)) {
                return $paymentMethods[0];
            }
        } catch (PaynowException $exception) {
            $this->logger->error($exception->getMessage());
        }

        return null;
    }

    /**
     * Returns payment method for cards
     *
     * @param string|null $currency
     * @param float|null $amount
     *
     * @return PaymentMethod
     * @throws NoSuchEntityException
     */
    public function getCardPaymentMethod(?string $currency = null, ?float $amount = null)
    {
        if (!$this->configHelper->isConfigured()) {
            return null;
        }

        try {
            $payment = new Payment($this->paymentHelper->initializePaynowClient());
            $amount = $this->paymentHelper->formatAmount($amount);
            $paymentMethods = $payment->getPaymentMethods($currency, $amount)->getOnlyCards();

            if (!empty($paymentMethods)) {
                return $paymentMethods[0];
            }
        } catch (PaynowException $exception) {
            $this->logger->error($exception->getMessage());
        }

        return null;
    }

    /**
     * Returns payment methods array for PBLs
     *
     * @param string|null $currency
     * @param float|null $amount
     *
     * @return PaymentMethod[]
     * @throws NoSuchEntityException
     */
    public function getPblPaymentMethods(?string $currency = null, ?float $amount = null)
    {
        if (!$this->configHelper->isConfigured()) {
            return null;
        }

        try {
            $payment = new Payment($this->paymentHelper->initializePaynowClient());
            $amount = $this->paymentHelper->formatAmount($amount);
            return $payment->getPaymentMethods($currency, $amount)->getOnlyPbls();
        } catch (PaynowException $exception) {
            $this->logger->error($exception->getMessage());
        }
        return null;
    }

    /**
     * Returns payment methods array
     *
     * @param string|null $currency
     * @param float|null $amount
     *
     * @return PaymentMethod[]
     * @throws NoSuchEntityException
     */
    public function getDigitalWalletsPaymentMethods(?string $currency = null, ?float $amount = null)
    {
        if (!$this->configHelper->isConfigured()) {
            return null;
        }

        try {
            $payment = new Payment($this->paymentHelper->initializePaynowClient());
            $amount = $this->paymentHelper->formatAmount($amount);
            $paymentMethods = $payment->getPaymentMethods($currency, $amount)->getOnlyGooglePay();

            return $paymentMethods;
        } catch (PaynowException $exception) {
            $this->logger->error($exception->getMessage());
        }
        return null;
    }

}
