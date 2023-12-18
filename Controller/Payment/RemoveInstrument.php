<?php

namespace Paynow\PaymentGateway\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Paynow\PaymentGateway\Helper\PaymentSavedInstrumentService;
use Paynow\PaymentGateway\Model\Logger\Logger;

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
	 * @var Logger
	 */
	private $logger;

    /**
     * @param JsonFactory $resultJsonFactory
     * @param Context $context
     * @param PaymentSavedInstrumentService $savedInstrumentService
	 * @param Logger $logger
     */
    public function __construct(
        JsonFactory $resultJsonFactory,
        Context $context,
        PaymentSavedInstrumentService $savedInstrumentService,
		Logger $logger
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->savedInstrumentService = $savedInstrumentService;
		$this->logger = $logger;
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
			$this->logger->error(
				'Error occurred removing saved instrument: ' . $e->getMessage(),
				[
					'file' => $e->getFile(),
					'line' => $e->getLine(),
				]
			);
            return $resultJson->setData([
                'success' => false,
                'error' => __('An error occurred while deleting the saved card.'),
            ]);
        }
    }
}
