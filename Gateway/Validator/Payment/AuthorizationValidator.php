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
 * Class PaymentAuthorizationValidator
 *
 * @package Paynow\PaymentGateway\Validator
 */
class AuthorizationValidator extends AbstractValidator
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * AuthorizationValidator constructor.
     *
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
     *
     * @return ResultInterface
     */
    public function validate(array $validationSubject)
    {
        $response        = SubjectReader::readResponse($validationSubject);
        $isResponseValid = array_key_exists(PaymentField::PAYMENT_ID_FIELD_NAME, $response) &&
                           array_key_exists(PaymentField::REDIRECT_URL_FIELD_NAME, $response) &&
                           array_key_exists(PaymentField::STATUS_FIELD_NAME, $response) &&
                           $response[PaymentField::STATUS_FIELD_NAME] === Status::STATUS_NEW;

        $this->logger->debug("Validating authorization response", ['valid' => $isResponseValid]);

        return $this->createResult(
            $isResponseValid,
            $isResponseValid ? [] : [__('Error occurred during the payment process.')]
        );
    }
}
