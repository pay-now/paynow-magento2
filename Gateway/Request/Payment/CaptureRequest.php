<?php

namespace Paynow\PaymentGateway\Gateway\Request\Payment;

use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Paynow\PaymentGateway\Helper\PaymentField;

/**
 * Class PaymentCaptureRequest
 *
 * @package Paynow\PaymentGateway\Gateway\Request
 */
class CaptureRequest implements BuilderInterface
{
    /**
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        /** @var PaymentDataObject $paymentDataObject */
        $paymentDataObject = SubjectReader::readPayment($buildSubject);
        $payment = $paymentDataObject->getPayment();

        $request['body'] = [
            PaymentField::PAYMENT_ID_FIELD_NAME => $payment->getLastTransId()
        ];

        return $request;
    }
}
