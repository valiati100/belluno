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

class PixDataRequest implements BuilderInterface
{
    /** Pix block */
    const PIX = "transaction";

    /** Value of order */
    const VALUE = "value";

    /** Day of expiration */
    const DAYS_EXPIRATION = "due_days";

    /** CLient */
    const CLIENT = "client";

    /** Client Name */
    const NAME = "client_name";

    /** Client Document */
    const DOCUMENT = "client_document";

    /** Client Email */
    const EMAIL = "client_email";

    /** Client Phone */
    const PHONE = "client_phone";

    /** Client Cellphone */
    const CELLPHONE = "client_cellphone";
	
	/** Order Detail */
    const DETAIL = "details";

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
		
		$detail = $order->getOrderIncrementId();
		
        $taxDocument = $this->getUseTaxDocumentCapture();
        if(!$taxDocument) {
            $clientDocument = $customerId != null ? $this->getTaxVat($customerId) : $shipping->getVatId();
        }else {
            $clientDocument = $additionalInformation["client_document"];
            $this->validations($clientDocument);
        }

        $dayExp = (int)$additionalInformation["expiration_days"];
        $name = $quote->getCustomerFirstname() . " " . $quote->getCustomerLastname();
        $email = $quote->getCustomerEmail();
        $phone = $shipping->getTelephone();

        $this->_credentialValidator->validateClientData($name, $email, $phone);

        $result = [
            self::PIX => [
                self::VALUE => $order->getGrandTotalAmount(),
                self::DAYS_EXPIRATION => $dayExp,
                self::NAME => $name,
                self::DOCUMENT => $clientDocument,
                self::EMAIL => $email,
                self::PHONE => $phone,
                self::CELLPHONE => $phone,
				self::DETAIL => $detail,
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
        $validateDocument = $this->_documentsValidator->validateDocument(
            $document
        );
        if($validateDocument != true) {
            throw new CouldNotSaveException(__("Client Document is Invalid!"));
        }
    }

    /**
     * Get if you use document capture on the form.
     * @return string
     */
    public function getUseTaxDocumentCapture()
    {
        $storeId = $this->_cart->getStoreId();
        $pathPattern = "payment/belluno_config/bellunopix/tax_document";

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
