/* global wpforms_builder, wpforms_addons, wpf */

/**
 * @param strings.calculation_notice_text
 * @param strings.calculation_notice_text_grp
 * @param strings.calculation_notice_tooltip
 * @param strings.cant_switch_to_rows_alert
 * @param strings.cl_notice_text
 * @param strings.cl_notice_text_grp
 * @param strings.move_to_rows_rejected_alert
 * @param strings.not_allowed
 * @param strings.not_allowed_alert_text
 * @param strings.not_allowed_fields
 * @param strings.rows_limit_max
 * @param wpforms_builder.repeater.fields_mapping.title
 * @param wpforms_builder.repeater.fields_mapping.content
 * @param wpforms_builder.repeater.addons_requirements
 * @param wpforms_builder.repeater.wpforms_builder.repeater.addons_requirements_alert
 */

// noinspection ES6ConvertVarToLetConst
/**
 * Form Builder Field Repeater module.
 *
 * @since 1.8.9
 */
var WPForms = window.WPForms || {}; // eslint-disable-line no-var

WPForms.Admin = WPForms.Admin || {};
WPForms.Admin.Builder = WPForms.Admin.Builder || {};

WPForms.Admin.Builder.FieldRepeater = WPForms.Admin.Builder.FieldRepeater || ( function( document, window, $ ) {
	/**
	 * Localized Repeater field strings.
	 *
	 * @since 1.8.9
	 *
	 * @type {Object}
	 */
	const strings = wpforms_builder.repeater;

	/**
	 * Elements holder.
	 *
	 * @since 1.8.9
	 *
	 * @type {Object}
	 */
	let el = {};

	/**
	 * Runtime variables.
	 *
	 * @since 1.8.9
	 *
	 * @type {Object}
	 */
	const vars = {};

	/**
	 * Public functions and properties.
	 *
	 * @since 1.8.9
	 *
	 * @type {Object}
	 */
	const app = {
		/**
		 * Start the engine.
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
			app.hooks();
			app.events();
		},

		/**
		 * Setup. Prepare some variables.
		 *
		 * @since 1.8.9
		 */
		setup() {
			// Cache DOM elements.
			el = {
				$builder: $( '#wpforms-builder' ),
			};
		},

		/**
		 * Init all the Repeater fields.
		 *
		 * @since 1.8.9
		 */
		initFields() {
			el.$builder.find( '.wpforms-field-repeater' ).each( function() {
				const $field = $( this );
				const fieldId = $field.data( 'field-id' );

				app.adjustRowsAppearance( fieldId );
			} );
		},

		/**
		 * Hooks.
		 *
		 * @since 1.8.9
		 */
		hooks() {
			// Determine if we could add a field to the column.
			wp.hooks.addFilter(
				'wpforms.LayoutField.isFieldAllowedDragInColumn',
				'wpforms/field-repeater',
				app.filterIsFieldAllowedDragInColumn
			);

			wp.hooks.addFilter(
				'wpforms.LayoutField.isFieldAllowedInColumn',
				'wpforms/field-repeater',
				app.filterIsFieldAllowedInColumn
			);

			// Update alert message modal options when the field is not allowed to be added to the column.
			wp.hooks.addFilter(
				'wpforms.LayoutField.fieldMoveRejectedModalOptions',
				'wpforms/field-repeater',
				app.filterFieldMoveRejectedModalOptions
			);

			// Filter fields in the CL fields dropdown.
			wp.hooks.addFilter(
				'wpforms.ConditionalLogicCore.BeforeRemoveUnsupportedFields',
				'wpforms/field-repeater',
				app.removeRepeaterFieldsAndChildren
			);
		},

		/**
		 * Bind events.
		 *
		 * @since 1.8.9
		 */
		events() {
			el.$builder
				.on( 'click', '.wpforms-field-option-row-display input', app.handleDisplayClick )
				.on( 'change', '.wpforms-field-option-row-display input', app.handleDisplayChange )
				.on( 'change', '.wpforms-field-option-row-preset input', app.handlePresetChange )
				.on( 'change', '.wpforms-field-option-row-button-type select', app.handleButtonTypeChange )
				.on( 'input', '.wpforms-field-option-row-label input', app.handleFieldLabelChange )
				.on( 'input', '.wpforms-field-option-row-button-labels input', app.handleButtonLabelsChange )
				.on( 'change', '.wpforms-field-option-row-rows-limit input', app.handleRowsLimitChange )
				.on( 'wpformsLayoutAfterPresetChange', app.handleAfterPresetChange )
				.on( 'wpformsLayoutAfterHeightBalance', app.handleAfterHeightBalance )
				.on( 'wpformsFieldAdd', app.handleFieldAdd )
				.on( 'wpformsFieldDelete', app.handleFieldDelete )
				.on( 'wpformsFieldMoveRejected', app.handleFieldMoveRejected )
				.on( 'wpformsFieldDuplicated', app.handleFieldDuplicated )
				.on( 'wpformsBuilderReady', app.initFields )
				.on( 'wpformsFieldOptionTabToggle', app.handleFieldOptionTabToggle )
				.on( 'wpformsFieldMove', app.handleFieldMove )
				.on( 'change', '.wpforms-field-option-layout .wpforms-field-option-row-conditional_logic input', app.handleUpdateFieldCLOption );

			$( window )
				.on( 'resize', _.debounce( app.handleWindowResize, 50 ) );
		},

		/**
		 * Fields mapping notice.
		 *
		 * @since 1.9.1
		 *
		 * @param {string} fieldId Field ID.
		 */
		// eslint-disable-next-line max-lines-per-function
		fieldsMappingNotice( fieldId ) {
			/**
			 * Check if the field is mapped to the select.
			 *
			 * @param {string} selectedFieldID Selected field ID.
			 *
			 * @return {boolean} True if the field is mapped.
			 */
			function isFieldMappedToSelect( selectedFieldID ) {
				return parseInt( selectedFieldID, 10 ) === parseInt( fieldId, 10 );
			}

			/**
			 * Get the section title.
			 *
			 * @param {Object} $select Field map select element.
			 *
			 * @return {string} Section title.
			 */
			function getSectionTitle( $select ) {
				return $select.closest( '.wpforms-panel-content-section' ).find( '.wpforms-panel-content-section-title' )[ 0 ].firstChild.nodeValue.trim();
			}

			/**
			 * Show the confirmation dialog.
			 *
			 * @param {string} sectionTitle Section title.
			 * @param {Object} $field       Field element.
			 */
			function showConfirmationDialog( sectionTitle, $field ) {
				el.$builder.on( 'wpformsBeforeFieldMapSelectUpdate', ( e ) => e.preventDefault() );

				$.confirm( {
					title: wpforms_builder.repeater.fields_mapping.title,
					content: wpforms_builder.repeater.fields_mapping.content.replace( '%s', sectionTitle ),
					icon: 'fa fa-exclamation-circle',
					type: 'orange',
					buttons: {
						confirm: {
							text: wpforms_builder.ok,
							btnClass: 'btn-confirm',
							keys: [ 'enter' ],
							action: () => {
								el.$builder.off( 'wpformsBeforeFieldMapSelectUpdate' );
								const fields = wpf.getFields( false, true, false, false );

								$( document ).trigger( 'wpformsFieldUpdate', [ fields ] );
							},
						},
						cancel: {
							text: wpforms_builder.cancel,
							action: () => {
								WPForms.Admin.Builder.DragFields.revertMoveFieldToColumn( $field );
								WPForms.Admin.Builder.FieldLayout.removeFieldFromColumns( $field.data( 'field-id' ) );
								app.initFields();
							},
						},
					},
				} );
			}

			/**
			 * Check and handle the field mapping.
			 *
			 * @param {Object} $field Field element.
			 */
			function checkAndHandleFieldMapping( $field ) {
				const data = {
					sections: [],
					$field: null,
				};

				// Check if the field is mapped to the select.
				$( 'select[data-field-map-allowed], select.wpforms-builder-provider-connection-field-value' ).each( function() {
					const $select = $( this );

					if ( isFieldMappedToSelect( $select.val() ) ) {
						data.sections.push( getSectionTitle( $select ) );
						data.$field = $field;
					}
				} );

				if ( data.$field ) {
					const sectionTitle = [ ...new Set( data.sections ) ].join( ' ' + wpforms_builder.repeater.fields_mapping.and + ' ' );
					showConfirmationDialog( sectionTitle, data.$field );
				}
			}

			/**
			 * Check if the field is inside the repeater and show the notice.
			 */
			function showNotice() {
				const $field = $( '#wpforms-field-' + fieldId );

				if ( ! $field.length || $field.hasClass( 'wpforms-field-repeater' ) || $field.hasClass( 'wpforms-field-layout' ) ) {
					return;
				}

				if ( ! $field.closest( '.wpforms-field-repeater' ).length ) {
					return;
				}

				checkAndHandleFieldMapping( $field );
			}

			showNotice();
		},

		/**
		 * Display click event handler.
		 *
		 * @since 1.8.9
		 *
		 * @return {boolean} Whether the change is allowed.
		 */
		handleDisplayClick() {
			const $input = $( this );
			const display = $input.val();

			if ( display !== 'rows' ) {
				return true;
			}

			const fieldId = $input.closest( '.wpforms-field-option-repeater' ).data( 'field-id' );
			const columnsData = WPForms.Admin.Builder.FieldLayout.getFieldColumnsData( fieldId );
			const allowRows = columnsData.every( ( column ) => {
				return column?.fields?.length <= 1;
			} );

			if ( ! allowRows ) {
				// Display alert.
				app.errorModal( strings.not_allowed, strings.cant_switch_to_rows_alert );

				return false;
			}

			return true;
		},

		/**
		 * Display change event handler.
		 *
		 * @since 1.8.9
		 */
		handleDisplayChange() {
			const $input = $( this );
			const display = $input.val();
			const $fieldOptions = $input.closest( '.wpforms-field-option-repeater' );
			const fieldId = $fieldOptions.data( 'field-id' );
			const $fieldPreview = $( '#wpforms-field-' + fieldId );
			const buttonType = $fieldOptions.find( '.wpforms-field-option-row-button-type select' ).val();

			// Show/hide button type and labels options.
			$fieldOptions
				.find( '.wpforms-field-option-row-button-type' )
				.toggleClass( 'wpforms-hidden', display === 'rows' );

			$fieldOptions
				.find( '.wpforms-field-option-row-button-labels' )
				.toggleClass( 'wpforms-hidden', display === 'rows' || buttonType === 'icons' );

			// Change field preview class according to selected Display value.
			$fieldPreview
				.find( '.wpforms-field-layout-columns' )
				.toggleClass( 'wpforms-layout-display-rows', display === 'rows' )
				.toggleClass( 'wpforms-layout-display-blocks', display === 'blocks' );

			if ( display === 'blocks' ) {
				$fieldPreview.find( '.wpforms-layout-column-placeholder' ).css( 'top', '' );
			}

			// Show/hide blocks' buttons on the field preview.
			$fieldPreview
				.find( '.wpforms-field-repeater-display-rows-buttons' )
				.toggleClass( 'wpforms-hidden', display !== 'rows' );

			// Show/hide rows' buttons on the field preview.
			$fieldPreview
				.find( '.wpforms-field-repeater-display-blocks-buttons' )
				.toggleClass( 'wpforms-hidden', display !== 'blocks' );

			app.adjustRowsAppearance( fieldId );
		},

		/**
		 * Preset change event handler.
		 *
		 * @since 1.8.9
		 */
		handlePresetChange() {
			const $input = $( this );
			const preset = $input.val();
			const $fieldOptions = $input.closest( '.wpforms-field-option-repeater' );

			$fieldOptions
				.find( '.wpforms-field-option-row-size' )
				.toggleClass( 'wpforms-disabled', preset !== '100' );
		},

		/**
		 * After preset change event handler.
		 *
		 * @since 1.8.9
		 *
		 * @param {Event}  e    Event object.
		 * @param {Object} data Event data.
		 */
		handleAfterPresetChange( e, data ) {
			const $fieldPreview = $( '#wpforms-field-' + data.fieldId );
			const display = $( `#wpforms-field-option-row-${ data.fieldId }-display input:checked` ).val();
			const rowsButtons = wp.template( 'wpforms-repeater-field-display-rows-buttons-template' );
			const classHidden = display === 'rows' ? '' : 'wpforms-hidden';

			$fieldPreview
				.find( '.wpforms-field-layout-columns' )
				.append( rowsButtons( { class: classHidden } ) );

			app.adjustRowsAppearance( data.fieldId );
		},

		/**
		 * Button Type change event handler.
		 *
		 * @since 1.8.9
		 */
		handleButtonTypeChange() {
			const $input = $( this );
			const buttonType = $input.val();
			const $fieldOptions = $input.closest( '.wpforms-field-option-repeater' );
			const $fieldPreview = $( '#wpforms-field-' + $fieldOptions.data( 'field-id' ) );

			$fieldOptions
				.find( '.wpforms-field-option-row-button-labels' )
				.toggleClass( 'wpforms-hidden', buttonType === 'icons' );

			$fieldPreview
				.find( '.wpforms-field-repeater-display-blocks-buttons' )
				.attr( 'data-button-type', buttonType );
		},

		/**
		 * Field Label change event handler.
		 *
		 * @since 1.8.9
		 */
		handleFieldLabelChange() {
			const $input = $( this );
			const $fieldOptions = $input.closest( '.wpforms-field-option-repeater' );
			const fieldId = $fieldOptions.data( 'field-id' );

			app.adjustRowsAppearance( fieldId );
		},

		/**
		 * Window resize event handler.
		 *
		 * @since 1.8.9
		 */
		handleWindowResize() {
			el.$builder.find( '.wpforms-field-repeater' ).each( function() {
				app.adjustRowsAppearance( $( this ).data( 'field-id' ) );
			} );
		},

		/**
		 * Button Labels change event handler.
		 *
		 * @since 1.8.9
		 */
		handleButtonLabelsChange() {
			const $input = $( this );
			const $fieldOptions = $input.closest( '.wpforms-field-option-repeater' );
			const fieldId = $fieldOptions.data( 'field-id' );
			const $fieldPreview = $( '#wpforms-field-' + fieldId );
			const buttonLabel = $input.val();
			const button = $input.attr( 'class' );

			$fieldPreview
				.find( `.wpforms-field-repeater-display-blocks-buttons-${ button } span` )
				.text( buttonLabel );
		},

		/**
		 * Rows Limit change event handler.
		 *
		 * @since 1.8.9
		 */
		handleRowsLimitChange() {
			const $input = $( this );
			const limit = $input.attr( 'class' );

			if ( limit === 'rows-limit-min' ) {
				app.normalizeLimitMin( $input );
			} else {
				app.normalizeLimitMax( $input );
			}

			// Round the value after normalization.
			$input.val( Math.round( $input.val() ) );
		},

		/**
		 * Normalize Rows Limit Minimum value.
		 *
		 * @since 1.8.9
		 *
		 * @param {jQuery} $input Limit Minimum input element.
		 */
		normalizeLimitMin( $input ) {
			const value = $input.val();
			const valueMin = parseInt( value, 10 ) || 0;

			// Minimal acceptable value of Minimum is 1.
			if ( value === '' || valueMin < 1 ) {
				$input.val( 1 );

				return;
			}

			const $inputMax = $input.closest( '.wpforms-field-option-row-rows-limit' ).find( 'input.rows-limit-max' );
			const valueMax = parseInt( $inputMax.val(), 10 );

			// The Minimum value should be less than the Maximum value.
			if ( valueMax <= valueMin ) {
				$input.val( valueMax - 1 );
			}
		},

		/**
		 * Normalize Rows Limit Maximum value.
		 *
		 * @since 1.8.9
		 *
		 * @param {jQuery} $input Limit Maximum input element.
		 */
		normalizeLimitMax( $input ) {
			const value = $input.val();
			const $inputMin = $input.closest( '.wpforms-field-option-row-rows-limit' ).find( 'input.rows-limit-min' );
			const valueMin = parseInt( $inputMin.val(), 10 );

			// The default Maximum value is relative to the Minimum.
			// When the Minimum is 1, the Maximum should be 10.
			if ( value === '' ) {
				const diff = valueMin === 1 ? 9 : 10;

				$input.val( valueMin + diff );

				return;
			}

			const valueMax = parseInt( value, 10 );

			// The Maximum value should be greater than the Minimum value.
			if ( valueMax <= valueMin ) {
				$input.val( valueMin + 1 );

				return;
			}

			// Attempt to set enormous maximum value. Limit it to 200.
			if ( valueMax > strings.rows_limit_max ) {
				$input.val( strings.rows_limit_max );
			}
		},

		/**
		 * After height balance event handler.
		 *
		 * @since 1.8.9
		 *
		 * @param {Event}  e    Event object.
		 * @param {Object} data Event data.
		 */
		handleAfterHeightBalance( e, data ) {
			if ( ! data?.$rows ) {
				return;
			}

			const fieldId = data.$rows.closest( '.wpforms-field-repeater' ).data( 'field-id' );

			if ( ! fieldId ) {
				return;
			}

			app.adjustRowsAppearance( fieldId );
		},

		/**
		 * Field add handler.
		 *
		 * @since 1.8.9
		 *
		 * @param {Event}  e         Event object.
		 * @param {string} fieldID   Field ID.
		 * @param {string} fieldType Field type.
		 */
		handleFieldAdd( e, fieldID, fieldType ) {
			if ( ! fieldID || ! fieldType ) {
				return;
			}

			const $fieldPreview = $( '#wpforms-field-' + fieldID );
			const $repeaterField = $fieldPreview.closest( '.wpforms-field-repeater' );

			if ( $repeaterField.length === 0 ) {
				return;
			}

			app.adjustRowsAppearance( $repeaterField.data( 'field-id' ) );
		},

		/**
		 * Field delete event handler.
		 *
		 * @since 1.8.9
		 *
		 * @param {Object} e                   Event object.
		 * @param {string} fieldID             Field ID.
		 * @param {string} fieldType           Field type.
		 * @param {jQuery} $fieldLayoutWrapper Field layout wrapper.
		 */
		handleFieldDelete( e, fieldID, fieldType, $fieldLayoutWrapper ) {
			if ( $fieldLayoutWrapper.length === 0 ) {
				return;
			}

			const $repeaterField = $fieldLayoutWrapper.closest( '.wpforms-field-repeater' );

			app.adjustRowsAppearance( $repeaterField.data( 'field-id' ) );
		},

		/**
		 * Field move rejected handler.
		 *
		 * @since 1.8.9
		 *
		 * @param {Event}  e      Event object.
		 * @param {jQuery} $field Field element.
		 */
		handleFieldMoveRejected( e, $field ) {
			app.adjustRowsAppearance( $field.closest( '.wpforms-field-repeater' ).data( 'field-id' ) );
		},

		/**
		 * The `wpformsFieldDuplicated` event handler.
		 *
		 * @since 1.8.9
		 *
		 * @param {Event}  e          Event.
		 * @param {number} fieldId    Field ID.
		 * @param {jQuery} $field     Field object.
		 * @param {number} newFieldId New field ID.
		 * @param {jQuery} $newField  New field object.
		 */
		handleFieldDuplicated( e, fieldId, $field, newFieldId, $newField ) { // eslint-disable-line no-unused-vars
			// Run for the Repeater field only.
			if ( $field.data( 'field-type' ) !== 'repeater' ) {
				return;
			}

			const display = $( `#wpforms-field-option-${ fieldId } .wpforms-field-option-row-display input:checked` ).val();

			// Set the Display option for the duplicated field.
			$( `#wpforms-field-option-${ newFieldId }-display-${ display }` ).prop( 'checked', true );

			// Adjust rows appearance after field duplication.
			app.adjustRowsAppearance( newFieldId );
		},

		/**
		 * The `wpformsFieldOptionTabToggle` event handler.
		 *
		 * @since 1.8.9
		 *
		 * @param {Event}  e       Event.
		 * @param {number} fieldId Field id.
		 */
		handleFieldOptionTabToggle( e, fieldId ) {
			app.updateFieldCLOption( fieldId );
			app.updateFieldCalculationOption( fieldId );
			app.updateFieldGeolocationRequirementsAlerts( fieldId );
			app.updateFieldSignatureRequirementsAlerts( fieldId );
		},

		/**
		 * The `wpformsFieldMove` event handler.
		 *
		 * @since 1.8.9
		 *
		 * @param {Object} e  Event object.
		 * @param {Object} ui UI object.
		 */
		handleFieldMove( e, ui ) {
			const fieldId = ui.item.first().data( 'field-id' );

			app.updateFieldCLOption( fieldId );
			app.updateFieldCalculationOption( fieldId );
			app.updateFieldGeolocationRequirementsAlerts( fieldId );
			app.updateFieldSignatureRequirementsAlerts( fieldId );
			app.fieldsMappingNotice( fieldId );
		},

		/**
		 * Update the Conditional Logic field option.
		 *
		 * @since 1.9.0
		 */
		handleUpdateFieldCLOption() {
			const $this = $( this );
			const fieldID = $this.parents( '.wpforms-field-option-row' ).data( 'field-id' );
			const $field = $( `#wpforms-field-${ fieldID }` );

			if ( ! $field.length ) {
				return;
			}

			$field.find( '.wpforms-field' ).each( function() {
				app.updateFieldCLOption( $( this ).data( 'field-id' ) );
			} );
		},

		/**
		 * Update the Geolocation field option.
		 *
		 * @since 1.8.9
		 *
		 * @param {number|string} fieldId Field ID.
		 */
		updateFieldGeolocationRequirementsAlerts( fieldId ) {
			const $field = $( `#wpforms-field-${ fieldId }` );

			if ( ! $field?.length || $field.hasClass( 'wpforms-field-repeater' ) || app.isInsideRepeaterAddonAllowed( 'wpforms-geolocation' ) || ( typeof wpforms_addons !== 'undefined' && wpforms_addons[ 'wpforms-geolocation' ] ) ) {
				return;
			}

			const isFieldInRepeater = $field.closest( '.wpforms-field-repeater' ).length > 0;
			const $fieldOptionToggleRow = $( `#wpforms-field-option-row-${ fieldId }-enable_address_autocomplete` );
			let $fieldOptionNotice = $fieldOptionToggleRow.siblings( '.wpforms-alert-field-requirements' );

			if ( ! $fieldOptionNotice.length ) {
				$fieldOptionNotice = $( wpforms_builder.repeater.addons_requirements_alert[ 'wpforms-geolocation' ] );

				$fieldOptionToggleRow.before( $fieldOptionNotice );
			}

			$fieldOptionNotice.toggleClass( 'wpforms-hidden', ! isFieldInRepeater );
		},

		/**
		 * Update the Signature field.
		 *
		 * @since 1.8.9
		 *
		 * @param {number|string} fieldId Field ID.
		 */
		updateFieldSignatureRequirementsAlerts( fieldId ) {
			const $field = $( `#wpforms-field-${ fieldId }` );

			if ( ! $field?.length || $field.hasClass( 'wpforms-field-repeater' ) || app.isInsideRepeaterAddonAllowed( 'wpforms-signatures' ) ) {
				return;
			}

			const isFieldInRepeater = $field.closest( '.wpforms-field-repeater' ).length > 0;
			const $fieldOptionToggleRow = $( `.wpforms-field-option-signature #wpforms-field-option-row-${ fieldId }-label` );
			let $fieldOptionNotice = $fieldOptionToggleRow.siblings( '.wpforms-alert-field-requirements' );

			if ( ! $fieldOptionNotice.length ) {
				$fieldOptionNotice = $( wpforms_builder.repeater.addons_requirements_alert[ 'wpforms-signatures' ] );

				$fieldOptionToggleRow.before( $fieldOptionNotice );
			}

			$fieldOptionNotice.toggleClass( 'wpforms-hidden', ! isFieldInRepeater );
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
		 * Update the Calculation field option.
		 *
		 * @since 1.8.9
		 *
		 * @param {number|string} fieldId Field ID.
		 */
		updateFieldCalculationOption( fieldId ) {
			const $field = $( `#wpforms-field-${ fieldId }` );

			if ( ! $field?.length || $field.hasClass( 'wpforms-field-repeater' ) || ( typeof wpforms_addons !== 'undefined' && wpforms_addons[ 'wpforms-calculations' ] ) ) {
				return;
			}

			const isFieldInRepeater = $field.closest( '.wpforms-field-repeater' ).length > 0;
			const $fieldOptionToggleRow = $( `#wpforms-field-option-row-${ fieldId }-calculation_is_enabled` );
			const $fieldOptionToggleInput = $fieldOptionToggleRow.find( 'input' );

			let $fieldOptionNotice = $fieldOptionToggleRow.siblings( '.wpforms-notice-field-calculation_is_enabled' );

			// Add "Calculation is disabled" notice.
			if ( ! $fieldOptionNotice.length ) {
				$fieldOptionNotice = $(
					`<div class="wpforms-alert wpforms-alert-warning wpforms-notice-field-calculation_is_enabled" title="${ strings.calculation_notice_tooltip }">
						<p>${ strings.calculation_notice_text }</p>
					</div>`
				);

				$fieldOptionToggleRow.before( $fieldOptionNotice );
			}

			// Notice text.
			$fieldOptionNotice.find( 'p' ).text( $fieldOptionToggleInput.prop( 'checked' ) ? strings.calculation_notice_text_grp : strings.calculation_notice_text );

			if ( isFieldInRepeater ) {
				$fieldOptionToggleInput.prop( 'checked', false ).trigger( 'change' );
			}

			$fieldOptionToggleRow.toggleClass( 'wpforms-disabled', isFieldInRepeater );
			$fieldOptionNotice.toggleClass( 'wpforms-hidden', ! isFieldInRepeater );
		},

		/**
		 * Update the Conditional Logic field option.
		 *
		 * @since 1.8.9
		 *
		 * @param {number|string} fieldId Field ID.
		 */
		updateFieldCLOption( fieldId ) { // eslint-disable-line complexity
			const $field = $( `#wpforms-field-${ fieldId }` );

			if ( ! $field?.length || $field.hasClass( 'wpforms-field-repeater' ) || $field.hasClass( 'wpforms-field-layout' ) ) {
				return;
			}

			const isFieldInRepeater = $field.closest( '.wpforms-field-repeater' ).length > 0;
			let isFieldInLayout = $field.closest( '.wpforms-field-layout' ).length > 0;
			const $fieldCLOptionRow = $( `#wpforms-field-option-row-${ fieldId }-conditional_logic` );
			const $fieldCLBlock = $fieldCLOptionRow.closest( '.wpforms-conditional-block' );
			const parentBlockType = isFieldInRepeater ? 'repeater' : 'layout';

			if ( isFieldInLayout && ! app.isLayoutCLEnabled( $field.closest( '.wpforms-field-layout' ) ) ) {
				isFieldInLayout = false;
			}

			let $fieldCLOptionNotice = $fieldCLBlock.siblings( '.wpforms-notice-field-conditional_logic' );

			// Add "Conditional Logic is disabled" notice.
			if ( ! $fieldCLOptionNotice.length ) {
				$fieldCLOptionNotice = $(
					`<div class="wpforms-alert wpforms-alert-warning wpforms-notice-field-conditional_logic">
						<p>${ wpforms_builder[ parentBlockType ].cl_notice_text }</p>
					</div>`
				);

				$fieldCLBlock.before( $fieldCLOptionNotice );
			}

			const $fieldCLOptionToggle = $fieldCLOptionRow.find( '.wpforms-toggle-control' );
			const $fieldCLOptionToggleInput = $fieldCLOptionToggle.find( 'input' );

			// Disable Conditional Logic when moved inside the Repeater field.
			if ( isFieldInRepeater || isFieldInLayout ) {
				$fieldCLOptionToggleInput.prop( 'checked', false );
			}

			// Enable the Conditional Logic if it exists when moved outside the Repeater field.
			if ( ! isFieldInRepeater && ! isFieldInLayout && ! $fieldCLOptionToggleInput.is( ':checked' ) ) {
				const hasCLGroups = $fieldCLOptionRow.siblings( '.wpforms-conditional-groups' ).length;

				$fieldCLOptionToggleInput.prop( 'checked', hasCLGroups );
			}

			const isCLHasGroups = $fieldCLBlock.find( '.wpforms-conditional-groups' ).length;

			// Notice text.
			$fieldCLOptionNotice.find( 'p' ).text( isCLHasGroups ? wpforms_builder[ parentBlockType ].cl_notice_text_grp : wpforms_builder[ parentBlockType ].cl_notice_text );

			// Toggle disabled state and notice visibility.
			$fieldCLBlock.toggleClass( 'wpforms-disabled', isFieldInRepeater || isFieldInLayout );
			$fieldCLOptionNotice.toggleClass( 'wpforms-hidden', ! isFieldInRepeater && ! isFieldInLayout );
		},

		/**
		 * Is the Conditional Logic enabled for the Layout field.
		 *
		 * @since 1.9.0
		 *
		 * @param {jQuery} $field Layout field.
		 *
		 * @return {boolean} Whether the Conditional Logic is enabled.
		 */
		isLayoutCLEnabled( $field ) {
			const fieldId = $field.data( 'field-id' );
			const $fieldCLOptionRow = $( `#wpforms-field-option-row-${ fieldId }-conditional_logic` );
			const $fieldCLOptionToggle = $fieldCLOptionRow.find( '.wpforms-toggle-control' );
			const $fieldCLOptionToggleInput = $fieldCLOptionToggle.find( 'input' );

			return $fieldCLOptionToggleInput.is( ':checked' );
		},

		/**
		 * Adjust rows appearance: buttons container position, column placeholder visibility.
		 *
		 * @since 1.8.9
		 *
		 * @param {number|string} fieldId Field ID.
		 */
		adjustRowsAppearance( fieldId ) {
			const $fieldPreview = $( '#wpforms-field-' + fieldId + '.wpforms-field-repeater' );

			if ( ! $fieldPreview.length ) {
				return;
			}

			const display = $( `#wpforms-field-option-row-${ fieldId }-display input:checked` ).val();
			const $columns = $fieldPreview.find( '.wpforms-layout-column' );
			const $rowsButtons = $fieldPreview.find( '.wpforms-field-repeater-display-rows-buttons' );
			let inputTopMin = 0;

			$columns.each( function() { // eslint-disable-line complexity
				const $column = $( this );
				const $field = $column.find( '.wpforms-field' ).first();
				const $alert = $column.find( '.wpforms-alert' );

				// Toggle column placeholder visibility.
				$column.toggleClass(
					'hide-placeholder',
					( $field.length > 0 || $alert.length > 0 ) && display === 'rows'
				);

				// Column without fields shouldn't affect `inputTopMin`.
				if ( ! $field.length && ! $alert.length ) {
					return;
				}

				let inputTop = $field.find( '.label-title' ).height() || 0;

				// Determine the top position if there is alert in the column.
				inputTop = $alert.length > 0
					? ( $alert.height() / 2 ) - ( $rowsButtons.height() / 2 ) - 4
					: inputTop;

				// Determine minimum input's top position in a row.
				inputTopMin = inputTop < inputTopMin || inputTopMin === 0 ? inputTop : inputTopMin;
			} );

			const labelHeight = $fieldPreview.find( '> .label-title' ).outerHeight() || 20;
			const fieldTop = labelHeight + 30;

			$fieldPreview
				.find( '.wpforms-field-layout-columns' )
				.css( 'margin-top', '-' + fieldTop + 'px' )
				.find( '.wpforms-layout-column' )
				.css( {
					'padding-top': fieldTop,
					'min-height' : fieldTop + 55,
				} );

			// Update the row buttons' position only if the columns have no drag placeholder (blue rectangle).
			if ( $columns.find( '.wpforms-field-drag-placeholder' ).length === 0 ) {
				const $columnPlaceholder = $fieldPreview.find( '.wpforms-layout-display-rows .wpforms-layout-column-placeholder' );
				const top = inputTopMin !== 0 ? inputTopMin + labelHeight + 47 : labelHeight + 16;

				$rowsButtons.css( 'top', top );
				$columnPlaceholder.css( 'top', top + 14 );
			}

			// Row with all hidden placeholders shouldn't have extra bottom padding.
			const $hiddenPlaceholders = $fieldPreview.find( '.wpforms-layout-column.hide-placeholder' );
			const $row = $fieldPreview.find( '.wpforms-field-layout-columns' );

			$row.toggleClass( 'hidden-placeholders', $hiddenPlaceholders.length === $columns.length );
		},

		/**
		 * Filter whether the field is allowed in column.
		 *
		 * @since 1.8.9
		 *
		 * @param {boolean|string} isFieldAllowed Whether the field is allowed to be placed in the column.
		 * @param {string}         fieldType      Field type.
		 * @param {jQuery}         $targetColumn  Target column element.
		 *
		 * @return {boolean|string} Whether the field is allowed.
		 */
		filterIsFieldAllowedDragInColumn( isFieldAllowed, fieldType, $targetColumn ) { // eslint-disable-line complexity
			vars.fieldMoveToRowsRejected = false;
			vars.fieldTypeRejected = ! isFieldAllowed ? fieldType : false;

			// Skip if the field is not allowed OR the target is not a column, return the original value.
			if ( ! isFieldAllowed || ! $targetColumn?.length || ! $targetColumn?.hasClass( 'wpforms-layout-column' ) ) {
				return isFieldAllowed;
			}

			const $repeaterField = $targetColumn?.closest( '.wpforms-field-repeater' );

			if ( ! $repeaterField?.length ) {
				return isFieldAllowed;
			}

			const repeaterFieldId = $repeaterField.data( 'field-id' );
			const display = $( `#wpforms-field-option-row-${ repeaterFieldId }-display input:checked` ).val();

			// Allow adding many fields. Skip if the display is not `rows`.
			if ( display !== 'rows' ) {
				return isFieldAllowed;
			}

			// Allow adding one field. The column doesn't contain fields.
			if ( ! $targetColumn.find( '.wpforms-field:not(.wpforms-field-dragging)' ).length ) {
				return isFieldAllowed;
			}

			vars.fieldMoveToRowsRejected = true;

			// Disallow adding field if the column already contains some field.
			return false;
		},

		/**
		 * Filter the field move rejected alert modal options.
		 *
		 * @since 1.8.9
		 *
		 * @param {Object} modalOptions  Field move rejected alert modal options.
		 * @param {jQuery} $field        Field element object.
		 * @param {Object} ui            Sortable ui object.
		 * @param {jQuery} $targetColumn Target column element.
		 *
		 * @return {Object} Updated the field move rejected alert modal options.
		 */
		filterFieldMoveRejectedModalOptions( modalOptions, $field, ui, $targetColumn ) {
			const $repeaterField = $targetColumn?.closest( '.wpforms-field-repeater' );

			if ( ! $repeaterField?.length ) {
				return modalOptions;
			}

			const updatedModalOptions = {
				title: strings.not_allowed,
				content: strings.move_to_rows_rejected_alert,
				type: 'orange',
			};

			// The field is rejected by type, return the message for the Repeater field.
			if ( vars.fieldTypeRejected ) {
				const name = $( `#wpforms-add-fields-${ vars.fieldTypeRejected }` ).text();

				updatedModalOptions.content = strings.not_allowed_alert_text.replace( /%s/g, `<strong>${ name }</strong>` );

				return updatedModalOptions;
			}

			// The field move to rows is not rejected, return original message.
			if ( ! vars.fieldMoveToRowsRejected ) {
				return modalOptions;
			}

			updatedModalOptions.content = strings.move_to_rows_rejected_alert;

			// The field move to rows is rejected, return the message for the Repeater field.
			return updatedModalOptions;
		},

		/**
		 * Remove the repeater field and its child fields from the fields' data.
		 *
		 * @since 1.8.9
		 *
		 * @param {Object} fields Fields data.
		 *
		 * @return {Object} Filtered list of fields.
		 */
		removeRepeaterFieldsAndChildren( fields ) { // eslint-disable-line complexity
			if ( ! fields ) {
				return {};
			}

			for ( const key in fields ) {
				if ( fields[ key ].type !== 'repeater' ) {
					continue;
				}

				const columns = fields[ key ][ 'columns-json' ];

				// Remove the Repeater itself.
				delete fields[ key ];

				if ( ! columns.length ) {
					continue;
				}

				// Remove all child fields.
				for ( const col of columns ) {
					if ( ! col.fields?.length ) {
						continue;
					}

					for ( const colField of col.fields ) {
						delete fields[ colField ];
					}
				}
			}

			return fields;
		},

		/**
		 * Display error modal.
		 *
		 * @since 1.8.9
		 *
		 * @param {string} title   Title.
		 * @param {string} message Message text.
		 */
		errorModal( title, message ) {
			$.confirm( {
				title,
				content: message,
				icon: 'fa fa-exclamation-circle',
				type: 'orange',
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
		 * Whether the field type is allowed to be in column.
		 *
		 * @since 1.8.9
		 *
		 * @param {string} fieldType Field type to check.
		 *
		 * @return {boolean} True if allowed.
		 */
		isFieldAllowedInColum( fieldType ) {
			return strings.not_allowed_fields.indexOf( fieldType ) < 0;
		},

		/**
		 * Filter the fields which are not allowed.
		 *
		 * @since 1.8.9
		 *
		 * @param {boolean} isAllowed     Whether the field is allowed.
		 * @param {string}  fieldType     Field type to check.
		 * @param {jQuery}  $targetColumn Target column element.
		 *
		 * @return {boolean} True if allowed.
		 */
		filterIsFieldAllowedInColumn( isAllowed, fieldType, $targetColumn ) {
			// Skip if the field is not allowed OR the target is not a column, return the original value.
			if ( ! isAllowed || ! $targetColumn?.length || ! $targetColumn?.hasClass( 'wpforms-layout-column' ) ) {
				return isAllowed;
			}

			const $repeaterField = $targetColumn?.closest( '.wpforms-field-repeater' );

			if ( ! $repeaterField?.length ) {
				return isAllowed;
			}

			return app.isFieldAllowedInColum( fieldType );
		},
	};

	// Provide access to public functions/properties.
	return app;
}( document, window, jQuery ) );

// Initialize.
WPForms.Admin.Builder.FieldRepeater.init();
