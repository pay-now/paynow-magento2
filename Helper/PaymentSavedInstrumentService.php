<?php

namespace Paynow\PaymentGateway\Helper;

use Magento\Quote\Model\Quote;
use Magento\Customer\Model\Session;
use Paynow\PaymentGateway\Model\Logger\Logger;
use Paynow\Service\Payment;

/**
 * Class PaymentSavedInstrumentService
 *
 * @package Paynow\PaymentGateway\Helper
 */
class PaymentSavedInstrumentService
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    /**
     * @var Quote
     */
    private $quote;

    /**
     * @var Session
     */
    private $customerSession;

    public function __construct(
        Logger $logger,
        PaymentHelper $paymentHelper,
        Quote $quote,
        Session $customerSession
    ) {
        $this->logger = $logger;
        $this->paymentHelper = $paymentHelper;
        $this->quote = $quote;
        $this->customerSession = $customerSession;
    }

    /**
     * @param $token
     * @return void
     */
    public function remove($token)
    {
        try {
            $idempotencyKey = KeysGenerator::generateIdempotencyKey(KeysGenerator::generateExternalIdFromQuoteId($this->quote->getId()));
            $customerId = $this->customerSession->getCustomer()->getId();
            $buyerExternalId = $customerId ? $this->paymentHelper->generateBuyerExternalId($customerId) : null;
            (new Payment($this->paymentHelper->initializePaynowClient()))->removeSavedInstrument($buyerExternalId, $token, $idempotencyKey);
        } catch (\Exception $exception) {
            $this->logger->error(
				$exception->getMessage(),
				[
					'service' => 'Payment',
					'action' => 'removeSavedInstrument',
					'token' => $token,
					'file' => $exception->getFile(),
					'line' => $exception->getLine()
				]
			);
        }
    }
}
