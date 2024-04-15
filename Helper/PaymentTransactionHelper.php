<?php

namespace Paynow\PaymentGateway\Helper;

use Magento\Sales\Api\Data\TransactionInterface;
use Magento\Sales\Api\TransactionRepositoryInterface;
use Magento\Sales\Model\Order\Payment\TransactionFactory;
use Magento\Sales\Model\ResourceModel\Order\Payment\Transaction;

/**
 * PaymentTransactionHelper class
 */
class PaymentTransactionHelper
{

    /**
     * @var TransactionRepositoryInterface
     */
    public $transactionRepository;

    /**
     * @var TransactionFactory
     */
    public $transactionFactory;

    /**
     * @var Transaction
     */
    public $transactionResourceModel;

    /**
     * @param TransactionRepositoryInterface $transactionRepository
     * @param TransactionFactory $transactionFactory
     * @param Transaction $transactionResourceModel
     */
    public function __construct(
        TransactionRepositoryInterface $transactionRepository,
        TransactionFactory             $transactionFactory,
        Transaction                    $transactionResourceModel
    ) {
        $this->transactionRepository = $transactionRepository;
        $this->transactionFactory = $transactionFactory;
        $this->transactionResourceModel = $transactionResourceModel;
    }

    /**
     * @param int $orderId
     * @param int $paymentId
     * @param string $oldTransactionId
     * @param string $newTransactionId
     * @return void
     */
    public function changeTransactionId($orderId, $paymentId, $oldTransactionId, $newTransactionId)
    {
        $newTransaction = $this->transactionResourceModel->loadObjectByTxnId(
            $this->transactionFactory->create(),
            $orderId,
            $paymentId,
            $newTransactionId
        );

        $currentTransaction = $this->transactionResourceModel->loadObjectByTxnId(
            $this->transactionFactory->create(),
            $orderId,
            $paymentId,
            $oldTransactionId
        );

        if ($newTransaction->getId()) {
            $newTransaction->setIsClosed(false);
            $this->transactionRepository->save($newTransaction);

            if ($currentTransaction->getId()) {
                $this->transactionRepository->delete($currentTransaction);
            }
            return;
        }

        if (!$currentTransaction->getId()) {
            return;
        }

        $currentTransaction->setTxnId($newTransactionId);
        $currentTransaction->setTxnType(TransactionInterface::TYPE_AUTH);
        $currentTransaction->setIsClosed(false);
        $this->transactionRepository->save($currentTransaction);
    }

    public function openTransactionId($orderId, $paymentId, $transactionId)
    {
        $transaction = $this->transactionFactory->create();
        $transaction = $this->transactionResourceModel->loadObjectByTxnId(
            $transaction,
            $orderId,
            $paymentId,
            $transactionId
        );
        if (!$transaction->getId()) {
            return;
        }
        $transaction->setIsClosed(false);
        $this->transactionRepository->save($transaction);
    }

    public function closeTransactionId($orderId, $paymentId, $transactionId)
    {
        $transaction = $this->transactionFactory->create();
        $transaction = $this->transactionResourceModel->loadObjectByTxnId(
            $transaction,
            $orderId,
            $paymentId,
            $transactionId
        );
        if (!$transaction->getId()) {
            return;
        }
        $transaction->setIsClosed(true);
        $this->transactionRepository->save($transaction);
    }
}
