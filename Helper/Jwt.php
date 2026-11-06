<?php

namespace Paynow\PaymentGateway\Helper;

use InvalidArgumentException;
use stdClass;
use UnexpectedValueException;

/**
 * Class Jwt
 *
 * Minimal HS256 JWT, wire-compatible with tokens issued by firebase/php-jwt in previous module versions.
 *
 * @package Paynow\PaymentGateway\Helper
 */
class Jwt
{
    /**
     * @param array $payload
     * @param string $key
     * @return string
     */
    public static function encode(array $payload, string $key): string
    {
        $signingInput = self::b64(json_encode(['typ' => 'JWT', 'alg' => 'HS256'], JSON_UNESCAPED_SLASHES))
            . '.' . self::b64(json_encode($payload, JSON_UNESCAPED_SLASHES));

        return $signingInput . '.' . self::b64(self::sign($signingInput, $key));
    }

    /**
     * @param string $jwt
     * @param string $key
     * @return stdClass
     * @throws UnexpectedValueException
     */
    public static function decode(string $jwt, string $key): stdClass
    {
        $parts = explode('.', $jwt);
        if (count($parts) !== 3) {
            throw new UnexpectedValueException('Wrong number of segments');
        }
        list($header64, $payload64, $signature64) = $parts;

        $header = json_decode(self::b64Decode($header64));
        if (!$header instanceof stdClass || !isset($header->alg) || $header->alg !== 'HS256') {
            throw new UnexpectedValueException('Algorithm not supported');
        }
        if (!hash_equals(self::sign($header64 . '.' . $payload64, $key), self::b64Decode($signature64))) {
            throw new UnexpectedValueException('Signature verification failed');
        }

        $payload = json_decode(self::b64Decode($payload64), false, 512, JSON_BIGINT_AS_STRING);
        if (!$payload instanceof stdClass) {
            throw new UnexpectedValueException('Payload must be a JSON object');
        }

        return $payload;
    }

    private static function sign(string $input, string $key): string
    {
        // empty key = signature anyone can forge
        if ($key === '') {
            throw new InvalidArgumentException('Key may not be empty');
        }

        return hash_hmac('sha256', $input, $key, true);
    }

    private static function b64(string $input): string
    {
        return rtrim(strtr(base64_encode($input), '+/', '-_'), '=');
    }

    private static function b64Decode(string $input): string
    {
        return (string)base64_decode(strtr($input, '-_', '+/'));
    }
}
