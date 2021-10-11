<?php

declare(strict_types=1);

namespace Belluno\Magento2\Model\Validations;

use Belluno\Magento2\Service\BellunoService;
use Psr\Log\LoggerInterface;
use Magento\Framework\App\Config\ScopeConfigInterface;
use phpseclib\Crypt\RSA;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\ScopeInterface;
use Belluno\Magento2\Helper\Helper;

class Encrypt {

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
  public function encrypt($additionalInformation) {
    $card_number = $additionalInformation['card_number'];
    $card_exp_month = $additionalInformation['cc_exp_month'];
    $card_exp_year = $additionalInformation['cc_exp_year'];
    $card_cvv = $additionalInformation['cc_cvv'];

    $card_exp_date = $card_exp_month . $card_exp_year;
    (strlen($card_exp_date) != 6) ? $card_exp_date = '0' . $card_exp_date : $card_exp_date = $card_exp_date;

    $queryString = http_build_query(
      [
        'card_number' => $card_number,
        'card_expiration_date' => $card_exp_date,
        'card_cvv' => $card_cvv
      ]
    );

    $params = [
      'data' => '',
      'method' => 'get',
      'host' => ''
    ];
    $function = '/transaction/card_hash_key';

    $storeId = $this->_cart->getStoreId();
    $response = $this->_helper->getBellunoService($storeId)->doRequest($function, $params);
    $response = json_decode($response, true);

    $id = $response['id'];
    $publicKey = $response['rsa_public_key'];

    $result = $this->encrypting($queryString, $publicKey);

    return $id . '_' . $result;
  }

  /**
   * Function to encrypt
   * @param $queryString
   * @param $publicKey
   * @return string
   */
  protected function encrypting($queryString, $publicKey) {
    $result = '';

    $rsa = new RSA();
    $rsa->loadKey($publicKey);
    $rsa->setEncryptionMode(RSA::ENCRYPTION_PKCS1);
    $result = $rsa->encrypt($queryString);

    return base64_encode($result);
  }
}
