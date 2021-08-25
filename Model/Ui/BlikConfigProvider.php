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

    /**
     * Returns configuration
     *
     * @return array
     */
    public function getConfig()
    {
        $blikPaymentMethod = $this->paymentMethodsHelper->getBlikPaymentMethod();
        $isActive          = $this->helper->isActive() && $this->helper->isBlikActive()
                             && $blikPaymentMethod->isEnabled();

        return [
            'payment' => [
                self::CODE => [
                    'isActive'        => $isActive,
                    'logoPath'        => $blikPaymentMethod->getImage() ?: null,
                    'redirectUrl'     => $this->getRedirectUrl(),
                    'paymentMethodId' => $blikPaymentMethod->getId()
                ]
            ]
        ];
    }
}
