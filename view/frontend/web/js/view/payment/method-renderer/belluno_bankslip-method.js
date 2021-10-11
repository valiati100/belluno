define([
  "Magento_Checkout/js/view/payment/default",
  "Magento_Checkout/js/model/quote",
  "jquery",
  "Belluno_Magento2/js/view/payment/lib/jquery/jquery.mask",
], function (Component, quote, $, mask) {
  'use strict';

  return Component.extend({
    defaults: {
      active: false,
      template: 'Belluno_Magento2/payment/belluno_bankslip-form',
      clientDocument: ""
    },

    initObservable() {
      this._super().observe([
        "active",
        "clientDocument"
      ]);
      return this;
    },

    getCode() {
      return 'bellunobankslip';
    },

    getData() {
      var data = {
        'method': this.getCode(),
        "additional_data": {
          "client_document": this.clientDocument(),
          "billing_address": quote.billingAddress()['street'][0],
          "billing_number": quote.billingAddress()['street'][1],
          "billing_district": quote.billingAddress()['street'][2],
          "expiration_days": this.getExpirationDays()
        }
      };
      data['additional_data'] = _.extend(data['additional_data'], this.additionalData);
      return data;
    },

    initialize: function () {
      
      var self = this;

      var cliD = $(`#${self.getCode()}_client_document`);
      this._super();
      
      this.clientDocument.subscribe(function (value) {
        var typeMaskVat = value.replace(/\D/g, "").length <= 11 ? "000.000.000-009" : "00.000.000/0000-00";
        cliD.mask(typeMaskVat, { clearIfNotMatch: true });
      });
      
    },

    isActive() {
      var active = this.getCode() === this.isChecked();
      this.active(active);
      return active;
    },

    validate: function () {
      var $form = $('#belluno-form');
      return $form.validation() && $form.validation('isValid');
    },

    getExpirationDays: function () {
      return window.checkoutConfig.bellunobankslip[this.getCode()].expiration_days;
    },

    getTaxDocument() {
      return window.checkoutConfig.bellunobankslip[this.getCode()].tax_document;
    },

  });
});