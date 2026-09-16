<?php

namespace Paynow\PaymentGateway\Test\Unit\Gateway\Http\Client;

use Paynow\Exception\PaynowException;
use Paynow\PaymentGateway\Gateway\Http\Client\PaymentTimeoutClassifier;
use PHPUnit\Framework\TestCase;

/**
 * Class PaymentTimeoutClassifierTest
 *
 * @package Paynow\PaymentGateway\Test\Unit\Gateway\Http\Client
 */
class PaymentTimeoutClassifierTest extends TestCase
{
    /**
     * @var PaymentTimeoutClassifier
     */
    private $classifier;

    protected function setUp(): void
    {
        $this->classifier = new PaymentTimeoutClassifier();
    }

    /**
     * @dataProvider dataProviderRecoverableTimeouts
     */
    public function testIsRecoverableReturnsTrueForNetworkTimeouts(PaynowException $exception): void
    {
        self::assertTrue($this->classifier->isRecoverable($exception));
    }

    public function dataProviderRecoverableTimeouts(): array
    {
        return [
            'HTTP 504 gateway timeout' => [
                new PaynowException('Gateway Timeout', 504),
            ],
            'php-http/curl-client idle timeout (real production case, code 0)' => [
                new PaynowException(
                    'Idle timeout reached for "https://api.paynow.pl/v3/payments".',
                    0
                ),
            ],
            'Guzzle cURL error 28 timeout message' => [
                new PaynowException(
                    'cURL error 28: Operation timed out after 30000 milliseconds with 0 bytes received',
                    0
                ),
            ],
            'generic connection timed out message' => [
                new PaynowException('Connection timed out after 5000 milliseconds', 0),
            ],
        ];
    }

    /**
     * @dataProvider dataProviderNonRecoverableExceptions
     */
    public function testIsRecoverableReturnsFalseForNonTimeoutExceptions(PaynowException $exception): void
    {
        self::assertFalse($this->classifier->isRecoverable($exception));
    }

    public function dataProviderNonRecoverableExceptions(): array
    {
        return [
            'structured API error response, unrelated message' => [
                new PaynowException(
                    'Bad Request',
                    400,
                    json_encode([
                        'errors' => [
                            ['errorType' => 'INVALID_SIGNATURE', 'message' => 'Invalid signature'],
                        ],
                    ])
                ),
            ],
            'business error mentioning "time", not a network timeout' => [
                new PaynowException(
                    'Payment validity time exceeded',
                    400,
                    json_encode([
                        'errors' => [
                            ['errorType' => 'VALIDITY_TIME_EXCEEDED', 'message' => 'Payment validity time exceeded'],
                        ],
                    ])
                ),
            ],
            'unrelated network error' => [
                new PaynowException('Could not resolve host', 0),
            ],
        ];
    }
}
