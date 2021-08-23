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

        return [
            'payment' => [
                self::CODE => [
                    'isActive'        => $this->isActive($blikPaymentMethod),
                    'logoPath'        => $blikPaymentMethod->getImage() ?: null,
                    'redirectUrl'     => $this->getRedirectUrl(),
                    'paymentMethodId' => $blikPaymentMethod->getId()
                ]
            ]
        ];
    }

    /**
     * @param $blikPaymentMethod
     *
     * @return bool
     */
    private function isActive($blikPaymentMethod)
    {
        return $this->paymentHelper->isActive()
               && $this->paymentHelper->isBlikActive()
               && $blikPaymentMethod->isEnabled();
    }
}
