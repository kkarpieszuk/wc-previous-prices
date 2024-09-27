jQuery( document ).ready( function($) {

	const $importButton = $( '#wc-price-history-import-file-button' );

	$importButton.on( 'click', function() {
		const fileInput = $( '#wc-price-history-import-file' );
		const settingsOverride = $( '#wc-price-history-import-settings-override' ).is( ':checked' );
		const productOverride = $( '#wc-price-history-import-product-override' ).is( ':checked' );

		if ( ! fileInput.val() ) {
			alert( wc_price_history_import.import_error );
			return;
		}

		const formData = new FormData();
		formData.append( 'wc_price_history_import_file', fileInput[0].files[0] );
		formData.append( 'wc_price_history_import_settings_override', settingsOverride );
		formData.append( 'wc_price_history_import_product_override', productOverride );
		formData.append( 'action', 'wc_price_history_import_file' );
		formData.append( 'security', wc_price_history_import.nonce );

		$.ajax( {
			url: ajaxurl,
			type: 'POST',
			data: formData,
			processData: false,
			contentType: false,
			success: function( response ) {
				if ( response.success ) {
					alert( wc_price_history_import.success );
				} else {
					alert( wc_price_history_import.error );
				}
			},
			error: function( response ) {
				alert( wc_price_history_import.error );
			}
		} );
	} );
} );