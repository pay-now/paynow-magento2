<?php

namespace Paynow\PaymentGateway\Test\Unit\Gateway\Validator\Payment;

use Magento\Framework\Phrase;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Validator\Result;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Magento\Sales\Model\Order\Payment;
use Paynow\Exception\Error;
use Paynow\PaymentGateway\Gateway\Validator\Payment\AuthorizationValidator;
use Paynow\PaymentGateway\Helper\PaymentField;
use Paynow\PaymentGateway\Observer\PaymentDataAssignObserver;
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
    protected function setUp(): void
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
    public function testValidate(array $validationSubject, bool $isValid, array $messages, $messageCodes)
    {
        /** @var ResultInterface|MockObject $result */
        $result = new Result($isValid, $messages);

        $this->resultFactory->method('create')
            ->with(
                [
                    'isValid' => $isValid,
                    'failsDescription' => $messages,
                    'errorCodes' => $messageCodes
                ]
            )
            ->willReturn($result);

        $actual = $this->authorizationValidator->validate($validationSubject);

        self::assertEquals($result, $actual);
    }

    /**
     * @return array
     */
    public function dataProviderTestValidate(): array
    {
        $error = $this->getMockBuilder(Error::class)
            ->disableOriginalConstructor()
            ->getMock();

        $error
            ->expects($this->any())
            ->method('getType')
            ->willReturn('WRONG_BLIK_CODE');

        return [
            [
                'validationSubject' => [
                    'response' => [
                        PaymentField::PAYMENT_ID_FIELD_NAME => 'testPaymentId',
                        PaymentField::STATUS_FIELD_NAME => 'NEW',
                        PaymentField::REDIRECT_URL_FIELD_NAME => 'testRedirectUrl'
                    ],
                    'payment' => $this->getPaymentDataObject('')
                ],
                'isValid' => true,
                [],
                []
            ],
            [
                'validationSubject' => [
                    'response' => [
                        PaymentField::PAYMENT_ID_FIELD_NAME => 'testPaymentId',
                        PaymentField::STATUS_FIELD_NAME => 'NEW'
                    ],
                    'payment' => $this->getPaymentDataObject('111111')
                ],
                'isValid' => true,
                [],
                []
            ],
            [
                'validationSubject' => [
                    'response' => [
                        PaymentField::PAYMENT_ID_FIELD_NAME => 'testPaymentId',
                        PaymentField::STATUS_FIELD_NAME => 'NEW'
                    ],
                    'payment' => $this->getPaymentDataObject('')
                ],
                'isValid' => false,
                [
                    __('Error occurred during the payment process.')
                ],
                []
            ],
            [
                'validationSubject' => [
                    'response' => [
                        'errors' => [$error]
                    ],
                    'payment' => $this->getPaymentDataObject('123123')
                ],
                'isValid' => false,
                [
                    __('Error occurred during the payment process.')
                ],
                ['WRONG_BLIK_CODE']
            ]
        ];
    }

    private function getPaymentDataObject($blikCode)
    {
        $paymentInfo = $this->getMockBuilder(Payment::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentInfo
            ->expects($this->any())
            ->method('getAdditionalInformation')
            ->willReturn([PaymentDataAssignObserver::BLIK_CODE => $blikCode]);

        $paymentInfo
            ->expects($this->any())
            ->method('hasAdditionalInformation')
            ->with(PaymentDataAssignObserver::BLIK_CODE)
            ->willReturn(boolval($blikCode));

        $paymentDO = $this->getMockBuilder(PaymentDataObjectInterface::class)
            ->disableOriginalConstructor()
            ->getMock();

        $paymentDO
            ->expects($this->any())
            ->method('getPayment')
            ->willReturn($paymentInfo);

        return $paymentDO;
    }
}
