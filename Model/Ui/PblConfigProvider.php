<?php

namespace Paynow\PaymentGateway\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Paynow\Model\PaymentMethods\Type;
use Paynow\PaymentGateway\Model\Config\Source\PaymentMethodsToHide;

class PblConfigProvider extends ConfigProvider implements ConfigProviderInterface
{

    const CODE = 'paynow_pbl_gateway';

    /**
     * @return \array[][]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getConfig(): array
    {
        $grandTotal = $this->checkoutSession->getQuote()->getGrandTotal();
        $currencyCode = $this->checkoutSession->getQuote()->getCurrency()->getQuoteCurrencyCode();

        $isActive = $this->configHelper->isActive() &&
            $this->configHelper->isConfigured() &&
            $this->configHelper->isPaymentMethodsActive()
            && !in_array(PaymentMethodsToHide::PAYMENT_TYPE_TO_CONFIG_MAP[Type::PBL], $this->configHelper->getPaymentMethodsToHide());;

        $paymentMethods = [];
        if ($isActive) {
            $paymentMethods = $this->paymentMethodsHelper->getPblPaymentMethods($currencyCode, $grandTotal);
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