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
use Paynow\PaymentGateway\Helper\PaymentStatusService;
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
     * @var PaymentStatusService
     */
    private $paymentStatusService;

    /**
     * @param CheckoutSession $checkoutSession
     * @param JsonFactory $resultJsonFactory
     * @param Context $context
     * @param PaymentStatusService $paymentStatusService
     */
    public function __construct(
        CheckoutSession $checkoutSession,
        JsonFactory $resultJsonFactory,
        Context $context,
        PaymentStatusService $paymentStatusService
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->resultJsonFactory = $resultJsonFactory;
        $this->paymentStatusService = $paymentStatusService;
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
        if ($lastPaymentId == $order->getIncrementId() . '_UNKNOWN') {
            $status =  \Paynow\Model\Payment\Status::STATUS_PENDING;
        } else {
            $status = $this->paymentStatusService->getStatus($lastPaymentId, $order->getIncrementId());
        }

        if ($status) {
            return $resultJson->setData([
                'paymentId'   => $lastPaymentId,
                'payment_status' => $status,
                'payment_status_label' => __(PaymentStatusLabel::${$status})
            ]);
        }

        return $resultJson->setStatusHeader(404, null, 'Not Found');
    }
}
