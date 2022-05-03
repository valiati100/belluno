/**
 * Copyright © Magento, Inc. All rights reserved.
 * See COPYING.txt for license details.
 */

/* @api */
define([
  "jquery",
  "Magento_Payment/js/model/credit-card-validation/cvv-validator",
  "Magento_Payment/js/model/credit-card-validation/credit-card-number-validator",
  "Magento_Payment/js/model/credit-card-validation/expiration-date-validator/expiration-year-validator",
  "Magento_Payment/js/model/credit-card-validation/expiration-date-validator/expiration-month-validator",
  "Magento_Payment/js/model/credit-card-validation/credit-card-data",
  "mage/translate"
], function ($, cvvValidator, creditCardNumberValidator, yearValidator, monthValidator, creditCardData) {
  "use strict";

  $(".payment-method-content input[type='tel']").on("keyup", function () {
    if ($(this).val() < 0) {
      $(this).val($(this).val().replace(/^-/, ""));
    }
  });

  var creditCartTypes = {
    "VI": [new RegExp('^4[0-9]{12}(?:[0-9]{3})?$'), new RegExp("^[0-9]{3}$"), true],
    "MC": [new RegExp('^(603136|603689|608619|606200|603326|605919|608783|607998|603690|604891|603600|603134|608718|603680|608710|604998)|(5[1-5][0-9]{14}|2221[0-9]{12}|222[2-9][0-9]{12}|22[3-9][0-9]{13}|2[3-6][0-9]{14}|27[01][0-9]{13}|2720[0-9]{12})$'), new RegExp("^[0-9]{3}$"), true],
    "HI": [new RegExp("^(6370950000000005|637095|637609|637599|637612|637568|63737423|63743358)"), new RegExp("^[0-9]{3}$"), true],
    "HC": [new RegExp("^(38[0-9]{17}|60[0-9]{14})$"), new RegExp("^[0-9]{3}$"), true],
    "ELO": [new RegExp("^(401178|401179|431274|438935|451416|457393|457631|457632|504175|627780|636297|636368|(506699|5067[0-6]\\d|50677[0-8])|(50900\\d|5090[1-9]\\d|509[1-9]\\d{2})|65003[1-3]|(65003[5-9]|65004\\d|65005[0-1])|(65040[5-9]|6504[1-3]\\d)|(65048[5-9]|65049\\d|6505[0-2]\\d|65053[0-8])|(65054[1-9]|6505[5-8]\\d|65059[0-8])|(65070\\d|65071[0-8])|65072[0-7]|(65090[1-9]|65091\\d|650920)|(65165[2-9]|6516[6-7]\\d)|(65500\\d|65501\\d)|(65502[1-9]|6550[3-4]\\d|65505[0-8]))[0-9]{10,12}"), new RegExp("^[0-9]{3}$"), true],
    "CAB": [new RegExp("^(60420[1-9]|6042[1-9][0-9]|6043[0-9]{2}|604400)"), new RegExp("^[0-9]{3}$"), true]
  }

  $.each({

    "validate-cc-type-belluno": [
      /**
       * Validate credit card number is for the correct credit card type.
       * @param {String} value - credit card number
       * @param {*} element - element contains credit card number
       * @param {*} params - selector for credit card type
       * @return {Boolean}
       */
      function (value, element, params) {
        var ccType;

        if (value && params) {
          ccType = $(params).val();
          value = value.replace(/\s/g, "").replace(/\-/g, "");
          if (creditCartTypes[ccType] && creditCartTypes[ccType][0]) {
            return creditCartTypes[ccType][0].test(value);
          } else if (creditCartTypes[ccType] && !creditCartTypes[ccType][0]) {
            return true;
          }
        }

        return false;
      },
      $.mage.__("Credit card number does not match credit card type.")
    ],

    "validate-card-type-belluno": [
      /**
       * Validate credit type belluno is for the correct credit card type.
       * @param {String} value - credit card number
       * @param {*} element - element contains credit card number
       * @param {*} params - selector for credit card type
       * @return {Boolean}
       */
      function (number, item, allowedTypes) {
        var cardInfo,
          i,
          l;

        if (!creditCardNumberValidator(number).isValid) {
          return false;
        }

        cardInfo = creditCardNumberValidator(number).card;

        for (i = 0, l = allowedTypes.length; i < l; i++) {
          if (cardInfo.title == allowedTypes[i].type) { //eslint-disable-line eqeqeq
            return true;
          }
        }

        return false;
      },
      $.mage.__("Please enter a valid credit card type number.")
    ],

    "validate-card-number-belluno": [
      /**
       * Validate credit card number based on mod 10
       * @param {*} number - credit card number
       * @return {Boolean}
       */
      function (number) {
        return creditCardNumberValidator(number).isValid;
      },
      $.mage.__("Please enter a valid credit card number.")
    ],

    "validate-card-cvv-belluno": [

      /**
       * Validate cvv
       *
       * @param {String} cvv - card verification value
       * @return {Boolean}
       */
      function (cvv) {
        var maxLength = creditCardData.creditCard ? creditCardData.creditCard.code.size : 3;

        return cvvValidator(cvv, maxLength).isValid;
      },
      $.mage.__("Please enter a valid credit card verification number.")
    ],

  }, function (i, rule) {
    rule.unshift(i);
    $.validator.addMethod.apply($.validator, rule);
  });
});