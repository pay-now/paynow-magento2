<?php

namespace Paynow\PaymentGateway\Test\Unit\Gateway\Validator\Payment;

use Magento\Framework\Phrase;
use Magento\Payment\Gateway\Validator\Result;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Paynow\PaymentGateway\Gateway\Validator\Payment\AuthorizationValidator;
use Paynow\PaymentGateway\Helper\PaymentField;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

/**
 * Class AuthorizationValidatorTest
 *
 * @package Paynow\PaymentGateway\Test\Unit\Gateway\Validator\Payment
 */
class AuthorizationValidatorTest extends TestCase
{
    /**
     * @var AuthorizationValidator
     */
    private $authorizationValidator;

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

        $this->authorizationValidator = new AuthorizationValidator(
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

        $this->authorizationValidator->validate($validationSubject);
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
                    'failsDescription' => $messages,
                    'errorCodes' => []
                ]
            )
            ->willReturn($result);

        $actual = $this->authorizationValidator->validate($validationSubject);

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
                        PaymentField::STATUS_FIELD_NAME => 'NEW',
                        PaymentField::REDIRECT_URL_FIELD_NAME => 'testRedirectUrl',
                        PaymentField::EXTERNAL_ID_FIELD_NAME => 'testExternalId'
                    ],
                ],
                'isValid' => true,
                []
            ],
            [
                'validationSubject' => [
                    'response' => [
                        PaymentField::PAYMENT_ID_FIELD_NAME => 'testPaymentId',
                        PaymentField::STATUS_FIELD_NAME => 'NEW',
                        PaymentField::EXTERNAL_ID_FIELD_NAME => 'testExternalId'
                    ]
                ],
                'isValid' => false,
                [
                    __('Error occurred during the payment process.')
                ]
            ]
        ];
    }
}
