<?php

namespace Paynow\PaymentGateway\Controller\Payment;

use Exception;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Store\Model\StoreManagerInterface;
use Paynow\Exception\SignatureVerificationException;
use Paynow\Notification;
use Paynow\PaymentGateway\Helper\PaymentField;
use Paynow\PaymentGateway\Helper\PaymentHelper;
use Paynow\PaymentGateway\Helper\NotificationProcessor;
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
     * @var Logger
     */
    private $logger;

    /**
     * Redirect constructor.
     * @param Context $context
     * @param StoreManagerInterface $storeManager
     * @param NotificationProcessor $notificationProcessor
     * @param Logger $logger
     * @param PaymentHelper $paymentHelper
     */
    public function __construct(
        Context $context,
        StoreManagerInterface $storeManager,
        NotificationProcessor $notificationProcessor,
        Logger $logger,
        PaymentHelper $paymentHelper
    ) {
        parent::__construct($context);
        $this->storeManager = $storeManager;
        $this->notificationProcessor = $notificationProcessor;
        $this->logger = $logger;
        $this->paymentHelper = $paymentHelper;
    }

    /**
     * Process payment status notification
     */
    public function execute()
    {
        $payload = $this->getRequest()->getContent();
        $notificationData = json_decode($payload, true);
        $this->logger->debug("Received payment status notification", $notificationData);
        $storeId = $this->storeManager->getStore()->getId();
        $signatureKey = $this->paymentHelper->getSignatureKey($storeId, $this->paymentHelper->isTestMode($storeId));

        try {
            new Notification(
                $signatureKey,
                $payload,
                $this->getSignaturesFromHeaders($this->getRequest()->getHeaders())
            );
            $this->notificationProcessor->process(
                $notificationData[PaymentField::PAYMENT_ID_FIELD_NAME],
                $notificationData[PaymentField::STATUS_FIELD_NAME],
                $notificationData[PaymentField::EXTERNAL_ID_FIELD_NAME]
            );
        } catch (SignatureVerificationException | Exception $exception) {
            $this->logger->error(
                'Error occurred handling notification: ' . $exception->getMessage(),
                $notificationData
            );
            $this->getResponse()->setHttpResponseCode(400);
        }
    }

    /**
     * @param Headers $headers
     * @return array
     */
    private function getSignaturesFromHeaders(Headers $headers)
    {
        return ['Signature' => $headers->has('Signature') ? $headers->get('Signature') : $headers->has('signature')];
    }
}
