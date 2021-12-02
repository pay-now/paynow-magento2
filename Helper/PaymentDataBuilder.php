<?php

use Paynow\PaymentGateway\Helper\ConfigHelper;
use Paynow\PaymentGateway\Helper\PaymentField;
use Paynow\PaymentGateway\Helper\PaymentHelper;
use Magento\Checkout\Model\Session;

class PaymentDataBuilder
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
     * @var Session
     */
    protected $checkoutSession;

    public function __construct(PaymentHelper $paymentHelper, ConfigHelper $configHelper, Session $checkoutSession)
    {
        $this->helper = $paymentHelper;
        $this->config = $configHelper;
        $this->checkoutSession = $checkoutSession;
    }

    /**
     * Returns payment request data based on cart
     *
     * @return array
     * @throws Exception
     */
    public function fromCart(): array
    {
        return $this->build(
            $this->checkoutSession->getQuote()->getCurrency()->getQuoteCurrencyCode(),
            $this->checkoutSession->getQuote()->getGrandTotal(),
            $this->checkoutSession->getQuote()->getCustomer(),
            uniqid($this->checkoutSession->getQuote()->getId() . '_'),
            '2007'
        );
    }

    /**
     * Returns payment request data based on order
     *
     * @param $order
     *
     * @return array
     */
    public function fromOrder($order): array
    {
        return $this->build(
            $order->id_currency,
            $order->id_customer,
            $order->total_paid,
            $order->id_cart,
            $this->translations['Order No: '] . $order->reference,
            $order->id,
            $order->reference
        );
    }

    /**
     * Returns payments request data
     *
     * @param $id_currency
     * @param $id_customer
     * @param $total_to_paid
     * @param $external_id
     * @param $description
     *
     * @return array
     */
    private function build(
        $id_currency,
        $total_to_paid,
        $customer,
        $external_id = null,
        $payment_method_id = null
    ): array {
        $referenceId        = $this->order->getOrderIncrementId();
        $paymentDescription = __('Order No: ') . $external_id;

        $request['body'] = [
            PaymentField::AMOUNT_FIELD_NAME      => $this->helper->formatAmount($total_to_paid),
            PaymentField::CURRENCY_FIELD_NAME    => $id_currency,
            PaymentField::EXTERNAL_ID_FIELD_NAME => $external_id,
            PaymentField::DESCRIPTION_FIELD_NAME => $paymentDescription,
            PaymentField::BUYER_FIELD_NAME       => [
                PaymentField::BUYER_EMAIL_FIELD_NAME     => $customer->getEmail(),
                PaymentField::BUYER_FIRSTNAME_FIELD_NAME => $customer->getFirstname(),
                PaymentField::BUYER_LASTNAME_FIELD_NAME  => $customer->getLastname(),
                PaymentField::BUYER_LOCALE               => $this->helper->getStoreLocale(),
            ],
            PaymentField::CONTINUE_URL_FIELD_NAME => $this->helper->getContinueUrl()
        ];

        if ($payment_method_id) {
            $request['body'][PaymentField::PAYMENT_METHOD_ID] = $payment_method_id;
        }

//        if ($this->config->isSendOrderItemsActive()) {
//            $orderItems = $this->helper->getOrderItems($this->order);
//            if (! empty($orderItems)) {
//                $request['body'][PaymentField::ORDER_ITEMS] = $orderItems;
//            }
//        }

        if ($this->config->isPaymentValidityActive()) {
            $validityTime = $this->config->getPaymentValidityTime();
            if (! empty($validityTime)) {
                $request['body'][PaymentField::VALIDITY_TIME] = $this->config->getPaymentValidityTime();
            }
        }

        $request['headers'] = [
            PaymentField::IDEMPOTENCY_KEY_FIELD_NAME => uniqid($referenceId, true)
        ];

        return $request;
    }

    /**
     * @param $id_category_default
     *
     * @return string
     */
    private function getCategoriesNames($id_category_default): string
    {
        $categoryDefault = new Category($id_category_default, $this->context->language->id);
        $categoriesNames = [$categoryDefault->name];
        foreach ($categoryDefault->getAllParents() as $category) {
            if ($category->id_parent != 0 && !$category->is_root_category) {
                array_unshift($categoriesNames, $category->name);
            }
        }
        return implode(", ", $categoriesNames);
    }
}
