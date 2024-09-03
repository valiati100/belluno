<?php

declare(strict_types=1);

namespace Belluno\Magento2\Helper;

use Belluno\Magento2\Gateway\Config\ConfigCc;

class Order
{
	protected $logger;
	protected $helper;
	protected $invoice;
	protected $orderInterface;
	protected $invoiceSender;
	protected $resourceConnection;
	
	public function __construct(
		\Belluno\Magento2\Block\Invoice\Invoice $invoice,
		\Belluno\Magento2\Helper\Helper $helper,
		\Magento\Sales\Api\OrderManagementInterface $orderInterface,
		\Magento\Sales\Model\Order\Email\Sender\InvoiceSender $invoiceSender,
		\Magento\Framework\App\ResourceConnection $resourceConnection
    ) {
		$this->invoice = $invoice;
		$this->helper = $helper;
		$this->orderInterface = $orderInterface;
		$this->invoiceSender = $invoiceSender;
		$this->resourceConnection = $resourceConnection;
		$this->logger = $this->helper->getLog('belluno_order_status');
    }
	
	public function updateStatus($order, $date) {
		try {
			$order->setBellunoStatus($date);
			$qty = (int)$order->getBellunoStatusQty() + 1;
			$order->setBellunoStatusQty($qty);
			$order->save();
		}catch (\Exception $e) {
			$this->logger->info($e->getMessage() . ' - Order(3) ' . $order->getIncrementId());
		}
	}
	
	public function createInvoice($order, $status, $msg) {
		try {	
			$this->invoice->generateInvoice($order->getId());
			$order->setState($status)->setStatus($status);
			$order->save();
			$this->logger->info('Invoice generation: '. $msg);
		}catch (\Exception $e) {
			$this->logger->info($e->getMessage() . ' - Order(2) ' . $order->getIncrementId());
		}
	}
	
	public function changeStatus($order, $status, $msg) {
		try {
			$order->setState($status)->setStatus($status);
			$order->save();
			$this->logger->info('Changing status to '.$status.': ' . $msg);
		}catch (\Exception $e) {
			$this->logger->info($e->getMessage() . ' - Order ' . $order->getIncrementId());
		}
	}
	
	public function sendInvoiceEmail($order) {
		try {
			$payment = $order->getPayment();
			if($payment->getMethod() == ConfigCc::METHOD) {
				$invoice = $order->getInvoiceCollection()->getFirstItem();
				$this->invoiceSender->send($invoice);
			}
		}catch (\Exception $e) {
			$this->logger->info($e->getMessage());
		}
	}
	
	public function cancelOrder($order, $msg) {
		try {
			$this->orderInterface->cancel($order->getId());
			$this->changeStatus($order, 'canceled', $msg);
			$this->logger->info('Order canceled: ' . $msg);
		}catch (\Exception $e) {
			$this->logger->info('Canceling error: '. $e->getMessage() . ' - ' . $order->getIncrementId());
		}
	}
	
	public function cancelOrderWithInvoice($order, $msg='') {
		$orderId = $order->getId();
		$connection = $this->resourceConnection->getConnection();
		try {
			$tableName = $this->resourceConnection->getTableName('sales_order_item');
			$condition = ["order_id = ?" => $orderId];
			$data = ['qty_invoiced' => 0];
			$connection->update($tableName, $data, $condition);
			$this->logger->info("Column 'qty_invoiced' updated to 0 for order $orderId");
		} catch (\Exception $e) {
			$this->logger->info("Error updating column 'qty_invoiced': " . $e->getMessage() . " - order $orderId");
		}

		try {
			$this->orderInterface->cancel($orderId);
			$this->changeStatus($order, 'canceled', $msg);
			$this->logger->info("Order canceled(force) $orderId");
		}catch (\Exception $e) {
			$this->logger->info("Error canceling order $orderId: " . $e->getMessage() . " - order $orderId");
		}
	}
}