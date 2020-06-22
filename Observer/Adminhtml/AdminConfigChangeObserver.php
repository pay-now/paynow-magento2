<?php

namespace Paynow\PaymentGateway\Observer\Adminhtml;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Paynow\PaymentGateway\Helper\ConfigurationChangeProcessor;

/**
 * Class AdminConfigChangeObserver
 *
 * @package Paynow\PaymentGateway\Observer\Adminhtml
 */
class AdminConfigChangeObserver implements ObserverInterface
{
    /**
     * @var ConfigurationChangeProcessor
     */
    private $configurationChangeProcessor;

    public function __construct(RequestInterface $request, ConfigurationChangeProcessor $configurationChangeProcessor)
    {
        $this->configurationChangeProcessor = $configurationChangeProcessor;
    }

    public function execute(Observer $observer)
    {
        if ($observer->getStore()) {
            $this->configurationChangeProcessor->process((int)$observer->getStore());
        }

        return $this;
    }
}
