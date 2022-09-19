<?php

namespace Paynow\PaymentGateway\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\Request\Http;
use Magento\Store\Model\StoreManagerInterface;
use Paynow\Exception\SignatureVerificationException;
use Paynow\Notification;
use Paynow\PaymentGateway\Helper\ConfigHelper;
use Paynow\PaymentGateway\Helper\NotificationProcessor;
use Paynow\PaymentGateway\Helper\PaymentField;
use Paynow\PaymentGateway\Helper\PaymentHelper;
use Paynow\PaymentGateway\Model\Exception\OrderHasBeenAlreadyPaidException;
use Paynow\PaymentGateway\Model\Exception\OrderNotFound;
use Paynow\PaymentGateway\Model\Exception\OrderPaymentStatusTransitionException;
use Paynow\PaymentGateway\Model\Exception\OrderPaymentStrictStatusTransitionException;
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
     * @var Logger
     */
    private $logger;

    /**
     * Redirect constructor.
     *
     * @param Context               $context
     * @param StoreManagerInterface $storeManager
     * @param NotificationProcessor $notificationProcessor
     * @param Logger                $logger
     * @param PaymentHelper         $paymentHelper
     * @param ConfigHelper          $configHelper
     */
    public function __construct(
        Context               $context,
        StoreManagerInterface $storeManager,
        NotificationProcessor $notificationProcessor,
        Logger                $logger,
        PaymentHelper         $paymentHelper,
        ConfigHelper          $configHelper
    ) {
        parent::__construct($context);
        $this->storeManager          = $storeManager;
        $this->notificationProcessor = $notificationProcessor;
        $this->logger                = $logger;
        $this->paymentHelper         = $paymentHelper;
        $this->configHelper          = $configHelper;
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
                $notificationData[PaymentField::EXTERNAL_ID_FIELD_NAME]
            );
        } catch (SignatureVerificationException $exception) {
            $this->logger->error(
                'Error occurred handling notification: ' . $exception->getMessage(),
                $notificationData
            );
            $this->getResponse()->setHttpResponseCode(400);
        } catch (OrderPaymentStatusTransitionException|OrderPaymentStrictStatusTransitionException|OrderNotFound $exception) {
            $this->logger->warning(
                $exception->getMessage(),
                $notificationData
            );
            $this->getResponse()->setHttpResponseCode(400);
        } catch (OrderHasBeenAlreadyPaidException $exception) {
            $this->logger->info($exception->getMessage() . ' Skip processing the notification.');
            $this->getResponse()->setHttpResponseCode(200);
        }
    }
}
