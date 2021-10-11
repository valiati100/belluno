<?php

declare(strict_types=1);

namespace Belluno\Magento2\Gateway\Config;

use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Locale\Bundle\DataBundle;
use Magento\Framework\Locale\ResolverInterface;
use Magento\Framework\Stdlib\DateTime\DateTime;
use Magento\Framework\View\Asset\Source;
use Magento\Payment\Gateway\Config\Config;
use Magento\Payment\Model\CcConfig;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\ScopeInterface;

class ConfigCc extends Config {

  const METHOD = 'bellunopayment';

  protected $_ccoptions = [
    'MC' => 'Mastercard',
    'VI' => 'Visa',
    'ELO' => 'Elo',
    'HC' => 'Hipercard',
    'HI' => 'HIPER',
    'CAB' => 'CAB'
  ];

  /** @var ResolverInterface */
  private $_localeResolver;

  /** @var DateTime */
  private $_dateTime;

  /** @var CartInterface */
  protected $_cart;

  /** @var array */
  protected $_icons = [];

  /** @var CcConfig */
  protected $_ccConfig;

  /** @var Source */
  protected $_assetSource;

  /** @var ScopeConfigInterface */
  protected $_scopeConfig;

  public function __construct(
    ResolverInterface $localeResolver,
    DateTime $dateTime,
    CartInterface $cart,
    CcConfig $ccConfig,
    Source $assetSource,
    ScopeConfigInterface $scopeConfig
  ) {
    $this->_localeResolver = $localeResolver;
    $this->_dateTime = $dateTime;
    $this->_cart = $cart;
    $this->_ccConfig = $ccConfig;
    $this->_assetSource = $assetSource;
    $this->_scopeConfig = $scopeConfig;
  }

  /**
   * Get CC available Types
   * @return array
   */
  public function getCcAvailableTypes() {
    return $this->_ccoptions;
  }

  /**
   * @return array
   */
  public function getMonths() {
    $data = [];
    $months = (new DataBundle())->get(
      $this->_localeResolver->getLocale()
    )['calendar']['gregorian']['monthNames']['format']['wide'];
    foreach ($months as $key => $value) {
      $monthNum = ++$key < 10 ? '0' . $key : $key;
      $data[$key] = $monthNum . ' - ' . $value;
    }
    return $data;
  }

  /**
   * @return array
   */
  public function getYears() {
    $years = [];
    $first = (int)$this->_dateTime->date('Y');
    for ($index = 0; $index <= 20; $index++) {
      $year = $first + $index;
      $years[$year] = $year;
    }
    return $years;
  }

  /**
   * Get icons for available payment methods.
   * @return array
   */
  public function getIcons() {
    if (!empty($this->_icons)) {
      return $this->_icons;
    }
    $storeId = $this->_cart->getStoreId();
    $types =  $this->getCcAvailableTypes($storeId);
    foreach ($types as $code => $label) {
      if (!array_key_exists($code, $this->_icons)) {
        $asset = $this->_ccConfig->createAsset('Belluno_Magento2::images/cc/' . strtolower($label) . '.png');
        $placeholder = $this->_assetSource->findSource($asset);
        if ($placeholder) {
          list($width, $height) = getimagesizefromstring($asset->getSourceFile());
          $this->_icons[$code] = [
            'url'    => $asset->getUrl(),
            'width'  => $width,
            'height' => $height,
            'title'  => __($label),
          ];
        }
      }
    }

    return $this->_icons;
  }

  /**
   * Get installments
   * @return array
   */
  public function getInstallments() {
    $storeId = $this->_cart->getStoreId();
    $installmentsConfig = $this->_scopeConfig->getValue(
      'payment/belluno_config/bellunopayment/installments',
      ScopeInterface::SCOPE_STORE,
      $storeId
    );
    if (!isset($installmentsConfig)) {
      return [];
    } else {
      $installmentsConfig = json_decode($installmentsConfig, true);
    }

    $i = 1;
    $installments = [];
    foreach ($installmentsConfig as $value) {
      $installments[$i] = $value['from_qty'];
      $i++;
    }

    return $installments;
  }

  /**
   * Get min installment.
   * @return int
   */
  public function getMinInstallment() {
    $storeId = $this->_cart->getStoreId();
    return $this->_scopeConfig->getValue(
      'payment/bellunopayment/min_installment',
      ScopeInterface::SCOPE_STORE,
      $storeId
    );
  }

  /**
   * Function to get public key konduto
   * @return string
   */
  public function getPubKeyKonduto() {
    $storeId = $this->_cart->getStoreId();
    return $this->_scopeConfig->getValue(
      'payment/belluno_config/belluno_auth/pub_key_konduto',
      ScopeInterface::SCOPE_STORE,
      $storeId
    );
  }

  /**
   * Get if you use document capture on the form.
   * @return string|null
   */
  public function getUseTaxDocumentCapture() {
    $storeId = $this->_cart->getStoreId();
    $pathPattern = 'payment/belluno_config/bellunopayment/tax_document';

    return (bool) $this->_scopeConfig->getValue(
      $pathPattern,
      ScopeInterface::SCOPE_STORE,
      $storeId
    );
  }
}
