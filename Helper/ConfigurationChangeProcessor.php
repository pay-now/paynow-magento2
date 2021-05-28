<?php

namespace Paynow\PaymentGateway\Helper;

use Exception;
use Paynow\Client;
use Paynow\PaymentGateway\Model\Logger\Logger;
use Paynow\Service\ShopConfiguration;

/**
 * Class ShopConfigurationChangeProcessor
 *
 * @package Paynow\PaymentGateway\Helper
 */
class ConfigurationChangeProcessor
{
    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(PaymentHelper $paymentHelper, Logger $logger)
    {
        $this->paymentHelper = $paymentHelper;
        $this->logger        = $logger;
    }

    public function process($storeId = null)
    {
        try {
            $this->logger->info("Updating shop configuration");
            /** @var Client */
            $client            = $this->paymentHelper->initializePaynowClient($storeId);
            $shopConfiguration = new ShopConfiguration($client);
            $shopConfiguration->changeUrls(
                $this->paymentHelper->getContinueUrl(),
                $this->paymentHelper->getNotificationUrl()
            );
            $this->logger->info("Shop configuration updated");
        } catch (Exception $exception) {
            $this->logger->error(
                $exception->getMessage(),
                [
                    'service' => 'ShopConfiguration',
                    'action'  => 'changeUrls'
                ]
            );
        }
    }
}
