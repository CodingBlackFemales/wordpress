/* global wpforms_captcha */

/**
 * WPForms Custom Captcha function.
 *
 * @since 1.8.7
 */
const WPFormsCaptcha = window.WPFormsCaptcha || ( function( document, window, $ ) {
	/**
	 * Public functions and properties.
	 *
	 * @since 1.8.7
	 *
	 * @type {Object}
	 */
	const app = {

		/**
		 * Start the engine.
		 *
		 * @since 1.8.7
		 */
		init() {
			$( app.ready );

			window.addEventListener( 'elementor/popup/show', function() {
				app.ready();
			} );
		},

		/**
		 * Initialize once the DOM is fully loaded.
		 *
		 * @since 1.8.7
		 */
		ready() {
			// Populate random equations for math captchas.
			$( '.wpforms-captcha-equation' ).each( function() {
				const $captcha = $( this ).parent(),
					calc = wpforms_captcha.cal[ Math.floor( Math.random() * wpforms_captcha.cal.length ) ],
					n1 = app.randomNumber( wpforms_captcha.min, wpforms_captcha.max ),
					n2 = app.randomNumber( wpforms_captcha.min, wpforms_captcha.max );

				$captcha.find( 'span.n1' ).text( n1 );
				$captcha.find( 'input.n1' ).val( n1 );
				$captcha.find( 'span.n2' ).text( n2 );
				$captcha.find( 'input.n2' ).val( n2 );
				$captcha.find( 'span.cal' ).text( calc );
				$captcha.find( 'input.cal' ).val( calc );
				$captcha.find( 'input.a' ).attr( {
					'data-cal': calc,
					'data-n1': n1,
					'data-n2': n2,
				} );
			} );

			// Reload after OptinMonster is loaded.
			document.addEventListener( 'om.Html.append.after', function() {
				app.ready();
			} );

			// Load custom validation.
			app.loadValidation();
		},

		/**
		 * Custom captcha validation for jQuery Validation.
		 *
		 * @since 1.8.7
		 */
		loadValidation() {
			// Only load if the jQuery validation library exists.
			if ( typeof $.fn.validate === 'undefined' ) {
				return;
			}

			$.validator.addMethod( 'wpf-captcha', function( value, element, param ) {
				const $ele = $( element );

				let a, res;

				if ( 'math' === param ) {
					// Math captcha.
					const n1 = Number( $ele.attr( 'data-n1' ) ),
						n2 = Number( $ele.attr( 'data-n2' ) ),
						cal = $ele.attr( 'data-cal' ),
						calculations = [ '-', '+', '*' ],
						operators = {
							'+' : ( num1, num2 ) => num1 + num2,
							'-' : ( num1, num2 ) => num1 - num2,
							'*' : ( num1, num2 ) => num1 * num2,
						};

					a = Number( value );
					res = false;

					if ( ! calculations.includes( cal ) ) {
						return false;
					}

					res = operators[ cal ]( n1, n2 );
				} else {
					// Question answer captcha.
					a = value.toString().toLowerCase().trim();
					res = $ele.attr( 'data-a' ).toString().toLowerCase().trim();
				}

				return this.optional( element ) || a === res;
			}, $.validator.format( wpforms_captcha.errorMsg ) );
		},

		/**
		 * Generate random whole number.
		 *
		 * @since 1.8.7
		 *
		 * @param {number} min Min number.
		 * @param {number} max Max number.
		 *
		 * @return {number} Random number.
		 */
		randomNumber( min, max ) {
			return Math.floor( Math.random() * ( Number( max ) - Number( min ) + 1 ) ) + Number( min );
		},
	};

	// Provide access to public functions/properties.
	return app;
}( document, window, jQuery ) );

// Initialize.
WPFormsCaptcha.init();
