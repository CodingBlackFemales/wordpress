/* global BP_PRO_HELLO */
/**
 * Loads for Hello in wp-admin for query string `hello=buddyboss` and `hello=buddyboss-platform-pro`.
 *
 * @package BuddyBossPro
 * @since [BBVERSION]
 */

(function() {
	/**
	 * Open the Hello BuddyBoss modal.
	 */
	var bp_pro_hello_open_modal = function( event ) {
		var backdrop = document.getElementById( 'bp-pro-hello-backdrop' ),
		modal        = document.querySelector( '#bp-pro-hello-container.bb-onload-modal' );

		if ( null === backdrop ) {
			return;
		}

		if (
			modal.classList.contains( 'bb-update-modal' ) &&
			'undefined' !== typeof BP_PRO_HELLO &&
			'1' !== BP_PRO_HELLO.bb_pro_display_auto_popup &&
			'click' !== event.type
		) {
			return;
		}

		if ( modal.classList.contains( 'bb-onload-modal' ) ) {
			document.body.classList.add( 'bp-pro-disable-scroll' );

			// Show modal and overlay.
			backdrop.style.display = '';
			modal.style.display    = '';

			// Focus the "X" so bp_pro_hello_handle_keyboard_events() works.
			var focus_target = modal.querySelectorAll( 'a[href], button' );
			focus_target     = Array.prototype.slice.call( focus_target );
			focus_target[0].focus();

			if ( modal.classList.contains( 'bb-update-modal' ) ) {
				// Open popup - click on changelog and close popup - Again open popup then changelog tab will active, at that time stop video.
				var iframeSelector = document.querySelector( '.bb-pro-hello-tabs_content iframe' );
				var getHref        = document.querySelector( '.bb-pro-hello-tabs .bb-pro-hello-tabs_anchor.is_active' );
				if ( getHref ) {
					var getHrefWithoutHash = getHref.getAttribute( 'data-action' );
					bbIframeActions( iframeSelector, getHrefWithoutHash );
				}
			}
		}

		// Events.
		modal.addEventListener( 'keydown', bp_pro_hello_handle_keyboard_events );
		backdrop.addEventListener( 'click', bp_pro_hello_close_modal );
	};

	/**
	 * Close modal if "X" or background is touched.
	 *
	 * @param {Event} event - A click event.
	 */
	document.addEventListener(
		'click',
		function( event ) {
			var backdrop = document.getElementById( 'bp-pro-hello-backdrop' );
			if ( ! backdrop || ! document.getElementById( 'bp-pro-hello-container' ) ) {
				return;
			}

			var backdrop_click = backdrop.contains( event.target ),
			modal_close_click  = event.target.classList.contains( 'close-modal' );

			if ( ! modal_close_click && ! backdrop_click ) {
				return;
			}

			bp_pro_hello_close_modal();
		},
		false
	);

	/**
	 * Close the Hello modal.
	 */
	var bp_pro_hello_close_modal = function() {

		document.getElementById( 'bp-pro-hello-container' ).setAttribute( 'style', 'display:none' );
		document.getElementById( 'bp-pro-hello-backdrop' ).setAttribute( 'style', 'display:none' );
		document.body.className = document.body.className.replace( 'bp-pro-disable-scroll','' );
		// Close model then video should also stop.
		var iframeSelector = document.querySelector( '.bb-pro-hello-tabs_content iframe' );
		var getHref        = document.querySelector( '.bb-pro-hello-tabs .bb-pro-hello-tabs_anchor.is_active' );
		if ( getHref ) {
			var getHrefWithoutHash = getHref.getAttribute( 'data-action' );
			bbIframeActions( iframeSelector, getHrefWithoutHash );
		}
	};

	/**
	 * Restrict keyboard focus to elements within the Hello BuddyBoss modal.
	 *
	 * @param {Event} event - A keyboard focus event.
	 */
	var bp_pro_hello_handle_keyboard_events = function( event ) {
		var modal          = document.getElementById( 'bp-pro-hello-container' ),
			focus_targets  = Array.prototype.slice.call(
				modal.querySelectorAll( 'a[href], button' )
			),
			first_tab_stop = focus_targets[0],
			last_tab_stop  = focus_targets[ focus_targets.length - 1 ];

		// Check for TAB key press.
		if ( event.keyCode !== 9 ) {
			return;
		}

		// When SHIFT+TAB on first tab stop, go to last tab stop in modal.
		if ( event.shiftKey && document.activeElement === first_tab_stop ) {
			event.preventDefault();
			last_tab_stop.focus();

			// When TAB reaches last tab stop, go to first tab stop in modal.
		} else if ( document.activeElement === last_tab_stop ) {
			event.preventDefault();
			first_tab_stop.focus();
		}
	};

	/**
	 * Close modal if escape key is presssed.
	 *
	 * @param {Event} event - A keyboard focus event.
	 */
	document.addEventListener(
		'keyup',
		function( event ) {
			if ( event.keyCode === 27 ) {
				if ( ! document.getElementById( 'bp-pro-hello-backdrop' ) || ! document.getElementById( 'bp-pro-hello-container' ) ) {
					return;
				}

				bp_pro_hello_close_modal();
			}
		},
		false
	);

	// Init modal after the screen's loaded.
	if ( document.attachEvent ? document.readyState === 'complete' : document.readyState !== 'loading' ) {
		bp_pro_hello_open_modal();
	} else {
		document.addEventListener( 'DOMContentLoaded', bp_pro_hello_open_modal );
	}
	var link = document.getElementById( 'bb-pro-plugin-release-link' );
	if ( link ) {
		link.addEventListener( 'click', bp_pro_hello_open_modal );
	}

	// Load tab for release content.
	var tab = document.querySelectorAll( '.bb-pro-hello-tabs_anchor' );
	if ( tab ) {
		for ( var i = 0; i < tab.length; i++ ) {
			tab[i].addEventListener( 'click', bbOnTabClick, false );
		}
	}

	function bbOnTabClick ( event ) {
		event.preventDefault();
		// deactivate existing active tabs and tabContent.
		for ( var i = 0; i < tab.length; i++ ) {
			tab[i].classList.remove( 'is_active' );
		}
		var tabContent = document.querySelectorAll( '.bb-pro-hello-tabs_content' );

		if ( tabContent ) {
			for ( var j = 0; j < tabContent.length; j++ ) {
				tabContent[j].classList.remove( 'is_active' );
			}
		}

		// activate new tabs and tabContent.
		event.target.classList.add( 'is_active' );
		var getHrefWithoutHash = event.target.getAttribute( 'data-action' );
		// If tab change from overview to changelog then also stop video in overview tab content.
		var iframeSelector = document.querySelector( '.bb-pro-hello-tabs_content iframe' );
		bbIframeActions( iframeSelector, getHrefWithoutHash );
		document.getElementById( getHrefWithoutHash ).classList.add( 'is_active' );
	}

	function bbIframeActions ( iframeSelector, getHrefWithoutHash ) {
		if ( 'bb-release-overview' !== getHrefWithoutHash ) { // If not overview tab then stop video.
			if ( iframeSelector && iframeSelector.src ) {
				var iframeSelectorData = iframeSelector.src;
				iframeSelector.src     = '';
				iframeSelector.src     = iframeSelectorData;
			}
		} else {
			if ( iframeSelector ) { // If overview tab then autoplay video.
				iframeSelector.src = iframeSelector.src;
			}
		}
	}
}());
