jQuery( document ).ready( function( $ ) {
    var pricingPane = $( '#woocommerce-product-data' ),
        productType = $( 'select#product-type' ).val();
    if( pricingPane.length ){
        pricingPane.find( '.pricing' ).addClass( 'show_if_course' ).end()
            .find( '.inventory_tab' ).addClass( 'hide_if_course' ).end()
            .find( '.shipping_tab' ).addClass( 'hide_if_course' ).end()
            .find( '.attributes_tab' ).addClass( 'hide_if_course' )
        ;

        if ( productType === 'course' ) {
            pricingPane.find( '.pricing' ).show();
        }
    }

    // Make tax fields visible on course type
    var $tax_field_group = $( '._tax_status_field' ).parent( '.options_group' );

    $tax_field_group.addClass( 'show_if_course' );

    $( window ).on( 'load', function( e ) {
        e.preventDefault();
        if ( $( '#product-type' ).val() == 'course' ) {
            $tax_field_group.show();
        }
    });

	/**
	 * Initialize Select2 for a given selector and options.
	 *
	 * @since 5.2.0
	 *
	 * @param {string} selector jQuery selector for the element(s).
	 * @param {Object} options  Select2 options.
	 *
	 * @return {void}
	 */
	function initSelect2( selector, options ) {
		$( selector ).show().select2( options );
	}

	// Select2 options for regular and full width.
	const select2RegularOptions = {
		closeOnSelect: false,
		allowClear: true,
		scrollAfterSelect: false,
		placeholder: '',
	};
	const select2FullOptions = {
		width: '100%',
		closeOnSelect: false,
		allowClear: true,
		scrollAfterSelect: false,
		placeholder: '',
	};

	// Initialize Select2 for related courses and groups.
	initSelect2( '.ld-woocommerce-select2.regular-width', select2RegularOptions );
	initSelect2( '.ld-woocommerce-select2.full-width', select2FullOptions );

	// Re-initialize Select2 when WooCommerce variations are loaded.
	$( document ).on( 'woocommerce_variations_loaded', function() {
		initSelect2( '#variable_product_options .ld-woocommerce-select2.regular-width', select2RegularOptions );
		initSelect2( '#variable_product_options .ld-woocommerce-select2.full-width', select2FullOptions );
	} );
} );
