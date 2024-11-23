/* global wpforms, wpforms_settings, WPFormsIframe */

'use strict';

var WPFormsEntryPreview = window.WPFormsEntryPreview || ( function( document, window, $ ) {

	/**
	 * Public functions and properties.
	 *
	 * @since 1.7.0
	 *
	 * @type {object}
	 */
	var app = {

		/**
		 * Start the engine.
		 *
		 * @since 1.7.0
		 */
		init: function() {

			$( document )
				.on( 'wpformsBeforePageChange', app.pageChange )
				.on( 'wpformsEntryPreviewUpdated', function( e, $form, $entryPreviewField ) {

					$entryPreviewField.find( '.wpforms-iframe' ).each( app.updateIframe );
				} )
				.on( 'wpformsAjaxSubmitSuccessConfirmation', function() {

					$( '.wpforms-container > .wpforms-entry-preview .wpforms-iframe' ).each( app.updateIframe );
				} );

			$( window ).on( 'load', function() {

				$( '.wpforms-iframe' ).each( app.updateIframe );
			} );
		},

		/**
		 * Entry preview field callback for a page changing.
		 *
		 * @since 1.7.0
		 *
		 * @param {Event}  event       Event.
		 * @param {int}    currentPage Current page.
		 * @param {jQuery} $form       Current form.
		 */
		pageChange: function( event, currentPage, $form ) {

			if ( ! $( event.target ).hasClass( 'wpforms-page-next' ) ) {
				return;
			}

			wpforms.saveTinyMCE();

			app.update( currentPage, $form );
		},

		/**
		 * Update the entry preview fields on the page.
		 *
		 * @since 1.7.0
		 *
		 * @param {int}    currentPage Current page.
		 * @param {jQuery} $form       Current form.
		 */
		update: function( currentPage, $form ) {

			var $entryPreviewField = $form.find( '.wpforms-page-' + currentPage + ' .wpforms-field-entry-preview' );

			if ( ! $entryPreviewField.length ) {
				return;
			}

			var entryPreviewId        = $entryPreviewField.data( 'field-id' ),
				$fieldUpdatingMessage = $entryPreviewField.find( '.wpforms-entry-preview-updating-message' ),
				$fieldNotice          = $entryPreviewField.find( '.wpforms-entry-preview-notice' ),
				$fieldWrapper         = $entryPreviewField.find( '.wpforms-entry-preview-wrapper' ),
				formData              = new FormData( $form.get( 0 ) );

			formData.append( 'action', 'wpforms_get_entry_preview' );
			formData.append( 'current_entry_preview_id', entryPreviewId );

			$.ajax( {
				data: formData,
				type: 'post',
				url: wpforms_settings.ajaxurl,
				dataType: 'json',

				// Disable processData and contentType to pass formData as an object.
				processData: false,
				contentType: false,
				beforeSend: function() {

					$entryPreviewField.addClass( 'wpforms-field-entry-preview-updating' );
					$fieldNotice.hide();
					$fieldWrapper.hide();
					$fieldUpdatingMessage.show();
				},
				success: function( response ) {

					if ( ! response.data ) {
						$entryPreviewField.hide();

						return;
					}

					$fieldWrapper.html( response.data );
					$entryPreviewField.show();
				},
				complete: function() {

					$entryPreviewField.removeClass( 'wpforms-field-entry-preview-updating' );
					$fieldUpdatingMessage.hide();
					$fieldNotice.show();
					$fieldWrapper.show();

					$( document ).trigger( 'wpformsEntryPreviewUpdated', [ $form, $entryPreviewField ] );
				},
			} );
		},

		/**
		 * Create an iframe from a div inside the entry preview.
		 *
		 * @since 1.7.0
		 * @since 1.9.2 Method body was moved to the WPFormsIframe.update method.
		 */
		updateIframe: function() {
			const color = $( '.wpforms-entry-preview-wrapper' ).is( ':visible' ) ? $( '.wpforms-entry-preview-value' ).css( 'color' ) : 'inherit'; // add color on entry preview field.

			WPFormsIframe.update.call( this, { color } );
		},

		/**
		 * Add styles to an iframe.
		 *
		 * @since 1.7.0
		 * @deprecated 1.9.2
		 *
		 * @param {HTMLIFrameElement} iframe Iframe element.
		 */
		iframeStyles: function( iframe ) {
			// eslint-disable-next-line no-console
			console.warn( 'WARNING! Function "WPFormsEntryPreview.iframeStyles( iframe )" has been deprecated, please use the new "WPFormsIframe.iframeStyles( iframe, options )" function instead!' );

			WPFormsIframe.iframeStyles( iframe );
		},

		/**
		 * Add HTML elements to an iframe.
		 *
		 * @since 1.7.0
		 * @deprecated 1.9.2
		 *
		 * @param {HTMLIFrameElement} iframe Iframe element.
		 * @param {string}            html   HTML.
		 */
		iframeBody: function( iframe, html ) {
			// eslint-disable-next-line no-console
			console.warn( 'WARNING! Function "WPFormsEntryPreview.iframeBody( iframe, html )" has been deprecated, please use the new "WPFormsIframe.iframeBody( iframe, html )" function instead!' );

			WPFormsIframe.iframeBody( iframe, html );
		},

		/**
		 * Set full height for an iframe.
		 *
		 * @since 1.7.0
		 * @deprecated 1.9.2
		 *
		 * @param {HTMLIFrameElement} iframe Iframe element.
		 */
		iframeFullHeight: function( iframe ) {
			// eslint-disable-next-line no-console
			console.warn( 'WARNING! Function "WPFormsEntryPreview.iframeFullHeight( iframe )" has been deprecated, please use the new "WPFormsIframe.iframeFullHeight( iframe )" function instead!' );

			WPFormsIframe.iframeFullHeight( iframe );
		},
	};

	return app;

}( document, window, jQuery ) );

WPFormsEntryPreview.init();
