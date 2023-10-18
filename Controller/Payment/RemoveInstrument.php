<?php

namespace Paynow\PaymentGateway\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Paynow\PaymentGateway\Helper\PaymentSavedInstrumentService;

/**
 * Class RemoveInstrument
 *
 * @package Paynow\PaymentGateway\Controller\Payment
 */
class RemoveInstrument extends Action
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var PaymentSavedInstrumentService
     */
    private $savedInstrumentService;

    /**
     * @param JsonFactory $resultJsonFactory
     * @param Context $context
     * @param PaymentSavedInstrumentService $savedInstrumentService
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        Context $context,
        PaymentSavedInstrumentService $savedInstrumentService
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->savedInstrumentService = $savedInstrumentService;
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();

        try {
            $token = $this->getRequest()->getParam('savedInstrumentToken');

            $this->savedInstrumentService->remove($token);

            return $resultJson->setData([
                'success' => true,
            ]);
        } catch (\Exception $e) {
            return $resultJson->setData([
                'success' => false,
                'error' => $e->getMessage(),
            ]);
        }
    }
}
