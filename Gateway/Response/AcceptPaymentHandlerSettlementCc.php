<?php

declare(strict_types=1);

namespace Belluno\Magento2\Gateway\Response;

use Magento\Payment\Gateway\Data\PaymentDataObjectInterface;
use Magento\Payment\Gateway\Response\HandlerInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use InvalidArgumentException;
use Psr\Log\LoggerInterface;
use Belluno\Magento2\Gateway\Helper\SubjectReader;

class AcceptPaymentHandlerSettlementCc implements HandlerInterface
{
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

        $this->logger->notice(json_encode($response));

        $status = "";
        if(isset($response["transaction"]["status"])) {
            $status = $response["transaction"]["status"];
        }

        if (
            $status == "Paid" ||
            $status == "Client Manual Analysis" ||
            $status == "Manual Analysis"
        ) {
            $paymentDO = $this->_subjectReader->readPayment($handlingSubject);
            $payment = $paymentDO->getPayment();
            $order = $paymentDO->getOrder();

            $transactionId = "";
            if(isset($response["transaction"]["transaction_id"])) {
                $transactionId = $response["transaction"]["transaction_id"];
            }
			
            $value = "";
            if(isset($response["transaction"]["value"])) {
                $value = $response["transaction"]["value"];
            }
			
            $orderId = "";
            if(isset($response["transaction"]["details"])) {
                $orderId = $response["transaction"]["details"];
            }

            $transaction = [];
            $transaction = [
                "response_json" => json_encode($response),
                "id_transaction" => $transactionId,
                "value" => $value,
                "id_order" => $orderId,
            ];

            $payment->setAdditionalInformation(
                "transaction_data",
                $transaction
            );

            /** @var Payment $payment */
            $payment->setTransactionId($order->getOrderIncrementId());
			
			if($status == "Paid") {
				$payment->setIsTransactionClosed(true);
				$payment->setShouldCloseParentTransaction(true);
			}else {
				 // do not close transaction so you can do a cancel() and void
				$payment->setIsTransactionClosed(false);
				$payment->setShouldCloseParentTransaction(false);
			}
			
        }else {
            throw new CouldNotSaveException(
                __("An error occurred with the capture.")
            );
        }
    }
}
