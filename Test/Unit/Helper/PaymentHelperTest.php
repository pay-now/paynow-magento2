<?php

namespace Helper;

use Paynow\PaymentGateway\Helper\PaymentHelper;
use PHPUnit\Framework\TestCase;

class PaymentHelperTest extends TestCase
{

    public function testDoesNotTruncatesOrderItemsNameThatCanBeTrimmed()
    {
        $str = self::getStringOf(120) . '     ';

        $output = PaymentHelper::truncateOrderItemName($str);
        self::assertSame(120, strlen($output));
        self::assertSame(trim($str), $output);
    }

    /**
     * @testWith [117]
     *           [118]
     *           [119]
     *           [120]
     */
    public function testDoesNotTruncatesShortOrderItemsName($length)
    {
        $str = self::getStringOf($length);
        self::assertSame($str, PaymentHelper::truncateOrderItemName($str));
    }

    public function testDoesNotTruncatesShortOrderItemsNameNoMB()
    {
        $str = self::getStringOf(120, false);
        self::assertSame($str, PaymentHelper::truncateOrderItemName($str));
    }

    public function testTruncatesOrderItemsName()
    {
        $str = self::getStringOf(121);
        $output = PaymentHelper::truncateOrderItemName($str);

        self::assertSame(120, strlen($output));
        self::assertSame('...', substr($output, -3));
    }

    public function testTruncatesOrderItemsNameNoMB()
    {
        $str = self::getStringOf(200, false);
        $output = PaymentHelper::truncateOrderItemName($str);

        self::assertSame(120, strlen($output));
        self::assertSame('...', substr($output, -3));
    }

    private static function getStringOf(int $length, bool $extra = true): string
    {
        if ($extra) {
            $extra = ' ABC zaŻÓłcić 睡眠帮手-背景乐';
        } else {
            $extra = ' ABC';
        }

        return sprintf("%'X${length}s", $extra);
    }
}
