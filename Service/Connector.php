<?php

declare(strict_types=1);

namespace Belluno\Magento2\Service;

use Psr\Log\LoggerInterface;
use Laminas\Http\Client;
use Laminas\Http\Client\Adapter\Exception\TimeoutException;
use Magento\Framework\Exception\CouldNotSaveException;

class Connector {

  protected $_client;

  protected $token;

  /** @var LoggerInterface */
  protected $logger;

  /**
   * BellunoService constructor
   * @param string $token
   * @param LoggerInterface $logger
   */
  public function __construct(
    string $token,
    LoggerInterface $logger
  ) {
    $this->token = $token;
    $this->logger = $logger;
  }

  /**
   * Function to get client
   * @return Client
   */
  public function getClient() {
    if (!$this->_client instanceof Client) {
      $this->_client = new Client();
      $options = [
        'maxredirects' => 0,
        'timeout' => 30
      ];
      $this->_client->setOptions($options);
      $this->_client->setHeaders([
        'Authorization' => 'Bearer ' . $this->token,
        'Content-Type' => 'application/json'
      ]);
    }

    return $this->_client;
  }

  /**
   * @param Client $client
   * @return Client
   */
  public function setClient(Client $client) {
    return $this->_client = $client;
  }

  /**
   * @param $function
   * @param $params
   * @return mixed
   */
  public function doRequest($function, $params) {
    try {
      $method = isset($params['method']) ? $params['method'] : 'post';

      $data = ($params['data']);
      $this->getClient()->setMethod($method);
      $this->getClient()->setRawBody($data);
      $this->getClient()->setUri($params['host'] . $function);

      $this->logger->notice($data);

      $response = $this->getClient()->send();
    } catch (TimeoutException $e) {
      $this->logger->error($e);
      throw new \Exception($e->getMessage());
    }

    $this->logger->notice(json_encode($response->getBody()));
    if ($response->getStatusCode() < 200 || $response->getStatusCode() > 299) {
      $responseErrorMessage = json_decode($response->getBody(), true);

      $errorsMessages = "";
      if (isset($responseErrorMessage['errors'])) {
        foreach ($responseErrorMessage['errors'] as $detail) {
          $errorsMessages = $errorsMessages . "\n" . $detail['detail'];
        }
      }
      
      throw new CouldNotSaveException(__('Something didn\'t go well. Check your information!'));
    }

    return $response->getBody();
  }
}
