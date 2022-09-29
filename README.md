[**English version**][ext0]

# Wtyczka Paynow dla Magento 2


[![Latest Version](https://img.shields.io/github/release/pay-now/paynow-magento2.svg)](https://github.com/pay-now/paynow-magento2/releases)
[![Software License](https://img.shields.io/badge/license-MIT-brightgreen.svg)](LICENSE)
[![Total Downloads](https://img.shields.io/packagist/dt/pay-now/paynow-magento2)](https://packagist.org/packages/pay-now/paynow-magento2)

Wtyczka Paynow dodaje szybkie płatności i płatności BLIK do sklepu na Magento 2.

## Spis treści
* [Wymagania](#wymgania)
* [Instalacja](#instalacja)
* [Konfiguracja](#konfiguracja)
* [FAQ](#faq)
* [Sandbox](#sandbox)
* [Wsparcie](#wsparcie)
* [Licencja](#licencja)

## Wymgania
- PHP od wersji 7.1
- Magento w wersji 2.0 lub wyższej

## Instalacja
Moduł można zainstalować przez Composer:
```bash
composer require pay-now/paynow-magento2
bin/magento module:enable Paynow_PaymentGateway
bin/magento setup:upgrade
```

## Konfiguracja
1. Przejdź do strony administracyjnej sklepu
2. Przejdź do  **Stores > Configuration > Sales > Payment Methods**.
3. Z listy dostępnych metod płatności wybierz **Paynow**
4. Po dokonaniu modyfikacji parametrów zapisz zmiany

## FAQ

**Jak skonfigurować adres powrotu?**

Adres powrotu ustawi się automatycznie dla każdego zamówienia. Nie ma potrzeby ręcznej konfiguracji tego adresu.

**Jak skonfigurować adres powiadomień?**

W panelu sprzedawcy Paynow przejdź do zakładki `Ustawienia > Sklepy i punkty płatności`, w polu `Adres powiadomień` ustaw adres:
`https://twoja-domena.pl/paynow/payment/notifications`.

## Sandbox
W celu przetestowania działania bramki Paynow zapraszamy do skorzystania z naszego środowiska testowego. W tym celu zarejestruj się na stronie: [panel.sandbox.paynow.pl][ext2]. 

## Wsparcie
Jeśli masz jakiekolwiek pytania lub problemy, skontaktuj się z naszym wsparciem technicznym: support@paynow.pl.

Jeśli chciałbyś dowiedzieć się więcej o bramce płatności Paynow odwiedź naszą stronę: https://www.paynow.pl/.

## Licencja
Licencja MIT. Szczegółowe informacje znajdziesz w pliku LICENSE.

[ext0]: README.EN.md
[ext1]: https://github.com/pay-now/paynow-magento2/releases/latest
[ext2]: https://panel.sandbox.paynow.pl/auth/register
