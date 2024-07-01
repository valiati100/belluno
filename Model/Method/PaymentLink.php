<?php

namespace Belluno\Magento2\Model\Method;

class PaymentLink extends \Magento\Payment\Model\Method\AbstractMethod {
	/**
    * Payment code
    *
  	* @var string
  	*/
	protected $_code = 'bellunolink';
}