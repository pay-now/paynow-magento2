<?php

namespace Paynow\PaymentGateway\Gateway\Http\Client;

use Paynow\Exception\PaynowException;

/**
 * Rozpoznaje, czy PaynowException oznacza timeout sieciowy, po którym nie wiadomo,
 * czy żądanie authorize dotarło do Paynow (a więc płatność mogła zostać mimo to utworzona).
 *
 * Kod i treść wyjątku zależą od tego, jaki klient HTTP jest aktualnie używany przez SDK
 * (m.in. php-http/curl-client zawsze zwraca code=0 i komunikat w formacie
 * "Idle timeout reached for ...", inny niż komunikaty Guzzle "cURL error 28: ..."),
 * dlatego rozpoznawanie nie może polegać wyłącznie na jednym konkretnym formacie.
 *
 * @package Paynow\PaymentGateway\Gateway\Http\Client
 */
class PaymentTimeoutClassifier
{
    private const RECOVERABLE_MESSAGE_PATTERN =
        '/cURL error 28|idle timeout reached|operation timed out|connection timed out/i';

    /**
     * @param PaynowException $exception
     * @return bool
     */
    public function isRecoverable(PaynowException $exception): bool
    {
        if (!empty($exception->getErrors())) {
            // Paynow zwrócił ustrukturyzowaną odpowiedź błędu - to nie jest brak odpowiedzi.
            return false;
        }

        if ($exception->getCode() == 504) {
            return true;
        }

        return (bool)preg_match(self::RECOVERABLE_MESSAGE_PATTERN, $exception->getMessage());
    }
}
