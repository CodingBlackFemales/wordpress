/* global WPFormsBuilder, wpforms_builder, WPFormsUtils */

// noinspection ES6ConvertVarToLetConst
/**
 * Form Builder Field Layout module.
 *
 * @since 1.7.7
 */

var WPForms = window.WPForms || {}; // eslint-disable-line no-var

WPForms.Admin = WPForms.Admin || {};
WPForms.Admin.Builder = WPForms.Admin.Builder || {};

WPForms.Admin.Builder.FieldLayout = WPForms.Admin.Builder.FieldLayout || ( function( document, window, $ ) {
	/**
	 * Elements holder.
	 *
	 * @since 1.7.7
	 *
	 * @type {Object}
	 */
	let el = {};

	/**
	 * Public functions and properties.
	 *
	 * @since 1.7.7
	 *
	 * @type {Object}
	 */
	const app = {
		/**
		 * Start the engine.
		 *
		 * @since 1.7.7
		 */
		init() {
			$( app.ready );
		},

		/**
		 * DOM is fully loaded.
		 *
		 * @since 1.7.7
		 */
		ready() {
			app.setup();
			app.initLabels();
			app.events();
			app.rowDisplayHeightBalance( $( '.wpforms-layout-display-rows, .wpforms-layout-display-blocks' ) );
		},

		/**
		 * Setup. Prepare some variables.
		 *
		 * @since 1.7.7
		 */
		setup() {
			// Cache DOM elements.
			el = {
				$builder:            $( '#wpforms-builder' ),
				$fieldOptions:       $( '#wpforms-field-options' ),
				$sortableFieldsWrap: $( '#wpforms-panel-fields .wpforms-field-wrap' ),
			};
		},

		/**
		 * Bind events.
		 *
		 * @since 1.7.7
		 */
		events() {
			el.$builder
				.on( 'change', '.wpforms-field-option-row-preset input', app.presetChange )
				.on( 'change', '.wpforms-field-option-row-display select', app.displayChange )
				.on( 'mouseenter', '.wpforms-field-layout-columns .wpforms-field', app.subfieldMouseEnter )
				.on( 'mouseleave', '.wpforms-field-layout-columns .wpforms-field', app.subfieldMouseLeave )
				.on( 'wpformsFieldAdd', app.fieldAdd )
				.on( 'wpformsBeforeFieldAddToDOM', app.beforeFieldAddToDOM )
				.on( 'wpformsBeforeFieldAddOnClick', app.beforeFieldAddOnClick )
				.on( 'wpformsBeforeFieldDelete', app.beforeFieldDelete )
				.on( 'wpformsBeforeFieldDeleteAlert', app.adjustDeleteFieldAlert )
				.on( 'wpformsFieldOptionTabToggle', app.fieldOptionsUpdate )
				.on( 'wpformsFieldMoveRejected', app.fieldMoveRejected )
				.on( 'wpformsBeforeFieldDuplicate', app.beforeFieldDuplicate )
				.on( 'wpformsFieldDuplicated', app.fieldDuplicated )
				.on( 'wpformsFieldDelete', app.handleFieldDelete )
				.on( 'wpformsFieldAdd wpformsFieldChoiceAdd wpformsFieldChoiceDelete wpformsFieldDynamicChoiceToggle wpformsFieldLayoutChangeDisplay', app.handleFieldOperations )
				.on( 'wpformsFieldMoveRejected', app.handleFieldMoveRejected )
				.on( 'wpformsFieldMove', app.handleFieldMove )
				.on( 'wpformsFieldDragOver wpformsFieldDragChange', app.handleFieldDrag )
				.on( 'change', '.wpforms-field-option-row-size select', app.handleFieldSizeChange );
		},

		/**
		 * Determine whether the field type is a layout-based field.
		 *
		 * @since 1.8.9
		 *
		 * @param {string} fieldType Field type.
		 *
		 * @return {boolean} True if it is the layout-based field.
		 */
		isLayoutBasedField( fieldType ) {
			return [ 'layout', 'repeater' ].includes( fieldType );
		},

		/**
		 * Field delete event handler.
		 *
		 * @since 1.8.8
		 *
		 * @param {Object} e                   Event object.
		 * @param {string} fieldID             Field ID.
		 * @param {string} fieldType           Field type.
		 * @param {jQuery} $fieldLayoutWrapper Field layout wrapper.
		 */
		handleFieldDelete( e, fieldID, fieldType, $fieldLayoutWrapper ) {
			if ( $fieldLayoutWrapper.length === 0 || ! $fieldLayoutWrapper.hasClass( 'wpforms-layout-display-rows' ) ) {
				return;
			}

			app.rowDisplayHeightBalance( $fieldLayoutWrapper );
		},

		/**
		 * Field change size event handler.
		 *
		 * @since 1.9.0
		 */
		handleFieldSizeChange() {
			const $this = $( this );
			const fieldID = $this.parent().data( 'field-id' );
			const $field = $( `#wpforms-field-${ fieldID }` );
			const $rows = $field.parents( '.wpforms-layout-display-rows, .wpforms-layout-display-blocks' );

			if ( ! $rows.length ) {
				return;
			}

			app.rowDisplayHeightBalance( $rows );
		},

		/**
		 * Field operations event handler.
		 *
		 * @since 1.8.8
		 *
		 * @param {Object} e       Event object.
		 * @param {string} fieldID Field ID.
		 */
		handleFieldOperations( e, fieldID ) {
			const $field = $( `#wpforms-field-${ fieldID }` );
			const $rows = $field.find( '.wpforms-layout-display-rows, .wpforms-layout-display-blocks' );

			if ( ! $rows.length ) {
				return;
			}

			app.rowDisplayHeightBalance( $rows );
		},

		/**
		 * Field move rejected event handler.
		 *
		 * @since 1.8.8
		 *
		 * @param {Object} e              Event object.
		 * @param {jQuery} $rejectedField Rejected a field object.
		 */
		handleFieldMoveRejected( e, $rejectedField ) {
			const fieldID = $rejectedField.prev( '.wpforms-field, .wpforms-alert' ).data( 'field-id' );
			const $field = $( `#wpforms-field-${ fieldID }` );
			const $rows = $field.find( '.wpforms-layout-display-rows, .wpforms-layout-display-blocks' );

			if ( ! $rows.length ) {
				return;
			}

			app.rowDisplayHeightBalance( $rows );
		},

		/**
		 * Field move event handler.
		 *
		 * @since 1.8.8
		 *
		 * @param {Object} e  Event object.
		 * @param {Object} ui UI object.
		 */
		handleFieldMove( e, ui ) {
			const $field = ui.item.first();
			const $rows = $field.parents( '.wpforms-layout-display-rows, .wpforms-layout-display-blocks' );

			if ( ! $rows.length ) {
				return;
			}

			app.rowDisplayHeightBalance( $rows );
		},

		/**
		 * Field drag event handler.
		 *
		 * @since 1.8.8
		 *
		 * @param {Object} e       Event object.
		 * @param {string} fieldID Field ID.
		 * @param {jQuery} $target Target element.
		 */
		handleFieldDrag( e, fieldID, $target ) {
			if ( ! $target.hasClass( 'wpforms-layout-column' ) ) {
				return;
			}

			const $rows = $target.parents( '.wpforms-layout-display-rows, .wpforms-layout-display-blocks' );

			if ( ! $rows.length ) {
				return;
			}

			app.rowDisplayHeightBalance( $rows );
		},

		/**
		 * Display option event handler.
		 *
		 * @since 1.8.8
		 */
		displayChange() {
			const $select = $( this );
			const display = $select.val();
			const fieldId = $select.closest( '.wpforms-field-option-row-display' ).data( 'field-id' );
			const $fieldPreviewColumns = $( `#wpforms-field-${ fieldId }` ).find( '.wpforms-field-layout-columns' );

			$select.closest( '.wpforms-field-option-row-display' ).parent().find( '.wpforms-field-option-row-preset' )
				.toggleClass( 'wpforms-layout-display-rows', display === 'rows' );

			$fieldPreviewColumns
				.toggleClass( 'wpforms-layout-display-rows', display === 'rows' )
				.toggleClass( 'wpforms-layout-display-columns', display === 'columns' )
				.find( '.wpforms-field' ).css( 'margin-bottom', display === 'columns' ? 5 : '' );

			el.$builder.trigger( 'wpformsFieldLayoutChangeDisplay', [ fieldId ] );
		},

		/**
		 * Display row fields height balance.
		 *
		 * @since 1.8.8
		 *
		 * @param {Object} $rows Rows container.
		 */
		rowDisplayHeightBalance( $rows ) {
			$rows.each( function() {
				const $wrapper = $( this );
				const data = [];

				$wrapper.find( '.wpforms-field, .wpforms-field-drag-placeholder' ).each( function() {
					const $field = $( this );
					const index = $field.index();

					data[ index ] = Math.max( data[ index ] || 0, $field.outerHeight() );
				} );

				$wrapper.find( '.wpforms-field, .wpforms-field-drag-placeholder' ).each( function() {
					const $field = $( this );

					$field.css( 'margin-bottom', data[ $field.index() ] - $field.outerHeight() + 5 );
				} );
			} );

			/**
			 * The Event fired when the height balance was performed.
			 *
			 * @since 1.8.9
			 *
			 * @param {Object} data Layout field data object.
			 */
			el.$builder.trigger( 'wpformsLayoutAfterHeightBalance', { $rows } );
		},

		/**
		 * Column placeholder click event handler.
		 *
		 * @since 1.7.7
		 *
		 * @param {Object} e Event object.
		 */
		presetChange( e ) { // eslint-disable-line no-unused-vars
			const $input = $( this ),
				preset = $input.val(),
				$presetsRow = $input.closest( '.wpforms-field-option-row-preset' ),
				fieldId = $presetsRow.data( 'field-id' ),
				$fieldPreview = $( `#wpforms-field-${ fieldId }` ),
				$fieldPreviewColumns = $fieldPreview.find( '.wpforms-field-layout-columns' ),
				$columnFields = [];

			// Detach and store in an array all the fields from columns.
			$fieldPreviewColumns.find( '.wpforms-layout-column' ).each( function( columnIndex ) {
				$columnFields[ columnIndex ] = $( this ).find( '.wpforms-field' ).detach();
			} );

			const oldColumnsData = app.getFieldColumnsData( fieldId ),
				newColumnsData = preset.split( '-' )
					.map( function( width, i ) {
						return {
							width_preset: width, // eslint-disable-line camelcase
							fields: oldColumnsData[ i ] ? oldColumnsData[ i ].fields : [],
						};
					} );

			// Generate new columns markup. Update field columns data.
			app.updateFieldColumnsData( fieldId, newColumnsData );
			$fieldPreviewColumns.html( app.generatePreviewColumns( newColumnsData ) );

			// Restore all the fields in new columns.
			$fieldPreviewColumns.find( '.wpforms-layout-column' ).each( function( columnIndex ) {
				const $column = $( this );

				$column.append( $columnFields[ columnIndex ] );
				WPForms.Admin.Builder.DragFields.initSortableContainer( $column );
			} );

			// When the new preset has fewer columns than the old one.
			if ( newColumnsData.length < oldColumnsData.length ) {
				// Combine all the remaining fields.
				let $allRemainingFields = $( [] );

				for ( let i = newColumnsData.length; i < oldColumnsData.length; i++ ) {
					$allRemainingFields = $allRemainingFields.add( $columnFields[ i ] );
				}

				// Remove custom margin-bottom from remaining fields.
				$allRemainingFields.css( 'margin-bottom', '' );

				// Add all the remaining fields to the base level after the layout field.
				$fieldPreview.after( $allRemainingFields );
			}

			app.rowDisplayHeightBalance( $fieldPreviewColumns );

			// Update the field options order inside the Layout field.
			app.reorderLayoutFieldsOptions( $fieldPreview );

			/**
			 * Event fired at the end of changing the layout preset.
			 *
			 * @since 1.7.8
			 *
			 * @param {Object} data Layout field data object.
			 */
			el.$builder.trigger( 'wpformsLayoutAfterPresetChange', { fieldId, preset, newColumnsData, oldColumnsData } );
		},

		/**
		 * Generate preview columns HTML.
		 *
		 * @since 1.7.7
		 *
		 * @param {Array} columnsData Columns data.
		 *
		 * @return {string} Preview columns HTML.
		 */
		generatePreviewColumns( columnsData ) {
			if ( ! columnsData?.length ) {
				return '';
			}

			const placeholder = wp.template( 'wpforms-layout-field-column-plus-placeholder-template' )();
			const columnsHTML = columnsData.map( function( column ) {
				return `<div class="wpforms-layout-column wpforms-layout-column-${ column.width_preset }">
							${ placeholder }
						</div>`;
			} );

			return columnsHTML.join( '' );
		},

		/**
		 * Get field columns data.
		 *
		 * @since 1.7.7
		 *
		 * @param {number} fieldId Field Id.
		 *
		 * @return {Array} Columns data.
		 */
		getFieldColumnsData( fieldId ) {
			const dataJson = $( `#wpforms-field-option-${ fieldId }-columns-json` ).val();
			let	data;

			try {
				data = JSON.parse( dataJson );
			} catch ( e ) {
				data = [];
			}

			return data;
		},

		/**
		 * Check if field with given ID is inside one of the columns in the layout with given ID.
		 *
		 * @since 1.7.8
		 *
		 * @param {number} layoutId The ID of the layout field.
		 * @param {number} fieldId  The ID of the field to look for.
		 *
		 * @return {boolean} If the field exists in the layout.
		 */
		columnsHasFieldID( layoutId, fieldId ) {
			/*
			 * Get field columns data, and filter it to have only those columns, which in column.fields has field ID value.
			 * Return true if the length of such reduced data is bigger than 0.
			 */
			return app.getFieldColumnsData( layoutId ).filter( function( column ) {
				return column.fields && column.fields.includes( fieldId );
			} ).length > 0;
		},

		/**
		 * Update field columns data.
		 *
		 * @since 1.7.7
		 * @since 1.7.8 Added new triggers: `wpformsLayoutColumnsDataUpdated`, `wpformsLayoutAfterUpdateColumnsData`.
		 *
		 * @param {number} fieldId Field ID.
		 * @param {Array}  data    Columns data.
		 */
		updateFieldColumnsData( fieldId, data ) {
			const $holder = $( `#wpforms-field-option-${ fieldId }-columns-json` ),
				currentColumnsData = $holder.val(),
				newColumnsData = JSON.stringify( data );

			$holder.val( newColumnsData );

			if ( currentColumnsData !== newColumnsData ) {
				el.$builder.trigger( 'wpformsLayoutColumnsDataUpdated', { fieldId, data } );
			}

			el.$builder.trigger( 'wpformsLayoutAfterUpdateColumnsData', { fieldId, data } );
		},

		/**
		 * Event `wpformsBeforeFieldAddToDOM` handler.
		 *
		 * @since 1.7.7
		 *
		 * @param {Event}  e           Event.
		 * @param {Object} options     Field add additional options.
		 * @param {jQuery} $newField   New field preview object.
		 * @param {jQuery} $newOptions New field options object.
		 * @param {jQuery} $sortable   Sortable container.
		 */
		beforeFieldAddToDOM( e, options, $newField, $newOptions, $sortable ) {
			if (
				! $sortable ||
				! $sortable.length ||
				! $sortable.hasClass( 'wpforms-layout-column' )
			) {
				return;
			}

			// This is needed to skip adding the field to the base level.
			// The corresponding check is in `admin-builder.js` before calling the `app.fieldAddToBaseLevel()`.
			e.skipAddFieldToBaseLevel = true;

			// Add the field to the column of the Layout field.
			app.fieldAddToColumn(
				$newField,
				$newOptions,
				options.position,
				$sortable
			);
		},

		/**
		 * Event `wpformsFieldAdd` handler.
		 *
		 * @since 1.7.7
		 *
		 * @param {Event}  e       Event.
		 * @param {number} fieldId Field id.
		 * @param {string} type    Field type.
		 */
		fieldAdd( e, fieldId, type ) {
			const $fieldOptions = $( `#wpforms-field-option-${ fieldId }` ),
				$prevFieldOptions = $fieldOptions.prev(),
				prevFieldType = $prevFieldOptions.find( '.wpforms-field-option-hidden-type' ).val();

			// In the case of new field was placed right after the Layout field,
			// we must reorder the options of the fields in the columns of this layout.
			if ( app.isLayoutBasedField( prevFieldType ) ) {
				const prevFieldId = $prevFieldOptions.find( '.wpforms-field-option-hidden-id' ).val();

				app.reorderLayoutFieldsOptions(
					$( `#wpforms-field-${ prevFieldId }` )
				);
			}

			if ( ! app.isLayoutBasedField( type ) ) {
				return;
			}

			el.$builder.find( `#wpforms-field-${ fieldId } .wpforms-layout-column` ).each( function() {
				WPForms.Admin.Builder.DragFields.initSortableContainer( $( this ) );
			} );
		},

		/**
		 * Event `wpformsBeforeFieldAddOnClick` handler.
		 *
		 * @since 1.7.7
		 *
		 * @param {Event}  e      Event.
		 * @param {string} type   Field type.
		 * @param {jQuery} $field Field button object
		 */
		beforeFieldAddOnClick( e, type, $field ) {
			const $defaultColumn = el.$sortableFieldsWrap.find( '.wpforms-fields-sortable-default' );

			if ( ! $defaultColumn.length ) {
				return;
			}

			if ( app.isFieldAllowedInColum( type, $defaultColumn ) ) {
				return;
			}

			e.preventDefault();
			app.fieldMoveRejected( e, $field, null, null );
		},

		/**
		 * Event `wpformsBeforeFieldDelete` handler.
		 *
		 * @since 1.7.7
		 *
		 * @param {Event}  e       Event.
		 * @param {number} fieldId Field id.
		 * @param {string} type    Field type.
		 */
		beforeFieldDelete( e, fieldId, type ) {
			if ( ! app.isLayoutBasedField( type ) ) {
				app.removeFieldFromColumns( fieldId );

				// When a user deletes the field in the column, we need to remove
				// the class `wpforms-field-child-hovered` from the Layout field
				// to bring back the Duplicate and Delete icons.
				$( `#wpforms-field-${ fieldId }` )
					.closest( '.wpforms-field' )
					.removeClass( 'wpforms-field-child-hovered' );

				return;
			}

			const columnsData = app.getFieldColumnsData( fieldId );

			// Delete all the fields inside columns.
			columnsData.forEach( function( column ) {
				if ( ! Array.isArray( column.fields ) ) {
					return;
				}

				column.fields.forEach( function( columnFieldId ) {
					WPFormsBuilder.fieldDeleteById( columnFieldId );
				} );
			} );
		},

		/**
		 * Adjust warning text for delete field alert.
		 *
		 * @since 1.7.7
		 *
		 * @param {Object} e         Triggered event.
		 * @param {Object} fieldData Field data.
		 * @param {string} type      Field type.
		 */
		adjustDeleteFieldAlert( e, fieldData, type ) {
			if ( ! app.isLayoutBasedField( type ) ) {
				return;
			}

			e.preventDefault();

			const content = wpforms_builder[ type ]?.delete_confirm
				? wpforms_builder[ type ].delete_confirm
				: wpforms_builder.layout.delete_confirm;

			$.confirm( {
				title: false,
				content,
				icon: 'fa fa-exclamation-circle',
				type: 'orange',
				buttons: {
					confirm: {
						text: wpforms_builder.ok,
						btnClass: 'btn-confirm',
						keys: [ 'enter' ],
						action() {
							WPFormsBuilder.fieldDeleteById( fieldData.id );
						},
					},
					cancel: {
						text: wpforms_builder.cancel,
					},
				},
			} );
		},

		/**
		 * Update field options according to the position of the field.
		 * Event `wpformsFieldOptionTabToggle` handler.
		 *
		 * @since 1.7.7
		 *
		 * @param {Event}  e       Event.
		 * @param {number} fieldId Field id.
		 */
		fieldOptionsUpdate( e, fieldId ) {
			app.fieldLegacyLayoutSelectorUpdate( fieldId );
			app.fieldSizeOptionUpdate( fieldId );
		},

		/**
		 * Update the legacy layout option.
		 *
		 * @since 1.7.7
		 *
		 * @param {number} fieldId Field ID.
		 */
		fieldLegacyLayoutSelectorUpdate( fieldId ) {
			const $fieldLegacyLayoutSelector = $( `#wpforms-field-option-row-${ fieldId }-css .layout-selector-display` );

			let	$fieldLegacyLayoutNotice = $fieldLegacyLayoutSelector.find( '.wpforms-alert-layout' );

			if ( $fieldLegacyLayoutNotice.length ) {
				return;
			}

			// Add "Layouts Have Moved!" notice.
			$fieldLegacyLayoutNotice = $( `
				<div class="wpforms-alert-warning wpforms-alert-layout wpforms-alert wpforms-alert-nomargin">
					<h4>${ wpforms_builder.layout.legacy_layout_notice_title }</h4>
					<p>${ wpforms_builder.layout.legacy_layout_notice_text }</p>
				</div>
			` );

			$fieldLegacyLayoutSelector.append( $fieldLegacyLayoutNotice );

			// Hide the legacy layout selector.
			$fieldLegacyLayoutSelector.find( '.heading, .layouts' ).addClass( 'wpforms-hidden-strict' );
		},

		/**
		 * Update the Field Size option according to the position of the field.
		 *
		 * @since 1.7.7
		 *
		 * @param {number} fieldId Field ID.
		 */
		fieldSizeOptionUpdate( fieldId ) {
			const $field = $( `#wpforms-field-${ fieldId }` ),
				type = $field.data( 'field-type' ),
				$column = $field.closest( '.wpforms-layout-column' ),
				isFieldInColumn = $column.length > 0,
				parentType = $column.closest( '.wpforms-field' ).data( 'field-type' ) ?? 'layout';

			// Do not touch the Field Size option for certain fields.
			if ( [ 'textarea', 'richtext' ].indexOf( type ) > -1 ) {
				return;
			}

			const $fieldSizeOptionRow = $( `#wpforms-field-option-row-${ fieldId }-size` );
			let	$fieldSizeOptionNotice = $fieldSizeOptionRow.find( '.wpforms-notice-field-size' );
			const isColumnFullWidth = $column.hasClass( 'wpforms-layout-column-100' ) && $field.closest( '.wpforms-field-layout' ).length;

			// Add "Field size cannot be changed" notice.
			$fieldSizeOptionNotice.remove();
			$fieldSizeOptionNotice = $( `
				<label class="sub-label wpforms-notice-field-size" title="${ wpforms_builder[ parentType ].size_notice_tooltip }">
					${ wpforms_builder[ parentType ].size_notice_text }
				</label>
			` );

			$fieldSizeOptionRow.append( $fieldSizeOptionNotice );

			const $fieldSizeOptionSelect = $fieldSizeOptionRow.find( 'select' );

			// Toggle field size selector title attribute.
			if ( isFieldInColumn ) {
				$fieldSizeOptionSelect.attr( 'title', wpforms_builder[ parentType ].size_notice_tooltip );
			} else {
				$fieldSizeOptionSelect.attr( 'title', '' );
			}

			// Toggle field size selector `disabled` attribute and notice visibility.
			const isDisabled = isFieldInColumn && ! isColumnFullWidth;

			$fieldSizeOptionSelect.toggleClass( 'wpforms-disabled', isDisabled );
			$fieldSizeOptionNotice.toggleClass( 'wpforms-hidden', ! isDisabled );
		},

		/**
		 * Receive field to column inside the Layout Field.
		 *
		 * @since 1.7.7
		 *
		 * @param {number} fieldId   Field ID.
		 * @param {number} position  Field position inside the column.
		 * @param {jQuery} $sortable Sortable column container.
		 */
		receiveFieldToColumn( fieldId, position, $sortable ) {
			// Remove the field from all the columns.
			app.removeFieldFromColumns( fieldId );

			// Do not need to continue if the field dropped to the main fields wrap.
			if ( ! $sortable || ! $sortable.hasClass( 'wpforms-layout-column' ) ) {
				return;
			}

			app.positionFieldInColumn( fieldId, position, $sortable );
			app.fieldOptionsUpdate( null, fieldId );

			/**
			 * Trigger on the end of the process of receiving field in layout's column.
			 *
			 * @since 1.7.8
			 *
			 * @param {Object} data Field data object.
			 */
			el.$builder.trigger( 'wpformsLayoutAfterReceiveFieldToColumn', { fieldId, position, column: $sortable } );
		},

		/**
		 * Remove field from all the columns in all Layout fields.
		 *
		 * @since 1.7.7
		 *
		 * @param {number} fieldId Field ID.
		 */
		removeFieldFromColumns( fieldId ) {
			fieldId = Number( fieldId );

			el.$builder.find( '.wpforms-field' ).each( function() {
				const $field = $( this );

				if ( ! app.isLayoutBasedField( $field.data( 'field-type' ) ) ) {
					return;
				}

				const layoutFieldId = Number( $field.data( 'field-id' ) );
				const columnsData = app.getFieldColumnsData( layoutFieldId );

				for ( let i = 0; i < columnsData.length; i++ ) {
					if ( ! Array.isArray( columnsData[ i ].fields ) ) {
						continue;
					}

					// Remove field from the column's list of fields.
					columnsData[ i ].fields = columnsData[ i ].fields.filter( function( id ) {
						return Number( id ) !== fieldId;
					} );
				}

				app.updateFieldColumnsData( layoutFieldId, columnsData );
			} );
		},

		/**
		 * Position field in the column inside the Layout Field.
		 *
		 * @since 1.7.7
		 *
		 * @param {number} fieldId   Field ID.
		 * @param {number} position  The new position of the field inside the column.
		 * @param {jQuery} $sortable Sortable column container.
		 */
		positionFieldInColumn( fieldId, position, $sortable ) {
			// Proceed only in the column.
			if ( ! $sortable || ! $sortable.hasClass( 'wpforms-layout-column' ) ) {
				return;
			}

			const $layoutField = $sortable.closest( '.wpforms-field' ),
				layoutFieldId = $layoutField.data( 'field-id' ),
				columnIndex = $sortable.index();

			const columnsData = app.getFieldColumnsData( layoutFieldId );

			// Skip if there is no data of the column.
			if ( ! columnsData || ! columnsData[ columnIndex ] ) {
				return;
			}

			const column = columnsData[ columnIndex ];

			column.fields = Array.isArray( column.fields ) ? column.fields : [];

			fieldId = Number( fieldId );

			// Remove field from the column.
			column.fields = column.fields.filter( function( id ) {
				return Number( id ) !== fieldId;
			} );

			// Add field to the new position in the column.
			column.fields.splice( position, 0, fieldId );

			columnsData[ columnIndex ] = column;

			app.updateFieldColumnsData( layoutFieldId, columnsData );
		},

		/**
		 * Duplicate Layout field.
		 *
		 * @since 1.7.7
		 *
		 * @param {number} layoutFieldId Layout field ID.
		 */
		duplicateLayoutField( layoutFieldId ) {
			const $layoutField = $( `#wpforms-field-${ layoutFieldId }` );

			if ( ! app.isLayoutBasedField( $layoutField.data( 'field-type' ) ) ) {
				return;
			}

			const columnsData = app.getFieldColumnsData( layoutFieldId ),
				newLayoutFieldID = WPFormsBuilder.fieldDuplicateRoutine( layoutFieldId ),
				$newLayoutField = $( `#wpforms-field-${ newLayoutFieldID }` ),
				$newLayoutFieldOptions = $( `#wpforms-field-option-${ newLayoutFieldID }` ),
				$newLayoutFieldColumn = $newLayoutField.find( '.wpforms-layout-column' ),
				newColumnsData = JSON.parse( JSON.stringify( columnsData ) ),
				preset = $( `#wpforms-field-option-${ layoutFieldId } .wpforms-field-option-row-preset input:checked` ).val();

			// Duplicate preset option value.
			$newLayoutFieldOptions.find( `#wpforms-field-option-${ newLayoutFieldID }-preset-${ preset }` ).prop( 'checked', true );

			// Delete the fields from the columns.
			$newLayoutField.find( '.wpforms-layout-column .wpforms-field' ).remove();

			// Reset "active column" state.
			$newLayoutField.find( '.wpforms-fields-sortable-default' ).removeClass( 'wpforms-fields-sortable-default' );

			columnsData.forEach( function( column, index ) {
				newColumnsData[ index ].fields = [];

				if ( ! Array.isArray( column.fields ) ) {
					return;
				}

				const $newColumn = $newLayoutFieldColumn.eq( index );

				column.fields.forEach( function( fieldId ) {
					const $field = $( `#wpforms-field-${ fieldId }` );

					// Skip if there is no field OR duplicate field button.
					if ( ! $field.length || ! $field.find( '> .wpforms-field-duplicate' ).length ) {
						return;
					}

					const newFieldID = WPFormsBuilder.fieldDuplicateRoutine( fieldId ),
						$newField = $( `#wpforms-field-${ newFieldID }` ).detach().removeClass( 'active' ),
						$newFieldOptions = $( `#wpforms-field-option-${ newFieldID }` );

					$newColumn.append( $newField );
					$newFieldOptions.hide();
					newColumnsData[ index ].fields.push( newFieldID );
				} );
			} );

			app.updateFieldColumnsData( newLayoutFieldID, newColumnsData );
			app.reorderLayoutFieldsOptions( $newLayoutField );

			// Activate duplicate field to keep consistent behavior with other fields.
			$newLayoutField.trigger( 'click' );

			WPFormsUtils.triggerEvent( el.$builder, 'wpformsFieldDuplicated', [ layoutFieldId, $layoutField, newLayoutFieldID, $newLayoutField ] );
		},

		/**
		 * Subfield mouseenter event handler.
		 *
		 * @since 1.7.7
		 *
		 * @param {Object} e Event object.
		 */
		subfieldMouseEnter( e ) { // eslint-disable-line no-unused-vars
			$( this )
				.closest( '.wpforms-field-layout-columns' ).closest( '.wpforms-field' )
				.addClass( 'wpforms-field-child-hovered' );
		},

		/**
		 * Subfield mouseleave event handler.
		 *
		 * @since 1.7.7
		 *
		 * @param {Object} e Event object.
		 */
		subfieldMouseLeave( e ) { // eslint-disable-line no-unused-vars
			$( this )
				.closest( '.wpforms-field-layout-columns' ).closest( '.wpforms-field' )
				.removeClass( 'wpforms-field-child-hovered' );
		},

		/**
		 * Init labels in all the Layout fields.
		 *
		 * @since 1.7.7
		 */
		initLabels() {
			$( '.wpforms-field-option-layout .wpforms-field-option-row-label input' ).trigger( 'input' );
		},

		/**
		 * Add field to the column of the Layout field.
		 *
		 * @since 1.7.7
		 *
		 * @param {jQuery}        $newField   New field preview.
		 * @param {jQuery}        $newOptions New field options.
		 * @param {number|string} position    New field position.
		 * @param {jQuery}        $column     Sortable column container.
		 */
		fieldAddToColumn( $newField, $newOptions, position, $column ) {
			const $fields = $column.find( '.wpforms-field' );

			if ( position === 'bottom' ) {
				position = $fields.length;
			}

			const $fieldInPosition = $fields.eq( position ),
				fieldInPositionId = $fieldInPosition.data( 'field-id' );

			if ( $fieldInPosition.length ) {
				$fieldInPosition.before( $newField );
			} else {
				$column.append( $newField );
			}

			const $fieldOptionsInPosition = $( `#wpforms-field-option-${ fieldInPositionId }` );

			if ( $fieldOptionsInPosition.length ) {
				$fieldOptionsInPosition.before( $newOptions );
			} else {
				el.$fieldOptions.append( $newOptions );
			}

			app.receiveFieldToColumn(
				$newField.data( 'field-id' ),
				position,
				$column
			);

			app.reorderLayoutFieldsOptions(
				$column.closest( '.wpforms-field-layout' )
			);
		},

		/**
		 * Display "This field is not allowed" alert modal.
		 * Event `wpformsFieldMoveRejected` handler.
		 *
		 * @since 1.7.7
		 *
		 * @param {Event}  e             Event.
		 * @param {jQuery} $field        Field object.
		 * @param {Object} ui            Sortable ui object.
		 * @param {jQuery} $targetColumn Target column element.
		 */
		fieldMoveRejected( e, $field, ui, $targetColumn ) { // eslint-disable-line no-unused-vars
			const type = $field.data( 'field-type' );
			const name = type ? $( `#wpforms-add-fields-${ type }` ).text() : $field.text();

			let modalOptions = {
				title: wpforms_builder.heads_up,
				content: wpforms_builder.layout.not_allowed_alert_text.replace( /%s/g, `<strong>${ name }</strong>` ),
				type: 'red',
			};

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
			 * @return {string} Filtered the field rejected message.
			 */
			modalOptions = wp.hooks.applyFilters( 'wpforms.LayoutField.fieldMoveRejectedModalOptions', modalOptions, $field, ui, $targetColumn );

			$.confirm( {
				title: modalOptions.title,
				content: modalOptions.content,
				icon: 'fa fa-exclamation-circle',
				type: modalOptions.type,
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
		 * Reorder fields options of the fields in columns.
		 * It is critical for proper functioning EP and PB fields.
		 *
		 * @since 1.7.7
		 *
		 * @param {jQuery} $layoutField Layout field object.
		 */
		reorderLayoutFieldsOptions( $layoutField ) {
			if ( ! $layoutField?.length || ! app.isLayoutBasedField( $layoutField.data( 'field-type' ) ) ) {
				return;
			}

			const layoutFieldId = $layoutField.data( 'field-id' ),
				columnsData = app.getFieldColumnsData( layoutFieldId );

			let $lastFieldOptions = $( `#wpforms-field-option-${ layoutFieldId }` );

			columnsData.forEach( function( column, c ) {
				if ( ! Array.isArray( column.fields ) ) {
					return;
				}

				const fields = column.fields.slice();

				column.fields.forEach( function( fieldId ) {
					let $fieldOptions = $( `#wpforms-field-option-${ fieldId }` );

					if ( ! $fieldOptions.length ) {
						// Remove not existing field.
						const fieldIndex = fields.indexOf( fieldId );

						if ( fieldIndex !== -1 ) {
							fields.splice( fieldIndex, 1 );
						}

						return;
					}

					$fieldOptions = $fieldOptions.detach();
					$lastFieldOptions.after( $fieldOptions );
					$lastFieldOptions = $fieldOptions;
				} );

				column.fields = fields;

				columnsData[ c ] = column;
			} );

			app.updateFieldColumnsData( layoutFieldId, columnsData );
		},

		/**
		 * Whether the field type is allowed to be in column.
		 *
		 * @since 1.7.7
		 *
		 * @param {string} fieldType Field type to check.
		 * @param {jQuery} $target   Target element.
		 *
		 * @return {boolean} True if allowed.
		 */
		isFieldAllowedInColum( fieldType, $target ) {
			const isAllowed = wpforms_builder.layout.not_allowed_fields.indexOf( fieldType ) < 0;

			/**
			 * Allows developers to determine whether the field is allowed to be in column.
			 *
			 * @since 1.8.9
			 *
			 * @param {boolean} isAllowed Whether the field is allowed to be placed in the column.
			 * @param {string}  fieldType Field type.
			 * @param {jQuery}  $target   Target element.
			 *
			 * @return {boolean} True if allowed.
			 */
			return wp.hooks.applyFilters( 'wpforms.LayoutField.isFieldAllowedInColumn', isAllowed, fieldType, $target );
		},

		/**
		 * Event `wpformsBeforeFieldDuplicate` handler.
		 *
		 * @since 1.7.7
		 *
		 * @param {Event}  e       Event.
		 * @param {number} fieldId Field Id.
		 * @param {jQuery} $field  Field object.
		 */
		beforeFieldDuplicate( e, fieldId, $field ) {
			// Run for the Layout-based fields only.
			if ( ! app.isLayoutBasedField( $field.data( 'field-type' ) ) ) {
				return;
			}

			e.preventDefault();
			app.duplicateLayoutField( fieldId );
			WPFormsBuilder.increaseNextFieldIdAjaxRequest();
		},

		/**
		 * Event `wpformsFieldDuplicated` handler.
		 *
		 * @since 1.7.7
		 *
		 * @param {Event}  e          Event.
		 * @param {number} fieldId    Field ID.
		 * @param {jQuery} $field     Field object.
		 * @param {number} newFieldId New field ID.
		 * @param {jQuery} $newField  New field object.
		 */
		fieldDuplicated( e, fieldId, $field, newFieldId, $newField ) {
			// Skip the Layout-based fields.
			if ( app.isLayoutBasedField( $field.data( 'field-type' ) ) ) {
				return;
			}

			app.positionFieldInColumn(
				newFieldId,
				$newField.index() - 1,
				$newField.parent()
			);
		},
	};

	// Provide access to public functions/properties.
	return app;
}( document, window, jQuery ) );

// Initialize.
WPForms.Admin.Builder.FieldLayout.init();
