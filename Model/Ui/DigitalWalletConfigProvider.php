<?php

namespace Paynow\PaymentGateway\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Paynow\PaymentGateway\Model\Config\Source\PaymentMethodsToHide;

/**
 * Class DigitalWalletConfigProvider
 *
 * @package Paynow\PaymentGateway\Model\Ui
 */
class DigitalWalletConfigProvider extends ConfigProvider implements ConfigProviderInterface
{

    const CODE = 'paynow_digital_wallet_gateway';

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
            $this->configHelper->isPaymentMethodsActive();
        $paymentMethods = $this->paymentMethodsHelper->getDigitalWalletsPaymentMethods($currencyCode, $grandTotal);
		$isActive = $isActive && !empty($paymentMethods);
        foreach ($paymentMethods as $paymentMethod) {
            if (in_array(PaymentMethodsToHide::PAYMENT_TYPE_TO_CONFIG_MAP[$paymentMethod['type'] ?? ''] ?? '', $this->configHelper->getPaymentMethodsToHide())) {
                $isActive = false;
                break;
            }
        }
        $GDPRNotices = $this->GDPRHelper->getNotices();

        return [
            'payment' => [
                self::CODE => [
                    'isActive' => $isActive,
                    'logoPath' => count($paymentMethods) === 1 ? $paymentMethods[0]['image'] : $this->getImageUrl('digital-wallets.svg'),
                    'redirectUrl' => $this->getRedirectUrl(),
                    'paymentMethods' => $paymentMethods,
                    'GDPRNotices' => $GDPRNotices,
                ]
            ]
        ];
    }
}
