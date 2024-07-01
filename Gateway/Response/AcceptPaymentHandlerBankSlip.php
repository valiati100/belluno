<?php

declare(strict_types=1);

namespace Belluno\Magento2\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Belluno\Magento2\Gateway\Helper\SubjectReader;

class AcceptPaymentHandlerBankSlip implements HandlerInterface
{
    /** @const id of bank slip */
    const ID = "id";

    /** @const document code of bank slip */
    const DOCUMENT_CODE = "document_code";

    /** @const digitable line of bank slip */
    const DIGITABLE_LINE = "digitable_line";

    /** @const url of bank slip */
    const URL = "url";

    /** @const status of bank slip */
    const STATUS = "status";

    /** @var SubjectReader */
    private $_subjectReader;

    /** @var LoggerInterface */
    protected $logger;

    public function __construct(
        SubjectReader $subjectReader,
        LoggerInterface $logger
    ) {
        $this->_subjectReader = $subjectReader;
        $this->logger = $logger;
    }

    public function handle(array $handlingSubject, array $response)
    {
        if(!isset($handlingSubject["payment"]) || !$handlingSubject["payment"] instanceof PaymentDataObjectInterface) {
            throw new InvalidArgumentException(
                __("Payment data object should be provided")
            );
        }

        $paymentDO = $this->_subjectReader->readPayment($handlingSubject);
        $payment = $paymentDO->getPayment();

        $bankslip = [
            self::ID => $response["bankslip"][self::ID],
            self::URL => $response["bankslip"][self::URL],
            self::DOCUMENT_CODE => $response["bankslip"][self::DOCUMENT_CODE],
            self::DIGITABLE_LINE => $response["bankslip"][self::DIGITABLE_LINE],
            self::STATUS => $response["bankslip"][self::STATUS],
        ];

        $payment->setAdditionalInformation("bankslip", $bankslip);
    }
}