<?php

namespace Paynow\PaymentGateway\Helper;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Encryption\EncryptorInterface;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\ScopeInterface;
use Magento\Store\Model\StoreManagerInterface;
use Paynow\Client;
use Paynow\Environment;
use Paynow\Model\Payment\Status;
use Paynow\PaymentGateway\Model\Ui\ConfigProvider;

/**
 * Class Data
 *
 * @package Paynow\PaymentGateway\Helper
 */
class PaymentHelper extends AbstractHelper
{
    /**
     * @var ComponentRegistrarInterface
     */
    protected $componentRegistrar;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var ProductMetadataInterface
     */
    protected $productMetadata;

    /**
     * @var EncryptorInterface
     */
    protected $encryptor;

    /**
     * @var ScopeConfigInterface
     */
    protected $scopeConfig;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var File
     */
    protected $driverFile;

    /**
     * Data constructor.
     * @param Context $context
     * @param ComponentRegistrarInterface $componentRegistrar
     * @param StoreManagerInterface $storeManager
     * @param ProductMetadataInterface $productMetadata
     * @param EncryptorInterface $encryptor
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlInterface $urlBuilder
     * @param File $driverFile
     */
    public function __construct(
        Context $context,
        ComponentRegistrarInterface $componentRegistrar,
        StoreManagerInterface $storeManager,
        ProductMetadataInterface $productMetadata,
        EncryptorInterface $encryptor,
        ScopeConfigInterface $scopeConfig,
        UrlInterface $urlBuilder,
        File $driverFile
    ) {
        parent::__construct($context);
        $this->componentRegistrar = $componentRegistrar;
        $this->storeManager = $storeManager;
        $this->productMetadata = $productMetadata;
        $this->encryptor = $encryptor;
        $this->scopeConfig = $scopeConfig;
        $this->urlBuilder = $urlBuilder;
        $this->driverFile = $driverFile;
    }

    /**
     * Return formatted amount
     *
     * @param $amount
     * @return int
     */
    public function formatAmount($amount): int
    {
        return (int)number_format($amount * 100, 0, '.', '');
    }

    /**
     * Returns module version from composer.json
     *
     * @return string
     */
    public function getModuleVersion(): string
    {
        $moduleDir = $this->componentRegistrar->getPath(ComponentRegistrar::MODULE, 'Paynow_PaymentGateway');

        $composerJson = $this->driverFile->fileGetContents($moduleDir . '/composer.json');
        $composerJson = json_decode($composerJson, true);

        return !empty($composerJson['version']) ? $composerJson['version'] : "no-version";
    }

    /**
     * Initializes and returns Paynow Client
     *
     * @param int|null $storeId
     * @return Client
     */
    public function initializePaynowClient($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        $isTestMode = $this->isTestMode($storeId);

        return new Client(
            $this->getApiKey($storeId, $isTestMode),
            $this->getSignatureKey($storeId, $isTestMode),
            $isTestMode ? Environment::SANDBOX : Environment::PRODUCTION,
            $this->getApplicationName()
        );
    }

    /**
     * Returns is Test mode enabled
     *
     * @param $storeId
     * @return bool
     */
    public function isTestMode($storeId): bool
    {
        return $this->getConfigData('test_mode', ConfigProvider::CODE, $storeId, true);
    }

    /**
     * Returns is module enabled
     *
     * @param int|null $storeId
     * @return bool
     */
    public function isActive($storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        return $this->getConfigData('active', ConfigProvider::CODE, $storeId, true);
    }

    /**
     * Returns is module retry payment enabled
     *
     * @param null $storeId
     * @return bool
     */
    public function isRetryPaymentActive($storeId = null): bool
    {
        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        return $this->getConfigData('retry_payment', ConfigProvider::CODE, $storeId, true);
    }

    /**
     * Returns is retry payment is available for order
     *
     * @param Order $order
     * @return bool
     */
    public function isRetryPaymentActiveForOrder($order): bool
    {
        $paymentStatus = $order->getPayment()->getAdditionalInformation(PaymentField::STATUS_FIELD_NAME);

        return $this->isRetryPaymentActive() &&
            $order->getStatus() === Order::STATE_PAYMENT_REVIEW &&
            in_array(
                $paymentStatus,
                [
                    Status::STATUS_NEW,
                    Status::STATUS_PENDING,
                    Status::STATUS_REJECTED,
                    Status::STATUS_ERROR
                ]
            );
    }

    /**
     * Returns Api Key for Paynow API
     *
     * @param $storeId
     * @param bool $isTestMode
     * @return string
     */
    public function getApiKey($storeId, $isTestMode = false): string
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
    public function getSignatureKey($storeId, $isTestMode = false): string
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
        return $this->encryptor->decrypt($this->getConfigData($keyName, ConfigProvider::CODE, $storeId));
    }

    /**
     * Returns information from payment configuration
     *
     * @param $field
     * @param $paymentMethodCode
     * @param $storeId
     * @param bool|false $flag
     * @return bool|string
     */
    public function getConfigData($field, $paymentMethodCode, $storeId, $flag = false)
    {
        $path = 'payment/' . $paymentMethodCode . '/' . $field;

        if ($flag) {
            return $this->scopeConfig->isSetFlag($path, ScopeInterface::SCOPE_STORE, $storeId);
        } else {
            return trim($this->scopeConfig->getValue($path, ScopeInterface::SCOPE_STORE, $storeId));
        }
    }

    /**
     * Returns application name for Paynow Client
     *
     * @return string
     */
    private function getApplicationName(): string
    {
        return $this->productMetadata->getName() .
            '-' .
            $this->productMetadata->getVersion() .
            '/Plugin-' .
            $this->getModuleVersion();
    }

    /**
     * Returns return url
     * @param bool $forRetryPayment
     * @return string
     */
    public function getContinueUrl($forRetryPayment = false): string
    {
        if ($forRetryPayment) {
            return $this->urlBuilder->getUrl('sales/order/history');
        }

        return $this->urlBuilder->getUrl('checkout/onepage/success');
    }

    /**
     * Returns notification url
     *
     * @return string
     */
    public function getNotificationUrl(): string
    {
        return $this->urlBuilder->getUrl('paynow/payment/notifications');
    }

    /**
     * Returns retry payment url
     *
     * @param $orderId
     * @return string
     */
    public function getRetryPaymentUrl($orderId): string
    {
        return $this->urlBuilder->getUrl('paynow/payment/retry', ['order_id' => $orderId]);
    }
}
