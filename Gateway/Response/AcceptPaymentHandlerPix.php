<?php

declare(strict_types=1);

namespace Belluno\Magento2\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Belluno\Magento2\Gateway\Helper\SubjectReader;

class AcceptPaymentHandlerPix implements HandlerInterface
{
    /** @const id of pix */
    const ID = "transaction_id";

    /** @const document code of pix */
    const LINK_CODE = "link_code";

    /** @const base64_text */
    const BASE64_TEXT = "base64_text";

    /** @const base64_image */
    const BASE64_IMAGE = "base64_image";

    /** @const expires_at */
    const EXPIRES_AT = "expires_at";

    /** @const link payment */
    const LINK_PAYMENT = "link";

    /** @const status of pix */
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
            throw new InvalidArgumentException(__("Payment data object should be provided"));
        }

        $paymentDO = $this->_subjectReader->readPayment($handlingSubject);
        $payment = $paymentDO->getPayment();

        $pix = [
            self::ID => $response["transaction"][self::ID],
            self::LINK_PAYMENT => $response["transaction"][self::LINK_PAYMENT],
            self::LINK_CODE => $response["transaction"][self::LINK_CODE],
            self::BASE64_TEXT => $response["transaction"]["pix"][self::BASE64_TEXT],
            self::BASE64_IMAGE => $response["transaction"]["pix"][self::BASE64_IMAGE],
            self::EXPIRES_AT => $response["transaction"]["pix"][self::EXPIRES_AT],
            self::STATUS => $response["transaction"][self::STATUS],
        ];

        $payment->setAdditionalInformation("pix", $pix);
    }
}