<?php

namespace Paynow\PaymentGateway\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect as ResponseRedirect;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order\Payment;
use Paynow\PaymentGateway\Helper\ConfigHelper;
use Paynow\PaymentGateway\Helper\PaymentField;
use Paynow\PaymentGateway\Helper\PaymentHelper;
use Paynow\PaymentGateway\Model\Logger\Logger;

/**
 * Class Retry
 *
 * @package Paynow\PaymentGateway\Controller\Payment
 */
class Retry extends Action
{
    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var ResponseRedirect
     */
    private $redirectResult;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * Retry constructor.
     * @param Context $context
     * @param OrderRepositoryInterface $orderRepository
     * @param ConfigHelper $configHelper
     * @param PaymentHelper $paymentHelper
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
        ConfigHelper $configHelper,
        PaymentHelper $paymentHelper,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
        $this->configHelper = $configHelper;
        $this->paymentHelper = $paymentHelper;
        $this->redirectResult = $this->resultRedirectFactory->create();
        $this->logger = $logger;
    }

    /**
     * @return ResponseRedirect
     */
    public function execute()
    {
        if (!$this->configHelper->isRetryPaymentActive()) {
            $this->messageManager->addErrorMessage(__('Retry payment is not active.'));
            $this->redirectResult->setPath('sales/order/history', ['_secure' => $this->getRequest()->isSecure()]);
        }

        $orderId = (int)$this->getRequest()->getParams()['order_id'];
        /** @var OrderInterface */
        $order = $this->orderRepository->get($orderId);

        if (!$this->paymentHelper->isRetryPaymentActiveForOrder($order)) {
            $this->messageManager->addErrorMessage(__('Retry payment is not available for the order.'));
            $this->redirectResult->setPath('checkout/cart', ['_secure' => $this->getRequest()->isSecure()]);
        }

        $this->authorizeNewPayment($order);

        return $this->redirectResult;
    }

    /**
     * Authorize new payment and set redirect url
     *
     * @param OrderInterface $order
     */
    private function authorizeNewPayment(OrderInterface $order)
    {
        $paymentAuthorization = $order->getPayment()
            ->setAdditionalInformation(PaymentField::IS_PAYMENT_RETRY_FIELD_NAME, true)
            ->authorize(true, $order->getBaseTotalDue());
        $redirectUrl = $paymentAuthorization->getAdditionalInformation(PaymentField::REDIRECT_URL_FIELD_NAME);
        $paymentId = $paymentAuthorization->getAdditionalInformation(PaymentField::PAYMENT_ID_FIELD_NAME);

        if ($redirectUrl) {
            $this->addPayment($order->getPayment(), $order, $paymentAuthorization);
            $this->logger->info(
                'Redirecting for retry payment to payment provider page',
                [
                    PaymentField::EXTERNAL_ID_FIELD_NAME => $order->getRealOrderId(),
                    PaymentField::PAYMENT_ID_FIELD_NAME => $paymentId
                ]
            );
            $this->redirectResult->setUrl($redirectUrl);
        }
    }

    /**
     * @param Payment $payment
     * @param OrderInterface $order
     * @param $paymentAuthorization
     * @throws LocalizedException
     */
    private function addPayment(Payment $payment, OrderInterface $order, $paymentAuthorization)
    {
        $payment->setIsTransactionPending(true)
            ->setTransactionId($paymentAuthorization[PaymentField::PAYMENT_ID_FIELD_NAME])
            ->setAdditionalInformation(
                PaymentField::PAYMENT_ID_FIELD_NAME,
                $paymentAuthorization->getAdditionalInformation(PaymentField::PAYMENT_ID_FIELD_NAME)
            )
            ->setAdditionalInformation(
                PaymentField::STATUS_FIELD_NAME,
                $paymentAuthorization->getAdditionalInformation(PaymentField::STATUS_FIELD_NAME)
            )
            ->setIsTransactionClosed(false);

        $order->setPayment($payment);
        $this->orderRepository->save($order);
    }
}
