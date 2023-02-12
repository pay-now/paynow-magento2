<?php

namespace Paynow\PaymentGateway\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Sales\Model\OrderFactory;
use Magento\Sales\Model\Order;
use Magento\Store\Model\StoreManagerInterface;
use Paynow\Exception\SignatureVerificationException;
use Paynow\Notification;
use Paynow\PaymentGateway\Helper\ConfigHelper;
use Paynow\PaymentGateway\Helper\LockingHelper;
use Paynow\PaymentGateway\Model\Exception\NotificationRetryProcessing;
use Paynow\PaymentGateway\Model\Exception\NotificationStopProcessing;
use Paynow\PaymentGateway\Helper\NotificationProcessor;
use Paynow\PaymentGateway\Helper\PaymentField;
use Paynow\PaymentGateway\Helper\PaymentHelper;
use Paynow\PaymentGateway\Model\Logger\Logger;
use Zend\Http\Headers;

/**
 * Class Notifications
 *
 * @package Paynow\PaymentGateway\Controller\Payment
 */
class Notifications extends Action
{
    /**
     * @var StoreManagerInterface
     */
    protected $storeManager;

    /**
     * @var NotificationProcessor
     */
    private $notificationProcessor;

    /**
     * @var PaymentHelper
     */
    private $paymentHelper;

    /**
     * @var ConfigHelper
     */
    private $configHelper;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    /**
     * @var Logger
     */
    private $logger;

    /**
     * @var LockingHelper
     */
    private $lockingHelper;

    /**
     * Notifications constructor.
     *
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param NotificationProcessor $notificationProcessor
     * @param Logger $logger
     * @param PaymentHelper $paymentHelper
     * @param ConfigHelper $configHelper
     * @param OrderFactory $orderFactory
     * @param LockingHelper $lockingHelper
     */
    public function __construct(
        Context               $context,
        StoreManagerInterface $storeManager,
        NotificationProcessor $notificationProcessor,
        Logger                $logger,
        PaymentHelper         $paymentHelper,
        ConfigHelper          $configHelper,
        OrderFactory          $orderFactory,
        LockingHelper         $lockingHelper
    ) {
        parent::__construct($context);
        $this->storeManager          = $storeManager;
        $this->notificationProcessor = $notificationProcessor;
        $this->logger                = $logger;
        $this->paymentHelper         = $paymentHelper;
        $this->configHelper          = $configHelper;
        $this->orderFactory          = $orderFactory;
        $this->lockingHelper         = $lockingHelper;
        if (interface_exists(\Magento\Framework\App\CsrfAwareActionInterface::class)) {
            $request = $this->getRequest();
            if ($request instanceof Http && $request->isPost()) {
                $request->setParam('isAjax', true);
                $request->getHeaders()->addHeaderLine('X_REQUESTED_WITH', 'XMLHttpRequest');
            }
        }
    }

    /**
     * Process payment status notification
     *
     * @return void
     */
    public function execute()
    {
        $payload          = $this->getRequest()->getContent();
        $notificationData = json_decode($payload, true);
        $this->logger->debug("Received payment status notification", $notificationData);

        $storeId      = $this->storeManager->getStore()->getId();
        $order        = $this->orderFactory->create()
            ->loadByIncrementId((string)$notificationData[PaymentField::EXTERNAL_ID_FIELD_NAME] ?? '');
        if ($order->getId()) {
            $this->storeManager->setCurrentStore($order->getStoreId());
            $storeId = $order->getStoreId();
        }

        $signatureKey = $this->configHelper->getSignatureKey(
            $storeId,
            $this->configHelper->isTestMode(
                $storeId
            )
        );

        try {
            new Notification(
                $signatureKey,
                $payload,
                apache_request_headers()
            );
            $this->notificationProcessor->process(
                $notificationData[PaymentField::PAYMENT_ID_FIELD_NAME],
                $notificationData[PaymentField::STATUS_FIELD_NAME],
                $notificationData[PaymentField::EXTERNAL_ID_FIELD_NAME],
                $notificationData[PaymentField::MODIFIED_AT] ?? ''
            );
            $this->lockingHelper->delete($notificationData[PaymentField::EXTERNAL_ID_FIELD_NAME]);
        } catch (SignatureVerificationException $exception) {
            $this->logger->error(
                'Error occurred handling notification: ' . $exception->getMessage(),
                $notificationData
            );
            $this->lockingHelper->delete($notificationData[PaymentField::EXTERNAL_ID_FIELD_NAME] ?? '');
            $this->getResponse()->setHttpResponseCode(400);
        } catch (NotificationStopProcessing | NotificationRetryProcessing $exception) {
            $responseCode = ($exception instanceof NotificationStopProcessing) ? 200 : 400;
            $exception->logContext['responseCode'] = $responseCode;
            $this->logger->debug(
                $exception->logMessage,
                $exception->logContext
            );
            $this->lockingHelper->delete($notificationData[PaymentField::EXTERNAL_ID_FIELD_NAME] ?? '');
            $this->getResponse()->setHttpResponseCode($responseCode);
        } catch (\Exception $exception) {
            $notificationData['exeption'] = $exception->getMessage();
            $this->logger->debug(
                'Payment status notification processor -> unknown error',
                $notificationData
            );
            $this->lockingHelper->delete($notificationData[PaymentField::EXTERNAL_ID_FIELD_NAME] ?? '');
            $this->getResponse()->setHttpResponseCode(400);
        }
    }
}
