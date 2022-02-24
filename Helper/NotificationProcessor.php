<?php

namespace Paynow\PaymentGateway\Helper;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\OrderFactory;
use Paynow\Model\Payment\Status;
use Paynow\PaymentGateway\Model\Exception\OrderHasBeenAlreadyPaidException;
use Paynow\PaymentGateway\Model\Exception\OrderNotFound;
use Paynow\PaymentGateway\Model\Exception\OrderPaymentStatusTransitionException;
use Paynow\PaymentGateway\Model\Logger\Logger;

/**
 * Class NotificationProcessor
 *
 * @package Paynow\PaymentGateway\Helper
 */
class NotificationProcessor
{
    /**
     * @var Magento\Sales\Model\OrderFactory
     */
    private $orderFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var Order
     */
    private $order;

    /**
     * @var array
     */
    private $loggerContext;

    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    public function __construct(
        OrderFactory $orderFactory,
        Logger $logger,
        ConfigHelper $configHelper,
        OrderRepositoryInterface $orderRepository
    ) {
        $this->orderFactory = $orderFactory;
        $this->logger = $logger;
        $this->configHelper = $configHelper;
        $this->orderRepository = $orderRepository;
    }

    /**
     * @param $paymentId
     * @param $status
     * @param $externalId
     * @throws OrderNotFound
     * @throws OrderHasBeenAlreadyPaidException
     * @throws OrderPaymentStatusTransitionException
     */
    public function process($paymentId, $status, $externalId)
    {
        $this->loggerContext = [
            PaymentField::EXTERNAL_ID_FIELD_NAME => $externalId,
            PaymentField::PAYMENT_ID_FIELD_NAME => $paymentId,
            PaymentField::STATUS_FIELD_NAME => $status
        ];
        $this->logger->info("Processing payment status notification", $this->loggerContext);

        /** @var Order */
        $this->order = $this->orderFactory->create()->loadByIncrementId($externalId);
        if (!$this->order->getId()) {
            throw new OrderNotFound($externalId);
        }

        $paymentAdditionalInformation = $this->order->getPayment()->getAdditionalInformation();
        $orderPaymentStatus = $paymentAdditionalInformation[PaymentField::STATUS_FIELD_NAME];
        $finalPaymentStatus = $orderPaymentStatus == Status::STATUS_CONFIRMED;

        if ($finalPaymentStatus) {
            throw new OrderHasBeenAlreadyPaidException($externalId, $paymentId);
        }

        if (! $this->isCorrectStatus($orderPaymentStatus, $status)) {
            throw new OrderPaymentStatusTransitionException($orderPaymentStatus, $status);
        }

        $this->order->getPayment()->setAdditionalInformation(PaymentField::STATUS_FIELD_NAME, $status);

        switch ($status) {
            case Status::STATUS_NEW:
                $this->paymentNew($paymentId);
                break;
            case Status::STATUS_PENDING:
                $this->paymentPending();
                break;
            case Status::STATUS_REJECTED:
                $this->paymentRejected();
                break;
            case Status::STATUS_CONFIRMED:
                $this->paymentConfirmed();
                break;
            case Status::STATUS_ERROR:
                $this->paymentError();
                break;
            case Status::STATUS_EXPIRED:
                $this->paymentExpired();
                break;
            case Status::STATUS_ABANDONED:
                $this->paymentAbandoned();
                break;
        }
        $this->orderRepository->save($this->order);
        $this->logger->info("Finished processing payment status notification", $this->loggerContext);
    }

    private function paymentNew($paymentId)
    {
        $payment = $this->order->getPayment();

        $payment
            ->setIsTransactionPending(true)
            ->setTransactionId($paymentId)
            ->setLastTransId($paymentId)
            ->setIsTransactionClosed(false)
            ->setAdditionalInformation(
                PaymentField::PAYMENT_ID_FIELD_NAME,
                $paymentId
            )
            ->setAdditionalInformation(
                PaymentField::STATUS_FIELD_NAME,
                Status::STATUS_NEW
            );
        $payment->addTransaction(Transaction::TYPE_AUTH);
        $this->order->setPayment($payment);
        $this->orderRepository->save($this->order);

        $message = __('New payment created for order. Transaction ID: ') . $paymentId;

        if ($this->configHelper->isOrderStatusChangeActive()) {
            $this->order
                ->setState(Order::STATE_PENDING_PAYMENT)
                ->addStatusToHistory(Order::STATE_PENDING_PAYMENT, $message);
        } else {
            $this->order->addCommentToStatusHistory($message);
        }
    }

