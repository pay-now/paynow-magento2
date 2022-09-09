<?php

declare(strict_types=1);

namespace Paynow\PaymentGateway\Model\ResourceModel\PaymentStatusHistory;

use Magento\Framework\Model\ResourceModel\Db\Collection\AbstractCollection;

class Collection extends AbstractCollection
{

    /**
     * @inheritDoc
     */
    protected $_idFieldName = 'entity_id';

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init(
            \Paynow\PaymentGateway\Model\PaymentStatusHistory::class,
            \Paynow\PaymentGateway\Model\ResourceModel\PaymentStatusHistory::class
        );
    }
}

