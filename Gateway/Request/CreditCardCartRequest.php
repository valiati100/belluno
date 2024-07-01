<?php

declare(strict_types=1);

namespace Belluno\Magento2\Gateway\Request;

use Belluno\Magento2\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;

class CreditCardCartRequest implements BuilderInterface
{
    /** Cart Block */
    const CART = "cart";

    /** Product Name */
    const PRODUCT_NAME = "product_name";

    /** Quantity */
    const QUANTITY = "quantity";

    /** Unit Value */
    const UNIT_VALUE = "unit_value";

    /** @var ScopeConfigInterface */
    private $_scopeConfig;

    public function __construct(
        SubjectReader $subjectReader,
        ScopeConfigInterface $scopeConfig
    ) {
        $this->_subjectReader = $subjectReader;
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * {@inheritdoc}
     */
    public function build(array $buildSubject)
    {
        $quote = $this->_subjectReader->getQuote();
        $items = $quote->getAllItems();
        $paymentDO = $this->_subjectReader->readPayment($buildSubject);
        $order = $paymentDO->getOrder();
        $total = $order->getGrandTotalAmount();
        $payment = $paymentDO->getPayment();
        $additionalInformation = $payment->getAdditionalInformation();
        $subTotal = $quote->getSubtotal();
        $shippingValue = $total - $subTotal;

        $result = [];
        $array = [];

        foreach($items as $item) {
            if($item->getProductType() != "configurable") {
                if($item->getPrice() == 0) {
                    $parentItem = $item->getParentItem();
                    $price = $parentItem->getPrice();
                }else {
                    $price = $item->getPrice();
                }
                $array[] = [
                    self::PRODUCT_NAME => $item->getName(),
                    self::QUANTITY => $item->getQty(),
                    self::UNIT_VALUE => $price,
                ];
            }
        }
		
        if($shippingValue != 0) {
            $array[] = [
                self::PRODUCT_NAME => "Shipping",
                self::QUANTITY => "1",
                self::UNIT_VALUE => $shippingValue,
            ];
        }

        $dataValue = $this->getValueWithInterest($total, $additionalInformation["cc_installments"]);
        if($dataValue["valueInterest"] != 0) {
            $array[] = [
                self::PRODUCT_NAME => "Interest",
                self::QUANTITY => "1",
                self::UNIT_VALUE => $dataValue["valueInterest"],
            ];
        }

        $result = [
            "transaction" => [
                self::CART => $array,
            ],
        ];

        return $result;
    }

    /**
     * Function to get total value with interest
     * @param string $totalValue
     * @param string $installmentNumber
     * @return array
     */
    public function getValueWithInterest($totalValue, $installmentNumber): array
    {
        $interest = $this->_scopeConfig->getValue(
            "payment/belluno_config/bellunopayment/installments",
            \Magento\Store\Model\ScopeInterface::SCOPE_STORE
        );

        if($interest == null) {
            return [
                "total" => $totalValue,
                "valueInterest" => 0,
            ];
        }

        $interest = json_decode($interest, true);
        $valueInterest = 0;
        $interestPercent = 0;

        $i = 1;
        foreach($interest as $value) {
            if($i == $installmentNumber) {
                if($value["from_qty"] > $valueInterest) {
                    $interestPercent = $value["from_qty"];
                }
            }
            $i++;
        }

        $valueInterest = ($interestPercent / 100) * $totalValue;
        $totalValue = $totalValue + ($interestPercent / 100) * $totalValue;

        return [
            "total" => $totalValue,
            "valueInterest" => $valueInterest,
        ];
    }
}