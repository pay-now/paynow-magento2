<?php

declare(strict_types=1);

namespace Paynow\PaymentGateway\Model\ResourceModel;

use Magento\Framework\Model\ResourceModel\Db\AbstractDb;

class PaymentStatusHistory extends AbstractDb
{

    /**
     * @inheritDoc
     */
    protected function _construct()
    {
        $this->_init('paynow_paymentgateway_paymentstatushistory', 'entity_id');
    }
}

