<?php

namespace Paynow\PaymentGateway\Controller\Checkout;

use Magento\Framework\App\Action\Action;

/**
 * Class Confirm
 *
 * @package Paynow\PaymentGateway\Controller\Checkout
 */
class Confirm extends Action
{
    protected $pageFactory;

    public function __construct(
        \Magento\Framework\App\Action\Context $context,
        \Magento\Framework\View\Result\PageFactory $pageFactory
    ) {
        $this->pageFactory = $pageFactory;
        return parent::__construct($context);
    }

    public function execute()
    {
        return $this->pageFactory->create();
    }
}
