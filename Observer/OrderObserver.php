<?php

declare(strict_types=1);

namespace Belluno\Magento2\Observer;

use Magento\Framework\Event\ObserverInterface;
use Magento\Framework\Event\Observer;
use Magento\Sales\Model\Order;
use Belluno\Magento2\Gateway\Config\ConfigCc;
use Belluno\Magento2\Gateway\Config\ConfigBankSlip;
use Belluno\Magento2\Gateway\Config\ConfigPix;

class OrderObserver implements ObserverInterface
{
    public function execute(Observer $observer)
    {
        /** @var Order */
        $order = $observer->getEvent()->getOrder();
        $payment = $order->getPayment();
        $additionalInformation = $payment->getAdditionalInformation();

        if($payment->getMethod() == ConfigCc::METHOD) {
            $additionalInformation = json_decode($additionalInformation["transaction_data"]["response_json"], true);
            $status = $additionalInformation["transaction"]["status"];
			if($status == "Client Manual Analysis" || $status == "Manual Analysis") {
				$order->setState(Order::STATE_PAYMENT_REVIEW)->setStatus(Order::STATE_PAYMENT_REVIEW);
				$order->save();
			}
			elseif($status != "Paid") {
                $order->setState("pending")->setStatus("pending");
                $order->save();
            }
        }
		elseif($payment->getMethod() == ConfigBankSlip::METHOD) {
            $status = $additionalInformation["bankslip"]["status"];
            if($status != "Paid") {
                $order->setState("pending")->setStatus("pending");
                $order->save();
            }
        }
		elseif($payment->getMethod() == ConfigPix::METHOD) {
            $status = $additionalInformation["pix"]["status"];
            if($status != "Paid") {
                $order->setState("pending")->setStatus("pending");
                $order->save();
            }
        }
    }
}