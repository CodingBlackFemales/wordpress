/* global wpforms, tinyMCEPreInit, tinymce, WPFormsUtils */

/**
 * @param tinymce.EditorManager
 */

/**
 * WPForms Repeater Field.
 *
 * @since 1.8.9
 */
const WPFormsRepeaterField = window.WPFormsRepeaterField || ( function( document, window, $ ) {
	/**
	 * Elements holder.
	 *
	 * @since 1.8.9
	 *
	 * @type {Object}
	 */
	const el = {};

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
		 * Document ready.
		 *
		 * @since 1.8.9
		 */
		ready() {
			el.$doc = $( document );

			app.initClones( el.$doc );
			app.updateAllFieldsCloneList( el.$doc );
			app.initDescriptions( el.$doc, 'init' );
			app.events();

			$( window ).resize( WPFormsUtils.debounce( () => app.initDescriptions( el.$doc, 'init' ), 50 ) );
		},

		/**
		 * Events.
		 *
		 * @since 1.8.9
		 */
		events() {
			el.$doc
				.off( 'click.WPFormsRepeaterAdd' )
				.on( 'click.WPFormsRepeaterAdd', '.wpforms-field-repeater-button-add', app.buttonAddClick )
				.on( 'click', '.wpforms-field-repeater-button-remove', app.buttonRemoveClick )
				.on( 'wpformsProcessConditionalsField', app.processConditionalsField )
				.on( 'wpformsPageChange', app.pageChange );
		},

		/**
		 * Init rows: button positioning, remove labels.
		 *
		 * @since 1.8.9
		 *
		 * @param {jQuery} $context Context container.
		 */
		initClones( $context ) {
			// Get the original buttons.
			const prefix = $context.hasClass( 'wpforms-field-repeater' ) ? '' : '.wpforms-field-repeater ';
			const $originalRowButtons = $context.find( prefix + '> .wpforms-field-repeater-display-rows .wpforms-field-repeater-display-rows-buttons' );
			const $originalBlocksButtons = $context.find( prefix + '> .wpforms-field-repeater-display-blocks-buttons' );

			app.initRowsButtons( $originalRowButtons );
			app.initBlocksButtons( $originalBlocksButtons );

			// Display rows buttons in clones.
			$context
				.find( '.wpforms-field-repeater-clone-wrap .wpforms-field-repeater-display-rows-buttons' )
				.addClass( 'wpforms-init' );

			// Init Rich Text field clones.
			app.initRichTextClones( $context.find( '.wpforms-field-repeater-clone-wrap' ) );
		},

		/**
		 * Init Display Rows Add/Remove buttons.
		 *
		 * @since 1.8.9
		 *
		 * @param {jQuery} $originalRowButtons Original rows buttons.
		 */
		initRowsButtons( $originalRowButtons ) {
			$originalRowButtons.each( function() {
				const $rowButtons = $( this );
				const $rowFields = $rowButtons.siblings( '.wpforms-layout-column' ).find( '.wpforms-field:not(.wpforms-field-hidden)' );
				const $label = $rowFields.last().find( '.wpforms-field-label' );

				// Get the label height and calculate the buttons top position.
				const labelStyle = $label.length ? window.getComputedStyle( $label.get( 0 ) ) : null;
				const margin = labelStyle?.getPropertyValue( '--wpforms-field-size-input-spacing' ) || 0;
				const height = $label.outerHeight() || 0;
				const top = height + Number.parseInt( margin, 10 ) + 10;

				// Remove buttons if the row doesn't contain any fields.
				if ( ! $rowFields.length ) {
					app.removeAllButtons( $rowButtons.closest( '.wpforms-field-repeater' ) );

					return;
				}

				$rowButtons
					// Display buttons only if there are fields inside the row.
					.toggleClass( 'wpforms-init', $rowFields.length > 0 )
					// Set the top position.
					.css( { top } );

				app.initMinMaxRows( $rowButtons );
			} );
		},

		/**
		 * Init Display Blocks Add/Remove buttons.
		 *
		 * @since 1.8.9
		 *
		 * @param {jQuery} $originalBlocksButtons Original blocks buttons.
		 */
		initBlocksButtons( $originalBlocksButtons ) {
			$originalBlocksButtons.each( function() {
				const $blockButtons = $( this );
				const $repeaterField = $blockButtons.closest( '.wpforms-field-repeater' );
				const $blockFields = $repeaterField.find( '.wpforms-field-repeater-display-blocks .wpforms-field:not(.wpforms-field-hidden)' );

				// Remove buttons if the row doesn't contain any fields.
				if ( ! $blockFields.length ) {
					app.removeAllButtons( $repeaterField );

					return;
				}

				app.initMinMaxRows( $blockButtons );
			} );
		},

		/**
		 * Remove all buttons from the Repeater field.
		 *
		 * @since 1.8.9
		 *
		 * @param {jQuery} $repeaterField Repeater field.
		 */
		removeAllButtons( $repeaterField ) {
			$repeaterField
				.find( `
					.wpforms-field-repeater-display-blocks-buttons,
					.wpforms-field-repeater-display-rows-buttons
				` )
				.remove();
		},

		/**
		 * Init min and max rows: disable/enable buttons.
		 *
		 * @since 1.8.9
		 *
		 * @param {jQuery} $container Any container inside the Repeater field.
		 */
		initMinMaxRows( $container ) {
			const $repeaterField = $container.closest( '.wpforms-field-repeater' );
			const $buttons = $repeaterField.find( '.wpforms-field-repeater-display-rows-buttons, .wpforms-field-repeater-display-blocks-buttons' );
			const rowsMin = $repeaterField.data( 'rows-min' ) || 1;

			$buttons
				.find( 'button' )
				.removeClass( 'wpforms-disabled' );

			// Total rows including the first row with original fields.
			const $rows = $repeaterField.find( `> .wpforms-field-layout-rows, .wpforms-field-repeater-clone-wrap` );

			// Determine the number of rows to disable the remove button.
			// If the total number of rows is less than or equal to the minimum number of rows, disable the remove button for all rows.
			// Otherwise, disable the remove button only for the first row.
			const disableRemoveRows = $rows.length <= rowsMin ? rowsMin : 1;

			// Disable the remove button for the first `disableRemoveRows` rows.
			const $minRows = $repeaterField.find(
				`.wpforms-field-layout-rows:lt(${ disableRemoveRows }),
				.wpforms-field-repeater-display-blocks-buttons:lt(${ disableRemoveRows })`
			);

			$minRows
				.find( '.wpforms-field-repeater-button-remove' )
				.addClass( 'wpforms-disabled' )
				.attr( 'tabindex', '-1' );

			const disableAdd = $buttons.length >= $repeaterField.data( 'rows-max' );

			$buttons
				.find( '.wpforms-field-repeater-button-add' )
				.toggleClass( 'wpforms-disabled', disableAdd )
				.attr( 'tabindex', () => {
					return disableAdd ? '-1' : null;
				} );
		},

		/**
		 * Add button click event.
		 *
		 * @since 1.8.9
		 */
		buttonAddClick() {
			const $button = $( this );

			if ( $button.hasClass( 'wpforms-disabled' ) ) {
				return;
			}

			const $repeaterField = $button.closest( '.wpforms-field-repeater' );
			const fieldID = $repeaterField.data( 'field-id' );
			const formID = $repeaterField.closest( '.wpforms-form' ).data( 'formid' );
			const template = $( '.tmpl-wpforms-field-repeater-template-' + fieldID + '-' + formID ).text();

			if ( ! template.length ) {
				return;
			}

			const cloneNum = $repeaterField.data( 'clone-num' ) || 2;
			const cloneHtml = template.replaceAll( '{CLONE}', cloneNum );

			// Store the next clone number.
			$repeaterField.data( 'clone-num', cloneNum + 1 );

			// Get the current block.
			let $block = $button.closest( '.wpforms-field-repeater-clone-wrap' );

			$block = $block.length ? $block : $button.closest( '.wpforms-field-layout-rows' );
			$block = $block.length ? $block : $repeaterField.find( '> .wpforms-field-repeater-display-blocks-buttons' );

			// Create a clone.
			const $clone = $( cloneHtml );

			$clone.hide();
			$clone.find( '.wpforms-field-repeater-display-rows-buttons button' ).removeClass( 'wpforms-disabled' );
			$clone.find( '.wpforms-field-repeater-display-blocks-buttons button' ).removeClass( 'wpforms-disabled' );

			// Inject clone HTML to DOM after the current block.
			$block.after( $clone );

			app.updateCloneList( $repeaterField );
			app.initClones( $repeaterField );
			app.initFields( $clone );
			app.initDescriptions( $repeaterField, 'add' );

			$clone.slideDown( 200, function() {
				/**
				 * The event fired when the clone is displayed.
				 *
				 * @since 1.8.9
				 *
				 * @param {jQuery} $clone         The clone element.
				 * @param {jQuery} $repeaterField The whole repeater field element.
				 */
				el.$doc.trigger( 'wpformsRepeaterFieldCloneDisplay', [ $clone, $repeaterField ] );
			} );

			app.updateBlockTitleNumbers( $repeaterField );

			/**
			 * The event fired when the clone is created.
			 *
			 * @since 1.8.9
			 *
			 * @param {jQuery} $clone         The clone element.
			 * @param {jQuery} $repeaterField The whole repeater field element.
			 */
			el.$doc.trigger( 'wpformsRepeaterFieldCloneCreated', [ $clone, $repeaterField ] );
		},

		/**
		 * Remove button click event.
		 *
		 * @since 1.8.9
		 */
		buttonRemoveClick() {
			const $button = $( this );

			if ( $button.hasClass( 'wpforms-disabled' ) ) {
				return;
			}

			const $repeaterField = $button.closest( '.wpforms-field-repeater' );
			const $clone = $button.closest( '.wpforms-field-repeater-clone-wrap' );
			const action = $repeaterField.find( '.wpforms-field-repeater-clone-wrap' ).last().is( $clone ) ? 'remove-last' : 'remove';

			app.initDescriptions( $repeaterField, action );

			$clone.slideUp( 200, function() {
				$clone.remove();

				app.updateCloneList( $repeaterField );
				app.initClones( $repeaterField );
				app.updateBlockTitleNumbers( $repeaterField );

				/**
				 * The event fired when the clone was removed.
				 *
				 * @since 1.8.9
				 *
				 * @param {jQuery} $clone         The clone element.
				 * @param {jQuery} $repeaterField The whole repeater field element.
				 */
				el.$doc.trigger( 'wpformsRepeaterFieldCloneRemoved', [ $clone, $repeaterField ] );
			} );
		},

		/**
		 * Update clone list hidden input.
		 *
		 * @since 1.8.9
		 *
		 * @param {jQuery} $repeaterField Field container.
		 */
		updateCloneList( $repeaterField ) {
			const $cloneList = $repeaterField.find( '.wpforms-field-repeater-clone-list' );

			if ( ! $cloneList.length ) {
				return;
			}

			const $clones = $repeaterField.find( '.wpforms-field-repeater-clone-wrap' );
			const cloneList = [];
			let maxCloneNum = 1;

			$clones.each( function() {
				const cloneNum = Number.parseInt( $( this ).data( 'clone' ), 10 ) || 0;

				maxCloneNum = cloneNum > maxCloneNum ? cloneNum : maxCloneNum;
				cloneList.push( $( this ).data( 'clone' ) );
			} );

			$cloneList.val( JSON.stringify( cloneList ) );
			$repeaterField.attr( 'data-clone-num', maxCloneNum + 1 );
		},

		/**
		 * Update clone list hidden input in all Repeater fields.
		 *
		 * @since 1.8.9
		 *
		 * @param {jQuery} $context Context container.
		 */
		updateAllFieldsCloneList( $context ) {
			const $cont = $context.hasClass( 'wpforms-field-repeater' ) ? $context.parent() : $context;

			$cont.find( '.wpforms-field-repeater' ).each( function() {
				app.updateCloneList( $( this ) );
			} );
		},

		/**
		 * The `wpformsProcessConditionalsField` event handler.
		 *
		 * @since 1.8.9
		 *
		 * @param {Object}        e       Event object.
		 * @param {number|string} formID  Form ID.
		 * @param {number|string} fieldID Field ID.
		 * @param {boolean}       pass    Whether the action was performed.
		 * @param {string}        action  Action name, `show` or `hide`.
		 */
		processConditionalsField( e, formID, fieldID, pass, action ) {
			const $field = $( `#wpforms-${ formID }-field_${ fieldID }-container` );

			if ( ! $field.hasClass( 'wpforms-field-repeater' ) ) {
				return;
			}

			if ( action === 'show' || pass ) {
				app.initClones( $field );
			}
		},

		/**
		 * Page change event.
		 *
		 * @since 1.8.9
		 *
		 * @param {Object} e     Event object.
		 * @param {number} page  Page number.
		 * @param {jQuery} $form Form container.
		 */
		pageChange( e, page, $form ) {
			app.initClones( $form.find( '.wpforms-page-' + page ) );
		},

		/**
		 * Update clone numbers in the clone titles.
		 *
		 * @since 1.8.9
		 *
		 * @param {jQuery} $repeaterField Repeater field container.
		 */
		updateBlockTitleNumbers( $repeaterField ) {
			const $blockTitleNums = $repeaterField.find( '.wpforms-wpforms-field-repeater-block-num' );

			$blockTitleNums.each( function( i ) {
				$( this ).text( '#' + ( i + 2 ) );
			} );
		},

		/**
		 * Init field descriptions in rows.
		 *
		 * @since 1.8.9
		 *
		 * @param {jQuery} $context Context container.
		 * @param {string} action   Action name, `init`, `add`, `remove` or `remove-last`.
		 */
		initDescriptions( $context, action ) {
			// Do not show descriptions on mobile.
			if ( $( window ).width() <= 600 ) {
				return;
			}

			const $repeaters = $context.hasClass( 'wpforms-field-repeater' ) ? $context : $context.find( '.wpforms-field-repeater' );

			$repeaters.each( function() {
				const $repeater = $( this );

				if ( ! $repeater.hasClass( 'wpforms-field-repeater-display-rows' ) ) {
					return;
				}

				const $rows = $repeater.find( '.wpforms-field-repeater-display-rows' );

				// Show the last row description on init.
				if ( action === 'init' ) {
					$rows
						.last()
						.find( '.wpforms-field-description' )
						.addClass( 'wpforms-init' );

					return;
				}

				// Show the previous row description when removing the last row.
				if ( action === 'remove-last' ) {
					$rows
						.filter( ( i ) => {
							return $rows.length >= 2 && i === $rows.length - 2;
						} )
						.find( '.wpforms-field-description' )
						.slideDown( 200, () => {
							$( this ).addClass( 'wpforms-init' );
						} );

					return;
				}

				// Hide all descriptions except the last row.
				$rows
					.filter( ( i ) => {
						return i !== $rows.length - 1;
					} )
					.find( '.wpforms-field-description' )
					.slideUp( 200, () => {
						$( this ).removeClass( 'wpforms-init', $rows.length > 1 );
					} );

				// Show the last row description when adding a new row.
				$rows
					.last()
					.find( '.wpforms-field-description' )
					.slideDown( 200, () => {
						$( this ).addClass( 'wpforms-init' );
					} );
			} );
		},

		/**
		 * Init fields inside a clone.
		 *
		 * @since 1.8.9
		 *
		 * @param {jQuery} $clone Clone container.
		 */
		initFields( $clone ) {
			app.initNumberSlider( $clone );
			wpforms.loadDatePicker( $clone );
			wpforms.loadTimePicker( $clone );
			wpforms.loadSmartPhoneField( $clone );
			wpforms.loadChoicesJS( $clone );
			wpforms.loadInputMask( $clone );
			window.WPFormsTextLimit?.initHint( '#' + $clone.attr( 'id' ) );
			window.WPForms?.FrontendModern?.updateGBBlockRatingColor( $clone );
			window.WPForms?.FrontendModern?.updateGBBlockIconChoicesColor( $clone );
		},

		/**
		 * Init Number Slider field clones.
		 *
		 * @since 1.8.9
		 *
		 * @param {jQuery} $clone Clone container.
		 */
		initNumberSlider( $clone ) {
			$clone.find( '.wpforms-field-number-slider input' ).each( function() {
				const $slider = $( this );
				const value = $slider.val();
				const $hint = $slider.siblings( '.wpforms-field-number-slider-hint' );

				$hint.html( $hint.data( 'hint' )?.replaceAll( '{value}', `<b>${ value }</b>` ) );
			} );
		},

		/**
		 * Init RichText field clones.
		 *
		 * @since 1.8.9
		 *
		 * @param {jQuery} $cloneBlocks Clone(s) blocks.
		 */
		initRichTextClones( $cloneBlocks ) {
			$cloneBlocks.each( function() {
				const $clone = $( this );
				const $repeaterField = $clone.closest( '.wpforms-field-repeater' );
				const $originalRichTextFields = $repeaterField.find( '> .wpforms-field-repeater-display-rows .wp-editor-area' );

				$clone.find( '.wp-editor-area' ).each( function( i ) {
					app.initClonedRichTextField(
						$( this ).attr( 'id' ),
						$originalRichTextFields.eq( i ).attr( 'id' )
					);
				} );
			} );
		},

		/**
		 * Init cloned RichText field.
		 *
		 * @since 1.8.9
		 *
		 * @param {string} id         Editor textarea ID.
		 * @param {string} originalId Original RichText field textarea ID.
		 */
		initClonedRichTextField( id, originalId ) {
			if ( ! tinyMCEPreInit || ! tinymce ) {
				return;
			}

			const newMceOptions = {};
			newMceOptions[ id ] = { ...tinyMCEPreInit.mceInit[ originalId ] };
			newMceOptions[ id ].body_class = newMceOptions[ id ].body_class?.replace( originalId, id ); // eslint-disable-line camelcase
			newMceOptions[ id ].selector = '#' + id;

			const newQtOptions = {};
			newQtOptions[ id ] = { ...tinyMCEPreInit.qtInit[ originalId ] };
			newQtOptions[ id ].id = id;

			tinyMCEPreInit.mceInit = { ...tinyMCEPreInit.mceInit, ...newMceOptions	};
			tinyMCEPreInit.qtInit = { ...tinyMCEPreInit.qtInit, ...newQtOptions };

			window.quicktags( tinyMCEPreInit.qtInit[ id ] );
			$( '#' + id ).css( 'visibility', 'initial' );

			tinymce.EditorManager.execCommand( 'mceAddEditor', true, id );
		},
	};

	return app;
}( document, window, jQuery ) );

// Initialize.
WPFormsRepeaterField.init();
