<?php

namespace Paynow\PaymentGateway\Controller\Payment;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\View\Result\PageFactory;
use Magento\Sales\Model\Order;
use Paynow\PaymentGateway\Helper\PaymentField;
use Paynow\PaymentGateway\Model\Logger\Logger;

/**
 * Class Confirm
 *
 * @package Paynow\PaymentGateway\Controller\Payment
 */
class Confirm extends Action
{
    private const CONFIRM_BLOCK_NAME = 'paynow_payment_confirm';

    /**
     * @var PageFactory
     */
    protected $pageFactory;

    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @param Logger $logger
     * @param CheckoutSession $checkoutSession
     * @param Context $context
     * @param PageFactory $pageFactory
     */
    public function __construct(
        Logger $logger,
        CheckoutSession $checkoutSession,
        Context $context,
        PageFactory $pageFactory
    ) {
        $this->pageFactory = $pageFactory;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        return parent::__construct($context);
    }

    public function execute()
    {
        $resultPage = $this->pageFactory->create();
        $resultPage->addHandle(self::CONFIRM_BLOCK_NAME);
        $this->preparePaymentData($resultPage);
        $resultPage->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
        return $resultPage;
    }

    /**
     * @param $resultPage
     * @return void
     */
    private function preparePaymentData($resultPage)
    {
        /** @var Order */
        $order = $this->checkoutSession->getLastRealOrder();
        $allPayments = $order->getAllPayments();
        $lastPayment = end($allPayments);
        $paymentId = $lastPayment->getAdditionalInformation(PaymentField::PAYMENT_ID_FIELD_NAME);
        $paymentStatus = $lastPayment->getAdditionalInformation(PaymentField::STATUS_FIELD_NAME);

        $this->logger->debug(
            "Retrieved payment data from checkout session",
            ["paymentId" => $paymentId, "paymentStatus" => $paymentStatus, "orderId" => $order->getIncrementId()]
        );

        $block = $resultPage->getLayout()->getBlock(self::CONFIRM_BLOCK_NAME);
        $block->setData('payment_id', $paymentId);
        $block->setData('payment_status', $paymentStatus);
    }
}
