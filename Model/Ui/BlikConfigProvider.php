<?php

namespace Paynow\PaymentGateway\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;

/**
 * Class ConfigProvider
 *
 * @package Paynow\PaymentGateway\Model\Ui
 */
class BlikConfigProvider extends ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'paynow_blik_gateway';

    const METHOD_ID = 2007;

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
                    'isActive'     => $this->paymentHelper->isActive() && $this->paymentHelper->isBlikActive(),
                    'logoPath'    => 'https://static.paynow.pl/payment-method-icons/' . self::METHOD_ID . '.png',
                    'redirectUrl' => $this->getRedirectUrl()
                ]
            ]
        ];
    }
}
