<?php

namespace Paynow\PaymentGateway\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;

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
                    'isActive'       => $this->paymentHelper->isActive(),
                    'logoPath'       => 'https://static.paynow.pl/brand/paynow_logo_black.png',
                    'redirectUrl'    => $this->getRedirectUrl(),
                    'paymentMethods' => $this->paymentMethodsHelper->getAvailable()
                ]
            ]
        ];
    }
}
