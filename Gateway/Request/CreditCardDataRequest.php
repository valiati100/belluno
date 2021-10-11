<?php

declare(strict_types=1);

namespace Belluno\Magento2\Gateway\Request;

use Magento\Framework\Exception\CouldNotSaveException;
use Belluno\Magento2\Model\Validations\CreditCardValidator;
use Belluno\Magento2\Model\Validations\DocumentsValidator;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Belluno\Magento2\Gateway\Helper\SubjectReader;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Belluno\Magento2\Model\Validations\Encrypt;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\ScopeInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Belluno\Magento2\Model\Validations\CredentialsValidator;

/**
 * CreditCardDataInformationRequest class
 */
class CreditCardDataRequest implements BuilderInterface {

  /** Transaction Block */
  const TRANSACTION = 'transaction';

  /** Value of order */
  const VALUE = 'value';

  /** Rule Capture */
  const CAPTURE = 'capture';

  /** Card Hash */
  const CARD_HASH = 'card_hash';

  /** Card Holder Name */
  const CARD_HOLDER_NAME = 'cardholder_name';

  /** Card Holder Document */
  const CARD_HOLDER_DOCUMENT = 'cardholder_document';

  /** Card Holder Cellphone */
  const CARD_HOLDER_CELLPHONE = 'cardholder_cellphone';

  /** Card Holder Birth */
  const CARD_HOLDER_BIRTH = 'cardholder_birth';

  /** Brand Card */
  const BRAND_CARD = 'brand';

  /** Installment Number */
  const INSTALLMENT_NUMBER = 'installment_number';

  /** Visitor Id (Anti-fraud system) */
  const VISITOR_ID = 'visitor_id';

  /** Payer Ip */
  const PAYER_IP = 'payer_ip';

  /** Client Name */
  const CLIENT_NAME = 'client_name';

  /** Client Document */
  const CLIENT_DOCUMENT = 'client_document';

  /** Client Email */
  const CLIENT_EMAIL = 'client_email';

  /** Client Cellphone */
  const CLIENT_CELLPHONE = 'client_cellphone';

  /** Order Detail */
  const DETAIL = 'details';

  /** @var SubjectReader */
  protected $_subjectReader;

  /** @var CreditCardValidator */
  private $_cardValidator;

  /** @var DocumentsValidator */
  private $_documentsValidator;

  /** @var Encrypt */
  private $_cardHash;

  /** @var CartInterface */
  private $_cart;

  /** @var ScopeConfigInterface */
  private $_scopeConfig;

  /** @var CredentialsValidator */
  private $_credentialValidator;

  /** @var CustomerRepositoryInterface */
  protected $_customer;

  public function __construct(
    SubjectReader $subjectReader,
    CreditCardValidator $cardValidator,
    DocumentsValidator $documentsValidator,
    Encrypt $cardHash,
    CartInterface $cart,
    ScopeConfigInterface $scopeConfig,
    CredentialsValidator $credentialsValidator,
    CustomerRepositoryInterface $customer
  ) {
    $this->_subjectReader = $subjectReader;
    $this->_cardValidator = $cardValidator;
    $this->_documentsValidator = $documentsValidator;
    $this->_cardHash = $cardHash;
    $this->_cart = $cart;
    $this->_scopeConfig = $scopeConfig;
    $this->_credentialValidator = $credentialsValidator;
    $this->_customer = $customer;
  }

  /**
   * {@inheritdoc}
   */
  public function build(array $buildSubject) {
    $paymentDO = $this->_subjectReader->readPayment($buildSubject);
    $payment = $paymentDO->getPayment();
    $order = $paymentDO->getOrder();
    $shipping = $order->getShippingAddress();
    $additionalInformation = $payment->getAdditionalInformation();
    $quote = $this->_subjectReader->getQuote();
    $customerId = $quote->getCustomerId();

    $detail = $order->getOrderIncrementId();

    $cellphone = $additionalInformation['cardholder_cellphone'];
    $isValid = $this->_credentialValidator->validateCellphone($cellphone);
    if ($isValid == false) {
      throw new CouldNotSaveException(__('Card Holder Cellphone is Invalid!'));
    }
    $dateBirth = $additionalInformation['cardholder_birth'];
    $isValid = $this->_credentialValidator->validateDateBirth($dateBirth);
    if ($isValid == false) {
      throw new CouldNotSaveException(__('Card Holder Birth is Invalid!'));
    }

    $name = $quote->getCustomerFirstname() . ' ' . $quote->getCustomerLastname();
    $email = $quote->getCustomerEmail();
    $phone = $cellphone;

    $this->_credentialValidator->validateClientData($name, $email, $phone);

    $taxDocument = $this->getUseTaxDocumentCapture();
    if (!$taxDocument) {
      $clientDocument = $this->getTaxVat($customerId);
    } else {
      $clientDocument = $additionalInformation['client_document'];
    }
    $cardHolderDocument = $additionalInformation['cardholder_document'];

    $this->validations(
      $clientDocument,
      $cardHolderDocument,
      $additionalInformation['card_number'],
      $additionalInformation['cc_cvv']
    );

    $ruleCapture = $this->getRuleCapture();

    $clientDocument = $this->formatCnpjCpf($clientDocument);
    $cardHolderDocument = $this->formatCnpjCpf($cardHolderDocument);

    $totalValue = $order->getGrandTotalAmount();
    $dataValue = $this->getValueWithInterest($totalValue, $additionalInformation['cc_installments']);
    if ($dataValue['total'] > $totalValue) {
      $totalValue = $dataValue['total'];
    }

    $result = [
      self::TRANSACTION => [
        self::VALUE => $totalValue,
        self::CAPTURE => $ruleCapture,
        self::CARD_HASH => $this->_cardHash->encrypt($additionalInformation),
        self::CARD_HOLDER_NAME => $additionalInformation['card_name'],
        self::CARD_HOLDER_DOCUMENT => $cardHolderDocument,
        self::CARD_HOLDER_CELLPHONE => $cellphone,
        self::CARD_HOLDER_BIRTH => $dateBirth,
        self::BRAND_CARD => $this->_cardValidator->getCardType($additionalInformation['card_number']),
        self::INSTALLMENT_NUMBER => $additionalInformation['cc_installments'],
        self::VISITOR_ID => $additionalInformation['visitor_id'],
        self::PAYER_IP => $order->getRemoteIp(),
        self::CLIENT_NAME => $name,
        self::CLIENT_DOCUMENT => $clientDocument,
        self::CLIENT_EMAIL => $email,
        self::CLIENT_CELLPHONE => $phone,
        self::DETAIL => $detail
      ]
    ];

    return $result;
  }

