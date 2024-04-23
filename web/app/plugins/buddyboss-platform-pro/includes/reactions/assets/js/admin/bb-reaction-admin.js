/* jshint browser: true */
/* global bp, wp, window, bbReactionAdminVars */
window.bp = window.bp || {};

( function( exports, $ ) {

	/**
	 * [Reactions description]
	 *
	 * @type {Object}
	 */
	bp.Reaction_Admin = {
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
			$( window ).on(
				'load',
				function() {
					$( '.bb_emotions_list' ).sortable(
						{
							cursor: 'move',
							items: '> div:not(.bb_emotions_item_action)',
							update: function () {
								$( '.bb_emotions_list input:checkbox:first' ).prop( 'checked', true );
							},
						}
					);
				}
			);

			// Define global variables.
			bp.Reaction_Admin.delete_emotion                = '';
			bp.Reaction_Admin.remove_emotion_ajax_request   = null;
			bp.Reaction_Admin.footer_migration_ajax_request = null;
			bp.Reaction_Admin.switch_migration_ajax_request = null;
			bp.Reaction_Admin.modal_loader                  = '<div class="bbpro-modal-box_loader"><span class="bb-icons bb-icon-spinner animate-spin"></span></div>';
			bp.Reaction_Admin.auto_refresh_interval 		= null;

			if (
				'undefined' === typeof bbProAddNewEmotionPlaceholder &&
				'undefined' !== typeof wp
			) {
				window.bbProAddNewEmotionPlaceholder = wp.template( 'bb-pro-add-new-emotion-placeholder' );
			}
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {
			// Emotions related.
			$( document ).on( 'click', '.bb_emotions_item .bb_emotions_actions_enable input', this.disableEmotion );

			// Modal related.
			$( document ).on( 'click', '.bbpro-modal-box #bbpro_icon_modal_close', this.closeMigrationModal );
			$( document ).on( 'click', '#bbpro_migration_wizard .next_migration_wizard', this.migrationWizardNext );
			$( document ).on( 'click', '#bbpro_migration_wizard .migration_wizard_prev', this.migrationWizardPrevious );

			// Switch reaction mode.
			$( document ).on( 'change', '#bp_reaction_settings_section input[name="bb_reaction_mode"]', this.onChangeReactionMode );

			// Remove emotion.
			$( document ).on( 'click', '.bb_emotions_item .bb_emotions_actions_remove', this.onRemoveEmotion );
			$( document ).on( 'click', '#bbpro_reaction_delete_confirmation .bb-pro-reaction-delete-emotion', this.onDeleteEmotion );
			$( document ).on( 'click', '#bbpro_reaction_delete_confirmation .bb-pro-reaction-cancel-delete-emotion', this.onCancelDeleteEmotion );

			// Migration wizard global.
			$( document ).on( 'change', '#bbpro_migration_wizard .migrate_single_emotion_input', this.onChangeWizardEmotion );
			$( document ).on( 'click', '#bbpro_migration_wizard .cancel_migration_wizard', this.onCancelWizardEmotion );
			$( document ).on( 'change', '#bbpro_migration_wizard #migrate_all_emotions', this.migrationEmotionSelectAll );
			$( document ).on( 'change', '#bbpro_migration_wizard #migration_emotion_select', this.migrationEmotionSelect );

			// Footer reaction migration wizard.
			$( document ).on( 'click', '#bp_reaction_settings_section .footer-reaction-migration-wizard', this.openFooterMigrationWizard );
			$( document ).on( 'click', '#bbpro_migration_wizard .footer_next_wizard_screen', this.openFooterNextMigrationWizard );
			$( document ).on( 'click', '#bbpro_migration_wizard .start_migration_wizard', this.submitFooterNextMigrationWizard );

			// Migration after switching the mode.
			$( document ).on( 'click', '#bb-pro-reaction-migration-exists-notice .reaction-start-conversion', this.migrationStartConversion );
			$( document ).on( 'click', '#bb-pro-reaction-migration-exists-notice .reaction-do-later', this.migrationDoLater );

			// Reaction button length related events.
			$( document ).on(
				'input',
				'#bb-reaction-button-text',
				function ( e ) {
					e.stopImmediatePropagation();

					// Keep entered string in limit of 12 characters.
					var enteredText = $( this ).val().substring( 0, 12 );

					$( this ).val( enteredText );
					$( this ).siblings( '.bb-reaction-button-text-limit' ).children( 'span' ).text( enteredText.length );
				}
			);

			$( document ).on(
				'focus',
				'#bb-reaction-button-text',
				function () {
					$( this ).parent( '.bb-reaction-button-label' ).find( '.bb-reaction-button-text-limit' ).addClass( 'active' );
				}
			);

			$( document ).on(
				'blur',
				'#bb-reaction-button-text',
				function () {
					$( this ).parent( '.bb-reaction-button-label' ).find( '.bb-reaction-button-text-limit' ).removeClass( 'active' );
				}
			);

			// Notice events.
			$( document ).on( 'click', '.bb-pro-reaction-notice.loading .recheck-status', this.recheckStatus );
			$( document ).on( 'click', '.bb-pro-reaction-notice.loading .stop-migration', this.stopMigration );
			$( document ).on( 'click', '.bb-pro-reaction-notice.success .close-reaction-notice', this.hideSuccessNotice );

			// Reload the window after 5 minutes during migration.
			if ( 'inprogress' === bbReactionAdminVars.migration_status ) {
				this.auto_refresh_interval = setInterval(
					function () {
						location.reload();
					},
					300000  // 5 minutes in milliseconds.
				);
			} else {
				clearInterval( this.auto_refresh_interval );
			}
		},

		submitSettingForm: function () {
			$( '.buddyboss_page_bp-settings .wrap form .submit input[type="submit"]' ).trigger( 'click' );
		},

		getErrorNotice: function ( notice ) {
			return '<div class="bb-pro-reaction-notice error"><p>' + notice + '</p></div>';
		},

		disableEmotion: function() {
			$( this ).closest( '.bb_emotions_item' ).toggleClass( 'is-disabled' );
		},

		hideNoticeElement: function () {
			var notice_wrap = $( '.bb-pro-reaction-notices' );

			if ( '' === $.trim( notice_wrap.find( 'td' ).html() ) ) {
				notice_wrap.addClass( 'bp-hide' );
			}
		},

		closeMigrationModal: function () {
			$( '.bbpro-modal-box' ).hide();
			$( '#migration_action' ).val( 'no' );

			// Clear content, abort ajax request for delete modal.
			$( '.bb-reaction-delete-modal__content' ).html( bp.Reaction_Admin.modal_loader );
			if ( bp.Reaction_Admin.remove_emotion_ajax_request ) {
				bp.Reaction_Admin.remove_emotion_ajax_request.abort();
			}

			// Clear content, abort ajax request for footer/switch wizard modal.
			$( '#bbpro_migration_wizard .wizard-label' ).html( bbReactionAdminVars.wizard_label );
			$( '#bbpro_migration_wizard .modal-content' ).html( bp.Reaction_Admin.modal_loader );
			if ( bp.Reaction_Admin.footer_migration_ajax_request ) {
				bp.Reaction_Admin.footer_migration_ajax_request.abort();
			}

			// Abort ajax request for footer/switch wizard modal.
			if ( bp.Reaction_Admin.switch_migration_ajax_request ) {
				bp.Reaction_Admin.switch_migration_ajax_request.abort();
			}
		},

		migrationWizardNext: function (e) {
			e.preventDefault();
			if ( ! $( this ).hasClass( 'disabled' ) ) {
				$( '#bbpro_migration_wizard' ).find( '.bbpro_migration_wizard_screens.active' ).removeClass( 'active' ).next( '.bbpro_migration_wizard_screens' ).addClass( 'active' );
			}
		},

		migrationWizardPrevious: function (e) {
			e.preventDefault();
			if ( ! $( this ).hasClass( 'disabled' ) ) {
				$( '#bbpro_migration_wizard' ).find( '.bbpro_migration_wizard_screens.active' ).removeClass( 'active' ).prev( '.bbpro_migration_wizard_screens' ).addClass( 'active' );
			}
		},

		onChangeReactionMode: function () {
			var reaction_mode   = '',
				reaction_notice = '';

			if ( $( this ).is( ':checked' ) ) {
				reaction_mode   = $( this ).val();
				reaction_notice = $( this ).data( 'notice' );
			}

			if ( 'likes' === reaction_mode ) {
				$( '.bb_emotions_list_row, .bb_reaction_button_row' ).addClass( 'bp-hide' );
			} else if ( 'emotions' === reaction_mode ) {
				$( '.bb_emotions_list_row, .bb_reaction_button_row' ).removeClass( 'bp-hide' );
			}

			if ( '' !== reaction_notice ) {
				$( '.bb-reaction-mode-description' ).html( reaction_notice );
			}

		},

		onRemoveEmotion: function (e) {
			e.preventDefault();

			var emotion_item = $( this ).closest( '.bb_emotions_item' ),
				emotion_id   = emotion_item.data( 'reaction-id' );

			bp.Reaction_Admin.delete_emotion = emotion_item;

			if ( jQuery.trim( emotion_id ).length > 0 ) {
				$( '#bbpro_reaction_delete_confirmation' ).css( 'display', 'block' );

				bp.Reaction_Admin.remove_emotion_ajax_request = $.ajax(
					{
						url: bbReactionAdminVars.ajax_url,
						data: {
							'action': 'bb_pro_reaction_check_delete_emotion',
							'emotion_id': emotion_id,
							'nonce': bbReactionAdminVars.nonce.check_delete_emotion
						},
						method: 'POST'
					}
				).done(
					function ( response ) {
						var $response_data = response.data;

						if ( true === response.success && 'undefined' !== typeof $response_data.content ) {
							$( '.bb-reaction-delete-modal__content' ).html( $response_data.content );
						} else if ( 'undefined' !== typeof $response_data.message && 0 < $response_data.message.length ) {
							$( '.bb-reaction-delete-modal__content' ).html( bp.Reaction_Admin.getErrorNotice( $response_data.message ) );
						}
					}
				);
			} else {
				bp.Reaction_Admin.delete_emotion.remove();
				bp.Reaction_Admin.delete_emotion = '';
				$( '.bb_emotions_list' ).append( window.bbProAddNewEmotionPlaceholder() );
			}
		},

		onDeleteEmotion: function (e) {
			e.preventDefault();
			bp.Reaction_Admin.delete_emotion.remove();
			bp.Reaction_Admin.delete_emotion = '';
			bp.Reaction_Admin.submitSettingForm();
		},

		onCancelDeleteEmotion: function (e) {
			e.preventDefault();
			bp.Reaction_Admin.closeMigrationModal();
		},

		onChangeWizardEmotion: function () {
			var checked_vals = $( '.migrate_single_emotion_input:checked' ).map(
				function(){
					return $( this ).val();
				}
			).get();

			if ( 0 < checked_vals.length ) {
				$( '.footer_next_wizard_screen' ).removeClass( 'disabled' );
			} else {
				$( '.footer_next_wizard_screen' ).addClass( 'disabled' );
			}
		},

		onCancelWizardEmotion: function ( e ) {
			e.preventDefault();
			bp.Reaction_Admin.closeMigrationModal();
		},

		migrationEmotionSelectAll: function () {
			if ( true === $( this ).prop( 'checked' ) ) {
				$( this ).closest( '.bbpro_migration_wizard_screens' ).find( '.migrate_emotion_input' ).prop( 'checked', true ).not( '#migrate_all_emotions' ).prop( 'disabled', true );
			} else {
				$( this ).closest( '.bbpro_migration_wizard_screens' ).find( '.migrate_emotion_input' ).prop( 'checked', false ).prop( 'disabled', false );
			}

			bp.Reaction_Admin.onChangeWizardEmotion();
		},

		migrationEmotionSelect: function () {
			if ( 'undefined' !== typeof $( this ).val().length && 0 < $( this ).val().length ) {
				$( '.footer_next_wizard_screen' ).removeClass( 'disabled' );
			} else {
				$( '.footer_next_wizard_screen' ).addClass( 'disabled' );
			}
		},

		openFooterMigrationWizard: function ( e ) {
			e.preventDefault();

			$( '#migration_action' ).val( 'footer' );
			$( '#bbpro_migration_wizard' ).css( 'display', 'block' );

			bp.Reaction_Admin.footer_migration_ajax_request = $.ajax(
				{
					url: bbReactionAdminVars.ajax_url,
					data: {
						'action' : 'bb_pro_reaction_footer_migration',
						'nonce'  : bbReactionAdminVars.nonce.footer_migration
					},
					method: 'POST'
				}
			).done(
				function( response ) {
					var $response_data = response.data;

					if ( true === response.success && 'undefined' !== typeof $response_data.content ) {
						$( '#bbpro_migration_wizard .modal-content' ).html( $response_data.content );
					} else if ( 'undefined' !== typeof $response_data.message && 0 < $response_data.message.length ) {
						$( '#bbpro_migration_wizard .modal-content' ).html( bp.Reaction_Admin.getErrorNotice( $response_data.message ) );
					}
				}
			);
		},

		openFooterNextMigrationWizard: function ( e ) {
			e.preventDefault();

			var checked_counts = $( '.migrate_single_emotion_input:checked' ).map(
				function(){
					return $( this ).data( 'count' );
				}
			).get();

			var emotions_count = checked_counts.reduce(
				function ( accumulator, currentValue ) {
					return accumulator + currentValue;
				},
				0
			);

			var emotion_text = $( '#migration_emotion_select option:selected' ).text();

			$( this ).parents( '.modal-content' ).find( '.from-reactions-count' ).html( bp.BB_Pro_Admin.getNumberFormat( emotions_count ) );
			$( this ).parents( '.modal-content' ).find( '.to-reactions-label' ).html( emotion_text );

			bp.Reaction_Admin.migrationWizardNext( e );
		},

		submitFooterNextMigrationWizard: function ( e ) {
			e.preventDefault();
			$( this ).css( 'pointer-events', 'none' );
			$( '.cancel_migration_wizard' ).prop( 'disabled', true );
			bp.Reaction_Admin.submitSettingForm();
		},

		migrationStartConversion: function ( e ) {
			e.preventDefault();

			var $this = $( e.currentTarget );

			$( '#migration_action' ).val( 'switch' );
			$( '#bbpro_migration_wizard' ).css( 'display', 'block' );

			bp.Reaction_Admin.footer_migration_ajax_request = $.ajax(
				{
					url: bbReactionAdminVars.ajax_url,
					data: {
						'action' : 'bb_pro_reaction_migration_start_conversion',
						'nonce'  : bbReactionAdminVars.nonce.migration_start_conversion
					},
					method: 'POST'
				}
			).done(
				function( response ) {
					var $response_data = response.data;

					// Setup label.
					if ( 'undefined' !== typeof $response_data.label ) {
						$( '#bbpro_migration_wizard .wizard-label' ).html( $response_data.label );
					}

					// Setup content.
					if ( true === response.success && 'undefined' !== typeof $response_data.content ) {
						$( '#bbpro_migration_wizard .modal-content' ).html( $response_data.content );
					} else if ( 'undefined' !== typeof $response_data.message && 0 < $response_data.message.length ) {
						$( '#bbpro_migration_wizard .modal-content' ).html( bp.Reaction_Admin.getErrorNotice( $response_data.message ) );
					}

					// Update count.
					if ( 'undefined' !== typeof $response_data.total_reactions ) {
						$this.parents( '#bb-pro-reaction-migration-exists-notice' ).find( '.reaction-notice-count' ).html( bp.BB_Pro_Admin.getNumberFormat( $response_data.total_reactions ) );
						bp.Reaction_Admin.hideNoticeElement();
					}

					// Hide notice.
					if ( 'undefined' !== typeof $response_data.is_notice_dismissed && true === $response_data.is_notice_dismissed ) {
						$this.parents( '#bb-pro-reaction-migration-exists-notice' ).remove();
						bp.Reaction_Admin.hideNoticeElement();
					}
				}
			);
		},

		migrationDoLater:function ( e ) {
			e.preventDefault();

			var notice = $( '#bb-pro-reaction-migration-exists-notice' );

			$( e.currentTarget ).addClass( 'loading' );

			// Set do later.
			bp.Reaction_Admin.remove_emotion_ajax_request = $.ajax(
				{
					url: bbReactionAdminVars.ajax_url,
					method: 'POST',
					data: {
						'action' : 'bb_pro_reaction_migration_do_later',
						'nonce'  : bbReactionAdminVars.nonce.migration_do_later
					},
					success: function( response ) {
						if ( true === response.success ) {
							// Remove notice.
							notice.remove();

							bp.Reaction_Admin.hideNoticeElement();
						} else {
							alert( response.data.message );
						}
					},
				}
			);
		},

		recheckStatus: function( e ) {
			e.preventDefault();
			$( e.currentTarget ).addClass( 'loading' );
			location.reload();
		},

		stopMigration: function(e) {
			e.preventDefault();

			$( e.currentTarget ).addClass( 'loading' );

			$.ajax(
				{
					url: bbReactionAdminVars.ajax_url,
					data: {
						'action' : 'bb_pro_reaction_migration_stop_conversion',
						'nonce'  : bbReactionAdminVars.nonce.migration_stop_conversion
					},
					method: 'POST'
				}
			).done(
				function() {
					location.reload();
				}
			);

		},

		hideSuccessNotice: function () {
			$( this ).parents( '.bb-pro-reaction-notice.success' ).remove();
			bp.Reaction_Admin.hideNoticeElement();
		}
	};

	// Launch Reaction Admin.
	bp.Reaction_Admin.start();

} )( bp, jQuery );
