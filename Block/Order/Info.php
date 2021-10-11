<?php

declare(strict_types=1);

namespace Belluno\Magento2\Block\Order;

use Magento\Framework\View\Element\Template;
use Magento\Sales\Api\OrderRepositoryInterface;

class Info extends Template {

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

  public function getPaymentMethod() {
    $order_id = $this->getRequest()->getParam('order_id');
    $order = $this->_orderRepositoryInterface->get($order_id);
    $payment = $order->getPayment();
    return $payment->getMethod();
  }

  public function getPaymentInfo() {
    $order_id = $this->getRequest()->getParam('order_id');
    $order = $this->_orderRepositoryInterface->get($order_id);
    if ($payment = $order->getPayment()) {
      $paymentMethod = $payment->getMethod();
      if ($paymentMethod == 'bellunobankslip') {
        $data = $payment->getAdditionalInformation();
        
        !$data['bankslip']['url'] ?? false;
        !$data['bankslip']['digitable_line'] ?? false;

        return [
          'url' => $data['bankslip']['url'],
          'digitable_line' => $data['bankslip']['digitable_line'],
          'text' => __('Click here to view your billet.'),
        ];
      } else {
        $data = $payment->getAdditionalInformation();
        isset($data['method_title']) ? $method = $data['method_title'] : $method = ' ';
        isset($data['card_name']) ? $cardName = $data['card_name'] : $cardName = ' ';
        isset($data['cardholder_document']) ? $cardDocument = $data['cardholder_document'] : $cardDocument = ' ';
        isset($data['cardholder_cellphone']) ? $cardPhone = $data['cardholder_cellphone'] : $cardPhone = ' ';
        isset($data['card_number']) ? $finalCreditCard = $data['card_number'] : $finalCreditCard = ' ';
        isset($data['cc_installments']) ? $intallments = $data['cc_installments'] : $intallments = ' ';
        isset($data['transaction_data']['value']) ? $valueTotal = $data['transaction_data']['value'] : $valueTotal = ' ';

        return [
          'Method Payment' => $method,
          'Card Holder Name' => $cardName,
          'Card Holder Document' => $cardDocument,
          'Card Holder Phone' => $cardPhone,
          'Final Digits Credit Card' => $finalCreditCard,
          'Number of Installments' => $intallments,
          'Total Paid' => $valueTotal
        ];
      }
    } else {
      return false;
    }
  }
}
