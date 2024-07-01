<?php

declare(strict_types=1);

namespace Belluno\Magento2\Model\Ui\Pix;

use Belluno\Magento2\Gateway\Config\ConfigPix;
use Magento\Checkout\Model\ConfigProviderInterface;

class ConfigProvider implements ConfigProviderInterface
{
    const CODE = ConfigPix::METHOD;

    /** @var ConfigPix */
    private $_config;

    public function __construct(ConfigPix $config)
    {
        $this->_config = $config;
    }

    /**
     * Retrieve array of checkout configuration.
     * @return array
     */
    public function getConfig()
    {
        return [
            "bellunopix" => [
                ConfigPix::METHOD => [
                    "expiration_days" => $this->_config->getExpirationDays(),
                    "tax_document" => $this->_config->getUseTaxDocumentCapture(),
                ],
            ],
        ];
    }
}