<?php

declare(strict_types=1);

namespace Belluno\Magento2\Model\Ui\CreditCard;

use Magento\Checkout\Model\ConfigProviderInterface;
use Belluno\Magento2\Gateway\Config\ConfigCc;

class ConfigProvider implements ConfigProviderInterface {

  const CODE = ConfigCc::METHOD;

  /** @var ConfigCc */
  private $_config;

  public function __construct(ConfigCc $config) {
    $this->_config = $config;
  }

  /**
   * Retrieve array of checkout configuration.
   * @return array
   */
  public function getConfig() {
    return [
      'bellunopayment' => [
        ConfigCc::METHOD => [
          'ccavailabletypes' => $this->_config->getCcAvailableTypes(),
          'years' => $this->_config->getYears(),
          'months' => $this->_config->getMonths(),
          'icons' => $this->_config->getIcons(),
          'installments' => $this->_config->getInstallments(),
          'min_installment' => $this->_config->getMinInstallment(),
          'max_installment' => '12',
          'tax_document' => $this->_config->getUseTaxDocumentCapture(),
          'pub_key_konduto' => $this->_config->getPubKeyKonduto(),
        ],
      ],
    ];
  }
}
