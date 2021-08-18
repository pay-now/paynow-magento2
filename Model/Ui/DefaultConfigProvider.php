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
class DefaultConfigProvider extends ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'paynow_gateway';

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
                    'isActive'     => $this->paymentHelper->isActive(),
                    'logoPath'    => 'https://static.paynow.pl/brand/paynow_logo_black.png',
                    'redirectUrl' => $this->getRedirectUrl()
                ]
            ]
        ];
    }
}
