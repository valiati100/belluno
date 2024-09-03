<?php

declare(strict_types=1);

namespace Belluno\Magento2\Service;

use Laminas\Http\Client;
use Laminas\Http\Client\Adapter\Exception\TimeoutException;
use Magento\Framework\Exception\CouldNotSaveException;

class Connector
{
    protected $_client;
    protected $token;
    protected $logger;

    /**
     * BellunoService constructor
     * @param string $token
     */
    public function __construct(string $token, $logger)
    {
        $this->token = $token;
		$this->logger = $logger;
    }

    /**
     * Function to get client
     * @return Client
     */
    public function getClient()
    {
        if(!$this->_client instanceof Client) {
            $this->_client = new Client();
            $options = [
                "maxredirects" => 0,
                "timeout" => 30,
                "sslverifypeer" => false,
            ];
            $this->_client->setOptions($options);
            $this->_client->setHeaders([
                "Authorization" => "Bearer " . $this->token,
                "Content-Type" => "application/json",
            ]);
        }

        return $this->_client;
    }

    /**
     * @param Client $client
     * @return Client
     */
    public function setClient(Client $client)
    {
        return $this->_client = $client;
    }

    /**
     * @param $function
     * @param $params
     * @return mixed
     */
    public function doRequest($function, $params)
    {
        try {
            $method = isset($params["method"]) ? $params["method"] : "post";
            $data = $params["data"];
            $this->getClient()->setMethod($method);
            $this->getClient()->setUri($params["host"] . $function);
			if(null != $data && count(json_decode($data, true)) > 0) {
				$this->getClient()->setRawBody($data);
				$this->logger->info(json_encode($data));
			}
            $response = $this->getClient()->send();

            if($params["method"] == "get" && strpos($params["host"] . $function, "card_hash_key") === false) {
                return $response;
            }
			
        }catch (TimeoutException $e) {
			$this->logger->info($e->getMessage());
            throw new \Exception($e->getMessage());
        }
		
		$this->logger->info(json_encode($response->getBody()));
        if($response->getStatusCode() < 200 || $response->getStatusCode() > 299) {
            $responseErrorMessage = json_decode($response->getBody(), true);
            $errorsMessages = "";
            if(isset($responseErrorMessage["errors"])) {
                foreach($responseErrorMessage["errors"] as $detail) {
                    foreach($detail as $message) {
                        $errorsMessages = $errorsMessages . "\n" . $message;
                    }
                }
            }

            if($errorsMessages != "") {
                throw new CouldNotSaveException(__($errorsMessages));
            }else {
                throw new CouldNotSaveException(__('Something didn\'t go well. Check your information!'));
            }
        }

        return $response->getBody();
    }
}