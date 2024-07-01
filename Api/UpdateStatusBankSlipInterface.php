<?php

declare(strict_types=1);

namespace Belluno\Magento2\Api;

interface UpdateStatusBankSlipInterface {

  /**
   * Postback Belluno
   * @return string
   */
  public function doUpdate();
}