/* global wpforms_admin */

/**
 * View single entry page.
 *
 * @since 1.7.0
 */
// eslint-disable-next-line no-var
var WPFormsViewEntry = window.WPFormsViewEntry || ( function( document, window, $ ) {
	/**
	 * Public functions and properties.
	 *
	 * @since 1.7.0
	 *
	 * @type {Object}
	 */
	var app = { // eslint-disable-line no-var

		/**
		 * Start the engine.
		 *
		 * @since 1.7.0
		 */
		init() {
			// Document ready.
			$( app.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 1.7.0
		 */
		ready() {
			app.loadAllRichTextFields();
			app.bindSettingsToggle();
			app.addAlternateStyles();
			app.hideDropdown();
		},

		/**
		 * Save settings.
		 *
		 * @since 1.8.3
		 */
		saveData() {
			// Ajax call to save settings.
			const checked = [];

			$( '.wpforms-entries-settings-menu-items input[type=checkbox]:checked' ).each( function() {
				checked.push( $( this ).attr( 'name' ) );
			} );

			const data = {
				action: 'wpforms_update_single_entry_filter_settings',
				nonce: wpforms_admin.nonce,
				wpforms_entry_view_settings: checked,
			};

			$.post( wpforms_admin.ajax_url, data );
		},

		/**
		 * Methods to handle entry settings.
		 *
		 * @since 1.8.3
		 */
		updateSettings: {

			$container: $( '#wpforms-entry-fields' ),
			$layoutWrapper: $( '.wpforms-entries-fields-wrapper' ),

			/**
			 * Show/Hide Description.
			 *
			 * @since 1.8.3
			 *
			 * @param {boolean} isActive Is the option active or not?
			 */
			showFieldDescriptions( isActive ) {
				app.updateSettings.$container.find( '.wpforms-entry-field-description' ).toggleClass( 'wpforms-hide', ! isActive );
			},

			/**
			 * Show/Hide Empty Fields.
			 *
			 * @since 1.8.3
			 *
			 * @param {boolean} isActive Is the option active or not?
			 */
			showEmptyFields( isActive ) {
				app.updateSettings.$container.find( '.empty' ).toggleClass( 'wpforms-hide', ! isActive );
			},

			/**
			 * Show/Hide Section Dividers.
			 *
			 * @since 1.8.3
			 *
			 * @param {boolean} isActive Is the option active or not?
			 */
			showSectionDividers( isActive ) {
				app.updateSettings.$container.find( '.wpforms-field-entry-divider' ).toggleClass( 'wpforms-hide', ! isActive );
			},

			/**
			 * Show/Hide Page Breaks.
			 *
			 * @since 1.8.3
			 *
			 * @param {boolean} isActive Is the option active or not?
			 */
			showPageBreaks( isActive ) {
				app.updateSettings.$container.find( '.wpforms-field-entry-pagebreak' ).toggleClass( 'wpforms-hide', ! isActive );
			},

			/**
			 * Show/Hide Unselected Choices.
			 *
			 * @since 1.8.3
			 *
			 * @param {boolean} isActive Is the option active or not?
			 */
			showUnselectedChoices( isActive ) {
				app.updateSettings.$container.find( '.wpforms-field-entry-toggle' ).find( '.wpforms-entry-field-value-is-choice' ).toggleClass( 'wpforms-hide', ! isActive );
				app.updateSettings.$container.find( '.wpforms-field-entry-toggle' ).find( '.wpforms-entry-field-value' ).toggleClass( 'wpforms-hide', isActive );
			},

			/**
			 * Show/Hide HTML Fields.
			 *
			 * @since 1.8.3
			 *
			 * @param {boolean} isActive Is the option active or not?
			 */
			showHtmlFields( isActive ) {
				app.updateSettings.$container.find( '.wpforms-field-entry-html' ).toggle( isActive );
				app.updateSettings.$container.find( '.wpforms-field-entry-content' ).toggle( isActive );
			},

			/**
			 * Toggle maintain layouts.
			 *
			 * @since 1.8.3
			 *
			 * @param {boolean} isActive Is the option active or not?
			 * @since 1.8.3
			 */
			maintainLayouts( isActive ) {
				app.updateSettings.$layoutWrapper.toggleClass( 'wpforms-entry-maintain-layout', isActive );
				app.updateSettings.$layoutWrapper.removeClass( 'wpforms-entry-compact-layout' );
				$( '#wpforms-entry-setting-compact_view' ).prop( 'checked', false );
			},

			/**
			 * Toggle Compat View.
			 *
			 * @since 1.8.3
			 *
			 * @param {boolean} isActive Is the option active or not?
			 */
			compactView( isActive ) {
				app.updateSettings.$layoutWrapper.toggleClass( 'wpforms-entry-compact-layout', isActive );
				app.updateSettings.$layoutWrapper.removeClass( 'wpforms-entry-maintain-layout' );
				$( '#wpforms-entry-setting-maintain_layouts' ).prop( 'checked', false );
			},
		},

		/**
		 * Load all Rich Text fields.
		 *
		 * @since 1.7.0
		 */
		loadAllRichTextFields() {
			$( '.wpforms-entry-field-value-richtext' ).each( function() {
				const iframe = this;
				const $iframe = $( this );

				$iframe.on( 'load', function() {
					app.loadRichTextField( iframe );
				} );

				$iframe.attr( 'src', $iframe.data( 'src' ) );
			} );
		},

		/**
		 * Rich Text field iframe onload handler.
		 *
		 * @since 1.7.0
		 *
		 * @param {Object} obj Iframe element.
		 */
		loadRichTextField( obj ) {
			const $contents = $( obj.contentWindow.document.documentElement );

			// Replicate `font-family` from the admin page to the iframe document.
			$contents.find( 'body' ).css( 'font-family', $( 'body' ).css( 'font-family' ) );
			// Adjust list styles.
			$contents.find( 'ul, ol' ).css( 'padding-inline-start', '30px' );
			$contents.find( 'li' ).css( 'list-style-position', 'outside' );

			app.resizeRichTextField( obj );
			app.addLinksAttr( obj );
		},

		/**
		 * Resize Rich Text field.
		 *
		 * @since 1.7.0
		 *
		 * @param {Object} obj Iframe element.
		 */
		resizeRichTextField( obj ) {
			if ( ! obj || ! obj.contentWindow ) {
				return;
			}

			const doc = obj.contentWindow.document.documentElement || false;

			if ( ! doc ) {
				return;
			}

			let height = doc.scrollHeight;

			height += doc.scrollWidth > doc.clientWidth ? 20 : 0;

			obj.style.height = height + 'px';
		},

		/**
		 * Add links attributes inside iframe.
		 *
		 * @since 1.7.0
		 *
		 * @param {Object} obj Iframe element.
		 */
		addLinksAttr( obj ) {
			$( obj ).contents().find( 'a' ).each( function() {
				let $this = $( this ); // eslint-disable-line prefer-const

				$this.attr( 'rel', 'noopener' );

				if ( ! $this.attr( 'target' ) ) {
					$this.attr( 'target', '_top' );
				}
			} );
		},

		/**
		 * Bind Settings dropdown.
		 *
		 * @since 1.8.3
		 */
		bindSettingsToggle() {
			$( '#wpforms-entries-settings-button' ).on( 'click', function( event ) {
				event.preventDefault();
				event.stopPropagation();

				// Toggle the visibility of the matched element.
				$( '.wpforms-entries-settings-menu' ).toggle( 0, function() {
					const $menu = $( this );

					// When the dropdown is open, aria-expended="true".
					$menu.attr( 'aria-expanded', $menu.is( ':visible' ) );
				} );
			} );

			$( '.wpforms-entries-settings-menu-items input' ).on( 'change', app.toggleMode );
		},

		/**
		 * Hide dropdown when clicking outside of it.
		 *
		 * @since 1.8.3
		 */
		hideDropdown() {
			// This will hide the dropdown when clicking outside of it.
			$( document ).on( 'click', function( event ) {
				// The dropdown container
				const $target = $( '.wpforms-entries-settings-menu' );
				const $targetClass = '.wpforms-entries-settings-menu';

				// Check if the clicked element is not the dropdown container or a child of it.
				if ( ! $( event.target ).closest( `${ $targetClass }:visible` ).length ) {
					$target.attr( 'aria-expanded', 'false' ).hide();
				}
			} );
		},

		/**
		 * Turn on/off mode in settings.
		 *
		 * @since 1.8.3
		 */
		toggleMode() {
			const $this = $( this );
			const isActive = $this.is( ':checked' );
			// eslint-disable-next-line prefer-const
			let setting = $this.attr( 'name' )
				.replace( /([-_][a-z])/g, group => group.toUpperCase() ) // eslint-disable-line arrow-parens
				.replaceAll( '_', '' );

			app.updateSettings[ setting ]( isActive );

			app.saveData();
		},

		/**
		 * Add alternate styles class to field rows.
		 *
		 * @since 1.8.3
		 */
		addAlternateStyles() {
			$( '.wpforms-field-entry-fields' ).each( function( index ) {
				if ( index % 2 !== 0 ) {
					$( this ).addClass( 'wpforms-entry-field-row-alt' );
				}
			} );
		},

	};

	// Provide access to public functions/properties.
	return app;
}( document, window, jQuery ) );

WPFormsViewEntry.init();
