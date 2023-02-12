<?php

namespace Paynow\PaymentGateway\Controller\Checkout;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect as ResponseRedirect;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;
use Paynow\PaymentGateway\Helper\LockingHelper;
use Paynow\PaymentGateway\Helper\NotificationProcessor;
use Paynow\PaymentGateway\Helper\PaymentField;
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
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var NotificationProcessor
     */
    private $notificationProcessor;

    /**
     * @var PaymentStatusService
     */
    private $paymentStatusService;

    /**
     * @var LockingHelper
     */
    private $lockingHelper;

    /**
     * Success constructor.
     *
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param Logger $logger
     * @param UrlInterface $urlBuilder
     * @param NotificationProcessor $notificationProcessor
     * @param PaymentStatusService $paymentStatusService
     * @param LockingHelper $lockingHelper
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        Logger $logger,
        UrlInterface $urlBuilder,
        NotificationProcessor $notificationProcessor,
        PaymentStatusService $paymentStatusService,
        LockingHelper         $lockingHelper
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->redirectResult = $this->resultRedirectFactory->create();
        $this->urlBuilder = $urlBuilder;
        $this->notificationProcessor = $notificationProcessor;
        $this->order = $this->checkoutSession->getLastRealOrder();
        $this->paymentStatusService = $paymentStatusService;
        $this->lockingHelper = $lockingHelper;
    }

    /**
     * @return ResponseRedirect
     */
    public function execute(): ResponseRedirect
    {
        if ($this->shouldRetrieveStatus()) {
            $this->retrievePaymentStatusAndUpdateOrder();
        }

        $this->redirectResult->setUrl(
            $this->urlBuilder->getUrl('checkout/onepage/success')
        );

        return $this->redirectResult;
    }

    private function retrievePaymentStatusAndUpdateOrder()
    {
        $allPayments = $this->order->getAllPayments();
        $lastPaymentId = end($allPayments)->getAdditionalInformation(PaymentField::PAYMENT_ID_FIELD_NAME);
        $status = $this->paymentStatusService->getStatus($lastPaymentId);
        $loggerContext = [PaymentField::PAYMENT_ID_FIELD_NAME => $lastPaymentId];
        try {
            $this->notificationProcessor->process(
                $lastPaymentId,
                $status,
                $this->order->getIncrementId(),
                date("Y-m-d\TH:i:s"),
                true
            );
            $this->lockingHelper->delete($this->order->getIncrementId());
        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage(), $loggerContext);
            $this->lockingHelper->delete($this->order->getIncrementId());
        }
    }

    /**
     * @return bool
     */
    private function shouldRetrieveStatus(): bool
    {
        return $this->getRequest()->getParam('paymentStatus') &&
            $this->getRequest()->getParam('paymentId') &&
            $this->order &&
            count($this->order->getAllPayments()) > 0;
    }
}
