<?php

declare(strict_types=1);

namespace Belluno\Magento2\Block\Adminhtml\Form\Field;

use Magento\Framework\Data\OptionSourceInterface;

class RuleCapture implements OptionSourceInterface {

  /*
  * Function to get capture options
  * @return array
  */
  public function toOptionArray() {
    $array = [
      [
        'value' => 'authorize',
        'label' => __('Authorize')
      ],
      [
        'value' => 'authorize_capture',
        'label' => __('Authorize and Capture')
      ]  
    ];

    return $array;
  }
}
