<?php

namespace Belluno\Magento2\Gateway\Command;

use Magento\Payment\Gateway\CommandInterface;
use Magento\Payment\Gateway\Helper\ContextHelper;
use Magento\Sales\Api\Data\OrderPaymentInterface;
use Magento\Payment\Gateway\Command\CommandPoolInterface;
use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Helper\SubjectReader;

class CaptureStrategyCommand implements CommandInterface
{
    const SALE = "sale";

    const CAPTURE = "settlement";

    private $subjectReader;

    public function __construct(
        SubjectReader $subjectReader,
        CommandPoolInterface $commandPool
    ) {
        $this->commandPool = $commandPool;
        $this->subjectReader = $subjectReader;
    }

    public function execute(array $commandSubject)
    {
        /** @var PaymentDataObjectInterface $paymentDO */
        $paymentDO = $this->subjectReader->readPayment($commandSubject);

        /** @var OrderPaymentInterface $paymentInfo */
        $paymentInfo = $paymentDO->getPayment();
        ContextHelper::assertOrderPayment($paymentInfo);

        $command = $this->getCommand($paymentInfo);
        $this->commandPool->get($command)->execute($commandSubject);
    }

    private function getCommand(OrderPaymentInterface $payment)
    {
        // if not exists authorization transaction
        if(!$payment->getAuthorizationTransaction()) {
            // make auth transaction with autoCapture = 1
            return self::SALE;
        }else {
			// make capture transaction only
            return self::CAPTURE;
        }
    }
}