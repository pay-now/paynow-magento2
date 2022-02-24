<?php

namespace Paynow\PaymentGateway\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Paynow\Client;
use Paynow\Exception\PaynowException;
use Paynow\PaymentGateway\Helper\PaymentField;
use Paynow\PaymentGateway\Helper\PaymentHelper;
use Paynow\PaymentGateway\Helper\RefundField;
use Paynow\PaymentGateway\Model\Logger\Logger;
use Paynow\Service\Refund;

/**
 * Class PaymentAuthorization
 *
 * @package Paynow\PaymentGateway\Gateway\Http\Client
 */
class PaymentRefund implements ClientInterface
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
        $data = $transferObject->getBody();
        $this->logger->info(
            "Processing create refund",
            array_merge($loggerContext, [
                PaymentField::PAYMENT_ID_FIELD_NAME => $data[PaymentField::PAYMENT_ID_FIELD_NAME],
                RefundField::AMOUNT_FIELD_NAME => $data[RefundField::AMOUNT_FIELD_NAME]
            ])
        );
        try {
            $apiResponseObject = (new Refund($this->client))->create(
                $data[PaymentField::PAYMENT_ID_FIELD_NAME],
                $transferObject->getHeaders()[PaymentField::IDEMPOTENCY_KEY_FIELD_NAME],
                $data[RefundField::AMOUNT_FIELD_NAME]
            );
            $this->logger->info(
                "Retrieved create refund response",
                array_merge($loggerContext, [
                    RefundField::STATUS_FIELD_NAME => $apiResponseObject->getStatus(),
                    PaymentField::PAYMENT_ID_FIELD_NAME => $data[PaymentField::PAYMENT_ID_FIELD_NAME],
                    RefundField::REFUND_ID_FIELD_NAME => $apiResponseObject->getRefundId()
                ])
            );
            return [
                RefundField::STATUS_FIELD_NAME => $apiResponseObject->getStatus(),
                RefundField::REFUND_ID_FIELD_NAME => $apiResponseObject->getRefundId(),
            ];
        } catch (PaynowException $exception) {
            $this->logger->error(
                'An error occurred during refund create',
                array_merge($loggerContext, [
                    'service' => 'Refund',
                    'action' => 'create',
                    'message' => $exception->getMessage(),
                    'errors' => $exception->getPrevious()->getErrors()
                ])
            );
            foreach ($exception->getErrors() as $error) {
                $this->logger->debug($error->getType() . ' - ' . $error->getMessage(), $loggerContext);
            }
            return $response['errors'] = $exception->getErrors();
        }
    }
}
