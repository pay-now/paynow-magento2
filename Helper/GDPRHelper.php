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
 * Class GDPRHelper
 *
 * @package Paynow\PaymentGateway\Helper
 */
class GDPRHelper
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
        $this->logger = $logger;
        $this->cache = $cache;
        $this->serializer = $serializer;
        $this->client = $this->paymentHelper->initializePaynowClient();
    }

    /**
     * @return array
     */
    public function getNotices(): array
    {
        $cacheKey  = GDPRNoticesCache::TYPE_IDENTIFIER . '-' . $this->paymentHelper->getStoreLocale();
        $cacheTag  = GDPRNoticesCache::CACHE_TAG;
        $notices = [];
        $gdpr_notices = $this->cache->load($cacheKey);
        if (!$gdpr_notices) {
            $gdpr_notices = $this->retrieve();
            foreach ($gdpr_notices ?? [] as $notice) {
                $notices[] = [
                    'title' => $notice->getTitle(),
                    'content' => $notice->getContent()
                ];
            }
            $this->cache->save(
                $this->serializer->serialize($notices),
                $cacheKey,
                [$cacheTag],
                1440
            );
        } else {
            $unserialized = $this->serializer->unserialize($gdpr_notices);
            if ($unserialized) {
                foreach ($unserialized ?? [] as $notice) {
                    $notices[] = [
                        'title' => $notice["title"],
                        'content' => $notice["content"]
                    ];
                }
            }
        }

        return $notices;
    }

    /**
     * @return array|null
     */
    private function retrieve(): ?array
    {
        try {
            return (new DataProcessing($this->client))
                ->getNotices($this->paymentHelper->getStoreLocale())
                ->getAll();
        } catch (PaynowException $exception) {
            $this->logger->error("Error occurred retrieving GDPR notices.",
                [
                    'message' => $exception->getMessage(),
                    'code' => $exception->getCode(),
                    'errors' => $exception->getErrors()
                ]
            );
        }

        return null;
    }
}
