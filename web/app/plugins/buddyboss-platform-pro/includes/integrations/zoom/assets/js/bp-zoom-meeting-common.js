/* jshint browser: true */
/* global bp, bpZoomMeetingCommonVars */
/* @version 1.0.0 */
window.bp = window.bp || {};

( function( exports, $ ) {

	/**
	 * [Zoom description]
	 *
	 * @type {Object}
	 */
	bp.Zoom_Common = {
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
			this.account_email_xhr = null;
			this.secret_token_xhr  = null;
			this.credential_xhr    = null;
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {
			$( document ).on( 'change', '#bp-edit-group-zoom', this.toggleGroupSettings.bind( this ) );
			$( document ).on( 'click', '.bp-step-nav li > a, .bp-step-actions > span', this.bpStepsNavigate.bind( this ) );
			$( window ).on( 'keyup', this.zoomInstructionNavigate.bind( this ) );
			if ( $( document ).find( '.bb-group-zoom-settings-container' ).length ) {
				$( document ).on( 'click', 'form#group-settings-form button.bb-save-settings', this.zoomSettingsSave.bind( this ) );
			}

			if (typeof jQuery.fn.magnificPopup !== 'undefined') {
				jQuery( '.show-zoom-instructions' ).magnificPopup(
					{
						type: 'inline',
						midClick: true
					}
				);
			}

			// Block settings.
			$( document ).on( 'click', '.bb-zoom-setting-tab .bb-zoom-setting-tabs a', this.switchedTab.bind( this ) );

			// Group zoom settings.
			$( document ).on( 'change', '#bb-group-zoom-s2s-account-id, #bb-group-zoom-s2s-client-id, #bb-group-zoom-s2s-client-secret', this.fetchZoomAccounts.bind( this ) );
			$( document ).on( 'click', '.bb-pro-tabs-list li', this.switchedTab.bind( this ) );

			// Admin group zoom settings.
			$( document ).on( 'change', '#s2s-account-id, #s2s-client-id, #s2s-client-secret', this.fetchZoomAccounts.bind( this ) );
			$( document ).on( 'change', '.postbox-container #bp-edit-group-zoom', this.toggleAdminGroupSettings.bind( this ) );
			$( document ).on( 'change', '.postbox-container input[name="bp-group-zoom-connection-type"]', this.toggleAdminGroupJWTNotice.bind( this ) );

			// Group wizard popup.
			$( document ).on( 'change', '#bb-group-zoom-s2s-secret-token-popup', this.updateGroupSecretToken.bind( this ) );
			$( document ).on( 'change', '#bb-group-zoom-s2s-account-id-popup, #bb-group-zoom-s2s-client-id-popup, #bb-group-zoom-s2s-client-secret-popup', this.fetchZoomAccounts.bind( this ) );
			$( document ).on( 'click', '.bp-zoom-group-show-instructions .save-settings', this.submitGroupZoomWizard.bind( this ) );
			$( document ).on( 'keyup change', '.zoom-group-instructions-cloned-input', this.copyInputData.bind( this ) );
			$( document ).on( 'keyup change', '.zoom-group-instructions-main-input', this.copyMainInputData.bind( this ) );

			$( window ).on( 'load', function() {
				if ( '' !== window.location.hash ) {
					if ( 0 < $( 'a[href="' + window.location.hash + '"]' ).length && 'undefined' !== typeof jQuery.fn.magnificPopup ) {
						$( 'a[href="' + window.location.hash + '"]' ).trigger( 'click' );
						window.location.hash = '';
					}
				}
			});
		},

		switchedTab: function ( e ) {
			var $clickedTab = $( e.currentTarget ),
				tab_val 	= $clickedTab.data( 'value' );

			$( 'input[name="bb-zoom-tab"]' ).val( tab_val );
		},

		toggleGroupSettings: function( e ) {
			var target = $( e.target );
			e.preventDefault();

			if ( target.is( ':checked' ) ) {
				$( 'form[name="group-settings-form"] .bb-zoom-setting-tab' ).removeClass( 'bp-hide' );
			} else {
				$( 'form[name="group-settings-form"] .bb-zoom-setting-tab' ).addClass( 'bp-hide' );
			}
		},

		bpStepsNavigate: function( e ) {

			e.preventDefault();

			var target = $( e.currentTarget );

			if ( target.closest( '.bp-step-nav' ).length ) {
				target.closest( 'li' ).addClass( 'selected' ).siblings().removeClass( 'selected' );
				target.closest( '.bp-step-nav-main' ).find( '.bp-step-block' + target.attr( 'href' ) ).addClass( 'selected' ).siblings().removeClass( 'selected' );
			} else if ( target.closest( '.bp-step-actions' ).length ) {
				var activeBlock = target.closest( '.bp-step-nav-main' ).find( '.bp-step-block.selected' );
				var activeTab   = target.closest( '.bp-step-nav-main' ).find( '.bp-step-nav li.selected' );
				if ( target.hasClass( 'bp-step-prev' ) ) {
					activeBlock.removeClass( 'selected' ).prev().addClass( 'selected' );
					activeTab.removeClass( 'selected' ).prev().addClass( 'selected' );
				} else if ( target.hasClass( 'bp-step-next' ) ) {
					activeBlock.removeClass( 'selected' ).next().addClass( 'selected' );
					activeTab.removeClass( 'selected' ).next().addClass( 'selected' );
				}
			}

			// Hide Next/Prev Buttons if first or last tab is active.
			var bpStepsLength = target.closest( '.bp-step-nav-main' ).find( '.bp-step-nav li' ).length;
			if ( target.closest( '.bp-step-nav-main' ).find( '.bp-step-nav li.selected' ).index() === 0 ) {
				target.closest( '.bp-step-nav-main' ).find( '.bp-step-actions .bp-step-prev' ).hide();
			} else {
				target.closest( '.bp-step-nav-main' ).find( '.bp-step-actions .bp-step-prev' ).show();
			}

			if ( target.closest( '.bp-step-nav-main' ).find( '.bp-step-nav li.selected' ).index() === bpStepsLength - 1 ) {
				target.closest( '.bp-step-nav-main' ).addClass( 'last-tab' ).find( '.bp-step-actions .bp-step-next' ).hide();
			} else {
				target.closest( '.bp-step-nav-main' ).removeClass( 'last-tab' ).find( '.bp-step-actions .bp-step-next' ).show();
			}
		},

		zoomSettingsSave: function( e ) {
			$( e.target ).addClass( 'loading' );
		},

		zoomInstructionNavigate: function( e ) {

			if ( $( '.bp-zoom-group-show-instructions' ).length ) {

				if ( e.keyCode === 39 ) {
					$( '.bp-zoom-group-show-instructions .bp-step-actions .bp-step-next:visible' ).trigger( 'click' );
				} else if ( e.keyCode === 37 ) {
					$( '.bp-zoom-group-show-instructions .bp-step-actions .bp-step-prev:visible' ).trigger( 'click' );
				}
			}

		},

		fetchZoomAccounts: function ( e ) {
			var account_id     = $( '#s2s-account-id' ).val(),
				client_id      = $( '#s2s-client-id' ).val(),
				client_secret  = $( '#s2s-client-secret' ).val(),
				$body          = $( 'body' ),
				$account_field = $( document ).find( '.bb-zoom_account-email #account-email' ),
				group_id       = 0;

			if ( $body.hasClass( 'groups' ) && $body.hasClass( 'zoom' ) ) {
				var is_wizard = '';
				if ( 0 < $( e.target ).closest( '.bb-group-zoom-wizard-credentials' ).length ) {
					is_wizard = '-popup';
				}

				group_id       = $( '#group-id' ).val();
				$account_field = $( '#bb-group-zoom-s2s-api-email' );
				account_id     = $( '#bb-group-zoom-s2s-account-id' + is_wizard ).val();
				client_id      = $( '#bb-group-zoom-s2s-client-id' + is_wizard ).val();
				client_secret  = $( '#bb-group-zoom-s2s-client-secret' + is_wizard ).val();
			}

			e.preventDefault();

			if ( '' === account_id || '' === client_id || '' === client_secret ) {
				return false;
			}

			$( document ).find( '.bb-zoom_account-email' ).addClass( 'loading' );

			if ( bp.Zoom_Common.account_email_xhr ) {
				bp.Zoom_Common.account_email_xhr.abort();
			}

			bp.Zoom_Common.account_email_xhr = $.ajax(
				{
					type: 'POST',
					url: bpZoomMeetingCommonVars.ajax_url,
					data: {
						action: 'zoom_api_get_account_emails',
						account_id: account_id,
						client_id: client_id,
						client_secret: client_secret,
						group_id: group_id,
						_nonce: bpZoomMeetingCommonVars.fetch_account_nonce
					},
					success: function ( response ) {
						if ( typeof response.data !== 'undefined' && response.data.email_accounts ) {
							$( document ).find( '.bb-zoom_account-email' ).removeClass( 'loading' );
							$account_field.html( response.data.email_accounts );

							var $wizard_account_email = '';
							if ( 0 < group_id ) {
								$wizard_account_email = $( '#bb-group-zoom-s2s-api-email-popup' );
								$wizard_account_email.html( response.data.email_accounts );
							}

							if ( '' !== response.data.field_disabled ) {
								$account_field.addClass( response.data.field_disabled );

								if ( '' !== $wizard_account_email ) {
									$wizard_account_email.addClass( response.data.field_disabled );
								}
							} else {
								$account_field.removeClass( 'is-disabled' );

								if ( '' !== $wizard_account_email ) {
									$wizard_account_email.removeClass( 'is-disabled' );
								}
							}
						}
					}
				}
			);
		},

		toggleAdminGroupSettings: function( e ) {
			var target = $( e.target );
			e.preventDefault();

			if ( target.is( ':checked' ) ) {
				$( '#bp-group-zoom-settings-connection-type, #bp-group-zoom-settings-additional' ).removeClass( 'bp-hide' );
			} else {
				$( '#bp-group-zoom-settings-connection-type, #bp-group-zoom-settings-additional' ).addClass( 'bp-hide' );
			}
		},

		toggleAdminGroupJWTNotice: function ( e ) {
			e.preventDefault();

			if ( 'group' === $( 'input[name="bp-group-zoom-connection-type"]:checked' ).val() ) {
				$( '#bb-zoom-group-admin-jwt-notice' ).removeClass( 'bp-hide' );
			} else {
				$( '#bb-zoom-group-admin-jwt-notice' ).addClass( 'bp-hide' );
			}
		},

		updateGroupSecretToken: function( e ) {
			var target       = $( e.target ),
				group_id     = $( '#group-id' ).val(),
				secret_token = target.val();
			e.preventDefault();

			if ( '' === secret_token || '' === group_id ) {
				return false;
			}

			if ( bp.Zoom_Common.secret_token_xhr ) {
				bp.Zoom_Common.secret_token_xhr.abort();
			}

			bp.Zoom_Common.secret_token_xhr = $.ajax(
				{
					type: 'POST',
					url: bpZoomMeetingCommonVars.ajax_url,
					data: {
						action: 'zoom_group_update_secret_token',
						secret_token: secret_token,
						group_id: group_id,
						_nonce: bpZoomMeetingCommonVars.update_secret_token_nonce
					},
					success: function () {}
				}
			);
		},

		submitGroupZoomWizard: function ( e ) {
			var account_id    = $( '#bb-group-zoom-s2s-account-id-popup' ).val(),
				client_id     = $( '#bb-group-zoom-s2s-client-id-popup' ).val(),
				client_secret = $( '#bb-group-zoom-s2s-client-secret-popup' ).val(),
				account_email = $( '#bb-group-zoom-s2s-api-email-popup' ).val(),
				target        = $( e.target ),
				group_id      = $( '#group-id' ).val();

			e.preventDefault();

			if ( bp.Zoom_Common.credential_xhr ) {
				bp.Zoom_Common.credential_xhr.abort();
			}

			target.addClass( 'loading' ).css( 'pointer-events', 'none' );

			bp.Zoom_Common.credential_xhr = $.ajax(
				{
					type: 'POST',
					url: bpZoomMeetingCommonVars.ajax_url,
					data: {
						action: 'zoom_api_submit_group_zoom_credentials',
						account_id: account_id,
						client_id: client_id,
						client_secret: client_secret,
						account_email: account_email,
						group_id: group_id,
						_nonce: bpZoomMeetingCommonVars.submit_zoom_wizard_nonce
					},
					success: function ( response ) {
						target.removeClass( 'loading' ).css( 'pointer-events', '' );

						if ( typeof response.data !== 'undefined' ) {

							if ( 'success' === response.data.type ) {

								// Enabled the group zoom and show fields.
								if ( ! $( '#bp-edit-group-zoom' ).is( ':checked' ) ) {
									$( '#bp-edit-group-zoom' ).prop( 'checked', true );
									$( '.bb-zoom-setting-tab' ).removeClass( 'bp-hide' );
								}

								// Hide side-wide notice.
								$( '.group-zoom-sidewide-deprecated-notice' ).remove();

								// Close wizard.
								if ( 'undefined' !== typeof jQuery.fn.magnificPopup ) {
									$.magnificPopup.close();
									$( 'body' ).css( 'overflow', '' );
								}
							}

							if ( response.data.notice ) {
								$( '.bb-group-zoom-s2s-notice' ).html( response.data.notice );
							}
						}
					}
				}
			);
		},

		copyInputData: function( e ) {
			if ( 'SELECT' === $( e.currentTarget ).prop( 'tagName' ) ) {
				$( document ).find( 'select[name=' + $( e.currentTarget ).attr( 'name' ).replace( '-popup','' ) + ']' ).val( $( e.currentTarget ).val() ).change();
			} else {
				$( document ).find( 'input[name=' + $( e.currentTarget ).attr( 'name' ).replace( '-popup','' ) + ']' ).val( $( e.currentTarget ).val() );
			}
		},

		copyMainInputData: function( e ) {
			if ( 'SELECT' === $( e.currentTarget ).prop( 'tagName' ) ) {
				$( document ).find( 'select[name=' + $( e.currentTarget ).attr( 'name' ) + '-popup]' ).val( $( e.currentTarget ).val() ).change();
			} else {
				$( document ).find( 'input[name=' + $( e.currentTarget ).attr( 'name' ) + '-popup]' ).val( $( e.currentTarget ).val() );
			}
		},
	};

	// Launch BP Zoom.
	bp.Zoom_Common.start();

} )( bp, jQuery );
