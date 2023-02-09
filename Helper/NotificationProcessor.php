<?php

namespace Paynow\PaymentGateway\Helper;

use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment\Transaction;
use Magento\Sales\Model\OrderFactory;
use Paynow\Model\Payment\Status;
use Paynow\PaymentGateway\Model\Exception\NotificationRetryProcessing;
use Paynow\PaymentGateway\Model\Exception\NotificationStopProcessing;
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
    private $context;

    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    public function __construct(
        OrderFactory                            $orderFactory,
        Logger                                  $logger,
        ConfigHelper                            $configHelper,
        OrderRepositoryInterface                $orderRepository
    ) {
        $this->orderFactory                   = $orderFactory;
        $this->logger                         = $logger;
        $this->configHelper                   = $configHelper;
        $this->orderRepository                = $orderRepository;
    }

    /**
     * @param      $paymentId
     * @param      $status
     * @param      $externalId
     * @param      $modifiedAt
     * @param bool $force
     * @throws \Magento\Framework\Exception\LocalizedException
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Paynow\PaymentGateway\Model\Exception\NotificationRetryProcessing
     * @throws \Paynow\PaymentGateway\Model\Exception\NotificationStopProcessing
     */
    public function process($paymentId, $status, $externalId, $modifiedAt, $force = false)
    {
        $this->context = [
            PaymentField::PAYMENT_ID_FIELD_NAME  => $paymentId,
            PaymentField::EXTERNAL_ID_FIELD_NAME => $externalId,
            PaymentField::STATUS_FIELD_NAME      => $status,
            PaymentField::MODIFIED_AT            => $modifiedAt
        ];

        $isNew = $status == Status::STATUS_NEW;
        $isConfirmed = $status == Status::STATUS_CONFIRMED;

        // Delay NEW status, in case when API sends notifications in bundle,
        // status NEW should finish processing at the very end
        // Delay CONFIRMED status, in case when API sends notifications in the same
        // time as Magento retrieve status o
        if ($isNew || (!$force && $isConfirmed)) {
            // phpcs:ignore Magento2.Functions.DiscouragedFunction
            sleep(3);
        }

        /** @var Order */
        $this->order = $this->orderFactory->create()->loadByIncrementId($externalId);
        if (!$this->order->getId()) {
            throw new NotificationStopProcessing(
                'Skipped processing. Order not found.',
                $this->context
            );
        }

        $paymentAdditionalInformation = $this->order->getPayment()->getAdditionalInformation();
        $orderPaymentId = $paymentAdditionalInformation[PaymentField::PAYMENT_ID_FIELD_NAME];
        $orderPaymentStatus = $paymentAdditionalInformation[PaymentField::STATUS_FIELD_NAME];
        $orderPaymentStatusDate = $paymentAdditionalInformation[PaymentField::MODIFIED_AT] ?? '';

        $this->context += [
            'orderPaymentId'         => $orderPaymentId,
            'orderPaymentStatus'     => $orderPaymentStatus,
            'orderPaymentStatusDate' => $orderPaymentStatusDate,
        ];

        if ($orderPaymentStatus == Status::STATUS_CONFIRMED) {
            if ($isConfirmed && $orderPaymentId != $paymentId) {
                $this->addConfirmPaymentToOrderHistory($paymentId);
            }
            throw new NotificationStopProcessing(
                'Skipped processing. Order has paid status.',
                $this->context
            );
        }

        if ($orderPaymentStatus == $status && $orderPaymentId == $paymentId) {
            throw new NotificationStopProcessing(
                sprintf(
                    'Skipped processing. Transition status (%s) already consumed.',
                    $status
                ),
                $this->context
            );
        }

        if ($orderPaymentId != $paymentId && !$isNew && !$force && !$isConfirmed) {
            $this->retryProcessingNTimes(
                'Skipped processing. Order has another active payment.'
            );
        }

        if (!empty($orderPaymentStatusDate) && $orderPaymentStatusDate > $modifiedAt) {
            if (!$isNew || $orderPaymentId == $paymentId) {
                throw new NotificationStopProcessing(
                    'Skipped processing. Order has newer status. Time travels are prohibited.',
                    $this->context
                );
            }
        }

        if (!$this->isCorrectStatus($orderPaymentStatus, $status) && !$isNew && !$force && !$isConfirmed) {
            $this->retryProcessingNTimes(
                sprintf(
                    'Order status transition from %s to %s is incorrect.',
                    $orderPaymentStatus,
                    $status
                )
            );
        }

        $this->order->getPayment()->setAdditionalInformation(
            PaymentField::STATUS_FIELD_NAME,
            $status
        );
        $this->order->getPayment()->setAdditionalInformation(
            PaymentField::MODIFIED_AT,
            $modifiedAt
        );

        if ($this->configHelper->extraLogsEnabled()) {
            $this->logger->debug('Notification passed validation', $this->context);
        }

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
                $this->paymentConfirmed($paymentId);
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
        if ($this->configHelper->extraLogsEnabled()) {
            $this->logger->debug('Notification processed successfully', $this->context);
        }
    }

    /**
     * @param $paymentId
     */
    protected function addConfirmPaymentToOrderHistory($paymentId): void
    {
        $message = __(
            'Transaction confirmed, but order already paid. Transaction ID: '
        ) . $paymentId;
        $this->order
            ->setState($this->order->getState())
            ->addStatusToHistory($this->order->getStatus(), $message);
        $this->orderRepository->save($this->order);
    }

    /**
     * @throws \Paynow\PaymentGateway\Model\Exception\NotificationRetryProcessing
     * @throws \Paynow\PaymentGateway\Model\Exception\NotificationStopProcessing
     */
    private function retryProcessingNTimes($message, $counter = 3)
    {
        $paymentAdditionalInformation = $this->order->getPayment()->getAdditionalInformation();
        $history = $paymentAdditionalInformation[PaymentField::NOTIFICATION_HISTORY] ?? [];

        $historyKey = sprintf(
            '%s:%s',
            $this->context[PaymentField::PAYMENT_ID_FIELD_NAME],
            $this->context[PaymentField::STATUS_FIELD_NAME]
        );

        if (!isset($history[$historyKey])) {
            $history[$historyKey] = 0;
        }
        $history[$historyKey]++;

        $this->order->getPayment()->setAdditionalInformation(
            PaymentField::NOTIFICATION_HISTORY,
            $history
        );
        $this->orderRepository->save($this->order);

        $this->context['statusCounter'] = $history[$historyKey];

        if ($history[$historyKey] >= $counter) {
            throw new NotificationStopProcessing($message, $this->context);
        } else {
            throw new NotificationRetryProcessing($message, $this->context);
        }
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
        $message = __(
            'Awaiting payment confirmation from Paynow. Transaction ID: '
        ) . $this->order->getPayment()->getAdditionalInformation(
            PaymentField::PAYMENT_ID_FIELD_NAME
        );
        if ($this->configHelper->isOrderStatusChangeActive()) {
            $this->order
                ->setState(Order::STATE_PENDING_PAYMENT)
                ->addStatusToHistory(Order::STATE_PENDING_PAYMENT, $message);
        } else {
            $this->order->addCommentToStatusHistory($message);
        }
        $this->order->getPayment()->setIsClosed(false);
    }

    /**
     * @return void
     */
    private function paymentAbandoned()
    {
        $message = __('Payment has been abandoned. Transaction ID: ') . $this->order->getPayment()
                ->getAdditionalInformation(PaymentField::PAYMENT_ID_FIELD_NAME);
        if ($this->configHelper->isOrderStatusChangeActive()) {
            $this->order
                ->setState(Order::STATE_PENDING_PAYMENT)
                ->addStatusToHistory(Order::STATE_PENDING_PAYMENT, $message);
        } else {
            $this->order->addCommentToStatusHistory($message);
        }
        $this->order->getPayment()->setIsClosed(true);
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    private function paymentConfirmed($paymentId = null)
    {
        if ($this->order->getPayment()->canCapture()) {
            $this->order->getPayment()->capture();
            $this->logger->info('Payment has been captured', $this->context);
        } elseif(!is_null($paymentId) && $this->order->getState() != Order::STATE_PROCESSING) {
            $this->paymentNew($paymentId);
            $this->paymentConfirmed();
            $this->logger->info('Payment has been captured, but with full order reprocessing.', $this->context);
        } else {
            $this->logger->warning('Payment has not been captured', $this->context);
        }
    }

    /**
     * @return void
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     */
    private function paymentRejected()
    {
        $message = __(
            'Payment has not been authorized by the buyer. Transaction ID: '
        ) . (string)$this->order->getPayment()->getAdditionalInformation(
            PaymentField::PAYMENT_ID_FIELD_NAME
        );
        if ($this->configHelper->isOrderStatusChangeActive()) {
            $this->order
                ->setState(Order::STATE_PAYMENT_REVIEW)
                ->addStatusToHistory(Order::STATE_PAYMENT_REVIEW, $message);
            $this->logger->info('Order has been canceled', $this->context);
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
        $message = __(
            'Payment has been ended with an error. Transaction ID: '
        ) . $this->order->getPayment()->getAdditionalInformation(
            PaymentField::PAYMENT_ID_FIELD_NAME
        );
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
        $message = __('Payment has been expired. Transaction ID: ') . $this->order->getPayment()
                ->getAdditionalInformation(PaymentField::PAYMENT_ID_FIELD_NAME);
        $this->order->addCommentToStatusHistory($message);
        $this->order->getPayment()->deny();
    }

    /**
     * @param string $previousStatus
     * @param string $nextStatus
     * @return bool
     */
    private function isCorrectStatus(
        string $previousStatus,
        string $nextStatus
    ): bool {
        $paymentStatusFlow = [

            Status::STATUS_NEW       => [
                Status::STATUS_PENDING,
                Status::STATUS_ERROR,
                Status::STATUS_EXPIRED,
                Status::STATUS_CONFIRMED,
                Status::STATUS_REJECTED
            ],
            Status::STATUS_PENDING   => [
                Status::STATUS_CONFIRMED,
                Status::STATUS_REJECTED,
                Status::STATUS_EXPIRED,
                Status::STATUS_ABANDONED
            ],
            Status::STATUS_REJECTED  => [
                Status::STATUS_ABANDONED,
                Status::STATUS_CONFIRMED
            ],
            Status::STATUS_CONFIRMED => [],
            Status::STATUS_ERROR     => [
                Status::STATUS_CONFIRMED,
                Status::STATUS_REJECTED,
                Status::STATUS_ABANDONED,
                Status::STATUS_NEW
            ],
            Status::STATUS_EXPIRED   => [],
            Status::STATUS_ABANDONED => [],
        ];

        $previousStatusExists = isset($paymentStatusFlow[$previousStatus]);
        $isChangePossible     = in_array($nextStatus, $paymentStatusFlow[$previousStatus]);
        if (!$previousStatusExists && $nextStatus == Status::STATUS_NEW) {
            return true;
        }
        return $previousStatusExists && $isChangePossible;
    }
}
