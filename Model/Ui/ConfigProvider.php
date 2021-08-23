<?php

namespace Paynow\PaymentGateway\Model\Ui;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Paynow\PaymentGateway\Helper\PaymentHelper;
use Paynow\PaymentGateway\Helper\PaymentMethodsHelper;

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
    protected $paymentHelper;

    /**
     * @var PaymentMethodsHelper
     */
    protected $paymentMethodsHelper;

    public function __construct(
        UrlInterface $urlBuilder,
        RequestInterface $request,
        PaymentHelper $paymentHelper,
        PaymentMethodsHelper $paymentMethodsHelper,
    ) {
        $this->urlBuilder           = $urlBuilder;
        $this->request              = $request;
        $this->paymentHelper        = $paymentHelper;
        $this->paymentMethodsHelper = $paymentMethodsHelper;
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
    protected function getRequest()
    {
        return $this->request;
    }
}