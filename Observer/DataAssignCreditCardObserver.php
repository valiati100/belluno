<?php

declare(strict_types=1);

namespace Belluno\Magento2\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

class DataAssignCreditCardObserver extends AbstractDataAssignObserver implements ObserverInterface {

  /** @const Method Name Block */
  const METHOD_NAME = 'method_name';

  /** @const Method Name */
  const METHOD_NAME_TYPE = 'bellunopayment';

  /** @const Card Holder Document */
  const CARD_HOLDER_DOCUMENT = 'cardholder_document';

  /** @const Payer Fullname */
  const CARD_HOLDER_NAME = 'card_name';

  /** @const Payer Phone */
  const CARD_HOLDER_PHONE = 'cardholder_cellphone';

  /** @const Card Holder Birth */
  const CARD_HOLDER_BIRTH = 'cardholder_birth';

  /** @const Credit card CVV */
  const CREDIT_CARD_CVV = 'cc_cvv';

  /** @const Credit Card Number */
  const CREDIT_CARD_NUMBER = 'card_number';

  /** @const Credit Card Expiration Month */
  const CREDIT_CARD_EXP_MONTH = 'cc_exp_month';

  /** @const Credit Card Expiration year */
  const CREDIT_CARD_EXP_YEAR = 'cc_exp_year';

  /** @const Info Installments */
  const INFO_INSTALLMENTS = 'cc_installments';

  /** @const Client Document */
  const CLIENT_DOCUMENT = 'client_document';

  /** @const Visitor ID */
  const VISITOR_ID = 'visitor_id';

  /** @const Shipping Street */
  const SHIPPING_STREET = 'shipping_street';

  /** @const Shipping Number */
  const SHIPPING_NUMBER = 'shipping_number';

  /** @const Billing Street */
  const BILLING_STREET = 'billing_street';

  /** @const Billing Number */
  const BILLING_NUMBER = 'billing_number';

  /** @var array */
  protected $addInformationList = [
    self::CARD_HOLDER_DOCUMENT,
    self::CARD_HOLDER_NAME,
    self::CARD_HOLDER_PHONE,
    self::CARD_HOLDER_BIRTH,
    self::CREDIT_CARD_CVV,
    self::CREDIT_CARD_NUMBER,
    self::CREDIT_CARD_EXP_MONTH,
    self::CREDIT_CARD_EXP_YEAR,
    self::INFO_INSTALLMENTS,
    self::CLIENT_DOCUMENT,
    self::VISITOR_ID,
    self::SHIPPING_STREET,
    self::SHIPPING_NUMBER,
    self::BILLING_STREET,
    self::BILLING_NUMBER
  ];

  /**
   * @param Observer $observer
   * @return void
   */
  public function execute(Observer $observer) {

    $data = $this->readDataArgument($observer);

    $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);

    if (!is_array($additionalData)) {
      return;
    }

    $paymentInfo = $this->readPaymentModelArgument($observer);

    $paymentInfo->setAdditionalInformation(
      self::METHOD_NAME,
      self::METHOD_NAME_TYPE
    );

    foreach ($this->addInformationList as $addInformationKey) {
      if (isset($additionalData[$addInformationKey])) {
        if ($additionalData[$addInformationKey]) {
          $paymentInfo->setAdditionalInformation(
            $addInformationKey,
            $additionalData[$addInformationKey]
          );
        }
      }
    }
  }
}
