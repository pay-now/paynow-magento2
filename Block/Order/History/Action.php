<?php

namespace Paynow\PaymentGateway\Block\Order\History;

use Magento\Framework\View\Element\Template;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Magento\Sales\Model\Order;
use Paynow\PaymentGateway\Helper\PaymentHelper;

/**
 * Class Action
 *
 * @package Paynow\PaymentGateway\Block\Order\History
 */
class Action extends Template
{
    /**
     * @var OrderInterfaceFactory
     */
    private $orderFactory;

    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    /**
     * Action constructor.
     *
     * @param Context $context
     * @param OrderInterfaceFactory $orderFactory
     * @param PaymentHelper $paymentHelper
     * @param array $data
     */
    public function __construct(
        Context $context,
        OrderInterfaceFactory $orderFactory,
        PaymentHelper $paymentHelper,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->orderFactory  = $orderFactory;
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * @param $orderId
     *
     * @return string
     */
    public function canRetryPayment($orderId): string
    {
        /** @var Order */
        $order = $this->orderFactory->create()->load($orderId);

        return $this->paymentHelper->isRetryPaymentActiveForOrder($order);
    }

    /**
     * Returns retry payment url
     *
     * @param $orderId
     *
     * @return string
     */
    public function getRetryPaymentUrl($orderId): string
    {
        return $this->paymentHelper->getRetryPaymentUrl($orderId);
    }
}
