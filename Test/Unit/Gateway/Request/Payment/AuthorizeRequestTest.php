<?php

namespace Paynow\PaymentGateway\Test\Unit\Gateway\Request\Payment;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Directory\Model\Region;
use Magento\Directory\Model\RegionFactory;
use Magento\Framework\DataObject;
use Magento\Framework\HTTP\Header;
use Magento\Payment\Gateway\Data\AddressAdapterInterface;
use Magento\Payment\Gateway\Data\OrderAdapterInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Quote\Model\Quote;
use Magento\Sales\Model\Order\Payment;
use Paynow\PaymentGateway\Gateway\Request\Payment\AuthorizeRequest;
use Paynow\PaymentGateway\Helper\ConfigHelper;
use Paynow\PaymentGateway\Helper\PaymentField;
use Paynow\PaymentGateway\Helper\PaymentHelper;
use PHPUnit\Framework\TestCase;

/**
 * Class AuthorizeRequestTest
 *
 * @package Paynow\PaymentGateway\Test\Unit\Gateway\Request\Payment
 */
class AuthorizeRequestTest extends TestCase
{
    private const REGIONS = ['PL' => ['PL-10' => 'łódzkie', 'PL-14' => 'mazowieckie']];

    public function testBuildUsesBillingAddressWhenOrderHasNoShippingAddress()
    {
        $billing = $this->createAddress('billing@example.com', 'Jan', 'Kowalski', 'Wesoła 22', '95-060');

        $buyer = $this->build(null, $billing)['body'][PaymentField::BUYER_FIELD_NAME];

        static::assertSame('billing@example.com', $buyer[PaymentField::BUYER_EMAIL_FIELD_NAME]);
        static::assertSame('Jan', $buyer[PaymentField::BUYER_FIRSTNAME_FIELD_NAME]);
        static::assertSame('Kowalski', $buyer[PaymentField::BUYER_LASTNAME_FIELD_NAME]);
        static::assertSame(
            $buyer[PaymentField::BUYER_ADDRESS_KEY][PaymentField::BUYER_BILLING_ADDRESS_KEY],
            $buyer[PaymentField::BUYER_ADDRESS_KEY][PaymentField::BUYER_SHIPPING_ADDRESS_KEY]
        );
        static::assertSame(
            'Wesoła 22',
            $buyer[PaymentField::BUYER_ADDRESS_KEY][PaymentField::BUYER_SHIPPING_ADDRESS_KEY][PaymentField::BUYER_SHIPPING_ADDRESS_STREET]
        );
    }

    public function testBuildUsesShippingAddressForBuyerWhenPresent()
    {
        $shipping = $this->createAddress('shipping@example.com', 'Anna', 'Nowak', 'Długa 1', '00-001');
        $billing = $this->createAddress('billing@example.com', 'Jan', 'Kowalski', 'Wesoła 22', '95-060');

        $buyer = $this->build($shipping, $billing)['body'][PaymentField::BUYER_FIELD_NAME];
        $addresses = $buyer[PaymentField::BUYER_ADDRESS_KEY];

        static::assertSame('shipping@example.com', $buyer[PaymentField::BUYER_EMAIL_FIELD_NAME]);
        static::assertSame('Anna', $buyer[PaymentField::BUYER_FIRSTNAME_FIELD_NAME]);
        static::assertSame('Nowak', $buyer[PaymentField::BUYER_LASTNAME_FIELD_NAME]);
        static::assertSame(
            'Długa 1',
            $addresses[PaymentField::BUYER_SHIPPING_ADDRESS_KEY][PaymentField::BUYER_SHIPPING_ADDRESS_STREET]
        );
        static::assertSame(
            '95-060',
            $addresses[PaymentField::BUYER_BILLING_ADDRESS_KEY][PaymentField::BUYER_BILLING_ADDRESS_ZIPCODE]
        );
    }

