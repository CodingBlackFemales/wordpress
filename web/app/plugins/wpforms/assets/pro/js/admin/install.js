/* global wpforms_install_data */
/**
 * WPForms Admin Install JS
 *
 * @since 1.9.0
 */

const WPFormsAdminInstall = window.WPFormsAdminInstall || ( function( document, window, $ ) {
	/**
	 * Public functions and properties.
	 *
	 * @since 1.9.0
	 */
	const app = {

		/**
		 * Start the engine.
		 *
		 * @since 1.9.0
		 */
		init() {
			$( app.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 1.9.0
		 */
		ready() {
			app.initAddonCheckboxes();
			app.events();
		},

		/**
		 * Register JS events.
		 *
		 * @since 1.9.0
		 */
		events() {
			app.updateButtonAfterInstall();
			app.clickMoreDetailsButton();
		},

		/**
		 * Initialize bulk action checkbox in addons.
		 *
		 * Disable checkboxes for addons with incompatible updates.
		 *
		 * @since 1.9.0
		 */
		initAddonCheckboxes() {
			$( '.wpforms-update-message.notice-error' ).each( function() {
				const $noticeRow = $( this ).closest( '.plugin-update-tr' );
				const $addonRow = $noticeRow.prev( 'tr' );

				$addonRow
					.find( '.check-column' ).css( 'pointer-events', 'none' )
					.find( 'input[type="checkbox"]' ).prop( 'disabled', true );
			} );
		},

		/**
		 * Update the button after WPForms Lite is installed.
		 *
		 * @since 1.9.0
		 */
		updateButtonAfterInstall() {
			$( document ).on( 'wp-plugin-install-success', function( event, response ) {
				if ( response.slug === 'wpforms-lite' ) {
					const button = $( '.plugin-action-buttons a[data-slug="wpforms-lite"]' );

					button.replaceWith( '<button type="button" class="button button-disabled" disabled="disabled">' + wpforms_install_data.activate + '</button>' );
				}
			} );
		},

		/**
		 * Click More Details button.
		 *
		 * @since 1.9.0
		 */
		clickMoreDetailsButton() {
			$( document ).on( 'thickbox:iframe:loaded', '#TB_window', function() {
				const $iframe = $( '#TB_iframeContent' );
				const iframeSrc = $iframe.attr( 'src' );

				// Check if the iframe is for incompatible addon.
				if ( iframeSrc.includes( 'update=disabled' ) ) {
					app.incompatibleAddonDisableUpdateButton( $iframe );

					return;
				}

				// Check if the iframe is for WPForms Lite.
				if ( ! iframeSrc.includes( 'wpforms-lite' ) ) {
					return;
				}

				const footer = $iframe.contents().find( '#plugin-information-footer' );
				const button = footer.find( '.button[data-slug="wpforms-lite"]' );

				// Process only the "Activate" button.
				if ( ! button.hasClass( 'activate-now' ) ) {
					return;
				}

				// Add notice.
				button.before( '<strong style="display:inline-block;margin-top:10px;">' + wpforms_install_data.lite_version_notice + '</strong>' );

				// Replace the button with a disabled one.
				button.replaceWith( '<button type="button" id="plugin_install_from_iframe" class="right button button-disabled" disabled="disabled">' + wpforms_install_data.activate + '</button>' );
			} );
		},

		/**
		 * Disable Update Now button in the addon details popup.
		 *
		 * @since 1.9.0
		 *
		 * @param {jQuery} $iframe The iframe object.
		 */
		incompatibleAddonDisableUpdateButton( $iframe ) {
			const $button = $iframe.contents().find( '#plugin-information-footer .button[data-slug^="wpforms-"]' );

			$button.prop( 'disabled', true ).css( {
				'pointer-events': 'none',
				tabindex: '-1',
				opacity: '0.5',
			} );
		},
	};

	return app;
}( document, window, jQuery ) );

WPFormsAdminInstall.init();
