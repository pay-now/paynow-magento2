<?php

namespace Paynow\PaymentGateway\Controller\Payment;

use Magento\Framework\View\Result\PageFactory;

/**
 * Class Confirm
 *
 * @package Paynow\PaymentGateway\Controller\Payment
 */
class Confirm extends \Magento\Framework\App\Action\Action
{

    /**
     * @var PageFactory
     */
    protected $pageFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        PageFactory $pageFactory
    ) {

        $this->pageFactory = $pageFactory;

        return parent::__construct($context);
    }

    public function execute()
    {
        $resultPage = $this->pageFactory->create();
        $resultPage->addHandle('paynow_payment_confirm');
        return $resultPage;
    }
}