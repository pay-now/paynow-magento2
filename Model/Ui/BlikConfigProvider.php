<?php

namespace Paynow\PaymentGateway\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\App\RequestInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\View\Asset\Repository;
use Paynow\PaymentGateway\Helper\PaymentHelper;

/**
 * Class ConfigProvider
 *
 * @package Paynow\PaymentGateway\Model\Ui
 */
class BlikConfigProvider implements ConfigProviderInterface
{
    const CODE = 'paynow_blik_gateway';

    const LOGO_PATH = 'https://static.paynow.pl/payment-method-icons/2007.png';

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
        $this->urlBuilder = $urlBuilder;
        $this->request = $request;
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * Returns configuration
     *
     * @return array
     */
    public function getConfig()
    {
        return [
            'payment' => [
                self::CODE => [
                    'iActive' => $this->paymentHelper->isActive(),
                    'logoPath' => $this->getLogoPath(),
                    'redirectUrl' => $this->getRedirectUrl()
                ]
            ]
        ];
    }

    /**
     * Returns payment method logo path
     * @return string
     */
    protected function getLogoPath()
    {
        return self::LOGO_PATH;
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
