<?php

namespace Paynow\PaymentGateway\Gateway\Request;

use LogicException;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObject;
use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Sales\Model\Order\Payment;

abstract class AbstractRequest
{
    /**
     * @var Payment
     */
    protected $payment;

    /**
     * @var OrderAdapterInterface
     */
    protected $order;

    /**
     * @param array $buildSubject
     * @throws LogicException
     */
    public function build(array $buildSubject)
    {
        /** @var PaymentDataObject $paymentDataObject */
        $paymentDataObject = SubjectReader::readPayment($buildSubject);

        $this->payment = $paymentDataObject->getPayment();
        $this->order = $paymentDataObject->getOrder();
    }
}