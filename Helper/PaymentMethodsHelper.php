<?php

namespace Paynow\PaymentGateway\Helper;

use Magento\Customer\Model\Session;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Quote\Model\Quote;
use Paynow\Exception\PaynowException;
use Paynow\Model\PaymentMethods\AuthorizationType;
use Paynow\Model\PaymentMethods\PaymentMethod;
use Paynow\Model\PaymentMethods\Type;
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

    /**
     * @var Quote
     */
    private $quote;

    /**
     * @var Session
     */
    private $customerSession;

    public function __construct(
        PaymentHelper $paymentHelper,
        Logger $logger,
        ConfigHelper
        $configHelper,
        Quote $quote,
        Session $customerSession
    ) {
        $this->paymentHelper = $paymentHelper;
        $this->logger        = $logger;
        $this->configHelper  = $configHelper;
        $this->quote         = $quote;
        $this->customerSession = $customerSession;
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
            $idempotencyKey = KeysGenerator::generateIdempotencyKey(KeysGenerator::generateExternalIdFromQuoteId($this->quote->getId()));
            $customerId = $this->customerSession->getCustomer()->getId();
            $buyerExternalId = $customerId ? $this->paymentHelper->generateBuyerExternalId($customerId) : null;
            $applePayEnabled = htmlspecialchars($_COOKIE['applePayEnabled'] ?? '0') === '1';
            $methods      = $payment->getPaymentMethods($currency, $amount, $applePayEnabled, $idempotencyKey, $buyerExternalId)->getAll();
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
			$this->logger->error(
				$exception->getMessage(),
				[
					'service' => 'Payment',
					'action' => 'getPaymentMethods',
					'currency' => $currency,
					'amount' => $amount,
					'code' => $exception->getCode(),
				]
			);
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
            $idempotencyKey = KeysGenerator::generateIdempotencyKey(KeysGenerator::generateExternalIdFromQuoteId($this->quote->getId()));
            $customerId = $this->customerSession->getCustomer()->getId();
            $buyerExternalId = $customerId ? $this->paymentHelper->generateBuyerExternalId($customerId) : null;
            $paymentMethods = $payment->getPaymentMethods($currency, $amount, false, $idempotencyKey, $buyerExternalId)->getOnlyBlik();

            if (! empty($paymentMethods)) {
                return $paymentMethods[0];
            }
        } catch (PaynowException $exception) {
			$this->logger->error(
				$exception->getMessage(),
				[
					'service' => 'Payment',
					'action' => 'getPaymentMethods',
					'paymentMethod' => 'BLIK',
					'currency' => $currency,
					'amount' => $amount,
					'code' => $exception->getCode(),
				]
			);
        }

        return null;
    }

    /**
     * Returns payment methods array
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
            $payment        = new Payment($this->paymentHelper->initializePaynowClient());
            $amount         = $this->paymentHelper->formatAmount($amount);
            $idempotencyKey = KeysGenerator::generateIdempotencyKey(KeysGenerator::generateExternalIdFromQuoteId($this->quote->getId()));
            $customerId = $this->customerSession->getCustomer()->getId();
            $buyerExternalId = $customerId ? $this->paymentHelper->generateBuyerExternalId($customerId) : null;
            $paymentMethods = $payment->getPaymentMethods($currency, $amount, false, $idempotencyKey, $buyerExternalId)->getOnlyCards();

            if (!empty($paymentMethods)) {
                return $paymentMethods[0];
            }
        } catch (PaynowException $exception) {
            $this->logger->error(
				$exception->getMessage(),
				[
					'service' => 'Payment',
					'action' => 'getPaymentMethods',
					'paymentMethod' => 'card',
					'currency' => $currency,
					'amount' => $amount,
					'code' => $exception->getCode(),
				]
			);
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
			$idempotencyKey = KeysGenerator::generateIdempotencyKey(KeysGenerator::generateExternalIdFromQuoteId($this->quote->getId()));
			$customerId = $this->customerSession->getCustomer()->getId();
			$buyerExternalId = $customerId ? $this->paymentHelper->generateBuyerExternalId($customerId) : null;
            $amount = $this->paymentHelper->formatAmount($amount);
			$methods = $payment->getPaymentMethods($currency, $amount, false, $idempotencyKey, $buyerExternalId)->getOnlyPbls();

			$paymentMethodsArray = [];
			foreach ($methods ?? [] as $paymentMethod) {
				$paymentMethodsArray[] = [
					'id'          => $paymentMethod->getId(),
					'name'        => $paymentMethod->getName(),
					'description' => $paymentMethod->getDescription(),
					'image'       => $paymentMethod->getImage(),
					'enabled'     => $paymentMethod->isEnabled()
				];
			}

            return $paymentMethodsArray;
        } catch (PaynowException $exception) {
			$this->logger->error(
				$exception->getMessage(),
				[
					'service' => 'Payment',
					'action' => 'getPaymentMethods',
					'paymentMethod' => 'pbl',
					'currency' => $currency,
					'amount' => $amount,
					'code' => $exception->getCode(),
				]
			);
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
            return [];
        }

        try {
            $payment = new Payment($this->paymentHelper->initializePaynowClient());
            $amount = $this->paymentHelper->formatAmount($amount);
			$idempotencyKey = KeysGenerator::generateIdempotencyKey(KeysGenerator::generateExternalIdFromQuoteId($this->quote->getId()));
			$customerId = $this->customerSession->getCustomer()->getId();
			$buyerExternalId = $customerId ? $this->paymentHelper->generateBuyerExternalId($customerId) : null;
			$applePayEnabled = htmlspecialchars($_COOKIE['applePayEnabled'] ?? '0') === '1';
            $paymentMethods = $payment->getPaymentMethods($currency, $amount, $applePayEnabled, $idempotencyKey, $buyerExternalId)->getAll();
            $digitalWalletsPaymentMethods = [];
            if (! empty($paymentMethods)) {
                foreach ($paymentMethods as $item) {
                    if (Type::GOOGLE_PAY === $item->getType() || Type::APPLE_PAY === $item->getType()) {
                        $digitalWalletsPaymentMethods[] = [
							'id'          => $item->getId(),
							'name'        => $item->getName(),
							'description' => $item->getDescription(),
							'image'       => $item->getImage(),
							'enabled'     => $item->isEnabled(),
							'type'		  => $item->getType()
						];
                    }
                }
            }
            return $digitalWalletsPaymentMethods;
        } catch (PaynowException $exception) {
			$this->logger->error(
				$exception->getMessage(),
				[
					'service' => 'Payment',
					'action' => 'getPaymentMethods',
					'paymentMethod' => 'digitalWallets',
					'currency' => $currency,
					'amount' => $amount,
					'code' => $exception->getCode(),
				]
			);
        }
        return [];
    }

    /**
     * Returns Paypo payment method
     *
     * @param string|null $currency
     * @param float|null $amount
     *
     * @return ?PaymentMethod
     * @throws NoSuchEntityException
     */
    public function getPaypoPaymentMethod(?string $currency = null, ?float $amount = null)
    {
        if (!$this->configHelper->isConfigured()) {
            return null;
        }
        try {
            $payment = new Payment($this->paymentHelper->initializePaynowClient());
            $idempotencyKey = KeysGenerator::generateIdempotencyKey(KeysGenerator::generateExternalIdFromQuoteId($this->quote->getId()));
            $customerId = $this->customerSession->getCustomer()->getId();
            $buyerExternalId = $customerId ? $this->paymentHelper->generateBuyerExternalId($customerId) : null;
            $amount = $this->paymentHelper->formatAmount($amount);
            $methods = $payment->getPaymentMethods($currency, $amount, false, $idempotencyKey, $buyerExternalId)->getAll();
            foreach ($methods ?? [] as $paymentMethod) {
                if ($paymentMethod->getType() == Type::PAYPO && $paymentMethod->isEnabled()) {
                    return $paymentMethod;
                }
            }
            return null;
        } catch (PaynowException $exception) {
            $this->logger->error(
                $exception->getMessage(),
                [
                    'service' => 'Payment',
                    'action' => 'getPaymentMethods',
                    'paymentMethod' => 'paypo',
                    'currency' => $currency,
                    'amount' => $amount,
                    'code' => $exception->getCode(),
                ]
            );
        }
        return null;
    }
}
