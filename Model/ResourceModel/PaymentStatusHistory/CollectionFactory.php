<?php

declare(strict_types=1);

namespace Paynow\PaymentGateway\Model\ResourceModel\PaymentStatusHistory;

class CollectionFactory implements CollectionFactoryInterface
{

    /**
     * Object Manager instance
     *
     * @var \Magento\Framework\ObjectManagerInterface
     */
    private $objectManager = null;

    /**
     * Instance name to create
     *
     * @var string
     */
    private $instanceName = null;

    /**
     * Factory constructor
     *
     * @param \Magento\Framework\ObjectManagerInterface $objectManager
     * @param string                                    $instanceName
     */
    public function __construct
    (
        \Magento\Framework\ObjectManagerInterface $objectManager,
        $instanceName = \Paynow\PaymentGateway\Model\ResourceModel\PaymentStatusHistory\Collection::class
    ) {
        $this->objectManager = $objectManager;
        $this->instanceName  = $instanceName;
    }

    /**
     * {@inheritdoc}
     */
    public function create(string $paymentId = null, string $externalId = null)
    {
        /** @var \Paynow\PaymentGateway\Model\ResourceModel\PaymentStatusHistory\Collection $collection */
        $collection = $this->objectManager->create($this->instanceName);

        if ($paymentId) {
            $collection->addFieldToFilter('payment_id', $paymentId);
        }

        if ($externalId) {
            $collection->addFieldToFilter('external_id', $externalId);
        }

        $collection->addFieldToSort('created_at', 'DESC');

        return $collection;
    }
}