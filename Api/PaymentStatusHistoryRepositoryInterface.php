<?php

declare(strict_types=1);

namespace Paynow\PaymentGateway\Api;

use Magento\Framework\Api\SearchCriteriaInterface;

interface PaymentStatusHistoryRepositoryInterface
{

    /**
     * Save PaymentStatusHistory
     *
     * @param \Paynow\PaymentGateway\Api\Data\PaymentStatusHistoryInterface $PaymentStatusHistory
     * @return \Paynow\PaymentGateway\Api\Data\PaymentStatusHistoryInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function save(
        \Paynow\PaymentGateway\Api\Data\PaymentStatusHistoryInterface $PaymentStatusHistory
    );

    /**
     * Retrieve PaymentStatusHistory
     *
     * @param string $id
     * @return \Paynow\PaymentGateway\Api\Data\PaymentStatusHistoryInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function get($id);

    /**
     * Retrieve PaymentStatusHistory matching the specified criteria.
     *
     * @param \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
     * @return \Paynow\PaymentGateway\Api\Data\PaymentStatusHistorySearchResultsInterface
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function getList(
        \Magento\Framework\Api\SearchCriteriaInterface $searchCriteria
    );

    /**
     * Delete PaymentStatusHistory
     *
     * @param \Paynow\PaymentGateway\Api\Data\PaymentStatusHistoryInterface $PaymentStatusHistory
     * @return bool true on success
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function delete(
        \Paynow\PaymentGateway\Api\Data\PaymentStatusHistoryInterface $PaymentStatusHistory
    );

    /**
     * Delete PaymentStatusHistory by ID
     *
     * @param string $id
     * @return bool true on success
     * @throws \Magento\Framework\Exception\NoSuchEntityException
     * @throws \Magento\Framework\Exception\LocalizedException
     */
    public function deleteById($id);

    /**
     *  Retrieve last PaymentStatusHistory by externalId and  paymentId
     *
     * @param $externalId
     * @param $paymentId
     * @return \Paynow\PaymentGateway\Api\Data\PaymentStatusHistoryInterface
     */
    public function getLastByExternalIdAndPaymentId($externalId, $paymentId);
}
