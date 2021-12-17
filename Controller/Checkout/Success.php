<?php

namespace Paynow\PaymentGateway\Controller\Checkout;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect as ResponseRedirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;
use Paynow\PaymentGateway\Helper\NotificationProcessor;
use Paynow\PaymentGateway\Helper\PaymentField;
use Paynow\PaymentGateway\Helper\PaymentHelper;
use Paynow\PaymentGateway\Helper\PaymentStatusService;
use Paynow\PaymentGateway\Model\Logger\Logger;

/**
 * Class Return
 *
 * @package Paynow\PaymentGateway\Controller\Checkout
 */
class Success extends Action
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ResponseRedirect
     */
    private $redirectResult;

    /**
     * @var Order
     */
    private $order;
    /**
     * @var \Magento\Framework\App\RequestInterface
     */
    private $request;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    /**
     * @var NotificationProcessor
     */
    private $notificationProcessor;

    /**
     * @var PaymentStatusService
     */
    private $paymentStatusService;

    /**
     * Redirect constructor.
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param Logger $logger
     * @param UrlInterface $urlBuilder
     * @param NotificationProcessor $notificationProcessor
     * @param PaymentHelper $paymentHelper
     * @param PaymentStatusService $paymentStatusService
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        Logger $logger,
        UrlInterface $urlBuilder,
        NotificationProcessor $notificationProcessor,
        PaymentHelper $paymentHelper,
        PaymentStatusService $paymentStatusService
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->redirectResult = $this->resultRedirectFactory->create();
        $this->request = $this->getRequest();
        $this->urlBuilder = $urlBuilder;
        $this->notificationProcessor = $notificationProcessor;
        $this->paymentHelper = $paymentHelper;
        $this->order = $this->checkoutSession->getLastRealOrder();
        $this->paymentStatusService = $paymentStatusService;
    }

    /**
     * @return ResponseInterface|ResponseRedirect|ResultInterface
     */
    public function execute()
    {
        $isRetry = $this->order &&
            $this->order->getPayment() &&
            $this->order->getPayment()->hasAdditionalInformation(PaymentField::IS_PAYMENT_RETRY_FIELD_NAME);

        if ($this->shouldRetrieveStatus() && ! $isRetry) {
            $this->retrievePaymentStatusAndUpdateOrder();
        }
        $this->redirectResult->setUrl($this->getRedirectUrl($isRetry));

        return $this->redirectResult;
    }

    /**
     * @param bool $forRetryPayment
     * @return string
     */
    public function getRedirectUrl(bool $forRetryPayment): string
    {
        if ($forRetryPayment) {
            return $this->urlBuilder->getUrl('sales/order/history');
        }

        return $this->urlBuilder->getUrl('checkout/onepage/success');
    }

    private function retrievePaymentStatusAndUpdateOrder()
    {
        $allPayments = $this->order->getAllPayments();
        $lastPaymentId = end($allPayments)->getAdditionalInformation(PaymentField::PAYMENT_ID_FIELD_NAME);
        $status = $this->paymentStatusService->getPaymentStatus($lastPaymentId);
        $this->notificationProcessor->process($lastPaymentId, $status, $this->order->getIncrementId());
    }

    /**
     * @return bool
     */
    private function shouldRetrieveStatus(): bool
    {
        return $this->getRequest()->getParam('paymentStatus') &&
            $this->getRequest()->getParam('paymentId') &&
            $this->order;
    }
}
