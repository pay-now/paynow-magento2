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

    public function  getOrder()
    {
        return $this->_orderFactory->create()->load($this->getLastOrderId());
    }

    /**
     *
     * @return string
     * @throws NoSuchEntityException
     */
    public function canRetryPayment(): string
    {
        return $this->paymentHelper->isRetryPaymentActiveForOrder($this->getOrder());
    }

    /**
     * Returns retry payment url
     *
     * @return string
     */
    public function getRetryPaymentUrl(): string
    {
        return $this->paymentHelper->getRetryPaymentUrl($this->getLastOrderId());
    }
}