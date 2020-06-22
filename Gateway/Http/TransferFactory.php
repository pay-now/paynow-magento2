<?php

namespace Paynow\PaymentGateway\Gateway\Http;

use Magento\Payment\Gateway\Http\TransferBuilder;
use Magento\Payment\Gateway\Http\TransferFactoryInterface;

/**
 * Class TransferFactory
 *
 * @package Paynow\PaymentGateway\Gateway\Http
 */
class TransferFactory implements TransferFactoryInterface
{
    /**
     * @var TransferBuilder
     */
    private $transferBuilder;

    /**
     * @param TransferBuilder $transferBuilder
     */
    public function __construct(TransferBuilder $transferBuilder)
    {
        $this->transferBuilder = $transferBuilder;
    }

    public function create(array $request)
    {
        if (!empty($request['headers'])) {
            $this->transferBuilder->setHeaders($request['headers']);
        }

        return $this->transferBuilder
            ->setBody($request['body'])
            ->build();
    }
}
