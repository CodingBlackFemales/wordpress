/* global wpforms_ai_form_generator, wpf */

/**
 * The WPForms AI form generator app.
 *
 * Form preview module.
 *
 * @since 1.9.2
 *
 * @param {Object} generator The AI form generator.
 * @param {Object} $         jQuery function.
 *
 * @return {Object} The preview module object.
 */
export default function( generator, $ ) { // eslint-disable-line max-lines-per-function
	/**
	 * Localized strings.
	 *
	 * @since 1.9.2
	 *
	 * @type {Object}
	 */
	const strings = wpforms_ai_form_generator;

	/**
	 * The preview module object.
	 *
	 * @since 1.9.2
	 */
	const preview = {
		/**
		 * DOM elements.
		 *
		 * @since 1.9.2
		 */
		el: {},

		/**
		 * Mouse coordinates.
		 *
		 * @since 1.9.2
		 */
		mouse: {},

		/**
		 * Init module.
		 *
		 * @since 1.9.2
		 */
		init() {
			preview.el.$contentWrap = generator.main.el.$generatorPanel.find( '.wpforms-panel-content-wrap' );
			preview.el.$content = preview.el.$contentWrap.find( '.wpforms-panel-content' );
			preview.el.$emptyState = preview.el.$content.find( '.wpforms-panel-empty-state' );

			preview.events();
		},

		/**
		 * Preview events.
		 *
		 * @since 1.9.2
		 */
		events() {
			// Track mouse coordinates.
			$( document ).on( 'mousemove', ( e ) => {
				preview.mouse.x = e.pageX;
				preview.mouse.y = e.pageY;
			} );

			preview.el.$contentWrap.on( 'scroll', preview.closeTooltips );
		},

		/**
		 * Update the preview according to the response stored in the generator state.
		 *
		 * @since 1.9.2
		 */
		update() {
			const response = generator.state.aiResponse;

			if ( ! response || ! response.fields ) {
				return;
			}

			// Set the preview update flag.
			generator.state.isPreviewUpdate = true;

			// Reset preview fields. Here we will store the field ids that where added to the preview.
			generator.state.previewFields = [];

			// Remove existing fields and hide empty state.
			preview.clear( false );

			// Add form title.
			preview.el.$content
				.prepend( `<h2 class="wpforms-ai-form-generator-preview-title">${ response.form_title ?? '' }</h2>` );

			for ( const key in response.fieldsOrder ) {
				const fieldId = response.fieldsOrder[ key ];
				preview.field( response.fields[ fieldId ], key );
			}

			// Add submit button.
			preview.displaySubmit( response.settings?.submit_text ?? strings.panel.submitButton );
		},

		/**
		 * A single field preview.
		 *
		 * @since 1.9.2
		 *
		 * @param {Object} fieldSettings Field settings.
		 * @param {number} key           Field key.
		 */
		async field( fieldSettings, key ) {
			// Add a field placeholder to the preview.
			const html = `
				<div id="wpforms-generator-field-${ fieldSettings.id ?? '' }" class="wpforms-ai-form-generator-preview-field">
					<div class="placeholder"></div>
					<div class="wpforms-field wpforms-field-${ fieldSettings.type ?? '' }"></div>
				</div>
			`;

			preview.el.$content.append( html );

			const data = {
				action: 'wpforms_get_ai_form_field_preview',
				nonce: strings.nonce,
				field: fieldSettings,
			};

			// Delay the AJAX request to simulate one-by-one field loading.
			await preview.delay( 300 * key );

			// Field preview AJAX request.
			$.post( strings.ajaxUrl, data )
				.done( function( res ) {
					if ( ! res.success ) {
						wpf.debug( 'Form Generator AJAX error:', res.data.error ?? res.data );
						return;
					}

					preview.displayField( res.data ?? '', fieldSettings );
				} )
				.fail( function( xhr ) {
					wpf.debug( 'Form Generator AJAX error:', xhr.responseText ?? xhr.statusText );
				} );
		},

		/**
		 * Display the field in his placeholder.
		 *
		 * @since 1.9.2
		 *
		 * @param {string} fieldHtml     Field HTML.
		 * @param {Object} fieldSettings Field settings.
		 */
		displayField( fieldHtml, fieldSettings ) {
			if ( ! fieldSettings.id && fieldSettings.id !== 0 ) {
				return;
			}

			const $fieldBlock = preview.el.$content.find( '#wpforms-generator-field-' + fieldSettings.id );
			const $field = $fieldBlock.find( '.wpforms-field' );
			const $placeholder = $fieldBlock.find( '.placeholder' );

			$placeholder
				.addClass( 'fade-out' );

			$field
				.html( fieldHtml ?? '' )
				.addClass( 'fade-in' )
				.toggleClass( 'wpforms-hidden', ! fieldHtml ) // Hide preview if the field is empty.
				.toggleClass( 'required', fieldSettings.required === '1' ); // Display the required field mark (asterisk) on the field label.

			preview.initTooltip( $field );
			preview.initPageBreak( $field, fieldSettings );

			generator.state.previewFields.push( fieldSettings.id );

			// Detect whether all the fields are loaded.
			if ( generator.state.previewFields.length === Object.keys( generator.state.aiResponse?.fields ).length ) {
				generator.state.isPreviewUpdate = false;
			}
		},

		/**
		 * Init the page breaks.
		 *
		 * @since 1.9.2
		 *
		 * @param {jQuery} $field        Field jQuery object.
		 * @param {Object} fieldSettings Field settings.
		 */
		initPageBreak( $field, fieldSettings ) {
			if ( fieldSettings.type === 'pagebreak' && ! [ 'top', 'bottom' ].includes( fieldSettings.position ) ) {
				$field.addClass( 'wpforms-pagebreak-normal' );
			}
		},

		/**
		 * Init the preview tooltip.
		 *
		 * @since 1.9.2
		 *
		 * @param {jQuery} $field Field jQuery object.
		 */
		initTooltip( $field ) {
			const width = 260;
			const args = {
				content: strings.panel.tooltipTitle + '<br>' + strings.panel.tooltipText,
				trigger: 'manual',
				interactive: true,
				animationDuration: 100,
				delay: 0,
				side: [ 'top' ],
				contentAsHTML: true,
				functionPosition: ( instance, helper, position ) => {
					// Set the tooltip position based on the mouse coordinates.
					position.coord.top = preview.mouse.y - 57;
					position.coord.left = preview.mouse.x - ( width / 2 );

					return position;
				},
			};

			// Initialize.
			$field.tooltipster( args );
			preview.toggleTooltipOnClick( $field );
		},

		/**
		 * Toggle the preview tooltip on click.
		 *
		 * @since 1.9.2
		 *
		 * @param {jQuery} $field Field jQuery object.
		 */
		toggleTooltipOnClick( $field ) {
			$field.on( 'click', () => {
				// Close opened tooltips on other fields.
				preview.closeTooltips();

				const status = $field.tooltipster( 'status' );

				$field.tooltipster( status.state === 'closed' ? 'open' : 'close' );

				if ( status.state !== 'closed' ) {
					return;
				}

				const instance = $field.tooltipster( 'instance' );

				// Adjust tooltip styling.
				instance._$tooltip.css( {
					height: 'auto',
				} );

				instance._$tooltip.find( '.tooltipster-arrow' ).css( {
					left: '50%',
				} );

				// Close the tooltip after 5 seconds.
				setTimeout( function() {
					preview.closeTooltips();
				}, 5000 );
			} );
		},

		/**
		 * Close tooltips.
		 *
		 * @since 1.9.2
		 */
		closeTooltips() {
			preview.el.$content.find( '.wpforms-field' ).each( function() {
				const $this = $( this );

				if ( $this.hasClass( 'tooltipstered' ) && $this.parent().length ) {
					$this.tooltipster( 'close' );
				}
			} );
		},

		/**
		 * Display the `submit` button.
		 *
		 * @since 1.9.2
		 *
		 * @param {string} label Button text.
		 */
		displaySubmit( label ) {
			preview.el.$content
				.append( `<button type="button" value="${ label }" class="wpforms-ai-form-generator-preview-submit">${ label }</button>` );
		},

		/**
		 * Clear the preview content.
		 *
		 * @since 1.9.2
		 *
		 * @param {boolean} isEmptyState Whether to show the empty state or not.
		 */
		clear( isEmptyState = true ) {
			preview.el.$content.find( '.wpforms-ai-form-generator-preview-field' ).remove();
			preview.el.$content.find( '.wpforms-ai-form-generator-preview-placeholder' ).remove();
			preview.el.$content.find( '.wpforms-ai-form-generator-preview-title' ).remove();
			preview.el.$content.find( '.wpforms-ai-form-generator-preview-submit' ).remove();
			preview.el.$emptyState.toggleClass( 'wpforms-hidden-strict', ! isEmptyState );
		},

		/**
		 * Delay promise.
		 *
		 * @since 1.9.2
		 *
		 * @param {number} time Time in milliseconds.
		 *
		 * @return {Promise} Promise.
		 */
		delay( time ) {
			return new Promise( ( res ) => {
				setTimeout( res, time );
			} );
		},
	};

	return preview;
}
