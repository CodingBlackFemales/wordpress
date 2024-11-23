/* global wpforms_settings */

/**
 * WPForms Iframe Helper for displaying сontent.
 *
 * @since 1.9.2
 */
window.WPFormsIframe = window.WPFormsIframe || ( function( document ) {
	/**
	 * Public functions and properties.
	 *
	 * @since 1.9.2
	 *
	 * @type {Object}
	 */
	const app = {

		/**
		 * Create an iframe from a div inside.
		 *
		 * @since 1.9.2
		 *
		 * @param {Object} options       Options for the iframe element.
		 * @param {string} options.color Value for the `color` css-property for <head> inside <iframe>.
		 *
		 * @this {HTMLDivElement}
		 */
		update( options = {} ) {
			const iframeWrapper = this;

			// Prevent double-execution.
			if ( iframeWrapper.classList.contains( 'wpforms-iframe-updated' ) ) {
				return;
			}

			iframeWrapper.classList.add( 'wpforms-iframe-updated' );

			const iframe = document.createElement( 'iframe' );

			iframe.onload = function() {
				app.iframeStyles( iframe, options );
				app.iframeBody( iframe, iframeWrapper.innerHTML );
				app.iframeFullHeight( iframe );

				iframeWrapper.remove();
			};

			iframeWrapper.after( iframe );
		},

		/**
		 * Add styles to an iframe.
		 *
		 * @since 1.9.2
		 *
		 * @param {HTMLIFrameElement} iframe        Iframe element.
		 * @param {Object}            options       Options for the iframe element.
		 * @param {string}            options.color Value for the `color` css-property for <head> inside <iframe>.
		 */
		iframeStyles( iframe, options = {} ) {
			const doc = iframe.contentWindow.document,
				head = doc.querySelector( 'head' ),
				style = doc.createElement( 'style' ),
				font = getComputedStyle( document.body ).fontFamily,
				{ color = 'inherit' } = options;

			style.setAttribute( 'type', 'text/css' );
			style.innerHTML = 'body.mce-content-body {' +
				'	margin: 0 !important;' +
				'	background-color: transparent !important;' +
				'	font-family: ' + font + ';' +
				'	color: ' + color + ';' +
				'}' +
				'*:first-child {' +
				'	margin-top: 0' +
				'}' +
				'*:last-child {' +
				'	margin-bottom: 0' +
				'}' +
				'pre {' +
				'	white-space: pre !important;' +
				'	overflow-x: auto !important;' +
				'}' +
				'a,' +
				'img {' +
				'	display: inline-block;' +
				'}';

			head.appendChild( style );

			app.entryPreviewAddLinkElement( iframe );
		},

		/**
		 * Maybe add link elements to an Entry Preview iframe.
		 *
		 * @since 1.9.2
		 *
		 * @param {HTMLIFrameElement} iframe Iframe element.
		 */
		entryPreviewAddLinkElement( iframe ) {
			if ( ! wpforms_settings.entry_preview_iframe_styles ) {
				return;
			}

			const doc = iframe.contentWindow.document,
				head = doc.querySelector( 'head' );

			wpforms_settings.entry_preview_iframe_styles.forEach( function( src ) {
				const link = doc.createElement( 'link' );

				link.setAttribute( 'rel', 'stylesheet' );
				link.setAttribute( 'href', src );
				link.onload = function() {
					app.iframeFullHeight( iframe );
				};
				head.appendChild( link );
			} );
		},

		/**
		 * Add HTML elements to an iframe.
		 *
		 * @since 1.9.2
		 *
		 * @param {HTMLIFrameElement} iframe Iframe element.
		 * @param {string}            html   HTML.
		 */
		iframeBody( iframe, html ) {
			const doc = iframe.contentWindow.document,
				body = doc.querySelector( 'body' ),
				wrapper = doc.createElement( 'div' );

			wrapper.classList.add( 'wpforms-iframe-wrapper' );
			body.append( wrapper );
			wrapper.innerHTML = html;
			body.classList.add( 'mce-content-body' );

			body.querySelectorAll( 'a' ).forEach( function( el ) {
				el.setAttribute( 'rel', 'noopener' );

				if ( ! el.hasAttribute( 'target' ) ) {
					el.setAttribute( 'target', '_top' );
				}
			} );

			body.querySelectorAll( 'table' )?.forEach?.( function( tableEl ) {
				tableEl.classList.add( 'mce-item-table' );
			} );
		},

		/**
		 * Set full height for an iframe.
		 *
		 * @since 1.9.2
		 *
		 * @param {HTMLIFrameElement} iframe Iframe element.
		 */
		iframeFullHeight( iframe ) {
			if ( ! iframe.contentWindow || ! iframe.contentWindow.document ) {
				return;
			}

			const doc = iframe.contentWindow.document,
				wrapper = doc.querySelector( '.wpforms-iframe-wrapper' );

			iframe.style.height = wrapper.scrollHeight + 'px';
		},

	};

	return app;
}( document ) );
