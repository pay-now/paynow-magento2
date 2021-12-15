<?php

namespace Paynow\PaymentGateway\Controller\Payment;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Paynow\PaymentGateway\Helper\PaymentField;
use Paynow\PaymentGateway\Helper\PaymentHelper;
use Paynow\PaymentGateway\Helper\PaymentStatusLabel;
use Paynow\PaymentGateway\Model\Logger\Logger;
use Paynow\Service\Payment;

/**
 * Class Status
 *
 * @package Paynow\PaymentGateway\Controller\Payment
 */
class Status extends Action
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param CheckoutSession $checkoutSession
     * @param JsonFactory $resultJsonFactory
     * @param PaymentHelper $paymentHelper
     * @param Logger $logger
     * @param Context $context
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        JsonFactory $resultJsonFactory,
        PaymentHelper $paymentHelper,
        Logger $logger,
        Context $context
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->paymentHelper = $paymentHelper;
        $this->logger = $logger;
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        /** @var Order */
        $order = $this->checkoutSession->getLastRealOrder();
        if (!$order) {
            return $resultJson->setStatusHeader(404, null, 'Not Found');
        }
        $allPayments = $order->getAllPayments();
        $lastPaymentId = end($allPayments)->getAdditionalInformation(PaymentField::PAYMENT_ID_FIELD_NAME);
        $loggerContext = [PaymentField::PAYMENT_ID_FIELD_NAME => $lastPaymentId];
        try {
            $service = new Payment($this->paymentHelper->initializePaynowClient());
            $paymentStatusObject  = $service->status($lastPaymentId);
            $status = $paymentStatusObject ->getStatus();
            $this->logger->debug(
                "Retrieved status response",
                array_merge($loggerContext, [$status])
            );

            return $resultJson->setData([
                'paymentId'   => $lastPaymentId,
                'payment_status' => $status,
                'payment_status_label' => __(PaymentStatusLabel::${$status})
            ]);

        } catch (\Exception $exception) {
            $this->logger->error($exception->getMessage(), $loggerContext);
        }

        return $resultJson->setStatusHeader(404, null, 'Not Found');
    }
}
