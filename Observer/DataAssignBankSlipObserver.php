<?php

declare(strict_types=1);

namespace Belluno\Magento2\Observer;

use Magento\Framework\Event\Observer;
use Magento\Framework\Event\ObserverInterface;
use Magento\Payment\Observer\AbstractDataAssignObserver;
use Magento\Quote\Api\Data\PaymentInterface;

class DataAssignBankSlipObserver extends AbstractDataAssignObserver implements ObserverInterface
{
    /** @const Method Name Block */
    const METHOD_NAME = "method_name";

    /** @const Method Name */
    const METHOD_NAME_TYPE = "Belluno - Bank Slip";

    /** @const Client Document */
    const CLIENT_DOCUMENT = "client_document";

    /** @const Billing Address */
    const BILLING_ADDRESS = "billing_address";

    /** @const Billing Number */
    const BILLING_NUMBER = "billing_number";

    /** @const Billing District */
    const BILLING_DISTRICT = "billing_district";

    /** @const Expiration Days */
    const EXPIRATION_DAYS = "expiration_days";

    /** @var array */
    protected $addInformationList = [
        self::CLIENT_DOCUMENT,
        self::BILLING_ADDRESS,
        self::BILLING_NUMBER,
        self::BILLING_DISTRICT,
        self::EXPIRATION_DAYS,
    ];

    /**
     * @param Observer $observer
     * @return void
     */
    public function execute(Observer $observer)
    {
        $data = $this->readDataArgument($observer);
        $additionalData = $data->getData(PaymentInterface::KEY_ADDITIONAL_DATA);
        if(!is_array($additionalData)) {
            return;
        }

        $paymentInfo = $this->readPaymentModelArgument($observer);
        $paymentInfo->setAdditionalInformation(
            self::METHOD_NAME,
            self::METHOD_NAME_TYPE
        );

        foreach($this->addInformationList as $addInformationKey) {
            if(isset($additionalData[$addInformationKey])) {
                if($additionalData[$addInformationKey]) {
                    $paymentInfo->setAdditionalInformation(
                        $addInformationKey,
                        $additionalData[$addInformationKey]
                    );
                }
            }
        }
    }
}