  /**
   * Function to validate client document, card holder document, card number and card cvv
   * @param string $clientDocument
   * @param string $cardHolderDocument
   * @param string $cardNumber
   * @param string $cardCvv
   */
  protected function validations($clientDocument, $cardHolderDocument, $cardNumber, $cardCvv) {
    $validateClientDocument = $this->_documentsValidator->validateDocument($clientDocument);
    if ($validateClientDocument != true) {
      throw new CouldNotSaveException(__('Client Document is Invalid!'));
    }

    $validateCardHolderDocument = $this->_documentsValidator->validateDocument($cardHolderDocument);
    if ($validateCardHolderDocument != true) {
      throw new CouldNotSaveException(__('Card Holder Document is Invalid!'));
    }

    $validateCardNumber = $this->_cardValidator->validCreditCard($cardNumber);
    if ($validateCardNumber['valid'] != true) {
      throw new CouldNotSaveException(__('Card Number is Invalid!'));
    }

    $validateCardCvv = $this->_cardValidator->validCvc($cardCvv, $validateCardNumber['type']);
    if ($validateCardCvv != true) {
      throw new CouldNotSaveException(__('Card CVV is Invalid!'));
    }
  }

  /**
   * Function to get rule of capture
   * @return string
   */
  public function getRuleCapture() {
    $storeId = $this->_cart->getStoreId();
    $rule = $this->_scopeConfig->getValue(
      'payment/bellunopayment/payment_action',
      ScopeInterface::SCOPE_STORE,
      $storeId
    );

    if ($rule == 'authorize_capture') {
      return '1';
    } else if ($rule == 'authorize') {
      return '2';
    } else {
      throw new \Exception(__("Error Processing Request, Rule of Capture not Selected!"), 1);
    }
  }

  /**
   * Get if you use document capture on the form.
   * @return string
   */
  public function getUseTaxDocumentCapture() {
    $storeId = $this->_cart->getStoreId();
    $pathPattern = 'payment/belluno_config/bellunopayment/tax_document';

    return $this->_scopeConfig->getValue(
      $pathPattern,
      ScopeInterface::SCOPE_STORE,
      $storeId
    );
  }

  /**
   * Function to get TaxVat
   * @param $customerId
   * @return string
   */
  public function getTaxVat($customerId) {
    $customer = $this->_customer->getById($customerId);
    return $customer->getTaxvat();
  }

  /**
   * Function to format cpf and cnpj
   */
  function formatCnpjCpf($document) {
    $cnpj_cpf = preg_replace("/\D/", '', $document);

    if (strlen($cnpj_cpf) === 11) {
      return preg_replace("/(\d{3})(\d{3})(\d{3})(\d{2})/", "\$1.\$2.\$3-\$4", $cnpj_cpf);
    } else {
      return preg_replace("/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/", "\$1.\$2.\$3/\$4-\$5", $cnpj_cpf);
    }
  }

  /**
   * Function to get total value with interest
   * @param string $totalValue
   * @param string $intallmentNumber
   * @return array
   */
  public function getValueWithInterest($totalValue, $installmentNumber): array {
    $interest = $this->_scopeConfig->getValue('payment/belluno_config/bellunopayment/installments', \Magento\Store\Model\ScopeInterface::SCOPE_STORE);

    $interest = json_decode($interest, true);
    $valueInterest = 0;
    $interestPercent = 0;

    $i = 1;
    foreach ($interest as $value) {
      if ($i == $installmentNumber) {
        if ($value['from_qty'] > $valueInterest) {
          $interestPercent = $value['from_qty'];
        }
      }
      $i++;
    }

    $valueInterest = (($interestPercent / 100) * $totalValue);
    $totalValue = $totalValue + (($interestPercent / 100) * $totalValue);

    return [
      'total' => $totalValue,
      'valueInterest' => $valueInterest
    ];
  }
}
