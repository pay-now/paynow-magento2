<?php

namespace Paynow\PaymentGateway\Gateway\Validator\Payment;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Paynow\Model\Payment\Status;
use Paynow\PaymentGateway\Helper\PaymentField;
use Paynow\PaymentGateway\Model\Logger\Logger;
use Paynow\PaymentGateway\Observer\PaymentDataAssignObserver;

/**
 * Class PaymentAuthorizationValidator
 *
 * @package Paynow\PaymentGateway\Validator\Payment
 */
class AuthorizationValidator extends AbstractValidator
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * AuthorizationValidator constructor.
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
    public function validate(array $validationSubject): ResultInterface
    {
        $response = SubjectReader::readResponse($validationSubject);
        $payment = SubjectReader::readPayment($validationSubject);

        $this->logger->debug("Validating authorization response", ['response' => $response]);

        $isWhiteLabelPayment = $payment->getPayment()->hasAdditionalInformation(PaymentDataAssignObserver::BLIK_CODE)
            && ! empty($payment->getPayment()->getAdditionalInformation(PaymentDataAssignObserver::BLIK_CODE));

        $isResponseValid = array_key_exists(PaymentField::PAYMENT_ID_FIELD_NAME, $response) &&
            array_key_exists(PaymentField::STATUS_FIELD_NAME, $response) &&
            in_array($response[PaymentField::STATUS_FIELD_NAME], [
                Status::STATUS_NEW,
                Status::STATUS_PENDING
        ]);

        if (! $isWhiteLabelPayment) {
            $isResponseValid=  $isResponseValid &&
                array_key_exists(PaymentField::REDIRECT_URL_FIELD_NAME, $response) &&
                ! empty($response[PaymentField::REDIRECT_URL_FIELD_NAME]);
        }

        $errorCodes = [];
        if (isset($response['errors'][0])) {
            $errorCodes[] = $response['errors'][0]->getType();
        }

        $this->logger->debug("Validating authorization response", [
            'valid' => $isResponseValid,
            'whiteLabel' => $isWhiteLabelPayment,
            'errorCodes' => $errorCodes
        ]);

        return $this->createResult(
            $isResponseValid,
            $isResponseValid ? [] : [__('Error occurred during the payment process.')],
            $errorCodes
        );
    }
}
