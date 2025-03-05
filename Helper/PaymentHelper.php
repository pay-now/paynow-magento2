<?php

namespace Paynow\PaymentGateway\Helper;

use Firebase\JWT\JWT;
use Magento\Catalog\Model\Product;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\App\Helper\AbstractHelper;
use Magento\Framework\App\Helper\Context;
use Magento\Framework\App\ProductMetadataInterface;
use Magento\Framework\Component\ComponentRegistrar;
use Magento\Framework\Component\ComponentRegistrarInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Filesystem\Driver\File;
use Magento\Framework\Locale\Resolver;
use Magento\Framework\UrlInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Paynow\Client;
use Paynow\Environment;
use Paynow\Model\Payment\Status;
use Paynow\PaymentGateway\Model\Logger\Logger;
use Paynow\Util\ClientExternalIdCalculator;

/**
 * Class Data
 *
 * @package Paynow\PaymentGateway\Helper
 */
class PaymentHelper extends AbstractHelper
{
    private const MAX_ORDER_ITEM_NAME_LENGTH = 120;
    /**
     * @var ConfigHelper
     */
    protected $configHelper;

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
     * @var Resolver
     */
    protected $localeResolver;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Data constructor.
     *
     * @param Context $context
     * @param ConfigHelper $configHelper
     * @param ComponentRegistrarInterface $componentRegistrar
     * @param StoreManagerInterface $storeManager
     * @param ProductMetadataInterface $productMetadata
     * @param ScopeConfigInterface $scopeConfig
     * @param UrlInterface $urlBuilder
     * @param File $driverFile
     * @param Resolver $localeResolver
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        ConfigHelper $configHelper,
        ComponentRegistrarInterface $componentRegistrar,
        StoreManagerInterface $storeManager,
        ProductMetadataInterface $productMetadata,
        ScopeConfigInterface $scopeConfig,
        UrlInterface $urlBuilder,
        File $driverFile,
        Resolver $localeResolver,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->configHelper       = $configHelper;
        $this->componentRegistrar = $componentRegistrar;
        $this->storeManager       = $storeManager;
        $this->productMetadata    = $productMetadata;
        $this->scopeConfig        = $scopeConfig;
        $this->urlBuilder         = $urlBuilder;
        $this->driverFile         = $driverFile;
        $this->localeResolver     = $localeResolver;
        $this->logger             = $logger;
    }

    /**
     * Return formatted amount
     *
     * @param $amount
     *
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

        return ! empty($composerJson['version']) ? $composerJson['version'] : "no-version";
    }

    /**
     * Initializes and returns Paynow Client
     *
     * @param int|null $storeId
     *
     * @return Client
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    public function initializePaynowClient(int $storeId = null)
    {
        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        $isTestMode = $this->configHelper->isTestMode($storeId);

        return new Client(
            $this->configHelper->getApiKey($storeId, $isTestMode),
            $this->configHelper->getSignatureKey($storeId, $isTestMode),
            $isTestMode ? Environment::SANDBOX : Environment::PRODUCTION,
            $this->getApplicationName()
        );
    }

    /**
     * Returns array of order items for order
     *
     * @param OrderAdapterInterface $order
     *
     * @return array
     */
    public function getOrderItems(OrderAdapterInterface $order): array
    {
        $orderItems = $order->getItems();

        return array_map(function ($item) {
            $product = $item->getProduct();

            return [
                'name'     => self::truncateOrderItemName($item->getName()),
                'category' => $this->getCategoriesNames($product),
                'quantity' => $item->getQtyOrdered(),
                'price'    => $this->formatAmount($item->getPrice())
            ];
        }, $orderItems);
    }

    /**
     * Returns array of categories names for product
     *
     * @param Product $product
     *
     * @return string|null
     */
    private function getCategoriesNames(Product $product): ?string
    {
        try {
            $categoriesCollection = $product->getCategoryCollection()->addAttributeToSelect('name');
            $rootCategoryId       = $this->storeManager->getStore()->getRootCategoryId();
            $categories           = [];
            foreach ($categoriesCollection as $category) {
                if ($category->getId() != $rootCategoryId) {
                    $categories[] = $category->getName();
                }
            }

            return implode(', ', $categories);

        } catch (LocalizedException $exception) {
            $this->logger->error('An error occurred during checkout: ' . $exception->getMessage());
        }

        return null;
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
     *
     * @param string $referenceId
     * @param $storeId
     * @return string
     * @throws NoSuchEntityException
     */
    public function getContinueUrl(string $referenceId, $storeId = null): string
    {
        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        $isTestMode = $this->configHelper->isTestMode($storeId);

        return $this->urlBuilder->getUrl(
            'paynow/checkout/success',
            ['_token' => JWT::encode(['referenceId' => $referenceId], $this->configHelper->getSignatureKey($storeId, $isTestMode), 'HS256')]
        );
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
     * @param int $orderId
     *
     * @return string
     */
    public function getRetryPaymentUrl(int $orderId): string
    {
        $storeId = $this->storeManager->getStore()->getId();
        $isTestMode = $this->configHelper->isTestMode($storeId);

        return $this->urlBuilder->getUrl('paynow/payment/retry', [
            'order_id' => $orderId,
            '_token' => JWT::encode(['orderId' => $orderId], $this->configHelper->getSignatureKey($storeId, $isTestMode), 'HS256'),
        ]);
    }

    /**
     * Returns store locale
     *
     * @return string
     */
    public function getStoreLocale()
    {
        return str_replace('_', '-', $this->localeResolver->getLocale());
    }

    /**
     * Returns is retry payment is available for order
     *
     * @param Order $order
     *
     * @return bool
     * @throws NoSuchEntityException
     */
    public function isRetryPaymentActiveForOrder(Order $order): bool
    {
        $paymentStatus = $order->getPayment()->getAdditionalInformation(PaymentField::STATUS_FIELD_NAME);

        return $this->configHelper->isRetryPaymentActive()
            && in_array(
                $order->getStatus(),
                [
                    Order::STATE_PAYMENT_REVIEW,
                    Order::STATE_PENDING_PAYMENT
                ]
            )
            && in_array(
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
     * @param string $identifier
     * @param $storeId
     * @return string
     * @throws NoSuchEntityException
     */
    public function generateBuyerExternalId(string $identifier, $storeId = null): string
    {
        if ($storeId === null) {
            $storeId = $this->storeManager->getStore()->getId();
        }

        $isTestMode = $this->configHelper->isTestMode($storeId);

        return ClientExternalIdCalculator::calculate($identifier, $this->configHelper->getSignatureKey($storeId, $isTestMode));
    }

    public static function truncateOrderItemName(string $name): string
    {
        $name = trim($name);

        if(strlen($name) <= self::MAX_ORDER_ITEM_NAME_LENGTH) {
            return $name;
        }

        return substr($name, 0, self::MAX_ORDER_ITEM_NAME_LENGTH - 3) . '...';
    }
}
