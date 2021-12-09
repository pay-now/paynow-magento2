<?php

namespace Paynow\PaymentGateway\Block\Payment;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Http\Context as AppContext;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Config;
use Paynow\PaymentGateway\Helper\NotificationProcessor;
use Paynow\PaymentGateway\Helper\PaymentHelper;
use Paynow\PaymentGateway\Model\Logger\Logger;

/**
 * Class Confirm
 *
 * @package Paynow\PaymentGateway\Block\Payment
 */
class Confirm extends \Magento\Framework\View\Element\Template
{

    public function __construct(
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);

    }
}