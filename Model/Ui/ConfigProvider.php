<?php

namespace Paynow\PaymentGateway\Model\Ui;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Paynow\PaymentGateway\Helper\ConfigHelper;
use Paynow\PaymentGateway\Helper\PaymentHelper;
use Paynow\PaymentGateway\Helper\PaymentMethodsHelper;

/**
 * Class ConfigProvider
 *
 * @package Paynow\PaymentGateway\Model\Ui
 */
class ConfigProvider
{
    /**
     * @var UrlInterface
     */
    private $urlBuilder;

    /**
     * Request object
     *
     * @var RequestInterface
     */
    protected $request;

    /**
     * @var PaymentHelper
     */
    protected $helper;

    /**
     * @var ConfigHelper
     */
    protected $configHelper;

    /**
     * @var PaymentMethodsHelper
     */
    protected $paymentMethodsHelper;

    public function __construct(
        UrlInterface $urlBuilder,
        RequestInterface $request,
        PaymentHelper $paymentHelper,
        PaymentMethodsHelper $paymentMethodsHelper,
        ConfigHelper $configHelper
    ) {
        $this->urlBuilder           = $urlBuilder;
        $this->request              = $request;
        $this->helper               = $paymentHelper;
        $this->paymentMethodsHelper = $paymentMethodsHelper;
        $this->configHelper = $configHelper;
    }

    /**
     * Return url for checkout redirect
     * @return mixed
     */
    protected function getRedirectUrl()
    {
        return $this->urlBuilder->getUrl('paynow/checkout/redirect', ['_secure' => $this->getRequest()->isSecure()]);
    }

    /**
     * Retrieve request object
     * @return RequestInterface
     */
    protected function getRequest(): RequestInterface
    {
        return $this->request;
    }
}
