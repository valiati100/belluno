<?php

declare(strict_types=1);

namespace Belluno\Magento2\Model\Validations;

use Magento\Framework\Exception\CouldNotSaveException;
use DateTime;

class CredentialsValidator {

  /**
   * Function to validate number of cellphone
   * @param string $cellphone
   * @return bool
   */
  public function validateCellphone(string $cellphone) {
    if (preg_match('/^\([1-9]{2}\) (?:[2-8]|9[1-9])[0-9]{3}\-[0-9]{4}$/', $cellphone)) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Function to validate date birth
   * @param string $date
   * @return bool
   */
  public function validateDateBirth(string $date) {
    if ($this->validateDate($date, 'd/m/Y')) {
      return true;
    } else {
      return false;
    }
  }

  /**
   * Function assistant for validateDateBirth
   * @param string $date
   * @param $format
   * @return bool
   */
  private function validateDate($date, $format = 'Y-m-d H:i:s') {
    $d = DateTime::createFromFormat($format, $date);
    return $d && $d->format($format) == $date;
  }

  /**
   * Function to validate shipping
   * @param string $postalCode
   * @param string $street
   * @param string $number
   * @param string $city
   * @param string $state
   */
  public function validateShipping(string $postalCode, string $street, string $number, string $city, string $state) {
    if ($postalCode == '' || $postalCode == ' ') {
      throw new CouldNotSaveException(__('Shipping postal code was not filled!'));
    } else if ($street == '' || $street == ' ') {
      throw new CouldNotSaveException(__('Shipping street was not filled!'));
    } else if ($number == '' || $number == ' ') {
      throw new CouldNotSaveException(__('Shipping number was not filled!'));
    } else if ($city == '' || $city == ' ') {
      throw new CouldNotSaveException(__('Shipping city was not filled!'));
    } else if ($state == '' || $state == ' ') {
      throw new CouldNotSaveException(__('Shipping state was not filled!'));
    }
  }

  /**
   * Function to validate billing
   * @param string $postalCode
   * @param string $street
   * @param string $number
   * @param string $city
   * @param string $state
   * @param string $district
   * if there is no district pass as '0'
   */
  public function validateBilling(string $postalCode, string $street, string $number, string $city, string $state, string $district) {
    if ($postalCode == '' || $postalCode == ' ') {
      throw new CouldNotSaveException(__('Billing postal code was not filled!'));
    } else if ($street == '' || $street == ' ') {
      throw new CouldNotSaveException(__('Billing street was not filled!'));
    } else if ($number == '' || $number == ' ') {
      throw new CouldNotSaveException(__('Billing number was not filled!'));
    } else if ($city == '' || $city == ' ') {
      throw new CouldNotSaveException(__('Billing city was not filled!'));
    } else if ($state == '' || $state == ' ') {
      throw new CouldNotSaveException(__('Billing state was not filled!'));
    } else if ($district == '' || $district == ' ') {
      throw new CouldNotSaveException(__('Billing district was not filled!'));
    }
  }

  /**
   * Function to validate client informations
   * @param string $name
   * @param string $email
   * @param string $phone
   */
  public function validateClientData(string $name, string $email, string $phone) {
    if ($name == '' || $name == ' ') {
      throw new CouldNotSaveException(__('Customer name was not filled!'));
    }
    $result = strstr($email, '@');
    if ($result == false) {
      throw new CouldNotSaveException(__('Customer email not filled in or invalid!'));
    }
    if (!strlen($phone) >= 9) {
      throw new CouldNotSaveException(__('Customer cellphone not filled in or invalid!'));
    }
  }
}
