<?php

declare(strict_types=1);

namespace Belluno\Magento2\Gateway\Request;

use Magento\Payment\Gateway\Request\BuilderInterface;
use Belluno\Magento2\Gateway\Helper\SubjectReader;
use Belluno\Magento2\Model\Validations\CredentialsValidator;

class CreditCardBillingRequest implements BuilderInterface {

  /** Billing Block */
  const BILLING = 'billing';

  /** Postal Code */
  const POSTAL_CODE = 'postalCode';

  /** Street */
  const STREET = 'street';

  /** Number */
  const NUMBER = 'number';

  /** City */
  const CITY = 'city';

  /** State */
  const STATE = 'state';

  /** @var SubjectReader */
  protected $_subjectReader;

  /** @var CredentialsValidator */
  private $_credentialValidator;

  public function __construct(
    SubjectReader $subjectReader,
    CredentialsValidator $credentialsValidator
  ) {
    $this->_subjectReader = $subjectReader;
    $this->_credentialValidator = $credentialsValidator;
  }

  /** 
   * {@inheritdoc} 
   */
  public function build(array $buildSubject) {
    $paymentDO = $this->_subjectReader->readPayment($buildSubject);
    $order = $paymentDO->getOrder();
    $billingAddress = $order->getBillingAddress();
    $payment = $paymentDO->getPayment();
    $additionalInformation = $payment->getAdditionalInformation();

    $postalCode = $billingAddress->getPostcode();
    $postalCode = preg_replace('/[^0-9]/is', '', $postalCode);
    $postalCode = substr_replace($postalCode, '-', 5, 0);
    isset($additionalInformation['billing_street']) ? $street = $additionalInformation['billing_street'] : $street = '';
    isset($additionalInformation['billing_number']) ? $number = $additionalInformation['billing_number'] : $number = '';
    $city = $billingAddress->getCity();
    $state = $billingAddress->getCity();

    $this->_credentialValidator->validateBilling($postalCode, $street, $number, $city, $state, '0');

    $result = [
      'transaction' => [
        self::BILLING => [
          self::POSTAL_CODE => $postalCode,
          self::STREET => $street,
          self::NUMBER => $number,
          self::CITY => $city,
          self::STATE => $state
        ]
      ]
    ];

    return $result;
  }
}
