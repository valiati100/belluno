<?php

declare(strict_types=1);

namespace Belluno\Magento2\Block\Adminhtml\Form\Field;

use Magento\Framework\Data\OptionSourceInterface;

class Environment implements OptionSourceInterface {
  
  const SANDBOX = 'sandbox';
  const PRODUCTION = 'production';

  /*
  * Function to get capture options
  * @return array
  */
  public function toOptionArray() {
    $array = [
      self::PRODUCTION => __('Production'),
      self::SANDBOX => __('Sandbox - Environment for tests')
    ];
  
    return $array;
  }
}