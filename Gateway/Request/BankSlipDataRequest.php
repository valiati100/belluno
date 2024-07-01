<?php

declare(strict_types=1);

namespace Belluno\Magento2\Gateway\Request;

use Belluno\Magento2\Model\Validations\DocumentsValidator;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Framework\Exception\CouldNotSaveException;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Magento\Customer\Api\CustomerRepositoryInterface;
use Belluno\Magento2\Gateway\Helper\SubjectReader;
use Magento\Quote\Api\Data\CartInterface;
use Magento\Store\Model\ScopeInterface;
use Belluno\Magento2\Model\Validations\CredentialsValidator;
use DateInterval;
use DateTime;

class BankSlipDataRequest implements BuilderInterface
{
    /** Bank Slip block */
    const BANKSLIP = "bankslip";

    /** Value of order */
    const VALUE = "value";

    /** Day of expiration */
    const DATE_EXPIRATION = "due";

    /** Code document doc */
    const DOCUMENT_CODE = "document_code";

    /** CLient */
    const CLIENT = "client";

    /** Client Name */
    const NAME = "name";

    /** Client Document */
    const DOCUMENT = "document";

    /** Client Email */
    const EMAIL = "email";

    /** Client Phone */
    const PHONE = "phone";

    /** @var SubjectReader */
    protected $_subjectReader;

    /** @var DocumentsValidator */
    private $_documentsValidator;

    /** @var CartInterface */
    protected $_cart;

    /** @var ScopeConfigInterface */
    protected $_scopeConfig;

    /** @var CustomerRepositoryInterface */
    protected $_customer;

    /** @var CredentialsValidator */
    private $_credentialValidator;

    public function __construct(
        SubjectReader $subjectReader,
        DocumentsValidator $documentsValidator,
        CartInterface $cart,
        ScopeConfigInterface $scopeConfig,
        CustomerRepositoryInterface $customer,
        CredentialsValidator $credentialsValidator
    ) {
        $this->_subjectReader = $subjectReader;
        $this->_documentsValidator = $documentsValidator;
        $this->_cart = $cart;
        $this->_scopeConfig = $scopeConfig;
        $this->_customer = $customer;
        $this->_credentialValidator = $credentialsValidator;
    }

    /**
     * {@inheritdoc}
     */
    public function build(array $buildSubject)
    {
        $paymentDO = $this->_subjectReader->readPayment($buildSubject);
        $order = $paymentDO->getOrder();
        $payment = $paymentDO->getPayment();
        $additionalInformation = $payment->getAdditionalInformation();
        $quote = $this->_subjectReader->getQuote();
        $customerId = $quote->getCustomerId();
        $shipping = $customerId != null ? $order->getShippingAddress() : $quote->getShippingAddress();

        $taxDocument = $this->getUseTaxDocumentCapture();
        if(!$taxDocument) {
            $clientDocument = $customerId != null ? $this->getTaxVat($customerId) : $shipping->getVatId();
        }else {
            $clientDocument = $additionalInformation["client_document"];
            $this->validations($clientDocument);
        }

        $dateExp = $this->generateDateExpiration($additionalInformation["expiration_days"]);
        $docCode = $order->getOrderIncrementId();

        $name = $quote->getCustomerFirstname() . " " . $quote->getCustomerLastname();
        $email = $quote->getCustomerEmail();
        $phone = $shipping->getTelephone();

        $this->_credentialValidator->validateClientData($name, $email, $phone);

        $result = [
            self::BANKSLIP => [
                self::VALUE => $order->getGrandTotalAmount(),
                self::DATE_EXPIRATION => $dateExp,
                self::DOCUMENT_CODE => $docCode,
                self::CLIENT => [
                    self::NAME => $name,
                    self::DOCUMENT => $clientDocument,
                    self::EMAIL => $email,
                    self::PHONE => $phone,
                ],
            ],
        ];

        return $result;
    }

    /**
     * Function to validate client document
     * @param $document
     * @return bool
     */
    public function validations($document)
    {
        $validateDocument = $this->_documentsValidator->validateDocument($document);
        if($validateDocument != true) {
            throw new CouldNotSaveException(__("Client Document is Invalid!"));
        }
    }

    /**
     * Function to generate day expiration
     * @param $daysExpiration
     * @return string
     */
    public function generateDateExpiration($daysExpiration)
    {
        $today = getdate();
        $date = new DateTime($today["year"] . "-" . $today["mon"] . "-" . $today["mday"]);
        $date->add(new DateInterval("P" . $daysExpiration . "D"));
        return $date->format("Y-m-d");
    }

    /**
     * Get if you use document capture on the form.
     * @return string
     */
    public function getUseTaxDocumentCapture()
    {
        $storeId = $this->_cart->getStoreId();
        $pathPattern = "payment/belluno_config/bellunobankslip/tax_document";

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
    public function getTaxVat($customerId)
    {
        $customer = $this->_customer->getById($customerId);
        return $customer->getTaxvat();
    }
}