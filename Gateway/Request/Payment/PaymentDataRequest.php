<?php

namespace Paynow\PaymentGateway\Gateway\Request\Payment;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Paynow\PaymentGateway\Gateway\Request\AbstractRequest;
use Paynow\PaymentGateway\Helper\PaymentField;

class PaymentDataRequest extends AbstractRequest implements BuilderInterface
{
    const PAYMENT_METHOD_ID = 'payment_method_id';

    public function build(array $buildSubject)
    {
        parent::build($buildSubject);

        return [
            PaymentField::PAYMENT_METHOD_ID => $this->payment->getAdditionalInformation(
                self::PAYMENT_METHOD_ID
            )
        ];
    }
}
