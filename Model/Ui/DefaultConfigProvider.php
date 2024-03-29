<?php

namespace Paynow\PaymentGateway\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Exception\LocalizedException;
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
     * @throws NoSuchEntityException|LocalizedException
     */
    public function getConfig(): array
    {
        $grandTotal = $this->checkoutSession->getQuote()->getGrandTotal();
        $currencyCode = $this->checkoutSession->getQuote()->getCurrency()->getQuoteCurrencyCode();

        $isActive = $this->configHelper->isActive() &&
            $this->configHelper->isConfigured() &&
            !$this->configHelper->isPaymentMethodsActive();

        $paymentMethods = [];
        if ($isActive) {
            $paymentMethods = $this->paymentMethodsHelper->getAvailable($currencyCode, $grandTotal);
        }
        $GDPRNotices = $this->GDPRHelper->getNotices();

        return [
            'payment' => [
                self::CODE => [
                    'isActive' => $isActive,
                    'logoPath' => 'https://static.paynow.pl/brand/paynow_logo_black.png',
                    'redirectUrl' => $this->getRedirectUrl(),
                    'paymentMethods' => $paymentMethods,
                    'GDPRNotices' => $GDPRNotices,
                ]
            ]
        ];
    }
}
