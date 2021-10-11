<?php

declare(strict_types=1);

namespace Belluno\Magento2\Block\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;

class Success extends Template {

  /** @var Session */
  protected $_checkoutSession;

  public function __construct(
    Session $checkoutSession,
    Context $context,
    array $data = []
  ) {
    parent::__construct($context, $data);
    $this->_checkoutSession = $checkoutSession;
  }

  public function getOrder(): Order {
    return $this->_checkoutSession->getLastRealOrder();
  }

  public function getAdditionalInformation() {
    $order = $this->getOrder();
    $payment = $order->getPayment();
    return $payment->getAdditionalInformation();
  }

  public function getBankSlip() {
    $data = $this->getAdditionalInformation();
    if (isset($data['bankslip']['url'])) {
      return $data['bankslip']['url'];
    } else {
      return ' ';
    }
  }

  public function getBankSlipDigitableLine() {
    $data = $this->getAdditionalInformation();
    if (isset($data['bankslip']['digitable_line'])) {
      return $data['bankslip']['digitable_line'];
    } else {
      return ' ';
    }
  }
}
