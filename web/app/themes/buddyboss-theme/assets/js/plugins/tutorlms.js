/**
 * Tutor LMS.
 *
 * @package BuddyBoss_Theme
 *
 * @since [BBVERSION]
 */

( function ( $ ) {
	window.BBLMS = {
		init: function () {
			this.toggleTheme();
			this.TutorSidePanel();
		},

		toggleTheme: function () {

			$( document ).on(
				'click',
				'#bb-toggle-theme',
				function ( e ) {
					e.preventDefault();
					if ( ! $( 'body' ).hasClass( 'bb-dark-theme' ) ) {
						$.cookie( 'bbtheme', 'dark', { path: '/' } );
						$( 'body' ).addClass( 'bb-dark-theme' );
					} else {
						$.removeCookie( 'bbtheme', { path: '/' } );
						$( 'body' ).removeClass( 'bb-dark-theme' );
					}
				}
			);
		},

		TutorSidePanel: function () {

			$( document ).on( 'click', '.header-maximize-link', function ( e ) {
				e.preventDefault();
				$( 'body' ).addClass( 'lms-side-panel-close' );
				$( '.tutor-course-single-sidebar-wrapper' ).addClass( 'lms-topic-sidebar-close' );
				$.cookie( 'lessonpanel', 'closed', { path: '/' } );
			} );

			$( document ).on( 'click', '.header-minimize-link', function ( e ) {
				e.preventDefault();
				$( 'body' ).removeClass( 'lms-side-panel-close' );
				$( '.tutor-course-single-sidebar-wrapper' ).removeClass( 'lms-topic-sidebar-close' );
				$.removeCookie( 'lessonpanel', { path: '/' } );
			} );

			function tutorSaidebarHeight() {
				var bbHeaderHeight = $( '#masthead' ).outerHeight();

				var adminBarHeight = 0;
				if ( $( 'body' ).hasClass( 'admin-bar' ) ) {
					adminBarHeight = $( '#wpadminbar' ).outerHeight();
				}
				$( '.tutor-course-single-sidebar-wrapper' ).css( { 'max-height': 'calc(100vh - ' + ( bbHeaderHeight + adminBarHeight ) + 'px' } );
			}

			tutorSaidebarHeight();

			$( window ).on(
				'resize',
				function () {
					tutorSaidebarHeight();
				}
			);
		},

	};

	$( document ).ready(
		function () {
			window.BBLMS.init();
		}
	);

} )( jQuery );
