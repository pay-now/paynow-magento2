<?php

namespace Paynow\PaymentGateway\Block\Onepage;

use Magento\Checkout\Block\Onepage\Success as MagentoSuccess;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Http\Context as AppContext;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Config;
use Paynow\PaymentGateway\Helper\PaymentHelper;

/**
 * Class Success
 *
 * @package Paynow\PaymentGateway\Block\Onepage
 */
class Success extends MagentoSuccess
{
    /**
     * @var OrderInterfaceFactory
     */
    private $orderFactory;

    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    public function __construct(
        Context $context,
        Session $checkoutSession,
        Config $orderConfig,
        AppContext $httpContext,
        PaymentHelper $paymentHelper,
        array $data = []
    ) {
        parent::__construct($context, $checkoutSession, $orderConfig, $httpContext, $data);
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * @param $orderId
     *
     * @return string
     * @throws NoSuchEntityException
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