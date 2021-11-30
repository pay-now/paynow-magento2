<?php

namespace Paynow\PaymentGateway\Helper;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Serialize\SerializerInterface;
use Paynow\Client;
use Paynow\Exception\PaynowException;
use Paynow\PaymentGateway\Model\Cache\GDPRNoticesCache;
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
     * @var GDPRNoticesCache
     */
    private $cache;
    /**
     * @var SerializerInterface
     */
    private $serializer;

    /**
     * DataProcessingNotesHelper constructor.
     * @param PaymentHelper $paymentHelper
     * @param Logger $logger
     * @param GDPRNoticesCache $cache
     * @param SerializerInterface $serializer
     * @throws NoSuchEntityException
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        Logger $logger,
        GDPRNoticesCache $cache,
        SerializerInterface $serializer
    ) {
        $this->paymentHelper = $paymentHelper;
        $this->logger        = $logger;
        $this->cache = $cache;
        $this->serializer = $serializer;
        $this->client = $this->paymentHelper->initializePaynowClient();
    }

    public function getNotices()
    {
        $cacheKey  = GDPRNoticesCache::TYPE_IDENTIFIER;
        $cacheTag  = GDPRNoticesCache::CACHE_TAG;
        $notices = [];
        $gdpr_notices = $this->cache->load($cacheKey);
        if (!$gdpr_notices) {
            $gdpr_notices = $this->retrieve();
            foreach ($gdpr_notices as $notice) {
                array_push($notices, [
                    'title'   => $notice->getTitle(),
                    'content' => $notice->getContent()
                ]);
            }
            $this->cache->save(
                $this->serializer->serialize($notices),
                $cacheKey,
                [$cacheTag],
                1440
            );
        } else {
            $this->logger->info("Retrieving GDPR notices from cache");
            $unserialized = $this->serializer->unserialize($gdpr_notices);
            if ($unserialized) {
                foreach ($unserialized as $notice) {

                    array_push($notices, [
                        'title' => $notice["title"],
                        'content' => $notice["content"]
                    ]);
                }
            }
        }

        return $notices;
    }

    public function retrieve()
    {
        try {
            $this->logger->info("Retrieving GDPR notices");
            return (new DataProcessing($this->client))
                ->getNotices($this->paymentHelper->getStoreLocale())
                ->getAll();
        } catch (PaynowException $exception) {
            $this->logger->error($exception->getMessage());
        }

        return null;
    }
}
