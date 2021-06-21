<?php

namespace Paynow\PaymentGateway\Gateway\Request\Refund;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Paynow\PaymentGateway\Helper\PaymentField;
use Paynow\PaymentGateway\Helper\PaymentHelper;
use Paynow\PaymentGateway\Helper\RefundField;

class RefundRequest implements BuilderInterface
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
        $refundAmount = SubjectReader::readAmount($buildSubject);
        $order = $paymentDataObject->getOrder();
        $referenceId = $order->getOrderIncrementId();

        $request['body'] = [
            RefundField::AMOUNT_FIELD_NAME => $this->paymentHelper->formatAmount($refundAmount)
        ];

        $request['headers'] = [
            RefundField::IDEMPOTENCY_KEY_FIELD_NAME => uniqid($referenceId, true)
        ];

        return $request;
    }
}