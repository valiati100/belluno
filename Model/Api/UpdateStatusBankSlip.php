<?php

namespace Belluno\Magento2\Model\Api;

use Belluno\Magento2\Api\UpdateStatusBankSlipInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Belluno\Magento2\Block\Invoice\Invoice;
use Magento\Framework\Webapi\Rest\Request;

class UpdateStatusBankSlip implements UpdateStatusBankSlipInterface
{

  /** @var OrderRepositoryInterface */
  private $_orderRepository;

  /** @var OrderFactory */
  private $_orderFactory;

  /** @var Invoice */
  private $_invoice;

  /** @var Request */
  protected $_request;

  public function __construct(
    OrderRepositoryInterface $orderRepository,
    Invoice $invoice,
    OrderFactory $orderFactory,
    Request $request
  ) {
    $this->_orderFactory = $orderFactory;
    $this->_orderRepository = $orderRepository;
    $this->_invoice = $invoice;
    $this->_request = $request;
  }

  /**
   * Postback Belluno
   * @return string
   */
  public function doUpdate()
  {
    $data = $this->_request->getBodyParams();

    try {
      $orderId = $data['bankslip']['document_code'];
      $status = $data['bankslip']['status'];
    } catch (\Throwable $th) {
      $orderId = $data['transaction']['details'];
      $status = $data['transaction']['status'];
    }

    if ($status == 'Paid') {
      $order = $this->_orderFactory->create()->loadByIncrementId($orderId);
      if (isset($order)) {
        $order->setState(Order::STATE_PROCESSING);
        $order->setStatus(Order::STATE_PROCESSING);
        $this->_orderRepository->save($order);

        $totalInvoiced = $order->getTotalInvoiced();
        if (empty($totalInvoiced)) {
          $this->_invoice->generateInvoice($order->getId());
        }
      } else {
        throw new \Magento\Framework\Webapi\Exception(__("Not found order"), 0, \Magento\Framework\Webapi\Exception::HTTP_BAD_REQUEST);
      }
    }

    if (isset($data['transaction']['refunds']['0']['status'])) {
      if (!empty($data['transaction']['refunds']['0']['amount'])) {
        $amount = $data['transaction']['refunds']['0']['amount'];
        if ($amount != '0') {
          $order = $this->_orderFactory->create()->loadByIncrementId($orderId);
          $order->setState(Order::STATE_CANCELED);
          $order->setStatus(Order::STATE_CANCELED);
          $this->_orderRepository->save($order);
        }
      }
    }
  }
}
