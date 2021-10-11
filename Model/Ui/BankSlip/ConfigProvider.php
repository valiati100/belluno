<?php

declare(strict_types=1);

namespace Belluno\Magento2\Model\Ui\BankSlip;

use Belluno\Magento2\Gateway\Config\ConfigBankSlip;
use Magento\Checkout\Model\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface {

  const CODE = ConfigBankSlip::METHOD;

  /** @var ConfigBankSlip */
  private $_config;

  public function __construct(ConfigBankSlip $config) {
    $this->_config = $config;
  }

  /**
   * Retrieve array of checkout configuration.
   * @return array
   */
  public function getConfig() {
    return [
      'bellunobankslip' => [
        ConfigBankSlip::METHOD => [
          'expiration_days' => $this->_config->getExpirationDays(),
          'tax_document' => $this->_config->getUseTaxDocumentCapture()
        ],
      ],
    ];
  }
}
