<?php

namespace Belluno\Magento2\Model\Api;

use Magento\Customer\Api\CustomerRepositoryInterface;
use Magento\Framework\UrlInterface;
use Magento\Framework\Mail\Template\TransportBuilder;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Belluno\Magento2\Helper\Helper;

class LinkDataRequest
{
	protected $_customer;
	protected $_urlInterface;
	protected $logger;
	protected $transportBuilder;
	protected $scopeConfig;
	protected $helper;

    public function __construct(
		ScopeConfigInterface $scopeConfig,
		CustomerRepositoryInterface $customer,
		UrlInterface $urlInterface,
		TransportBuilder $transportBuilder,
		Helper $helper
		
    ) {
		$this->_customer = $customer;
		$this->_urlInterface = $urlInterface;
		$this->transportBuilder = $transportBuilder;
		$this->scopeConfig = $scopeConfig;
		$this->helper = $helper;
		$this->logger = $this->helper->getLog('belluno_link');
    }
	
    public function buildRequest($order) {
		$items = $order->getAllItems();
		$customerId = $order->getCustomerId();
		$shipping = $order->getShippingAddress();
		$billing = $order->getBillingAddress();
		$total = $order->getGrandTotal();
		$name = $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname();
		$document = $customerId != null ? $this->getTaxVat($customerId) : $shipping->getVatId();
		$shippingAddress = $shipping->getStreet();
		$billingAddress = $billing->getStreet();
		$cart = [];
		$result = [];	

		foreach($items as $item) {
			if($item->getProductType() != 'configurable') {
				if($item->getPrice() == 0) {
					$parentItem = $item->getParentItem();
					$price = $parentItem->getPrice();
				}else {
					$price = $item->getPrice();
				}
				$cart[] = [
					'product_name' => $item->getName(),
					'quantity' => (int)$item->getQtyOrdered(),
					'unit_value' => $price
				];
			}
		}

		$result = [
			'transaction' => [
				'value' => $total,
				#'capture' => '1',
				'client_name' => $name,
				'client_email' => $this->getEmail($order),
				'client_document' => $document,
				'client_cellphone' => str_replace(['-', ' '], '', $shipping->getTelephone()),
				'details' => $order->getIncrementId(),
				'shipping' => [
					'postalCode' => $this->formatPostcode($shipping->getPostcode()),
					'street' => $shippingAddress[0],
					'number' => $shippingAddress[1],
					'city' => $shipping->getCity(),
					'state' => $shipping->getRegionCode()
				],
				'billing' => [
					'postalCode' => $this->formatPostcode($billing->getPostcode()),
					'street' => $billingAddress[0],
					'number' => $billingAddress[1],
					'city' => $billing->getCity(),
					'state' => $billing->getRegionCode()
				],
				'cart' => $cart,
				'postback' => ['url' => $this->_urlInterface->getBaseUrl() . 'rest/V1/status/update']
			]
		];

		return $result;
	}
	
	public function sendEmail($order, $link) {
		$fullName = $order->getCustomerFirstname() . ' ' . $order->getCustomerLastname();
		$customerEmail = $this->getEmail($order);
		$email = $this->scopeConfig->getValue('trans_email/ident_general/email', ScopeInterface::SCOPE_STORE, $order->getStoreId());
		$name  = $this->scopeConfig->getValue('trans_email/ident_general/name', ScopeInterface::SCOPE_STORE, $order->getStoreId());

		$postObject = new \Magento\Framework\DataObject();
		$postObject->setData([
			'customer_name' => $fullName,
			'payment_link' => $link,
		]);
		
		$templateId = $this->scopeConfig->getValue(
			'payment/bellunolink/emailtemplate',
			ScopeInterface::SCOPE_STORE,
			$order->getStoreId()
		);
		$templateId = $templateId ?? 'payment_belluno_config_bellunolink_emailtemplate';
			
		try {
			$transport = $this->transportBuilder
				->setTemplateIdentifier($templateId)
				->setTemplateOptions(['area' => \Magento\Framework\App\Area::AREA_FRONTEND, 'store' => $order->getStoreId()])
				->setTemplateVars(['data' => $postObject])
				->setFrom(['name' => $name,'email' => $email])
				->addTo([$customerEmail])
				->getTransport();
			$transport->sendMessage();
		}catch (\Exception $e) {
			$this->logger->info(trim($e->getMessage()));
		}
	}
	
	public function formatPostcode($postcode) {
		$postcode = preg_replace('/[^0-9]/is', '', $postcode);
		$postcode = substr_replace($postcode, '-', 5, 0);
		return $postcode;
	}
	
	public function getTaxVat($customerId) {
		$customer = $this->_customer->getById($customerId);
		return $customer->getTaxvat();
	}
	
	public function getEmail($order) {
		$email = $order->getCustomerEmail();
		if($email == null && $order->getCustomerId()) {
			$customer = $this->_customer->getById($order->getCustomerId());
			$email = $customer->getEmail();
		}
		return $email;
	}
	
	public function getTransactionId($response) {
		$response = json_decode($response, true);
		if(isset($response['transaction']['transaction_id'])) {
			return (int)$response['transaction']['transaction_id'];
		}
	}
}