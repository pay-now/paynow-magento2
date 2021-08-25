<?php

namespace Paynow\PaymentGateway\Gateway\Validator\Payment;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Paynow\Model\Payment\Status;
use Paynow\PaymentGateway\Helper\PaymentField;
use Paynow\PaymentGateway\Model\Logger\Logger;

/**
 * Class PaymentCaptureValidator
 *
 * @package Paynow\PaymentGateway\Validator\Payment
 */
class CaptureValidator extends AbstractValidator
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * CaptureValidator constructor.
     * @param ResultInterfaceFactory $resultFactory
     * @param Logger $logger
     */
    public function __construct(ResultInterfaceFactory $resultFactory, Logger $logger)
    {
        parent::__construct($resultFactory);
        $this->logger = $logger;
    }

    /**
     * @param array $validationSubject
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        $response = SubjectReader::readResponse($validationSubject);
        $isResponseValid = array_key_exists(PaymentField::PAYMENT_ID_FIELD_NAME, $response) &&
            array_key_exists(PaymentField::STATUS_FIELD_NAME, $response) &&
            $response[PaymentField::STATUS_FIELD_NAME] === Status::STATUS_CONFIRMED;

        $this->logger->debug(
            "Validating capture response",
            [
                'valid' => $isResponseValid,
                'paymentId' => $response[PaymentField::PAYMENT_ID_FIELD_NAME],
                'status' => $response[PaymentField::STATUS_FIELD_NAME]
            ]
        );

        return $this->createResult(
            $isResponseValid,
            $isResponseValid ? [] : [__('Error occurred during the capture process.')]
        );
    }
}
