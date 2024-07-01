<?php

declare(strict_types=1);

namespace Belluno\Magento2\Block\Order;

use Magento\Framework\View\Element\Template;
use Magento\Sales\Api\OrderRepositoryInterface;

class Info extends Template
{
    /** @var OrderRepositoryInterface */
    protected $_orderRepositoryInterface;

    public function __construct(
        OrderRepositoryInterface $orderRepositoryInterface,
        Template\Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_orderRepositoryInterface = $orderRepositoryInterface;
    }

    public function getPaymentMethod()
    {
        $order_id = $this->getRequest()->getParam("order_id");
        $order = $this->_orderRepositoryInterface->get($order_id);
        $payment = $order->getPayment();
        return $payment->getMethod();
    }

    public function getPaymentInfo()
    {
        $order_id = $this->getRequest()->getParam("order_id");
        $order = $this->_orderRepositoryInterface->get($order_id);
        if($payment = $order->getPayment()) {
            $paymentMethod = $payment->getMethod();
            
			if($paymentMethod == 'bellunolink') {
				$data = $payment->getAdditionalInformation();
				return [
					'url' => isset($data['bellunolink']) ? $data['bellunolink'] : false,
					'text' => __('Click here to open the payment link.'),
				];
			}
			elseif($paymentMethod == "bellunobankslip") {
                $data = $payment->getAdditionalInformation();
				if(isset($data["bankslip"]["url"]) && isset($data["bankslip"]["digitable_line"])) {
					return [
						"url" => $data["bankslip"]["url"],
						"digitable_line" => $data["bankslip"]["digitable_line"],
						"text" => __("Click here to view your billet."),
					];
				}
            }
			elseif ($paymentMethod == "bellunopix") {
                $data = $payment->getAdditionalInformation();
				if(isset($data["pix"]["base64_image"]) && isset($data["pix"]["base64_text"])) {
					return [
						"base64_image" => $data["pix"]["base64_image"],
						"base64_text" => $data["pix"]["base64_text"],
						"text" => __("Click here to view payment information."),
					];
				}
            }
			else {
                $data = $payment->getAdditionalInformation();
                $method = isset($data["method_title"]) ? $data["method_title"] : " ";
                $cardName = isset($data["card_name"]) ? $data["card_name"] : " ";
                $cardDocument = isset($data["cardholder_document"]) ? $data["cardholder_document"] : " ";
                $cardPhone = isset($data["cardholder_cellphone"]) ? $data["cardholder_cellphone"] : " ";
                $finalCreditCard = isset($data["card_number"]) ? $data["card_number"] : " ";
                $intallments = isset($data["cc_installments"]) ? $data["cc_installments"] : " ";
                $valueTotal = isset($data["transaction_data"]["value"]) ? $data["transaction_data"]["value"] : " ";

                return [
                    "Method Payment" => $method,
                    "Card Holder Name" => $cardName,
                    "Card Holder Document" => $cardDocument,
                    "Card Holder Phone" => $cardPhone,
                    "Final Digits Credit Card" => $finalCreditCard,
                    "Number of Installments" => $intallments,
                    "Total Paid" => $valueTotal,
                ];
            }
        }
		
		return false;
    }
}