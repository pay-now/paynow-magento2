<?php

declare(strict_types=1);

namespace Paynow\PaymentGateway\Api\Data;

interface PaymentStatusHistorySearchResultsInterface extends \Magento\Framework\Api\SearchResultsInterface
{

    /**
     * Get PaymentStatusHistory list.
     *
     * @return \Paynow\PaymentGateway\Api\Data\PaymentStatusHistoryInterface[]
     */
    public function getItems();

    /**
     * Set payment_id list.
     *
     * @param \Paynow\PaymentGateway\Api\Data\PaymentStatusHistoryInterface[] $items
     * @return $this
     */
    public function setItems(array $items);
}

