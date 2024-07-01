<?php

declare(strict_types=1);

namespace Belluno\Magento2\Model\Validations;

class CreditCardValidator
{
    protected static $cards = [
        "visa" => [
            "type" => "visa",
            //'pattern' => '/^4[0-9]{15}$/',
            "pattern" => ['/^4[0-9]{15}$/', '/^\d{16}$/'],
            "length" => [13, 16],
            "cvcLength" => [3],
            "luhn" => true,
        ],
        "mastercard" => [
            "type" => "mastercard",
            "pattern" => '/^(5[1-5]\d{4}|677189)\d{10}$/',
            "length" => [16],
            "cvcLength" => [3],
            "luhn" => true,
        ],
        "elo" => [
            "type" => "elo",
            "pattern" =>
                "/^4011(78|79)|^43(1274|8935)|^45(1416|7393|763(1|2))|^50(4175|6699|67[0-6][0-9]|677[0-8]|9[0-8][0-9]{2}|99[0-8][0-9]|999[0-9])|^627780|^63(6297|6368|6369)|^65(0(0(3([1-3]|[5-9])|4([0-9])|5[0-1])|4(0[5-9]|[1-3][0-9]|8[5-9]|9[0-9])|5([0-2][0-9]|3[0-8]|4[1-9]|[5-8][0-9]|9[0-8])|7(0[0-9]|1[0-8]|2[0-7])|9(0[1-9]|[1-6][0-9]|7[0-8]))|16(5[2-9]|[6-7][0-9])|50(0[0-9]|1[0-9]|2[1-9]|[3-4][0-9]|5[0-8]))/",
            "length" => [16],
            "cvcLength" => [3],
            "luhn" => true,
        ],
        "hipercard" => [
            "type" => "hipercard",
            "pattern" => '/^(606282\d{10}(\d{3})?)|(3841\d{15})$/',
            "length" => [13, 16, 19],
            "cvcLength" => [3],
            "luhn" => true,
        ],
        "hiper" => [
            "type" => "hiper",
            "pattern" => "/^(637095|637612|637599|637609|637568)/",
            "length" => [12, 13, 14, 15, 16, 17, 18, 19],
            "cvcLength" => [3],
            "luhn" => true,
        ],
        "cabal" => [
            "type" => "cabal",
            "pattern" => "/(60420[1-9]|6042[1-9][0-9]|6043[0-9]{2}|604400)/",
            "length" => [16],
            "cvcLength" => [3],
            "luhn" => true,
        ],
    ];

    public function validCreditCard($number, $type = null)
    {
        $ret = [
            "valid" => false,
            "number" => "",
            "type" => "",
        ];

        if(empty($type)) {
            $type = $this->creditCardType($number);
        }

        $this->validCard($number, $type);

        if(array_key_exists($type, self::$cards) && $this->validCard($number, $type)) {
            return [
                "valid" => true,
                "number" => $number,
                "type" => $type,
            ];
        }

        return $ret;
    }

    public function validCvc($cvc, $type)
    {
        return ctype_digit($cvc) && array_key_exists($type, self::$cards) && $this->validCvcLength($cvc, $type);
    }

    /**
     * Function to get card type
     * @param $number
     * @return string $type
     */
    public function getCardType($number)
    {
        $arrayTypes = [
            "1" => "mastercard",
            "2" => "visa",
            "3" => "elo",
            "4" => "hipercard",
            "5" => "cabal",
            "6" => "hiper",
        ];

        $res = $this->validCreditCard($number);
        $type = $res["type"];
        $resultTypeCard = "";

        foreach($arrayTypes as $key => $value) {
            if($type == $value) {
                $resultTypeCard = $key;
            }
        }

        return $resultTypeCard;
    }

    //Internal functions
    protected function creditCardType($number)
    {
        foreach(self::$cards as $type => $card) {
            if(is_array($card["pattern"])) {
                foreach($card["pattern"] as $pattern) {
                    if(preg_match($pattern, $number)) {
                        return $type;
                    }
                }
            }else {
                if(preg_match($card["pattern"], $number)) {
                    return $type;
                }
            }
        }
        return "";
    }

    protected function validCard($number, $type)
    {
        return $this->validPattern($number, $type) && $this->validLength($number, $type);
    }

    protected function validPattern($number, $type)
    {
        if(is_array(self::$cards[$type]["pattern"])) {
            foreach(self::$cards[$type]["pattern"] as $pattern) {
                if(preg_match($pattern, $number)) {
                    return true;
                }
            }
        }else {
            return preg_match(self::$cards[$type]["pattern"], $number);
        }

        return false;
        //return preg_match(self::$cards[$type]['pattern'], $number);
    }

    protected function validLength($number, $type)
    {
        foreach(self::$cards[$type]["length"] as $length) {
            if(strlen($number) == $length) {
                return true;
            }
        }
        return false;
    }

    protected function validCvcLength($cvc, $type)
    {
        foreach(self::$cards[$type]["cvcLength"] as $length) {
            if(strlen($cvc) == $length) {
                return true;
            }
        }
        return false;
    }
}
