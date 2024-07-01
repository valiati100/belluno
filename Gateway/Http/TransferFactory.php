<?php

declare(strict_types=1);

namespace Belluno\Magento2\Gateway\Http;

use Magento\Payment\Gateway\Http\TransferFactoryInterface;
use Magento\Payment\Gateway\Http\TransferBuilder;

class TransferFactory implements TransferFactoryInterface
{
    /** @var TransferBuilder */
    private $_transferBuilder;

    /**
     * Transfer factory constructor
     * @param TransferBuilder $transferBuilder
     */
    public function __construct(TransferBuilder $transferBuilder)
    {
        $this->_transferBuilder = $transferBuilder;
    }

    /**
     * Builds gateway transfer object
     * @param array $request
     * @return TransferInterface
     */
    public function create(array $request)
    {
        return $this->_transferBuilder
            ->setBody($request)
            ->setMethod("POST")
            ->build();
    }
}