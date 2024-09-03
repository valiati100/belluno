<?php

namespace Belluno\Magento2\Plugin;

use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Sales\Model\Order\Invoice;
use Belluno\Magento2\Gateway\Config\ConfigCc;

class InvoiceEmailHandler
{
    public function aroundSend(
        InvoiceSender $subject,
        callable $proceed,
        Invoice $invoice,
        $forceSyncMode = false
    ) {
        $order = $invoice->getOrder();
        $payment = $order->getPayment();
		if($payment->getMethod() == ConfigCc::METHOD) {
			$additionalInformation = $payment->getAdditionalInformation();
			$additionalInformation = json_decode($additionalInformation["transaction_data"]["response_json"], true);
			$status = $additionalInformation["transaction"]["status"];
			if($status == "Paid") {
				return $proceed($invoice, $forceSyncMode);
			}
			
			return false;
		}

        return $proceed($invoice, $forceSyncMode);
    }
}