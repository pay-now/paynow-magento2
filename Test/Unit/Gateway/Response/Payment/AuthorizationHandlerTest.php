<?php

namespace Paynow\PaymentGateway\Test\Unit\Gateway\Response\Payment;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\Order\Payment;
use Paynow\PaymentGateway\Gateway\Response\Payment\AuthorizationHandler;
use Paynow\PaymentGateway\Helper\PaymentField;
use PHPUnit\Framework\TestCase;

/**
 * Class AuthorizationHandlerTest
 *
 * @package Paynow\PaymentGateway\Test\Unit\Gateway\Response\Payment
 */
class AuthorizationHandlerTest extends TestCase
{
    public function testHandle()
    {
        $paymentDO = $this->createMock(PaymentDataObjectInterface::class);
        $paymentInfo = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();
        $order = $this->getMockBuilder(Order::class)
            ->disableOriginalConstructor()
            ->getMock();

        $handlingSubject = [
            'payment' => $paymentDO
        ];

        $response = [
            PaymentField::PAYMENT_ID_FIELD_NAME => 'testPaymentId',
            PaymentField::STATUS_FIELD_NAME => 'NEW',
            PaymentField::REDIRECT_URL_FIELD_NAME => 'testRedirectUrl',
        ];

        $paymentDO->expects(static::atLeastOnce())
            ->method('getPayment')
            ->willReturn($paymentInfo);

        $paymentInfo->expects(static::once())
            ->method('setTransactionId')
            ->with("testPaymentId")
            ->willReturn($paymentInfo);

        $paymentInfo->expects(static::once())
            ->method('setIsTransactionPending')
            ->with(true)
            ->willReturn($paymentInfo);

        $paymentInfo->expects(static::once())
            ->method('setIsTransactionClosed')
            ->with(false)
            ->willReturn($paymentInfo);

        $paymentInfo->expects(static::exactly(3))
            ->method('setAdditionalInformation')
            ->willReturn($paymentInfo);

        $handler = new AuthorizationHandler();
        $handler->handle($handlingSubject, $response);
    }
}
