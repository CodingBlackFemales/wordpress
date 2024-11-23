/* global wpforms_builder */

/**
 * @param wpforms_builder.repeater.addons_requirements_alert_text
 * @param wpforms_builder.repeater.enabled_cf_alert_text
 * @param wpforms_builder.repeater.is_google_sheets_has_connection
 * @param wpforms_builder.field_add_cf_alert_text
 */

// noinspection ES6ConvertVarToLetConst
/**
 * Form Builder Field Addons module.
 *
 * @since 1.8.9
 */
var WPForms = window.WPForms || {}; // eslint-disable-line no-var

WPForms.Admin = WPForms.Admin || {};
WPForms.Admin.Builder = WPForms.Admin.Builder || {};

WPForms.Admin.Builder.Addons = WPForms.Admin.Builder.Addons || ( function( document, window, $ ) {
	/**
	 * Elements holder.
	 *
	 * @since 1.8.9
	 *
	 * @type {Object}
	 */
	const el = {};

	// noinspection ES6ShorthandObjectProperty
	/**
	 * Public functions and properties.
	 *
	 * @since 1.8.9
	 *
	 * @type {Object}
	 */
	const app = {

		isGoogleSheetsVisited: false,

		/**
		 * Init Filters section.
		 *
		 * @since 1.8.9
		 */
		init() {
			$( app.ready );
		},

		/**
		 * DOM is fully loaded.
		 *
		 * @since 1.8.9
		 */
		ready() {
			app.setup();
			app.events();
		},

		/**
		 * Setup. Prepare some variables.
		 *
		 * @since 1.8.9
		 */
		setup() {
			// Cache DOM elements.
			el.$builder = $( '#wpforms-builder' );
		},

		/**
		 * Bind events.
		 *
		 * @since 1.8.9
		 */
		events() {
			el.$builder.on( 'click', '#wpforms-panel-field-settings-conversational_forms_enable', app.optionCFEnableModal );
			el.$builder.on( 'click', '#wpforms-panel-field-settings-save_resume_enable', app.optionSaveAndResumeEnableModal );
			el.$builder.on( 'click', '#wpforms-panel-field-settings-form_abandonment', app.optionFormAbandonmentEnableModal );
			el.$builder.on( 'click', '.wpforms-panel-sidebar-section-google-sheets', app.optionGoogleSheetsVisitedModal );
			el.$builder.on( 'click', '#wpforms-panel-field-lead_forms-enable', app.optionLeadFormsEnableModal );
			el.$builder.on( 'wpformsFieldAddDragStart wpformsBeforeFieldAddOnClick', app.layoutFieldsCantAddModal );
		},

		/**
		 * Layout-based field can't be added modal.
		 *
		 * @since 1.8.9
		 *
		 * @param {Event}  e    Event.
		 * @param {string} type Field type.
		 */
		layoutFieldsCantAddModal( e, type ) { // eslint-disable-line complexity
			if ( ! app.isLayoutBasedField( type ) ) {
				return;
			}

			let alertMessage = '';

			if ( app.isAddonEnabled( 'wpforms-conversational-forms' ) ) {
				alertMessage = wpforms_builder[ type ].field_add_cf_alert_text;

				e.preventDefault();
			}

			if ( app.isAddonEnabled( 'wpforms-save-resume' ) && type === 'repeater' && ! app.isInsideRepeaterAddonAllowed( 'wpforms-save-resume' ) ) {
				alertMessage = wpforms_builder.repeater.addons_requirements_alert_text[ 'wpforms-save-resume' ];
			}

			if ( app.isAddonEnabled( 'wpforms-form-abandonment' ) && type === 'repeater' && ! app.isInsideRepeaterAddonAllowed( 'wpforms-form-abandonment' ) ) {
				alertMessage = wpforms_builder.repeater.addons_requirements_alert_text[ 'wpforms-form-abandonment' ];
			}

			if ( ! $( '.wpforms-panel-sidebar-section-google-sheets' ).hasClass( 'education-modal' ) && type === 'repeater' && ! app.isInsideRepeaterAddonAllowed( 'wpforms-google-sheets' ) && app.isGoogleSheetsConnectionsExist() ) {
				alertMessage = wpforms_builder.repeater.addons_requirements_alert_text[ 'wpforms-google-sheets' ];
			}

			if ( app.isAddonEnabled( 'wpforms-lead-forms' ) && type === 'repeater' && ! app.isInsideRepeaterAddonAllowed( 'wpforms-lead-forms' ) ) {
				alertMessage = wpforms_builder.repeater.addons_requirements_alert[ 'wpforms-lead-forms' ];

				e.preventDefault();
			}

			if ( alertMessage === '' ) {
				return;
			}

			app.openModal( {
				content: alertMessage,
			} );
		},

		/**
		 * Check if the addon is enabled.
		 *
		 * @since 1.8.9
		 *
		 * @param {string} slug Addon slug.
		 *
		 * @return {boolean} True if the addon is enabled.
		 */
		isAddonEnabled( slug ) {
			// Addon toggle selectors.
			const toggleSelector = {
				'wpforms-conversational-forms': '#wpforms-panel-field-settings-conversational_forms_enable',
				'wpforms-save-resume': '#wpforms-panel-field-settings-save_resume_enable',
				'wpforms-form-abandonment': '#wpforms-panel-field-settings-form_abandonment',
				'wpforms-lead-forms': '#wpforms-panel-field-lead_forms-enable',
			};

			if ( ! toggleSelector[ slug ] ) {
				return false;
			}

			return $( toggleSelector[ slug ] ).is( ':checked' );
		},

		/**
		 * Check if the addon is allowed inside the repeater.
		 *
		 * @param {string} slug Addon slug.
		 *
		 * @return {boolean} True if the addon is allowed.
		 */
		isInsideRepeaterAddonAllowed( slug ) {
			return wpforms_builder.repeater.addons_requirements[ slug ];
		},

		/**
		 * Check if the Repeater field added to the form.
		 *
		 * @return {boolean} True if the Repeater field added to the form.
		 */
		isRepeaterAdded() {
			return $( '#wpforms-field-options .wpforms-field-option-repeater' ).length > 0;
		},

		/**
		 * Save and Resume can't be enabled modal.
		 *
		 * @since 1.8.9
		 */
		optionFormAbandonmentEnableModal() {
			if ( ! app.isAddonEnabled( 'wpforms-form-abandonment' ) || app.isInsideRepeaterAddonAllowed( 'wpforms-form-abandonment' ) ) {
				return;
			}

			if ( ! app.isRepeaterAdded() ) {
				return;
			}

			app.openModal( {
				content: wpforms_builder.repeater.addons_requirements_alert_text[ 'wpforms-form-abandonment' ],
			} );
		},

		/**
		 * Show a proposal to upgrade Google Sheets addon for using repeated fields.
		 *
		 * @since 1.8.9
		 */
		optionGoogleSheetsVisitedModal() {
			if ( app.isGoogleSheetsVisited ) {
				return;
			}

			if ( $( this ).hasClass( 'education-modal' ) ) {
				return;
			}

			if ( app.isInsideRepeaterAddonAllowed( 'wpforms-google-sheets' ) ) {
				return;
			}

			if ( ! app.isRepeaterAdded() ) {
				return;
			}

			if ( ! app.isGoogleSheetsConnectionsExist() ) {
				return;
			}

			app.isGoogleSheetsVisited = true;

			app.openModal( {
				content: wpforms_builder.repeater.addons_requirements_alert_text[ 'wpforms-google-sheets' ],
			} );
		},

		/**
		 * Determine if Google Sheets connections exist.
		 *
		 * @since 1.9.0
		 *
		 * @return {boolean} True if Google Sheets connections exist.
		 */
		isGoogleSheetsConnectionsExist() {
			if ( ! WPForms.Admin.Builder.Providers?.GoogleSheets?.isReady ) {
				return wpforms_builder.repeater.is_google_sheets_has_connection;
			}

			return Boolean( $( '.wpforms-builder-provider-connection', '#google-sheets-provider' ).length );
		},

		/**
		 * Save and Resume can't be enabled modal.
		 *
		 * @since 1.8.9
		 */
		optionSaveAndResumeEnableModal() {
			if ( ! app.isAddonEnabled( 'wpforms-save-resume' ) || app.isInsideRepeaterAddonAllowed( 'wpforms-save-resume' ) ) {
				return;
			}

			if ( ! app.isRepeaterAdded() ) {
				return;
			}

			app.openModal( {
				content: wpforms_builder.repeater.addons_requirements_alert_text[ 'wpforms-save-resume' ],
			} );
		},

		/**
		 * Conversational Forms can't be enabled modal.
		 *
		 * @since 1.8.9
		 *
		 * @param {Event} e Event.
		 */
		optionCFEnableModal( e ) {
			if ( ! app.isAddonEnabled( 'wpforms-conversational-forms' ) ) {
				return;
			}

			if ( ! app.isRepeaterAdded() ) {
				return;
			}

			e.preventDefault();

			app.openModal( {
				content: wpforms_builder.repeater.enabled_cf_alert_text,
			} );
		},

		/**
		 * Lead Forms can't be enabled modal.
		 *
		 * @since 1.8.9
		 *
		 * @param {Event} e Event.
		 */
		optionLeadFormsEnableModal( e ) {
			if ( ! app.isAddonEnabled( 'wpforms-lead-forms' ) ) {
				return;
			}

			if ( ! app.isRepeaterAdded() ) {
				return;
			}

			e.preventDefault();

			app.openModal( {
				content: wpforms_builder.repeater.addons_requirements_alert_text[ 'wpforms-lead-forms' ],
			} );
		},

		/**
		 * Open modal window.
		 *
		 * @since 1.8.9
		 *
		 * @param {Object} args Arguments.
		 */
		openModal( args ) {
			if ( ! args || ! args.content ) {
				return;
			}

			$.confirm( {
				title: args.title ?? wpforms_builder.heads_up,
				content: args.content,
				icon: args.icon ?? 'fa fa-exclamation-circle',
				type: args.type ?? 'orange',
				buttons: {
					confirm: {
						text: wpforms_builder.ok,
						btnClass: 'btn-confirm',
						keys: [ 'enter' ],
					},
				},
			} );
		},

		/**
		 * Determine whether the field type is a layout-based field.
		 *
		 * @since 1.8.9
		 *
		 * @param {string} fieldType Field type to check.
		 *
		 * @return {boolean} True if it is the Layout-based field.
		 */
		isLayoutBasedField( fieldType ) {
			if ( ! WPForms.Admin.Builder.FieldLayout ) {
				return false;
			}

			return WPForms.Admin.Builder.FieldLayout.isLayoutBasedField( fieldType );
		},
	};

	// Provide access to public functions/properties.
	return app;
}( document, window, jQuery ) );

// Initialize.
WPForms.Admin.Builder.Addons.init();
