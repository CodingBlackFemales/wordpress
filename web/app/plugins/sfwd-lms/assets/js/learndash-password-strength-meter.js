/* global wp, pwsL10n, learndash_password_strength_meter_params */
( function( $ ) {
	'use strict';
	/**
	 * Password Strength Meter class.
	 */
	var learndash_password_strength_meter = {

		/**
		 * Initialize strength meter actions.
		 */
		init: function() {
			$( document.body )
				.on(
					'keyup change',
					'.ld-form__field--needs-password-strength, form.ldregister #password',
					this.strengthMeter
				);
		},

		/**
		 * Strength Meter.
		 */
		strengthMeter: function( event ) {
			let field      = $( event.target );
			let wrapper    = field.closest( 'form' );
			let submit     = wrapper.find( 'input[type="submit"]');
			let strength   = 1;
			let fieldValue = field.val();

			learndash_password_strength_meter.includeMeter( wrapper, field );

			strength = learndash_password_strength_meter.checkPasswordStrength( wrapper, field );

			// Notify the document that the password field was changed.
			document.dispatchEvent(
				new CustomEvent( 'learndashPasswordFieldChange', {
					detail: {
						wrapper,
						field,
					},
				} )
			);

			// Avoid disabling directly for newer forms (identified by forms with ld-form__field class).
			if ( field.is( '.ld-form__field' ) ) {
				return;
			}

			if (
				fieldValue.length > 0 &&
				strength < learndash_password_strength_meter_params.min_password_strength &&
				-1 !== strength &&
				learndash_password_strength_meter_params.stop_register
			) {
				submit.attr( 'disabled', 'disabled' ).addClass( 'disabled' );
			} else {
				submit.prop( 'disabled', false ).removeClass( 'disabled' );
			}
		},

		/**
		 * Include meter HTML.
		 *
		 * @param {Object} wrapper
		 * @param {Object} field
		 */
		includeMeter: function( wrapper, field ) {
			let meter = wrapper.find( '.ld-password-strength' );
			let $field = $(field);

			if ( '' === field.val() ) {
				meter.hide();
				$( document.body ).trigger( 'learndash-password-strength-hide' );
			} else if ( 0 === meter.length ) {
				let el = field;
				let $fieldWrapper = $field.closest('.ld-form__field-wrapper');

				if ($fieldWrapper.length) {
					el = $fieldWrapper;
				}

				el.after( '<div id="ld-password-strength__meter" class="learndash-password-strength ld-password-strength" aria-live="polite"></div>' );
				$( document.body ).trigger( 'learndash-password-strength-added' );
			} else {
				meter.show();
				$( document.body ).trigger( 'learndash-password-strength-show' );
			}
		},

		/**
		 * Check password strength.
		 *
		 * @param          wrapper
		 * @param {Object} field
		 *
		 * @return {Int}
		 */
		checkPasswordStrength: function( wrapper, field ) {
			let meter = wrapper.find( '.learndash-password-strength' ),
				hint = wrapper.find( '.learndash-password-hint' ),
				hint_html = '<small class="learndash-password-hint ld-password-strength__hint ld-password-strength__hint--injected">' + learndash_password_strength_meter_params.i18n_password_hint + '</small>',
				strength = wp.passwordStrength.meter( field.val(), wp.passwordStrength.userInputDisallowedList() ),
				error = '';

			let $hint = wrapper.find( '.ld-password-strength__hint:not(.ld-password-strength__hint--injected)' );
			let $field = $(field);

			// Reset.
			meter.removeClass( 'short bad good strong' );
			hint.remove();

			if ( meter.is( ':hidden' ) ) {
				this.clearFieldStatus($field);
				return strength;
			}

			let prefixMarkers = '<span class="ld-password-strength__prefix-marker ld-password-strength__prefix-marker-1"></span><span class="ld-password-strength__prefix-marker ld-password-strength__prefix-marker-2"></span><span class="ld-password-strength__prefix-marker ld-password-strength__prefix-marker-3"></span><span class="ld-password-strength__prefix-marker ld-password-strength__prefix-marker-4"></span>';

			if ( strength < learndash_password_strength_meter_params.min_password_strength ) {
				error = `<span class="ld-password-strength__error"> - ${ learndash_password_strength_meter_params.i18n_password_error }</span>`;
			}

			const strengthMap = {
				'0': {
					'meterClass': 'short',
					'descriptor': pwsL10n.short + error,
					'isError': true,
				},
				'1': {
					'meterClass': 'bad',
					'descriptor': pwsL10n.bad + error,
					'isError': true,
				},
				'2': {
					'meterClass': 'bad',
					'descriptor': pwsL10n.bad + error,
					'isError': true,
				},
				'3': {
					'meterClass': 'good',
					'descriptor': pwsL10n.good,
					'isError': false,
				},
				'4': {
					'meterClass': 'strong',
					'descriptor': pwsL10n.strong,
					'isError': false,
				},
				'5': {
					'meterClass': 'short',
					'descriptor': pwsL10n.mismatch,
					'isError': true,
				},
			};

			if ( strengthMap[strength] === undefined ) {
				return strength;
			}

			this.clearFieldStatus( $field );

			meter
				.addClass( strengthMap[strength].meterClass )
				.html( prefixMarkers + '<span class="ld-password-strength__descriptor">' + strengthMap[strength].descriptor + '</span>' )
				.after( ! $hint.length ? hint_html : '' );

			if ( strengthMap[strength].isError === true ) {
				$field.addClass( 'ld-form__field--error' );
			} else {
				$field.addClass( 'ld-form__field--valid' );
			}

			return strength;
		},

		/**
		 * Clears password field status classes.
		 *
		 * @since 4.16.0
		 */
		clearFieldStatus: function( $field ) {
			$field.removeClass('ld-form__field--valid ld-form__field--error');
		}
	};

	learndash_password_strength_meter.init();
}( jQuery ) );
