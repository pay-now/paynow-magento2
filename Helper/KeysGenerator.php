<?php

namespace Paynow\PaymentGateway\Helper;

/**
 * Class KeysGenerator
 *
 * @package Paynow\PaymentGateway\Helper
 */
class KeysGenerator
{
    /**
     * @param $externalId
     * @return string
     */
    public static function generateIdempotencyKey($externalId): string
    {
        return uniqid(substr($externalId, 0, 22), true);
    }

    public static function generateExternalIdFromQuoteId($quoteId): string
    {
        return uniqid($quoteId . '_');
    }
}
