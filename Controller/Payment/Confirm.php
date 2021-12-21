<?php

namespace Paynow\PaymentGateway\Controller\Payment;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect as ResponseRedirect;
use Magento\Framework\UrlInterface;
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
     * @var ResponseRedirect
     */
    private $redirectResult;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @param Logger $logger
     * @param CheckoutSession $checkoutSession
     * @param Context $context
     * @param PageFactory $pageFactory
     * @param UrlInterface $urlBuilder
     */
    public function __construct(
        Logger $logger,
        CheckoutSession $checkoutSession,
        Context $context,
        PageFactory $pageFactory,
        UrlInterface $urlBuilder
    ) {
        parent::__construct($context);
        $this->pageFactory = $pageFactory;
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->redirectResult = $this->resultRedirectFactory->create();
        $this->urlBuilder = $urlBuilder;
    }

    public function execute()
    {
        $paymentData = $this->preparePaymentData();
        if ($paymentData) {
            $resultPage = $this->pageFactory->create();
            $resultPage->addHandle(self::CONFIRM_BLOCK_NAME);
            $paymentData = $this->preparePaymentData();
            $block = $resultPage->getLayout()->getBlock(self::CONFIRM_BLOCK_NAME);
            $block->setData('payment_id', $paymentData['payment_id']);
            $block->setData('payment_status', $paymentData['payment_status']);
            $resultPage->setHeader('Cache-Control', 'no-store, no-cache, must-revalidate, max-age=0', true);
            return $resultPage;
        }

        return $this->redirectResult->setUrl($this->urlBuilder->getUrl('paynow/checkout/success'));
    }

    /**
     * @return array
     */
    private function preparePaymentData(): ?array
    {
        /** @var Order */
        $order = $this->checkoutSession->getLastRealOrder();
        if ($order) {
            $allPayments = $order->getAllPayments();
            $lastPayment = end($allPayments);
            if ($lastPayment) {
                $paymentId = $lastPayment->getAdditionalInformation(PaymentField::PAYMENT_ID_FIELD_NAME);
                $paymentStatus = $lastPayment->getAdditionalInformation(PaymentField::STATUS_FIELD_NAME);

                $this->logger->debug(
                    "Retrieved payment data from checkout session",
                    ["paymentId" => $paymentId,
                     "paymentStatus" => $paymentStatus,
                     "orderId" => $order->getIncrementId()
                    ]
                );

                return ["payment_id" => $paymentId, "payment_status" => $paymentStatus];
            }
        }
        return null;
    }
}
