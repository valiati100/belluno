<?php

declare(strict_types=1);

namespace Belluno\Magento2\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Belluno\Magento2\Gateway\Helper\SubjectReader;

class SettlementRequest implements BuilderInterface {

  /** Status to refuse or approve */
  const STATUS = 'status';

  /** Reason to action */
  const REASON = 'reason';

  /** @var SubjectReader */
  protected $_subjectReader;

  public function __construct(SubjectReader $subjectReader) {
    $this->_subjectReader = $subjectReader;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $buildSubject) {
    $paymentDO = $this->_subjectReader->readPayment($buildSubject);
    $payment = $paymentDO->getPayment();
    $additionalInformation = $payment->getAdditionalInformation();

    $transactionId = '';
    if (isset($additionalInformation['transaction_data']['id_transaction'])) {
      $transactionId = $additionalInformation['transaction_data']['id_transaction'];
    }

    $result = [
      'id' => $transactionId,
      'request' => [
        self::STATUS => 'approve',
        self::REASON => 'Nothing to declare'
      ]
    ];

    return $result;
  }
}
