<?php

namespace Paynow\PaymentGateway\Test\Unit\Gateway\Validator\Payment;

use Magento\Framework\Phrase;
use Magento\Payment\Gateway\Validator\Result;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Paynow\PaymentGateway\Gateway\Validator\Payment\CaptureValidator;
use Paynow\PaymentGateway\Helper\PaymentField;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class CaptureValidatorTest
 *
 * @package Paynow\PaymentGateway\Test\Unit\Gateway\Validator\Payment
 */
class CaptureValidatorTest extends TestCase
{
    /**
     * @var CaptureValidator
     */
    private $captureValidator;

    /**
     * @var ResultInterfaceFactory|MockObject
     */
    private $resultFactory;

    /**
     * Set up
     *
     * @return void
     */
    protected function setUp()
    {
        $this->resultFactory = $this->getMockBuilder('Magento\Payment\Gateway\Validator\ResultInterfaceFactory')
            ->disableOriginalConstructor()
            ->setMethods(['create'])
            ->getMock();

        $loggerMock = $this->getMockBuilder('Paynow\PaymentGateway\Model\Logger\Logger')
            ->disableOriginalConstructor()
            ->getMock();

        $this->captureValidator = new CaptureValidator(
            $this->resultFactory,
            $loggerMock
        );
    }

    public function testValidateReadResponseException()
    {
        $this->expectException(\InvalidArgumentException::class);
        $validationSubject = [
            'response' => null
        ];

        $this->captureValidator->validate($validationSubject);
    }

    /**
     * Run test for validate method
     *
     * @param array $validationSubject
     * @param bool $isValid
     * @param Phrase[] $messages
     * @return void
     *
     * @dataProvider dataProviderTestValidate
     */
    public function testValidate(array $validationSubject, $isValid, $messages)
    {
        /** @var ResultInterface|MockObject $result */
        $result = new Result($isValid, $messages);

        $this->resultFactory->method('create')
            ->with(
                [
                    'isValid' => $isValid,
                    'failsDescription' => $messages
                ]
            )
            ->willReturn($result);

        $actual = $this->captureValidator->validate($validationSubject);

        self::assertEquals($result, $actual);
    }

    /**
     * @return array
     */
    public function dataProviderTestValidate()
    {
        return [
            [
                'validationSubject' => [
                    'response' => [
                        PaymentField::PAYMENT_ID_FIELD_NAME => 'testPaymentId',
                        PaymentField::STATUS_FIELD_NAME => 'CONFIRMED'
                    ],
                ],
                'isValid' => true,
                []
            ],
            [
                'validationSubject' => [
                    'response' => [
                        PaymentField::PAYMENT_ID_FIELD_NAME => 'testPaymentId',
                        PaymentField::STATUS_FIELD_NAME => 'NEW'
                    ]
                ],
                'isValid' => false,
                [
                    __('Error occurred during the capture process.')
                ]
            ]
        ];
    }
}
