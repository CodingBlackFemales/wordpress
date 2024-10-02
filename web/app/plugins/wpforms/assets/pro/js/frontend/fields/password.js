/* global pwsL10n */

/**
 * Password field.
 *
 * @since 1.6.7
 */

'use strict';

window.WPFormsPasswordField = window.WPFormsPasswordField || ( function( document, window, $ ) {

	var app = {

		/**
		 * Toggle the hide message depending on if user hiding a from.
		 *
		 * @since 1.6.7
		 *
		 * @param {string} value   Password value.
		 * @param {Object} element Password field.
		 *
		 * @return {number} Strength result.
		 */
		// eslint-disable-next-line complexity
		passwordStrength( value, element ) {
			const $input = $( element );
			const $field = $input.closest( '.wpforms-field' );
			let $strengthResult = $field.find( '.wpforms-pass-strength-result' );

			// Don't check the password strength for empty fields which is set as not required.
			if ( $input.val().trim() === '' && ! $input.hasClass( 'wpforms-field-required' ) ) {
				$strengthResult.remove();
				$input.removeClass( 'wpforms-error-pass-strength' );

				return 0;
			}

			if ( ! $strengthResult.length ) {
				$strengthResult = $( '<div class="wpforms-pass-strength-result"></div>' );
				$strengthResult.css( 'max-width', $input.css( 'max-width' ) );
			}

			$strengthResult.removeClass( 'short bad good strong empty' );

			if ( ! value || value.trim() === '' ) {
				$strengthResult.remove();
				$input.removeClass( 'wpforms-error-pass-strength' );

				return 0;
			}

			const disallowedList = Object.prototype.hasOwnProperty.call( wp.passwordStrength, 'userInputDisallowedList' )
				? wp.passwordStrength.userInputDisallowedList()
				: wp.passwordStrength.userInputBlacklist();

			const strength = wp.passwordStrength.meter( value, disallowedList, value );

			$strengthResult = app.updateStrengthResultEl( $strengthResult, strength );

			$strengthResult.insertAfter( $input );
			$input.addClass( 'wpforms-error-pass-strength' );

			return strength;
		},

		/**
		 * Update strength result element to show current result strength.
		 *
		 * @since 1.6.7
		 *
		 * @param {jQuery} $strengthResult Strength result element.
		 * @param {number} strength Strength result number.
		 *
		 * @returns {jQuery} Modified strength result element.
		 */
		updateStrengthResultEl: function( $strengthResult, strength ) {

			switch ( strength ) {
				case -1:
					$strengthResult.addClass( 'bad' ).html( pwsL10n.unknown );
					break;
				case 2:
					$strengthResult.addClass( 'bad' ).html( pwsL10n.bad );
					break;
				case 3:
					$strengthResult.addClass( 'good' ).html( pwsL10n.good );
					break;
				case 4:
					$strengthResult.addClass( 'strong' ).html( pwsL10n.strong );
					break;
				default:
					$strengthResult.addClass( 'short' ).html( pwsL10n.short );
			}

			return $strengthResult;
		},
	};

	// Provide access to public functions/properties.
	return app;

}( document, window, jQuery ) );
