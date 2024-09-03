<?php
namespace Belluno\Magento2\Cron;

class ChangeOrderStatus
{
	protected $logger;
	protected $orderCollectionFactory;
	protected $helper;
	protected $orderHelper;
	protected $limitTime = 40;
	protected $expirationDays;
	protected $configBankSlip;
	
	public function __construct(
        \Magento\Sales\Model\ResourceModel\Order\CollectionFactory $orderCollectionFactory,
		\Belluno\Magento2\Helper\Helper $helper,
		\Belluno\Magento2\Helper\Order $orderHelper,
		\Belluno\Magento2\Gateway\Config\ConfigBankSlip $configBankSlip
    ) {
		$this->orderCollectionFactory = $orderCollectionFactory;
		$this->helper = $helper;
		$this->orderHelper = $orderHelper;
		$this->configBankSlip = $configBankSlip;
		
		$expirationDays = (int)$this->configBankSlip->getExpirationDays();
		$expirationDays = ($expirationDays * 24 * 60)/$this->limitTime;
		$this->expirationDays = $expirationDays * 2;
		$this->logger = $this->helper->getLog('belluno_order_status');
    }
	
	public function execute()
	{	
		$now = (new \DateTime())->modify('-3 hours')->format('Y-m-d H:i:s');
		try {
			$orderCollection = $this->orderCollectionFactory->create();
			$orderCollection->addFieldToFilter('status', ['in' => ['pending', 'payment_review']]);
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
					$this->orderHelper->updateStatus($order, $forward);
					continue;
				}
				
				if(isset($response['bankslip']) || isset($response['transaction'])) {
					$method = isset($response['bankslip']) ? $response['bankslip'] : $response['transaction'];
					$status = $method['status'];
					$msg = $order->getIncrementId() . ' - status belluno: ' . $status;
					if($status == 'Expired' || $status == 'Refused' || $status == 'Cancelled') {
						if($order->hasInvoices()) {
							$this->orderHelper->cancelOrderWithInvoice($order, $msg);
						}else {
							$this->orderHelper->cancelOrder($order, $msg);
						}
					}
					if($status == 'Paid') {
						if($order->hasInvoices()) {
							$this->orderHelper->changeStatus($order, 'processing', $msg);
							$this->orderHelper->sendInvoiceEmail($order);
						}
						else {
							$this->orderHelper->createInvoice($order, 'processing', $msg);
						}
					}
				}	
			}
			
			$this->orderHelper->updateStatus($order, $now);
		}
		
		return $this;
	}
}