<?php

declare(strict_types=1);

namespace Belluno\Magento2\Gateway\Request;

use Belluno\Magento2\Gateway\Helper\SubjectReader;
use Magento\Payment\Gateway\Request\BuilderInterface;
use Belluno\Magento2\Model\Validations\CredentialsValidator;

class CreditCardShippingnRequest implements BuilderInterface
{
    /** Shipping Block */
    const SHIPPING = "shipping";

    /** Postal Code */
    const POSTAL_CODE = "postalCode";

    /** Street */
    const STREET = "street";

    /** Number */
    const NUMBER = "number";

    /** City */
    const CITY = "city";

    /** State */
    const STATE = "state";

    /** @var SubjectReader */
    private $_subjectReader;

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
    public function build(array $buildSubject)
    {
        $paymentDO = $this->_subjectReader->readPayment($buildSubject);
        $order = $paymentDO->getOrder();
        $shippingAddress = $order->getShippingAddress();
        $payment = $paymentDO->getPayment();
        $additionalInformation = $payment->getAdditionalInformation();
        $result = [];

        $postalCode = $shippingAddress->getPostcode();
        $postalCode = preg_replace("/[^0-9]/is", "", $postalCode);
        $postalCode = substr_replace($postalCode, "-", 5, 0);
        $street = isset($additionalInformation["billing_street"]) ? $additionalInformation["billing_street"] : "";
        $number = isset($additionalInformation["billing_number"]) ? $additionalInformation["billing_number"] : "";
        $city = $shippingAddress->getCity();
        $state = $shippingAddress->getRegionCode();

        $this->_credentialValidator->validateShipping(
            $postalCode,
            $street,
            $number,
            $city,
            $state
        );

        $result = [
            "transaction" => [
                self::SHIPPING => [
                    self::POSTAL_CODE => $postalCode,
                    self::STREET => $street,
                    self::NUMBER => $number,
                    self::CITY => $city,
                    self::STATE => $state,
                ],
            ],
        ];

        return $result;
    }
}