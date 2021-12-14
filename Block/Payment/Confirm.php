<?php

namespace Paynow\PaymentGateway\Block\Payment;

use Magento\Framework\Phrase;
use Magento\Framework\View\Element\Template;
use Paynow\PaymentGateway\Helper\PaymentStatusLabel;

/**
 * Class Confirm
 *
 * @package Paynow\PaymentGateway\Block\Payment
 */
class Confirm extends Template
{
    public function getPaymentId(): ?string
    {
        return $this->getData('payment_id');
    }

    /**
     * @return array|mixed|null
     */
    public function getPaymentStatus(): ?string
    {
        return $this->getData('payment_status');
    }

    /**
     * @return Phrase|null
     */
    public function getPaymentStatusLabel(): ?Phrase
    {
        $paymentStatus = $this->getPaymentStatus();
        if ($paymentStatus) {
            return __(PaymentStatusLabel::${$paymentStatus});
        }
        return null;
    }
}
