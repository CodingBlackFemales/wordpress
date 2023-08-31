/* jshint browser: true */
/* global bp, bb_onesignal_vars */
/* @version 2.0.3 */
window.bp        = window.bp || {};
window.OneSignal = window.OneSignal || [];

var bb_player_id = '';

(function ( exports, $ ) {

	/**
	 * [OneSignal description]
	 *
	 * @type {Object}
	 */
	bp.OneSignal_FrontCommon = {

		/**
		 * [start description]
		 *
		 * @return {[type]} [description]
		 */
		start: function () {
			this.setupGlobals();

			// Listen to events ("Add hooks!").
			this.addListeners();

			$( document ).on(
				'ready',
				function () {

					$.magnificPopup.defaults.closeOnBgClick = false;

					if ( '' === bb_player_id ) {
						bb_player_id = bp.OneSignal_FrontCommon.getCookie(
							'bbpro-player-id'
						);
					}
				}
			);
		},

		/**
		 * [setupGlobals description]
		 *
		 * @return {[type]} [description]
		 */
		setupGlobals: function () {
			// Define global variables.
			bp.OneSignal_FrontCommon.is_updated_device_info = false;

			window.OneSignal.push(
				function () {

					window.OneSignal.on(
						'subscriptionChange',
						function ( isSubscribed ) {
							window.OneSignal.push(
								function () {
									window.OneSignal.getUserId(
										function ( userId ) {
											bb_player_id = userId;
											if ( isSubscribed ) {
												window.OneSignal.push( [ 'setSubscription', true ] );
												bp.OneSignal_FrontCommon.updateDeviceInfo(
													bb_player_id,
													true,
													true
												);
											} else {
												bp.OneSignal_FrontCommon.updateDeviceInfo(
													bb_player_id,
													false,
													true
												);
											}

										}
									);
								}
							);
						}
					);

					window.OneSignal.isPushNotificationsEnabled(
						function ( isEnabled ) {

							window.OneSignal.getUserId(
								function ( userId ) {
									bp.OneSignal_FrontCommon.is_updated_device_info = true;
									bb_player_id 									= userId;

									if ( isEnabled ) {
										window.OneSignal.push( [ 'setSubscription', true ] );
										bp.OneSignal_FrontCommon.updateDeviceInfo(
											bb_player_id,
											true,
											true
										);
									} else {
										bp.OneSignal_FrontCommon.updateDeviceInfo(
											bb_player_id,
											false,
											true
										);
									}

								}
							);
						}
					);
				}
			);

			window.OneSignal.push(
				function () {
					window.OneSignal.SERVICE_WORKER_UPDATER_PATH = 'OneSignalSDKUpdaterWorker.js';
					window.OneSignal.SERVICE_WORKER_PATH         = 'OneSignalSDKWorker.js';
					window.OneSignal.SERVICE_WORKER_PARAM        = {
						scope: bb_onesignal_vars.path + '/sdk_files/push/onesignal/',
					};
					window.OneSignal.setDefaultNotificationUrl( bb_onesignal_vars.home_url );
					var oneSignal_options        = {};
					window._oneSignalInitOptions = oneSignal_options;

					oneSignal_options.appId                        = bb_onesignal_vars.app_id;
					oneSignal_options.allowLocalhostAsSecureOrigin = true;
					oneSignal_options.path                         = bb_onesignal_vars.http_path + 'sdk_files/';
					oneSignal_options.safari_web_id                = bb_onesignal_vars.safari_web_id;

					if ( bb_onesignal_vars.subDomainName ) {
						oneSignal_options.subdomainName = bb_onesignal_vars.subDomainName;
					}

					window.OneSignal.init( window._oneSignalInitOptions );

					window.OneSignal.setExternalUserId( bb_onesignal_vars.prompt_user_id );

					if (
						parseInt( bb_onesignal_vars.auto_prompt_request_permission ) > 0 &&
						(
							'visit' === bb_onesignal_vars.auto_prompt_validate ||
							(
								'login' === bb_onesignal_vars.auto_prompt_validate &&
								('true' !== sessionStorage.getItem( 'ONESIGNAL_HTTP_PROMPT_SHOWN' ))
							)
						)
					) {
						bp.OneSignal_FrontCommon.notificationPrompt();
					}

				}
			);

			window.OneSignal.push(
				function () {
					window.OneSignal.on(
						'notificationPermissionChange',
						function ( permissionChange ) {
							var currentPermission = permissionChange.to;
							if ( bb_player_id ) {
								if ( 'granted' === currentPermission ) {
									window.OneSignal.push( [ 'setSubscription', true ] );
									bp.OneSignal_FrontCommon.updateDeviceInfo(
										bb_player_id,
										true,
										true
									);
								} else {
									if ( 'denied' === currentPermission ) {
										window.OneSignal.push( [ 'setSubscription', false ] );
									}
									bp.OneSignal_FrontCommon.updateDeviceInfo(
										bb_player_id,
										false,
										true
									);
								}
							}
						}
					);
				}
			);
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {
			$( document ).on( 'change', '.notification-toggle', this.toggleNotifcationOnOff.bind( this ) );
			$( document ).on( 'click', 'body .mfp-inline-holder', this.disableMagificPopupBackgroundClick.bind( this ) );
			$( document ).on( 'click', '.mfp-close', this.enableMagificPopupBackgroundClick.bind( this ) );
			$( document ).on(
				'click',
				'#onesignal-slidedown-cancel-button',
				function () {
					$( 'body .notification-toggle' ).prop( 'checked', false );
				}
			);
			$( window ).on( 'load', this.handleNotificationToggle.bind( this ) );
		},

		disableMagificPopupBackgroundClick: function () {
			var modal = $( '.mfp-bg' ).length;
			if ( modal >= 1 ) {
				$( 'body, html' ).css( 'overflow', 'hidden' );
			}
		},

		enableMagificPopupBackgroundClick: function () {
			$( 'body, html' ).css( 'overflow', 'inherit' );
		},

		toggleNotifcationOnOff: function ( e ) {
			var target = $( e.target );

			if ( target.is( ':checked' ) ) {
				if (
					'denied' !== bp.OneSignal_FrontCommon.getUserNotificationStatus()
				) {
					localStorage.removeItem( 'onesignal-notification-prompt' );
					bp.OneSignal_FrontCommon.notificationPrompt();

				} else {

					$( '#permission-helper-modal' ).magnificPopup(
						{
							items: {
								src: $( '#permission-helper-modal' ),
								type: 'inline',
								closeOnContentClick: false,
								closeBtnInside: true,
								enableEscapeKey: false,
								closeOnBgClick: false,
							},
							callbacks: {
								open: function () {
									$( '.notification-popup .mfp-close' ).click(
										function () {
											bp.OneSignal_FrontCommon.closeMfpPopup();
										}
									);

									if ( 'granted' === bp.OneSignal_FrontCommon.getUserNotificationStatus() ) {
										$( '.notification-popup .turn-on-button' ).removeAttr( 'disabled' );
									} else {
										window.OneSignal.showNativePrompt();
									}

									$( '.notification-popup .turn-on-button' ).click(
										function () {
											window.OneSignal.push( [ 'setSubscription', true ] );
											setTimeout(
												function () {
													bp.OneSignal_FrontCommon.closeMfpPopup();
												},
												300
											);

										}
									);

								},
								close: function () {
									if ( 'granted' === bp.OneSignal_FrontCommon.getUserNotificationStatus() ) {
										$( 'body .notification-toggle' ).prop( 'checked', true );
									} else {
										$( 'body .notification-toggle' ).prop( 'checked', false );
									}
								}
							},
						}
					).magnificPopup( 'open' );
				}
			} else {
				$( '#permission-helper-modal-close' ).magnificPopup(
					{
						items: {
							src: $( '#permission-helper-modal-close' ),
							type: 'inline',
							closeOnContentClick: false,
							closeBtnInside: true,
							enableEscapeKey: false,
							closeOnBgClick: false,
						},
						callbacks: {
							open: function () {
								$( '.notification-popup-close .mfp-close' ).click(
									function () {
										bp.OneSignal_FrontCommon.closeMfpPopup();
									}
								);

								if ( 'disabled' === bp.OneSignal_FrontCommon.getUserNotificationStatus() || 'default' === bp.OneSignal_FrontCommon.getUserNotificationStatus() ) {
									$( '.notification-popup-close .turn-on-button-close' ).removeAttr( 'disabled' );
								}

								$( '.notification-popup-close .turn-on-button-close' ).click(
									function () {
										window.OneSignal.push( [ 'setSubscription', false ] );
										setTimeout(
											function () {
												bp.OneSignal_FrontCommon.closeMfpPopup();
											},
											300
										);

									}
								);

							},
							close: function () {
								if ( 'granted' === bp.OneSignal_FrontCommon.getUserNotificationStatus() ) {
									$( 'body .notification-toggle' ).prop( 'checked', true );
								} else {
									$( 'body .notification-toggle' ).prop( 'checked', false );
								}
							}
						},
					}
				).magnificPopup( 'open' );
			}
		},

		closeMfpPopup: function () {
			if (
				$( document ).find( '.notification-popup  button.turn-on-button' ).length > 0 &&
				$( document ).find( '.notification-popup  button.turn-on-button' ).is( ':disabled' )
			) {
				$( 'body .notification-toggle' ).prop( 'checked', false );
			}
			$.magnificPopup.close();
		},

		notificationPrompt: function () {

			if ( typeof Notification !== 'undefined' ) {

				if (
					(
						'http:' === location.protocol ||
						(
							'granted' !== Notification.permission &&
							'denied' !== Notification.permission
						)
					) &&
					parseInt( bb_onesignal_vars.is_component_active ) > 0 &&
					parseInt( bb_onesignal_vars.is_web_push_enable ) > 0 &&
					parseInt( bb_onesignal_vars.is_valid_licence ) > 0 &&
					parseInt( bb_onesignal_vars.prompt_user_id ) > 0
				) {
					if ( parseInt( bb_onesignal_vars.is_soft_prompt_enabled ) > 0 ) {
						window._oneSignalInitOptions.promptOptions                  = {};
						window._oneSignalInitOptions.promptOptions.actionMessage    = bb_onesignal_vars.actionMessage;
						window._oneSignalInitOptions.promptOptions.acceptButtonText = bb_onesignal_vars.acceptButtonText;
						window._oneSignalInitOptions.promptOptions.cancelButtonText = bb_onesignal_vars.cancelButtonText;

						window.OneSignal.showSlidedownPrompt();
					} else {
						window.OneSignal.showNativePrompt();
					}
				}

			}
		},

		getUserNotificationStatus: function () {
			return 'undefined' !== typeof Notification ? Notification.permission : '';
		},

		updateDeviceInfo: function ( player_id, active, update_via_curl ) {
			$.ajax(
				{
					type: 'POST',
					url: bb_onesignal_vars.ajax_url,
					data: {
						'action': 'onesignal_update_device_info',
						'user_id': bb_onesignal_vars.prompt_user_id,
						'player_id': player_id,
						'active': active,
						'update_via_curl': update_via_curl,
					},
					success: function ( response ) {
						if (
							'undefined' !== typeof response.data &&
							response.data.browser_box &&
							$( document ).find( '.bb-onesignal-render-browser-block' ).length > 0
						) {
							$( document )
								.find( '.bb-onesignal-render-browser-block' ).empty()
								.append( response.data.browser_box )
								.removeClass( 'bp-hide' );
						}
					},
					error: function () {
						return false;
					},
				}
			);
		},

		// gets the type of browser.
		detectBrowser: function () {
			if (
				(navigator.userAgent.indexOf( 'Opera' ) !== -1) ||
				(navigator.userAgent.indexOf( 'OPR' ) !== -1)
			) {
				return 'Opera';
			} else if (
				(navigator.userAgent.indexOf( 'Edge' ) !== -1) ||
				(navigator.userAgent.indexOf( 'Edg' ) !== -1)
			) {
				return 'Edge';
			} else if ( navigator.userAgent.indexOf( 'Chrome' ) !== -1 ) {
				return 'Chrome';
			} else if ( navigator.userAgent.indexOf( 'Safari' ) !== -1 ) {
				return 'Safari';
			} else if ( navigator.userAgent.indexOf( 'Firefox' ) !== -1 ) {
				return 'Firefox';
			} else if (
				(navigator.userAgent.indexOf( 'MSIE' ) !== -1) ||
				(typeof document.documentMod !== 'undefined')
			) {
				return 'IE'; // crap.
			} else {
				return 'Unknown';
			}
		},

		getCookie: function ( cname ) {
			var name          = cname + '=';
			var decodedCookie = decodeURIComponent( document.cookie );
			var ca            = decodedCookie.split( ';' );
			for ( var i = 0; i < ca.length; i++ ) {
				var c = ca[ i ];
				while ( c.charAt( 0 ) == ' ' ) {
					c = c.substring( 1 );
				}
				if ( c.indexOf( name ) == 0 ) {
					return c.substring( name.length, c.length );
				}
			}
			return '';
		},

		// Set a Cookie.
		setCookie: function ( cname, cvalue, expDays ) {
			var date = new Date();
			date.setTime( date.getTime() + (expDays * 24 * 60 * 60 * 1000) );
			var expires     = 'expires=' + date.toUTCString();
			document.cookie = cname + '=' + cvalue + '; ' + expires + '; path=/';
		},

		handleNotificationToggle: function () {
			setTimeout(
				function () {
					if ( ! bp.OneSignal_FrontCommon.is_updated_device_info ) {
						bp.OneSignal_FrontCommon.updateDeviceInfo(
							'',
							false,
							false
						);
					}
				},
				1000
			);
		},

	};

	// Launch OneSignal Common.
	bp.OneSignal_FrontCommon.start();

})( bp, jQuery );
