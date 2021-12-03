<?php

namespace Paynow\PaymentGateway\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;


/**
 * Class Status
 *
 * @package Paynow\PaymentGateway\Controller\Payment
 */
class Status extends Action
{
    private $resultJsonFactory;

    public function __construct(JsonFactory $resultJsonFactory, Context $context)
    {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        return $resultJson->setData([['title' => 'title', 'content' => 'content']]);
    }
}