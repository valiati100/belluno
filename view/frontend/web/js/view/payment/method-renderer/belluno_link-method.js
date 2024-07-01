define([
    "Magento_Checkout/js/view/payment/default",
    "Magento_Checkout/js/model/quote",
    "jquery",
    "Belluno_Magento2/js/view/payment/lib/jquery/jquery.mask"
], function(Component, quote, $, mask) {
    'use strict';

    return Component.extend({
        defaults: {
            active: false,
            template: 'Belluno_Magento2/payment/belluno_link-form'
        },
        initObservable() {
            this._super().observe(["active"]);
            return this;
        },
        getCode() {
            return 'bellunolink';
        },
        getData() {
            var data = {'method': this.getCode()};
            return data;
        },
        initialize: function() {
            var self = this;
            this._super();
        },
        isActive() {
            var active = this.getCode() === this.isChecked();
            this.active(active);
            return active;
        }
    });
});