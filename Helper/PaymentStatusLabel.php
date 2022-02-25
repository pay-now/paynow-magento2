<?php

namespace Paynow\PaymentGateway\Helper;

/**
 * Class PaymentStatusLabel
 *
 * @package Paynow\PaymentGateway\Helper
 */
class PaymentStatusLabel
{
    public static $PENDING = 'processing';
    public static $EXPIRED = 'expired';
    public static $CONFIRMED = 'succeeded';
    public static $NEW = 'processing';
    public static $ERROR = 'failed';
    public static $REJECTED = 'rejected';
    public static $ABANDONED = 'abandoned';
}
