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

/**
 * Class PaymentAuthorization
 *
 * @package Paynow\PaymentGateway\Gateway\Http\Client
 */
class PaymentAuthorization implements ClientInterface
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * PaymentAuthorization constructor.
     * @param PaymentHelper $paymentHelper
     * @param Logger $logger
     */
    public function __construct(PaymentHelper $paymentHelper, Logger $logger)
    {
        $this->client = $paymentHelper->initializePaynowClient();
        $this->logger = $logger;
    }

    /**
     * @param TransferInterface $transferObject
     * @return array|mixed
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $loggerContext = [
            PaymentField::EXTERNAL_ID_FIELD_NAME => $transferObject->getBody()[PaymentField::EXTERNAL_ID_FIELD_NAME]
        ];
        $this->logger->debug('body'. json_encode($transferObject->getBody()));
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
