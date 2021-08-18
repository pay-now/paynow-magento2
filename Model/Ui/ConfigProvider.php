<?php

namespace Paynow\PaymentGateway\Model\Ui;

use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Paynow\PaymentGateway\Helper\PaymentHelper;

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

    public function __construct(
        UrlInterface $urlBuilder,
        RequestInterface $request,
        PaymentHelper $paymentHelper
    ) {
        $this->urlBuilder    = $urlBuilder;
        $this->request       = $request;
        $this->paymentHelper = $paymentHelper;
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