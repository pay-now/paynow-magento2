<?php

namespace Paynow\PaymentGateway\Gateway\Validator\Refund;

use Magento\Payment\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Validator\AbstractValidator;
use Magento\Payment\Gateway\Validator\ResultInterface;
use Magento\Payment\Gateway\Validator\ResultInterfaceFactory;
use Paynow\Model\Payment\Status;
use Paynow\PaymentGateway\Helper\RefundField;
use Paynow\PaymentGateway\Model\Logger\Logger;

/**
 * Class PaymentRefundValidator
 *
 * @package Paynow\PaymentGateway\Validator\Refund
 */
class RefundValidator extends AbstractValidator
{
    /**
     * @var Logger
     */
    private $logger;

    /**
     * RefundValidator constructor.
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
        $isResponseValid = array_key_exists(RefundField::REFUND_ID_FIELD_NAME, $response) &&
                           array_key_exists(RefundField::STATUS_FIELD_NAME, $response) &&
                            $response[RefundField::STATUS_FIELD_NAME] === Status::STATUS_PENDING;

        $this->logger->debug("Validating refund response", ['valid' => $isResponseValid]);

        return $this->createResult(
            $isResponseValid,
            $isResponseValid ? [] : [__('Error occurred during the refund process.')]
        );
    }
}
