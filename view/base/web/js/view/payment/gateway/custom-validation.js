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
], function($, cvvValidator, creditCardNumberValidator, yearValidator, monthValidator, creditCardData) {
    "use strict";

    $(".payment-method-content input[type='tel']").on("keyup", function() {
        if ($(this).val() < 0) {
            $(this).val($(this).val().replace(/^-/, ""));
        }
    });

    var creditCartTypes = {
        "VI": [new RegExp('^4[0-9]{12}([0-9]{3})?$'), new RegExp("^[0-9]{3}$"), true],
        "MC": [new RegExp('^5([1-5]\\d*)?$'), new RegExp("^[0-9]{3}$"), true],
        "HI": [new RegExp("^(637095|637612|637599|637609|637568)"), new RegExp("^[0-9]{3}$"), true],
        "HC": [new RegExp("^(606282|3841)[0-9]{5,}$"), new RegExp("^[0-9]{3}$"), true],
        "ELO": [new RegExp("^(636368|438935|504175|451416|636297|5067|4576|4011|50904|50905|50906)"), new RegExp("^[0-9]{3}$"), true],
        "CAB": [new RegExp("(60420[1-9]|6042[1-9][0-9]|6043[0-9]{2}|604400)"), new RegExp("^[0-9]{3}$"), true]
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
            function(value, element, params) {
                var ccType;
                return true;

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
            function(number, item, allowedTypes) {
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
            function(number) {
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
            function(cvv) {
                var maxLength = creditCardData.creditCard ? creditCardData.creditCard.code.size : 3;

                return cvvValidator(cvv, maxLength).isValid;
            },
            $.mage.__("Please enter a valid credit card verification number.")
        ],

    }, function(i, rule) {
        rule.unshift(i);
        $.validator.addMethod.apply($.validator, rule);
    });
});