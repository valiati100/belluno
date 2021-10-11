define([
  "Magento_Payment/js/view/payment/cc-form",
  "Magento_Checkout/js/model/quote",
  "jquery",
  "Magento_Catalog/js/price-utils",
  "Belluno_Magento2/js/view/payment/gateway/custom-validation",
  "Belluno_Magento2/js/view/payment/lib/jquery/jquery.mask",
  "Magento_Payment/js/model/credit-card-validation/credit-card-data",
  "Magento_Payment/js/model/credit-card-validation/validator",
  "ko",
  "jquery/ui",
], function (Component, quote, $, priceUtils, custom, mask, creditCardData) {
  'use strict';

  var visitor = '';

  return Component.extend({
    defaults: {
      active: false,
      template: 'Belluno_Magento2/payment/belluno_payment-form',
      clientDocument: "",
      cardHolderDocument: "",
      cardHolderCellphone: "",
      cardHolderBirth: "",
      cardNumber: "",
      cardName: "",
      ccExpMonth: "",
      ccExpYear: "",
      ccCvv: "",
      ccInstallments: "",
    },

    initObservable() {
      this._super().observe([
        "active",
        "clientDocument",
        "cardHolderDocument",
        "cardHolderCellphone",
        "cardHolderBirth",
        "cardNumber",
        "cardName",
        "ccExpMonth",
        "ccExpYear",
        "ccCvv",
        "ccInstallments",
      ]);
      return this;
    },

    getCode() {
      return 'bellunopayment';
    },

    getData() {
      var data = {
        'method': this.getCode(),
        "additional_data": {
          "client_document": this.clientDocument(),
          "cardholder_document": this.cardHolderDocument(),
          "cardholder_cellphone": this.cardHolderCellphone(),
          "cardholder_birth": this.cardHolderBirth(),
          "card_number": this.creditCardNumber().replace(/[^\d]+/g, ''),
          "card_name": this.cardName(),
          "cc_exp_month": this.ccExpMonth(),
          "cc_exp_year": this.ccExpYear(),
          "cc_cvv": this.ccCvv(),
          "cc_installments": this.ccInstallments(),
          "visitor_id": visitor,
          "shipping_street": quote.shippingAddress()['street'][0],
          "shipping_number": quote.shippingAddress()['street'][1],
          "billing_street": quote.billingAddress()['street'][0],
          "billing_number": quote.billingAddress()['street'][1],
        }
      };
      data['additional_data'] = _.extend(data['additional_data'], this.additionalData);
      return data;
    },

    initialize: function () {
      var self = this;

      var cliD = $(`#${self.getCode()}_client_document`);
      var tel = $(`#${self.getCode()}_cardholder_cellphone`);
      var bitth = $(`#${self.getCode()}_cardholder_birth`);
      var cardHD = $(`#${self.getCode()}_cardholder_document`);
      var inputCc = $(`#${self.getCode()}_card_number`);
      this._super();

      this.selectedCardType.subscribe((value) => {
        creditCardData.selectedCardType = value;
      })

      inputCc.mask("0000 0000 0000 0000");
      bitth.mask("00/00/0000", { clearIfNotMatch: true });
      tel.mask("(00) 00000-0000", { clearIfNotMatch: true });

      this.clientDocument.subscribe(function (value) {
        var typeMaskVat = value.replace(/\D/g, "").length <= 11 ? "000.000.000-009" : "00.000.000/0000-00";
        cliD.mask(typeMaskVat, { clearIfNotMatch: true });
      });

      this.cardHolderDocument.subscribe(function (value) {
        var typeMaskVat = value.replace(/\D/g, "").length <= 11 ? "000.000.000-009" : "00.000.000/0000-00";
        cardHD.mask(typeMaskVat, { clearIfNotMatch: true });
      });
    },

    loadBelluno() {
      if (document.querySelector(".payment-method")) {
        const s2 = document.createElement("script");
        s2.id = "belluno";
        s2.async = false;

        document.getElementsByTagName("body")[0].appendChild(s2);
        document.getElementById("belluno").innerHTML =
          `var __kdt = __kdt || [];
        __kdt.push({"public_key": "${this.getPubKeyKonduto()}"}); // A chave pública identifica a sua loja  
          (function() {   
            var kdt = document.createElement('script');   
            kdt.id = 'kdtjs'; kdt.type = 'text/javascript';   
            kdt.async = true;    kdt.src = 'https://i.k-analytix.com/k.js';   
            var s = document.getElementsByTagName('body')[0];   
            s.parentNode.insertBefore(kdt, s);
             })();`;

        var visitorID;
        (function () {
          var period = 300;
          var limit = 20 * 1e3;
          var nTry = 0;
          var intervalID = setInterval(

            function () {
              var clear = limit / period <= ++nTry;

              if ((typeof (Konduto) !== "undefined") && (typeof (Konduto.getVisitorID) !== "undefined")) {
                visitorID = window.Konduto.getVisitorID();
                visitor = visitorID;
                clear = true;
              }
              if (clear) {
                clearInterval(intervalID);
              }
            },
            period);

        })(visitorID);
      }
    },

    getCcAvailableTypes: function () {
      return window.checkoutConfig.bellunopayment[this.getCode()].ccavailabletypes;
    },

    getPubKeyKonduto() {
      return window.checkoutConfig.bellunopayment[this.getCode()].pub_key_konduto;
    },

    getCcAvailableTypesValues: function () {
      return _.map(this.getCcAvailableTypes(), function (value, key) {
        return {
          'value': key,
          'type': value
        };
      });
    },

    getTaxDocument() {
      return window.checkoutConfig.bellunopayment[this.getCode()].tax_document;
    },

    getIcons(type) {
      return window.checkoutConfig.bellunopayment[this.getCode()].icons.hasOwnProperty(type) ?
        window.checkoutConfig.bellunopayment[this.getCode()].icons[type]
        : false;
    },

    getCcMonthsValues() {
      this.loadBelluno();
      return _.map(window.checkoutConfig.bellunopayment[this.getCode()].months, function (value, key) {
        return {
          'value': key,
          'month': value
        };
      });
    },

    getCcYearsValues() {
      return _.map(window.checkoutConfig.bellunopayment[this.getCode()].years, function (value, key) {
        return {
          'value': key,
          'year': value
        };
      });
    },

    isActive() {
      var active = this.getCode() === this.isChecked();
      this.active(active);
      return active;
    },

    validate: function () {
      var $form = $('#bellunocc-form');
      return $form.validation() && $form.validation('isValid');
    },

    getInstall() {
      var valor = quote.totals().base_grand_total;
      Object.size = function (obj) {
        var size = 0,
          key;
        for (key in obj) {
          if (obj.hasOwnProperty(key)) size++;
        }
        return size;
      };
      var info_interest = window.checkoutConfig.bellunopayment[this.getCode()].installments;
      var min_installment = window.checkoutConfig.bellunopayment[this.getCode()].min_installment;
      var max_installment = window.checkoutConfig.bellunopayment[this.getCode()].max_installment;

      if (min_installment === '0' || min_installment == null) {
        min_installment = 1;
      }

      var json_parcelas = {};
      var count = 1;

      var max_div = (valor / min_installment);
      max_div = parseInt(max_div);

      if (max_div > max_installment) {
        max_div = max_installment;
      } else {
        if (max_div > 12) {
          max_div = 12;
        }
      }
      var limite = max_div;

      _.each(info_interest, function (key, value) {
        if (count <= max_div) {
          key = info_interest[value];
          if (key > 0) {
            var taxa = key / 100;
            var parcela = ((valor * taxa) + valor) / count;
            var total_parcelado = parcela * count;
            var juros = key;
            if (parcela > 5 && parcela > min_installment) {
              json_parcelas[count] = {
                "parcela": priceUtils.formatPrice(parcela, quote.getPriceFormat()),
                "total_parcelado": priceUtils.formatPrice(total_parcelado, quote.getPriceFormat()),
                "total_juros": priceUtils.formatPrice(total_parcelado - valor, quote.getPriceFormat()),
                "juros": juros,
              };
            }
          } else {
            if (valor > 0 && count > 0) {
              json_parcelas[count] = {
                "parcela": priceUtils.formatPrice((valor / count), quote.getPriceFormat()),
                "total_parcelado": priceUtils.formatPrice(valor, quote.getPriceFormat()),
                "total_juros": 0,
                "juros": 0,
              };
            }
          }
        }
        count++;
      });

      if (Object.size(json_parcelas) === 0) {
        json_parcelas[1] = {
          "parcela": priceUtils.formatPrice(valor, quote.getPriceFormat()),
          "total_parcelado": priceUtils.formatPrice(valor, quote.getPriceFormat()),
          "total_juros": 0,
          "juros": 0,
        };
      }

      _.each(json_parcelas, function (key, value) {
        if (key > limite) {
          delete json_parcelas[key];
        }
      });
      return json_parcelas;
    },

    getInstallments() {
      var temp = _.map(this.getInstall(), function (value, key) {

        if (value["juros"] === 0) {
          var info_interest = "sem juros";
        } else {
          var info_interest = "com juros total de " + value["total_juros"];
        }
        var inst = key + " x " + value["parcela"] + " no valor total de " + value["total_parcelado"] + " " + info_interest;
        return {
          "value": key,
          "installments": inst
        };

      });
      var newArray = [];
      for (var i = 0; i < temp.length; i++) {

        if (temp[i].installments != "undefined" && temp[i].installments != undefined) {
          newArray.push(temp[i]);
        }
      }

      return newArray;
    },

  });
});

