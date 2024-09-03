<?php

declare(strict_types=1);

namespace Belluno\Magento2\Model\Validations;

use Belluno\Magento2\Service\BellunoService;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\ScopeInterface;
use Belluno\Magento2\Helper\Helper;

#use phpseclib3\Crypt\RSA;
#use phpseclib3\Crypt\PublicKeyLoader;
#use phpseclib\Crypt\RSA as RSA;

class Encrypt
{
    /** @var BellunoService */
    protected $_bellunoService;

    /** @var ScopeConfigInterface */
    protected $_scopeConfig;

    /** @var LoggerInterface */
    protected $_logger;

    /** @var CartInterface */
    protected $_cart;

    /** @var Helper */
    private $_helper;

    public function __construct(
        LoggerInterface $logger,
        ScopeConfigInterface $scopeConfig,
        CartInterface $cart,
        Helper $helper
    ) {
        $this->_logger = $logger;
        $this->_scopeConfig = $scopeConfig;
        $this->_cart = $cart;
        $this->_helper = $helper;
    }

    /**
     * Function to encrypt
     * @param $additionalInformation
     * @return string
     */
    public function encrypt($additionalInformation)
    {
        $card_number = $additionalInformation["card_number"];
        $card_exp_month = $additionalInformation["cc_exp_month"];
        $card_exp_year = $additionalInformation["cc_exp_year"];
        $card_cvv = $additionalInformation["cc_cvv"];

        $card_exp_date = $card_exp_month . $card_exp_year;
		if(strlen($card_exp_date) != 6) {
			$card_exp_date = "0" . $card_exp_date;
		}

        $queryString = http_build_query([
            "card_number" => $card_number,
            "card_expiration_date" => $card_exp_date,
            "card_cvv" => $card_cvv,
        ]);

        $params = [
            "data" => json_encode([]),
            "method" => "get",
            "host" => "",
        ];
        $function = "/transaction/card_hash_key";

        $storeId = $this->_cart->getStoreId();
        $response = $this->_helper->getBellunoService($storeId)->doRequest($function, $params);
        $response = json_decode($response, true);

        $id = $response["id"];
        $publicKey = $response["rsa_public_key"];
        $result = $this->encrypting($queryString, $publicKey);

        return $id . "_" . $result;
    }

    /**
     * Function to encrypt
     * @param $queryString
     * @param $publicKey
     * @return string
     */
    /*protected function encrypting($queryString, $publicKey) {
		$result = '';
		$rsa = new RSA();
		$rsa->loadKey($publicKey);
		$rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
		$result = $rsa->encrypt($queryString);
		return base64_encode($result);
	}*/

    protected function encrypting($queryString, $publicKey)
    {
        if(class_exists("phpseclib3\Crypt\PublicKeyLoader")) {
            #https://phpseclib.com/docs/php
            #https://phpseclib.com/docs/rsa
            $key = \phpseclib3\Crypt\PublicKeyLoader::load($publicKey);
            $key = $key->withPadding(\phpseclib3\Crypt\RSA::ENCRYPTION_PKCS1);
            return base64_encode($key->encrypt($queryString));
        }else {
            $result = "";
            $rsa = new \phpseclib\Crypt\RSA();
            $rsa->loadKey($publicKey);
            $rsa->setEncryptionMode(\phpseclib\Crypt\RSA::ENCRYPTION_PKCS1);
            $result = $rsa->encrypt($queryString);
            return base64_encode($result);
        }
    }
}