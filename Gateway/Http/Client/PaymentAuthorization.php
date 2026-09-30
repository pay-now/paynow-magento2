<?php

namespace Paynow\PaymentGateway\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Paynow\Client;
use Paynow\Exception\PaynowException;
use Paynow\PaymentGateway\Helper\PaymentField;
use Paynow\PaymentGateway\Helper\PaymentHelper;
use Paynow\PaymentGateway\Model\Logger\Logger;
use Paynow\Service\Payment;
use Paynow\Model\Payment\Status;
use Magento\Framework\App\CacheInterface;

/**
 * Class PaymentAuthorization
 *
 * @package Paynow\PaymentGateway\Gateway\Http\Client
 */
class PaymentAuthorization implements ClientInterface
{
    private const MAX_AUTH_ATTEMPTS = 6;
    private const CACHE_TAG = 'paynow_auth_attempts';

    /**
     * @var Client
     */
    private $client;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var CacheInterface
     */
    private $cache;

    /**
     * @var PaymentTimeoutClassifier
     */
    private $timeoutClassifier;

    /**
     * PaymentAuthorization constructor.
     * @param PaymentHelper $paymentHelper
     * @param Logger $logger
     * @param CacheInterface $cache
     * @param PaymentTimeoutClassifier|null $timeoutClassifier
     */
    public function __construct(
        PaymentHelper $paymentHelper,
        Logger $logger,
        CacheInterface $cache,
        ?PaymentTimeoutClassifier $timeoutClassifier = null
    ) {
        $this->client = $paymentHelper->initializePaynowClient();
        $this->logger = $logger;
        $this->cache = $cache;
        $this->timeoutClassifier = $timeoutClassifier ?? new PaymentTimeoutClassifier();
    }

    /**
     * @param TransferInterface $transferObject
     * @return array|mixed
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $cartId = (string)($transferObject->getHeaders()[PaymentField::CART_ID_FIELD_NAME] ?? '');
        $externalId = (string)($transferObject->getBody()[PaymentField::EXTERNAL_ID_FIELD_NAME] ?? '');
        $loggerContext = [
            PaymentField::EXTERNAL_ID_FIELD_NAME => $externalId,
            PaymentField::CART_ID_FIELD_NAME => $cartId,
        ];

        // Limit liczby prób autoryzacji per koszyk (best-effort bez locka)
        $attempts = $this->getAttempts($cartId);
        if ($attempts >= self::MAX_AUTH_ATTEMPTS) {
            $this->logger->warning(
                sprintf('Max authorize attempts reached (%d) for cart %s', $attempts, $cartId),
                array_merge($loggerContext, ['service' => 'Payment', 'action' => 'authorize'])
            );

            return [
                'errors' => [
					[
						'type' => 'max_attempts_exceeded',
						'message' => sprintf(
							'Osiągnięto limit %d prób autoryzacji dla zamówienia %s.',
							self::MAX_AUTH_ATTEMPTS,
							$externalId
						)
                	]
				]
            ];
        }
        // Zapisz kolejną próbę przed wywołaniem API
        $this->saveAttempts($cartId, $attempts + 1);

        try {
            $service = new Payment($this->client);
            $apiResponseObject = $service->authorize(
                $transferObject->getBody(),
                $transferObject->getHeaders()[PaymentField::IDEMPOTENCY_KEY_FIELD_NAME]
            );
            $this->logger->debug(
                "Retrieved authorization response",
                array_merge($loggerContext, [
                    PaymentField::STATUS_FIELD_NAME => $apiResponseObject->getStatus(),
                    PaymentField::PAYMENT_ID_FIELD_NAME => $apiResponseObject->getPaymentId()
                ])
            );
            return [
                PaymentField::REDIRECT_URL_FIELD_NAME => $apiResponseObject->getRedirectUrl(),
                PaymentField::STATUS_FIELD_NAME => $apiResponseObject->getStatus(),
                PaymentField::PAYMENT_ID_FIELD_NAME => $apiResponseObject->getPaymentId(),
            ];
        } catch (PaynowException $exception) {
            if (isset($transferObject->getBody()[PaymentField::CONTINUE_URL_FIELD_NAME]) &&
                $this->timeoutClassifier->isRecoverable($exception)) {
                return [
                    PaymentField::STATUS_FIELD_NAME => Status::STATUS_NEW,
                    PaymentField::PAYMENT_ID_FIELD_NAME =>
                        $transferObject->getBody()[PaymentField::EXTERNAL_ID_FIELD_NAME].'_UNKNOWN' ,
                    PaymentField::REDIRECT_URL_FIELD_NAME =>
                        $transferObject->getBody()[PaymentField::CONTINUE_URL_FIELD_NAME],
                ];
            } else {
                $this->logger->error(
                    $exception->getMessage(),
                    array_merge($loggerContext, [
                        'service' => 'Payment',
                        'action' => 'authorize'
                    ])
                );
                foreach ($exception->getErrors() as $error) {
                    $this->logger->debug($error->getType() . ' - ' . $error->getMessage(), $loggerContext);
                }
                return ['errors' => $exception->getErrors()];
            }
        }
    }

    /**
     * Zwraca klucz licznika prób w cache
     */
    private function getCounterKey(string $externalId): string
    {
        return 'paynow_auth_attempts_' . $externalId;
    }

    /**
     * Pobiera liczbę dotychczasowych prób autoryzacji dla zamówienia
     */
    private function getAttempts(string $externalId): int
    {
        $raw = $this->cache->load($this->getCounterKey($externalId));
        if ($raw === false || $raw === null) {
            return 0;
        }
        return (int)$raw;
    }

    /**
     * Zapisuje liczbę prób autoryzacji dla zamówienia
     */
    private function saveAttempts(string $externalId, int $value): void
    {
        $this->cache->save((string)$value, $this->getCounterKey($externalId), [self::CACHE_TAG]);
    }
}
