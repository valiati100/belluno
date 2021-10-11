<?php

declare(strict_types=1);

namespace Belluno\Magento2\Gateway\Request;

use Belluno\Magento2\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;

class BankSlipCartRequest implements BuilderInterface {

  /** Cart Block */
  const CART = 'cart';

  /** Product Name */
  const PRODUCT_NAME = 'product_name';

  /** Quantity */
  const QUANTITY = 'quantity';

  /** Unit Value */
  const UNIT_VALUE = 'unit_value';

  public function __construct(
    SubjectReader $subjectReader
  ) {
    $this->_subjectReader = $subjectReader;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $buildSubject) {
    $quote = $this->_subjectReader->getQuote();
    $items = $quote->getAllItems();
    $paymentDO = $this->_subjectReader->readPayment($buildSubject);
    $order = $paymentDO->getOrder();
    $total = $order->getGrandTotalAmount();
    $subTotal = $quote->getSubtotal();
    $shippingValue = $total - $subTotal;

    $result = [];
    $array = [];

    foreach ($items as $item) {
      if ($item->getProductType() != 'configurable') {
        if ($item->getPrice() == 0) {
          $parentItem = $item->getParentItem();
          $price = $parentItem->getPrice();
        } else {
          $price = $item->getPrice();
        }
        $array[] = [
          self::PRODUCT_NAME => $item->getName(),
          self::QUANTITY => $item->getQty(),
          self::UNIT_VALUE => $price
        ];
      }
    }
    if ($shippingValue != 0) {
      $array[] = [
        self::PRODUCT_NAME => 'Shipping',
        self::QUANTITY => '1',
        self::UNIT_VALUE => $shippingValue
      ];
    }

    $result = [
      'bankslip' => [
        self::CART => $array
      ]
    ];

    return $result;
  }
}
