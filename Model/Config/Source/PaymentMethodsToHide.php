<?php

namespace Paynow\PaymentGateway\Model\Config\Source;

use Magento\Framework\Data\OptionSourceInterface;
use Paynow\Model\PaymentMethods\Type;


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

    public const PAYMENT_TYPE_TO_CONFIG_MAP = [
        Type::BLIK => self::BLIK,
        Type::PBL => self::PBL,
        Type::CARD => self::CARD,
        Type::GOOGLE_PAY => self::DIGITAL_WALLET,
        Type::APPLE_PAY => self::DIGITAL_WALLET
    ];

    public function toOptionArray()
    {
        return [
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
        ];
    }
}