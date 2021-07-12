<?php

namespace Paynow\PaymentGateway\Gateway\Response\Payment;

use Magento\Framework\Exception\LocalizedException;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Sales\Model\Order\Payment;
use Paynow\PaymentGateway\Helper\PaymentField;

/**
 * Class PaymentAuthorizationHandler
 *
 * @package Paynow\PaymentGateway\Gateway\Response
 */
class AuthorizationHandler implements HandlerInterface
{
    /**
     * Handles transaction id
     *
     * @param array $handlingSubject
     * @param array $response
     * @throws LocalizedException
     */
    public function handle(array $handlingSubject, array $response)
    {
        /** @var PaymentDataObject $paymentDataObject */
        $paymentDataObject = SubjectReader::readPayment($handlingSubject);

        /** @var Payment $payment */
        $payment = $paymentDataObject->getPayment();

        $payment->setIsTransactionPending(true)
            ->setTransactionId($response[PaymentField::PAYMENT_ID_FIELD_NAME])
            ->setIsTransactionClosed(false)
            ->setAdditionalInformation(
                PaymentField::REDIRECT_URL_FIELD_NAME,
                $response[PaymentField::REDIRECT_URL_FIELD_NAME]
            )
            ->setAdditionalInformation(
                PaymentField::PAYMENT_ID_FIELD_NAME,
                $response[PaymentField::PAYMENT_ID_FIELD_NAME]
            )
            ->setAdditionalInformation(
                PaymentField::STATUS_FIELD_NAME,
                $response[PaymentField::STATUS_FIELD_NAME]
            );
    }
}
