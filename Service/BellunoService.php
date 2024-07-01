<?php

declare(strict_types=1);

namespace Belluno\Magento2\Service;

use Belluno\Magento2\Service\Connector;

class BellunoService
{
    protected $_token;

    protected $_host;

    protected $_logger;

    /** @var Connector */
    protected $_connector;

    /**
     * BellunoService constructor
	 * @param $logger
     * @param string $token
     * @param string $host
     */
    public function __construct(
        $logger,
        string $token,
        string $host
    ) {
        $this->_logger = $logger;
        $this->_token = $token;
        $this->_host = $host;
    }

    /**
     * Function to return connector
     * @return Connector
     */
    public function getConnector()
    {
        if(!$this->_connector instanceof Connector) {
            $this->_connector = new Connector($this->_token, $this->_logger);
        }
        return $this->_connector;
    }

    /**
     * Function to connect with API
     * @param $function
     * @param $params
     * @return mixed
     */
    public function doRequest($function, $params)
    {
        $params["host"] = $this->_host;
        return $this->getConnector()->doRequest($function, $params);
    }
}
