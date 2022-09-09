<?php

declare(strict_types=1);

namespace Paynow\PaymentGateway\Model\ResourceModel\PaymentStatusHistory;

interface CollectionFactoryInterface
{
    /**
     * Create class instance
     *
     * @return \Paynow\PaymentGateway\Model\ResourceModel\PaymentStatusHistory\Collection
     */
    public function create(
        string $paymentId = null,
        string $externalId = null
    );
}