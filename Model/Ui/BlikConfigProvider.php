<?php

namespace Paynow\PaymentGateway\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Paynow\Model\PaymentMethods\AuthorizationType;

/**
 * Class BlikConfigProvider
 *
 * @package Paynow\PaymentGateway\Model\Ui
 */
class BlikConfigProvider extends ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'paynow_blik_gateway';

    /**
     * @return \array[][]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getConfig(): array
    {
        $grandTotal = $this->checkoutSession->getQuote()->getGrandTotal();
        $currencyCode = $this->checkoutSession->getQuote()->getCurrency()->getQuoteCurrencyCode();
        $blikPaymentMethod = $this->paymentMethodsHelper->getBlikPaymentMethod($currencyCode, $grandTotal);
        $isActive = $this->configHelper->isActive()
            && $this->configHelper->isConfigured()
            && $blikPaymentMethod
            && $blikPaymentMethod->isEnabled();
        $GDPRNotices = $this->GDPRHelper->getNotices();
        $isWhiteLabel = $blikPaymentMethod && $blikPaymentMethod->getAuthorizationType() === AuthorizationType::CODE;

        return [
            'payment' => [
                self::CODE => [
                    'isActive' => $isActive,
                    'logoPath' => $blikPaymentMethod ? $blikPaymentMethod->getImage() : null,
                    'redirectUrl' => $this->getRedirectUrl(),
                    'paymentMethodId' => $blikPaymentMethod ? $blikPaymentMethod->getId() : null,
                    'GDPRNotices' => $GDPRNotices,
                    'isWhiteLabel' => $isWhiteLabel,
                    'blikConfirmUrl' => $this->getConfirmBlikUrl()
                ]
            ]
        ];
    }
}
