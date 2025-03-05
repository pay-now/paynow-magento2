<?php

namespace Paynow\PaymentGateway\Block\Onepage;

use Magento\Checkout\Block\Onepage\Success as MagentoSuccess;
use Magento\Checkout\Model\Session;
use Magento\Framework\App\Http\Context as AppContext;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\Phrase;
use Magento\Framework\View\Element\Template\Context;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Config;
use Magento\Sales\Api\Data\OrderInterfaceFactory;
use Paynow\Model\Payment\Status;
use Paynow\PaymentGateway\Helper\NotificationProcessor;
use Paynow\PaymentGateway\Helper\PaymentField;
use Paynow\PaymentGateway\Helper\PaymentHelper;
use Paynow\PaymentGateway\Model\Logger\Logger;

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

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var NotificationProcessor
     */
    private $notificationProcessor;
    /**
     * @var Order
     */
    private $order;

    public function __construct(
        Context $context,
        Session $checkoutSession,
        Config $orderConfig,
        AppContext $httpContext,
        PaymentHelper $paymentHelper,
        Logger $logger,
        NotificationProcessor $notificationProcessor,
        array $data = []
    ) {
        parent::__construct($context, $checkoutSession, $orderConfig, $httpContext, $data);
        $this->paymentHelper = $paymentHelper;
        $this->logger = $logger;
        $this->notificationProcessor = $notificationProcessor;
        $this->order = $this->_checkoutSession->getLastRealOrder();
    }

    /**
     * @return bool
     * @throws NoSuchEntityException
     */
    public function canRetryPayment(): bool
    {
        return $this->paymentHelper->isRetryPaymentActiveForOrder($this->order);
    }

    /**
     * Returns retry payment url
     *
     * @return string
     */
    public function getRetryPaymentUrl(): string
    {
        return $this->paymentHelper->getRetryPaymentUrl((int) $this->order->getEntityId());
    }

    /**
     * @return Phrase
     */
    public function getPaymentStatusPhrase()
    {
        $allPayments = $this->order->getAllPayments();
        $status = end($allPayments)->getAdditionalInformation(PaymentField::STATUS_FIELD_NAME);
        switch ($status) {
            case Status::STATUS_REJECTED:
                return __('Your payment has been rejected.');
            case Status::STATUS_ERROR:
                return __('An error occurred during your payment process.');
            case Status::STATUS_EXPIRED:
                return __('Your payment has been expired.');
            case Status::STATUS_NEW:
            case Status::STATUS_PENDING:
                return __('Your payment process has not been completed.');
            case Status::STATUS_CONFIRMED:
                return __('Your payment has been completed.');
        }
    }
}
