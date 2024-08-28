jQuery(document).ready(function($) {

	$('form.variations_form').on('found_variation', function(event, variation) {

		const $lowestPricePlaceholder = $( '.wc-price-history.prior-price-value .wc-price-history.prior-price.lowest-raw-value'),
		  lowestInVariation = variation._wc_price_history_lowest_price;

		 if ( $lowestPricePlaceholder.length ) {
			 $lowestPricePlaceholder.text( formatPrice( lowestInVariation ) );
		 }

		 console.log( variation );
	});

	function formatPrice(price) {

		let formattedPrice = parseFloat( price ).toFixed( wc_price_history_frontend.decimals );

		formattedPrice = formattedPrice.replace(',', wc_price_history_frontend.thousand_separator);
		formattedPrice = formattedPrice.replace('.', wc_price_history_frontend.decimal_separator);

		return formattedPrice;
	}
});