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
    )
    {
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
        $transaction = $this->transactionFactory->create();
        $transaction = $this->transactionResourceModel->loadObjectByTxnId(
            $transaction,
            $orderId,
            $paymentId,
            $oldTransactionId
        );
        if (!$transaction->getId()) {
            return;
        }

        $transaction->setTxnId($newTransactionId);
        $transaction->setTxnType(TransactionInterface::TYPE_AUTH);
        $transaction->setIsClosed(false);
        $this->transactionRepository->save($transaction);
    }

    public function closeTransactionId($orderId, $paymentId, $oldTransactionId)
    {
        $transaction = $this->transactionFactory->create();
        $transaction = $this->transactionResourceModel->loadObjectByTxnId(
            $transaction,
            $orderId,
            $paymentId,
            $oldTransactionId
        );
        if (!$transaction->getId()) {
            return;
        }
        $transaction->setIsClosed(false);
        $this->transactionRepository->save($transaction);
    }
}
