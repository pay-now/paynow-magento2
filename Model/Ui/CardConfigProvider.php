<?php

namespace Paynow\PaymentGateway\Model\Ui;

use Magento\Checkout\Model\ConfigProviderInterface;

/**
 * Class ConfigProvider
 *
 * @package Paynow\PaymentGateway\Model\Ui
 */
class CardConfigProvider extends ConfigProvider implements ConfigProviderInterface
{
    const CODE = 'paynow_card_gateway';

    /**
     * Returns configuration
     *
     * @return array
     */
    public function getConfig(): array
    {
        $grandTotal        = $this->checkoutSession->getQuote()->getGrandTotal();
        $currencyCode      = $this->checkoutSession->getQuote()->getCurrency()->getQuoteCurrencyCode();
        $cardPaymentMethod = $this->paymentMethodsHelper->getCardPaymentMethod($currencyCode, $grandTotal);
        $isActive          = $this->configHelper->isActive()
                             && $this->configHelper->isConfigured()
                             && $cardPaymentMethod
                             && $cardPaymentMethod->isEnabled();
        $GDPRNotices = $this->GDPRHelper->getNotices();
        $instruments = [];

        if ($cardPaymentMethod) {
            foreach ($cardPaymentMethod->getSavedInstruments() ?? [] as $savedInstrument) {
                $instruments[] = [
                    'token' => $savedInstrument->getToken(),
                    'isExpired' => $savedInstrument->isExpired(),
                    'image' => $savedInstrument->getImage(),
                    'brand' => $savedInstrument->getBrand(),
                    'name' => $savedInstrument->getName(),
                    'expirationDate' => $savedInstrument->getExpirationDate(),
                ];
            }
        }

        return [
            'payment' => [
                self::CODE => [
                    'isActive'        => $isActive,
                    'defaultCartImage' => $this->getImageUrl('card-default.svg'),
                    'logoPath'        => $cardPaymentMethod ? $cardPaymentMethod->getImage() : null,
                    'redirectUrl'     => $this->getRedirectUrl(),
                    'paymentMethodId' => $cardPaymentMethod ? $cardPaymentMethod->getId(): null,
                    'GDPRNotices'     => $GDPRNotices,
                    'instruments'     => $instruments,
                    'hasInstruments'  => !empty($instruments),
					'removeCardErrorMessage' => __('An error occurred while deleting the saved card.'),
                ]
            ]
        ];
    }
}
