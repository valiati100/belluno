<?php

declare(strict_types=1);

namespace Belluno\Magento2\Helper;

use Belluno\Magento2\Service\BellunoService;
use Magento\Framework\App\Config\ScopeConfigInterface;
use Magento\Store\Model\ScopeInterface;

class Helper
{
    /** @var BellunoService */
    private $_bellunoService;

    /** @var ScopeConfigInterface */
    private $_scopeConfig;

    public function __construct(
        ScopeConfigInterface $scopeConfig
    ) {
        $this->_scopeConfig = $scopeConfig;
    }

    /**
     * Function to get new instance of BellunoService
     * @param string $storeId
     * @return BellunoService
     */
    public function getBellunoService($storeId)
    {
        $token = $this->_scopeConfig->getValue(
            "payment/belluno_config/belluno_auth/authentication",
            ScopeInterface::SCOPE_STORE,
            $storeId
        );

        $environment = $this->_scopeConfig->getValue(
            "payment/belluno_config/belluno_config/environment",
            ScopeInterface::SCOPE_STORE,
            $storeId
        );
		
		$host = "https://ws-sandbox.bellunopag.com.br";
        if($environment == "production") {
            $host = "https://api.belluno.digital";
        }

        $this->_bellunoService = new BellunoService(
            $this->getLog('belluno'),
            $token,
            $host
        );

        return $this->_bellunoService;
    }
	
	public function getLog($fileName) {
		if(class_exists('\Zend\Log\Writer\Stream')) {
			$writer = new \Zend\Log\Writer\Stream(BP . '/var/log/'.$fileName.'.log');
			$logger = new \Zend\Log\Logger();
		}
		elseif(class_exists('\Laminas\Log\Writer\Stream')) {
			$writer = new \Laminas\Log\Writer\Stream(BP . '/var/log/'.$fileName.'.log');
			$logger = new  \Laminas\Log\Logger();
		}
		elseif(class_exists('\Zend_Log_Writer_Stream')) {
			$writer = new \Zend_Log_Writer_Stream(BP . '/var/log/'.$fileName.'.log');
			$logger = new \Zend_Log();
		}
		
		$logger->addWriter($writer);
		return $logger;
	}
}