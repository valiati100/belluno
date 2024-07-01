<?php

namespace Belluno\Magento2\Model\Config\Source;

use Magento\Framework\Option\ArrayInterface;
use Magento\Email\Model\Template\Config;

class EmailTemplate implements ArrayInterface
{
    /**
     * @var emailTemplateConfig
    */
    private $emailTemplateConfig;

    public function __construct(Config $emailTemplateConfig)
    {
        $this->emailTemplateConfig = $emailTemplateConfig;
    }

    /**
     * Return array of email templates
     *
     * @return array
     */
    public function toOptionArray()
    {
		$collection = $this->emailTemplateConfig->getAvailableTemplates();
        $options = [];
        foreach ($collection as $template) {
            $options[] = [
                'value' => $template['value'],
                'label' => $template['label']
            ];
        }
        return $options;
    }
}