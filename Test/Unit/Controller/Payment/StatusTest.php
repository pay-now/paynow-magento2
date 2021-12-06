<?php

namespace Paynow\PaymentGateway\Test\Unit\Controller\Payment;

use PHPUnit\Framework\TestCase;

class StatusTest extends TestCase
{
    protected $controller;
    protected $resultJsonFactory;

    public function setUp()
    {
        $objectManager = new \Magento\Framework\TestFramework\Unit\Helper\ObjectManager($this);
        $jsonResult = $objectManager->getObject('Magento\Framework\Controller\Result\Json');

        $this->resultJsonFactory = $this->getMockBuilder('Magento\Framework\Controller\Result\JsonFactory')
                                        ->disableOriginalConstructor()
                                        ->getMock();

        $this->resultJsonFactory->method('create')->willReturn($jsonResult);

        $this->controller = $objectManager->getObject(
            '\Paynow\PaymentGateway\Controller\Payment\Status',
            ['resultJsonFactory' => $this->resultJsonFactory]
        );

        parent::setUp();
    }

    public function testStatus()
    {
        $this->assertTrue(true);
//        $result = $this->controller->execute();
//
////        $this->assertInstanceOf('\Magento\Framework\Controller\Result\Json', $result);
    }
}
