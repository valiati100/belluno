<?php

declare(strict_types=1);

namespace Belluno\Magento2\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Framework\UrlInterface;

class BankSlipPostbackRequest implements BuilderInterface {

  /** Postback Block */
  const POSTBACK = 'postback';

  /** Url Postback */
  const URL = 'url';

  /** @var UrlInterface */
  private $_urlInterface;

  public function __construct(
    UrlInterface $urlInterface
  ) {
    $this->_urlInterface = $urlInterface;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $buildSubject) {
    $url = $this->_urlInterface->getBaseUrl() . 'rest/V1/status/update';

    $array = [
      self::URL => $url
    ];

    $result = [
      'bankslip' => [
        self::POSTBACK => $array
      ]
    ];

    return $result;
  }
}