    private function paymentPending()
    {
        $message = __('Awaiting payment confirmation from Paynow. Transaction ID: ') . $this->order->getPayment()->getAdditionalInformation(PaymentField::PAYMENT_ID_FIELD_NAME);
        if ($this->configHelper->isOrderStatusChangeActive()) {
            $this->order
                ->setState(Order::STATE_PENDING_PAYMENT)
                ->addStatusToHistory(Order::STATE_PENDING_PAYMENT, $message);
        } else {
            $this->order->addCommentToStatusHistory($message);
        }
        $this->order->getPayment()->setIsClosed(false);
    }

    private function paymentAbandoned()
    {
        $message = __('Payment has been abandoned. Transaction ID: ') . $this->order->getPayment()->getAdditionalInformation(PaymentField::PAYMENT_ID_FIELD_NAME);
        if ($this->configHelper->isOrderStatusChangeActive()) {
            $this->order
                ->setState(Order::STATE_PENDING_PAYMENT)
                ->addStatusToHistory(Order::STATE_PENDING_PAYMENT, $message);
        } else {
            $this->order->addCommentToStatusHistory($message);
        }
        $this->order->getPayment()->setIsClosed(true);
    }

    private function paymentConfirmed()
    {
        if ($this->order->getPayment()->canCapture()) {
            $this->order->getPayment()->capture();
            $this->logger->info('Payment has been captured', $this->loggerContext);
        } else {
            $this->logger->warning('Payment has not been captured', $this->loggerContext);
        }
    }

    private function paymentRejected()
    {
        $message = __('Payment has not been authorized by the buyer. Transaction ID: ') . (string)$this->order->getPayment()->getAdditionalInformation(PaymentField::PAYMENT_ID_FIELD_NAME);
        if ($this->configHelper->isOrderStatusChangeActive()) {
            $this->order
                ->setState(Order::STATE_PAYMENT_REVIEW)
                ->addStatusToHistory(Order::STATE_PAYMENT_REVIEW, $message);
            $this->logger->info('Order has been canceled', $this->loggerContext);
        } else {
            $this->order->addCommentToStatusHistory($message);
        }

            $this->order->getPayment()->setIsClosed(true);
    }

    /**
     * Sets payment errored
     */
    private function paymentError()
    {
        $message = __('Payment has been ended with an error. Transaction ID: ') . $this->order->getPayment()->getAdditionalInformation(PaymentField::PAYMENT_ID_FIELD_NAME);
        if ($this->configHelper->isOrderStatusChangeActive()) {
            $this->order
                ->setState(Order::STATE_PAYMENT_REVIEW)
                ->addStatusToHistory(Order::STATE_PAYMENT_REVIEW, $message);

            $this->order->getPayment()->setIsClosed(true);
        }
    }

    /**
     * Sets payment as expired
     */
    private function paymentExpired()
    {
        $message = __('Payment has been expired. Transaction ID: ') . $this->order->getPayment()->getAdditionalInformation(PaymentField::PAYMENT_ID_FIELD_NAME);
        $this->order->addCommentToStatusHistory($message);
        $this->order->getPayment()->deny();
    }

    /**
     * @param $previousStatus
     * @param $nextStatus
     *
     * @return bool
     */
    private function isCorrectStatus($previousStatus, $nextStatus): bool
    {
        $paymentStatusFlow = [
            Status::STATUS_NEW => [
                Status::STATUS_PENDING,
                Status::STATUS_ERROR,
                Status::STATUS_EXPIRED,
                Status::STATUS_CONFIRMED,
                Status::STATUS_REJECTED
            ],
            Status::STATUS_PENDING => [
                Status::STATUS_CONFIRMED,
                Status::STATUS_REJECTED,
                Status::STATUS_EXPIRED,
                Status::STATUS_ABANDONED
            ],
            Status::STATUS_REJECTED => [
                Status::STATUS_PENDING,
                Status::STATUS_CONFIRMED,
                Status::STATUS_ABANDONED,
                Status::STATUS_NEW
            ],
            Status::STATUS_CONFIRMED => [],
            Status::STATUS_ERROR => [
                Status::STATUS_CONFIRMED,
                Status::STATUS_REJECTED,
                Status::STATUS_ABANDONED,
                Status::STATUS_NEW
            ],
            Status::STATUS_EXPIRED => [],
            Status::STATUS_ABANDONED => [Status::STATUS_NEW]
        ];

        $previousStatusExists = isset($paymentStatusFlow[$previousStatus]);
        $isChangePossible = in_array($nextStatus, $paymentStatusFlow[$previousStatus]);
        return $previousStatusExists && $isChangePossible;
    }
}
