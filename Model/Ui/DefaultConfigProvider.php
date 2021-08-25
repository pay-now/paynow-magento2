<?php

namespace Paynow\PaymentGateway\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Exception\NoSuchEntityException;

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
     * @throws NoSuchEntityException
     */
    public function getConfig(): array
    {
        $methods = $this->configHelper->isPaymentMethodsActive() ? $this->paymentMethodsHelper->getAvailable() : [];

        return [
            'payment' => [
                self::CODE => [
                    'isActive'       => $this->configHelper->isActive(),
                    'logoPath'       => 'https://static.paynow.pl/brand/paynow_logo_black.png',
                    'redirectUrl'    => $this->getRedirectUrl(),
                    'paymentMethods' => $methods
                ]
            ]
        ];
    }
}
