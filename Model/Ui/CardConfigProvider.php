<?php

namespace Paynow\PaymentGateway\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Paynow\PaymentGateway\Model\Config\Source\PaymentMethodsToHide;

/**
 * Class CardConfigProvider
 *
 * @package Paynow\PaymentGateway\Model\Ui
 */
class CardConfigProvider extends ConfigProvider implements ConfigProviderInterface
{

    const CODE = 'paynow_card_gateway';

    /**
     * @return \array[][]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getConfig(): array
    {
        $grandTotal = $this->checkoutSession->getQuote()->getGrandTotal();
        $currencyCode = $this->checkoutSession->getQuote()->getCurrency()->getQuoteCurrencyCode();
        $cardPaymentMethod = $this->paymentMethodsHelper->getCardPaymentMethod($currencyCode, $grandTotal);
        $isActive = $this->configHelper->isActive()
            && $this->configHelper->isConfigured()
            && $cardPaymentMethod
            && $cardPaymentMethod->isEnabled()
            && !in_array(PaymentMethodsToHide::PAYMENT_TYPE_TO_CONFIG_MAP[$cardPaymentMethod->getType()], $this->configHelper->getPaymentMethodsToHide());
        $GDPRNotices = $this->GDPRHelper->getNotices();

        return [
            'payment' => [
                self::CODE => [
                    'isActive' => $isActive,
                    'logoPath' => $cardPaymentMethod ? $cardPaymentMethod->getImage() : null,
                    'redirectUrl' => $this->getRedirectUrl(),
                    'paymentMethodId' => $cardPaymentMethod ? $cardPaymentMethod->getId() : null,
                    'GDPRNotices' => $GDPRNotices
                ]
            ]
        ];
    }
}