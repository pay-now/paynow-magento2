<?php

namespace Paynow\PaymentGateway\Gateway\Request\Payment;

use Magento\Directory\Model\ResourceModel\Country\Collection as CountryCollection;
use Magento\Directory\Model\ResourceModel\Region\Collection as RegionCollection;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Checkout\Model\Session as CheckoutSession;
use Paynow\PaymentGateway\Gateway\Request\AbstractRequest;
use Paynow\PaymentGateway\Helper\ConfigHelper;
use Paynow\PaymentGateway\Helper\PaymentField;
use Paynow\PaymentGateway\Helper\PaymentHelper;
use Paynow\PaymentGateway\Observer\PaymentDataAssignObserver;

/**
 * Class PaymentAuthorizationRequest
 *
 * @package Paynow\PaymentGateway\Gateway\Request\Payment
 */
class AuthorizeRequest extends AbstractRequest implements BuilderInterface
{
    /**
     * @var PaymentHelper
     */
    private $helper;

    /**
     * @var ConfigHelper
     */
    private $config;

    /**
     * @var RegionCollection
     */
    private $regionCollection;

    /**
     * @var CountryCollection
     */
    private $countryCollection;

	private $checkoutSession;

    public function __construct(
        PaymentHelper     $paymentHelper,
        ConfigHelper      $configHelper,
        RegionCollection  $regionCollection,
        CountryCollection $countryCollection,
		CheckoutSession   $checkoutSession
	) {
        $this->helper = $paymentHelper;
        $this->config = $configHelper;
        $this->regionCollection = $regionCollection;
        $this->countryCollection = $countryCollection;
		$this->checkoutSession = $checkoutSession;
    }