    public function testBuildSendsCountyOfEachAddress()
    {
        $shipping = $this->createAddress('shipping@example.com', 'Anna', 'Nowak', 'Długa 1', '00-001', 'PL-14');
        $billing = $this->createAddress('billing@example.com', 'Jan', 'Kowalski', 'Wesoła 22', '95-060', 'PL-10');

        $addresses = $this->build($shipping, $billing)['body'][PaymentField::BUYER_FIELD_NAME][PaymentField::BUYER_ADDRESS_KEY];

        static::assertSame(
            'mazowieckie',
            $addresses[PaymentField::BUYER_SHIPPING_ADDRESS_KEY][PaymentField::BUYER_SHIPPING_ADDRESS_COUNTY]
        );
        static::assertSame(
            'łódzkie',
            $addresses[PaymentField::BUYER_BILLING_ADDRESS_KEY][PaymentField::BUYER_BILLING_ADDRESS_COUNTY]
        );
    }

    public function testBuildSendsEmptyCountyWhenAddressHasNoRegion()
    {
        $billing = $this->createAddress('billing@example.com', 'Jan', 'Kowalski', 'Wesoła 22', '95-060', '');

        $addresses = $this->build(null, $billing)['body'][PaymentField::BUYER_FIELD_NAME][PaymentField::BUYER_ADDRESS_KEY];

        static::assertSame('', $addresses[PaymentField::BUYER_BILLING_ADDRESS_KEY][PaymentField::BUYER_BILLING_ADDRESS_COUNTY]);
    }

    private function build(?AddressAdapterInterface $shipping, ?AddressAdapterInterface $billing): array
    {
        $order = $this->createMock(OrderAdapterInterface::class);
        $order->method('getOrderIncrementId')->willReturn('000000049');
        $order->method('getGrandTotalAmount')->willReturn(58.0);
        $order->method('getCurrencyCode')->willReturn('PLN');
        $order->method('getStoreId')->willReturn(1);
        $order->method('getShippingAddress')->willReturn($shipping);
        $order->method('getBillingAddress')->willReturn($billing);

        $payment = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $payment->method('hasAdditionalInformation')->willReturn(false);

        $paymentDO = $this->createMock(PaymentDataObjectInterface::class);
        $paymentDO->method('getPayment')->willReturn($payment);
        $paymentDO->method('getOrder')->willReturn($order);

        $helper = $this->createMock(PaymentHelper::class);
        $helper->method('formatAmount')->willReturn(5800);
        $helper->method('getStoreLocale')->willReturn('pl-PL');
        $helper->method('getContinueUrl')->willReturn('https://shop.test/paynow/checkout/success');

        // Region::loadByCode() loads nothing for an empty code, so getName() is null then
        $regionFactory = $this->createMock(RegionFactory::class);
        $regionFactory->expects(static::exactly(2))->method('create')->willReturnCallback(function () {
            $region = $this->createMock(Region::class);
            $region->method('loadByCode')->willReturnCallback(
                fn ($code, $countryId) => new DataObject(['name' => self::REGIONS[$countryId][$code] ?? null])
            );

            return $region;
        });

        $quote = $this->createMock(Quote::class);
        $quote->method('getId')->willReturn(10);
        $checkoutSession = $this->createMock(CheckoutSession::class);
        $checkoutSession->method('getQuote')->willReturn($quote);

        $request = new AuthorizeRequest(
            $helper,
            $this->createMock(ConfigHelper::class),
            $regionFactory,
            $checkoutSession,
            $this->createMock(Header::class)
        );

        return $request->build(['payment' => $paymentDO]);
    }

    private function createAddress(
        string $email,
        string $firstname,
        string $lastname,
        string $street,
        string $postcode,
        string $regionCode = 'PL-10'
    ): AddressAdapterInterface {
        $address = $this->createMock(AddressAdapterInterface::class);
        $address->method('getEmail')->willReturn($email);
        $address->method('getFirstname')->willReturn($firstname);
        $address->method('getLastname')->willReturn($lastname);
        $address->method('getStreetLine1')->willReturn($street);
        $address->method('getStreetLine2')->willReturn('1');
        $address->method('getPostcode')->willReturn($postcode);
        $address->method('getCity')->willReturn('Brzeziny');
        $address->method('getRegionCode')->willReturn($regionCode);
        $address->method('getCountryId')->willReturn('PL');

        return $address;
    }
}
