<?php

namespace Paynow\PaymentGateway\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;
use Paynow\PaymentGateway\Gateway\Request\Payment\PaymentDataRequest;
use Paynow\PaymentGateway\Helper\PaymentField;

class PaymentDataAssignObserver extends AbstractDataAssignObserver
{
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (! is_array($additionalData)) {
            return;
        }

        if (empty($additionalData[PaymentDataRequest::PAYMENT_METHOD_ID])) {
            return;
        }

        $paymentInfo = $this->readPaymentModelArgument($observer);
        $paymentInfo->setAdditionalInformation(
            PaymentField::PAYMENT_METHOD_ID,
            $additionalData[PaymentDataRequest::PAYMENT_METHOD_ID]
        );
    }
}
