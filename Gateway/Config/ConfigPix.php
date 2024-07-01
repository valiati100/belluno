<?php

declare(strict_types=1);

namespace Belluno\Magento2\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Payment\Gateway\Config\Config;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\ScopeInterface;

class ConfigPix extends Config
{
    const METHOD = "bellunopix";

    /** @var ScopeConfigInterface */
    protected $_scopeConfig;

    /** @var CartInterface */
    protected $_cart;

    public function __construct(
        ScopeConfigInterface $scopeConfig,
        CartInterface $cart
    ) {
        $this->_scopeConfig = $scopeConfig;
        $this->_cart = $cart;
    }

    /**
     * Function to get expiration days of pix
     */
    public function getExpirationDays()
    {
        $storeId = $this->_cart->getStoreId();
        $days = $this->_scopeConfig->getValue(
            "payment/bellunopix/expiration_days",
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
		
        $days = !isset($days) ? "1" : $days;
        return $days;
    }

    /**
     * Get if you use document capture on the form.
     * @return string|null
     */
    public function getUseTaxDocumentCapture()
    {
        $storeId = $this->_cart->getStoreId();
        $pathPattern = "payment/belluno_config/bellunopix/tax_document";

        return (bool) $this->_scopeConfig->getValue(
            $pathPattern,
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
    }
}