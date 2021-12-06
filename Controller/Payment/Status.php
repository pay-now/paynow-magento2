<?php

namespace Paynow\PaymentGateway\Controller\Payment;

use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Magento\Framework\Controller\Result\JsonFactory;
use Paynow\PaymentGateway\Helper\PaymentField;

class Status extends Action
{
    /**
     * @var Magento\Sales\Model\OrderFactory
     */
    private $orderFactory;

    /**
     * @var JsonFactory
     */
    private $resultJsonFactory;

    public function __construct(Context $context, OrderFactory $orderFactory, JsonFactory $resultJsonFactory)
    {
        $this->orderFactory = $orderFactory;
        $this->resultJsonFactory = $resultJsonFactory;
        parent::__construct($context);
    }

    public function execute()
    {
        $result = $this->resultJsonFactory->create();
        $orderId = (int)$this->getRequest()->getParams()['order_id'];
        /** @var Order */
        $order = $this->orderFactory->create()->loadByIncrementId($orderId);

        if (!$order->getId()) {
            return $result->setStatusHeader(404);
        }

        $paymentAdditionalInformation = $order->getPayment()->getAdditionalInformation();
        $orderPaymentStatus = $paymentAdditionalInformation[PaymentField::STATUS_FIELD_NAME];

//        if ($this->getRequest()->isAjax()) {
            $data = [
                'order_status'   => $order->getStatus(), //getStatusLabel()
                'payment_status' => $orderPaymentStatus
            ];
            return $result->setData($data);
//        }

//        return null;
    }
}
