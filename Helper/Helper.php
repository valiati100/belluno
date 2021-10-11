<?php

declare(strict_types=1);

namespace Belluno\Magento2\Helper;

use Belluno\Magento2\Service\BellunoService;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;
use Psr\Log\LoggerInterface;

class Helper {

  /** @var BellunoService */
  private $_bellunoService;

  /** @var ScopeConfigInterface */
  private $_scopeConfig;

  /** @var LoggerInterface */
  private $_logger;

  public function __construct(
    ScopeConfigInterface $scopeConfig,
    LoggerInterface $logger
  ) {
    $this->_scopeConfig = $scopeConfig;
    $this->_logger = $logger;
  }

  /** 
   * Function to get new instance of BellunoService
   * @param string $storeId
   * @return BellunoService
   */
  public function getBellunoService($storeId) {
    $token = $this->_scopeConfig->getValue(
      'payment/belluno_config/belluno_auth/authentication',
      ScopeInterface::SCOPE_STORE,
      $storeId
    );

    $environment = $this->_scopeConfig->getValue(
      'payment/belluno_config/belluno_config/environment',
      ScopeInterface::SCOPE_STORE,
      $storeId
    );

    if ($environment == 'production') {
      $host = 'https://api.belluno.digital';
    } else {
      $host = 'https://ws-sandbox.bellunopag.com.br';
    }

    $this->_bellunoService = new BellunoService(
      $this->_logger,
      $token,
      $host
    );

    return $this->_bellunoService;
  }
}
