<?php

namespace Paynow\PaymentGateway\Helper;

use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Paynow\PaymentGateway\Model\Ui\DefaultConfigProvider;

/**
 * Class ConfigHelper
 *
 * @package Paynow\PaymentGateway\Helper
 */
class ConfigHelper extends AbstractHelper
{
    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @param Context $context
     * @param EncryptorInterface $encryptor
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(Context $context, EncryptorInterface $encryptor, StoreManagerInterface $storeManager)
    {
        parent::__construct($context);
        $this->encryptor = $encryptor;
        $this->storeManager = $storeManager;
    }

    /**
     * Returns is Test mode enabled
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function isTestMode(?int $storeId = null): bool
    {
        return $this->getConfigData('test_mode', DefaultConfigProvider::CODE, $storeId, true);
    }

    /**
     * Returns is extra logs enabled
     *
     * @param int|null $storeId
     *
     * @return bool
     */
    public function extraLogsEnabled(?int $storeId = null): bool
    {
        return $this->getConfigData('extra_logs', DefaultConfigProvider::CODE, $storeId, true);
    }

    /**
     * Returns is module enabled
     *
     * @param int|null $storeId
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isActive(?int $storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        return $this->getConfigData('active', DefaultConfigProvider::CODE, $storeId, true);
    }

    /**
     * Returns are visible payments methods
     *
     * @param int|null $storeId
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isPaymentMethodsActive(?int $storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        return $this->getConfigData('show_payment_methods', DefaultConfigProvider::CODE, $storeId, true);
    }

    /**
     * Returns is module retry payment enabled
     *
     * @param null $storeId
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isRetryPaymentActive($storeId = null): bool
    {
        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        return $this->getConfigData('retry_payment', DefaultConfigProvider::CODE, $storeId, true);
    }

    /**
     * Returns is order status change enabled
     *
     * @param null $storeId
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isOrderStatusChangeActive($storeId = null): bool
    {
        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        return $this->getConfigData('order_status_change', DefaultConfigProvider::CODE, $storeId, true);
    }

    /**
     * Returns is send order items enabled
     *
     * @param null $storeId
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isSendOrderItemsActive($storeId = null): bool
    {
        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        return $this->getConfigData('send_order_items', DefaultConfigProvider::CODE, $storeId, true);
    }

    /**
     * Returns is payment validity usage enabled
     *
     * @param null $storeId
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isPaymentValidityActive($storeId = null): bool
    {
        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        return $this->getConfigData('use_payment_validity', DefaultConfigProvider::CODE, $storeId, true);
    }

    /**
     * Returns is payment validity time
     *
     * @param null $storeId
     *
     * @return int
     * @throws NoSuchEntityException
     */
    public function getPaymentValidityTime($storeId = null): int
    {
        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        return (int)$this->getConfigData('payment_validity_time', DefaultConfigProvider::CODE, $storeId, false);
    }


    /**
     * Returns payment methods to hide
     *
     * @param null $storeId
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function getPaymentMethodsToHide($storeId = null): array
	{
        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getId();
        }

		$data = $this->getConfigData('hide_payment_methods', DefaultConfigProvider::CODE, $storeId, false);

		if (is_string($data)) {
			return explode(',', $data);
		} else {
			return [];
		}
    }

    /**
     * Returns information from payment configuration
     *
     * @param $field
     * @param $paymentMethodCode
     * @param $storeId
     * @param bool|false $flag
     *
     * @return bool|string|array
     */
    public function getConfigData($field, $paymentMethodCode, $storeId, bool $flag)
    {
        $path = 'payment/' . $paymentMethodCode . '/' . $field;

        if ($flag) {
            return $this->scopeConfig->isSetFlag($path, ScopeInterface::SCOPE_STORE, $storeId);
        } else {
            $value = $this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId) ?? '';
            if (is_array($value)) {
                return $value;
            }
            return trim($value);
        }
    }

    /**
     * Returns that is configured
     *
     * @param $storeId
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isConfigured($storeId = null): bool
    {
        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        $isTestMode = $this->isTestMode($storeId);

        return ! empty($this->getApiKey($storeId, $isTestMode))
               && ! empty($this->getSignatureKey($storeId, $isTestMode));
    }

    /**
     * Returns Api Key for Paynow API
     *
     * @param $storeId
     * @param bool $isTestMode
     *
     * @return string
     */
    public function getApiKey($storeId, bool $isTestMode): string
    {
        if ($isTestMode) {
            return $this->getDecryptedApiKey('sandbox_api_key', $storeId);
        }

        return $this->getDecryptedApiKey('production_api_key', $storeId);
    }

    /**
     * Returns Signature Key for Paynow API
     *
     * @param $storeId
     * @param bool $isTestMode
     * @return string
     */
    public function getSignatureKey($storeId, bool $isTestMode = false): string
    {
        if ($isTestMode) {
            return $this->getDecryptedApiKey('sandbox_signature_key', $storeId);
        }

        return $this->getDecryptedApiKey('production_signature_key', $storeId);
    }

    /**
     * Returns decrypted keys
     *
     * @param $keyName
     * @param $storeId
     * @return string
     */
    private function getDecryptedApiKey($keyName, $storeId): string
    {
        return $this->encryptor->decrypt($this->getConfigData($keyName, DefaultConfigProvider::CODE, $storeId, false));
    }
}