    /**
     * @param array $buildSubject
     *
     * @return array
     * @throws NoSuchEntityException
     */
    public function build(array $buildSubject)
    {
        parent::build($buildSubject);

        $referenceId        = $this->order->getOrderIncrementId();
        $paymentDescription = __('Order No: ') . $referenceId;

        $shippingAddress = $this->order->getShippingAddress();
        $billingAddress  = $this->order->getBillingAddress();
        $request['body'] = [
            PaymentField::AMOUNT_FIELD_NAME => $this->helper->formatAmount($this->order->getGrandTotalAmount()),
            PaymentField::CURRENCY_FIELD_NAME => $this->order->getCurrencyCode(),
            PaymentField::EXTERNAL_ID_FIELD_NAME => $referenceId,
            PaymentField::DESCRIPTION_FIELD_NAME => $paymentDescription,
            PaymentField::BUYER_FIELD_NAME => [
                PaymentField::BUYER_EMAIL_FIELD_NAME => $shippingAddress ? $shippingAddress->getEmail() : "",
                PaymentField::BUYER_FIRSTNAME_FIELD_NAME => $shippingAddress ? $shippingAddress->getFirstname() : "",
                PaymentField::BUYER_LASTNAME_FIELD_NAME => $shippingAddress ? $shippingAddress->getLastname() : "",
                PaymentField::BUYER_LOCALE => $this->helper->getStoreLocale(),
                PaymentField::BUYER_ADDRESS_KEY => [
                    PaymentField::BUYER_SHIPPING_ADDRESS_KEY => [
                        PaymentField::BUYER_SHIPPING_ADDRESS_STREET => $shippingAddress->getStreetLine1(),
                        PaymentField::BUYER_SHIPPING_ADDRESS_HOUSE_NUMBER => $shippingAddress->getStreetLine2(),
                        PaymentField::BUYER_SHIPPING_ADDRESS_APARTMENT_NUMBER => '',
                        PaymentField::BUYER_SHIPPING_ADDRESS_ZIPCODE => $shippingAddress->getPostcode(),
                        PaymentField::BUYER_SHIPPING_ADDRESS_CITY => $shippingAddress->getCity(),
                        PaymentField::BUYER_SHIPPING_ADDRESS_COUNTY => $this->regionCollection
                                ->addRegionCodeFilter($shippingAddress->getRegionCode())
                                ->getFirstItem()
                                ->getData('name') ?? '',
                        PaymentField::BUYER_SHIPPING_ADDRESS_COUNTRY => $shippingAddress->getCountryId() ?? '',
                    ],
                    PaymentField::BUYER_BILLING_ADDRESS_KEY => [
                        PaymentField::BUYER_BILLING_ADDRESS_STREET => $billingAddress->getStreetLine1(),
                        PaymentField::BUYER_BILLING_ADDRESS_HOUSE_NUMBER => $billingAddress->getStreetLine2(),
                        PaymentField::BUYER_BILLING_ADDRESS_APARTMENT_NUMBER => '',
                        PaymentField::BUYER_BILLING_ADDRESS_ZIPCODE => $billingAddress->getPostcode(),
                        PaymentField::BUYER_BILLING_ADDRESS_CITY => $billingAddress->getCity(),
                        PaymentField::BUYER_BILLING_ADDRESS_COUNTY => $this->regionCollection
                                ->addRegionCodeFilter($billingAddress->getRegionCode())
                                ->getFirstItem()
                                ->getData('name') ?? '',
                        PaymentField::BUYER_BILLING_ADDRESS_COUNTRY => $billingAddress->getCountryId() ?? '',
                    ]
                ]
            ],
            PaymentField::CONTINUE_URL_FIELD_NAME => $this->helper->getContinueUrl($referenceId, $this->order->getStoreId()),
        ];

        if ($this->order->getCustomerId()) {
            $request['body'][PaymentField::BUYER_FIELD_NAME][PaymentField::BUYER_EXTERNAL_ID] = $this->helper
                ->generateBuyerExternalId($this->order->getCustomerId(), $this->order->getStoreId());
        }
        $isRetry = (
            $this->payment->hasAdditionalInformation(PaymentField::IS_PAYMENT_RETRY_FIELD_NAME)
            && $this->payment->getAdditionalInformation(PaymentField::IS_PAYMENT_RETRY_FIELD_NAME) == 1
        );

        if ($this->payment->hasAdditionalInformation(PaymentDataAssignObserver::PAYMENT_METHOD_ID)
            && ! empty($this->payment->getAdditionalInformation(PaymentDataAssignObserver::PAYMENT_METHOD_ID))
            && !$isRetry) {
            $request['body'][PaymentField::PAYMENT_METHOD_ID] = $this->payment
                ->getAdditionalInformation(PaymentDataAssignObserver::PAYMENT_METHOD_ID);
        }

        if ($this->payment->hasAdditionalInformation(PaymentDataAssignObserver::PAYMENT_METHOD_TOKEN)
            && ! empty($this->payment->getAdditionalInformation(PaymentDataAssignObserver::PAYMENT_METHOD_TOKEN))
            && !$isRetry) {
            $request['body'][PaymentField::PAYMENT_METHOD_TOKEN] = $this->payment
                ->getAdditionalInformation(PaymentDataAssignObserver::PAYMENT_METHOD_TOKEN);
        }

        if ($this->payment->hasAdditionalInformation(PaymentDataAssignObserver::PAYMENT_METHOD_FINGERPRINT)
			&& ! empty($this->payment->getAdditionalInformation(PaymentDataAssignObserver::PAYMENT_METHOD_FINGERPRINT))
            && !$isRetry) {
			$request['body'][PaymentField::BUYER_FIELD_NAME][PaymentField::BUYER_DEVICE_FINGERPRINT] = $this->payment
				->getAdditionalInformation(PaymentDataAssignObserver::PAYMENT_METHOD_FINGERPRINT);
		}

        if ($this->config->isSendOrderItemsActive()) {
            $orderItems = $this->helper->getOrderItems($this->order);
            if (! empty($orderItems)) {
                $request['body'][PaymentField::ORDER_ITEMS] = $orderItems;
            }
        }

        if ($this->config->isPaymentValidityActive()) {
            $validityTime = $this->config->getPaymentValidityTime();
            if (! empty($validityTime)) {
                $request['body'][PaymentField::VALIDITY_TIME] = $this->config->getPaymentValidityTime();
            }
        }

        if ($this->payment->hasAdditionalInformation(PaymentDataAssignObserver::BLIK_CODE)
            && ! empty($this->payment->getAdditionalInformation(PaymentDataAssignObserver::BLIK_CODE))
            && !$isRetry) {
            $request['body'][PaymentField::AUTHORIZATION_CODE] = $this->payment
                ->getAdditionalInformation(PaymentDataAssignObserver::BLIK_CODE);
        }

        $request['headers'] = [
            PaymentField::IDEMPOTENCY_KEY_FIELD_NAME => uniqid(substr($referenceId, 0, 22), true),
			PaymentField::CART_ID_FIELD_NAME  => $this->checkoutSession->getQuote()->getId()
        ];

        return $request;
    }
}
