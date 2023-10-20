<?php

namespace Paynow\PaymentGateway\Gateway\Request\Payment;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Paynow\PaymentGateway\Gateway\Request\AbstractRequest;
use Paynow\PaymentGateway\Helper\KeysGenerator;
use Paynow\PaymentGateway\Helper\PaymentField;

/**
 * Class PaymentCaptureRequest
 *
 * @package Paynow\PaymentGateway\Gateway\Request\Payment
 */
class CaptureRequest extends AbstractRequest implements BuilderInterface
{
    /**
     * @param array $buildSubject
     * @return array
     */
    public function build(array $buildSubject)
    {
        parent::build($buildSubject);

        $request['body'] = [
            PaymentField::PAYMENT_ID_FIELD_NAME => $this->payment->getLastTransId()
        ];

        $request['headers'] = [
            PaymentField::IDEMPOTENCY_KEY_FIELD_NAME => KeysGenerator::generateIdempotencyKey($this->order->getOrderIncrementId())
        ];

        return $request;
    }
}
