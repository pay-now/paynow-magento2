<?php

namespace Paynow\PaymentGateway\Gateway\Request\Payment;

use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Paynow\PaymentGateway\Gateway\Request\AbstractRequest;
use Paynow\PaymentGateway\Helper\ConfigHelper;
use Paynow\PaymentGateway\Helper\PaymentField;
use Paynow\PaymentGateway\Helper\PaymentHelper;
use Paynow\PaymentGateway\Observer\PaymentDataAssignObserver;

/**
 * Class PaymentAuthorizationRequest
 *
 * @package Paynow\PaymentGateway\Gateway\Request\Payment
 */
class AuthorizeRequest extends AbstractRequest implements BuilderInterface
{
    /**
     * @var PaymentHelper
     */
    private $helper;

    /**
     * @var ConfigHelper
     */
    private $config;

    public function __construct(PaymentHelper $paymentHelper, ConfigHelper $configHelper)
    {
        $this->helper = $paymentHelper;
        $this->config = $configHelper;
    }

    /**
     * @param array $buildSubject
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function build(array $buildSubject)
    {
        parent::build($buildSubject);

        $referenceId        = $this->order->getOrderIncrementId();
        $paymentDescription = __('Order No: ') . $referenceId;

        $request['body'] = [
            PaymentField::AMOUNT_FIELD_NAME      => $this->helper->formatAmount($this->order->getGrandTotalAmount()),
            PaymentField::CURRENCY_FIELD_NAME    => $this->order->getCurrencyCode(),
            PaymentField::EXTERNAL_ID_FIELD_NAME => $referenceId,
            PaymentField::DESCRIPTION_FIELD_NAME => $paymentDescription,
            PaymentField::BUYER_FIELD_NAME       => [
                PaymentField::BUYER_EMAIL_FIELD_NAME     => $this->order->getShippingAddress()->getEmail(),
                PaymentField::BUYER_FIRSTNAME_FIELD_NAME => $this->order->getShippingAddress()->getFirstname(),
                PaymentField::BUYER_LASTNAME_FIELD_NAME  => $this->order->getShippingAddress()->getLastname(),
                PaymentField::BUYER_LOCALE               => $this->helper->getStoreLocale(),
            ],
            PaymentField::CONTINUE_URL_FIELD_NAME => $this->helper->getContinueUrl()
        ];

        if ($this->payment->hasAdditionalInformation(PaymentDataAssignObserver::PAYMENT_METHOD_ID)
            && ! empty($this->payment->getAdditionalInformation(PaymentDataAssignObserver::PAYMENT_METHOD_ID))) {
            $request['body'][PaymentField::PAYMENT_METHOD_ID] = $this->payment
                ->getAdditionalInformation(PaymentDataAssignObserver::PAYMENT_METHOD_ID);
        }

        if ($this->config->isSendOrderItemsActive()) {
            $orderItems = $this->helper->getOrderItems($this->order);
            if (! empty($orderItems)) {
                $request['body'][PaymentField::ORDER_ITEMS] = $orderItems;
            }
        }

        if ($this->config->isPaymentValidityActive()) {
            $validityTime = $this->config->getPaymentValidityTime();
            if (! empty($validityTime)) {
                $request['body'][PaymentField::VALIDITY_TIME] = $this->config->getPaymentValidityTime();
            }
        }

        if ($this->payment->hasAdditionalInformation(PaymentDataAssignObserver::BLIK_CODE)
            && ! empty($this->payment->getAdditionalInformation(PaymentDataAssignObserver::BLIK_CODE))) {
            $request['body'][PaymentField::AUTHORIZATION_CODE] = $this->payment
                ->getAdditionalInformation(PaymentDataAssignObserver::BLIK_CODE);
        }

        $request['headers'] = [
            PaymentField::IDEMPOTENCY_KEY_FIELD_NAME => uniqid($referenceId, true)
        ];

        return $request;
    }
}
