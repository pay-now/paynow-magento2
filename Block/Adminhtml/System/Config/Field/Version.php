<?php

namespace Paynow\PaymentGateway\Block\Adminhtml\System\Config\Field;

use Magento\Backend\Block\Template\Context;
use Magento\Config\Block\System\Config\Form\Field;
use Magento\Framework\Data\Form\Element\AbstractElement;
use Paynow\PaymentGateway\Helper\PaymentHelper;

class Version extends Field
{
    private PaymentHelper $paymentHelper;

    public function __construct(PaymentHelper $helper, Context $context, array $data = [])
    {
        parent::__construct($context, $data);
        $this->paymentHelper = $helper;
    }

    /**
     * Retrieve version of the module
     *
     * @param AbstractElement $element
     * @return string
     */
    protected function _getElementHtml(AbstractElement $element)
    {
        return $this->paymentHelper->getModuleVersion();
    }
}
