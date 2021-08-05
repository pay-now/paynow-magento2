<?php

namespace Paynow\PaymentGateway\Helper;

use Magento\Sales\Model\Order;
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
     * @var PaymentHelper
     */
    private $paymentHelper;

    public function __construct(OrderFactory $orderFactory, Logger $logger, PaymentHelper $paymentHelper)
    {
        $this->orderFactory = $orderFactory;
        $this->logger = $logger;
        $this->paymentHelper = $paymentHelper;
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
            PaymentField::PAYMENT_ID_FIELD_NAME => $paymentId,
            PaymentField::EXTERNAL_ID_FIELD_NAME => $externalId
        ];

        /** @var Order */
        $this->order = $this->orderFactory->create()->loadByIncrementId($externalId);
        if (!$this->order->getId()) {
            throw new OrderNotFound($externalId);
        }

        $paymentAdditionalInformation = $this->order->getPayment()->getAdditionalInformation();
        $orderPaymentStatus = $paymentAdditionalInformation[PaymentField::STATUS_FIELD_NAME];
        $finalPaymentStatus = $orderPaymentStatus == Status::STATUS_CONFIRMED;

        if ($finalPaymentStatus) {
            $this->logger->info('Order has paid status. Skipped notification processing', $this->loggerContext);
            throw new OrderHasBeenAlreadyPaidException($externalId, $paymentId);
        }

        if ( ! $this->isCorrectStatus($orderPaymentStatus, $status)) {
            throw new OrderPaymentStatusTransitionException($orderPaymentStatus, $status);
        }

        $this->order->getPayment()->setAdditionalInformation(PaymentField::STATUS_FIELD_NAME, $status);

        switch ($status) {
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
        }
        $this->order->save();
    }

    private function paymentPending()
    {
        $message = __('Awaiting payment confirmation from Paynow.');
        if ($this->paymentHelper->isOrderStatusChangeActive()) {
            $this->order
                ->setState(Order::STATE_PENDING_PAYMENT)
                ->addStatusToHistory(Order::STATE_PENDING_PAYMENT, $message);
        } else {
            $this->order->addCommentToStatusHistory($message);
        }
        $this->order->getPayment()->setIsClosed(false);
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
        $message = __('Payment has not been authorized by the buyer.');
        if ($this->order->canCancel() && !$this->paymentHelper->isRetryPaymentActive()) {
            if ($this->paymentHelper->isOrderStatusChangeActive()) {
                $this->order
                    ->setState(Order::STATE_CANCELED)
                    ->addStatusToHistory(Order::STATE_CANCELED, $message);
                $this->order->cancel();
                $this->logger->info('Order has been canceled', $this->loggerContext);
            } else {
                $this->order->addCommentToStatusHistory($message);
            }
        } else {
            if ($this->paymentHelper->isOrderStatusChangeActive()) {
                $this->order
                    ->setState(Order::STATE_PAYMENT_REVIEW)
                    ->addStatusToHistory(Order::STATE_PAYMENT_REVIEW, $message);
                $this->logger->warning('Order has not been canceled because retry payment is active', $this->loggerContext);
            } else {
                $this->order->addCommentToStatusHistory($message);
            }
        }
    }

    private function paymentError()
    {
        $message = __('Payment has been ended with an error.');
        if (!$this->paymentHelper->isRetryPaymentActive()) {
            if ($this->paymentHelper->isOrderStatusChangeActive()) {
                $this->order
                    ->setState(Order::STATE_PAYMENT_REVIEW)
                    ->addStatusToHistory(Order::STATE_PAYMENT_REVIEW, $message);
            } else {
                $this->order->addCommentToStatusHistory($message);
            }
        }
    }

    private function paymentExpired()
    {
        $message = __('Payment has been expired.');
        if ($this->paymentHelper->isOrderStatusChangeActive()) {
            $this->order
                ->setState(Order::STATE_PAYMENT_REVIEW)
                ->addStatusToHistory(Order::STATE_PAYMENT_REVIEW, $message);
        } else {
            $this->order->addCommentToStatusHistory($message);
        }
        $this->order->getPayment()->setIsClosed(true);
    }

    private function isCorrectStatus($previousStatus, $nextStatus)
    {
        $paymentStatusFlow = [
            Status::STATUS_NEW => [
                Status::STATUS_NEW,
                Status::STATUS_PENDING,
                Status::STATUS_ERROR,
                Status::STATUS_EXPIRED,
                Status::STATUS_CONFIRMED,
                Status::STATUS_REJECTED
            ],
            Status::STATUS_PENDING => [
                Status::STATUS_CONFIRMED,
                Status::STATUS_REJECTED,
                Status::STATUS_EXPIRED
            ],
            Status::STATUS_REJECTED => [Status::STATUS_PENDING, Status::STATUS_CONFIRMED],
            Status::STATUS_CONFIRMED => [],
            Status::STATUS_ERROR => [
                Status::STATUS_CONFIRMED,
                Status::STATUS_REJECTED
            ],
            Status::STATUS_EXPIRED => []
        ];

        $previousStatusExists = isset($paymentStatusFlow[$previousStatus]);
        $isChangePossible = in_array($nextStatus, $paymentStatusFlow[$previousStatus]);
        return $previousStatusExists && $isChangePossible;
    }
}
