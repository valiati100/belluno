/**
 * Copyright © Tezus E-commerce. All rights reserved.
 *
 * @author Lucas Silva <lucas.silva@tezus.com.br>
 * See LICENSE.txt for license details.
 */
define([
  "jquery",
  "mageUtils"
], function ($, utils) {
  "use strict";
  var types = [
    {
      title: 'Discover',
      type: 'discover',
      pattern: '^6(?:011|5[0-9]{2})[0-9]{12}$',
      gaps: [4, 8, 12],
      lengths: [16],
      code: {
        name: 'CID',
        size: 3
      }
    },
    {
      title: 'Visa',
      type: 'VI',
      pattern: '^4\\d*$',
      gaps: [4, 8, 12],
      lengths: [16],
      code: {
        name: 'CVV',
        size: 3
      }
    },
    {
      title: 'Mastercard',
      type: 'MC',
      pattern: '^5([1-5]\\d*)?$',
      gaps: [4, 8, 12],
      lengths: [16],
      code: {
        name: 'CVC',
        size: 3
      }
    },
    {
      title: 'American Express',
      type: 'amex',
      pattern: '^3([47]\\d*)?$',
      isAmex: true,
      gaps: [4, 10],
      lengths: [15],
      code: {
        name: 'CID',
        size: 4
      }
    },
    {
      title: 'Diners',
      type: 'diners',
      pattern: '^3((0([0-5]\\d*)?)|[689]\\d*)?$',
      gaps: [4, 10],
      lengths: [14],
      code: {
        name: 'CVV',
        size: 3
      }
    },
    {
      title: 'Hipercard',
      type: 'HC',
      pattern: '^(606282|3841)[0-9]{5,}$',
      gaps: [4, 8, 12],
      lengths: [13, 16, 19],
      code: {
        name: 'CVV',
        size: 3
      }
    },
    {
      title: 'Elo',
      type: 'ELO',
      payment_id: '16',
      pattern: '^(4011(78|79)|43(1274|8935)|45(1416|7393|763(1|2))|50(4175|6699|67[0-7][0-9]|9000)|50(9[0-9][0-9][0-9])|627780|63(6297|6368)|650(03([^4])|04([0-9])|05(0|1)|05([7-9])|06([0-9])|07([0-9])|08([0-9])|4([0-3][0-9]|8[5-9]|9[0-9])|5([0-9][0-9]|3[0-8])|9([0-6][0-9]|7[0-8])|7([0-2][0-9])|541|700|720|727|901)|65165([2-9])|6516([6-7][0-9])|65500([0-9])|6550([0-5][0-9])|655021|65505([6-7])|6516([8-9][0-9])|65170([0-4]))',
      gaps: [4, 8, 12],
      lengths: [16],
      code: {
        name: 'CVV',
        size: 3
      }
    },
    {
      title: 'HIPER',
      type: 'HI',
      pattern: '^(637095|637612|637599|637609|637568)',
      gaps: [4, 8, 12],
      lengths: [12, 13, 14, 15, 16, 17, 18, 19],
      code: {
        name: 'CVV',
        size: 3
      }
    },
    {
      title: 'JCB',
      type: 'jcb',
      payment_id: '19',
      pattern: '^(3(?:088|096|112|158|337|5(?:2[89]|[3-8][0-9]))\\d{12})$',
      gaps: [4, 8, 12],
      lengths: [12, 13, 14, 15, 16, 17, 18, 19],
      code: {
        name: 'CVV',
        size: 3
      }
    },
    {
      title: 'CAB',
      type: 'CAB',
      payment_id: '3',
      pattern: '(60420[1-9]|6042[1-9][0-9]|6043[0-9]{2}|604400)',
      gaps: [4, 8, 12],
      lengths: [16],
      code: {
        name: 'CVV',
        size: 3
      }
    }
  ];

  var mixin = {
    getCardTypes(cardNumber) {
      var i, value,
        result = [];
      if (utils.isEmpty(cardNumber)) {
        return result;
      }
      if (cardNumber === "") {
        return $.extend(true, {}, types);
      }
      for (i = 0; i < types.length; i++) {
        value = types[i];
        if (new RegExp(value.pattern).test(cardNumber)) {
          result.push($.extend(true, {}, value));
        }
      }
      if (result.length > 1) {
        return [result[1]];
      }
      return result;
    }
  };

  return function (target) {
    return mixin;
  };
});