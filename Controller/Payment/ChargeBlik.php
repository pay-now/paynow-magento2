<?php

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Paynow\Client;
use Paynow\Exception\PaynowException;
use Paynow\PaymentGateway\Helper\PaymentHelper;
use Paynow\Service\Payment;

class PaynowChargeBlikModuleFrontController extends Action
{
    /**
     * @var Client
     */
    private $client;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var PaymentDataBuilder
     */
    private $paymentDataBuilder;

    /**
     * @var Session
     */
    protected $checkoutSession;

    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    public function __construct(JsonFactory $resultJsonFactory, Context $context, Session $checkoutSession, PaymentDataBuilder $paymentDataBuilder, PaymentHelper $paymentHelper)
    {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession = $checkoutSession;
        $this->paymentDataBuilder = $paymentDataBuilder;
        $this->paymentHelper = $paymentHelper;
        $this->client = $paymentHelper->initializePaynowClient();
    }

    public function execute()
    {
        $this->executePayment();
    }

    private function executePayment()
    {
        $resultJson = $this->resultJsonFactory->create();

        $response = [
            'success' => false
        ];

            $cart = $this->checkoutSession->getQuote();
        if (empty($cart) || ! $cart->getId()) {
            return $resultJson->setData($response);
        }

        try {
            $external_id          = $cart->getId();
            $idempotency_key      = uniqid($external_id . '_');
            $payment_request_data = $this->paymentDataBuilder->fromCart();
             $service = new Payment($this->client);
            $payment              = $service->authorize($payment_request_data, $idempotency_key);

            if ($payment && in_array($payment->getStatus(), [
                    Paynow\Model\Payment\Status::STATUS_NEW,
                    Paynow\Model\Payment\Status::STATUS_PENDING
                ])) {
//                $order    = new Order($this->createOrder($cart));
                $response = array_merge($response, [
                    'success'      => true,
                    'payment_id'   => $payment->getPaymentId()
                ]);

//                if ($order->id) {
//                    PaynowPaymentData::create(
//                        $payment->getPaymentId(),
//                        Paynow\Model\Payment\Status::STATUS_NEW,
//                        $order->id,
//                        $order->id_cart,
//                        $order->reference,
//                        $payment_request_data['externalId']
//                    );
//                }
                PaynowLogger::info(
                    'Payment has been successfully created {orderReference={}, paymentId={}, status={}}',
                    [
//                        $order->reference,
                        $payment->getPaymentId(),
                        $payment->getStatus()
                    ]
                );
            }
        } catch (PaynowException $exception) {
            if ($exception->getErrors() && $exception->getErrors()[0]) {
                switch ($exception->getErrors()[0]->getType()) {
                    case 'AUTHORIZATION_CODE_INVALID':
                        $response['message'] = 'Wrong BLIK code';
                        break;
                    case 'AUTHORIZATION_CODE_EXPIRED':
                        $response['message'] = 'BLIK code has expired';
                        break;
                    case 'AUTHORIZATION_CODE_USED':
                        $response['message'] = 'BLIK code already used';
                        break;
                    default:
                        $response['message'] = 'An error occurred during the payment process';
                }
            }
        }

        return $resultJson->setData($response);
    }

    private function createOrder($cart)
    {
        $currency = $this->context->currency;
        $customer = new Customer($cart->id_customer);

        $this->module->validateOrder(
            (int)$cart->id,
            Configuration::get('PAYNOW_ORDER_INITIAL_STATE'),
            (float)$cart->getOrderTotal(),
            $this->module->displayName,
            null,
            null,
            (int)$currency->id,
            false,
            $customer->secure_key
        );

        return $this->module->currentOrder;
    }
}
