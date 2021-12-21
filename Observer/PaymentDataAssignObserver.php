<?php

namespace Paynow\PaymentGateway\Observer;

use Magento\Framework\Event\Observer;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

/**
 * Class PaymentDataAssignObserver
 *
 * @package Paynow\PaymentGateway\Observer
 */
class PaymentDataAssignObserver extends AbstractDataAssignObserver
{
    const PAYMENT_METHOD_ID = 'payment_method_id';

    const BLIK_CODE = 'blik_code';

    /**
     * @var array
     */
    protected $additionalInformationList = [
        self::PAYMENT_METHOD_ID,
        self::BLIK_CODE
    ];

    /**
     * @param Observer $observer
     *
     * @return void
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);

        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if (! is_array($additionalData)) {
            return;
        }

        $paymentInfo = $this->readPaymentModelArgument($observer);

        foreach ($this->additionalInformationList as $additionalInformationKey) {
            if (isset($additionalData[$additionalInformationKey])) {
                $paymentInfo->setAdditionalInformation(
                    $additionalInformationKey,
                    $additionalData[$additionalInformationKey]
                );
            }
        }
    }
}
