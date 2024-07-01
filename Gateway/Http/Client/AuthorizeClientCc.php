<?php

declare(strict_types=1);

namespace Belluno\Magento2\Gateway\Http\Client;

use Magento\Payment\Gateway\Http\ClientInterface;
use Magento\Payment\Gateway\Http\TransferInterface;
use Belluno\Magento2\Helper\Helper;
use Magento\Quote\Api\Data\CartInterface;

class AuthorizeClientCc implements ClientInterface
{
    /** @var Helper */
    private $_helper;

    /** @var CartInterface */
    private $_cart;

    public function __construct(Helper $helper, CartInterface $cart)
    {
        $this->_helper = $helper;
        $this->_cart = $cart;
    }

    public function placeRequest(TransferInterface $transferObject)
    {
        $request = $transferObject->getBody();
        $request = json_encode($request);

        $params = [
            "data" => $request,
            "method" => "post",
            "host" => "",
        ];
        $function = "/transaction";

        $response = $this->_helper
            ->getBellunoService($this->_cart->getStoreId())
            ->doRequest($function, $params);
        $response = json_decode($response, true);

        return $response;
    }
}