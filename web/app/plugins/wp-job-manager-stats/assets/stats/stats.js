/**
 * Stats
 *
 * @since 2.7.0
 */
( function( window, undefined ) {

	window.wp = window.wp || {};

	var document = window.document;
	var $ = window.jQuery;
	var wp = window.wp;
	var $document = $(document);

	/**
	 * Log.
	 * @since 2.7.0
	 */
	wpjmStats.log = function( stat ) {
		wp.ajax.post( 'wpjms_stat_' + stat, {
			post_id : wpjmStats.post_id,
		} )
		.done( function( data ) {
			if ( window.console && wpjmStats.isDebug ) {
				 window.console.log( data );
			}
			return;
		} )
		.fail( function( data ) {
			if ( window.console && wpjmStats.isDebug ) {
				 window.console.log( data );
			}
			return;
		} );
	};

	/**
	 * Log Apply Button Click.
	 * @since 2.7.0
	 */
	wpjmStats.logApplyButtonClick = function() {
		$( 'body' ).on( 'click', '.application_button.button', function(e) {
			var that = $( this );
			if ( ! that.hasClass( 'wpjms_clicked' ) ) {
				wp.ajax.post( 'wpjms_stat_apply_button_click', {
					post_id : wpjmStats.post_id,
				} )
				.done( function( data ) {
					that.addClass( 'wpjms_clicked' );
					if ( window.console && wpjmStats.isDebug ) {
						 window.console.log( data );
					}
					return;
				} )
				.fail( function( data ) {
					if ( window.console && wpjmStats.isDebug ) {
						 window.console.log( data );
					}
					return;
				} );
			}
		});
	};

	/**
	 * Log Apply Form Submit.
	 * @since 2.7.0
	 */
	wpjmStats.logApplyFormSubmit = function() {
		var logStat = function( wrap ) {
			if( ! wrap.hasClass( 'wpjms_submitted' ) ) {
				wp.ajax.post( 'wpjms_stat_apply_form_submit', {
					post_id : wpjmStats.post_id,
				} )
				.done( function( data ) {
					wrap.addClass( 'wpjms_submitted' );
					if ( window.console && wpjmStats.isDebug ) {
						 window.console.log( data );
					}
					return;
				} )
				.fail( function( data ) {
					if ( window.console && wpjmStats.isDebug ) {
						 window.console.log( data );
					}
					return;
				} );
			}
		};

		// Contact Form.
		$( 'body' ).on( 'submit', '.application_details form', function() {
			logStat( $( this ).closest( '.application_details' ) );
		});

		// Ninja Form.
		if ( typeof nfRadio !== 'undefined' ) {
			nfRadio.channel( 'forms' ).on( 'submit:response', function() {
				logStat( $( this ).closest( '.application_details' ) );
			});
		}
	};


	/***********************************
	 * Wait for DOM ready.
	 *
	 * @since 2.7.0
	 ***********************************/
	$document.ready( function() {

		$.each( wpjmStats.stats, function( index, value ) {
			switch ( value ) {
				case 'apply_button_click':
					wpjmStats.logApplyButtonClick();
					break;
				case 'apply_form_submit':
					wpjmStats.logApplyFormSubmit();
					break;
				default:
					wpjmStats.log( value );
			}
		});

	} );

}( window ) );
