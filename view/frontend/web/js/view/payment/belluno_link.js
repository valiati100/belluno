define([
	'uiComponent',
	'Magento_Checkout/js/model/payment/renderer-list'
],
function(Component, rendererList) {
	'use strict';

	rendererList.push({
		type: 'bellunolink',
		component: 'Belluno_Magento2/js/view/payment/method-renderer/belluno_link-method'
	});

	return Component.extend({});
});