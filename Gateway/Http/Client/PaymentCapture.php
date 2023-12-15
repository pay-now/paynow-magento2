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
 * Class PaymentCapture
 *
 * @package Paynow\PaymentGateway\Gateway\Http\Client
 */
class PaymentCapture implements ClientInterface
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var Logger
     */
    private $logger;

    public function __construct(PaymentHelper $paymentHelper, Logger $logger)
    {
        $this->client = $paymentHelper->initializePaynowClient();
        $this->logger = $logger;
    }

    /**
     * @param TransferInterface $transferObject
     * @return array
     */
    public function placeRequest(TransferInterface $transferObject)
    {
        $request = $transferObject->getBody();
        $loggerContext = [PaymentField::PAYMENT_ID_FIELD_NAME => $request[PaymentField::PAYMENT_ID_FIELD_NAME]];
        try {
            $service = new Payment($this->client);
            $apiResponseObject = $service->status(
                $request[PaymentField::PAYMENT_ID_FIELD_NAME],
                $transferObject->getHeaders()[PaymentField::IDEMPOTENCY_KEY_FIELD_NAME] ?? null
            );
            $response = [
                PaymentField::STATUS_FIELD_NAME => $apiResponseObject->getStatus(),
                PaymentField::PAYMENT_ID_FIELD_NAME => $apiResponseObject->getPaymentId(),
            ];
            $this->logger->debug(
                "Retrieved capture response",
                array_merge($loggerContext, $response)
            );
        } catch (PaynowException $exception) {
            $response['errors'] = $exception->getMessage();
            $this->logger->error($exception->getMessage(), array_merge(
                $loggerContext,
                [
                    'service' => 'Payment',
                    'action' => 'status'
                ]
            ));
        }

        return $response;
    }
}
