// import learndash_sidebar from "./learndash-sidebar";
/* global bs_data, BBGetUrlParameter */
( function ( $ ) {

	"use strict";

	window.BBLMS = {
		init: function () {
			this.switchLdGridList();
			this.toggleTheme();
			this.learnDashSidePanel();
			this.lms_user_profile_js();
			// this.lms_course_single_js();
			this.lms_single_course();
			this.course_archive_js();
			// this.quiz_progress();
			this.quizDetails();
			// this.ajaxCompleteProcess();
			this.quizUpload();
			this.setElementorSpacing();
			this.courseViewCookie();
			this.bbStickyLdSidebar();
			// this.StyleInputQuestion();
			this.singleLesson();
			this.singleTopic();
			this.singleQuiz();
			this.showMoreParticipants();
			this.inforBarStatus();
		},

		switchLdGridList: function() {

			var courseLoopSelector = $( '.bb-course-items:not(.is-cover)' );
			if ( window.sessionStorage ) {
				var getView = sessionStorage.getItem( 'course-view' );
				if ( typeof getView === 'undefined' || getView === null ) {
					sessionStorage.setItem( 'course-view', 'grid' );
					getView = sessionStorage.getItem( 'course-view' );
				}

				$( '.layout-view-course' ).removeClass( 'active' );
				courseLoopSelector.removeClass( 'grid-view' );
				courseLoopSelector.removeClass( 'bb-grid' );
				courseLoopSelector.removeClass( 'list-view' );
				courseLoopSelector.removeClass( 'bb-list' );

				if ( 'grid' === getView ) {
					$( '.layout-view-course.layout-grid-view' ).addClass( 'active' );
					courseLoopSelector.addClass( 'grid-view' );
					courseLoopSelector.addClass( 'bb-grid' );
				} else {
					$( '.layout-view-course.layout-list-view' ).addClass( 'active' );
					courseLoopSelector.addClass( 'list-view' );
					courseLoopSelector.addClass( 'bb-list' );
				}
			}
			$( document ).on(
				'click',
				'.grid-filters .layout-view-course',
				function(e) {
					e.preventDefault();
					courseLoopSelector = $( e.target ).closest( 'form' ).find( '.bb-course-items:not(.is-cover)' );
					if ( $( this ).hasClass( 'layout-list-view' ) ) {
						if ( window.sessionStorage ) {
							sessionStorage.setItem( 'course-view', 'list' );
						}
						$( '.layout-view-course' ).removeClass( 'active' );
						courseLoopSelector.removeClass( 'grid-view' );
						courseLoopSelector.removeClass( 'bb-grid' );
						courseLoopSelector.removeClass( 'list-view' );
						courseLoopSelector.removeClass( 'bb-list' );
						$( '.layout-view-course.layout-list-view' ).addClass( 'active' );
						courseLoopSelector.addClass( 'list-view' );
						courseLoopSelector.addClass( 'bb-list' );
						$.ajax(
							{
								method  : 'GET',
								url     : bs_data.ajaxurl,
								data    : 'action=buddyboss_lms_save_view&option=bb_theme_learndash_grid_list&type=list',
								success : function ( response ) {
								}
							}
						);
					} else {
						if ( window.sessionStorage ) {
							sessionStorage.setItem( 'course-view', 'grid' );
						}
						$( '.layout-view-course' ).removeClass( 'active' );
						courseLoopSelector.removeClass( 'grid-view' );
						courseLoopSelector.removeClass( 'bb-grid' );
						courseLoopSelector.removeClass( 'list-view' );
						courseLoopSelector.removeClass( 'bb-list' );
						$( '.layout-view-course.layout-grid-view' ).addClass( 'active' );
						courseLoopSelector.addClass( 'grid-view' );
						courseLoopSelector.addClass( 'bb-grid' );
						$.ajax(
							{
								method  : 'GET',
								url     : bs_data.ajaxurl,
								data    : 'action=buddyboss_lms_save_view&option=bb_theme_learndash_grid_list&type=grid',
								success : function ( response ) {
								}
							}
						);
					}
				}
			);
		},

		showMoreParticipants: function() {

			var total            = $( '.lms-course-members-list .lms-course-sidebar-heading .lms-count' ).text();
			var paged            = 2;
			var course           = $( '.lms-course-members-list #buddyboss_theme_learndash_course_participants_course_id' ).val();
			var spinnerSelector  = $( '.lms-course-members-list .bb-course-member-wrap .lme-more i' );
			var viewMoreSelector = $( '.lms-course-members-list .bb-course-member-wrap .lme-more' );

			$( '.lms-course-members-list' ).on(
				'click',
				'.bb-course-member-wrap .lme-more',
				function() {

					if ( $( this ).hasClass( 'loading-members' ) ) {
						return;
					}

					$( this ).addClass( 'loading-members' );

					spinnerSelector.removeClass( 'bb-icon-angle-down' );
					spinnerSelector.addClass( 'bb-icon-spin' );
					spinnerSelector.addClass( 'animate-spin' );
					$( '.lms-course-members-list .bb-course-member-wrap .lme-less' ).hide();

					$.ajax(
						{
							method  : 'GET',
							url     : bs_data.ajaxurl,
							data    : 'action=buddyboss_lms_get_course_participants&_wpnonce=' + bs_data.learndash.nonce_get_courses + '&total=' + total + '&page=' + paged + '&course=' + course,
							success : function ( response ) {
								$( '.lms-course-members-list .bb-course-member-wrap .course-members-list.course-members-list-extra' ).show();
								$( '.lms-course-members-list .bb-course-member-wrap .course-members-list-extra' ).append( response.data.html );
								$( window ).trigger( 'resize' );
								if ( 'false' === response.data.show_more ) {
									$( '.lms-course-members-list .bb-course-member-wrap .lme-more' ).remove();
								}
								paged = response.data.page;
								if ( $( '.ld-sidebar-widgets' ).length === 0 ) {
									$( '.lms-topic-sidebar-wrapper .lms-topic-sidebar-data' ).animate( { scrollTop: $( document ).height() }, 1000 );
								}
								spinnerSelector.addClass( 'bb-icon-angle-down' );
								spinnerSelector.removeClass( 'bb-icon-spin' );
								spinnerSelector.removeClass( 'animate-spin' );
								viewMoreSelector.removeClass( 'loading-members' );
							}
						}
					);
				}
			);

		},

		fetchCourses: function( target ) {
			var $form = $( '#bb-courses-directory-form' );
			if ( typeof target !== 'undefined' && $( target ).length > 0 ) {
				$form = $( target ).closest( 'form.bb-courses-directory' );
			}

			var reset_pagination = false;

			// reset pagintion if categories or instructor filters are changed.
			// reset pagination if search term has changed.
			var resetting_fields = [ 'filter-categories', 'filter-instructors', 'search' ];
			for ( var i = 0; i < resetting_fields.length; i++ ) {
				var prev_val = BBGetUrlParameter( window.location.search, resetting_fields[ i ] );
				var new_val  = $form.find( '[name="' + resetting_fields[ i ] + '"]' ).val();

				if ( prev_val !== new_val ) {

					switch ( resetting_fields[ i ] ) {
						case 'filter-categories':
						case 'filter-instructors':
							if ( ! prev_val && new_val === 'all' ) {
								// hasn't really changed.
							} else {
								reset_pagination = true;
							}
							break;
						default:
							reset_pagination = true;
							break;
					}

				}

				if ( reset_pagination ) {
					break;
				}
			}

			if ( reset_pagination ) {
				$form.find( '[name="current_page"]' ).val( 1 );
			}

			var data = $form.serialize();

			// update url.
			var new_url = bs_data.learndash.course_archive_url;
			if ( $form.hasClass( 'bb-elementor-widget' ) ) {
				new_url = $form.data( 'current_page_url' );
			}

			// view.
			var view = 'grid';
			if ( $form.find( '.layout-list-view' ).hasClass( 'active' ) ) {
				view = 'list';
			}
			data += '&view=' + view + '&request_url=' + encodeURIComponent( new_url );

			$.ajax(
				{
					method  : 'GET',
					url     : bs_data.ajaxurl,
					data    : data + '&action=buddyboss_lms_get_courses&_wpnonce=' + bs_data.learndash.nonce_get_courses,
					success : function ( response ) {

						var current_page = $form.find( '[name="current_page"]' ).val();
						if ( isNaN( current_page ) ) {
							current_page = 1;
						}
						if ( current_page > 1 ) {
							new_url += 'page/' + current_page + '/';
						}

						new_url += '?' + data;

						window.history.pushState( { 'bblms_has_changes' : true, 'courses_html' : response.data.html, 'type' : $form.find( '[name="type"]' ).val() }, "", new_url );

						// update html.
						$form.find( '.bs-dir-list' ).html( response.data.html );
						// update count.
						$form.find( 'li.selected a span' ).text( response.data.count );

						if ( response.data.scopes ) {
							for (var i in response.data.scopes) {
								$form.find( 'li#courses-' + i + ' a span' ).text( response.data.scopes[i] ).show();
							}
						}

						$( '.courses-nav' ).find( '.bb-icon-loader' ).remove();
					}
				}
			);

			return false;
			// $( '#bb-courses-directory-form' ).submit();
		},

		fetchCoursesPagination: function( target ) {
			var $form = $( '#bb-courses-directory-form' );
			if ( typeof target !== 'undefined' && $( target ).length > 0 ) {
				$form = $( target ).closest( 'form.bb-courses-directory' );
			}
			var data = $form.serialize();

			// update url.
			var new_url = $form.attr( 'action' );
			if ( $form.hasClass( 'bb-elementor-widget' ) ) {
				new_url = $form.data( 'current_page_url' );
			}

			if ( '' === new_url ) {
				new_url = bs_data.learndash.course_archive_url;
			}

			// view.
			var view = 'list';
			if ( $form.find( '.layout-grid-view' ).hasClass( 'active' ) ) {
				view = 'grid';
			}
			data += '&view=' + view + '&request_url=' + encodeURIComponent( new_url );

			var order = $form.data( 'order' );
			if ( typeof order !== 'undefined' && order !== false ) {
				data = data + '&order=' + order;
			}

			var orderby = $form.data( 'orderby' );
			if ( typeof orderby !== 'undefined' && orderby !== false ) {
				data = data + '&orderby=' + orderby;
			}

			$.ajax(
				{
					method  : 'GET',
					url     : bs_data.ajaxurl,
					data    : data + '&action=buddyboss_lms_get_courses&_wpnonce=' + bs_data.learndash.nonce_get_courses,
					success : function ( response ) {
						var current_page = $form.find( '[name="current_page"]' ).val();
						if ( isNaN( current_page ) ) {
							current_page = 1;
						}
						if ( '/' !== new_url.substr( -1 ) ) {
							new_url += '/';
						}
						if ( current_page > 1 ) {
							new_url += 'page/' + current_page + '/';
						}

						new_url += '?' + data;

						window.history.pushState( { 'bblms_has_changes' : true, 'courses_html' : response.data.html, 'type' : $form.find( '[name="type"]' ).val() }, "", new_url );

						// update html.
						$form.find( '.bs-dir-list' ).html( response.data.html );

						// update count.
						$form.find( 'li.selected a span' ).text( response.data.count );

						if ( response.data.scopes ) {
							for (var i in response.data.scopes) {
								$form.find( 'li#courses-' + i + ' a span' ).text( response.data.scopes[i] ).show();
							}
						}
						$( '.courses-nav' ).find( '.bb-icon-loader' ).remove();
					}
				}
			);

			return false;
			// $( '#bb-courses-directory-form' ).submit();
		},

		course_archive_js: function() {

			$( document ).on(
				'change',
				'#bb-courses-directory-form input[type=checkbox]',
				function ( e ) {
					e.preventDefault();
					window.BBLMS.fetchCourses();
				}
			);

			window.onpopstate = function(e) {
				if ( ! e.state ) {
					return;
				}

				var has_changes = e.state.hasOwnProperty( 'bblms_has_changes' ) ? e.state.bblms_has_changes : false;
				if ( has_changes ) {
					var $form = $( '#bb-courses-directory-form' );

					// update courses html.
					$form.find( '.bs-dir-list' ).html( e.state.courses_html );

					// highlight correct nav.
					$form.find( '[name="type"]' ).val( e.state.type );

					$form.find( '.component-navigation > li' ).each(
						function(){
							$( this ).removeClass( 'selected' );
							var type = BBGetUrlParameter( $( this ).find( ' > a' ).attr( 'href' ), 'type' );
							if ( type === e.state.type ) {
								$( this ).addClass( 'selected' );
							}
						}
					);
				}
			};

			$( document ).on(
				'click',
				'#bb-course-list-grid-filters .grid-filters a',
				function ( e ) {
					e.preventDefault();
					$( '#bb-course-list-grid-filters .grid-filters a' ).removeClass( 'active' );
					$( e.currentTarget ).addClass( 'active' );
					var view     = $( e.currentTarget ).data( 'view' );
					var selector = $( '.ld-course-list-content' );
					if ( selector.hasClass( 'list-view' ) ) {
						selector.removeClass( 'list-view' );
					}

					if ( selector.hasClass( 'grid-view' ) ) {
						selector.removeClass( 'grid-view' );
					}

					selector.addClass( view + '-view' );

					$.ajax(
						{
							method  : 'GET',
							url     : bs_data.ajaxurl,
							data    : 'action=buddyboss_lms_save_view&option=bb_theme_learndash_grid_list&type=' + view,
							success : function ( response ) {
							}
						}
					);

				}
			);

			$( document ).ready(
				function() {
					if ( $( 'body #bb-course-list-grid-filters' ).length ) {
						var active = '';
						if ($( '#bb-course-list-grid-filters .grid-filters .layout-grid-view' ).hasClass( 'active' )) {
							active = 'grid-view';
						} else {
							active = 'list-view';
						}
						$( '.ld-course-list-content' ).addClass( active );
					}
				}
			);

			$( document ).on(
				'change',
				'#bb-courses-directory-form [name=\'orderby\'], #bb-courses-directory-form [name=\'filter-categories\'], #bb-courses-directory-form [name=\'filter-instructors\']',
				function ( e ) {
					e.preventDefault();
					window.BBLMS.fetchCourses( e.target );
				}
			);

			$( document ).on(
				'click',
				'#bb-courses-directory-form .bs-sort-button',
				function ( e ) {
					e.preventDefault();
					e.currentTarget.classList.toggle( 'active' );
					$( '#bs-courses-order-by' ).toggleClass( 'open' );
				}
			);

			$( document ).on(
				'click',
				'#bb-courses-directory-form .bb-lms-pagination a.page-numbers',
				function ( e ) {
					e.preventDefault();
					var page_number = 1;
					var url_parts   = $( this ).attr( 'href' ).split( '/' );
					if ( url_parts.length > 0 ) {
						for ( var i = 0; i < url_parts.length; i++ ) {
							if ( 'page' === url_parts[i] ) {
								page_number = url_parts[ i + 1 ];
								break;
							}
						}
					}

					if ( 0 === $( this ).closest( 'form' ).find( '[name="current_page"]' ).length ) {
						$( '<input>' ).attr(
							{
								type: 'hidden',
								name: 'current_page'
							}
						).appendTo( $( this ).closest( 'form' ) );
					}
					$( this ).closest( 'form' ).find( '[name="current_page"]' ).val( page_number );
					window.BBLMS.fetchCoursesPagination( e.target );
				}
			);

			$( document ).on(
				'click',
				'#bb-courses-directory-form .component-navigation a:not(.more-button)',
				function ( e ) {
					e.preventDefault();

					$( this ).closest( '.component-navigation' ).find( '> li' ).removeClass( 'selected' );
					$( this ).closest( 'li' ).addClass( 'selected' ).append( '<i class="bb-icon-loader animate-spin"></i>' );

					var type = BBGetUrlParameter( $( this ).attr( 'href' ), 'type' );
					$( this ).closest( 'form' ).find( '[name="type"]' ).val( type );

					// resetting the page number if important, as sometimes 'all courses' can have more items than 'my courses'.
					$( this ).closest( 'form' ).find( '[name="current_page"]' ).val( 1 );
					window.BBLMS.fetchCourses( e.target );
				}
			);

			document.addEventListener(
				'click',
				function ( e ) {
					var openFilterDropdown = $( '#course-order-dropdown' );
					var target             = e.target;
					if ( openFilterDropdown === target && openFilterDropdown.contains( target ) ) {
						return false;
					}

					var dropdowns     = $( '#bb-courses-directory-form .bs-dropdown' );
					var download_link = $( '#bb-courses-directory-form .bs-dropdown-link' );

					for ( var i = 0; i < download_link.length; i++ ) {
						if ( download_link[i] !== target && ! download_link[i].contains( target ) ) {
							download_link[i].classList.remove( 'active' );
						}
					}

					for ( var i = 0; i < dropdowns.length; i++ ) {
						if ( dropdowns[i] != target.parentElement.nextElementSibling ) {
							dropdowns[i].classList.remove( 'open' );
						}
					}
				}
			);

			$( 'form.bb-courses-directory' ).on(
				'submit',
				function(e){
					e.preventDefault();
					window.BBLMS.fetchCourses( e.target );
					return false;
				}
			);

			$( '#bb-courses-directory-form #bs_members_search' ).on(
				'keypress' ,
				function(e){
					if ( e.which == 13 ) {
						$( e.target ).closest( 'form.bb-courses-directory' ).submit();
					}
				}
			);

		},

		toggleTheme: function() {

			$( document ).on(
				'click',
				'#bb-toggle-theme',
				function ( e ) {
					e.preventDefault();
					var color = '';
					if ( ! $( 'body' ).hasClass( 'bb-dark-theme' ) ) {
						$.cookie( 'bbtheme', 'dark', { path: '/' } );
						$( 'body' ).addClass( 'bb-dark-theme' );
						color = 'dark';
					} else {
						$.removeCookie( 'bbtheme', { path: '/' } );
						$( 'body' ).removeClass( 'bb-dark-theme' );
					}

					if ( typeof( toggle_theme_ajax ) != 'undefined' && toggle_theme_ajax != null ) {
						toggle_theme_ajax.abort();
					}

					var data = {
						'action': 'buddyboss_lms_toggle_theme_color',
						'color': color
					};

					// since 2.8 ajaxurl is always defined in the admin header and points to admin-ajax.php.
					if ( typeof( toggle_theme_ajax ) != 'undefined' && toggle_theme_ajax != null ) {
						toggle_theme_ajax = $.post( ajaxurl, data, function ( response ) {} );
					}
				}
			);
		},

		learnDashSidePanel: function() {

			/* Resize elementor header */
            function elementorHeaderTriggerResize() {
                if ( $( '.bb-buddypanel.bb-sfwd-aside .site-header--elementor' ).length > 0 ) {
                    setTimeout(
                        function () {
                            $( window ).trigger( 'resize' );
                        },
                        300
                    );
                    setTimeout(
                        function () {
                            $( window ).trigger( 'resize' );
                        },
                        500
                    );
                }
            }

			$( document ).on(
				'click',
				'.header-maximize-link',
				function ( e ) {
					e.preventDefault();
					$( 'body' ).addClass( 'lms-side-panel-close' );
					$( '.lms-topic-sidebar-wrapper' ).addClass( 'lms-topic-sidebar-close' );
					$.cookie( 'lessonpanel', 'closed', { path: '/' } );
					elementorHeaderTriggerResize();
				}
			);

			$( document ).on(
				'click',
				'.header-minimize-link',
				function ( e ) {
					e.preventDefault();
					$( 'body' ).removeClass( 'lms-side-panel-close' );
					$( '.lms-topic-sidebar-wrapper' ).removeClass( 'lms-topic-sidebar-close' );
					$.removeCookie( 'lessonpanel', { path: '/' } );
					elementorHeaderTriggerResize();
				}
			);

			if ( $( window ).width() < 768 ) {
				$( 'body' ).addClass( 'lms-side-panel-close' );
				$( '.lms-topic-sidebar-wrapper' ).addClass( 'lms-topic-sidebar-close show-in-mobile' );

				$( document ).on(
					'click',
					'.header-minimize-link',
					function ( e ) {
						e.preventDefault();
						$( 'body' ).addClass( 'lms-side-panel-close-sm' );
					}
				);

				$( document ).click(
					function(e) {
						var container = $( '.header-minimize-link' );
						if ( ! container.is( e.target ) && container.has( e.target ).length === 0 ) {
							  $( 'body' ).removeClass( 'lms-side-panel-close-sm' );
						}
					}
				);
			}

			$( window ).on(
				'resize',
				function () {
					if ( $( window ).width() < 768 ) {
						$( document ).click(
							function(e) {
								var container = $( '.header-minimize-link' );
								if ( ! container.is( e.target ) && container.has( e.target ).length === 0 ) {
									  $( 'body' ).removeClass( 'lms-side-panel-close-sm' );
								}
							}
						);
					}
				}
			);
		},

		lms_course_single_js: function() {
			$( document ).on(
				'click',
				'.learndash-course-single-nav',
				function ( e ) {
					e.preventDefault();
					$( 'ul.learndash-course-single-main-nav' ).find( 'li.current.selected' ).removeClass( 'current' ).removeClass( 'selected' );

					var li = $( e.currentTarget ).closest( 'li' );
					li.addClass( 'current' );
					li.addClass( 'selected' );

					var currentTab    = $( e.currentTarget );
					var tab_to_change = currentTab.data( 'tab' );

					var content_elems = $( '.learndash-course-single-tab-content' );
					$.each(
						content_elems,
						function ( elem ) {
							var _this = $( this );
							if ( _this.data( 'tab' ) == tab_to_change ) {
								_this.removeClass( 'hide' );
							} else {
								_this.addClass( 'hide' );
							}
						}
					);

				}
			);
		},

		lms_single_course: function() {
			$( '.bb-course-video-overlay' ).magnificPopup(
				{
					fixedBgPos: true,
					fixedContentPos: true,
					items: {
						src: '.bb_course_video_details',
						type: 'inline'
					},
					callbacks: {
						open: function () {
							// Pause the video if someone click on the close button of the magnificPopup.
							$.magnificPopup.instance.close = function () {
								if ($( '.mfp-container .mfp-content .bb_course_video_details video'.length )) {
									$( 'video' ).trigger( 'pause' );
									$.magnificPopup.proto.close.call( this );
								}
							};
						}
					}
				}
			);

			function sideBarPosition() {
				var courseBannerHeight = $( '.bb-learndash-banner' ).height();
				var courseBannerVideo  = $( '.bb-thumbnail-preview .bb-preview-course-link-wrap' );
				if ( courseBannerVideo.length ) {
					var thumbnailContainerHeight = courseBannerVideo.height();
				} else {
					var thumbnailContainerHeight = 0;
				}
				var sidebarOffset = ( courseBannerHeight / 2 ) + ( thumbnailContainerHeight / 2 );
				if ( $( window ).width() > 820 ) {
					$( '.bb-single-course-sidebar.bb-preview-wrap' ).css( { 'margin-top' : '-' + sidebarOffset + 'px' } );
				}
			}

			function courseBanner() {
				var mainWidth = $( '#main' ).width();
				$( '.bb-learndash-banner .bb-course-banner-info.container' ).width( mainWidth );
			}

			sideBarPosition();
			courseBanner();

			$( window ).on(
				'resize',
				function () {
					courseBanner();
					sideBarPosition();
				}
			);

			$( '.bb-toggle-panel' ).on(
				'click',
				function(e) {
					e.preventDefault();

					setTimeout(
						function(){
							courseBanner();
						},
						300
					);
					setTimeout(
						function(){
							courseBanner();
						},
						600
					);
				}
			);

			$( '.lms-topic-sidebar-wrapper .lms-toggle-lesson' ).on(
				'click',
				function() {
					setTimeout( function(){ $( window ).trigger( 'resize' ); }, 300 );
				}
			);
		},

		lms_user_profile_js: function() {
			$( document ).on(
				'click',
				'.bb-lms-user-profile-tab',
				function ( e ) {
					e.preventDefault();
					$( 'ul.bb-lms-user-profile-tabs' ).find( 'li.current.selected' ).removeClass( 'current' ).removeClass( 'selected' );

					var li = $( e.currentTarget ).closest( 'li' );
					li.addClass( 'current' );
					li.addClass( 'selected' );

					var currentTab    = $( e.currentTarget );
					var tab_to_change = currentTab.data( 'tab' );

					var content_elems = $( '.bb-lms-user-profile-tab-content' );
					$.each(
						content_elems,
						function ( elem ) {
							var _this = $( this );
							if ( _this.data( 'tab' ) == tab_to_change ) {
								_this.removeClass( 'hide' );
							} else {
								_this.addClass( 'hide' );
							}
						}
					);

				}
			);
		},

		quizDetails: function() {

			if ( $( '#bb-lms-quiz-id' ).length ) {
				var quiz_id = $( '#bb-lms-quiz-id' ).val();

				$( 'div.quiz_progress_container' ).insertBefore( $( '.wpProQuiz_results .wpProQuiz_resultTable' ) );

				$( '#wpProQuiz_' + quiz_id ).on(
					'learndash-quiz-init',
					function () {
						// $( document ).find( 'input[name="startQuiz"]' ).click( function () {
						// BBLMS.showQuizNavigation();
						// } );
						//
						// $( document ).on( 'click', '.bb-lms-quiz-questions', function ( e ) {
						// e.preventDefault();
						// var index = $( e.currentTarget ).data( 'index' );
						// if ( typeof index !== 'undefined' ) {
						// $( '#wpProQuiz_' + quiz_id ).data( "wpProQuizFront" ).methode.showQuestion( index );
						// }
						// } );
					}
				);
			}
		},

		ajaxCompleteProcess: function () {
			$( document ).ajaxComplete(
				function (event, request, settings) {
					if (settings.data.indexOf( 'action=wp_pro_quiz_load_quiz_data' ) || settings.data.indexOf( 'action=wp_pro_quiz_completed_quiz' )) {
						if (settings.data && settings.data != '') {
							var splitted = settings.data.split( '&' );
							var action   = '';
							for (var i in splitted) {
								if (splitted[i].indexOf( 'action' ) != -1) {
									action = splitted[i].split( '=' );
									action = typeof action[1] !== 'undefined' ? action[1] : '';
									break;
								}
							}
							if (action != '' && action == 'wp_pro_quiz_load_quiz_data') {
								if ($( '.wpProQuiz_resultTable' ).length) {
									$( '.bb_avg_progress' ).show();

									var pathAvg = new ProgressBar.Path(
										"#bb_avg_shape",
										{
											duration: 3000,
											from: {
												color: "#ECCBFF",
												width: 8
											},
											to: {
												color: "#ECCBFF",
												width: 8
											},
											easing: "easeInOut",
											step: function (state, shape) {
												shape.path.setAttribute( "stroke", state.color );
												shape.path.setAttribute( "stroke-width", state.width );
											}
										}
									);

									var avarage = request.responseJSON.averageResult;
									if (avarage > 0) {
										avarage = -avarage / 100;
									} else {
										avarage = 0;
									}
									pathAvg.animate( avarage );
								}
							}
							if (action != '' && action == 'wp_pro_quiz_completed_quiz') {
								if ($( '.wpProQuiz_resultTable' ).length) {

									var data     = decodeURIComponent( settings.data );
									var splitted = data.split( '&' );
									var result   = '';
									for (var i in splitted) {
										if (splitted[i].indexOf( 'results' ) != -1) {
											result = splitted[i].split( '=' );
											result = typeof result[1] !== 'undefined' ? result[1] : '';
											result = JSON.parse( result );
											break;
										}
									}

									if (typeof result.comp.result !== 'undefined') {
										result = result.comp.result;
									}

									var path = new ProgressBar.Path(
										"#quiz_shape_progress",
										{
											duration: 3000,
											from: {
												color: "#00A2FF",
												width: 8
											},
											to: {
												color: "#7FE0FF",
												width: 8
											},
											easing: "easeInOut",
											step: function (state, shape) {
												shape.path.setAttribute( "stroke", state.color );
												shape.path.setAttribute( "stroke-width", state.width );
											}
										}
									);

									jQuery( '.bb_progressbar_label' ).text( result + '%' );
									jQuery( '.bb_progressbar_points' ).text( jQuery( '.wpProQuiz_points' ).text() );

									if (result > 0) {
										result = -result / 100;
									}

									path.animate( result );
								}
							}
						}
					}
				}
			);
		},

		quizUpload: function() {
			function inputFileStyle() {
				$( 'input.wpProQuiz_upload_essay[type=file]:not(.styled)' ).each(
					function () {
						var $fileInput    = $( this );
						var $fileInputFor = $fileInput.attr( 'id' );
						$fileInput.addClass( 'styled' );
						$fileInput.after( '<label for="' + $fileInputFor + '">' + bs_data.translation.choose_a_file_label + '</label>' );
					}
				);

				$( 'input.wpProQuiz_upload_essay[type=file]' ).change(
					function ( e ) {
						var $in    = $( this );
						var $inval = $in.next().html( $in.val() );
						if ( $in.val().length === 0 ) {
							  $in.next().html( bs_data.translation.choose_a_file_label );
						} else {
							$in.next().html( $in.val().replace( /C:\\fakepath\\/i, '' ) );
						}
					}
				);
			}

			inputFileStyle();
			$( document ).ajaxComplete(
				function() {
					inputFileStyle();
				}
			);
		},

		courseViewCookie: function () {
			$( '#bb-courses-directory-form .layout-grid-view' ).click(
				function () {
					$.cookie( 'courseview', 'grid' );
				}
			);

			$( '#bb-courses-directory-form .layout-list-view' ).click(
				function () {
					$.cookie( 'courseview', 'list' );
				}
			);
		},

		bbStickyLdSidebar: function () {
			function ldSaidebarPosition() {
				var bbHeaderHeight = $( '#masthead' ).outerHeight();

				if ( $( window ).width() > 820 && $( '.bb-ld-sticky-sidebar .ld-sidebar-widgets' ).length == 0 ) {
					$( '.bb-ld-sticky-sidebar' ).stick_in_parent( {offset_top: bbHeaderHeight + 45} );

					var adminBarHeight = 0;
					if ( $( 'body' ).hasClass( 'admin-bar' ) ) {
						adminBarHeight = 32;
					}
					$( '.lms-topic-sidebar-data' ).css( {'max-height': 'calc(100vh - ' + ( bbHeaderHeight + adminBarHeight ) + 'px', 'top': ( bbHeaderHeight + adminBarHeight ) + 'px' } );
					/* Learndash single lesson/topic/quiz pages - header always sticky */
					/*if( !$('body').hasClass( 'sticky-header' ) ) {
						if( $(window).scrollTop() >= $('#masthead').outerHeight() ) {
							bbHeaderHeight = 0;
						} else {
							bbHeaderHeight = $('#masthead').outerHeight();
						}
						$('.lms-topic-sidebar-data').css({'max-height': 'calc(100vh - '+ ( bbHeaderHeight + adminBarHeight ) +'px', 'top': ( bbHeaderHeight + adminBarHeight ) +'px' });
					}*/

				} else {
					$( '.bb-ld-sticky-sidebar' ).trigger( "sticky_kit:detach" );
					// $('.lms-topic-sidebar-data').trigger("sticky_kit:detach");
				}
			}

			ldSaidebarPosition();

			$( window ).on(
				'resize',
				function () {
					ldSaidebarPosition();
				}
			);

			$(window).on('scroll', function () {
				if ( $( '#learndash-payment-button-dropdown' ).length ) {
					$( '#learndash-payment-button-dropdown' ).fadeOut( 'fast' );
					$( '.learndash_checkout_buttons .learndash_checkout_button' ).removeClass( 'jq-dropdown-open' ).blur();
				}
			});

			/* Learndash single lesson/topic/quiz pages - header always sticky */
			/*$(window).on('scroll', function () {
				if( !$('body').hasClass( 'sticky-header' ) ) {
					if( $(window).scrollTop() >= $('#masthead').outerHeight() ) {
						bbHeaderHeight = 0;
					} else {
						bbHeaderHeight = $('#masthead').outerHeight();
					}
					$('.lms-topic-sidebar-data').css({'max-height': 'calc(100vh - '+ ( bbHeaderHeight + adminBarHeight ) +'px', 'top': ( bbHeaderHeight + adminBarHeight ) +'px' });
				}

			});*/

			if ($( '.wpProQuiz_matrixSortString' ).length > 0) {
				$( 'html' ).addClass( 'quiz-sort' );
			}
		},

		setElementorSpacing: function() {
			if ( $( '.elementor-location-header' ).length > 0 ) {
				var setHeight = $( '.elementor-location-header' ).outerHeight();
				$( '.lms-topic-sidebar-wrapper, #learndash-page-content' ).css( {'min-height': 'calc(100vh - ' + setHeight + 'px)'} );
			}
		},

		showQuizNavigation: function() {
			var question_list = $( '.wpProQuiz_list' ).find( '.wpProQuiz_listItem' );
			var nav_wrapper   = $( '#bb-lms-quiz-navigation' );
			if ( question_list.length && nav_wrapper.length ) {
				var str = '<ul>';
				for ( var i = 1; i <= question_list.length; i++ ) {
					str += '<li><a href="#" class="bb-lms-quiz-questions" data-index="' + ( i - 1 ) + '">' + i + '</a></li>';
				}
				str += '</ul>';
				nav_wrapper.html( str );
			}
		},

		StyleInputQuestion: function() {
			function styleInputs() {
				$( '.wpProQuiz_questionInput:not([type="text"]):not([type="email"]):not([type="tel"]):not([type="date"])' ).each(
					function() {
						if ( ! $( this ).hasClass( 'bbstyled' ) ) {
							$( this ).addClass( 'bbstyled' ).after( '<span class="input-style"></span>' );
						}
					}
				);
			}

			styleInputs();

			$( document ).ajaxComplete(
				function() {
					styleInputs();
				}
			);
		},

		singleLesson: function() {
			var lsPageContent = document.getElementById( 'learndash-page-content' );
			if ( lsPageContent ) {
				$( '#learndash-page-content' ).scroll(
					function() {
						$( window ).trigger( 'resize' );
					}
				);
			}
		},

		singleTopic: function() {
			var lsPageContent = $( 'body.single-sfwd-topic .lms-topic-item.current' );
			if ( lsPageContent.length ) {
				lsPageContent.closest( 'div' ).show();
				lsPageContent.parents().closest( 'li' ).removeClass( 'lms-lesson-turnover' );
			}
			// Remove comment system added by shortcodes.
			var learndash_pages = ['.sfwd-lessons-template-default', '.sfwd-topic-template-default', '.sfwd-quiz-template-default'];
			for (var i = 0; i < learndash_pages.length; i++) {
				if ($( learndash_pages[i] + ' #learndash-page-content #learndash-content #learndash-page-content .learndash_content_wrap #comments.comments-area' ).length > 0) {
					$( learndash_pages[i] + ' #learndash-page-content #learndash-content #learndash-page-content .learndash_content_wrap #comments.comments-area' ).remove();
				}
				if ($( learndash_pages[i] + ' #learndash-page-content #learndash-content #learndash-page-content .learndash_content_wrap .ld-focus-comments' ).length > 0) {
					$( learndash_pages[i] + ' #learndash-page-content #learndash-content #learndash-page-content .learndash_content_wrap .ld-focus-comments' ).remove();
				}
			}
		},

		singleQuiz: function() {
			var lsPageContent = $( 'body.single-sfwd-quiz .lms-quiz-item.current' );
			if ( lsPageContent.length ) {
				lsPageContent.closest( 'div' ).show();
				lsPageContent.parents().closest( 'li' ).removeClass( 'lms-lesson-turnover' );
			}
			if ( $( window ).width() > 768 && $( '.wpProQuiz_quiz #bbpress-forums .bs-topic-sidebar-inner' ).length > 0 ) {
				// https://developers.learndash.com/snippet/hook-into-quiz-start-button-logic/
				var triggerElements = [
					'.wpProQuiz_content input.wpProQuiz_button[name="startQuiz"]',
					'.wpProQuiz_content .wpProQuiz_QuestionButton[value="Next"]',
					'.wpProQuiz_content .wpProQuiz_QuestionButton[value="Back"]' ];
				triggerElements.forEach(
					function ( el ) {
						$( el ).click(
							function () {
								setTimeout(
									function () {
										// Trigger scroll to initiate sidebar stick_in_parent script.
										$( window ).trigger( 'scroll' );
									},
									0
								);
							}
						);
					}
				);
			}
		},

		inforBarStatus: function () {
			if ( $( '.ld-course-status-segment' ).length ) {
				$( '.ld-course-status-segment' ).each(
					function () {
						if ( $( this ).find( '.ld-course-status-label' ).text().trim() == '' ) {
							$( this ).find( '.ld-course-status-label' ).addClass( 'no-label' );
						}
					}
				);
			}
		},

	};

	$( document ).ready(
		function () {
			window.BBLMS.init();
		}
	);

} )( jQuery );
