<?php

declare(strict_types=1);

namespace Belluno\Magento2\Block\Checkout;

use Magento\Checkout\Model\Session;
use Magento\Framework\View\Element\Template\Context;
use Magento\Framework\View\Element\Template;
use Magento\Sales\Model\Order;
use Magento\Framework\Pricing\Helper\Data;

class Success extends Template
{
    /** @var Session */
    protected $_checkoutSession;
	
	protected $_priceHelper;

    public function __construct(
        Session $checkoutSession,
		Data $priceHelper,
        Context $context,
        array $data = []
    ) {
        parent::__construct($context, $data);
        $this->_checkoutSession = $checkoutSession;
		$this->_priceHelper = $priceHelper;
    }

    public function getOrder(): Order
    {
        return $this->_checkoutSession->getLastRealOrder();
    }

    public function getAdditionalInformation()
    {
        $order = $this->getOrder();
        $payment = $order->getPayment();
        return $payment->getAdditionalInformation();
    }

    public function getBankSlip()
    {
        $data = $this->getAdditionalInformation();
        if(isset($data["bankslip"]["url"])) {
            return $data["bankslip"]["url"];
        }
		return " ";
    }

    public function getBankSlipDigitableLine()
    {
        $data = $this->getAdditionalInformation();
        if(isset($data["bankslip"]["digitable_line"])) {
            return $data["bankslip"]["digitable_line"];
        }
		return " ";
    }

    public function getBase64Image()
    {
        $data = $this->getAdditionalInformation();
        if(isset($data["pix"]["base64_image"])) {
            return $data["pix"]["base64_image"];
        }
		return " ";
    }

    public function getBase64Text()
    {
        $data = $this->getAdditionalInformation();
        if(isset($data["pix"]["base64_text"])) {
            return $data["pix"]["base64_text"];
        }
		return " ";
    }

    public function getExpireDate()
    {
        $data = $this->getAdditionalInformation();
        if(isset($data["pix"]["expires_at"])) {
            return $data["pix"]["expires_at"];
        }
		return " ";
    }
	
	public function totalFormatted($value) {
		return $this->_priceHelper->currency($value, true, false);
	}
}