<?php

namespace Paynow\PaymentGateway\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Paynow\Model\PaymentMethods\Type;
use Paynow\PaymentGateway\Helper\PaymentMethodsHelper;

/**
 * Class PaymentMethodsToHide
 *
 * @package Paynow\PaymentGateway\Model\Config\Source
 */
class PaymentMethodsToHide implements OptionSourceInterface
{

    public const BLIK = 'paynow_blik';
    public const PBL = 'paynow_pbl';
    public const CARD = 'paynow_card';
    public const DIGITAL_WALLET = 'paynow_digital_wallet';
    public const PAYPO = 'paynow_paypo';
    public const CLICK_TO_PAY = 'paynow_click_to_pay';

    public const PAYMENT_TYPE_TO_CONFIG_MAP = [
        Type::BLIK => self::BLIK,
        Type::PBL => self::PBL,
        Type::CARD => self::CARD,
        PaymentMethodsHelper::CLICK_TO_PAY => self::DIGITAL_WALLET,
        Type::GOOGLE_PAY => self::DIGITAL_WALLET,
        Type::APPLE_PAY => self::DIGITAL_WALLET,
        Type::PAYPO => self::PAYPO,
    ];

    public function toOptionArray()
    {
        return [
            [
                'value' => 'none',
                'label' => __("None")
            ],
            [
                'value' => self::BLIK,
                'label' => __("BLIK")
            ],
            [
                'value' => self::PBL,
                'label' => __("Online transfers")
            ],
            [
                'value' => self::CARD,
                'label' => __("Card payment")
            ],
            [
                'value' => self::DIGITAL_WALLET,
                'label' => __("Digital wallets")
            ],
            [
                'value' => self::PAYPO,
                'label' => __("PayPo")
            ],
            [
                'value' => self::CLICK_TO_PAY,
                'label' => __("Click to Pay")
            ],
        ];
    }
}