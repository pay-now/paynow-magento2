<?php

namespace Paynow\PaymentGateway\Gateway\Request\Payment;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Paynow\PaymentGateway\Gateway\Request\AbstractRequest;
use Paynow\PaymentGateway\Helper\PaymentField;
use Paynow\PaymentGateway\Helper\PaymentHelper;

/**
 * Class PaymentAuthorizationRequest
 *
 * @package Paynow\PaymentGateway\Gateway\Request
 */
class AuthorizeRequest extends AbstractRequest implements BuilderInterface
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
        parent::build($buildSubject);

        $referenceId = $this->order->getOrderIncrementId();
        $paymentDescription = __('Order No: ') . $referenceId;

        $isRetry = $this->payment->hasAdditionalInformation(PaymentField::IS_PAYMENT_RETRY_FIELD_NAME);

        $request['body'] = [
            PaymentField::AMOUNT_FIELD_NAME => $this->paymentHelper->formatAmount($this->order->getGrandTotalAmount()),
            PaymentField::CURRENCY_FIELD_NAME => $this->order->getCurrencyCode(),
            PaymentField::EXTERNAL_ID_FIELD_NAME => $referenceId,
            PaymentField::DESCRIPTION_FIELD_NAME => $paymentDescription,
            PaymentField::BUYER_FIELD_NAME => [
                PaymentField::BUYER_EMAIL_FIELD_NAME => $this->order->getShippingAddress()->getEmail(),
                PaymentField::BUYER_FIRSTNAME_FIELD_NAME => $this->order->getShippingAddress()->getFirstname(),
                PaymentField::BUYER_LASTNAME_FIELD_NAME => $this->order->getShippingAddress()->getLastname(),
                PaymentField::BUYER_LOCALE => $this->paymentHelper->getStoreLocale(),
            ],
            PaymentField::CONTINUE_URL_FIELD_NAME => $this->paymentHelper->getContinueUrl($isRetry)
        ];

        if ($this->paymentHelper->isSendOrderItemsActive()) {
            $orderItems = $this->paymentHelper->getOrderItems($this->order);
            if (! empty($orderItems)) {
                $request['body'][PaymentField::ORDER_ITEMS] = $orderItems;
            }
        }

        if ($this->paymentHelper->isPaymentValidityActive()) {
            $validityTime = $this->paymentHelper->getPaymentValidityTime();
            if (! empty($validityTime)) {
                $request['body'][PaymentField::VALIDITY_TIME] = $this->paymentHelper->getPaymentValidityTime();
            }
        }

        $request['headers'] = [
            PaymentField::IDEMPOTENCY_KEY_FIELD_NAME => uniqid($referenceId, true)
        ];

        return $request;
    }
}
