<?php

namespace Paynow\PaymentGateway\Controller\Checkout;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;
use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\Api\SearchCriteriaBuilder;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\Redirect as ResponseRedirect;
use Magento\Framework\Exception\NoSuchEntityException;
use Magento\Framework\UrlInterface;
use Magento\Sales\Api\Data\OrderInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Paynow\PaymentGateway\Helper\ConfigHelper;
use Paynow\PaymentGateway\Helper\NotificationProcessor;
use Paynow\PaymentGateway\Helper\PaymentField;
use Paynow\PaymentGateway\Helper\PaymentStatusService;
use Paynow\PaymentGateway\Model\Exception\NotificationRetryProcessing;
use Paynow\PaymentGateway\Model\Exception\NotificationStopProcessing;
use Paynow\PaymentGateway\Model\Logger\Logger;

/**
 * Class Return
 *
 * @package Paynow\PaymentGateway\Controller\Checkout
 */
class Success extends Action
{
    /**
     * @var CheckoutSession
     */
    private $checkoutSession;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var ResponseRedirect
     */
    private $redirectResult;

    /**
     * @var UrlInterface
     */
    protected $urlBuilder;

    /**
     * @var NotificationProcessor
     */
    private $notificationProcessor;

    /**
     * @var PaymentStatusService
     */
    private $paymentStatusService;

    /**
     * @var OrderRepositoryInterface
     */
    private $orderRepository;

    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var SearchCriteriaBuilder
     */
    protected $searchCriteriaBuilder;

    /**
     * Success constructor.
     *
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param Logger $logger
     * @param UrlInterface $urlBuilder
     * @param NotificationProcessor $notificationProcessor
     * @param PaymentStatusService $paymentStatusService
     * @param OrderRepositoryInterface $orderRepository
     * @param ConfigHelper $configHelper
     * @param SearchCriteriaBuilder $searchCriteriaBuilder
     * @param StoreManagerInterface $storeManager
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        Logger $logger,
        UrlInterface $urlBuilder,
        NotificationProcessor $notificationProcessor,
        PaymentStatusService $paymentStatusService,
        OrderRepositoryInterface $orderRepository,
        ConfigHelper $configHelper,
        SearchCriteriaBuilder $searchCriteriaBuilder,
        StoreManagerInterface $storeManager

    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->redirectResult = $this->resultRedirectFactory->create();
        $this->urlBuilder = $urlBuilder;
        $this->notificationProcessor = $notificationProcessor;
        $this->paymentStatusService = $paymentStatusService;
        $this->orderRepository = $orderRepository;
        $this->configHelper = $configHelper;
        $this->searchCriteriaBuilder = $searchCriteriaBuilder;
        $this->storeManager =  $storeManager;
    }

    /**
     * @return ResponseRedirect
     * @throws NoSuchEntityException
     */
    public function execute(): ResponseRedirect
    {
        $this->redirectResult->setUrl(
            $this->urlBuilder->getUrl('checkout/onepage/success')
        );

        $token = $this->getRequest()->getParam('_token');
        $storeId = $this->storeManager->getStore()->getId();
        $isTestMode = $this->configHelper->isTestMode($storeId);
        $payload = JWT::decode($token ?? '', new Key($this->configHelper->getSignatureKey($storeId, $isTestMode), 'HS256'));
        if (property_exists($payload, 'referenceId') && is_numeric($payload->referenceId)) {
            $orders = $this->orderRepository->getList(
                $this->searchCriteriaBuilder
                ->addFilter(OrderInterface::INCREMENT_ID, $payload->referenceId)
                ->create()
            )->getItems();
            $order = array_shift($orders);
            if (is_null($order) || is_null($order->getEntityId())) {
                return $this->redirectResult;
            }
            $this->checkoutSession->setLastSuccessQuoteId($order->getQuoteId());
            $this->checkoutSession->setLastQuoteId($order->getQuoteId());
            $this->checkoutSession->setLastOrderId($order->getEntityId());
            $this->checkoutSession->setLastRealOrderId($order->getIncrementId());
        } else {
            $order = $this->checkoutSession->getLastRealOrder();
        }

        if (!is_null($order) && !is_null($order->getEntityId()) && $this->shouldRetrieveStatus($order)) {
            $this->retrievePaymentStatusAndUpdateOrder($order);
        }
        return $this->redirectResult;
    }

    private function retrievePaymentStatusAndUpdateOrder(Order $order)
    {
        $allPayments = $order->getAllPayments();
        $lastPaymentId = end($allPayments)->getAdditionalInformation(PaymentField::PAYMENT_ID_FIELD_NAME);
        if ($lastPaymentId == $order->getIncrementId() . '_UNKNOWN') {
            $status =  \Paynow\Model\Payment\Status::STATUS_PENDING;
        } else {
            $status = $this->paymentStatusService->getStatus($lastPaymentId, $order->getIncrementId());
        }
        $loggerContext = [PaymentField::PAYMENT_ID_FIELD_NAME => $lastPaymentId];
        try {
            $this->notificationProcessor->process(
                $lastPaymentId,
                $status,
                $order->getIncrementId(),
                date("Y-m-d\TH:i:s"),
                true
            );
        } catch (NotificationStopProcessing | NotificationRetryProcessing $exception) {
            $this->logger->debug($exception->logMessage, $exception->logContext);
        } catch (\Exception $exception) {
            $loggerContext['exception'] = [
                'message' => $exception->getMessage(),
                'file' => $exception->getFile(),
                'line' => $exception->line(),
            ];
            $this->logger->error(
                'Error occurred handling notification',
                $loggerContext
            );
        }
    }

    /**
     * @param Order $order
     * @return bool
     */
    private function shouldRetrieveStatus(Order $order): bool
    {
        return $this->getRequest()->getParam('paymentStatus') &&
            $this->getRequest()->getParam('paymentId') &&
            count($order->getAllPayments()) > 0;
    }
}
