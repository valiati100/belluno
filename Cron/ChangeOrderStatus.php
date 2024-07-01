<?php
namespace Belluno\Magento2\Cron;

class ChangeOrderStatus
{
	protected $logger;
	protected $orderCollectionFactory;
	protected $helper;
	protected $invoice;
	protected $orderInterface;
	protected $limitTime = 15;
	protected $expirationDays;
	protected $objectManager;
	
	public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
		\Belluno\Magento2\Helper\Helper $helper,
		\Belluno\Magento2\Block\Invoice\Invoice $invoice,
		\Magento\Sales\Api\OrderManagementInterface $orderInterface
    ) {
		$this->orderCollectionFactory = $orderCollectionFactory;
		$this->helper = $helper;
		$this->invoice = $invoice;
		$this->orderInterface = $orderInterface;
		$this->logger = $this->helper->getLog('belluno_order_status');
		
		$this->objectManager = \Magento\Framework\App\ObjectManager::getInstance(); 
		$expirationDays = (int)$this->objectManager->get('\Belluno\Magento2\Gateway\Config\ConfigBankSlip')->getExpirationDays();
		$expirationDays = ($expirationDays * 24 * 60)/$this->limitTime;
		$this->expirationDays = $expirationDays * 2;
    }
	
	public function execute()
	{	
		$now = (new \DateTime())->modify('-3 hours')->format('Y-m-d H:i:s');
		try {
			$orderCollection = $this->orderCollectionFactory->create();
			$orderCollection->addFieldToFilter('status', 'pending');
			$orderCollection->addFieldToFilter('belluno_status_qty', ['lteq' => $this->expirationDays]);
			$orderCollection->getSelect()->where(
				"TIMESTAMPDIFF(MINUTE, belluno_status, '$now') > ".$this->limitTime." OR belluno_status IS NULL"
			);
	
			$orderCollection->getSelect()
			->join(
				['payment' => $orderCollection->getTable('sales_order_payment')],
				'main_table.entity_id = payment.parent_id',
				[]
			)->where(
				'payment.method IN (?)',
				['bellunopayment', 'bellunobankslip', 'bellunopix', 'bellunolink']
			);
			$orderCollection->setOrder('entity_id', 'ASC')->setPageSize(50);

		}catch (\Exception $e) {
		}
		
		if(count($orderCollection) == 0) {
			return $this;
		}
		
		foreach($orderCollection as $order) {
			$payment = $order->getPayment()->getAdditionalInformation();
			if(isset($payment['bankslip']['id'])) {
				$function = '/bankslip/' . $payment['bankslip']['id'];
			}
			elseif(isset($payment['transaction_data']['id_transaction'])) {
				$function = '/transaction/' . $payment['transaction_data']['id_transaction'];
			}
			elseif(isset($payment['pix']['transaction_id'])) {
				$function = '/v2/transaction/' . $payment['pix']['transaction_id'] . '/pix';
			}
			elseif(isset($payment['response'])) {
				$response = json_decode($payment['response'], true);
				if(isset($response['transaction']['transaction_id'])) {
					$function = '/v2/transaction/' . $response['transaction']['transaction_id'];
				}
			}
			
			if(isset($function)) {
				$params = ['data' => json_encode([]), 'method' => 'get', 'host' => ''];
				$response = $this->helper->getBellunoService($order->getStoreId())->doRequest($function, $params, true);
				$response = json_decode($response->getBody(), true);
				$this->logger->info('---------------------' . $order->getIncrementId() . '---------------------');
				$this->logger->info(json_encode($response));
				
				if(isset($response['message'])) {
					$forward = (new \DateTime($now))->modify('+12 hours')->format('Y-m-d H:i:s');
					$this->updateStatus($order, $forward);
					continue;
				}
				
				if(isset($response['bankslip']) || isset($response['transaction'])) {
					$method = isset($response['bankslip']) ? $response['bankslip'] : $response['transaction'];
					$status = $method['status'];
					$msg = $order->getIncrementId() . ' - status belluno: ' . $status;
					
					if($status == 'Expired' || $status == 'Refused' || $status == 'Cancelled') {
						if($order->hasInvoices()) {
							$this->cancelOrderWithInvoice($order, $msg);
						}else {
							$this->cancelOrder($order, $msg);
						}
					}
					
					if($status == 'Paid') {
						if($order->hasInvoices()) {
							$this->changeStatus($order, 'processing', $msg);
						}
						else {
							$this->createInvoice($order, 'processing', $msg);
						}
					}
				}	
			}
			
			$this->updateStatus($order, $now);
		}
		
		return $this;
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
		$resource = $this->objectManager->get('Magento\Framework\App\ResourceConnection');
		$connection = $resource->getConnection();
		try {
			$tableName = $resource->getTableName('sales_order_item');
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