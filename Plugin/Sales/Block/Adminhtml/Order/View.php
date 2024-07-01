<?php
namespace Belluno\Magento2\Plugin\Sales\Block\Adminhtml\Order;

use Magento\Sales\Block\Adminhtml\Order\View as OrderView;
use Magento\Framework\Registry;
use Magento\Framework\UrlInterface;

class View
{
	protected $registry;
	protected $url;
	
	public function __construct(
        Registry $coreRegistry,
        UrlInterface $urlBuilder
    ) {
        $this->registry = $coreRegistry;
		$this->url = $urlBuilder;
    }
	
    public function beforeSetLayout(OrderView $subject)
    {
		$order = $this->registry->registry('current_order');
		if($order->getId() && $order->getPayment()->getMethod() == 'bellunolink') {
			$payment = $order->getPayment();
			if($payment->getAdditionalInformation('bellunolink') == null) {
				$url = $this->url->getUrl('generatepaymentlink/order/index', ['order_id' => $order->getId()]);
				$subject->addButton(
					'generate_payment_link',
					[
						'label' => __('Generate payment link'),
						'id' => 'generatePaymentLinkClick',
						'onclick' => 'setLocation(\'' . $url . '\')'
					]
				);
			}elseif($payment->getAdditionalInformation('canceled') == null) {
				$url = $this->url->getUrl('generatepaymentlink/order/cancelpaymentlink', ['order_id' => $order->getId()]);
				$subject->addButton(
					'cancel_payment_link',
					[
						'label' => __('Calcel payment link'),
						'id' => 'calcelPaymentLinkClick',
						'onclick' => 'setLocation(\'' . $url . '\')'
					]
				);
			}
		}
    }
}