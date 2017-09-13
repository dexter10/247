jQuery(document).ready(function($) {

	"use strict";
			
	$("#ship-to-different-address-checkbox").on("change", function() {
		if (this.checked) {
			billingToShipping();
		}
	});
	
	$("#place_order").on("click", function() {
		if( !$('.shipping_address').is(':visible') ) {
			billingToShipping();
		}
	});

	function billingToShipping() {
		$("[name='shipping_first_name']").val($("[name='billing_first_name']").val());
		$("[name='shipping_last_name']").val($("[name='billing_last_name']").val());
		$("[name='shipping_address_1']").val($("[name='billing_address_1']").val());
		$("[name='shipping_address_house']").val($("[name='billing_address_house']").val());
		$("[name='shipping_address_house_ext']").val($("[name='billing_address_house_ext']").val());
		$("[name='shipping_city']").val($("[name='billing_city']").val());
		$("[name='shipping_postcode']").val($("[name='billing_postcode']").val());
	}

});