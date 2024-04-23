/* jshint browser: true */
/* global bp, wp, window, document */
window.bp = window.bp || {};

(function (exports, $) {

	/**
	 * Emoticon picker that hold emojis, icons and custom icon selection.
	 *
	 * @type {Object}
	 */
	bp.EmotionPicker = {

		/**
		 * Initiate the emojis picker.
		 *
		 * @return {[type]} [description]
		 */
		start: function () {
			this.setupGlobals();
			this.addListeners();
		},

		/**
		 * Setup all global variables and member variables.
		 *
		 * @return {[type]}
		 */
		setupGlobals: function () {
			this.defaultColor        = '#000000';
			this.currentEventHandler = null;
			this.editorMode          = 'reactionEmotion';
			this.defaultData         = {
				id: '',
				type: 'emotions',
				name: '',
				icon: '',
				icon_color: this.defaultColor,
				icon_text: '',
				text_color: this.defaultColor,
				notification_text: '',
				icon_path: '',
				is_active: true
			};

			if ( 'undefined' === typeof this.iconPreviewData ) {
				this.iconPreviewData   = this.defaultData;
				window.iconPreviewData = this.iconPreviewData;
			}

			if ('undefined' === typeof bbProEmojiPreviewTemplate) {
				window.bbProEmojiPreviewTemplate = wp.template( 'buddyboss-emojis-picker-preview' );
			}

			if ('undefined' === typeof bbProEmojiSettingTemplate) {
				window.bbProEmojiSettingTemplate = wp.template( 'buddyboss-emoji-picker-setting' );
			}

			if ('undefined' === typeof bbProCustomIconPreviewTemplate) {
				window.bbProCustomIconPreviewTemplate = wp.template( 'bbpro-custom-icon-picker-preview' );
			}

			if ('undefined' === typeof bbProCustomIconSettingTemplate) {
				window.bbProCustomIconSettingTemplate = wp.template( 'bbpro-custom-icon-picker-setting' );
			}

			if ('undefined' === typeof bbProBuddyBossIconPreviewTemplate) {
				window.bbProBuddyBossIconPreviewTemplate = wp.template( 'buddyboss-pro-new-icon-preview' );
			}

			if ('undefined' === typeof bbProBuddyBossIconSettingsTemplate) {
				window.bbProBuddyBossIconSettingsTemplate = wp.template( 'buddyboss-pro-new-icon-settings' );
			}

			if ( 'undefined' === typeof bbProAddEmotion) {
				window.bbProAddEmotion = wp.template( 'bb-pro-add-emotion' );
			}

			if ( 'undefined' === typeof bbProIconsCategoryList) {
				window.bbProIconsCategoryList = wp.template( 'buddyboss-icons-category-list' );
			}

			if ( 'undefined' === typeof bbProEmojisCategoryList) {
				window.bbProEmojisCategoryList = wp.template( 'buddyboss-emojis-category-list' );
			}

			if ( 'undefined' === typeof bbProNoResults) {
				window.bbProNoResults = wp.template( 'buddyboss-no-results' );
			}

			this.emotionTypeFilter();
			this.customIconPickerTabs();
			this.categoryFilter();
			this.handleEmojiSearch();

			this.bbIconPreviewSettings();
			this.bbEmojiPreviewSettings();
			this.customIconPreviewSettings();

			this.customIconUpload();
			this.deleteCustomIcon();
			this.saveSelectedIcon();
		},

		/**
		 * Add all event handlers and listener hooks.
		 */
		addListeners: function () {
			var self = this;

			// Open the emotion picker modal.
			$( document ).on(
				'click',
				'.bb_emotions_add_new',
				function (e) {
					self.openEmotionPickerModal( e );
				}
			);

			// Edit the reaction.
			$( document ).on(
				'click',
				'.bb_emotions_edit',
				function (e) {
					self.openEmotionPickerModal( e );
				}
			);

			// Close the emotion picker modal.
			$( document ).on(
				'click',
				'button#bbpro_icon_modal_close',
				function (e) {
					self.closeEmotionPickerModal( e );
				}
			);

			// Open the icon picker modal.
			$( document ).on(
				'click',
				'#bb-reaction-button-chooser',
				function (e) {
					self.openEmotionPickerModal( e );
					self.resetEditorMode( 'reactionButton' );
				}
			);

			// Register input control events.
			this.handleIconColor();
			this.handleIconTextColor();
			this.handleNotificationText();
			this.handleIconText();
		},

		/**
		 * Reset the editor screens as per emotions or reaction button editor.
		 *
		 * @param {string} editorMode - The mode to set the editor.
		 */
		resetEditorMode: function ( editorMode ) {
			this.editorMode = editorMode;

			var editorWrapper   = $( '#bbpro_emotion_modal' ),
				editorHeader    = editorWrapper.find( '.bbpro-modal-box__header h3' ),
				emojisDropdown  = editorWrapper.find( '.bbpro-emotion-type-select-filter' ),
				rightSection    = editorWrapper.find( '#bbpro-icon-right-section' ),
				leftSection     = editorWrapper.find( '#bbpro-icon-left-section' ),
				saveButton      = editorWrapper.find( '#icon-picker-saved' ),
				iconsSelector   = editorWrapper.find( '#bbpro-icons .bbpro-icon-tag-render' ),
				iconType        = editorMode === 'reactionButton' ? 'bb-icons' : 'emotions',
				headerTitle     = editorHeader.text(),
				saveButtonTitle = saveButton.val();

			if ( editorMode === 'reactionButton' ) {
				var currentIcon           = $( '#bb-reaction-button-hidden-field' ).val();
				this.iconPreviewData.icon = currentIcon;
				this.iconPreviewData.type = iconType;
				window.iconPreviewData    = this.iconPreviewData;

				// If icon already set then show it selected and scroll to it.
				if ( typeof currentIcon !== 'undefined' && currentIcon !== '' ) {
					this.loadBBIconPreviewOnEdit();
					var selectedIconEl = document.querySelector( '#bbpro-icon-left-section .icons .bbpro-icon.selected' );
					selectedIconEl && selectedIconEl.scrollIntoView( { behavior: 'auto' } );
				} else {
					emojisDropdown.val( iconType ).trigger( 'change' );
				}

				iconsSelector.find( '.bb-icon-rf' ).toggleClass( 'bb-icon-r bb-icon-rf' );
				leftSection.addClass( 'full-width' );
				rightSection.hide();
				emojisDropdown.hide();
			} else {
				this.iconPreviewData.icon      = '';
				this.iconPreviewData.icon_text = '';
				this.iconPreviewData.type      = iconType;
				window.iconPreviewData         = this.iconPreviewData;

				emojisDropdown.val( iconType ).trigger( 'change' );
				iconsSelector.find( '.bb-icon-r' ).toggleClass( 'bb-icon-r bb-icon-rf' );
				leftSection.removeClass( 'full-width' );
				emojisDropdown.show();
				rightSection.show();
			}

			// Updated editor header and save button text.
			editorHeader.text( editorHeader.attr( 'data-alternate-title' ) );
			editorHeader.attr( 'data-alternate-title', headerTitle.trim() );
			saveButton.val( saveButton.attr( 'data-alternate-title' ) );
			saveButton.attr( 'data-alternate-title', saveButtonTitle.trim() );
		},

		/**
		 * Opens the emotion picker modal.
		 *
		 * @param {Event} e - The event object.
		 */
		openEmotionPickerModal: function (e) {
			e.preventDefault();

			var curObj               = $( e.currentTarget );
			this.currentEventHandler = curObj;

			var emoji_type     = curObj.attr( 'data-type' );
			var iconData       = curObj.attr( 'data-icon' );
			var jsonIconData   = null;
			var scrollSelector = '';

			if ( typeof iconData !== 'undefined' && iconData.length > 0 ) {
				jsonIconData           = $.parseJSON( iconData );
				this.iconPreviewData   = jsonIconData;
				window.iconPreviewData = jsonIconData;
				emoji_type             = jsonIconData.type;
				scrollSelector         = '.bbpro-icon';

				if ( 'custom' === emoji_type ) {
					this.saveLocalStorage( emoji_type, jsonIconData );
				}

			} else {
				emoji_type = this.iconPreviewData.type;
			}

			var reactionItem  = curObj.parents( '.bb_emotions_item' );
			var reactionCheck = reactionItem.find( 'input[type="checkbox"]' );

			var isActive = true;
			if ( typeof reactionCheck !== 'undefined' && reactionCheck.length > 0 ) {
				isActive = reactionCheck.prop( 'checked' );
			}

			window.iconPreviewData.isActive = isActive;
			this.iconPreviewData            = window.iconPreviewData;

			$( document ).find( '#bbpro_emotion_modal' ).css( 'display', 'block' );
			$( document ).find( 'body' ).addClass( 'modal-open' );
			$( '.bbpro_select_icon' ).prop( 'disabled', true );

			emoji_type = typeof emoji_type !== 'undefined' ? emoji_type : 'emotions';
			$( '.bbpro-emotion-type-select-filter' ).val( emoji_type );
			$( '.bbpro-icon-search-input' ).val( '' ).trigger( 'keyup' );

			if ( typeof jsonIconData !== 'undefined' && jsonIconData !== null ) {
				if ( 'emotions' === emoji_type ) {
					this.loadEmojiPreviewOnEdit();
				} else if ( 'bb-icons' === emoji_type ) {
					this.loadBBIconPreviewOnEdit();
				} else if ('custom' === emoji_type) {
					this.loadCustomIconPreviewOnEdit();
				}
			} else {
				this.loadDefaultPreviewOnAdd();
			}

			// Scroll the position to the selected icon.
			if ( scrollSelector !== '' ) {
				var selectedElement = document.querySelector( '#bbpro-icon-left-section .icons ' + scrollSelector + '.selected' );
				if ( null !== selectedElement ) {
					selectedElement.scrollIntoView( { behavior: 'auto' } );
				}
			}

			bp.EmotionPicker.lazyLoad( '.lazy-emoji' );

			$( '#bbpro-emojis' ).on(
				'scroll',
				function () {
					bp.EmotionPicker.lazyLoad( '.lazy-emoji' );
				}
			);
		},

		lazyLoad: function ( lazyTarget ) {
			var lazy = $( lazyTarget );
			if ( lazy.length ) {
				for ( var i = 0; i < lazy.length; i++ ) {
					var isInViewPort = false;

					if ( ! isInViewPort && lazy[ i ].getBoundingClientRect().top <= ( ( window.innerHeight || document.documentElement.clientHeight ) + window.scrollY ) ) {
						isInViewPort = true;
					}

					if ( isInViewPort && lazy[ i ].getAttribute( 'data-src' ) ) {
						lazy[ i ].src = lazy[ i ].getAttribute( 'data-src' );
						lazy[ i ].removeAttribute( 'data-src' );
						/* jshint ignore:start */
						$( lazy[ i ] ).on(
							'load',
							function () {
								$( this ).removeClass( 'lazy' );
							}
						);
						/* jshint ignore:end */
					}
				}
			}
		},

		/**
		 * Closes the emotion picker modal.
		 *
		 * @param {Event} e - The event object.
		 */
		closeEmotionPickerModal: function (e) {
			e.preventDefault();

			$( '#bbpro_emotion_modal' ).css( 'display', 'none' );
			$( document ).find( 'body' ).removeClass( 'modal-open' );
			$( '.bbpro-icon-search-input' ).val( '' ).trigger( 'keyup' );

			// Remove Uploaded Icon if not saved.
			if ( $( document ).find( '.bbpro-icon-file-upload' ).prev().find( '.bbpro_icon_preview' ).find( '.inner' ).hasClass( 'croppie-container' ) ) {
				$( document ).find( '.bbpro-icon-file-upload' ).prev().find( '.bbpro_icon_preview' ).hide().find( '.inner' ).show().html( '' );
				$( document ).find( '.bbpro-icon-file-upload' ).prev().find( '.bbpro_icon_preview' ).find( '.inner' ).removeClass( 'croppie-container' );
				$( document ).find( '.bbpro_custom_icon_upload' ).val( '' );
			}

			// Reset editor if editor is open for reaction button.
			if ( 'reactionButton' === bp.EmotionPicker.editorMode ) {
				bp.EmotionPicker.resetEditorMode( 'reactionEmotion' );
			}

			this.deleteLocalStorage( 'custom' );
			window.iconPreviewData = this.defaultData;

			bp.EmotionPicker.resetPreviewSettings();
		},

		/**
		 * Function to show the screen based on selected value.
		 */
		emotionTypeFilter: function () {
			var self = this;

			$( document ).on(
				'change',
				'.bbpro-emotion-type-select-filter',
				function () {
					var curObj         = $( this );
					var categoryFilter = $( '.bbpro-icon-category-filter-select' );
					var searchFilter   = $( '.bbpro-icon-search' );

					var buddyBossIconsList    = $( '#bbpro-icons' );
					var buddyBossIconsPreview = $( '.bbpro-buddyboss-icon-settings' );

					var buddyBossEmojisList    = $( '#bbpro-emojis' );
					var buddyBossEmojisPreview = $( '.bbpro-emojis-settings' );

					var uploadCustomIcons        = $( '#bbpro-custom-upload' );
					var uploadCustomIconsPreview = $( '.bbpro-custom-upload-settings' );

					$( '#bbpro-icon-right-section' ).show();
					$( '#bbpro-icon-left-section' ).removeClass( 'full-width' );
					$( '#icon-picker-saved' ).attr( 'disabled', true );

					switch ( curObj.val() ) {
						case 'bb-icons':
							categoryFilter.replaceWith( window.bbProIconsCategoryList() );
							searchFilter.show();

							buddyBossEmojisList.addClass( 'bbpro-hide' );
							buddyBossEmojisPreview.addClass( 'bbpro-hide' );

							uploadCustomIcons.addClass( 'bbpro-hide' );
							uploadCustomIconsPreview.addClass( 'bbpro-hide' );

							buddyBossIconsList.removeClass( 'bbpro-hide' );
							buddyBossIconsPreview.removeClass( 'bbpro-hide' );

							$( '.bbpro-icon-tag-render' ).show();
							break;
						case 'custom':
							categoryFilter.hide();
							searchFilter.hide();

							buddyBossEmojisList.addClass( 'bbpro-hide' );
							buddyBossEmojisPreview.addClass( 'bbpro-hide' );
							buddyBossIconsList.addClass( 'bbpro-hide' );
							buddyBossIconsPreview.addClass( 'bbpro-hide' );

							uploadCustomIcons.removeClass( 'bbpro-hide' );
							uploadCustomIconsPreview.removeClass( 'bbpro-hide' );

							if ( 0 < uploadCustomIcons.find( '.bbpro-custom-icons-list .bbpro-icon' ).length ) {
								uploadCustomIcons.find( 'li.bbpro-tab-uploaded' ).trigger( 'click' );
							} else {
								$( '#bbpro_emotion_modal' ).find( '.bbpro-tab-uploaded' ).hide();
								uploadCustomIcons.find( 'li.bbpro-tab-upload-icon' ).trigger( 'click' );
							}

							break;
						default:
							categoryFilter.replaceWith( window.bbProEmojisCategoryList() );
							searchFilter.show();

							uploadCustomIcons.addClass( 'bbpro-hide' );
							uploadCustomIconsPreview.addClass( 'bbpro-hide' );

							buddyBossIconsList.addClass( 'bbpro-hide' );
							buddyBossIconsPreview.addClass( 'bbpro-hide' );

							buddyBossEmojisList.removeClass( 'bbpro-hide' );
							buddyBossEmojisPreview.removeClass( 'bbpro-hide' );
							break;
					}

					categoryFilter.val( 'all' ).trigger( 'change' );
					$( '.bbpro-icon-search-input' ).val( '' ).trigger( 'keyup' );

					$( '.bbpro_select_icon' ).attr( 'data-screen', curObj.val() );
					self.resetPreviewSettings();
				}
			);
		},

		/**
		 * Emoji picker category filter.
		 */
		categoryFilter: function () {
			var self = this;

			$( document ).on(
				'change',
				'.bbpro-icon-category-filter-select',
				function () {
					var selectedCategory = $( this ).val();
					var emojiType        = $( '.bbpro-emotion-type-select-filter' ).val();
					var searchTerm       = $( '.bbpro-icon-search-input' ).val();
					var iconSelector     = 'bb-icons' === emojiType ? $( '.bbpro-icon-tag-render' ) : $( '.bbpro-emoji-tag-render' );
					var targetContainer  = 'bb-icons' === emojiType ? '#bbpro-icons' : '#bbpro-emojis';

					$( targetContainer ).find( '.bb-pro-no-results-screen' ).remove();

					if ( 'all' === selectedCategory && '' === searchTerm ) {
						iconSelector.show();
						return;
					}

					iconSelector.hide();

					var selectedIconCategory = '', searchedIcons;
					if ( 'bb-icons' === emojiType ) {
						searchedIcons        = $( '.bbpro-icon-tag-render[data-group="' + selectedCategory + '"]' );
						selectedIconCategory = $( '.bbpro-icon-tag-render[data-css="' + self.iconPreviewData.name + '" i]' ).attr( 'data-group' );
					} else {
						searchedIcons 		 = $( '.bbpro-emoji-tag-render[data-group="' + selectedCategory + '"]' );
						selectedIconCategory = $( '.bbpro-emoji-tag-render[data-name="' + self.iconPreviewData.name + '" i]' ).attr( 'data-group' );
					}

					// Search icons in selected category with given search term.
					if ( 'all' !== selectedCategory ) {
						searchedIcons = searchedIcons.filter(
							function () {
								var name = emojiType === 'bb-icons' ? $( this ).attr( 'data-css' ) : $( this ).attr( 'data-name' );
								return name.includes( searchTerm );
							}
						);
					} else {
						searchedIcons = emojiType === 'bb-icons' ? $( '.bbpro-icon-tag-render[data-css*="' + searchTerm + '" i]' ) : $( '.bbpro-emoji-tag-render[data-name*="' + searchTerm + '" i]' );
					}

					searchedIcons.show();

					// Reset sidebar preview and settings if selected icon is not in selected category.
					if (
						selectedIconCategory !== '' &&
						selectedCategory !== 'all' &&
						selectedIconCategory !== selectedCategory
					) {
						self.resetPreviewSettings();
					}

					// If no icon found then show no results screen.
					if ( searchedIcons.length <= 0 ) {
						$( targetContainer ).append( window.bbProNoResults() );
					}
				}
			);
		},

		/**
		 * Emoji picker search icon.
		 */
		handleEmojiSearch: function () {
			$( document ).on(
				'keyup',
				'.bbpro-icon-search-input',
				function (e) {
					e.stopImmediatePropagation();

					var curObj           = $( this ),
						searchTerm       = curObj.val(),
						emojiType        = $( '.bbpro-emotion-type-select-filter' ).val(),
						selectedCategory = $( '.bbpro-icon-category-filter-select' ).val(),
						iconsSelector    = 'bb-icons' === emojiType ? $( '.bbpro-icon-tag-render' ) : $( '.bbpro-emoji-tag-render' ),
						targetContainer  = 'bb-icons' === emojiType ? '#bbpro-icons' : '#bbpro-emojis',
						searchedIcons;

					$( targetContainer ).find( '.bb-pro-no-results-screen' ).remove();

					if ( '' === searchTerm ) {

						if ( 'all' === selectedCategory ) {
							iconsSelector.show();
							return;
						}

						iconsSelector.hide();

						if ( 'bb-icons' === emojiType && 'all' !== selectedCategory ) {
							$( '.bbpro-icon-tag-render[data-group="' + selectedCategory + '"]' ).show();
							return;
						}

						if ( 'emotions' === emojiType && 'all' !== selectedCategory ) {
							$( '.bbpro-emoji-tag-render[data-group="' + selectedCategory + '"]' ).show();
							return;
						}
					}

					iconsSelector.hide();

					// Search icons in given term.
					if ( 'bb-icons' === emojiType ) {
						searchedIcons = $( '.bbpro-icon-tag-render[data-label-lower*="' + searchTerm.toLowerCase() + '"]' );
					} else {
						searchedIcons = $( '.bbpro-emoji-tag-render[data-name*="' + searchTerm + '" i]' );
						bp.EmotionPicker.lazyLoad( '.lazy-emoji' );
					}

					// If selected category is not 'all' then filter icons by selected category.
					if ( 'all' !== selectedCategory ) {
						searchedIcons = searchedIcons.filter(
							function() {
								var group = $( this ).attr( 'data-group' );

								return group === selectedCategory;
							}
						);
					}

					searchedIcons.show();

					if ( searchedIcons.length <= 0 ) {
						$( targetContainer ).append( window.bbProNoResults() );
					}
				}
			);
		},

		/**
		 * Register keyup event handlers and update notification text.
		 */
		handleNotificationText: function () {
			var self = this;
			$( document ).on(
				'keyup',
				'.bbpro-icon-notification-text',
				function (e) {
					e.stopImmediatePropagation();
					self.iconPreviewData.notification_text = $( this ).val();
					window.iconPreviewData                 = self.iconPreviewData;

					$( '#icon-picker-saved' ).attr( 'disabled', false );
				}
			);
		},

		/**
		 * Register keyup event handlers and update icon text.
		 */
		handleIconText: function () {
			var self = this;

			$( document ).on(
				'input',
				'.bbpro-new-icon-label',
				function (e) {
					e.stopImmediatePropagation();

					var icon_text_limit = $( this ).siblings( '.bbpro-icon-text-limit' );
					// Get the entered text.
					var enteredText = $( this ).val();

					// Check if the entered text exceeds 12 characters.
					if ( enteredText.length > 12 ) {
						icon_text_limit.addClass( 'has-limit-error' );
					} else {
						icon_text_limit.removeClass( 'has-limit-error' );
					}

					self.iconPreviewData.icon_text = $( this ).val();
					$( this ).siblings( '.bbpro-icon-text-limit' ).children( 'span' ).text( self.iconPreviewData.icon_text.length );
					window.iconPreviewData = self.iconPreviewData;

					self.updatePreviewTemplate();
					$( '#icon-picker-saved' ).attr( 'disabled', false );
				}
			);
		},

		/**
		 * Upload Icon function.
		 */
		customIconUpload: function () {
			var self = this;
			// Icon upload range percentage.
			$( document ).on(
				'change mousemove',
				'.bbpro_icon_preview .croppie-container .cr-slider',
				function () {
					var val    = $( this ).val();
					var min    = 0.0000;
					var max    = 1.5000;
					var newVal = Number( (val - min) * 100 ) / (max - min);

					// Sorta magic numbers based on size of the native UI thumb.
					newVal = parseInt( newVal ) + '%';

					$( this ).closest( '.croppie-container' ).closest( '.bbpro_icon_preview' ).find( '.cr-percentage' ).html( newVal );
				}
			);

			$( document ).on(
				'change',
				'.bbpro_custom_icon_upload',
				function () {
					var _this    = this;
					var iconSize = 200;

					self.readImageUpload(
						_this,
						function (data) {
							// Remove Uploaded Icon if not saved.
							if ( $( document ).find( '.bbpro-icon-file-upload' ).prev().find( '.bbpro_icon_preview' ).find( '.inner' ).hasClass( 'croppie-container' ) ) {
								$( document ).find( '.bbpro-icon-file-upload' ).prev().find( '.bbpro_icon_preview' ).hide().find( '.inner' ).show().html( '' );
								$( document ).find( '.bbpro-icon-file-upload' ).prev().find( '.bbpro_icon_preview' ).find( '.inner' ).removeClass( 'croppie-container' );
								$( document ).find( '.bbpro_custom_icon_upload' ).val( '' );
							}

							var container   = $( _this ).parent().prev();
							var previewIcon = $( container ).find( '.bbpro_icon_preview' );

							container.find( '.bbpro_icon_preview' ).show().find( '.inner' ).show().html( '' );
							container.addClass( 'hasPreview' );

							var uploadPreview = previewIcon.find( '.inner' ).croppie(
								{
									viewport: {
										width: 120,
										height: 120,
									},
									enableExif: true,
									boundary: {
										width: 120,
										height: 120,
									},
									enforceBoundary: false,
								}
							);

							uploadPreview.croppie(
								'bind',
								{
									url: data,
								}
							).then(
								function () {
									// Trigger range percentage.
									$( '.bbpro_icon_preview .croppie-container .cr-slider' ).trigger( 'change' );
								}
							);

							previewIcon.find( '.done' ).click(
								function (e) {
									e.preventDefault();

									var doneBtn = $( this );
									doneBtn.hide();
									doneBtn.parent().find( '.loading' ).show();

									if ( doneBtn.hasClass( 'disabled' ) ) {
										return false;
									}

									doneBtn.addClass( 'disabled' );

									// Save the blob.
									uploadPreview.croppie(
										'result',
										{
											type: 'blob',
											size: { height: iconSize, width: iconSize },
										}
									).then(
										function (resp) {
											var formdata = new FormData();

											formdata.append( 'icon', resp );
											formdata.append( 'action', 'bbpro_icon_picker_upload' );
											formdata.append( 'nonce', $( '#bbpro-upload-nonce' ).val() );

											var post = $.ajax(
												{
													url: window.bbEmotionsEditor.ajaxurl,
													type: 'POST',
													data: formdata,
													processData: false,
													contentType: false,
												}
											);

											post.done(
												function (dataRes) {
													self.addCustomIcon( dataRes.data );

													if ( container.find( '.inner' ).hasClass( 'croppie-container' ) ) {
														container.find( '.inner' ).removeClass( 'croppie-container' );
													}
												}
											);

											post.fail(
												function () {
													try {
														// eslint-disable-next-line no-alert
														alert( data.data );
													} catch ( errObj ) {
														// eslint-disable-next-line no-alert
														alert( window.bbEmotionsEditor.upload_error_notice );
													}
												}
											);

											post.always(
												function () {
													doneBtn.removeClass( 'disabled' );
													doneBtn.show();
													doneBtn.parent().find( '.loading' ).hide();
													previewIcon.hide();
													container.removeClass( 'hasPreview' );
												}
											);
										}
									);
								}
							);
						}
					);
				}
			);
		},

		/**
		 * Add custom icon and show listing.
		 *
		 * @param {Object} res Data response.
		 */
		addCustomIcon: function (res) {
			var bbproIcon = $( '<a href="javascript:void(0);" class="bbpro-icon custom" data-value="custom/' + res.id + '"><img src="' + res.url + '" /></a>' );
			$( document ).find( '#bbpro-icon-left-section .bbpro-custom-icons-list' ).append( bbproIcon );

			var tabUploadIcon         = $( '.bbpro-tab-upload-icon' ),
				tabTabUploaded        = $( '.bbpro-tab-uploaded' ),
				tabUploadIconContent  = $( '.bbpro-tab-upload-icon-content' ),
				tabTabUploadedContent = $( '.bbpro-tab-uploaded-content' );

			tabUploadIcon.removeClass( 'current' );
			tabUploadIconContent.removeClass( 'current' );
			tabTabUploaded.addClass( 'current' );
			tabTabUploadedContent.addClass( 'current' );

			$( '#bbpro-icon-right-section' ).show();
			$( '#bbpro-icon-left-section' ).removeClass( 'full-width' );
			$( '#bbpro_emotion_modal' ).find( '.bbpro-tab-uploaded' ).show();
			$( document ).find( '#bbpro-icon-left-section .bbpro-custom-icons-list' ).find( 'a:last-child' ).trigger( 'click' );
		},

		/**
		 * Updates the preview template based on the specified icon type.
		 *
		 * @param {string} iconType - The type of icon.
		 */
		updatePreviewTemplate: function( iconType ) {

			if ( typeof iconType === 'undefined' ) {
				iconType = $( document ).find( '.bbpro-emotion-type-select-filter' ).val();
			}

			// Update preview.
			if ( iconType === 'custom' ) {
				$( '.bbpro-custom-icon-picker-message-box' ).hide();
				$( '.bbpro-custom-icon-picker-preview-box' ).html( window.bbProCustomIconPreviewTemplate( window.iconPreviewData ) );
			} else if ( iconType === 'bb-icons' ) {
				$( '.bbpro-new-icon-icon-picker-message-box' ).hide();
				$( '.bbpro-new-icon-picker-preview-box' ).html( window.bbProBuddyBossIconPreviewTemplate( window.iconPreviewData ) );
			} else {
				$( '.bbpro-emoji-picker-message-box' ).hide();
				$( '.bbpro-emoji-picker-preview-box' ).html( window.bbProEmojiPreviewTemplate( window.iconPreviewData ) );
			}

			// Wait for preview to render then check for character limit
			setTimeout( function() {
				var preview_box = $( '#bbpro-icon-right-section > div:not(.bbpro-hide)' );
				var icon_text_limit = preview_box.find( '.bbpro-icon-text-limit' );
				// Get the entered text.
				var enteredText = preview_box.find( '.bbpro-new-icon-label' ).val();

				// Check if the entered text exceeds 12 characters.
				if ( enteredText.length > 12 ) {
					icon_text_limit.addClass( 'has-limit-error' );
				} else {
					icon_text_limit.removeClass( 'has-limit-error' );
				}
			}, 0 );
		},

		/**
		 * Updates the settings template based on the specified icon type.
		 *
		 * @param {string} iconType - The type of icon.
		 */
		updateSettingsTemplate: function( iconType ) {

			if ( typeof iconType === 'undefined' ) {
				iconType = $( document ).find( '.bbpro-emotion-type-select-filter' ).val();
			}

			// Update preview.
			if ( iconType === 'custom' ) {
				$( '.bbpro-custom-icon-picker-message-box' ).hide();
				$( '.bbpro-custom-icon-picker-settings-box' ).html( window.bbProCustomIconSettingTemplate( window.iconPreviewData ) );
			} else if ( iconType === 'bb-icons' ) {
				$( '.bbpro-new-icon-icon-picker-message-box' ).hide();
				$( '.bbpro-new-icon-picker-settings-box' ).html( window.bbProBuddyBossIconSettingsTemplate( window.iconPreviewData ) );
			} else {
				$( '.bbpro-emoji-picker-message-box' ).hide();
				$( '.bbpro-emoji-picker-settings-box' ).html( window.bbProEmojiSettingTemplate( window.iconPreviewData ) );
			}

			// Re-render the color picker when settings templates are loaded.
			bp.EmotionPicker.handleIconColor();
			bp.EmotionPicker.handleIconTextColor();
		},

		/**
		 * Icon preview/settings
		 */
		bbIconPreviewSettings: function () {
			var self = this;
			$( document ).on(
				'click',
				'.bbpro-icon-tag-render',
				function () {
					$( '.bbpro-icon-tag-render' ).removeClass( 'selected' );
					$( this ).addClass( 'selected' );
					$( '#icon-picker-saved' ).attr( 'disabled', false );

					var iconName  = $( this ).attr( 'data-css' );
					var iconLabel = $( this ).attr( 'data-label' );

					self.iconPreviewData.name              = iconName;
					self.iconPreviewData.icon              = iconName;
					self.iconPreviewData.icon_text         = iconLabel;
					self.iconPreviewData.type              = 'bb-icons';
					self.iconPreviewData.notification_text = '';
					self.iconPreviewData.icon_color        = self.defaultColor;
					self.iconPreviewData.text_color        = self.defaultColor;
					window.iconPreviewData                 = self.iconPreviewData;

					self.updatePreviewTemplate( 'bb-icons' );
					self.updateSettingsTemplate( 'bb-icons' );
				}
			);
		},

		/**
		 * Icon preview/settings
		 */
		bbEmojiPreviewSettings: function () {
			var self = this;
			$( document ).on(
				'click',
				'.bbpro-emoji-tag-render',
				function () {
					$( '.bbpro-emoji-tag-render' ).removeClass( 'selected' );
					$( this ).addClass( 'selected' );

					var iconText = $( this ).attr( 'data-name' );
					iconText     = iconText.replace( '-', ' ' );
					iconText     = iconText.replace(
						/\w\S*/g,
						function ( txt ) {
							return txt.charAt( 0 ).toUpperCase() + txt.substr( 1 ).toLowerCase();
						}
					);

					self.iconPreviewData.name              = $( this ).attr( 'data-name' );
					self.iconPreviewData.icon              = $( this ).attr( 'data-unicode' );
					self.iconPreviewData.icon_path         = $( this ).find( 'img' ).attr( 'src' );
					self.iconPreviewData.icon_text         = iconText;
					self.iconPreviewData.type              = 'emotions';
					self.iconPreviewData.text_color        = self.defaultColor;
					self.iconPreviewData.notification_text = '';
					window.iconPreviewData                 = self.iconPreviewData;

					$( '#icon-picker-saved' ).attr( 'disabled', false );
					self.updatePreviewTemplate( 'emotions' );
					self.updateSettingsTemplate( 'emotions' );
				}
			);
		},

		/**
		 * Render custom icon preview.
		 */
		resetPreviewSettings: function ( emojiType ) {

			if ( typeof emojiType === 'undefined' || emojiType === '' ) {
				emojiType = $( document ).find( '.bbpro-emotion-type-select-filter' ).val();
			}

			if ( 'custom' === emojiType ) {
				$( document ).find( '#bbpro_emotion_modal #bbpro-icon-left-section .bbpro-custom-icons-list' ).find( '.selected' ).removeClass( 'selected' );
				$( '.bbpro-custom-icon-picker-message-box' ).show();
				$( '#icon-picker-saved' ).attr( 'disabled', true );
				$( '.bbpro-custom-icon-picker-preview-box' ).html( '' );
				$( '.bbpro-custom-icon-picker-settings-box' ).html( '' );

				if ( $( '#bbpro_emotion_modal ' ).find( '.bbpro-custom-icons-list a.bbpro-icon' ).length <= 0 ) {
					$( '#bbpro_emotion_modal' ).find( '.bbpro-tab-upload-icon' ).trigger( 'click' );
					$( '#bbpro_emotion_modal' ).find( '.bbpro-tab-uploaded' ).hide();
				} else {
					$( '#bbpro_emotion_modal' ).find( '.bbpro-tab-uploaded' ).show();
				}
			}

			if ( 'bb-icons' === emojiType ) {
				$( document ).find( '#bbpro_emotion_modal #bbpro-icon-left-section .bbpro-icons-list' ).find( '.selected' ).removeClass( 'selected' );
				$( '.bbpro-new-icon-icon-picker-message-box' ).show();
				$( '.bbpro-new-icon-picker-preview-box' ).html( '' );
				$( '.bbpro-new-icon-picker-settings-box' ).html( '' );
			}

			if ( 'emotions' === emojiType ) {
				$( document ).find( '#bbpro_emotion_modal #bbpro-icon-left-section .icons' ).find( '.selected' ).removeClass( 'selected' );
				$( '.bbpro-emoji-picker-message-box' ).show();
				$( '.bbpro-emoji-picker-preview-box' ).html( '' );
				$( '.bbpro-emoji-picker-settings-box' ).html( '' );
			}

			$( '#icon-picker-saved' ).attr( 'disabled', true );
		},

		/**
		 * Icon picker Preview.
		 */
		customIconPreviewSettings: function () {
			var self = this;
			/**
			 * Set Icon Preview.
			 */
			$( document ).on(
				'click',
				'.bbpro-dialog-icon-picker .bbpro-custom-icons-list .bbpro-icon',
				function (e) {
					e.preventDefault();
					$( document ).find( '#bbpro_emotion_modal #bbpro-icon-left-section .bbpro-custom-icons-list' ).find( '.selected' ).removeClass( 'selected' );
					$( this ).addClass( 'selected' );

					var dataValue = $( this ).attr( 'data-value' );

					var previous_data = self.getLocalStorage( 'custom' );

					self.iconPreviewData.name              = dataValue.replace( 'custom/', '' );
					self.iconPreviewData.icon              = dataValue;
					self.iconPreviewData.icon_path         = $( this ).find( 'img' ).attr( 'src' );
					self.iconPreviewData.type              = 'custom';
					self.iconPreviewData.icon_text         = ( previous_data && 'undefined' !== typeof previous_data.icon_text ) ? previous_data.icon_text : '';
					self.iconPreviewData.notification_text = '';
					self.iconPreviewData.text_color        = ( previous_data && 'undefined' !== typeof previous_data.text_color ) ? previous_data.text_color : self.defaultColor;
					window.iconPreviewData                 = self.iconPreviewData;

					$( '#icon-picker-saved' ).attr( 'disabled', false );
					self.updatePreviewTemplate( 'custom' );
					self.updateSettingsTemplate( 'custom' );
				}
			);
		},

		/**
		 * Delete Custom Icon.
		 */
		deleteCustomIcon: function () {
			$( document ).on(
				'click',
				'.bbpro-delete-icon',
				function (e) {
					e.preventDefault();

					var r = false;

					if ( 'undefined' !== window.showNotice) {
						r = window.showNotice.warn();
					}

					if ( r ) {
						var deleteIcon  = $( this );
						var elm1        = $( '.bbpro-dialog-icon-picker' ).find( '.bbpro-custom-icons-list' ).find( '.selected' );
						var elm1DataVal = elm1.attr( 'data-value' );
						var messageBox  = $( '.bbpro-custom-icon-picker-message-box' );
						var defaultMsg  = messageBox.html();

						// Return if selected icon is custom.
						if ( true !== elm1.hasClass( 'custom' ) ) {
							return;
						}

						var current_emotion_id = 0;
						if (
							'undefined' !== typeof window.iconPreviewData &&
							'undefined' !== typeof window.iconPreviewData.id
						) {
							current_emotion_id = window.iconPreviewData.id;
						}

						var formdata = new FormData();

						formdata.append( 'action', 'bbpro_delete_custom_icon' );
						formdata.append( 'elm1_data_val', elm1DataVal );
						formdata.append( 'task', 'delete_custom_icon' );
						formdata.append( 'id', current_emotion_id );
						formdata.append( 'nonce', $( '#bbpro-delete-nonce' ).val() );

						$.ajax(
							{
								url: window.window.bbEmotionsEditor.ajaxurl,
								type: 'POST',
								data: formdata,
								processData: false,
								contentType: false,
								beforeSend: function() {
									deleteIcon.show().addClass( 'disabled' ).prop( 'diabled', true );
									deleteIcon.find( '.bbpro-delete-icon-loader' ).show();
								},
								error: function( error ) {
									messageBox.html(
										'<p class="bbpro_notice bb-reaction-error">' +
										'<i class = "bb-icon-f bb-icon-info-triangle" ></i>' +
										error.statusText +
										'</p>'
									);
									messageBox.show();

									setTimeout(
										function () {
											messageBox.html( defaultMsg );
											messageBox.hide();
											deleteIcon.show().removeClass( 'disabled' ).prop( 'disabled', false ).find( '.bbpro-delete-icon-loader' ).hide();
										},
										10000
									);
								},
								success: function( response ) {
									if ( true === response.success ) {
										elm1.remove();
										bp.EmotionPicker.resetPreviewSettings( 'custom' );
									} else {
										messageBox.html(
											'<p class="bbpro_notice bb-reaction-error">' +
											'<i class = "bb-icon-f bb-icon-info-triangle" ></i>' +
											response.data +
											'</p>'
										);
										messageBox.show();
									}

									setTimeout(
										function () {
											messageBox.html( defaultMsg );
											messageBox.hide();
											deleteIcon.show().removeClass( 'disabled' ).prop( 'disabled', false ).find( '.bbpro-delete-icon-loader' ).hide();
										},
										5000
									);
								}
							}
						);
					}
				}
			);
		},

		/**
		 * Save selected icon.
		 */
		saveSelectedIcon: function () {

			var self = this;
			/**
			 * Click on 'Save Emotion' button
			 */
			$( document ).on(
				'click',
				'.bbpro_select_icon',
				function () {
					// If editor is open for reaction button and saving icon then save and closed.
					if ( 'reactionButton' === self.editorMode ) {
						$( '#bb-reaction-button-hidden-field' ).val( self.iconPreviewData.icon );
						$( '#bb-reaction-button-chooser' ).html( '<i class="bb-icon-' + self.iconPreviewData.icon + '"></i>' );

						self.resetEditorMode( 'reactionEmotion' );

						$( '#bbpro_emotion_modal' ).css( 'display', 'none' );
						$( document ).find( 'body' ).removeClass( 'modal-open' );
						return;
					}

					var emotionType        = $( '.bbpro-emotion-type-select-filter' ).val(),
						messageBoxSelector = '.bbpro-emoji-picker-message-box',
						message            = '';

					if ( 'custom' === emotionType ) {
						messageBoxSelector = '.bbpro-custom-icon-picker-message-box';
					} else if ( 'bb-icons' === emotionType ) {
						messageBoxSelector = '.bbpro-new-icon-icon-picker-message-box';
					}

					if ( typeof self.iconPreviewData === 'undefined' ) {
						message = window.bbEmotionsEditor.no_data_found;
					} else if ( '' === self.iconPreviewData.icon_text ) {
						message = window.bbEmotionsEditor.icon_label_required;
					} else if ( self.iconPreviewData.icon_text.length > 12 ) {
						message = window.bbEmotionsEditor.icon_label__length;
					}

					if ( '' !== message ) {
						$( document ).find( messageBoxSelector ).show();
						$( document ).find( messageBoxSelector ).html(
							'<p class="bbpro_notice bb-reaction-error">' +
								'<i class = "bb-icon-f bb-icon-info-triangle" ></i>' +
								message +
							'</p>'
						);

						setTimeout(
							function () {
								$( document ).find( messageBoxSelector ).html( '' );
								$( document ).find( messageBoxSelector ).hide();
							},
							10000
						);

						return;
					}

					$( '#bbpro_emotion_modal' ).css( 'display', 'none' );
					$( document ).find( 'body' ).removeClass( 'modal-open' );

					if ( self.currentEventHandler !== null ) {
						self.currentEventHandler.parents( '.bb_emotions_item' ).replaceWith( window.bbProAddEmotion( self.iconPreviewData ) );
					}

					self.deleteLocalStorage( emotionType );
					window.iconPreviewData = self.defaultData;

					$( document ).trigger( 'bbpro-icon-selected', self.iconPreviewData );
				}
			);
		},

		/**
		 * Register and update the icon color.
		 */
		handleIconColor: function () {
			$( document ).find( '.bbpro-icon-color' ).wpColorPicker(
				{
					defaultColor: bp.EmotionPicker.defaultColor,
					change: function( event, ui ) {
						bp.EmotionPicker.iconPreviewData.icon_color = ui.color.toString();
						window.iconPreviewData 						= bp.EmotionPicker.iconPreviewData;

						bp.EmotionPicker.updatePreviewTemplate( 'bb-icons' );
						$( '#icon-picker-saved' ).attr( 'disabled', false );
					},
					clear: function() {
						bp.EmotionPicker.iconPreviewData.icon_color = bp.EmotionPicker.defaultColor;
						window.iconPreviewData 						= bp.EmotionPicker.iconPreviewData;

						bp.EmotionPicker.updatePreviewTemplate( 'bb-icons' );
						$( '#icon-picker-saved' ).attr( 'disabled', false );
					},
					palettes: [
						'#8D8D92', // Gray.
						'#EA4D3D', // Red.
						'#EA445A', // Pink.
						'#F19937', // Orange.
						'#65C465', // Green.
						'#58A7D7', // Teal.
						'#3379F6', // Blue.
						'#5756CE', // Purple.
					],
				}
			);
		},

		/**
		 * Register and update the text color.
		 */
		handleIconTextColor: function () {
			var self = this;

			$( '.bbpro-icon-text-color' ).wpColorPicker(
				{
					defaultColor: bp.EmotionPicker.defaultColor,
					change: function( event, ui ) {
						self.iconPreviewData.text_color = ui.color.toString();
						window.iconPreviewData          = self.iconPreviewData;
						bp.EmotionPicker.updatePreviewTemplate();
						$( '#icon-picker-saved' ).attr( 'disabled', false );
					},
					clear: function() {
						self.iconPreviewData.text_color = self.defaultColor;
						window.iconPreviewData          = self.iconPreviewData;

						bp.EmotionPicker.updatePreviewTemplate();
						$( '#icon-picker-saved' ).attr( 'disabled', false );
					},
					palettes: [
						'#8D8D92', // Gray.
						'#EA4D3D', // Red.
						'#EA445A', // Pink.
						'#F19937', // Orange.
						'#65C465', // Green.
						'#58A7D7', // Teal.
						'#3379F6', // Blue.
						'#5756CE', // Purple.
					],
				}
			);
		},

		/**
		 * Load buddyboss icon preview.
		 */
		loadBBIconPreviewOnEdit: function () {
			$( '.bbpro-emotion-type-select-filter' ).val( 'bb-icons' );
			$( '.bbpro-emojis-list' ).addClass( 'bbpro-hide' );
			$( '.bbpro-emojis-settings' ).addClass( 'bbpro-hide' );
			$( '#bbpro-custom-upload' ).addClass( 'bbpro-hide' );
			$( '.bbpro-custom-upload-settings' ).addClass( 'bbpro-hide' );
			$( '.bbpro-icons-list' ).removeClass( 'bbpro-hide' );
			$( '.bbpro-buddyboss-icon-settings' ).removeClass( 'bbpro-hide' );
			$( '.bbpro-icon-category-filter-select' ).show();
			$( '.bbpro-icon-search' ).show();

			// Render bb-icon category list.
			$( '.bbpro-icon-category-filter-select' ).replaceWith( window.bbProIconsCategoryList() );

			// Remove 'Selected' Class of old.
			if ( $( document ).find( '#bbpro_emotion_modal #bbpro-icon-left-section .bbpro-icons-list' ).find( 'a' ).hasClass( 'selected' ) ) {
				$( document ).find( '#bbpro_emotion_modal #bbpro-icon-left-section .bbpro-icons-list' ).find( '.selected' ).removeClass( 'selected' );
			}

			if ( $( document ).find( '#bbpro_emotion_modal #bbpro-icon-left-section .bbpro-icons-list' ).find( 'a[data-css="' + window.iconPreviewData.icon + '"]' ).length > 0 ) {
				$( document ).find( '#bbpro_emotion_modal #bbpro-icon-left-section .bbpro-icons-list' ).find( 'a[data-css="' + window.iconPreviewData.icon + '"]' ).addClass( 'selected' );
			}

			this.updatePreviewTemplate( 'bb-icons' );
			this.updateSettingsTemplate( 'bb-icons' );
		},

		loadEmojiPreviewOnEdit: function () {
			$( '.bbpro-emotion-type-select-filter' ).val( 'emotions' );
			$( '.bbpro-icons-list' ).addClass( 'bbpro-hide' );
			$( '.bbpro-buddyboss-icon-settings' ).addClass( 'bbpro-hide' );
			$( '#bbpro-custom-upload' ).addClass( 'bbpro-hide' );
			$( '.bbpro-custom-upload-settings' ).addClass( 'bbpro-hide' );
			$( '.bbpro-emojis-list' ).removeClass( 'bbpro-hide' );
			$( '.bbpro-emojis-settings' ).removeClass( 'bbpro-hide' );
			$( '.bbpro-icon-search' ).show();

			// Render emoji category list.
			var category = $( '.bbpro-icon-category-filter-select' );
			category.show();
			category.replaceWith( window.bbProEmojisCategoryList() );

			// Remove 'Selected' Class of old.
			if ( $( document ).find( '#bbpro_emotion_modal #bbpro-icon-left-section .bbpro-emojis-list' ).find( 'a' ).hasClass( 'selected' ) ) {
				$( document ).find( '#bbpro_emotion_modal #bbpro-icon-left-section .bbpro-emojis-list' ).find( '.selected' ).removeClass( 'selected' );
			}

			if ( $( document ).find( '#bbpro_emotion_modal #bbpro-icon-left-section .bbpro-emojis-list' ).find( 'a[data-name="' + window.iconPreviewData.name + '"]' ).length > 0) {
				$( document ).find( '#bbpro_emotion_modal #bbpro-icon-left-section .bbpro-emojis-list' ).find( 'a[data-name="' + window.iconPreviewData.name + '"]' ).addClass( 'selected' );
			}

			this.updatePreviewTemplate( 'emotions' );
			this.updateSettingsTemplate( 'emotions' );
		},

		/**
		 * Load custom icon preview.
		 */
		loadCustomIconPreviewOnEdit: function () {
			$( '.bbpro-emojis-list' ).addClass( 'bbpro-hide' );
			$( '.bbpro-emojis-settings' ).addClass( 'bbpro-hide' );
			$( '.bbpro-icons-list' ).addClass( 'bbpro-hide' );
			$( '.bbpro-buddyboss-icon-settings' ).addClass( 'bbpro-hide' );
			$( '#bbpro-custom-upload' ).removeClass( 'bbpro-hide' );
			$( '.bbpro-custom-upload-settings' ).removeClass( 'bbpro-hide' );
			$( '.bbpro-icon-category-filter-select' ).hide();
			$( '.bbpro-icon-search' ).hide();

			$( '#bbpro_emotion_modal li.bbpro-tab-uploaded' ).trigger( 'click' );

			// Remove 'Selected' Class of old.
			if ($( document ).find( '#bbpro_emotion_modal #bbpro-icon-left-section .bbpro-custom-icons-list' ).find( 'a' ).hasClass( 'selected' )) {
				$( document ).find( '#bbpro_emotion_modal #bbpro-icon-left-section .bbpro-custom-icons-list' ).find( '.selected' ).removeClass( 'selected' );
			}

			if ($( document ).find( '#bbpro_emotion_modal #bbpro-icon-left-section .bbpro-custom-icons-list' ).find( 'a[data-value="custom/' + window.iconPreviewData.name + '"]' ).length > 0) {
				$( document ).find( '#bbpro_emotion_modal #bbpro-icon-left-section .bbpro-custom-icons-list' ).find( 'a[data-value="custom/' + window.iconPreviewData.name + '"]' ).addClass( 'selected' );
			}

			this.updatePreviewTemplate( 'custom' );
			this.updateSettingsTemplate( 'custom' );
		},

		/**
		 * Icon picker tabs.
		 */
		customIconPickerTabs: function () {
			// Custom icon uploaded tab click event.
			$( document ).on(
				'click',
				'li.bbpro-tab-uploaded',
				function (e) {
					e.preventDefault();
					const tabId = $( this ).attr( 'data-tab' );

					$( '#bbpro_emotion_modal li.bbpro-tab-upload-icon' ).removeClass( 'current' );
					$( '#bbpro-tab-upload-icon' ).removeClass( 'current' );
					$( this ).addClass( 'current' );
					const tabSelector = $( '#' + tabId );
					tabSelector.addClass( 'current' );

					$( '#bbpro-icon-right-section' ).show();
					$( '#bbpro-icon-left-section' ).removeClass( 'full-width' );
					$( '.bbpro_select_icon' ).attr( 'data-screen', 'custom' );
				}
			);

			// New custom icon uploader tab click event.
			$( document ).on(
				'click',
				'li.bbpro-tab-upload-icon',
				function (e) {
					e.preventDefault();
					const tabId = $( this ).attr( 'data-tab' );

					$( '#bbpro_emotion_modal li.bbpro-tab-uploaded' ).removeClass( 'current' );
					$( '#bbpro-tab-uploaded' ).removeClass( 'current' );
					$( this ).addClass( 'current' );
					const tabSelector = $( '#' + tabId );
					tabSelector.addClass( 'current' );

					$( '#bbpro-icon-right-section' ).hide();
					$( '#bbpro-icon-left-section' ).addClass( 'full-width' );
					$( '.bbpro_select_icon' ).attr( 'disabled', true );
				}
			);
		},

		readImageUpload: function (input, callback) {
			const container = $( input ).closest( '.upload_field' );

			if ( input.files && input.files[0] ) {
				const uploadedFile       = input.files[0],
					fileSize             = uploadedFile.size,
					allowedFileExtension = [ 'image/png' ],
					fileType             = uploadedFile.type;

				if ( allowedFileExtension.indexOf( fileType ) <= -1 ) {
					// eslint-disable-next-line no-alert.
					alert( window.bbEmotionsEditor.invalid_upload_notice );

					if (
						'' !== container.find( '.bbpro_upload_preview' ).attr( 'src' ) &&
						'undefined' !== typeof container.find( '.bbpro_upload_preview' ).attr( 'src' )
					) {
						container.find( '.delete' ).show();
						container.find( '.replace' ).show();
					}

					return false;
				}

				// Checks the file more than 400KB.
				if ( fileSize > 409600 ) {
					// eslint-disable-next-line no-alert.

					alert( window.bbEmotionsEditor.max_upload_size_notice );

					if (
						'' !== container.find( '.bbpro_upload_preview' ).attr( 'src' ) &&
						'undefined' !== typeof container.find( '.bbpro_upload_preview' ).attr( 'src' )
					) {
						container.find( '.delete' ).show();
						container.find( '.replace' ).show();
					}

					return false;
				}

				const reader = new FileReader();

				reader.onload = function (event) {
					// get the uploaded image real height & width.
					const _URL = window.URL || window.webkitURL;
					const img  = new Image();

					img.onload = function () {
						if ( fileType === 'image/svg+xml' ) {
							// SVG upload to png.
							const canvas  = document.createElement( 'canvas' );
							const context = canvas.getContext( '2d' );

							if ( img.width < 100 && img.height < 100 ) {
								canvas.width  = 300;
								canvas.height = 300;
							} else {
								canvas.width  = img.width;
								canvas.height = img.height;
							}

							context.drawImage( img, 0, 0, img.width, img.height, 0, 0, canvas.width, canvas.height );
							callback( canvas.toDataURL( 'image/png' ), { width: canvas.width, height: canvas.height } );
						} else {
							// Image file.
							callback( event.target.result, { width: this.width, height: this.height } );
						}
					};

					img.src = _URL.createObjectURL( input.files[0] );
				};

				reader.readAsDataURL( input.files[0] );
			} else if ('' !== container.find( '.bbpro_upload_preview' ).attr( 'src' ) && 'undefined' !== typeof container.find( '.bbpro_upload_preview' ).attr( 'src' )) {
				container.find( '.delete' ).show();
				container.find( '.replace' ).show();
			}
		},

		loadDefaultPreviewOnAdd: function () {
			$( '.bbpro-emotion-type-select-filter' ).val( 'emotions' );
			$( '.bbpro-icons-list' ).addClass( 'bbpro-hide' );
			$( '.bbpro-buddyboss-icon-settings' ).addClass( 'bbpro-hide' );
			$( '#bbpro-custom-upload' ).addClass( 'bbpro-hide' );
			$( '.bbpro-custom-upload-settings' ).addClass( 'bbpro-hide' );
			$( '.bbpro-emojis-list' ).removeClass( 'bbpro-hide' );
			$( '.bbpro-emojis-settings' ).removeClass( 'bbpro-hide' );
			$( '.bbpro-icon-search' ).show();

			// Render emoji category list.
			var category = $( '.bbpro-icon-category-filter-select' );
			category.show();
			category.replaceWith( window.bbProEmojisCategoryList() );

			// Remove 'Selected' Class of old.
			if ( $( document ).find( '#bbpro_emotion_modal #bbpro-icon-left-section .bbpro-emojis-list' ).find( 'a' ).hasClass( 'selected' ) ) {
				$( document ).find( '#bbpro_emotion_modal #bbpro-icon-left-section .bbpro-emojis-list' ).find( '.selected' ).removeClass( 'selected' );
			}
		},

		saveLocalStorage: function( key, val ) {
			localStorage.setItem( key, JSON.stringify( val ) );
		},

		getLocalStorage: function( key ) {
			var retrievedData = localStorage.getItem( key );
			return JSON.parse( retrievedData );
		},

		deleteLocalStorage: function( key ) {
			localStorage.removeItem( key );
		}
	};

	// Launch Reaction Emotion Picker.
	bp.EmotionPicker.start();

})( bp, jQuery );
