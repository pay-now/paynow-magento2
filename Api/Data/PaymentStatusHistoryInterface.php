<?php

declare(strict_types=1);

namespace Paynow\PaymentGateway\Api\Data;

interface PaymentStatusHistoryInterface
{

    const ORDER_ID    = 'order_id';
    const STATUS      = 'status';
    const ENTITY_ID   = 'entity_id';
    const EXTERNAL_ID = 'external_id';
    const CREATED_AT  = 'created_at';
    const PAYMENT_ID  = 'payment_id';

    /**
     * Get entity_id
     *
     * @return string|null
     */
    public function getId();

    /**
     * Set entity_id
     *
     * @param string $id
     * @return \Paynow\PaymentGateway\PaymentStatusHistory\Api\Data\PaymentStatusHistoryInterface
     */
    public function setId($id);

    /**
     * Get payment_id
     *
     * @return string|null
     */
    public function getPaymentId();

    /**
     * Set payment_id
     *
     * @param string $paymentId
     * @return \Paynow\PaymentGateway\PaymentStatusHistory\Api\Data\PaymentStatusHistoryInterface
     */
    public function setPaymentId($paymentId);

    /**
     * Get external_id
     *
     * @return string|null
     */
    public function getExternalId();

    /**
     * Set external_id
     *
     * @param string $externalId
     * @return \Paynow\PaymentGateway\PaymentStatusHistory\Api\Data\PaymentStatusHistoryInterface
     */
    public function setExternalId($externalId);

    /**
     * Get status
     *
     * @return string|null
     */
    public function getStatus();

    /**
     * Set status
     *
     * @param string $status
     * @return \Paynow\PaymentGateway\PaymentStatusHistory\Api\Data\PaymentStatusHistoryInterface
     */
    public function setStatus($status);

    /**
     * Get created_at
     *
     * @return string|null
     */
    public function getCreatedAt();

    /**
     * Set created_at
     *
     * @param string $createdAt
     * @return \Paynow\PaymentGateway\PaymentStatusHistory\Api\Data\PaymentStatusHistoryInterface
     */
    public function setCreatedAt($createdAt);

    /**
     * Get order_id
     *
     * @return string|null
     */
    public function getOrderId();

    /**
     * Set order_id
     *
     * @param string $orderId
     * @return \Paynow\PaymentGateway\PaymentStatusHistory\Api\Data\PaymentStatusHistoryInterface
     */
    public function setOrderId($orderId);
}

