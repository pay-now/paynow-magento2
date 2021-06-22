<?php

namespace Paynow\PaymentGateway\Gateway\Request\Refund;

use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Paynow\PaymentGateway\Gateway\Request\AbstractRequest;
use Paynow\PaymentGateway\Helper\PaymentField;
use Paynow\PaymentGateway\Helper\PaymentHelper;
use Paynow\PaymentGateway\Helper\RefundField;

class RefundRequest extends AbstractRequest implements BuilderInterface
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
        $refundAmount = SubjectReader::readAmount($buildSubject);

        $request['body'] = [
            RefundField::AMOUNT_FIELD_NAME => $this->paymentHelper->formatAmount($refundAmount),
            PaymentField::EXTERNAL_ID_FIELD_NAME => $referenceId,
            PaymentField::PAYMENT_ID_FIELD_NAME => $this->payment->getAdditionalInformation(PaymentField::PAYMENT_ID_FIELD_NAME)
        ];

        $request['headers'] = [
            PaymentField::IDEMPOTENCY_KEY_FIELD_NAME => uniqid($referenceId, true)
        ];

        return $request;
    }
}