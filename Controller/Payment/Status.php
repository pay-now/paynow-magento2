<?php

namespace Paynow\PaymentGateway\Controller\Payment;

use Magento\Framework\App\Action\Action;
use Magento\Framework\App\Action\Context;
use Magento\Framework\Controller\Result\JsonFactory;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Paynow\PaymentGateway\Helper\PaymentField;

/**
 * Class Status
 *
 * @package Paynow\PaymentGateway\Controller\Payment
 */
class Status extends Action
{
    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    /**
     * @var OrderFactory
     */
    private $orderFactory;

    public function __construct(OrderFactory $orderFactory, JsonFactory $resultJsonFactory, Context $context)
    {
        parent::__construct($context);
        $this->orderFactory = $orderFactory;
        $this->resultJsonFactory = $resultJsonFactory;
    }

    public function execute()
    {
        $resultJson = $this->resultJsonFactory->create();
        $id_order = $this->getRequest()->getParam('id_order');

        /** @var Order */
        $order = $this->orderFactory->create()->loadByIncrementId($id_order);
        if (!$order->getId()) {
            return $resultJson->setStatusHeader(404, null, 'Not Found');
        }

        $paymentAdditionalInformation = $order->getPayment()->getAdditionalInformation();
        $paymentStatus = $paymentAdditionalInformation[PaymentField::STATUS_FIELD_NAME] ?? null;

        return $resultJson->setData([
            'order_status'   => $order->getStatus(),
            'order_status_label'   => $order->getStatusLabel(),
            'payment_status' => $paymentStatus
        ]);
    }
}
