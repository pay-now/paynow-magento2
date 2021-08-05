<?php

namespace Paynow\PaymentGateway\Controller\Checkout;

use Magento\Checkout\Model\Session as CheckoutSession;
use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\App\CsrfAwareActionInterface;
use Magento\Framework\App\Request\Http;
use Magento\Framework\App\ResponseInterface;
use Magento\Framework\Controller\Result\Redirect as ResponseRedirect;
use Magento\Framework\Controller\ResultInterface;
use Magento\Framework\Exception\LocalizedException;
use Paynow\PaymentGateway\Helper\PaymentField;
use Paynow\PaymentGateway\Model\Logger\Logger;

/**
 * Class Redirect
 *
 * @package Paynow\PaymentGateway\Controller\Checkout
 */
class Redirect extends Action
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
     * Redirect constructor.
     * @param Context $context
     * @param CheckoutSession $checkoutSession
     * @param Logger $logger
     */
    public function __construct(
        Context $context,
        CheckoutSession $checkoutSession,
        Logger $logger
    ) {
        parent::__construct($context);
        $this->checkoutSession = $checkoutSession;
        $this->logger = $logger;
        $this->redirectResult = $this->resultRedirectFactory->create();
        if (interface_exists(CsrfAwareActionInterface::class)) {
            $request = $this->getRequest();
            if ($request instanceof Http && $request->isPost()) {
                $request->setParam('isAjax', true);
                $request->getHeaders()->addHeaderLine('X_REQUESTED_WITH', 'XMLHttpRequest');
            }
        }
    }

    /**
     * @return ResponseInterface|ResponseRedirect|ResultInterface
     */
    public function execute()
    {
        $order = $this->checkoutSession->getLastRealOrder();
        try {
            if (!$order->getRealOrderId()) {
                $this->logger->error('An error occurred during checkout: Can\'t get order');
                $this->setRedirectToCart();
            } else {
                $redirectUrl = $order->getPayment()->getAdditionalInformation(PaymentField::REDIRECT_URL_FIELD_NAME);
                $paymentId = $order->getPayment()->getAdditionalInformation(PaymentField::PAYMENT_ID_FIELD_NAME);
                if ($redirectUrl) {
                    $this->logger->info(
                        'Redirecting to payment provider page',
                        [
                            PaymentField::EXTERNAL_ID_FIELD_NAME => $order->getRealOrderId(),
                            PaymentField::PAYMENT_ID_FIELD_NAME => $paymentId
                        ]
                    );
                    $this->redirectResult->setUrl((string)$redirectUrl);
                }
            }
        } catch (LocalizedException $exception) {
            $this->logger->error('An error occurred during checkout: ' . $exception->getMessage(), [
                PaymentField::EXTERNAL_ID_FIELD_NAME => $order->getRealOrderId()
            ]);
            $this->setRedirectToCart();
        }

        return $this->redirectResult;
    }

    private function setRedirectToCart()
    {
        $this->messageManager->addErrorMessage(__('An error occurred during checkout. Please try again for a moment.'));
        $this->redirectResult->setPath('checkout/cart', ['_secure' => $this->getRequest()->isSecure()]);
    }
}
