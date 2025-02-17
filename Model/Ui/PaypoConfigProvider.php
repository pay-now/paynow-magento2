<?php

namespace Paynow\PaymentGateway\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Framework\Exception\NoSuchEntityException;
use Paynow\Model\PaymentMethods\Type;
use Paynow\PaymentGateway\Model\Config\Source\PaymentMethodsToHide;

class PaypoConfigProvider extends ConfigProvider implements ConfigProviderInterface
{

    const CODE = 'paynow_paypo_gateway';

    /**
     * @return \array[][]
     * @throws LocalizedException
     * @throws NoSuchEntityException
     */
    public function getConfig(): array
    {
        $grandTotal = $this->checkoutSession->getQuote()->getGrandTotal();
        $currencyCode = $this->checkoutSession->getQuote()->getCurrency()->getQuoteCurrencyCode();

        $paymentMethod = $this->paymentMethodsHelper->getPaypoPaymentMethod($currencyCode, $grandTotal);
        $isActive = $this->configHelper->isActive() &&
            $this->configHelper->isConfigured()
			&& $this->configHelper->isPaymentMethodsActive()
            && !in_array(PaymentMethodsToHide::PAYMENT_TYPE_TO_CONFIG_MAP[Type::PAYPO], $this->configHelper->getPaymentMethodsToHide())
            && $paymentMethod;

        $GDPRNotices = $this->GDPRHelper->getNotices();
        return [
            'payment' => [
                self::CODE => [
                    'isActive' => $isActive,
                    'logoPath' => $this->getImageUrl('paypo-logo.svg'),
                    'redirectUrl' => $this->getRedirectUrl(),
                    'paymentMethodId' => $paymentMethod ? $paymentMethod->getId() : null,
                    'GDPRNotices' => $GDPRNotices,
                ]
            ]
        ];
    }
}
