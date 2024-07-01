<?php

namespace Belluno\Magento2\Controller\Adminhtml\Order;

use Magento\Backend\App\Action;
use Magento\Backend\App\Action\Context;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Framework\Controller\ResultFactory;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Belluno\Magento2\Helper\Helper;
use Belluno\Magento2\Model\Api\LinkDataRequest;

class CancelPaymentLink extends Action
{
    protected $orderRepository;
	protected $resultFactory;
	protected $helper;
	protected $logger;
	protected $linkDataRequest;

    public function __construct(
        Context $context,
        OrderRepositoryInterface $orderRepository,
		ResultFactory $resultFactory,
		Helper $helper,
		LinkDataRequest $linkDataRequest
		
    ) {
        parent::__construct($context);
        $this->orderRepository = $orderRepository;
		$this->resultFactory = $resultFactory;
		$this->helper = $helper;
		$this->linkDataRequest = $linkDataRequest;
		
		$writer = new \Zend_Log_Writer_Stream(BP . '/var/log/belluno_link.log');
		$this->logger = new \Zend_Log();
		$this->logger->addWriter($writer);
    }

    public function execute()
    {
        $request = $this->getRequest();
        $orderId = (int)$request->getParam('order_id');
        $order = $this->orderRepository->get($orderId);
		
        if($order->getId() && $order->getPayment()->getMethod() == 'bellunolink') {
			$payment = $order->getPayment();
			if($payment->getAdditionalInformation('bellunolink') != null) {
				$this->logger->info('############################################################################');
				$params = [
					'data' => [],
					'method' => 'post',
					'host' => ''
				];
				
				$id = $this->linkDataRequest->getTransactionId($payment->getAdditionalInformation('response'));
				$function = '/v2/transaction/'.$id.'/inactivate';
				
				try {				
					$response = $this->helper->getBellunoService($order->getStoreId())->doRequest($function, $params);
					$this->logger->info($order->getId() . ' - ' . $response);
					$response = json_decode($response, true);
				}catch (\Exception $e) {
					$this->logger->info($order->getId() . ' - ' . trim($e->getMessage()));
					$this->messageManager->addErrorMessage(__($e->getMessage()));
					return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setUrl($this->_redirect->getRefererUrl());
				}
				
				if(isset($response['transaction']['link'])) {
					$info = $payment->getAdditionalInformation();
					$info['canceled'] = json_encode($response);
					
					try {
						$payment->setAdditionalInformation($info)->save();
					}catch (\Exception $e) {
						$this->logger->info($order->getId() . ' - ' . $e->getMessage());
					}

					$this->messageManager->addSuccessMessage(__('Successfully canceled payment link.'));
					return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setUrl($this->_redirect->getRefererUrl());
				}
				else {
					$this->messageManager->addErrorMessage(__('Error. Please try again.'));
					return $this->resultFactory->create(ResultFactory::TYPE_REDIRECT)->setUrl($this->_redirect->getRefererUrl());
				}
			}	
        }
    }
  
    protected function _isAllowed()
    {
        return $this->_authorization->isAllowed('Belluno_Magento2::generatepaymentlink');
    }
}
