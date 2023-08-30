/* jshint browser: true */
/* global BP_Uploader, BP_Confirm, bp */
/* @version 1.0.0 */
window.bp = window.bp || {};

(function ( exports, $ ) {

	/**
	 * [OneSignal description]
	 *
	 * @type {Object}
	 */
	bp.OneSignal_Common = {
		/**
		 * [start description]
		 *
		 * @return {[type]} [description]
		 */
		start: function () {
			this.setupGlobals();

			// Listen to events ("Add hooks!").
			this.addListeners();

			this.validateWebPush();

			this.validateAutoPrompt();

			this.validateSoftPrompt();

			this.uploadAttachment();
		},

		/**
		 * [setupGlobals description]
		 *
		 * @return {[type]} [description]
		 */
		setupGlobals: function() {
			jQuery(
				function() {
					bp.OneSignal_Common.validateWebPush();
					bp.OneSignal_Common.validateAutoPrompt();
					bp.OneSignal_Common.validateSoftPrompt();
				}
			);
		},

		/**
		 * [addListeners descriptiong]
		 */
		addListeners: function () {

			$( document ).on( 'change', '#bb-onesignal-enable-soft-prompt', this.validateSoftPrompt.bind( this ) );
			$( document ).on( 'change', '#bb-onesignal-request-permission', this.validateRequestPermission.bind( this ) );
			$( document ).on( 'change', '#bb-onesignal-enabled-web-push', this.enableWebPush.bind( this ) );

			$( document ).on( 'click', '.bb-onesignal-enable-soft-prompt .bb-learn-more', this.softPromptLearnMore.bind( this ) );

			$( document ).on( 'keyup', '#bb-onesignal-enable-soft-prompt-message', this.updateSoftPromptMessage.bind( this ) );

			$( document ).on( 'keyup', '#bb-onesignal-enable-soft-prompt-allow-button', this.updateSoftPromptAllowButton.bind( this ) );
			$( document ).on( 'keyup', '#bb-onesignal-enable-soft-prompt-cancel-button', this.updateSoftPromptCancelButton.bind( this ) );
			$( document ).on( 'click', '.bb-hide-pw', this.TogglePasswordField.bind( this ) );

		},

		validateSoftPrompt: function () {

			if ( jQuery( '#bb-onesignal-request-permission' ).is( ':checked' ) && jQuery( '#bb-onesignal-enabled-web-push' ).is( ':checked' ) && $( '#bb-onesignal-enable-soft-prompt' ).is( ':checked' ) ) {
				$( '.bb-onesignal-enable-soft-prompt-extra-fields' ).removeClass( 'bp-hide' );
			} else {
				$( '.bb-onesignal-enable-soft-prompt-extra-fields' ).addClass( 'bp-hide' );
			}
		},

		validateRequestPermission: function ( event ) {
			if ( $( event.currentTarget ).is( ':checked' ) ) {
				$( 'select[name="bb-onesignal-permission-validate"]' ).prop( 'disabled', false );
			} else {
				$( 'select[name="bb-onesignal-permission-validate"]' ).prop( 'disabled', true );
			}

			bp.OneSignal_Common.validateAutoPrompt();

		},

		enableWebPush: function() {
			bp.OneSignal_Common.validateWebPush();
		},

		validateWebPush: function() {
			if ( jQuery( '#bb-onesignal-enabled-web-push' ).is( ':checked' ) ) {
				jQuery( '.bb-onesignal-request-permission, .bb-onesignal-default-notification-icon, .bb-onesignal-web-push-skip-active-members' ).removeClass( 'bp-hide' );

				if ( jQuery( '#bb-onesignal-request-permission' ).is( ':checked' ) ) {
					jQuery( '.bb-onesignal-enable-soft-prompt' ).removeClass( 'bp-hide' );

					if ( jQuery( '#bb-onesignal-enable-soft-prompt' ).is( ':checked' ) ) {
						jQuery( '.bb-onesignal-enable-soft-prompt-extra-fields' ).removeClass( 'bp-hide' );
					}
				} else {
					jQuery( '.bb-onesignal-enable-soft-prompt, .bb-onesignal-enable-soft-prompt-extra-fields' ).addClass( 'bp-hide' );
				}

			} else {
				jQuery( '.bb-onesignal-request-permission, .bb-onesignal-default-notification-icon, .bb-onesignal-enable-soft-prompt, .bb-onesignal-enable-soft-prompt-extra-fields, .bb-onesignal-web-push-skip-active-members' ).addClass( 'bp-hide' );
			}
		},

		validateAutoPrompt: function() {
			if ( jQuery( '#bb-onesignal-request-permission' ).is( ':checked' ) && jQuery( '#bb-onesignal-enabled-web-push' ).is( ':checked' ) ) {
				jQuery( '.bb-onesignal-enable-soft-prompt' ).removeClass( 'bp-hide' );

				if ( jQuery( '#bb-onesignal-enable-soft-prompt' ).is( ':checked' ) ) {
					jQuery( '.bb-onesignal-enable-soft-prompt-extra-fields' ).removeClass( 'bp-hide' );
				}
			} else {
				jQuery( '.bb-onesignal-enable-soft-prompt, .bb-onesignal-enable-soft-prompt-extra-fields' ).addClass( 'bp-hide' );
			}
		},

		softPromptLearnMore: function () {

			$( '.bb-onesignal-enable-soft-prompt .small-text' ).addClass( 'bp-hide' );
			$( '.bb-onesignal-enable-soft-prompt .full-text' ).removeClass( 'bp-hide' );
			return false;
		},

		updateSoftPromptMessage: function ( event ) {
			var current_target  = $( event.currentTarget ),
				placeholder_val = current_target.attr( 'placeholder' ),
				current_val     = current_target.val();

			if ( current_val.length < 1 ) {
				current_val = placeholder_val;
			}

			$( '.bb-onesignal-enable-soft-prompt-extra-fields .soft-prompt-text' ).text( current_val );
			bp.OneSignal_Common.validateCharacterLimit( event );
		},

		updateSoftPromptAllowButton: function ( event ) {
			var current_target  = $( event.currentTarget ),
				placeholder_val = current_target.attr( 'placeholder' ),
				current_val     = current_target.val();

			if ( current_val.length < 1 ) {
				current_val = placeholder_val;
			}

			$( '.bb-onesignal-enable-soft-prompt-extra-fields .allow-soft-prompt-button' ).text( current_val );
			bp.OneSignal_Common.validateCharacterLimit( event );
		},

		updateSoftPromptCancelButton: function ( event ) {
			var current_target  = $( event.currentTarget ),
				placeholder_val = current_target.attr( 'placeholder' ),
				current_val     = current_target.val();

			if ( current_val.length < 1 ) {
				current_val = placeholder_val;
			}

			$( '.bb-onesignal-enable-soft-prompt-extra-fields .cancel-soft-prompt-button' ).text( current_val );
			bp.OneSignal_Common.validateCharacterLimit( event );
		},

		uploadAttachment: function () {

			var upload_button = '';

			$( '.bbpro-upload-attachment' ).on(
				'click',
				'.bb-attachment-user-edit',
				function () {
					upload_button        = $( this );
					var feedback_element = upload_button.parents( '.bbpro-upload-attachment' ).find( '.bbpro-attachment-status' );
					if ( feedback_element.length > 0 && feedback_element.find( '.bbpro-attachment-upload-feedback' ).length ) {
						feedback_element.find( '.bbpro-attachment-upload-feedback' ).removeClass( 'success error' ).html( '' );
						feedback_element.hide();
					}

					setTimeout(
						function () {
							if ( jQuery( document ).find( '#TB_ajaxContent .bp-avatar-status .error' ).length > 0 ) {
								jQuery( document ).find( '#TB_ajaxContent .bp-avatar-status' ).empty();
								jQuery( document ).find( '#TB_ajaxContent .bp-avatar-status' ).append( '<p class="warning">' + BP_Uploader.strings.avatar_size_warning + '<br></p>' );
							} else if ( jQuery( document ).find( '#TB_ajaxContent .bp-avatar-status .warning' ).length <= 0 ) {
								jQuery( document ).find( '#TB_ajaxContent .bp-avatar-status' ).empty();
								jQuery( document ).find( '#TB_ajaxContent .bp-avatar-status' ).append( '<p class="warning">' + BP_Uploader.strings.avatar_size_warning + '<br></p>' );
							}
						},
						50
					);
				}
			);

			$( '.bbpro-upload-attachment' ).on(
				'click',
				'.bb-attachment-user-edit, .bbpro-img-remove-button',
				function () {
					var main_wrap = $( this ).parents( '.bbpro-upload-attachment' );
					if ( main_wrap.length > 0 ) {
						var object = main_wrap.data( 'object' );
						BP_Uploader.settings.defaults.multipart_params.bp_params.object = object;
					}
				}
			);

			$( document ).ajaxSuccess(
				function ( event, xhr, settings ) {
					if (
						settings.hasOwnProperty( 'data' ) &&
						'string' === typeof settings.data &&
						settings.data.search( 'action=bp_avatar_set' ) > 0 &&
						settings.data.search( 'object=notification' )
					) {

						var response = JSON.parse( xhr.responseText );
						var feedback = BP_Uploader.strings.default_error;

						if ( 'undefined' !== typeof response.success && false === response.success ) {
							if ( 'undefined' !== typeof response.data && 'undefined' !== typeof response.data.feedback_code ) {
								feedback = BP_Uploader.strings.feedback_messages[ response.data.feedback_code ];
							}

							var feedback_element = upload_button.parents( '.bbpro-upload-attachment' ).find( '.bbpro-attachment-status' );
							if ( feedback_element.length > 0 && feedback_element.find( '.bbpro-attachment-upload-feedback' ).length ) {
								feedback_element.find( '.bbpro-attachment-upload-feedback' ).removeClass( 'success error' ).addClass( 'error' ).html( feedback );
								feedback_element.show();
							}
						}

						$( event.currentTarget.activeElement ).find( '#TB_closeWindowButton' ).trigger( 'click' );
						upload_button.html( upload_button.data( 'upload' ) );
					}
				}
			);

			$( document ).ajaxError(
				function ( event, jqxhr, settings ) {
					if (
						settings.hasOwnProperty( 'data' ) &&
						'string' === typeof settings.data &&
						settings.data.search( 'action=bp_avatar_set' ) > 0 &&
						settings.data.search( 'object=notification' )
					) {
						$( event.currentTarget.activeElement ).find( '#TB_closeWindowButton' ).trigger( 'click' );
						upload_button.html( upload_button.data( 'upload' ) );
					}
				}
			);

			$( document ).ajaxSend(
				function ( event, jqxhr, settings ) {
					if (
						settings.hasOwnProperty( 'data' ) &&
						'string' === typeof settings.data &&
						settings.data.search( 'action=bp_avatar_set' ) > 0 &&
						settings.data.search( 'object=notification' )
					) {
						$( document ).find( '#TB_closeWindowButton' ).trigger( 'click' );
						upload_button.html( upload_button.data( 'uploading' ) );
					}
				}
			);

			var img      = document.querySelectorAll( '.bbpro-attachment-upload-container .bb-upload-preview' ),
				observer = new MutationObserver(
					function ( changes ) {
						changes.forEach(
							function ( change ) {
								if ( change.attributeName.includes( 'src' ) ) {
									var attr_src = $(change.target).attr('src');
									if ( '' === attr_src ) {
										$( change.target ).addClass( 'bp-hide' );
										$( change.target ).next( '.bb-default-custom-avatar-field' ).val( '' );
										$( change.target ).parents( '.bbpro-upload-attachment' ).find( '.bbpro-img-remove-button' ).addClass( 'bp-hide' );
									} else {
										$( change.target ).removeClass( 'bp-hide' );
										$( change.target ).next( '.bb-default-custom-avatar-field' ).val( attr_src );
										$( change.target ).parents( '.bbpro-upload-attachment' ).find( '.bbpro-img-remove-button' ).removeClass( 'bp-hide' );
									}
								}
							}
						);
					}
				);

			img.forEach(
				function ( element ) {
					observer.observe( element, { attributes: true } );
				}
			);

			$( '.bbpro-upload-attachment' ).on(
				'click',
				'a.bbpro-img-remove-button',
				function ( e ) {
					e.preventDefault();

					if ( confirm( BP_Confirm.are_you_sure ) ) {
						var $this                   = $( this ),
							avatarContainer         = $this.parents( 'tr' ),
							avatarPreviewContainer  = avatarContainer.find( '.bbpro-upload-attachment' ),
							avatarFeedbackContainer = avatarContainer.find( '.bbpro-attachment-status' ),
							avatarItemID            = BP_Uploader.settings.defaults.multipart_params.bp_params.item_id,
							avatarObject            = BP_Uploader.settings.defaults.multipart_params.bp_params.object;

						$this.html( $this.data( 'removing' ) );
						avatarFeedbackContainer.hide();
						avatarFeedbackContainer.find( '.bp-feedback' ).removeClass( 'success error' );

						// Remove the avatar !
						bp.ajax.post(
							'bp_avatar_delete',
							{
								json: true,
								item_id: avatarItemID,
								item_type: BP_Uploader.settings.defaults.multipart_params.bp_params.item_type,
								object: avatarObject,
								nonce: BP_Uploader.settings.defaults.multipart_params.bp_params.nonces.remove
							}
						).done(
							function ( response ) {
								$this.html( $this.data( 'remove' ) );

								// Update each avatars of the page.
								$( '.' + avatarObject + '-' + response.item_id + '-avatar' ).each(
									function () {
										$( this ).prop( 'src', response.avatar );
									}
								);

								// Hide image preview when avatar deleted.
								avatarPreviewContainer.find( '.bb-upload-container img' ).prop( 'src', response.avatar ).addClass( 'bp-hide' );

								setTimeout(
									function () {
										// Hide 'Remove' button when avatar deleted.
										if ( avatarPreviewContainer.find( '.bbpro-img-remove-button' ).length ) {
											avatarPreviewContainer.find( '.bbpro-img-remove-button' ).addClass( 'bp-hide' );
										}
									},
									50
								);
							}
						).fail(
							function ( response ) {
								var feedback     = BP_Uploader.strings.default_error,
									feedbackType = 'error';

								$this.html( $this.data( 'remove' ) );

								if ( ! _.isUndefined( response ) ) {
									feedback = BP_Uploader.strings.feedback_messages[ response.feedback_code ];
								}

								// Show feedback.
								avatarFeedbackContainer.find( '.bp-feedback' ).removeClass( 'success error' ).addClass( feedbackType ).find( 'p' ).html( feedback );
								avatarFeedbackContainer.show();
							}
						);
					}
				}
			);
		},

		validateCharacterLimit: function ( event ) {
			var $current       = $( event.currentTarget );
			var characterCount = $current.val().length;
			$current.parent().find( '.description .current' ).text( characterCount );
			if ( characterCount === 0 ) {
				$current.parent().find( '.description:not(.soft_prompt_label_header)' ).addClass( 'bp-hide' );
			} else {
				$current.parent().find( '.description:not(.soft_prompt_label_header)' ).removeClass( 'bp-hide' );
			}
		},

		TogglePasswordField: function( event ) {
			var current_item = $( event.currentTarget ),
				pass_field   = current_item.parent( '.password-toggle' ).find( 'input' );

			if ( 'password' === pass_field.attr( 'type' ) ) {
				pass_field.attr( 'type', 'text' );
			} else {
				pass_field.attr( 'type', 'password' );
			}
		}

	};

	// Launch OneSignal Common.
	bp.OneSignal_Common.start();

})( bp, jQuery );
