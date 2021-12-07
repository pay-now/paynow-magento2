<?php
namespace Paynow\PaymentGateway\Controller\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Quote\Api\CartManagementInterface;
use Paynow\PaymentGateway\Helper\NotificationProcessor;
use Paynow\PaymentGateway\Helper\PaymentDataBuilder;
use Paynow\Client;
use Paynow\Exception\PaynowException;
use Paynow\PaymentGateway\Helper\PaymentHelper;
use Paynow\PaymentGateway\Model\Logger\Logger;
use Paynow\Service\Payment;
use Paynow\Model\Payment\Status;

class ChargeBlik extends Action
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

    /**
     * @var Http
     */
    protected $request;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var CartManagementInterface
     */
    protected $quoteManagement;

    /**
     * @var NotificationProcessor
     */
    private $notificationProcessor;

    public function __construct(
        JsonFactory $resultJsonFactory,
        Context $context,
        Session $checkoutSession,
        PaymentDataBuilder $paymentDataBuilder,
        PaymentHelper $paymentHelper,
        Http $request,
        Logger $logger,
        CartManagementInterface $quoteManagement,
        NotificationProcessor $notificationProcessor
    ) {
        parent::__construct($context);
        $this->resultJsonFactory = $resultJsonFactory;
        $this->checkoutSession = $checkoutSession;
        $this->paymentDataBuilder = $paymentDataBuilder;
        $this->paymentHelper = $paymentHelper;
        $this->client = $paymentHelper->initializePaynowClient();
        $this->request = $request;
        $this->logger = $logger;
        $this->quoteManagement = $quoteManagement;
        $this->notificationProcessor = $notificationProcessor;
    }

    public function execute()
    {
        return $this->executePayment();
    }

    private function executePayment()
    {
        $resultJson = $this->resultJsonFactory->create();

        $response = [
            'success' => false
        ];

            $quote = $this->checkoutSession->getQuote();
        if (empty($quote) || ! $quote->getId()) {
            $resultJson->setData($response);
            return $resultJson;
        }

        try {
            $external_id          = $quote->getId();
            $idempotency_key      = uniqid($external_id . '_');
            $blikCode = $this->request->getParam('blikCode');
            $payment_request_data = $this->paymentDataBuilder->fromCart($blikCode);
             $service = new Payment($this->client);
            $payment              = $service->authorize($payment_request_data, $idempotency_key);

            if ($payment && in_array($payment->getStatus(), [
                    Status::STATUS_NEW,
                    Status::STATUS_PENDING
                ])) {

                $order = $this->createOrder($quote);

                $response = array_merge($response, [
                    'success'      => true,
                    'payment_id'   => $payment->getPaymentId()
                ]);

                $this->logger->info(
                    'Payment has been successfully created { paymentId={}, status={}}',
                    [
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

         $resultJson->setData($response);
        return $resultJson;
    }

    /**
     * @param $quote
     * @return mixed
     */
    private function createOrder($quote)
    {
        $order = $this->quoteManagement->submit($quote);

        $this->checkoutSession
            ->setLastOrderId($order->getId())
            ->setLastRealOrderId($order->getIncrementId());

        return $order;
    }
}
