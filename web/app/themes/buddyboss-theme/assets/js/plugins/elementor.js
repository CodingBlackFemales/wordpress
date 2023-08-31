( function ( $ ) {

	"use strict";

	window.BuddyBossThemeElementor = {
		init: function () {
			this.ignoreFitVids();
			this.membersBlock();
		},

		ignoreFitVids: function() {

			$( ".elementor-section[data-settings*='background_video_link']" ).addClass( 'fitvidsignore' );
			$( '.elementor-widget-video' ).addClass( 'fitvidsignore' );
			$( '.elementor-video-container' ).addClass( 'fitvidsignore' );

		},

		membersBlock: function () {
			/**
			 * Show Gamipress Widget data in popup
			 */

			if ( $( '.elementor-widget-bbp-members .gamipress-buddypress-user-details-listing' ).length ) {
				var tempStyles;

				window.BuddyBossThemeElementor.GamiPressWidgetData();

				$( document ).on(
					'click',
					'.elementor-widget-bbp-members .showGamipressData',
					function () {
						$( this ).parent().find( '.GamiPress-data-popup' ).addClass( 'is_active' );
						if ( $( this ).closest( '.bb-sticky-sidebar' ).length ) { // Check if parent is sticky.
							tempStyles = $( this ).closest( '.bb-sticky-sidebar' ).attr( 'style' ); // Store parent's fixed styling and remove to avoid issue.
							$( this ).closest( '.bb-sticky-sidebar' ).attr( 'style', '' );
							$( 'body' ).addClass( 'hide-overflow' );
						}
					}
				);

				$( document ).on(
					'heartbeat-tick',
					function () { // When heartbeat called re-run function for widgets.
						setTimeout(
							function () {
								window.BuddyBossThemeElementor.GamiPressWidgetData();
							},
							1000
						);
					}
				);

				$( '.elementor-widget-bbp-members .bb-members .item-options a' ).on(
					'click',
					function () {
						setTimeout(
							function () {
								window.BuddyBossThemeElementor.GamiPressWidgetData();
							},
							3000
						);
					}
				);

				$( document ).on(
					'click',
					'.elementor-widget-bbp-members .GamiPress-data-popup .hideGamipressData',
					function () {
						$( this ).closest( '.GamiPress-data-popup' ).removeClass( 'is_active' );
						if ( $( this ).closest( '.bb-sticky-sidebar' ).length ) {
							$( this ).closest( '.bb-sticky-sidebar' ).attr( 'style', tempStyles ); // add parent's fixed styling back.
							tempStyles = '';
							$( 'body' ).removeClass( 'hide-overflow' );
						}
					}
				);



			}
		},

		GamiPressWidgetData: function() {
			$( '.elementor-widget-bbp-members .gamipress-buddypress-user-details-listing:not(.is_loaded)' ).each(
				function () {
					if ( $( this ).text().trim() !== '' ) {
						$( this ).parent().append( '<span class="showGamipressData" data-balloon-pos="right" data-balloon="' + bs_data.gamipress_badge_label + '"></span>' );
						if ( $( this ).find( 'img' ).length ) {
							$( this ).parent().find( '.showGamipressData' ).append( '<img src="' + $( this ).find( 'img' ).attr( 'src' ) + '"/>' );
						} else {
							$( this ).parent().find( '.showGamipressData' ).append( '<i class="bb-icon-l bb-icon-award"></i>' );
						}
						$( this ).parent().find( '.gamipress-buddypress-user-details-listing' ).wrap( '<div class="GamiPress-data-popup"></div>' );
						$( this ).parent().find( '.gamipress-buddypress-user-details-listing' ).append( '<i class="bb-icon-l bb-icon-times hideGamipressData"></i>' );
					}
					$( this ).addClass( 'is_loaded' );
				}
			);
		}

	};

	$( document ).on(
		'ready',
		function () {
			window.BuddyBossThemeElementor.init();
		}
	);

} )( jQuery );
