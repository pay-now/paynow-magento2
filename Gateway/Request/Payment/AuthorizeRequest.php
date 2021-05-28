<?php

namespace Paynow\PaymentGateway\Gateway\Request\Payment;

use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Paynow\PaymentGateway\Helper\PaymentField;
use Paynow\PaymentGateway\Helper\PaymentHelper;

/**
 * Class PaymentAuthorizationRequest
 *
 * @package Paynow\PaymentGateway\Gateway\Request
 */
class AuthorizeRequest implements BuilderInterface
{
    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    public function __construct(PaymentHelper $paymentHelper)
    {
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        /** @var PaymentDataObject $paymentDataObject */
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $order = $paymentDataObject->getOrder();
        $referenceId = $order->getOrderIncrementId();
        $paymentDescription = __('Order No: ') . $referenceId;

        $isRetry = $paymentDataObject->getPayment()
            ->hasAdditionalInformation(PaymentField::IS_PAYMENT_RETRY_FIELD_NAME);

        $request['body'] = [
            PaymentField::AMOUNT_FIELD_NAME => $this->paymentHelper->formatAmount($order->getGrandTotalAmount()),
            PaymentField::CURRENCY_FIELD_NAME => $order->getCurrencyCode(),
            PaymentField::EXTERNAL_ID_FIELD_NAME => $referenceId,
            PaymentField::DESCRIPTION_FIELD_NAME => $paymentDescription,
            PaymentField::BUYER_FIELD_NAME => [
                PaymentField::BUYER_EMAIL_FIELD_NAME => $order->getShippingAddress()->getEmail(),
                PaymentField::BUYER_FIRSTNAME_FIELD_NAME => $order->getShippingAddress()->getFirstname(),
                PaymentField::BUYER_LASTNAME_FIELD_NAME => $order->getShippingAddress()->getLastname(),
                PaymentField::BUYER_LOCALE => $this->paymentHelper->getStoreLocale(),
            ],
            PaymentField::CONTINUE_URL_FIELD_NAME => $this->paymentHelper->getContinueUrl($isRetry)
        ];

        $request['headers'] = [
            PaymentField::IDEMPOTENCY_KEY_FIELD_NAME => uniqid($referenceId, true)
        ];

        return $request;
    }
}
