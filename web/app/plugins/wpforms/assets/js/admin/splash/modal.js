/* global wpforms_splash_data */
/**
 * WPForms What's New.
 *
 * @since 1.8.7
 */
const WPSplash = window.WPSplash || ( function( document, window, $ ) {
	/**
	 * Public functions and properties.
	 *
	 * @since 1.8.7
	 *
	 * @type {Object}
	 */
	const app = {

		/**
		 * Initialize.
		 *
		 * @since 1.8.7
		 */
		init() {
			$( app.ready );
		},

		/**
		 * Document ready.
		 *
		 * @since 1.8.7
		 */
		ready() {
			app.events();

			if ( wpforms_splash_data.triggerForceOpen ) {
				app.openModal();
			}
		},

		/**
		 * Events.
		 *
		 * @since 1.8.7
		 */
		events() {
			$( document )
				.on( 'click', '.wpforms-splash-modal-open', function( e ) {
					e.preventDefault();
					app.openModal();
				} );
		},

		/**
		 * Open the modal.
		 *
		 * @since 1.8.7
		 */
		openModal() {
			$.alert( {
				title: false,
				content: wp.template( 'wpforms-splash-modal-content' )(),
				icon: false,
				closeIcon: true,
				boxWidth: '1000px',
				theme: 'modern',
				useBootstrap: false,
				scrollToPreviousElement: false,
				buttons: false,
				backgroundDismiss: true,
				offsetTop: 50,
				offsetBottom: 50,
				animation: 'opacity',
				closeAnimation: 'opacity',
				animateFromElement: false,
				onOpenBefore() {
					$( 'body' ).addClass( 'wpforms-splash-modal' );
					$( '.wpforms-challenge-popup-container' ).addClass( 'wpforms-invisible' );
				},
				onOpen() {
					$( '.jconfirm' ).css( 'bottom', 0 );
				},
				onDestroy() {
					$( 'body' ).removeClass( 'wpforms-splash-modal' );
				},
			} );
		},
	};

	// Provide access to public functions/properties.
	return app;
}( document, window, jQuery ) );

WPSplash.init();
