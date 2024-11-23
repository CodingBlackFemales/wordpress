/**
 * Form Builder Field Phone module.
 *
 * @since 1.9.2
 */
var WPForms = window.WPForms || {}; // eslint-disable-line no-var

WPForms.Admin = WPForms.Admin || {};
WPForms.Admin.Builder = WPForms.Admin.Builder || {};

WPForms.Admin.Builder.FieldPhone = WPForms.Admin.Builder.FieldPhone || ( function( document, window, $ ) {
	/**
	 * Elements holder.
	 *
	 * @since 1.9.2
	 *
	 * @type {Object}
	 */
	let el = {};

	const app = {

		/**
		 * Start the engine.
		 *
		 * @since 1.9.2
		 */
		init() {
			$( app.ready );
		},

		/**
		 * DOM is fully loaded.
		 *
		 * @since 1.9.2
		 */
		ready() {
			app.setup();
			app.events();
		},

		/**
		 * Setup. Prepare some variables.
		 *
		 * @since 1.9.2
		 */
		setup() {
			// Cache DOM elements.
			el = {
				$builder: $( '#wpforms-builder' ),
			};
		},

		/**
		 * Add handlers on events.
		 *
		 * @since 1.9.2
		 */
		events() {
			el.$builder
				.on( 'change', '.wpforms-field-option-phone .wpforms-field-option-row-format select', app.handleFormatChange );
		},

		/**
		 * Handle a changing "Format" option.
		 *
		 * @since 1.9.2
		 */
		handleFormatChange() {
			const $select = $( this ),
				fieldId = $select.closest( '.wpforms-field-option-row' ).data( 'field-id' );

			$( `#wpforms-field-${ fieldId } .wpforms-field-phone-input-container` ).attr( 'data-format', $select.val() );
		},
	};

	// Provide access to public functions/properties.
	return app;
}( document, window, jQuery ) );

WPForms.Admin.Builder.FieldPhone.init();
