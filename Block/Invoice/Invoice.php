<?php

declare(strict_types=1);

namespace Belluno\Magento2\Block\Invoice;

use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Service\InvoiceService;
use Magento\Sales\Model\Order\Email\Sender\InvoiceSender;
use Magento\Framework\DB\TransactionFactory;
use Magento\Framework\Message\ManagerInterface;
use Magento\Framework\Exception\LocalizedException;
use Magento\Sales\Model\Order\Invoice as OrderInvoice;
use Exception;

class Invoice extends Template {

  /** @var OrderRepositoryInterface */
  private $_orderRepositoty;

  /** @var InvoiceService */
  private $_invoiceService;

  /** @var InvoiceSender */
  private $_invoiceSender;

  /** @var ManagerInterface */
  private $_messageManager;

  /** @var TransactionFactory */
  private $_transactionFactory;

  public function __construct(
    OrderRepositoryInterface $orderRepository,
    InvoiceService $invoiceService,
    InvoiceSender $invoiceSender,
    ManagerInterface $messageManager,
    TransactionFactory $transactionFactory,
    Context $context,
    array $data = []
  ) {
    $this->_orderRepositoty = $orderRepository;
    $this->_invoiceService = $invoiceService;
    $this->_invoiceSender = $invoiceSender;
    $this->_messageManager = $messageManager;
    $this->_transactionFactory = $transactionFactory;
    parent::__construct($context, $data);
  }

  /**
   * Function to create Invoice based on order object
   * @param $orderId
   */
  public function generateInvoice($orderId) {
    try {
      $order = $this->_orderRepositoty->get($orderId);

      //validations order
      if (!$order->getEntityId()) {
        throw new LocalizedException(__('The order no longer exists.'));
      }

      $invoice = $this->_invoiceService->prepareInvoice($order);

      //validation invoice
      if (!$invoice) {
        throw new LocalizedException(__('We can\'t save the invoice right now.'));
      }
      if (!$invoice->getTotalQty()) {
        throw new LocalizedException(__('You can\'t create an invoice without products.'));
      }

      $invoice->setRequestedCaptureCase(OrderInvoice::CAPTURE_OFFLINE);
      $invoice->register();
      $invoice->getOrder()->setCustomerNoteNotify(false);
      $invoice->getOrder()->setIsInProcess(true);
      $transactionSave = $this->_transactionFactory->create()->addObject($invoice)->addObject($invoice->getOrder());
      $transactionSave->save();

      //send invoice email
      try {
        $this->_invoiceSender->send($invoice);
      } catch (Exception $e) {
        $this->_messageManager->addErrorMessage(__('We can\'t send the invoice email right now.'));
      }
    } catch (Exception $e) {
      $this->_messageManager->addErrorMessage($e->getMessage());
    }
  }
}
