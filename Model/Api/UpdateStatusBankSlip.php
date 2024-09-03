<?php

namespace Belluno\Magento2\Model\Api;

use Belluno\Magento2\Api\UpdateStatusBankSlipInterface;
use Magento\Sales\Api\OrderRepositoryInterface;
use Magento\Sales\Model\Order;
use Magento\Sales\Model\OrderFactory;
use Belluno\Magento2\Block\Invoice\Invoice;
use Magento\Framework\Webapi\Rest\Request;
use Belluno\Magento2\Helper\Helper;
use Belluno\Magento2\Helper\Order as OrderHelper;
use Belluno\Magento2\Gateway\Config\ConfigCc;

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
	
	protected $logger;
	
	protected $helper;
	
	protected $orderHelper;

    public function __construct(
        OrderRepositoryInterface $orderRepository,
        Invoice $invoice,
        OrderFactory $orderFactory,
        Request $request,
		Helper $helper,
		OrderHelper $orderHelper
    ) {
        $this->_orderFactory = $orderFactory;
        $this->_orderRepository = $orderRepository;
        $this->_invoice = $invoice;
        $this->_request = $request;
		$this->helper = $helper;
		$this->orderHelper = $orderHelper;
		$this->logger = $this->helper->getLog('belluno_notifications');
    }

    /**
     * Postback Belluno
     * @return string
     */
    public function doUpdate()
    {
		$this->logger->info("####################################HELLO####################################");
        $data = $this->_request->getBodyParams();
		$this->logger->info(json_encode($data));
        $status = "";
		
		if(isset($data["bankslip"])) {
			$orderId = trim($data["bankslip"]["document_code"]);
            $status = trim($data["bankslip"]["status"]);
		}
		elseif(isset($data["transaction"])) {
			$orderId = trim($data["transaction"]["details"]);
            $status = trim($data["transaction"]["status"]);
		}
		
		#try {
        #    $orderId = $data["bankslip"]["document_code"];
        #    $status = $data["bankslip"]["status"];
        #} catch (\Throwable $th) {
        #    $orderId = $data["transaction"]["details"];
        #    $status = $data["transaction"]["status"];
        #}

        if($status == "Paid") {
            $order = $this->_orderFactory->create()->loadByIncrementId($orderId);
            if(isset($order)) {
				try {
					if($order->canInvoice()) {
						$this->orderHelper->changeStatus($order, Order::STATE_PROCESSING, $order->getIncrementId());
						$totalInvoiced = $order->getTotalInvoiced();
						if(empty($totalInvoiced)) {
							$this->_invoice->generateInvoice($order->getId());
						}
					}elseif($order->hasInvoices()) {
						$payment = $order->getPayment();
						if($payment->getMethod() == ConfigCc::METHOD && $order->getStatus() == Order::STATE_PAYMENT_REVIEW) {
							$this->orderHelper->changeStatus($order, Order::STATE_PROCESSING, $order->getIncrementId());
							$this->orderHelper->sendInvoiceEmail($order);
						}
					}
				}catch (\Exception $e) {
					$this->logger->info($e->getMessage());
				}
            }else {
				$this->logger->info(__("Not found order"));
                throw new \Magento\Framework\Webapi\Exception(__("Not found order"), 0, \Magento\Framework\Webapi\Exception::HTTP_BAD_REQUEST);
            }
        }

        if(isset($data["transaction"]["refunds"]["0"]["status"])) {
            if(!empty($data["transaction"]["refunds"]["0"]["amount"])) {
                $amount = $data["transaction"]["refunds"]["0"]["amount"];
                if($amount != "0") {
                    $order = $this->_orderFactory->create()->loadByIncrementId($orderId);
                    $order->setState(Order::STATE_CANCELED);
                    $order->setStatus(Order::STATE_CANCELED);
                    $this->_orderRepository->save($order);
                }
            }
        }
    }
}