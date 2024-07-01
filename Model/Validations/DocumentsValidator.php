<?php

declare(strict_types=1);

namespace Belluno\Magento2\Model\Validations;

class DocumentsValidator
{
    /**
     * Function to redirect validate document
     * @param $document
     * @return bool
     */
    public function validateDocument($document)
    {
        $document = preg_replace("/[^0-9]/is", "", $document);

        if(strlen($document) == 11) {
            return $this->validateDocumentCpf($document);
        }elseif(strlen($document) == 14) {
            return $this->validateDocumentCnpj($document);
        }
    }

    /**
     * Function to validate document cpf
     * @param $document
     * @return bool
     */
    protected function validateDocumentCpf($cpf)
    {
        if(strlen($cpf) != 11) {
            return false;
        }

        if(preg_match('/(\d)\1{10}/', $cpf)) {
            return false;
        }

        for($t = 9; $t < 11; $t++) {
            for($d = 0, $c = 0; $c < $t; $c++) {
                $d += $cpf[$c] * ($t + 1 - $c);
            }
            $d = ((10 * $d) % 11) % 10;
            if($cpf[$c] != $d) {
                return false;
            }
        }
        return true;
    }

    /**
     * Function to validate document cnpj
     * @param $document
     * @return bool
     */
    protected function validateDocumentCnpj($cnpj)
    {
        if(strlen($cnpj) != 14) {
            return false;
        }

        if(preg_match('/(\d)\1{13}/', $cnpj)) {
            return false;
        }

        for($i = 0, $j = 5, $soma = 0; $i < 12; $i++) {
            $soma += $cnpj[$i] * $j;
            $j = $j == 2 ? 9 : $j - 1;
        }

        $resto = $soma % 11;

        if($cnpj[12] != ($resto < 2 ? 0 : 11 - $resto)) {
            return false;
        }

        for($i = 0, $j = 6, $soma = 0; $i < 13; $i++) {
            $soma += $cnpj[$i] * $j;
            $j = $j == 2 ? 9 : $j - 1;
        }

        $resto = $soma % 11;

        return $cnpj[13] == ($resto < 2 ? 0 : 11 - $resto);
    }
}