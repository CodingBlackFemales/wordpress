/* jshint browser: true */
/* global bp, BB_PRO_ADMIN */
/* @version 1.0.0 */

/**
 * Global script file for the admin.
 *
 * @package BuddyBossPro
 * @since [BBVERSION]
 */
window.bp = window.bp || {};

(function ( exports, $ ) {

	bp.BB_Pro_Admin = {

		/**
		 * [start description]
		 *
		 * @return {[type]} [description]
		 */
		start: function () {
			this.setupGlobals();

			// Listen to events ("Add hooks!").
			this.addListeners();
		},

		/**
		 * [setupGlobals description]
		 *
		 * @return {[type]} [description]
		 */
		setupGlobals: function () {
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {
			$( document ).on( 'click', '.bb-onsignal-dismiss-site-notice .notice-dismiss', this.dismissNotice.bind( this ) );
			$( document ).on( 'click', '.bb-zoom-dismiss-site-notice .notice-dismiss', this.dismissZoomNotice.bind( this ) );
			$( document ).on( 'click', '.bb-pro-tabs-list li', this.toggleTabs.bind( this ) );
			$( document ).on( 'click', '.bb-copy-button', this.copyContent.bind( this ) );
		},

		dismissNotice: function( e ) {
			e.preventDefault();

			if ( 'undefined' === typeof BB_PRO_ADMIN.dismiss_notice_nonce ) {
				return;
			}

			$.ajax(
				{
					type: 'POST',
					url: BB_PRO_ADMIN.ajax_url,
					data: {
						'action': 'onesignal_dismiss_notice',
						'nonce': BB_PRO_ADMIN.dismiss_notice_nonce,
					},
				}
			);
		},

		dismissZoomNotice: function( e ) {
			e.preventDefault();

			if ( 'undefined' === typeof BB_PRO_ADMIN.zoom_dismiss_notice_nonce ) {
				return;
			}

			$.ajax(
				{
					type: 'POST',
					url: BB_PRO_ADMIN.ajax_url,
					data: {
						'action': 'zoom_dismiss_notice',
						'nonce': BB_PRO_ADMIN.zoom_dismiss_notice_nonce,
					},
				}
			);
		},

		toggleTabs: function ( e ) {
			e.preventDefault();

			var $clickedTab = $( e.currentTarget );
			var $parent     = $clickedTab.closest( '.bb-pro-tabs' );

			$parent.find( '.bb-pro-tabs-list li' ).removeClass( 'selected' ).attr( 'aria-selected', 'false' );
			$parent.find( '.bb-pro-tabs-content .bb-pro-tabs-content-parts' ).addClass( 'bp-hide' ).attr( 'aria-hidden', 'true' );

			$clickedTab.addClass( 'selected' ).attr( 'aria-selected', 'true' );

			var selectedTab = $clickedTab.attr( 'id' );
			$( '.bb-pro-tabs-content #' + selectedTab + '-content' ).removeClass( 'bp-hide' ).attr( 'aria-hidden', 'false' );
		},

		copyContent: function ( e ) {
			e.preventDefault();

			var $clickedButton 		= $( e.currentTarget );
			var $clickedButtonText	= $( e.currentTarget ).text();
			var $parent        		= $clickedButton.closest( '.copy-toggle' );

			var $content = $parent.find( '.bb-copy-value' );
			$content.select();
			document.execCommand( 'copy' );
			$content.blur();
			$clickedButton.text( $clickedButton.data( 'copied-text' ) );
			setTimeout( function() {
				$clickedButton.text( $clickedButtonText );
			}, 2000 );
		}

	};

	// Launch Platform Pro Admin.
	bp.BB_Pro_Admin.start();

})( bp, jQuery );
