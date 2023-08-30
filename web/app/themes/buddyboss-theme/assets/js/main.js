( function ( $ ) {

	"use strict";

	window.BuddyBossTheme = {
		init: function () {
			this.add_Class();
			this.header_search();
			this.header_notifications();
			this.Menu();
			this.inputStyles();
			this.sidePanel();
			this.bbMasonry();
			// this.bbSlider();
			this.stickySidebars();
			this.bbFitVideo();
			this.LoadMorePosts();
			this.jsSocial();
			this.beforeLogIn();
			this.bbRelatedSlider();
			this.BuddyPanel_Dropdown();
			this.fileUpload();
			this.commentsValidate();
			this.messageScroll();
			if ( $( '.ld-in-focus-mode' ).length <= 0 ) {
				this.ajax_comment();
			}
			this.DropdownToggle();
			this.photoCommentFocus();
			this.bpRegRequired();
			this.inputFileStyle();
			this.primaryNavBar();
			this.forumsTopic();
			this.profileFilterPoint();
			this.heartbeat();
			this.unReadNotifications()
			this.ajaxComplete();
			this.bpNavDropDown();
			this.scrollToAnchor();
		},

		ajaxComplete: function () {
			// When user gets new message then heartbeat send request to refresh notifications.
			$( document ).ajaxComplete( function ( event, xhr, settings ) {
				if ( typeof settings.data !== 'undefined' &&
					typeof settings.data === 'string' &&
					settings.data.indexOf( "action=messages_get_thread_messages" ) > 0 &&
					typeof wp !== 'undefined' &&
					typeof wp.heartbeat !== 'undefined'
				) {
					wp.heartbeat.connectNow();
				}
			} );
		},

		ajax_comment: function () {

			$( document ).on(
				"submit",
				"#commentform",
				function ( e ) {

					e.preventDefault();
					var form = $( "#commentform" );

					var do_comment = $.post( form.attr( "action" ), form.serialize() );

					var ori_btn_val = $( "#commentform" ).find( "[type='submit']" ).val();
					$( "#comment" ).prop( "disabled", true );
					$( "#commentform" ).find( "[type='submit']" ).addClass( 'waiting' );
					$( "#commentform" ).find( "[type='submit']" ).prop( "disabled", true ).val( bs_data.translation.comment_btn_loading );
					do_comment.success(
						function ( data, status, request ) {

							var body = $( "<div></div>" );
							body.append( data );
							var comment_section = "#comments";
							var comments = body.find( comment_section );

							if ( comments.length < 1 ) {
								comment_section = '.comment-list';
								comments = body.find( comment_section );
							}

							var commentslists = comments.find( "li" );

							var new_comment_id = false;

							// catch the new comment id by comparing to old dom.
							commentslists.each(
								function ( index ) {
									var _this = $( commentslists[ index ] );
									if ( $( "#" + _this.attr( "id" ) ).length == 0 ) {
										new_comment_id = _this.attr( "id" );
									}
								}
							);

							$( comment_section ).replaceWith( comments );

							var commentTop = $( "#" + new_comment_id ).offset().top;

							if ( $( 'body' ).hasClass( 'sticky-header' ) ) {
								commentTop = $( "#" + new_comment_id ).offset().top - $( '#masthead' ).height();
							}

							if ( $( 'body' ).hasClass( 'admin-bar' ) ) {
								commentTop = commentTop - $( '#wpadminbar' ).height();
							}

							// scroll to comment
							if ( new_comment_id ) {
								$( "body, html" ).animate(
									{
										scrollTop: commentTop
									},
									600
								);
							}
						}
					);

					do_comment.fail(
						function ( data ) {
							var body = $( "<div></div>" );
							body.append( data.responseText );
							body.find( "style,meta,title,a" ).remove();
							body = body.find( '.wp-die-message p' ).text(); // clean text
							if ( typeof bb_vue_loader == 'object' &&
								typeof bb_vue_loader.common == 'object' &&
								typeof bb_vue_loader.common.showSnackbar != 'undefined' ) {
								bb_vue_loader.common.showSnackbar( body )
							} else {
								alert( body );
							}
						}
					);

					do_comment.always(
						function () {
							$( "#comment" ).prop( "disabled", false );
							$( "#commentform" ).find( "[type='submit']" ).removeClass( 'waiting' );
							$( "#commentform" ).find( "[type='submit']" ).prop( "disabled", false ).val( ori_btn_val );
						}
					);

				}
			);

		},

		add_Class: function () {

			// Page load class.
			if ( document.readyState === 'complete' || document.readyState === 'interactive' ) {
				document.getElementsByTagName( 'body' )[ 0 ].className += ' bb-page-loaded';
			}

			function classToggle( e ) {
				e.preventDefault();
				var elemPanelWrapper = document.querySelector( '.bb-mobile-panel-wrapper' );
				var elemBody = document.body;
				elemPanelWrapper.classList.toggle( 'closed' );
				elemBody.classList.toggle( 'bb-mobile-panel-open' );
			}

			var elemPanel = document.querySelector( '.bb-left-panel-mobile' );

			if ( elemPanel ) {
				elemPanel.addEventListener( 'click', classToggle );
			}

			$( '.bb-close-panel' ).on(
				'click',
				function ( e ) {
					e.preventDefault();
					$( '.bb-mobile-panel-wrapper' ).addClass( 'closed' );
					$( 'body' ).removeClass( 'bb-mobile-panel-open' );
				}
			);

			$( '.bp-template-notice.bp-sitewide-notice' ).insertAfter( '#masthead' );

			// learndash single page issues
			var ldContent = document.getElementById( 'learndash-page-content' );
			if ( ldContent ) {
				$( '.bp-template-notice.bp-sitewide-notice' ).prependTo( '#learndash-page-content' );
			}

			if ( $( '.btn-new-topic' ).attr( 'data-modal-id' ) === 'bbp-topic-form' ) {
				$( 'body' ).addClass( 'forum' ).find( '#bbpress-forums' ).closest( 'article.type-page').addClass( 'type-forum' );
			}

			if ( $( '.bb-sfwd-aside #masthead' ).hasClass( 'elementor-header' ) ) {
				$( '.bb-toggle-panel' ).prependTo( '.ld-course-navigation' );
				//Check if Elementor Header has Learndash theme toggle button and learndash corse sidebar toggle button, add only if not
				if( !$('.site-header--elementor #bb-toggle-theme').length && !$( '.site-header--elementor .course-toggle-view' ).length ) {
					$( '.bb-elementor-header-items' ).prependTo( '.learndash-content-body' );
				}
			}

			if ( $( '.bb-sfwd-aside #masthead' ).hasClass( 'beaver-header' ) ) {
				$( '.bb-toggle-panel' ).prependTo( '.ld-course-navigation' );
				$( '.bb-elementor-header-items' ).prependTo( '.learndash-content-body' );
			}

			if ( $( '.beaver-header > header' ).attr( 'data-sticky' ) === '1' ) {
				$( 'body' ).addClass( 'beaver-sticky-header' );
			}

			$( document ).on(
				'click',
				'#members-list.item-list:not(.grid) li .has_hook_content .more-action-button',
				function ( e ) {
					$( this ).parents( 'li.item-entry' ).toggleClass( 'active' ).siblings( 'li' ).removeClass( 'active' );
					e.preventDefault();
				}
			);

			// Close mobile menu when user clicks on any link
			$( '.bb-mobile-panel-inner .main-navigation a' ).on( 'click', function( e ) {
				if( !$( e.target ).hasClass( 'bs-submenu-toggle' ) && !$( e.target ).parents( '.bb-menu-section').length > 0 ) {
					$( '.bb-mobile-panel-wrapper' ).addClass( 'closed' );
					$( 'body' ).removeClass( 'bb-mobile-panel-open' );
				}
			});
		},

		Menu: function () {
			var $width = 150;
			// $( '#primary-menu' ).BossSocialMenu( $width );
			$( '#activity-sub-nav' ).BossSocialMenu( 90 );
			$( '#object-nav > ul' ).BossSocialMenu( 35 );
			$( '.bb-footer ul.footer-menu' ).BossSocialMenu( 90 );
			$( '.bp-navs ul.component-navigation:not(.search-nav)' ).BossSocialMenu( 35 );
			$( '.bb-profile-grid.bb-grid .bp-navs ul.subnav' ).BossSocialMenu( 90 );
			if ( $( '#object-nav > ul' ).length > 0 ) {
				setTimeout( function () {
					window.dispatchEvent(new Event('resize'));
				}, 0 );
			}
			// $( '.widget_bp_groups_widget #alphabetical-groups' ).after( '<div class="bb-widget-dropdown"><a class="bb-toggle-dropdown"><i class="bb-icon-menu-dots-v"></i></a><div class="bb-dropdown"></div></div>' ).appendTo( '.bb-widget-dropdown .bb-dropdown' );
			$( '.toggle-button' ).panelslider( { bodyClass: 'ps-active', clickClose: true, onOpen: null } );

			$( document ).on(
				'click',
				'.more-button',
				function ( e ) {
					e.preventDefault();
					$( this ).toggleClass( 'active' ).next().toggleClass( 'active' );
				}
			);

			$( document ).on(
				'click',
				'.hideshow .sub-menu a',
				function ( e ) {
					// e.preventDefault();
					$( 'body' ).trigger( 'click' );

					// add 'current' and 'selected' class
					var currentLI = $( this ).parent();
					currentLI.parent( '.sub-menu' ).find( 'li' ).removeClass( 'current selected' );
					currentLI.addClass( 'current selected' );
				}
			);

			$( document ).on(
				'click',
				'.bb-share',
				function ( e ) {
					e.preventDefault();
				}
			);

			$( document ).click(
				function ( e ) {
					var container = $( '.more-button, .sub-menu' );
					if ( !container.is( e.target ) && container.has( e.target ).length === 0 ) {
						$( '.more-button' ).removeClass( 'active' ).next().removeClass( 'active' );
					}
				}
			);

			var headerHeight = $( '#masthead' ).height();
			var headerHeightExt = headerHeight + 55;

			if ( $( window ).width() > 768 ) {
				$( '.site-content-grid > .bb-share-container' ).stick_in_parent( { offset_top: headerHeightExt, spacer: false } );
			}

			var mediaQuery = '( max-width: 768px )';
			var mediaQuerySpot = window.matchMedia( mediaQuery );

			mediaQuerySpot.addEventListener('change', function( event ) {
				if ( event.matches ) {
					$( '.site-content-grid > .bb-share-container' ).trigger( "sticky_kit:detach" );
				} else {
					$( '.site-content-grid > .bb-share-container' ).stick_in_parent( { offset_top: headerHeightExt, spacer: false } );
				}
			} );
			$( '.site-content-grid > .bb-share-container' ).stick_in_parent( { offset_top: headerHeightExt, spacer: false } );

			var $document = $( document ),
				$elementHeader = $( '.sticky-header .site-header' ),
				$elementPanel  = $( '.bb-sfwd-aside .buddypanel' ),
				$LMSlessonsHeader = $( '.single-sfwd-lessons .site-header' ),
				$LMStopicHeader = $( '.single-sfwd-topic .site-header' ),
				className      = 'has-scrolled';

			$document.scroll(
				function () {
					$elementHeader.toggleClass( className, $document.scrollTop() >= 1 );
					$elementPanel.toggleClass( className, $document.scrollTop() >= 5 );
					$LMSlessonsHeader.toggleClass( className, $document.scrollTop() >= 1 );
					$LMStopicHeader.toggleClass( className, $document.scrollTop() >= 1 );
				}
			);

			$( document ).on(
				'click',
				'.header-aside div.menu-item-has-children > a',
				function ( e ) {
					e.preventDefault();
					var current = $( this ).closest( 'div.menu-item-has-children' );
					current.siblings( '.selected' ).removeClass( 'selected' );
					current.toggleClass( 'selected' );
				}
			);

			$( 'body' ).mouseup(
				function ( e ) {
					var container = $( '.header-aside div.menu-item-has-children *' );
					if ( !container.is( e.target ) ) {
						$( '.header-aside div.menu-item-has-children' ).removeClass( 'selected' );
					}
				}
			);
		},

		inputStyles: function () {
			var submitButton = $( '.mc4wp-form-fields input[type="submit"]' );
			submitButton.attr( 'disabled', true );

			$( '.mc4wp-form-fields input[type="email"]' ).keyup(
				function () {
					if ( $( this ).val().length != 0 ) {
						submitButton.attr( 'disabled', false );
					} else {
						submitButton.attr( 'disabled', true );
					}
				}
			);

			function customRegRadio() {
				$( '.bs-bp-container-reg .field-visibility-settings input[type=radio]' ).each(
					function () {
						var $this = $( this );
						$( '<span class="bs-radio"></span>' ).insertAfter( $this );
						$this.addClass( 'bs-radio' );
						if ( $this.is( ':checked' ) ) {
							$this.next( 'span.bs-radio' ).addClass( 'on' );
							$this.closest( 'label' ).addClass( 'on' );
						}

						$this.change(
							function () {
								$this.closest( 'div.radio' ).find( 'span.bs-radio' ).removeClass( 'on' );
								$this.closest( 'div.radio' ).find( 'label' ).removeClass( 'on' );
								$this.next( 'span.bs-radio' ).addClass( 'on' );
								$this.closest( 'label' ).addClass( 'on' );
							}
						);
					}
				);
			}

			customRegRadio();
		},

		header_search: function () {
			// Toggle Search
			$( '.site-header.site-header--bb .header-search-link' ).on(
				'click',
				function ( e ) {
					e.preventDefault();
					$( 'body' ).toggleClass( 'search-visible' );
					if ( !navigator.userAgent.match( /(iPod|iPhone|iPad)/ ) ) {
						setTimeout(
							function () {
								$( 'body' ).find( '.header-search-wrap .search-field-top' ).focus();
							},
							90
						);
					}
				}
			);

			$( '.header-search-wrap .search-field-top' ).focus(
				function () {
					if ( !navigator.userAgent.match( /(iPod|iPhone|iPad)/ ) ) {
						var $input = this;
						setTimeout(
							function () {
								$input.selectionStart = $input.selectionEnd = 10000;
							},
							0
						);
					}
				}
			);

			// Hide Search
			$( '.site-header.site-header--bb .close-search' ).on(
				'click',
				function ( e ) {
					e.preventDefault();
					$( 'body' ).removeClass( 'search-visible' );
					$( '.header-search-wrap input.search-field-top' ).val( '' );
				}
			);

			$( document ).click(
				function ( e ) {
					var container = $( '.header-search-wrap, .header-search-link' );
					if ( !container.is( e.target ) && container.has( e.target ).length === 0 ) {
						$( 'body' ).removeClass( 'search-visible' );
					}
				}
			);

			$( document ).keyup(
				function ( e ) {

					if ( e.keyCode === 27 ) {
						$( 'body' ).removeClass( 'search-visible' );
					}
				}
			);
		},

		unReadNotifications: function () {
			var notification_queue = [];
			$( document ).on(
				"click",
				".action-unread",
				function ( e ) {
					var data = {
						'action': 'buddyboss_theme_unread_notification',
						'notification_id': $( this ).data( 'notification-id' )
					};
					if ( notification_queue.indexOf( $( this ).data( 'notification-id' ) ) !== -1 ) {
						return false;
					}
					notification_queue.push( $( this ).data( 'notification-id' ) );
					var notifs = $( '.bb-icon-bell' );
					var notif_icons = $( notifs ).parent().children( '.count' );
					if ( notif_icons.length > 0 ) {
						if ( $( this ).data( 'notification-id' ) !== 'all' ) {
							notif_icons.html( parseInt( notif_icons.html() ) - 1 );
						} else {
							if ( parseInt( $( '#header-notifications-dropdown-elem ul.notification-list li' ).length ) < 25 ) {
								notif_icons.fadeOut();
							} else {
								notif_icons.html( parseInt( notif_icons.html() ) - parseInt( $( '#header-notifications-dropdown-elem ul.notification-list li' ).length ) );
							}
						}
					}
					if ( $( '.notification-wrap.menu-item-has-children.selected ul.notification-list li' ).length !== 'undefined' && $( '.notification-wrap.menu-item-has-children.selected ul.notification-list li' ).length == 1 || $( this ).data( 'notification-id' ) === 'all' ) {
						$( '#header-notifications-dropdown-elem ul.notification-list' ).html( '<p class="bb-header-loader"><i class="bb-icon-loader animate-spin"></i></p>' );
					}
					if ( $( this ).data( 'notification-id' ) !== 'all' ) {
						$( this ).parent().parent().fadeOut();
						$( this ).parent().parent().remove();
					}
					$.post(
						ajaxurl,
						data,
						function ( response ) {
							var notifs = $( '.bb-icon-bell' );
							var notif_icons = $( notifs ).parent().children( '.count' );
							if ( notification_queue.length === 1 && response.success && typeof response.data !== 'undefined' && typeof response.data.contents !== 'undefined' && $( '#header-notifications-dropdown-elem ul.notification-list' ).length ) {
								$( '#header-notifications-dropdown-elem ul.notification-list' ).html( response.data.contents );
							}
							if ( typeof response.data.total_notifications !== 'undefined' && response.data.total_notifications > 0 && notif_icons.length > 0 ) {
								$( notif_icons ).text( response.data.total_notifications );
								$( '.notification-header .mark-read-all' ).show();
							} else {
								$( notif_icons ).remove();
								$( '.notification-header .mark-read-all' ).fadeOut();
							}
							var index = notification_queue.indexOf( $( this ).data( 'notification-id' ) );
							notification_queue.splice( index, 1 );
						}
					);
				}
			);
		},

		header_notifications: function () {
			if ( $( '#header-notifications-dropdown-elem' ).length ) {
				setTimeout(
					function () {
						$( '#header-notifications-dropdown-elem ul.notification-list' ).html( '<p class="bb-header-loader"><i class="bb-icon-loader animate-spin"></i></p>' );
						$.get(
							ajaxurl,
							{ action: 'buddyboss_theme_get_header_notifications' },
							function ( response, status, e ) {
								if ( response.success && typeof response.data !== 'undefined' && typeof response.data.contents !== 'undefined' && $( '#header-notifications-dropdown-elem ul.notification-list' ).length ) {
									$( '#header-notifications-dropdown-elem ul.notification-list' ).html( response.data.contents );
									if ( typeof response.data.total_notifications !== 'undefined' && response.data.total_notifications > 0 ) {
										$( '.notification-header .mark-read-all' ).fadeIn();
									} else {
										$( '.notification-header .mark-read-all' ).fadeOut();
									}
								}
							}
						);
					},
					3000
				);
			}
			if ( $( '#header-messages-dropdown-elem' ).length ) {
				setTimeout(
					function () {
						$( '#header-messages-dropdown-elem ul.notification-list' ).html( '<p class="bb-header-loader"><i class="bb-icon-loader animate-spin"></i></p>' );
						$.get(
							ajaxurl,
							{ action: 'buddyboss_theme_get_header_unread_messages' },
							function ( response, status, e ) {
								if ( response.success && typeof response.data !== 'undefined' && typeof response.data.contents !== 'undefined' && $( '#header-messages-dropdown-elem ul.notification-list' ).length ) {
									$( '#header-messages-dropdown-elem ul.notification-list' ).html( response.data.contents );
								}
							}
						);
					},
					3000
				);
			}
		},

		sidePanel: function () {
			var status = '';

			// BuddyPanel Menu li height
			function buddyPanelMenuHeight() {
				if ( $( '.buddypanel .side-panel-menu' ).length ) {
					$( '.buddypanel .side-panel-menu li > a' ).each(
						function () {
							var buddyPanelMenuHeight = $( this ).outerHeight();
							if( buddyPanelMenuHeight !== $( this ).parent().outerHeight() ){
								$( this ).parent().css( 'min-height', buddyPanelMenuHeight );
							}
						}
					);
				}
			}

			buddyPanelMenuHeight();

			$( '.bb-toggle-panel' ).on(
				'click',
				function ( e ) {
					e.preventDefault();

					$( 'body' ).addClass( 'buddypanel-transtioned' );

					$( '.buddypanel' ).on(
						'webkitTransitionEnd otransitionend oTransitionEnd msTransitionEnd transitionend',
						function () {
							setTimeout(
								function () {
									$( 'body' ).removeClass( 'buddypanel-transtioned' );
								},
								200
							);
						}
					);

					if ( $( 'body' ).hasClass( 'buddypanel-open' ) ) {
						$( 'body' ).removeClass( 'buddypanel-open' );
						status = 'closed';
					} else {
						$( 'body' ).addClass( 'buddypanel-open' );
						status = 'open';
					}

					setCookie( 'buddypanel', status, 30, '/' );

					if ( $( '.elementor-section-stretched' ).length > 0 ) {
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

					/* Elementor edit mode specific case when 'Stretch Section' option is toggled */
					if ( $( '.elementor-section[style*=width]' ).length > 0 ) {
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

					$( 'side-panel-inner:not(.is_block) .bs-submenu-toggle' ).removeClass( 'bs-submenu-open' );
					$( 'side-panel-inner:not(.is_block) .sub-menu' ).removeClass( 'bb-open' );
				}
			);

			function sidePanelHeight() {
				if ( $( 'body' ).hasClass( 'buddypanel-header' ) || $( 'body' ).hasClass( 'buddypanel-logo' ) ) {
					if ( $( '.buddypanel .site-branding' ).length ) {
						var bbPanelBranding = $( '.buddypanel .site-branding' ).outerHeight();
					} else {
						var bbPanelBranding = 0;
					}
					if ( $( '.buddypanel .panel-head' ).length ) {
						var bbPanelHead = $( '.buddypanel .panel-head' ).outerHeight();
					} else {
						var bbPanelHead = 0;
					}
					if ( $( '.buddypanel .buddypanel-site-icon:visible' ).length ) {
						var bbPanelSiteIcon = $( '.buddypanel .buddypanel-site-icon' ).outerHeight();
					} else {
						var bbPanelSiteIcon = 0;
					}
					$( 'body:not(.buddypanel-open) .side-panel-inner:not(.is_block)' ).css( 'height', '100%' ).css( 'height', '-=' + ( bbPanelHead + bbPanelSiteIcon + 40 ) + 'px' );
					$( 'body.buddypanel-open .side-panel-inner:not(.is_block)' ).css( 'height', '100%' ).css( 'height', '-=' + ( bbPanelBranding + bbPanelHead + 40 ) + 'px' );
				}
			}

			sidePanelHeight();

			$( window ).on(
				'resize',
				function () {
					sidePanelHeight();
				}
			);

			$( '.bb-toggle-panel' ).on(
				'click',
				function ( e ) {
					e.preventDefault();
					sidePanelHeight();

					setTimeout(
						function () {
							sidePanelHeight();
						},
						300
					);
					setTimeout(
						function () {
							sidePanelHeight();
						},
						600
					);
				}
			);

			function buddyPanelScroll(){
				if(window.matchMedia("(pointer: coarse)").matches) {
					$('.side-panel-inner:not(.is_block)').addClass( 'touch-only' );
				} else {
					$('.side-panel-inner:not(.is_block)').removeClass( 'touch-only' );
					$( '.side-panel-inner:not(.is_block)' ).mousewheel(
						function ( event ) {
							event.preventDefault();
							$('.side-panel-inner').removeClass( 'touch-only' );
							var scrollTop = this.scrollTop;
							this.scrollTop = ( scrollTop + ( ( event.deltaY * event.deltaFactor ) * -1 ) );
		
							$( '#buddypanel-menu:not(.buddypanel-menu-block)' ).addClass( 'side-panel-scroll' );
							$( '#buddypanel-menu:not(.buddypanel-menu-block) li a' ).css( 'margin-top', '-' + this.scrollTop + 'px' );
							$( '#buddypanel-menu:not(.buddypanel-menu-block) li a:after', '#buddypanel-menu:not(.buddypanel-menu-block) li a:before' ).css( 'display', 'none' );
		
							clearTimeout( $.data( this, 'scrollTimer' ) );
							$.data(
								this,
								'scrollTimer',
								setTimeout(
									function () {
										$( '#buddypanel-menu:not(.buddypanel-menu-block)' ).removeClass( 'side-panel-scroll' );
									},
									250
								)
							);
						}
					);
	
				}
			};

			buddyPanelScroll();

			$( window ).resize( function(){
				buddyPanelScroll();
			});

			// Add class wrapper if absent
			$( '.sub-menu-inner > li' ).each(
				function () {
					if ( $( this ).find( '.ab-sub-wrapper' ).length !== 0 ) {
						$( this ).addClass( 'parent' );
						$( this ).find( '.ab-sub-wrapper' ).addClass( 'wrapper' );
					}
				}
			);

			// whenever we hover over a menu item that has a submenu
			$( '.user-wrap li.parent, .user-wrap .menu-item-has-children' ).on(
				'mouseover',
				function () {
					var $menuItem = $( this ),
						$submenuWrapper = $( '> .wrapper', $menuItem );

					// grab the menu item's position relative to its positioned parent
					var menuItemPos = $menuItem.position();

					// place the submenu in the correct position relevant to the menu item
					$submenuWrapper.css(
						{
							top: menuItemPos.top
						}
					);
				}
			);

			function setCookie ( key, value, expires, path, domain ) {
				var cookie = key + '=' + escape( value ) + ';';

				if ( expires ) {
					// If it's a date
					if ( expires instanceof Date ) {
						// If it isn't a valid date
						if ( isNaN( expires.getTime() ) ) {
							expires = new Date();
						}
					} else {
						expires = new Date( new Date().getTime() + parseInt( expires ) * 1000 * 60 * 60 * 24 );
					}
					cookie += 'expires=' + expires.toUTCString() + ';';
				}

				if ( path ) {
					cookie += 'path=' + path + ';';
				}
				if ( domain ) {
					cookie += 'domain=' + domain + ';';
				}

				document.cookie = cookie;
			}
		},

		DropdownToggle: function () {
			jQuery( document ).on(
				'click',
				'a.bs-dropdown-link.bb-reply-actions-button',
				function ( e ) {
					e.preventDefault();
					if ( jQuery( this ).hasClass( 'active' ) ) {
						jQuery( this ).removeClass( 'active' ).next( '.bb-reply-actions-dropdown' ).removeClass( 'open' ).closest( 'li' ).removeClass( 'dropdown-open' );
					} else {
						jQuery( 'a.bs-dropdown-link.bb-reply-actions-button' ).removeClass( 'active' ).next( '.bb-reply-actions-dropdown' ).removeClass( 'open' ).closest( 'li' ).removeClass( 'dropdown-open' );
						jQuery( this ).toggleClass( 'active' ).next( '.bb-reply-actions-dropdown' ).toggleClass( 'open' ).closest( 'li' ).toggleClass( 'dropdown-open' );
					}
				}
			);

			$( document ).click(
				function ( e ) {
					var container = $( '.bb-reply-actions-dropdown, a.bs-dropdown-link.bb-reply-actions-button' );
					var forumMoreAction = $( '.forum_single_action_more-wrap' );
					if ( ! container.is( e.target ) && container.has( e.target ).length === 0) {
						$( '.bb-reply-actions-dropdown' ).removeClass( 'open' );
						$( 'a.bs-dropdown-link.bb-reply-actions-button' ).removeClass( 'active' ).closest( 'li' ).removeClass( 'dropdown-open' );
					}
					if ( ! forumMoreAction.is( e.target ) && forumMoreAction.has( e.target ).length === 0) {
						$( '.forum_single_action_wrap.is_visible' ).removeClass( 'is_visible' );
					}
				}
			);

			$( document ).on(
				'click',
				'.bb-reply-actions-dropdown .bbp-reply-to-link',
				function ( e ) {
					$( '.bb-reply-actions-dropdown' ).removeClass( 'open' );
					$( 'a.bs-dropdown-link.bb-reply-actions-button' ).removeClass( 'active' );
				}
			);

			$( document ).on(
				'click',
				'.bs-forums-items .bbp-reply-to-link',
				function ( e ) {
					$( this ).closest( '.bs-reply-list-item' ).addClass( 'in-focus' );
				}
			);
		},

		bbMasonry: function () {
			$( '.bb-masonry' ).css( "visibility", "visible" ).masonry(
				{
					itemSelector: '.bb-masonry .hentry',
					columnWidth: '.bb-masonry-sizer',
				}
			);
		},

		bbSlider: function () {
			// bs_gallery_slider();
		},

		stickySidebars: function () {
			var bbHeaderHeight = $( '#masthead' ).outerHeight(),
				offsetTop = 30;

			if ( $( 'body' ).hasClass( 'sticky-header' ) && $( 'body' ).hasClass( 'admin-bar' ) ) {
				offsetTop = bbHeaderHeight + 62;
			} else if ( $( 'body' ).hasClass( 'sticky-header' ) ) {
				offsetTop = bbHeaderHeight + 30;
			} else if ( $( 'body' ).hasClass( 'admin-bar' ) ) {
				offsetTop = 62;
			}

			if ( $( window ).width() > 1081 ) {
				$( '.bb-sticky-sidebar' ).stick_in_parent( { spacer: false, offset_top: offsetTop } );

				// allow smooth scroll if activity list is not loaded
				if ( $( '.activity #activity-stream #bp-ajax-loader .loading' ).length ) {
					$( '.bb-sticky-sidebar' ).trigger( "sticky_kit:detach" );
				}
				$( '.activity #buddypress' ).on( 'bp_ajax_request', '[data-bp-list="activity"]', function () {
					$( '.bb-sticky-sidebar' ).stick_in_parent( { spacer: false, offset_top: offsetTop } );
				} );

				$( '.bb-sticky-sidebar' ).parent( '#secondary' ).css( 'min-height', $( '.bb-sticky-sidebar' ).height() );
			}

			var mediaQuery = '( max-width: 1081px )';
			var mediaQuerySpot = window.matchMedia( mediaQuery );

			mediaQuerySpot.addEventListener('change', function( event ) {
				if ( event.matches ) {
					$( '.bb-sticky-sidebar' ).trigger( "sticky_kit:detach" );
				} else {
					$( '.bb-sticky-sidebar' ).stick_in_parent( { spacer: false, offset_top: offsetTop } );
				}
			} );

			$( window ).resize( function () {
				$( '.bb-sticky-sidebar' ).parent( '#secondary' ).css( 'min-height', $( '.bb-sticky-sidebar' ).height() );
			} );

			if ( $( '.bb-sticky-sidebar' ).length > 0 ) {
				$( document ).ajaxComplete( function ( event, request, settings ) {
					setTimeout( function () {
						$( document.body ).trigger( 'sticky_kit:recalc' );
					}, 150 );
				} );
			}
		},

		bbFitVideo: function () {

			// Thrive Architect background video fix
			if ( $( '.thrv-page-section .tcb-video-background-el iframe' ).length > 0 ) {
				$( '.thrv-page-section .tcb-video-background-el iframe' ).addClass( 'fitvidsignore' );
			}

			var doFitVids = function () {
				setTimeout(
					function () {
						$( 'iframe[src*="youtube"], iframe[src*="vimeo"]' ).parent().fitVids();
					},
					300
				);
			};
			doFitVids();
			$( document ).ajaxComplete( function () {
				if ( !$( '.elementor-popup-modal .elementor-widget-video' ).length ) {
					doFitVids();
				}
                $( '.elementor-video-container' ).addClass( 'fitvidsignore' );
			} );

			var doFitVidsOnLazyLoad = function ( event, data ) {
				if ( typeof data !== 'undefined' && typeof data.element !== 'undefined' ) {
					// load iframe in correct dimension
					if ( data.element.getAttribute( 'data-lazy-type' ) == 'iframe' ) {
						doFitVids();
					}
				}
			};
			$( document ).on( 'bp_nouveau_lazy_load', doFitVidsOnLazyLoad );
		},

		LoadMorePosts: function () {
			$( document ).on(
				'click',
				'.button-load-more-posts',
				function ( event ) {
					event.preventDefault();

					var self = $( this ),
						href = self.attr( 'href' ),
						container = $( '.post-grid' );

					self.addClass( 'loading' );

					$.get(
						href,
						function ( response ) {
							$( '.pagination-below' ).remove(); // remove old pagination.

							$( response ).find( 'article.status-publish' ).each(
								function ( i, e ) {

									var elem = $( e );

									if ( container.hasClass( 'bb-masonry' ) ) {
										container.append( elem ).masonry( 'appended', elem ).masonry();
									} else {
										container.append( elem );
									}

								}
							);

							$( '.post-grid' ).after( $( response ).find( '.pagination-below' ) );

							if ( $( '.post-grid' ).hasClass( 'bb-masonry' ) ) {
								$( '.bb-masonry' ).masonry( {} );
							}

							// scripts to execute?
							var $script_tags = $( response ).filter( 'script.bb_bookmarks_bootstrap' );
							if ( $script_tags.length > 0 ) {
								$script_tags.each(
									function () {
										$( 'body' ).append( $( this ) );
									}
								);
							}

							// setTimeout(function () {
							// bs_gallery_slider();
							// }, 600);
						}
					);
				}
			);

			$( document ).on(
				'scroll',
				function () {
					var load_more_posts = $( '.post-infinite-scroll' );
					if ( load_more_posts.length ) {
						var pos = load_more_posts.offset();
						if ( $( window ).scrollTop() + $( window ).height() > pos.top ) {
							if ( !load_more_posts.hasClass( 'loading' ) ) {
								load_more_posts.trigger( 'click' );
							}
						}
					}
				}
			);
		},

		/**
		 * Generates the sample for loader purpose for post grids.
		 */
		postGridLoader: function () {

			var loading = $( 'article.type-post' ).not( ".first" ).first().clone();

			// remove not needed elements.
			loading.removeClass( "format-quote" );
			loading.find( ".entry-meta" ).remove();
			loading.find( ".mejs-offscreen" ).remove();
			loading.find( ".mejs-container" ).remove();
			loading.find( "img" ).remove();
			loading.find( ".post-format-icon" ).remove();
			loading.find( ".post-main-link" ).remove();
			loading.find( ".bb-gallery-slider" ).replaceWith( '<a href="" class="entry-media entry-img"></a>' );

			if ( !loading.find( ".entry-img" ).length ) {
				loading.prepend( '<a href="" class="entry-media entry-img"></a>' );
			}

			// Append Dummy Data,

			var spaces = '';
			for ( var i = 0; i <= 60; i++ ) {
				spaces += '&nbsp; ';
			}

			loading.find( '.entry-content' ).html( "<span>" + spaces + "</span>" );

			spaces = '';
			for ( var i = 0; i <= 20; i++ ) {
				spaces += '&nbsp; ';
			}

			loading.find( '.entry-title > a' ).html( spaces );

			// add loading class

			loading.addClass( "loading" );

			return loading;

		},

		jsSocial: function () {
			$( '.bb-shareIcons' ).jsSocials(
				{
					showLabel: true,
					showCount: false,
					shares: [
						{ share: "facebook", label: bs_data.facebook_label },
						{ share: "twitter", label: bs_data.twitter_label },
					]
				}
			);

			$( '.jssocials-share-link' ).each(
				function () {
					$( this ).attr( 'data-balloon-pos', 'right' );
					$( this ).attr( 'data-balloon', $( this ).find( '.jssocials-share-label' ).html() );
				}
			);

			$( '.post-related-posts' ).find( 'a[data-balloon-pos]' ).attr( 'data-balloon-pos', 'left' );
		},

		beforeLogIn: function () {
			var $loginUserName = '#bp-login-widget-user-login';
			var $loginUserPass = '#bp-login-widget-user-pass';
			var $loginUserBtn = $( '#bp-login-widget-submit' );

			function checkLogIn() {
				var empty = false;
				$( $loginUserName + ',' + $loginUserPass ).each(
					function () {
						if ( $( this ).val() == '' ) {
							empty = true;
						}
					}
				);

				if ( empty ) {
					$loginUserBtn.removeClass( 'bp-login-btn-active' );
				} else {
					$loginUserBtn.addClass( 'bp-login-btn-active' );
				}
			}

			checkLogIn();

			$( $loginUserName + ', ' + $loginUserPass ).keyup(
				function () {
					checkLogIn();
				}
			);

			setTimeout(
				function () {
					$( '#bp-login-widget-user-pass' ).each(
						function ( i, element ) {
							var el = $( this );

							if ( el.is( "*:-webkit-autofill" ) ) {
								$loginUserBtn.addClass( 'bp-login-btn-active' );
							}
						}
					);
				},
				200
			);

			var $bbpLoginUserName = '.bbp-login-form #user_login';
			var $bbpLoginUserPass = '.bbp-login-form #user_pass';
			var $bbpLoginUserBtn = $( '.bbp-login-form #user-submit' );

			function checkbbpLogIn() {
				var empty = false;
				$( $bbpLoginUserName + ',' + $bbpLoginUserPass ).each(
					function () {
						if ( $( this ).val() == '' ) {
							empty = true;
						}
					}
				);

				if ( empty ) {
					$bbpLoginUserBtn.removeClass( 'bp-login-btn-active' );
				} else {
					$bbpLoginUserBtn.addClass( 'bp-login-btn-active' );
				}
			}

			checkbbpLogIn();

			$( $bbpLoginUserName + ', ' + $bbpLoginUserPass ).keyup(
				function () {
					checkbbpLogIn();
				}
			);

			$( 'form.bbp-login-form label[for="user_pass"]' ).append( "<span class='label-switch'></span>" );

			$( document ).on(
				'click',
				'form.bbp-login-form .label-switch',
				function ( e ) {
					var $this = $( this );
					var $input = $this.closest( '.bbp-password' ).find( 'input#user_pass' );
					$this.toggleClass( "bb-eye" );
					if ( $this.hasClass( 'bb-eye' ) ) {
						$input.attr( "type", "text" );
					} else {
						$input.attr( "type", "password" );
					}
				}
			);

			$( 'form#bp-login-widget-form label[for="bp-login-widget-user-pass"]' ).append( "<span class='label-switch'></span>" );

			$( document ).on(
				'click',
				'form#bp-login-widget-form .label-switch',
				function ( e ) {
					var $this = $( this );
					var $input = $this.closest( 'form' ).find( 'input#bp-login-widget-user-pass' );
					$this.toggleClass( "bb-eye" );
					if ( $this.hasClass( 'bb-eye' ) ) {
						$input.attr( "type", "text" );
					} else {
						$input.attr( "type", "password" );
					}
				}
			);
		},

		bbRelatedSlider: function () {
			if ( $( 'body' ).hasClass( 'has-sidebar' ) ) {
				var $break = 900;
			} else {
				var $break = 544;
			}

			function runSlickRelated() {
				var slickRelated = {
					infinite: false,
					slidesToShow: 2,
					slidesToScroll: 2,
					adaptiveHeight: true,
					arrows: true,
					prevArrow: '<a class="bb-slide-prev"><i class="bb-icon-l bb-icon-angle-right"></i></a>',
					nextArrow: '<a class="bb-slide-next"><i class="bb-icon-l bb-icon-angle-right"></i></a>',
					appendArrows: '.post-related-posts h3',
					responsive: [
						{
							breakpoint: $break,
							settings: {
								slidesToShow: 1,
								slidesToScroll: 1,
							}
						}
					]
				}

				$( '.post-related-posts .post-grid' ).not( '.slick-initialized' ).slick( slickRelated );
			}

			function slickGalleryReinit() {
				$( '.post-related-posts .slick-slider' ).slick( 'reinit' );
				/*$( '.slick-slider' ).on( 'reInit', function ( event, slick ) {
				 $( '.slick-slider' ).slick( 'slickSetOption', { arrows: false, dots: false } );
				 } );*/
				$( '.post-related-posts .slick-slider' ).slick( 'resize' );
				$( '.post-related-posts .slick-slider' ).slick( 'refresh' );
			}

			runSlickRelated();

			// slickGalleryReinit();

			$( window ).on(
				'resize',
				function () {
					runSlickRelated();
					slickGalleryReinit();
				}
			);
		},

		BuddyPanel_Dropdown: function () {
			$( '.buddypanel-menu .sub-menu' ).each(
				function () {
					$( this ).closest( 'li.menu-item-has-children' ).find( 'a:first' ).append( '<i class="bb-icon-l bb-icon-angle-down bs-submenu-toggle"></i>' );
				}
			);

			$( document ).on(
				'click',
				'.bs-submenu-toggle',
				function ( e ) {
					e.preventDefault();
					$( this ).toggleClass( 'bs-submenu-open' ).closest( 'a' ).next( '.sub-menu' ).toggleClass( 'bb-open' );
					$( this ).parent( '.menu-item-has-children' ).toggleClass( 'bb-open-parent' );
				}
			);
			/**
			 * when we select sub menu it will be expand sub menu when page load
			 */

			$( 'aside.buddypanel .buddypanel-menu .current-menu-parent' ).find( 'ul.sub-menu' ).addClass( 'bb-open' );

			var currentMenu = $( '.bb-mobile-panel-inner .menu-item-has-children.current-menu-ancestor, .bb-mobile-panel-inner .menu-item-has-children.current_page_item, .bb-mobile-panel-inner .menu-item-has-children.current-menu-item, .bb-mobile-panel-inner .menu-item-has-children.current-menu-parent' );
			currentMenu.children( 'ul.sub-menu' ).addClass( 'bb-open' );
			currentMenu.children( 'ul.sub-menu' ).prev().children( '.bs-submenu-toggle' ).addClass( 'bs-submenu-open' );
		},

		fileUpload: function () {
			$( '.job-manager-form fieldset input[type=file], .ginput_container_fileupload > input[type=file], .ginput_container_post_image input[type=file]' ).each(
				function () {
					var $fileInput = $( this );
					var $fileInputFor = $fileInput.attr( 'id' );
					$fileInput.after( '<label for="' + $fileInputFor + '">' + bs_data.translation.choose_a_file_label + '</label>' );
				}
			);

			$( '.job-manager-form fieldset input[type=file], .ginput_container_fileupload > input[type=file], .ginput_container_post_image input[type=file]' ).change(
				function ( e ) {
					var $in = $( this );
					var $inval = $in.next().html( $in.val() );
					if ( $in.val().length === 0 ) {
						$in.next().html( bs_data.translation.choose_a_file_label );
					} else {
						$in.next().html( $in.val().replace( /C:\\fakepath\\/i, '' ) );
					}
				}
			);
		},

		commentsValidate: function () {
			function resetForm() {
				$( '#commentform' ).reset();
				validator.resetForm();
			}

			function validateForm() {
				if ( validator.form() ) {
					$( '#commentform' ).submit();
				}
			}

			jQuery.validator.addMethod(
				'BBEmail',
				function ( value, element ) {
					return this.optional(element) || /^([a-zA-Z0-9!#$%&\'*+\/=?^_`{|}~\.-])+\@(([a-zA-Z0-9-])+\.)+([a-zA-Z0-9]{2,4})+$/.test(value);
				},
				bs_data.translation.email_validation
			);

			var validator = $( "#commentform" ).validate(
				{
					rules: {
						author: {
							required: true,
							normalizer: function ( value ) {
								return $.trim( value );
							}
						},
						email: {
							required: true,
							BBEmail: true
						},
						url: {
							url: true
						},
						comment: {
							required: true,
							normalizer: function ( value ) {
								return $.trim( value );
							}
						}

					},
					messages: {
						author: "Please enter your name",
						email: {
							required: "Please enter an email address",
							email: "Please enter a valid email address"
						},
						url: "Please enter a valid URL e.g. http://www.mysite.com",
						comment: "Please fill the required field"
					},
					errorElement: "div",
					errorPlacement: function ( error, element ) {
						element.after( error );
					}
				}
			);

			$( '#comment' ).focus(
				function () {
					$( this ).parents( '#respond' ).addClass( 'bb-active' );
				}
			);

			$( '#comment' ).blur(
				function () {
					$( this ).parents( '#respond' ).removeClass( 'bb-active' );
				}
			);
		},

		messageScroll: function () {

		},

		photoCommentFocus: function () {
			$( document ).on(
				'click',
				'.bb-media-model-wrapper .bs-comment-textarea',
				function ( e ) {
					e.preventDefault();

					$( '.bb-media-model-wrapper' ).animate(
						{
							scrollTop: $( '.bb-media-model-wrapper' )[ 0 ].scrollHeight
						},
						"slow"
					);
				}
			);
		},

		bpRegRequired: function () {
			$( '.bs-bp-container-reg .signup-form input' ).removeAttr( 'required' );
		},

		inputFileStyle: function () {
			var inputs = document.querySelectorAll( '.bb-inputfile' );
			Array.prototype.forEach.call(
				inputs,
				function ( input ) {
					var label = input.nextElementSibling,
						labelVal = label.innerHTML;

					input.addEventListener(
						'change',
						function ( e ) {
							var fileName = '';
							if ( this.files && this.files.length > 1 ) {
								fileName = ( this.getAttribute( 'data-multiple-caption' ) || '' ).replace( '{count}', this.files.length );
							} else {
								fileName = e.target.value.split( '\\' ).pop();
							}

							if ( fileName ) {
								label.querySelector( 'span' ).innerHTML = fileName;
							} else {
								label.innerHTML = labelVal;
							}
						}
					);
				}
			);
		},

		primaryNavBar: function () {
			/*
			* Allow use of Array.from in implementations that don't natively support it
			function conNavArray(arr) { if (Array.isArray(arr)) { for (var i = 0, arr2 = Array(arr.length); i < arr.length; i++) { arr2[i] = arr[i]; } return arr2; } else { return Array.from(arr); } }
			*/

			function conNavArray( arr ) {
				if ( Array.isArray( arr ) ) {
					for ( var i = 0, arr2 = Array( arr.length ); i < arr.length; i++ ) {
						arr2[ i ] = arr[ i ];
					}
					return arr2;
				} else {
					return [].slice.call( arr );
				}
			}

			var primaryWrap = document.getElementById( 'primary-navbar' ),
				primaryNav = document.getElementById( 'primary-menu' ),
				extendNav = document.getElementById( 'navbar-extend' ),
				navCollapse = document.getElementById( 'navbar-collapse' );

			function navListOrder() {
				var eChildren = extendNav.children;
				var numW = 0;

				[].concat( conNavArray( eChildren ) ).forEach(
					function ( item ) {
						item.outHTML = '';
						primaryNav.appendChild( item );
					}
				);

				var primaryWrapWidth = primaryWrap.offsetWidth,
					navCollapseWidth = navCollapse.offsetWidth + 30,
					primaryWrapCalc = primaryWrapWidth - navCollapseWidth,
					primaryNavWidth = primaryNav.offsetWidth,
					pChildren = primaryNav.children;

				[].concat( conNavArray( pChildren ) ).forEach(
					function ( item ) {
						numW += item.offsetWidth + ( $( 'body' ).hasClass( 'header-style-2' ) ? 0 : 5 );

						if ( numW > primaryWrapCalc ) {
							item.outHTML = '';
							extendNav.appendChild( item );
						}

					}
				);

				if ( extendNav.getElementsByTagName( 'li' ).length >= 1 ) {
					navCollapse.classList.add( 'hasItems' );
				} else {
					navCollapse.classList.remove( 'hasItems' );
				}

				primaryNav.classList.remove( 'bb-primary-overflow' );
			}

			if ( typeof ( primaryNav ) != 'undefined' && primaryNav != null ) {
				window.onresize = navListOrder;
				navListOrder();

				setTimeout(
					function () {
						navListOrder();
					},
					300
				);
				setTimeout(
					function () {
						navListOrder();
					},
					900
				);

				$( '.bb-toggle-panel' ).on(
					'click',
					function ( e ) {
						e.preventDefault();
						navListOrder();

						setTimeout(
							function () {
								navListOrder();
								if ( $( '.post-grid' ).hasClass( 'bb-masonry' ) ) {
									$( '.bb-masonry' ).masonry( {} );
								}
							},
							300
						);

						setTimeout(
							function () {
								navListOrder();
							},
							600
						);
					}
				);
			}
		},

		forumsTopic: function () {
			var bbHeaderHeight = $( '#masthead' ).outerHeight();

			if ( $( window ).width() > 768 ) {
				$( '#bbpress-forums .bs-topic-sidebar-inner' ).stick_in_parent( { offset_top: bbHeaderHeight + 45, spacer: false, bottoming: true } );
			}

			var mediaQuery = '( max-width: 768px )';
			var mediaQuerySpot = window.matchMedia( mediaQuery );

			mediaQuerySpot.addEventListener('change', function( event ) {
				if ( event.matches ) {
					$( '#bbpress-forums .bs-topic-sidebar-inner' ).trigger( "sticky_kit:detach" );
				} else {
					$( '#bbpress-forums .bs-topic-sidebar-inner' ).stick_in_parent( { offset_top: bbHeaderHeight + 45, spacer: false, bottoming: false } );
				}
			} );

			if ( $( 'body .bbp-topic-form' ).length ) {

				$( document ).on(
					'keyup',
					'.bbp-topic-form #new-post #bbp_topic_title',
					function ( e ) {
						if ( e.which == 9 && !e.shiftKey ) {
							e.preventDefault();
							$( e.target ).closest( '.bbp-topic-form #new-post .bbp-editor-content' ).focus();
						}
					}
				);

				$( document ).on(
					'keyup',
					'.bbp-topic-form #new-post .bbp-editor-content',
					function ( e ) {
						if ( e.which == 9 && e.shiftKey ) {
							e.preventDefault();
							$( e.target ).closest( '.bbp-topic-form #new-post #bbp_topic_title' ).focus();
						}
					}
				);

				$( document ).on(
					'keyup',
					'.bbp-topic-form #new-post #bbp_topic_tags',
					function ( e ) {
						if ( e.which == 9 && e.shiftKey ) {
							e.preventDefault();
							$( e.target ).closest( '.bbp-topic-form #new-post #bbp_editor_topic_content' ).focus();
						}
					}
				);
			}

			if ( $( 'body .bbp-reply-form' ).length ) {

				$( document ).on(
					'keyup',
					'.bbp-reply-form #new-post #bbp_topic_tags',
					function ( e ) {
						if ( e.which == 9 && e.shiftKey ) {
							e.preventDefault();
							$( e.target ).closest( '.bbp-reply-form #new-post .bbp-editor-content' ).focus();
						}
					}
				);
			}

			var appendthis = ( '<div class="bb-modal-overlay js-modal-close"></div>' );

			$( document ).on(
				'click',
				'a[data-modal-id]',
				function ( e ) {
					e.preventDefault();
					$( 'body' ).addClass( 'bb-modal-overlay-open' ).append( appendthis );
					$( '.bb-modal-overlay' ).fadeTo( 0, 1 );
					// $(".js-modalbox").fadeIn(500);
					var $bbpress_forums_element = $( e.target ).closest( '.bb-grid' );
					var modalBox                = $( this ).attr( 'data-modal-id' );

					if ( 1 === $( 'body.single-forum').length && $( e.target ).parent().hasClass( 'bbpress-sidebar' ) ) {

						//Main Standalone forum page with new discussion button in sidebar.
						$bbpress_forums_element = $bbpress_forums_element.find( '#bbpress-forums' );
					} else if ( 0 < $bbpress_forums_element.find( '#bbpress-forums' ).length ) {
						$bbpress_forums_element = $( e.target ).closest( '#bbpress-forums' );
					}

					$bbpress_forums_element.find( '.' + modalBox ).fadeIn( 0 ).addClass( 'bb-modal-open' );

					if ( $bbpress_forums_element.find( '.bbp-reply-form' ).length ) {
						$bbpress_forums_element.find( '.bbp-reply-form' ).find( '#bbp_reply_to' ).val( 0 );
						$bbpress_forums_element.find( '.bbp-reply-form' ).find( '#bbp-reply-to-user' ).html( $bbpress_forums_element.find( $bbpress_forums_element.find( '.topic-lead' ).find( '.item-meta' ).get( 0 ) ).find( '.bbp-author-name' ).text() );

						var topic_id     = $bbpress_forums_element.find( '#bbp_topic_id' ).val();
						var reply_exerpt = $( '#topic-' + topic_id + '-replies li.bs-item-wrap #bbp_topic_excerpt' ).val();

						if ( '' != reply_exerpt ) {
							reply_exerpt = reply_exerpt + '...';
							$bbpress_forums_element.find( '.bbp-reply-form' ).find( '#bbp-reply-exerpt' ).text( reply_exerpt );
						}

						var editor_key = $bbpress_forums_element.find( '.bbp-the-content' ).data( 'key' );

						if ( typeof window.forums_medium_reply_editor !== 'undefined' && typeof window.forums_medium_reply_editor[ editor_key ] !== 'undefined' ) {
							window.forums_medium_reply_editor[ editor_key ].subscribe(
								'editableInput',
								function () {
									if ( $.trim( window.forums_medium_reply_editor[ editor_key ].getContent() ).replace( '<p><br></p>', '' ) != '' ) {
										$bbpress_forums_element.find( '.bbp-the-content' ).removeClass( 'error' );
									} else {
										$bbpress_forums_element.find( '.bbp-the-content' ).addClass( 'error' );
									}
								}
							);
						}
					}

					if ( typeof window.forums_medium_topic_editor !== 'undefined' && typeof window.forums_medium_topic_editor[ editor_key ] !== 'undefined' ) {
						window.forums_medium_topic_editor[ editor_key ].subscribe(
							'editableInput',
							function () {
								if ( $.trim( window.forums_medium_topic_editor[ editor_key ].getContent() ).replace( '<p><br></p>', '' ) != '' ) {
									$bbpress_forums_element.find( '.bbp-the-content' ).removeClass( 'error' );
								} else {
									$bbpress_forums_element.find( '.bbp-the-content' ).addClass( 'error' );
								}
							}
						);
					}

					$bbpress_forums_element.find( '.bbp-topic-form' ).trigger(
						'bbp_after_load_topic_form',
						{
							click_event: this,
						}
					);

					$bbpress_forums_element.find( '.bbp-reply-form' ).trigger(
						'bbp_after_load_reply_form',
						{
							click_event: this,
						}
					);
				}
			);

			$( document ).on(
				'click',
				'a[data-modal-id-inline]',
				function ( e ) {
					if ( $(this).closest('.bs-single-forum-list').hasClass('bb-single') ) {
						return;
					}

					e.preventDefault();
					$( 'body' ).addClass( 'bb-modal-overlay-open' ).append( appendthis );
					$( '.bb-modal-overlay' ).fadeTo( 0, 1 );
					// $(".js-modalbox").fadeIn(500);
					var modalBox = $( this ).attr( 'data-modal-id-inline' );
					$( '#' + modalBox ).fadeIn( 0 ).addClass( 'bb-modal-open' );

					var $bbpress_forums_element = $( e.target ).closest( '.bb-grid' );

					if ( $bbpress_forums_element.find( '.bbp-reply-form' ).length ) {

						// Add author name.
						$bbpress_forums_element.find( '.bbp-reply-form' ).find( '#bbp-reply-to-user' ).html( $( $( this ).closest( 'li' ).find( '.bs-reply-list-item' ).get( 0 ) ).find( '.item-meta' ).find( '.bbp-author-name' ).text() );

						var reply_to = $( this ).closest( '.bs-reply-list-item' ).find( '.bbp-author-name' ).text().trim();
						var reply_exerpt = $( this ).closest( '.bs-reply-list-item' ).find( '>.bbp-reply-content' ).text().trim().substring( 0, 50 );
						if ( $( this ).closest( '.bs-reply-list-item' ).find( '>.bbp-reply-content .bb-activity-media-elem' ).length ) {
							var clickToDownloadText = $( this ).closest( '.bs-reply-list-item' ).find( '>.bbp-reply-content .bb-activity-media-elem .document-helper-text' )[ 0 ];
							clickToDownloadText = $( clickToDownloadText ).text();
							if ( '' !== clickToDownloadText ) {
								reply_exerpt = $( this ).closest( '.bs-reply-list-item' ).find( '>.bbp-reply-content' ).text().trim().replace(new RegExp(clickToDownloadText, 'g'), '').substring( 0, 50 ).trim();
							}
							var mediaFileText = $( this ).closest( '.bs-reply-list-item' ).find( '>.bbp-reply-content .bb-activity-media-elem' ).not('.document-activity')[0];
							mediaFileText = $( mediaFileText ).text().trim();
							if ( '' !== mediaFileText ) {
								reply_exerpt = $( this ).closest( '.bs-reply-list-item' ).find( '>.bbp-reply-content' ).text().trim().replace(new RegExp(mediaFileText, 'g'), '').substring( 0, 50 ).trim();
							}
						}
						if ( $( this ).closest( '.bs-reply-list-item' ).find( '>.bbp-reply-content .bb-activity-media-wrap .bp-video-thumbnail-uploader' ).length ) {
							var videoFileText = $( this ).closest( '.bs-reply-list-item' ).find( '>.bbp-reply-content .bb-activity-media-wrap' )[ 0 ];
							videoFileText = $( videoFileText ).text().trim();
							if ( '' !== videoFileText ) {
								reply_exerpt = $( this ).closest( '.bs-reply-list-item' ).find( '>.bbp-reply-content' ).text().trim().replace(new RegExp(videoFileText, 'g'), '').substring( 0, 50 ).trim();
							}
						}
						if( $( this ).closest( '.bs-reply-list-item' ).find( '>.bbp-reply-content .bbp-reply-revision-log' ).length ) {
							var revisionText  = $( this ).closest( '.bs-reply-list-item' ).find( '>.bbp-reply-content .bbp-reply-revision-log' ).text().trim();
							reply_exerpt = $( this ).closest( '.bs-reply-list-item' ).find( '>.bbp-reply-content' ).text().trim().replace(new RegExp(revisionText, 'g'), '').substring( 0, 50 ).trim();
						}
						if( '' !== reply_to ) {
							$bbpress_forums_element.find( '.bbp-reply-form' ).find( '#bbp-reply-to-user' ).text( reply_to );
						}
						var excerpt_block = $bbpress_forums_element.find( '.bbp-reply-form' ).find( '#bbp-reply-exerpt' );
						if ( '' !== reply_exerpt ) {
							excerpt_block.removeClass( 'show-empty' );
							reply_exerpt = reply_exerpt + '...';
						} else {
							excerpt_block.addClass( 'show-empty' );
						}
						excerpt_block.text( reply_exerpt );
						var editor_key = $bbpress_forums_element.find( '.bbp-the-content' ).data( 'key' );
						if ( typeof window.forums_medium_reply_editor !== 'undefined' && typeof window.forums_medium_reply_editor[ editor_key ] !== 'undefined' ) {
							window.forums_medium_reply_editor[ editor_key ].subscribe(
								'editableInput',
								function () {
									if ( $.trim( window.forums_medium_reply_editor[ editor_key ].getContent() ).replace( '<p><br></p>', '' ) != '' ) {
										$bbpress_forums_element.find( '.bbp-the-content' ).removeClass( 'error' );
									} else {
										$bbpress_forums_element.find( '.bbp-the-content' ).addClass( 'error' );
									}
								}
							);
						}
					}

					$bbpress_forums_element.find( '.bbp-reply-form' ).trigger(
						'bbp_after_load_inline_reply_form',
						{
							click_event: this,
						}
					);
				}
			);

			$( document ).on(
				'click',
				'.js-modal-close',
				function ( e ) {
					e.preventDefault();
					$( '.bb-modal-box, .bb-modal-overlay' ).fadeOut(
						50,
						function () {
							$( '.bb-modal-overlay' ).remove();
						}
					);
					$( 'body' ).removeClass( 'popup-modal-reply bb-modal-overlay-open' );
					$( '.bs-reply-list-item.in-focus' ).removeClass( 'in-focus' );
					$( '.bb-modal-box' ).removeClass( 'bb-modal-open' );

					$( this ).trigger(
						'bbp_after_close_topic_reply_form',
						{
							click_event: this,
						}
					);
				}
			);

			$( document ).on(
				'click',
				'.bb-modal-overlay',
				function ( e ) {
					e.preventDefault();
					$( 'body' ).removeClass( 'bb-modal-overlay-open' );
					$( '.bb-modal-box, .bb-modal-overlay' ).fadeOut(
						50,
						function () {
							$( '.bb-modal-overlay' ).remove();
						}
					);
					$( '.bb-modal-box' ).removeClass( 'bb-modal-open' );

					$( document ).trigger(
						'bbp_after_close_topic_reply_form_on_overlay',
						{
							click_event: this,
						}
					);
				}
			);

			if ( bs_getUrlParameter( 'bbp_reply_to' ) ) {
				if ( bs_getUrlParameter( 'bbp_reply_to' ) ) {
					if ( parseInt( bs_getUrlParameter( 'bbp_reply_to' ) ) > 0 && $( document ).find( '.bbp-reply-to-link.' + bs_getUrlParameter( 'bbp_reply_to' ) ).length ) {
						$( window ).load( function () {
							$( '.bbp-reply-to-link.' + bs_getUrlParameter( 'bbp_reply_to' ) ).trigger( 'click' );
						} );
					} else {
						$( '.bbp-topic-reply-link' ).trigger( 'click' );
					}
				}
			}

			if ( $( '.bbp-topic-form form' ).length && $( '.bbp-topic-form form' ).find( '.bp-feedback.error' ).length ) {
				$( '.btn-new-topic' ).trigger( 'click' );
			}

			$( '.bbp-topic-form form' ).on(
				'keyup',
				'#bbp_topic_title,#bbp_anonymous_author,#bbp_anonymous_email',
				function ( e ) {
					e.preventDefault();
					if ( $.trim( $( this ).val() ) === '' ) {
						$( this ).addClass( 'error' );
					} else {
						$( this ).removeClass( 'error' );
					}
				}
			);

			$( document ).on(
				'click',
				'.bbp-topic-form form #bbp_topic_submit',
				function ( e ) {
					e.preventDefault();
					var $topicForm = $( e.target ).closest( 'form' );
					if ( $topicForm.hasClass( 'submitting' ) ) {
						return false;
					}
					$topicForm.addClass( 'submitting' );
					var valid       = true;
					var media_valid = true;

					if ( $topicForm.find( '.bbp-form-anonymous' ).length ) {
						if ( $.trim( $topicForm.find( '#bbp_anonymous_author' ).val() ) === '' ) {
							$topicForm.find( '#bbp_anonymous_author' ).addClass( 'error' );
							valid = false;
						} else {
							$topicForm.find( '#bbp_anonymous_author' ).removeClass( 'error' );
						}

						if ( $.trim( $topicForm.find( '#bbp_anonymous_email' ).val() ) === '' ) {
							$topicForm.find( '#bbp_anonymous_email' ).addClass( 'error' );
							valid = false;
						} else {
							$topicForm.find( '#bbp_anonymous_email' ).removeClass( 'error' );
						}
					}

					if ( $.trim( $topicForm.find( '#bbp_topic_title' ).val() ) === '' ) {
						$topicForm.find( '#bbp_topic_title' ).addClass( 'error' );
						valid = false;
					} else {
						$topicForm.find( '#bbp_topic_title' ).removeClass( 'error' );
					}

					var editor_key = $topicForm.find( '.bbp_editor_topic_content' ).data( 'key' );

					var editor = false;
					if ( typeof window.forums_medium_topic_editor !== 'undefined' && typeof window.forums_medium_topic_editor[ editor_key ] !== 'undefined' ) {
						editor = window.forums_medium_topic_editor[ editor_key ];
					}

					if (
						(
							$topicForm.find( '#bbp_media' ).length > 0
							&& $topicForm.find( '#bbp_document' ).length <= 0
							&& $topicForm.find( '#bbp_video' ).length <= 0
							&& $topicForm.find( '#bbp_media_gif' ).length > 0
							&& $topicForm.find( '#bbp_media' ).val() == ''
							&& $topicForm.find( '#bbp_media_gif' ).val() == ''
						)
						|| (
							$topicForm.find( '#bbp_document' ).length > 0
							&& $topicForm.find( '#bbp_media' ).length <= 0
							&& $topicForm.find( '#bbp_video' ).length <= 0
							&& $topicForm.find( '#bbp_media_gif' ).length > 0
							&& $topicForm.find( '#bbp_document' ).val() == ''
							&& $topicForm.find( '#bbp_media_gif' ).val() == ''
						)
						|| (
							$topicForm.find( '#bbp_video' ).length > 0
							&& $topicForm.find( '#bbp_media' ).length <= 0
							&& $topicForm.find( '#bbp_document' ).length <= 0
							&& $topicForm.find( '#bbp_media_gif' ).length > 0
							&& $topicForm.find( '#bbp_video' ).val() == ''
							&& $topicForm.find( '#bbp_media_gif' ).val() == ''
						)
						|| (
							$topicForm.find( '#bbp_document' ).length > 0
							&& $topicForm.find( '#bbp_media_gif' ).length <= 0
							&& $topicForm.find( '#bbp_document' ).val() == ''
						)
						|| (
							$topicForm.find( '#bbp_media' ).length > 0
							&& $topicForm.find( '#bbp_media_gif' ).length <= 0
							&& $topicForm.find( '#bbp_media' ).val() == ''
						)
						|| (
							$topicForm.find( '#bbp_video' ).length > 0
							&& $topicForm.find( '#bbp_media_gif' ).length <= 0
							&& $topicForm.find( '#bbp_video' ).val() == ''
						)
						|| (
							$topicForm.find( '#bbp_media_gif' ).length > 0
							&& $topicForm.find( '#bbp_media' ).length <= 0
							&& $topicForm.find( '#bbp_document' ).length <= 0
							&& $topicForm.find( '#bbp_video' ).length <= 0
							&& $topicForm.find( '#bbp_media_gif' ).val() == ''
						)
					) {
						media_valid = false;
					}

					var link_preview_url = '';
					if( $topicForm.find( '#link_preview_data' ).length > 0 && $topicForm.find( '#link_preview_data' ).val() !== '' ) {
						link_preview_url = JSON.parse( $topicForm.find( '#link_preview_data' ).val() ).link_url;
					}

					if (
						( editor && $.trim( editor.getContent().replace( '<p><br></p>', '' ) ) === '' )
						&& media_valid == false
					) {
						if( link_preview_url === '' ){
							$topicForm.find( '#bbp_editor_topic_content' ).addClass( 'error' );
							valid = false;
						}
					} else if (
						( !editor && $.trim( $topicForm.find( '#bbp_topic_content' ).val() ) === '' )
						&& media_valid == false
					) {
						if( link_preview_url === '' ){
							$topicForm.find( '#bbp_topic_content' ).addClass( 'error' );
							valid = false;
						}
					} else {
						if ( editor ) {
							$topicForm.find( '#bbp_editor_topic_content' ).removeClass( 'error' );
						}
						$topicForm.find( '#bbp_topic_content' ).removeClass( 'error' );
					}

					if ( valid ) {
						$topicForm.submit();
					} else {
						$topicForm.removeClass( 'submitting' );
					}
				}
			);

			$( '.bbp-reply-form form #bbp_reply_submit' ).on(
				'click',
				function ( e ) {
					e.preventDefault();

					var valid = true;
					var $replyForm = $( e.target ).closest( 'form' );

					if ( $replyForm.find( '.bbp-form-anonymous' ).length ) {
						if ( $.trim( $replyForm.find( '#bbp_anonymous_author' ).val() ) === '' ) {
							$replyForm.find( '#bbp_anonymous_author' ).addClass( 'error' );
							valid = false;
						} else {
							$replyForm.find( '#bbp_anonymous_author' ).removeClass( 'error' );
						}

						if ( $.trim( $replyForm.find( '#bbp_anonymous_email' ).val() ) === '' ) {
							$replyForm.find( '#bbp_anonymous_email' ).addClass( 'error' );
							valid = false;
						} else {
							$replyForm.find( '#bbp_anonymous_email' ).removeClass( 'error' );
						}
					}

					if ( valid ) {
						var topic_id = $replyForm
		 					.find( 'input[name="bbp_topic_id"]' )
		 					.val();
						$replyForm.submit();
					}
					$( 'body' ).removeClass( 'popup-modal-reply' );
					$( '.bs-reply-list-item.in-focus' ).removeClass( 'in-focus' );
					$replyForm.find( '.bbp_topic_tags_wrapper tags tag' ).remove();
				}
			);
			$( document ).keydown(
				function ( e ) {
					if ( e.ctrlKey && 13 === e.keyCode ) {
						var bb_topic = $( '.bbp-topic-form form' ), bb_reply = $( '.bbp-reply-form form' );
						if ( bb_reply.length ) {
							bb_reply.find( '#bbp_reply_submit' ).trigger( 'click' );
						}
						if ( bb_topic.length ) {
							bb_topic.find( '#bbp_topic_submit' ).trigger( 'click' );
						}
					}
				}
			);
			window.addReply = {
				moveForm: function ( replyId, parentId, respondId, postId ) {
					$( '.bbp-reply-form' ).find( '#bbp_reply_to' ).val( parentId );
					var t = this, div, reply = t.I( replyId ), respond = t.I( respondId ),
						cancel = t.I( 'bbp-cancel-reply-to-link' ), parent = t.I( 'bbp_reply_to' ),
						post = t.I( 'bbp_topic_id' );

					if ( !reply || !respond || !cancel || !parent ) {
						return;
					}

					t.respondId = respondId;
					postId = postId || false;

					if ( !t.I( 'bbp-temp-form-div' ) ) {
						div = document.createElement( 'div' );
						div.id = 'bbp-temp-form-div';
						div.style.display = 'none';
						respond.parentNode.insertBefore( div, respond );
					}

					respond.classList.remove( 'mfp-hide' );
					reply.parentNode.appendChild( respond );

					if ( typeof tinyMCE !== 'undefined' ) {

						// Remove existing instances of tinyMCE.
						tinyMCE.remove();

						// magnificPopup reinitialize tinyMCE.
						tinyMCE.init(
							{
								selector: 'textarea.bbp-the-content',
								menubar: false,
								branding: false,
								plugins: "image,lists,link",
								toolbar: "bold italic bullist numlist blockquote link",
							}
						);
					}

					if ( post && postId ) {
						post.value = postId;
					}
					parent.value = parentId;
					cancel.style.display = '';

					cancel.onclick = function () {
						var t = addReply, temp = t.I( 'bbp-temp-form-div' ), respond = t.I( t.respondId );

						if ( !temp || !respond ) {
							return;
						}

						t.I( 'bbp_reply_to' ).value = '0';
						respond.classList.add( 'mfp-hide' );
						temp.parentNode.insertBefore( respond, temp );
						temp.parentNode.removeChild( temp );
						this.style.display = 'none';
						this.onclick = null;
						return false;
					};

					try {
						t.I( 'bbp_reply_content' ).focus();
					} catch ( e ) {
					}

					return false;
				},

				I: function ( e ) {
					return document.getElementById( e );
				}
			};

		},

		profileFilterPoint: function () {
			var mediaQuery = '( max-width: 800px )';
			var mediaQuerySpot = window.matchMedia( mediaQuery );

			function profileSearchShift() {
				if ( mediaQuerySpot.matches ) {
					$( '#bp-profile-search-form-outer' ).prependTo( '#buddypress' );
				} else {
					$( '#bp-profile-search-form-outer' ).prependTo( '#secondary' );
				}
			}

			mediaQuerySpot.addEventListener('change', function( event ) {
				profileSearchShift();
			} )

			profileSearchShift();
		},

		heartbeat: function () {
			if ( ( typeof bs_data.show_notifications !== 'undefined' && bs_data.show_notifications == '1' ) || ( typeof bs_data.show_messages !== 'undefined' && bs_data.show_messages == '1' ) ) {
				// HeartBeat Send and Receive.
				$( document ).on( 'heartbeat-send', this.bpHeartbeatSend.bind( this ) );
				$( document ).on( 'heartbeat-tick', this.bpHeartbeatTick.bind( this ) );
			}
		},

		// Mobile dropdown navigation.
		bpNavDropDown: function () {
			$( 'nav .bp-navs__dropdown' ).change( function () {
				window.location = $( this ).find( 'option:selected' ).val();
			} );
		},

		scrollToAnchor: function () {
			if ( $( 'body' ).hasClass( 'sticky-header' ) && $( 'body' ).hasClass( 'single-post' ) ) {
				var headerHeight = $( '#masthead' ).height();
				var headerHeightExt = headerHeight + 55;
				$( document ).on( 'click', '.widget_recent_comments .recentcomments > a, .widget_recent_comments .wp-block-latest-comments__comment-link, .widget.new_blog_comment.bp-latest-activities .activity-time-since', function ( e ) {	
					var anchor = $( this ).attr( 'href' );
					var anachorName = anchor.substr( anchor.indexOf( '#' ) + 1);
					var anachorElem = $( '#' + anachorName );
					if (anachorElem.length) {
						e.preventDefault();
						$( 'html,body' ).animate( { scrollTop: anachorElem.offset().top + (-headerHeightExt) }, 'slow' );
						window.history.pushState({}, '', '#' + anachorName);
					}
				} );

				if ( window.location.href.indexOf("#comment-") > -1 ) {
					var url = window.location.href;
					var commentNum = url.substr( url.indexOf( '#' ) + 1);
					var commentElem = $( '#' + commentNum );
					setTimeout( function(){
						if (commentElem.length) {
							$( 'html,body' ).animate( { scrollTop: commentElem.offset().top + (-headerHeightExt) }, 100 );
						}
					}, 100);
				}
			}
		},

		/**
		 * [heartbeatSend description]
		 *
		 * @param  {[type]} event [description]
		 * @param  {[type]} data  [description]
		 * @return {[type]}       [description]
		 */
		bpHeartbeatSend: function ( event, data ) {
			data.customfield = '';

			// Add an heartbeat send event to possibly any BuddyPress pages.
			$( '#buddypress' ).trigger( 'bp_heartbeat_send', data );
		},

		/**
		 * [heartbeatTick description]
		 *
		 * @param  {[type]} event [description]
		 * @param  {[type]} data  [description]
		 * @return {[type]}       [description]
		 */
		bpHeartbeatTick: function ( event, data ) {
			this.bpInjectNotifications( event, data );

			// Add an heartbeat send event to possibly any BuddyPress pages.
			$( '#buddypress' ).trigger( 'bp_heartbeat_tick', data );
		},

		/**
		 * Injects all unread notifications
		 */
		bpInjectNotifications: function ( event, data ) {
			if ( typeof data.unread_notifications !== 'undefined' && data.unread_notifications !== '' ) {
				$( '#header-notifications-dropdown-elem .notification-dropdown .notification-list' ).empty().html( data.unread_notifications );
			}

			// inject all unread messages notifications.
			if ( typeof data.unread_messages !== 'undefined' && data.unread_messages !== '' ) {
				$( '#header-messages-dropdown-elem .notification-dropdown .notification-list' ).empty().html( data.unread_messages );
			}

			if ( typeof data.total_notifications !== 'undefined' && data.total_notifications > 0 ) {
				var notifs = $( '.bb-icon-bell' );
				var notif_icons = $( notifs ).parent().children( '.count' );
				$( '.notification-header .mark-read-all' ).show();

				if ( notif_icons.length > 0 ) {
					$( notif_icons ).text( data.total_notifications );
				} else {
					$( notifs ).parent(':not(.group-subscription)').append( '<span class="count"> ' + data.total_notifications + ' </span>' );
				}
			} else {
				var notifs = $( '.bb-icon-bell' );
				var notif_icons = $( notifs ).parent().children( '.count' );
				$( notif_icons ).remove();
				$( '.notification-header .mark-read-all' ).fadeOut();
			}

			if ( typeof data.total_unread_messages !== 'undefined' && parseInt( data.total_unread_messages ) === 0 ) {
				var msg = $( '.bb-icon-inbox' );
				var msg_icons = $( msg ).parent().children( '.count' );
				if ( msg_icons.length > 0 ) {
					$( msg_icons ).remove();
				}

				var menu_item = $( '.bp-messages-nav' );
				var menu_count = menu_item.find( '.count' );
				if ( menu_count.length > 0 ) {
					menu_count.text( data.total_unread_messages ).addClass( 'bp-hide' );
				}
			}

			if ( typeof data.total_unread_messages !== 'undefined' && data.total_unread_messages > 0 ) {
				var msg = $( '.bb-icon-inbox' );
				var msg_icons = $( msg ).parent().children( '.count' );

				if ( msg_icons.length > 0 ) {
					$( msg_icons ).text( data.total_unread_messages );
				} else {
					$( msg ).parent().append( '<span class="count"> ' + data.total_unread_messages + ' </span>' );
				}

				var menu_item = $( '.bp-messages-nav' );
				var menu_count = menu_item.find( '.count' );
				if ( menu_count.length > 0 ) {
					menu_count.text( data.total_unread_messages ).removeClass( 'bp-hide' );
				}
			}
		},
	};

	$( document ).on(
		'ready',
		function () {
			BuddyBossTheme.init();

			$( '.bp-personal-sub-tab #compose' ).on(
				'click',
				function () {
					$( this ).parent().toggleClass( 'current selected' );
				}
			);
		}
	);

	function bs_gallery_slider() {
		if ( $( 'body' ).hasClass( 'has-sidebar' ) ) {
			var $break = 900;
		} else {
			var $break = 544;
		}

		var index = 0;
		$( '.gallery' ).each(
			function () {
				if ( !$( this ).hasClass( 'slick-initialized' ) ) { // Prevent error on loading more posts.
					index++;
					$( this ).attr( 'data-slider', index );
					$( this ).slick(
						{
							arrows: true,
							prevArrow: '<a class="bb-slide-prev"><i class="bb-icon-angle-right"></i></a>',
							nextArrow: '<a class="bb-slide-next"><i class="bb-icon-angle-right"></i></a>',
							dots: true,
							fade: true,
							slidesToShow: 1,
							slidesToScroll: 1,
							// customPaging: function ( slider, i ) { // example.
							customPaging: function () {
								return '<span></span>'; // Remove button, customize content of "li".
							},
							mobileFirst: true,
							responsive: [
								{
									breakpoint: $break,
									settings: {
										slidesToShow: 1,
										slidesToScroll: 1,
									}
								}
							]
						}
					);
				}
			}
		);
	}

	function bs_getUrlParameter( sParam ) {
		var sPageURL = window.location.search.substring( 1 ),
			sURLVariables = sPageURL.split( '&' ),
			sParameterName,
			i;

		for ( i = 0; i < sURLVariables.length; i++ ) {
			sParameterName = sURLVariables[ i ].split( '=' );

			if ( sParameterName[ 0 ] === sParam ) {
				return sParameterName[ 1 ] === undefined ? true : decodeURIComponent( sParameterName[ 1 ] );
			}
		}
	}

	function GamiPressWidgetData() {
		$( '.buddypress.widget .gamipress-buddypress-user-details-listing:not(.is_loaded)' ).each ( function() {
			if( $( this ).text().trim() !== '' ) {
				$( this ).parent().append('<span class="showGamipressData" data-balloon-pos="right" data-balloon="' + bs_data.gamipress_badge_label + '"></span>');
				if( $( this ).find( 'img' ).length ) {
					$( this ).parent().find( '.showGamipressData' ).append( '<img src="' +  $( this ).find( 'img' ).attr('src') +'"/>');
				} else {
					$( this ).parent().find( '.showGamipressData' ).append( '<i class="bb-icon-l bb-icon-award"></i>');
				}
				$( this ).parent().find( '.gamipress-buddypress-user-details-listing' ).wrap( '<div class="GamiPress-data-popup"></div>' );
				$( this ).parent().find( '.gamipress-buddypress-user-details-listing' ).append( '<i class="bb-icon-l bb-icon-times hideGamipressData"></i>' );
			}
			$( this ).addClass( 'is_loaded' );
		});
	}

	/**
	 * Learndash Gutenberg
	 */

	$( ".ld-entry-content > .entry-content-wrap .entry-content" ).addClass( "ld-gb-content" );

	/**
	 * LifterLMS
	 */

	$( ".llms-notice > a" ).click(
		function () {

			if ( $( ".llms-person-login-form-wrapper" ).hasClass( "llms-person-login-aktif" ) ) {
				$( ".llms-login" ).css( "display", "none" );
				$( ".llms-person-login-form-wrapper" ).removeClass( "llms-person-login-aktif" );
			} else {
				$( ".llms-person-login-form-wrapper" ).addClass( "llms-person-login-aktif" );
			}
		}
	);

	$.fn.wrapStart = function ( numWords ) {
		var node = this.contents().filter(
			function () {
				return this.nodeType == 3
			}
			).first(),
			text = node.text().replace( /\s+/g, " " ).replace( /^\s|\s$/g, "" ),
			first = text.split( " ", numWords ).join( " " );

		if ( !node.length ) {
			return;
		}

		node[ 0 ].nodeValue = text.slice( first.length );
		node.before( '<span>' + first + '</span>' );
	};

	$( '.mepr-price-box-price' ).each(
		function () {
			$( this ).wrapStart( 1 )
		}
	);

	/**
	 * Profile Dropdown Menu
	 */

	if ( $( '#header-my-account-menu' ).length ) {

		$( '#header-my-account-menu > li' ).each(
			function () {
				if ( $( this ).hasClass( 'icon-added' ) ) {
					$( this ).closest( 'ul' ).addClass( 'has-icon' );
					return false; // Break loop as we know this menu item has icon.
				}
			}
		);

		$( '#header-my-account-menu ul' ).each(
			function () {
				$( this ).children( 'li' ).each( function () {
					if ( $( this ).hasClass( 'icon-added' ) ) {
						$( this ).closest( 'ul' ).addClass( 'has-icon' );
						return false; // Break loop as we know this menu item has icon.
					}
				} );
			}
		);

	}

	/**
	 * WP Profile Dropdown Menu
	 */

	if ( $( '.admin-bar #wp-admin-bar-my-account-default' ).length ) {
		$( '.admin-bar #wp-admin-bar-my-account-default > li' ).each( function () {
			if ( $( this ).hasClass( 'menupop' ) ) {
				$( this ).closest( 'ul' ).addClass( 'has-menupop' );
				return false; // Break loop as we know this menu item has icon.
			}
		} );
	}

	/**
	 * Show More Option Dropdown
	 */
	$( document ).on( 'click', '.forum_single_action_wrap .forum_single_action_more-wrap', function( event ) {
		var $this = $( event.currentTarget ).closest( '.forum_single_action_wrap' );
		if( $this.hasClass( 'is_visible' ) ) {
			$this.removeClass( 'is_visible' );
		} else {
			$this.addClass( 'is_visible' );
		}
	});

	/**
	 * Show Gamipress Widget data in popup
	 */

	if( $( '.buddypress.widget .gamipress-buddypress-user-details-listing' ).length ) {
		var tempStyles;

		GamiPressWidgetData();

		$( document ).on('click', '.buddypress.widget .showGamipressData', function() {
			$( this ).parent().find( '.GamiPress-data-popup' ).addClass( 'is_active' );
			if( $( this ).closest( '.bb-sticky-sidebar' ).length ) { //Check if parent is sticky
				tempStyles = $( this ).closest( '.bb-sticky-sidebar' ).attr( 'style' ); //Store parent's fixed styling and remove to avoid issue
				$( this ).closest( '.bb-sticky-sidebar' ).attr( 'style', '' );
				$('body').addClass( 'hide-overflow' );
			}
		});

		$( document ).on( 'heartbeat-tick', function ( event, data ) { // When heartbeat called re-run function for widgets
			setTimeout( function(){
				GamiPressWidgetData();
			}, 1000);
		});

		$( '.widget div#members-list-options a' ).on('click', function() {
			setTimeout( function(){
				GamiPressWidgetData();
			}, 3000);
		});

		$( document ).on('click', '.buddypress.widget .GamiPress-data-popup .hideGamipressData', function() {
			$( this ).closest( '.GamiPress-data-popup' ).removeClass( 'is_active' );
			if( $( this ).closest( '.bb-sticky-sidebar' ).length ) {
				$( this ).closest( '.bb-sticky-sidebar' ).attr( 'style', tempStyles ); //add parent's fixed styling back
				tempStyles = '';
				$('body').removeClass( 'hide-overflow' );
			}
		});

	}

	/**
	 * Set reply title and topic id after load the reply form in user timeline.
	 *
	 * Using jQuery trigger 'bbp_after_load_reply_form'
	 */
	$( 'body' ).on( 'bbp_after_load_reply_form', function( event, button ) {
		if ( ! $( 'div[data-component="activity"]' ).length ) {
			return;
		}

		var self = $( button.click_event ),
			form = self
				.closest( '.bb-grid' )
				.find( '.bbp-reply-form' );

		if ( form.length ) {
			var topic_id     = self.data( 'topic-id' ),
				authorName   = self.data( 'author-name' ),
 				reply_exerpt = self.closest( 'li' )
 					.find( '.activity-discussion-title-wrap a' )
 					.text();

			form
				.closest( '.bb-quick-reply-form-wrap' )
				.show();

			form
				.css( { 'display': 'block' } );

			form
				.addClass( 'bb-modal bb-modal-box' );

			form
				.closest( '.bb-quick-reply-form-wrap' )
				.show();

 			form
 				.find( '#bbp_reply_to' )
 				.val( 0 );

			form
 				.find( '#bbp-reply-exerpt' )
 				.text( reply_exerpt + '...' );

			form
				.find( 'input[name="bbp_topic_id"]' )
				.val( topic_id );
	 	}
	});

	/**
	 * Show success message after submit reply form.
	 *
	 * Using jQuery trigger 'bbp_after_submit_reply_form'
	 */
	$( document ).on( 'bbp_after_submit_reply_form', '.bbp-reply-form', function( event, data ) {
		if ( !data.response.success ) {
			return;
		}

		if ( !$( 'div[data-component="activity"]' ).length ) {
			return;
		}

		var reply_exerpt = $( 'a[data-topic-id=' + data.topic_id + ']' )
			.closest( 'li' )
			.find( '.activity-discussion-title-wrap' )
			.clone();

		var reply_view = $( 'a[data-topic-id=' + data.topic_id + ']' )
			.closest( 'li' )
			.find( '.activity-discussion-title-wrap' )
			.find( 'a' )
			.attr( 'href' );

		$( '.bbp-reply-form-success-modal' )
			.find( '.activity-list' )
			.html( reply_exerpt );

		setTimeout( function () {
			$( '.bbp-reply-form-success-modal .view-reply-button a' ).attr( 'href', data.response.redirect_url );
			$( '.bbp-reply-form-success-modal' ).removeAttr( 'style' );
			$( '.bbp-reply-form-success-modal .bb-modal' ).css( 'z-index', 999992 );
			$( '.bbp-reply-form-success' ).fadeIn( 100 );
		}, 200 );
	});

})( jQuery );

/**
 *
 * @param {String} query
 * @param {String} variable
 * @returns {String|Boolean}
 */
var BBGetQueryVariable = BBGetQueryVariable || function ( query, variable ) {
	if ( typeof query !== 'string' || query == '' || typeof variable == 'undefined' || variable == '' ) {
		return '';
	}

	var vars = query.split( "&" );

	for ( var i = 0; i < vars.length; i++ ) {
		var pair = vars[ i ].split( "=" );

		if ( pair[ 0 ] == variable ) {
			return pair[ 1 ];
		}
	}
	return ( false );
};

var BBGetUrlParameter = BBGetUrlParameter || function ( url, parameter_name ) {
	parameter_name = parameter_name.replace( /[\[]/, '\\[' ).replace( /[\]]/, '\\]' );
	var regex      = new RegExp( '[\\?&]' + parameter_name + '=([^&#]*)' );
	var results    = regex.exec( url );
	return results === null ? '' : decodeURIComponent( results[1].replace( /\+/g, ' ' ) );
};

//Add Class to member card which is last of type in section after member ajax completes
jQuery( '#members-group-list' ).on("DOMSubtreeModified", function() {
	jQuery('#members-list.item-list:not(.grid) li.item-entry-header').prev('li').addClass('last-of-type');
});

// whenever we hover over a menu item that has a submenu
setTimeout(
	function() {
		jQuery( document ).on( 'mouseover', '#site-navigation #navbar-collapse #navbar-extend .menu-item-has-children, #site-navigation #primary-menu .ab-submenu .menu-item-has-children', function () {
			var $menuItem = jQuery( this ),
				$submenuWrapper = jQuery(this).children( '.ab-submenu' );

			// grab the menu item's position relative to its positioned parent
			var menuItemPos = $menuItem.position();

			// place the submenu in the correct position relevant to the menu item
			$submenuWrapper.css(
				{
					top: menuItemPos.top
				}
			);
		}
	);
}, 500);
