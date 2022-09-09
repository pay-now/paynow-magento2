<?php

declare(strict_types=1);

namespace Paynow\PaymentGateway\Model;

use Magento\Framework\Api\SearchCriteria\CollectionProcessorInterface;
use Magento\Framework\Exception\CouldNotDeleteException;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Framework\Exception\NoSuchEntityException;
use Paynow\PaymentGateway\Api\Data\PaymentStatusHistoryInterface;
use Paynow\PaymentGateway\Api\Data\PaymentStatusHistoryInterfaceFactory;
use Paynow\PaymentGateway\Api\Data\PaymentStatusHistorySearchResultsInterfaceFactory;
use Paynow\PaymentGateway\Api\PaymentStatusHistoryRepositoryInterface;
use Paynow\PaymentGateway\Model\ResourceModel\PaymentStatusHistory as ResourcePaymentStatusHistory;
use Paynow\PaymentGateway\Model\ResourceModel\PaymentStatusHistory\CollectionFactory as PaymentStatusHistoryCollectionFactory;

class PaymentStatusHistoryRepository implements PaymentStatusHistoryRepositoryInterface
{

    /**
     * @var PaymentStatusHistoryCollectionFactory
     */
    protected $PaymentStatusHistoryCollectionFactory;

    /**
     * @var PaymentStatusHistoryInterfaceFactory
     */
    protected $PaymentStatusHistoryFactory;

    /**
     * @var PaymentStatusHistory
     */
    protected $searchResultsFactory;

    /**
     * @var CollectionProcessorInterface
     */
    protected $collectionProcessor;

    /**
     * @var ResourcePaymentStatusHistory
     */
    protected $resource;

    /**
     * @param ResourcePaymentStatusHistory                      $resource
     * @param PaymentStatusHistoryInterfaceFactory              $PaymentStatusHistoryFactory
     * @param PaymentStatusHistoryCollectionFactory             $PaymentStatusHistoryCollectionFactory
     * @param PaymentStatusHistorySearchResultsInterfaceFactory $searchResultsFactory
     * @param CollectionProcessorInterface                            $collectionProcessor
     */
    public function __construct(
        ResourcePaymentStatusHistory                      $resource,
        PaymentStatusHistoryInterfaceFactory              $PaymentStatusHistoryFactory,
        PaymentStatusHistoryCollectionFactory             $PaymentStatusHistoryCollectionFactory,
        PaymentStatusHistorySearchResultsInterfaceFactory $searchResultsFactory,
        CollectionProcessorInterface                            $collectionProcessor
    ) {
        $this->resource                                    = $resource;
        $this->PaymentStatusHistoryFactory           = $PaymentStatusHistoryFactory;
        $this->PaymentStatusHistoryCollectionFactory = $PaymentStatusHistoryCollectionFactory;
        $this->searchResultsFactory                        = $searchResultsFactory;
        $this->collectionProcessor                         = $collectionProcessor;
    }

    /**
     * @inheritDoc
     */
    public function save(
        PaymentStatusHistoryInterface $PaymentStatusHistory
    ) {
        try {
            $this->resource->save($PaymentStatusHistory);
        } catch (\Exception $exception) {
            throw new CouldNotSaveException(
                __(
                    'Could not save the PaymentStatusHistory: %1',
                    $exception->getMessage()
                )
            );
        }
        return $PaymentStatusHistory;
    }

    /**
     * @inheritDoc
     */
    public function get($id)
    {
        $PaymentStatusHistory = $this->PaymentStatusHistoryFactory->create();
        $this->resource->load($PaymentStatusHistory, $id);
        if (!$PaymentStatusHistory->getId()) {
            throw new NoSuchEntityException(
                __('PaymentStatusHistory with id "%1" does not exist.', $id)
            );
        }
        return $PaymentStatusHistory;
    }

    /**
     * @inheritDoc
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $criteria
    ) {
        $collection = $this->PaymentStatusHistoryCollectionFactory->create();

        $this->collectionProcessor->process($criteria, $collection);

        $searchResults = $this->searchResultsFactory->create();
        $searchResults->setSearchCriteria($criteria);

        $items = [];
        foreach ($collection as $model) {
            $items[] = $model;
        }

        $searchResults->setItems($items);
        $searchResults->setTotalCount($collection->getSize());
        return $searchResults;
    }

    /**
     * @inheritDoc
     */
    public function delete(
        PaymentStatusHistoryInterface $PaymentStatusHistory
    ) {
        try {
            $PaymentStatusHistoryModel = $this->PaymentStatusHistoryFactory->create();
            $this->resource->load(
                $PaymentStatusHistoryModel,
                $PaymentStatusHistory->getId()
            );
            $this->resource->delete($PaymentStatusHistoryModel);
        } catch (\Exception $exception) {
            throw new CouldNotDeleteException(
                __(
                    'Could not delete the PaymentStatusHistory: %1',
                    $exception->getMessage()
                )
            );
        }
        return true;
    }

    /**
     * @inheritDoc
     */
    public function getLastByExternalIdAndPaymentId($externalId, $paymentId)
    {
        $paymentHistoryCollection = $this->PaymentStatusHistoryCollectionFactory->create(
            $paymentId,
            $externalId,
            true
        );
        return $paymentHistoryCollection->getFirstItem();
    }

    /**
     * @inheritDoc
     */
    public function deleteById($id)
    {
        return $this->delete($this->get($id));
    }
}

