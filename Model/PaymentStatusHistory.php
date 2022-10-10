<?php

declare(strict_types=1);

namespace Paynow\PaymentGateway\Model;

use Magento\Framework\Model\AbstractModel;
use Paynow\PaymentGateway\Api\Data\PaymentStatusHistoryInterface;

/**
 * Class PaymentStatusHistory
 *
 * @package Paynow\PaymentGateway\Model
 */
class PaymentStatusHistory extends AbstractModel implements PaymentStatusHistoryInterface
{

    /**
     * @inheritDoc
     */
    public function _construct()
    {
        $this->_init(\Paynow\PaymentGateway\Model\ResourceModel\PaymentStatusHistory::class);
    }

    /**
     * @inheritDoc
     */
    public function getId()
    {
        return $this->getData(self::ENTITY_ID);
    }

    /**
     * @inheritDoc
     */
    public function setId($id)
    {
        return $this->setData(self::ENTITY_ID, $id);
    }

    /**
     * @inheritDoc
     */
    public function getPaymentId()
    {
        return $this->getData(self::PAYMENT_ID);
    }

    /**
     * @inheritDoc
     */
    public function setPaymentId($paymentId)
    {
        return $this->setData(self::PAYMENT_ID, $paymentId);
    }

    /**
     * @inheritDoc
     */
    public function getExternalId()
    {
        return $this->getData(self::EXTERNAL_ID);
    }

    /**
     * @inheritDoc
     */
    public function setExternalId($externalId)
    {
        return $this->setData(self::EXTERNAL_ID, $externalId);
    }

    /**
     * @inheritDoc
     */
    public function getStatus()
    {
        return $this->getData(self::STATUS);
    }

    /**
     * @inheritDoc
     */
    public function setStatus($status)
    {
        return $this->setData(self::STATUS, $status);
    }

    /**
     * @inheritDoc
     */
    public function getCreatedAt()
    {
        return $this->getData(self::CREATED_AT);
    }

    /**
     * @inheritDoc
     */
    public function setCreatedAt($createdAt)
    {
        return $this->setData(self::CREATED_AT, $createdAt);
    }

    /**
     * @inheritDoc
     */
    public function getOrderId()
    {
        return $this->getData(self::ORDER_ID);
    }

    /**
     * @inheritDoc
     */
    public function setOrderId($orderId)
    {
        return $this->setData(self::ORDER_ID, $orderId);
    }

    /**
     * @inheritDoc
     */
    public function getCounter()
    {
        return $this->getData(self::COUNTER);
    }

    /**
     * @inheritDoc
     */
    public function setCounter($counter)
    {
        return $this->setData(self::COUNTER, $counter);
    }
}
