<?php

namespace Paynow\PaymentGateway\Test\Unit\Helper;

use Paynow\PaymentGateway\Helper\Jwt;
use PHPUnit\Framework\TestCase;

/**
 * Class JwtTest
 *
 * @package Paynow\PaymentGateway\Test\Unit\Helper
 */
class JwtTest extends TestCase
{
    private const KEY = 'b305b996-bca5-4404-a0b7-2ccea3d2b64b';

    // issued by firebase/php-jwt 6.4.0 and 7.2.1 (identical) - links created by previous module versions
    private const FIREBASE_REFERENCE_TOKEN = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJyZWZlcmVuY2VJZCI6IjAwMDAwMDEyMyJ9.M7JnnAJn3723BQ0pLF9vSNTXyUDy02E_83J2AInEjPs';
    private const FIREBASE_ORDER_TOKEN = 'eyJ0eXAiOiJKV1QiLCJhbGciOiJIUzI1NiJ9.eyJvcmRlcklkIjoxMjN9.i60BX5PmEFkG93cYQCUhnTy19THtsUpJpc8UmZVbERg';

    public function testEncodesSameTokensAsFirebase()
    {
        self::assertSame(self::FIREBASE_REFERENCE_TOKEN, Jwt::encode(['referenceId' => '000000123'], self::KEY));
        self::assertSame(self::FIREBASE_ORDER_TOKEN, Jwt::encode(['orderId' => 123], self::KEY));
    }

    public function testDecodesFirebaseTokens()
    {
        self::assertSame('000000123', Jwt::decode(self::FIREBASE_REFERENCE_TOKEN, self::KEY)->referenceId);
        self::assertSame(123, Jwt::decode(self::FIREBASE_ORDER_TOKEN, self::KEY)->orderId);
    }

    /**
     * @dataProvider invalidTokens
     */
    public function testRejectsInvalidToken(string $token, string $key = self::KEY)
    {
        $this->expectException(\Exception::class);
        Jwt::decode($token, $key);
    }

    public static function invalidTokens(): array
    {
        list($header, , $signature) = explode('.', self::FIREBASE_ORDER_TOKEN);
        $b64 = function (string $input) {
            return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
        };
        $arrayPayload = $header . '.' . $b64('[1]');

        return [
            'tampered payload' => [$header . '.' . $b64('{"orderId":124}') . '.' . $signature],
            'wrong key' => [self::FIREBASE_ORDER_TOKEN, str_repeat('x', 36)],
            'empty key' => [self::FIREBASE_ORDER_TOKEN, ''],
            'alg none' => [$b64('{"typ":"JWT","alg":"none"}') . '.' . $b64('{"orderId":123}') . '.'],
            'alg HS512' => [$b64('{"typ":"JWT","alg":"HS512"}') . '.' . $b64('{"orderId":123}') . '.' . $signature],
            'two segments' => [$header . '.' . $b64('{"orderId":123}')],
            'garbage' => ['a.b.c'],
            'empty' => [''],
            'non-object payload' => [$arrayPayload . '.' . $b64(hash_hmac('sha256', $arrayPayload, self::KEY, true))],
        ];
    }

    public function testRejectsEmptyKeyOnEncode()
    {
        $this->expectException(\InvalidArgumentException::class);
        Jwt::encode(['orderId' => 123], '');
    }
}
