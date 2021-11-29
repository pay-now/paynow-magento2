<?php

namespace Paynow\PaymentGateway\Helper;

use Paynow\Client;
use Paynow\Exception\PaynowException;
use Paynow\PaymentGateway\Model\Logger\Logger;
use Paynow\Service\DataProcessing;

/**
 * Class DataProcessingNotesHelper
 *
 * @package Paynow\PaymentGateway\Helper
 */
class DataProcessingNotesHelper
{
    /**
     * @var Client
     */
    private $client;

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
        $this->client = $this->paymentHelper->initializePaynowClient();
    }

    public function getNotices()
    {
            $gdpr_notices = $this->retrieve();
            $notices      = [];
        if ($gdpr_notices) {
            foreach ($gdpr_notices as $notice) {
                array_push($notices, [
                    'title'   => $notice->getTitle(),
                    'content' => $notice->getContent()
                ]);
            }
        }

        return $notices;
    }

    public function retrieve()
    {
        try {
            $this->logger->info("Retrieving GDPR notices");
            return (new DataProcessing($this->client))->getNotices($this->paymentHelper->getStoreLocale())->getAll();
        } catch (PaynowException $exception) {
            $this->logger->error($exception->getMessage());
        }

        return null;
    }
}
