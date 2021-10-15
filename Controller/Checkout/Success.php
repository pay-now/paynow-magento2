<?php

namespace Paynow\PaymentGateway\Controller\Checkout;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect as ResponseRedirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\UrlInterface;
use Magento\Sales\Model\Order;
use Paynow\PaymentGateway\Helper\NotificationProcessor;
use Paynow\PaymentGateway\Helper\PaymentField;
use Paynow\PaymentGateway\Helper\PaymentHelper;
use Paynow\PaymentGateway\Model\Logger\Logger;
use Paynow\Service\Payment;

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
     * Redirect constructor.
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param Logger $logger
     * @param UrlInterface $urlBuilder
     * @param NotificationProcessor $notificationProcessor
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        Logger $logger,
        UrlInterface $urlBuilder,
        NotificationProcessor $notificationProcessor,
        PaymentHelper $paymentHelper
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
    }

    /**
     * @return ResponseInterface|ResponseRedirect|ResultInterface
     */
    public function execute()
    {
        if ($this->shouldRetrieveStatus()) {
            $this->retrievePaymentStatusAndUpdateOrder();
        }
        $isRetry = $this->order->getPayment()->hasAdditionalInformation(PaymentField::IS_PAYMENT_RETRY_FIELD_NAME);
        $this->redirectResult->setUrl($this->getRedirectUrl($isRetry));

        return $this->redirectResult;
    }

    public function getRedirectUrl(bool $forRetryPayment): string
    {
        if ($forRetryPayment) {
            return $this->urlBuilder->getUrl('sales/order/history');
        }

        return $this->urlBuilder->getUrl('checkout/onepage/success');
    }

    private function retrievePaymentStatusAndUpdateOrder()
    {
        $paymentId = $this->order->getPayment()->getAdditionalInformation(PaymentField::PAYMENT_ID_FIELD_NAME);
        $loggerContext = [PaymentField::PAYMENT_ID_FIELD_NAME => $paymentId];
        try {
            $service = new Payment($this->paymentHelper->initializePaynowClient());
            $paymentStatusObject  = $service->status($paymentId);
            $status = $paymentStatusObject ->getStatus();
            $this->logger->debug(
                "Retrieved status response",
                array_merge($loggerContext, [$status])
            );
            $this->notificationProcessor->process($paymentId, $status, $this->order->getIncrementId());

        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage(), $loggerContext);
        }
    }

    /**
     * @return bool
     */
    private function shouldRetrieveStatus()
    {
        return $this->getRequest()->getParam('paymentStatus') &&
            $this->getRequest()->getParam('paymentId') &&
            $this->order;
    }

}
