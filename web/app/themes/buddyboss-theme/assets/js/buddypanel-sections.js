/**
 * WordPress Administration BuddyPanel Navigation Menu
 * Interface JS functions
 *
 * @version WP 2.0.0
 *
 * @use wp-admin/js/nav-menu.js
 */

/* global menus, isRtl, ajaxurl, wpNavMenu */

(function ( $ ) {

	'use strict';

	window.BuddyPanelSections = {

		init: function () {

			if ( 'undefined' === typeof window.wpNavMenu ) {
				return;
			}

			var current_depth = 0;
			var bb_transport  = '';
			var bb_children   = '';

			$( document ).on(
				'click',
				'#buddypanel-menu .button-controls #submit-buddypanel-section-menu',
				function () {

					var menu          = $( '#menu' ).val(),
						nonce         = $( '#menu-settings-column-nonce' ).val(),
						processMethod = window.wpNavMenu.addMenuItemToBottom;

					var params = {
						'action': 'add-menu-item',
						'menu': menu,
						'menu-settings-column-nonce': nonce,
						'menu-item': {
							'-1': {
								'menu-item-object-id': Math.floor( Math.random() * 1E16 ),
								'menu-item-object': 'custom',
								'menu-item-patent-id': 0,
								'menu-item-type': 'custom',
								'menu-item-description': 'bb-theme-section',
								'menu-item-title': wp.i18n.__( 'Get Started' ),
							},
						},
					};

					$( '#buddypanel-menu' ).find( '.button-controls .spinner' ).addClass( 'is-active' );

					$.post(
						ajaxurl,
						params,
						function ( menuMarkup ) {
							var ins = $( '#menu-instructions' );
							$( '#buddypanel-menu' ).find( '.button-controls .spinner' ).removeClass( 'is-active' );

							menuMarkup = menuMarkup || '';
							menuMarkup = menuMarkup.toString().trim(); // Trim leading whitespaces.
							processMethod( menuMarkup, params );

							// Make it stand out a bit more visually, by adding a fadeIn.
							$( 'li.pending' ).hide().fadeIn( 'slow' );
							$( '.drag-instructions' ).show();
							if (
								! ins.hasClass( 'menu-instructions-inactive' ) &&
								ins.siblings().length
							) {
								ins.addClass( 'menu-instructions-inactive' );
							}
						}
					);
				}
			);

			$( '#menu-to-edit' ).on(
				'sortstart',
				function ( event, ui ) {
					bb_transport = ui.item.children( '.menu-item-transport' );

				}
			);

			$( '#menu-to-edit' ).on(
				'sortstop',
				function ( event, ui ) {
					var current_item  = ui.item;
					var originalDepth = current_item.menuItemDepth();

					if ( $( current_item ).prev( '.menu-item-section' ).length > 0 && current_depth > 0 ) {
						bb_children = bb_transport.children().insertAfter( ui.item );
					}

					setTimeout(
						function () {

							if ( $( current_item ).prev( '.menu-item-section' ).length > 0 && current_depth > 0 ) {
								current_item.updateDepthClass( 0 );
								window.BuddyPanelSections.bb_shiftDepthClass( 1, bb_children, originalDepth );
								// Update the item data.
								current_item.updateParentMenuItemDBId();
								window.wpNavMenu.refreshKeyboardAccessibility();
								window.wpNavMenu.refreshAdvancedAccessibility();
							}

							if ( $( current_item ).hasClass( 'menu-item-section' ) && current_depth > 0 ) {
								current_item.find( '.item-title .is-submenu' ).hide();
								current_item.find( 'input.menu-item-data-parent-id' ).val(0);
								current_item.updateDepthClass( 0 );
								window.wpNavMenu.menuList.sortable( 'cancel' );
							}

							if (
								$( current_item ).hasClass( 'menu-item-section' ) &&
								(
									$( current_item ).prev().length > 0 &&
									$( current_item ).next().length > 0 &&
									(
										! $( current_item ).prev().hasClass( 'menu-item-depth-0' ) ||
										! $( current_item ).next().hasClass( 'menu-item-depth-0' )
									)
								)
							) {
								current_item.find( '.item-title .is-submenu' ).hide();
								current_item.find( 'input.menu-item-data-parent-id' ).val(0);
								current_item.updateDepthClass( 0 );
								window.wpNavMenu.menuList.sortable( 'cancel' );
							}
						}
					);
				}
			);

			$( '#menu-to-edit' ).on(
				'sort',
				function ( event, ui ) {
					var offset   = ui.helper.offset(),
						menuEdge = window.wpNavMenu.menuList.offset().left,
						edge     = window.wpNavMenu.isRTL ? offset.left + ui.helper.width() : offset.left;

					current_depth = window.wpNavMenu.negateIfRTL * window.wpNavMenu.pxToDepth( edge - menuEdge );
				}
			);

			$( '#menu-to-edit' ).on(
				'click',
				'a.item-edit',
				function () {
					var item = jQuery( this ).closest( '.menu-item-section' );

					if ( item.length > 0 ) {
						item.find( '.field-url' ).css( 'display', 'none' );
						item.find( '.field-icon' ).css( 'display', 'none' );
						// Hide for Mobile menu loggedin or logged out.
						if (
							false === $( '#locations-mobile-menu-logged-in' ).length && $( '#locations-mobile-menu-logged-in' ).prop( 'checked' ) &&
							false === $( '#locations-mobile-menu-logged-out' ).length && $( '#locations-mobile-menu-logged-out' ).prop( 'checked' )
						) {
							item.find( '.field-stick_to_bottom' ).css( 'display', 'none' );
						}
						item.find( '.field-description' ).css( 'display', 'none' );
						item.find( '.field-description textarea' ).attr('readonly','readonly');
					}
				}
			);

			$( '.menu-theme-locations input' ).on(
				'change',
				function () {
					if (
						$( '.menu-theme-locations .menu-settings-input input:checked' ).length === 0 ||
						$( '#locations-header-menu' ).prop( 'checked' ) ||
						$( '#locations-header-menu-logout' ).prop( 'checked' ) ||
						$( '#locations-header-my-account' ).prop( 'checked' )

					) {
						$( '#buddypanel-menu p.warning' ).show();
						$( '#buddypanel-menu p.button-controls' ).hide();
					} else {
						$( '#buddypanel-menu p.warning' ).hide();
						$( '#buddypanel-menu p.button-controls' ).show();
					}
				}
			);

			window.wpNavMenu.moveMenuItem = function ( $this, dir ) {

				var items, newItemPosition, newDepth,
					menuItems        = $( '#menu-to-edit li' ),
					menuItemsCount   = menuItems.length,
					thisItem         = $this.parents( 'li.menu-item' ),
					thisItemChildren = thisItem.childMenuItems(),
					thisItemData     = thisItem.getItemData(),
					thisItemDepth    = parseInt( thisItem.menuItemDepth(), 10 ),
					thisItemPosition = parseInt( thisItem.index(), 10 ),
					nextItem         = thisItem.next(),
					nextItemChildren = nextItem.childMenuItems(),
					nextItemDepth    = parseInt( nextItem.menuItemDepth(), 10 ) + 1,
					prevItem         = thisItem.prev(),
					prevItemDepth    = parseInt( prevItem.menuItemDepth(), 10 ),
					prevItemId       = prevItem.getItemData()[ 'menu-item-db-id' ],
					a11ySpeech       = menus[ 'moved' + dir.charAt( 0 ).toUpperCase() + dir.slice( 1 ) ];

				switch ( dir ) {
					case 'up':
						newItemPosition = thisItemPosition - 1;

						if ( thisItem.hasClass( 'menu-item-section' ) ) {
							if ( 0 === thisItemPosition ) {
								break;
							}

							var closest_previous = thisItem.prevAll( '.menu-item-depth-0:first' );
							var next_position    = parseInt( closest_previous.index(), 10 );

							thisItem.detach().insertBefore( menuItems.eq( next_position ) ).updateParentMenuItemDBId();
						} else {

							// Already at top.
							if ( 0 === thisItemPosition ) {
								break;
							}

							// If a sub item is moved to top, shift it to 0 depth.
							if ( 0 === newItemPosition && 0 !== thisItemDepth ) {
								thisItem.moveHorizontally( 0, thisItemDepth );
							}

							// If prev item is sub item, shift to match depth.
							if ( 0 !== prevItemDepth ) {
								thisItem.moveHorizontally( prevItemDepth, thisItemDepth );
							}

							// Move if destination previous element was bb.
							if ( prevItem.prev().hasClass( 'menu-item-section' ) ) {
								var nDepth = parseInt( prevItem.prev().menuItemDepth(), 10 );
								thisItem.moveHorizontally( nDepth, thisItemDepth );
							}

							// Does this item have sub items?
							if ( thisItemChildren ) {
								items = thisItem.add( thisItemChildren );
								// Move the entire block.
								items.detach().insertBefore( menuItems.eq( newItemPosition ) ).updateParentMenuItemDBId();
							} else {
								thisItem.detach().insertBefore( menuItems.eq( newItemPosition ) ).updateParentMenuItemDBId();
							}
						}
						break;
					case 'down':
						if ( thisItem.hasClass( 'menu-item-section' ) ) {

							if ( menuItemsCount === thisItemPosition + 1 ) {
								break;
							}

							var nextPosition = thisItemPosition + 1;

							if ( nextItemChildren && nextItemChildren.length > 0 ) {
								nextPosition = nextPosition + nextItemChildren.length;
							}

							thisItem.moveHorizontally( 0, 0 );
							thisItem.detach().insertAfter( menuItems.eq( nextPosition ) ).updateParentMenuItemDBId();
						} else {
							if ( thisItemChildren ) {
								items                = thisItem.add( thisItemChildren ),
									nextItem         = menuItems.eq( items.length + thisItemPosition ),
									nextItemChildren = 0 !== nextItem.childMenuItems().length;

								if ( nextItemChildren ) {
									newDepth = parseInt( nextItem.menuItemDepth(), 10 ) + 1;
									thisItem.moveHorizontally( newDepth, thisItemDepth );
								}

								if ( nextItem.hasClass( 'menu-item-section' ) ) {
									var nxtDepth = parseInt( nextItem.menuItemDepth(), 10 );
									thisItem.moveHorizontally( nxtDepth, thisItemDepth );
								}

								// Have we reached the bottom?
								if ( menuItemsCount === thisItemPosition + items.length ) {
									break;
								}

								items.detach().insertAfter( menuItems.eq( thisItemPosition + items.length ) ).updateParentMenuItemDBId();
							} else {
								// If next item has sub items, shift depth.
								if ( 0 !== nextItemChildren.length ) {
									thisItem.moveHorizontally( nextItemDepth, thisItemDepth );
								}

								// Have we reached the bottom?
								if ( menuItemsCount === thisItemPosition + 1 ) {
									break;
								}
								thisItem.detach().insertAfter( menuItems.eq( thisItemPosition + 1 ) ).updateParentMenuItemDBId();
							}
						}

						break;
					case 'top':
						// Already at top.
						if ( 0 === thisItemPosition ) {
							break;
						}
						// Does this item have sub items?
						if ( thisItemChildren ) {
							items = thisItem.add( thisItemChildren );
							// Move the entire block.
							items.detach().insertBefore( menuItems.eq( 0 ) ).updateParentMenuItemDBId();
						} else {
							thisItem.detach().insertBefore( menuItems.eq( 0 ) ).updateParentMenuItemDBId();
						}
						break;
					case 'left':
						if ( thisItem.hasClass( 'menu-item-section' ) || prevItem.hasClass( 'menu-item-section' ) ) {
							// As far left as possible.
							if ( 0 === thisItemDepth ) {
								break;
							}
							thisItem.shiftHorizontally( -thisItemDepth );

						} else {
							// As far left as possible.
							if ( 0 === thisItemDepth ) {
								break;
							}
							thisItem.shiftHorizontally( -1 );
						}
						break;
					case 'right':
						if ( thisItem.hasClass( 'menu-item-section' ) || prevItem.hasClass( 'menu-item-section' ) ) {
							// Can't be sub item at top.
							if ( 0 === thisItemPosition ) {
								break;
							}
							// Already sub item of prevItem.
							if ( thisItemData[ 'menu-item-parent-id' ] === prevItemId ) {
								break;
							}
							thisItem.shiftHorizontally( -thisItemDepth );
						} else {
							// Can't be sub item at top.
							if ( 0 === thisItemPosition ) {
								break;
							}
							// Already sub item of prevItem.
							if ( thisItemData[ 'menu-item-parent-id' ] === prevItemId ) {
								break;
							}
							thisItem.shiftHorizontally( 1 );
						}
						break;
				}
				$this.trigger( 'focus' );
				window.wpNavMenu.registerChange();
				window.wpNavMenu.refreshKeyboardAccessibility();
				window.wpNavMenu.refreshAdvancedAccessibility();

				if ( a11ySpeech ) {
					wp.a11y.speak( a11ySpeech );
				}
			};
		},

		bb_shiftDepthClass: function ( change, element, current_depth ) {
			return element.each(
				function () {
					var t = $( this );

					var depth = t.menuItemDepth();

					var newDepth = depth - current_depth;

					t.removeClass( 'menu-item-depth-' + depth ).addClass( 'menu-item-depth-' + (newDepth) );

					if ( 0 === newDepth ) {
						  t.find( '.is-submenu' ).hide();
					}
				}
			);
		},
	};

	window.BuddyPanelSections.init();

})( jQuery );
