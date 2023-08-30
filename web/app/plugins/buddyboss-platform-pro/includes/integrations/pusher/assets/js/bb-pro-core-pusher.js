/* jshint browser: true */
/* global bp, bb_pusher_vars, Pusher, wp, BP_Nouveau, PusherBatchAuthorizer */
/* @version 2.2.0 */
window.bp       = window.bp || {};
window.Backbone = window.Backbone || [];


(function ( exports, $ ) {

	/**
	 * [Pusher description]
	 *
	 * @type {Object}
	 */
	bp.Pusher_FrontCommon = {

		pusher: {},
		typer_data: {},
		xhr: false,
		can_type: true,
		throttleTime: 6000,
		clearInterval: 6000, // 6 seconds.
		clearTimerId: [],
		pendingRemoves: [],
		leaveWaitingTime: 5000, // 5 seconds.
		chunks_event: [],
		bb_pusher_channels: [],
		worker_enabled: false,
		try_reconnect: false,

		/**
		 * [start description]
		 *
		 * @return {[type]} [description]
		 */
		start: function () {

			if (
				'undefined' === typeof bb_pusher_vars ||
				'undefined' === typeof bb_pusher_vars.loggedin_user_id ||
				parseInt( bb_pusher_vars.loggedin_user_id ) === 0
			) {
				return;
			}

			this.setupGlobals();

			// Listen to events ("Add hooks!").
			this.addListeners();

			$( document ).on(
				'ready',
				function () {
					bp.Pusher_FrontCommon.setupToast();
				}
			);
		},

		/**
		 * [setupGlobals description]
		 *
		 * @return {[type]} [description]
		 */
		setupGlobals: function () {
			bp.Pusher_FrontCommon.typer_data               = [];
			bb_pusher_vars.current_thread_recipients_count = 0;
			bb_pusher_vars.current_thread_group_id         = 0;

			bp.Pusher_FrontCommon.pusher = this.pusherGlobalObject();

			if (
				bp.Pusher_FrontCommon.pusher &&
				'undefined' !== typeof bp.Pusher_FrontCommon.pusher.channels &&
				'on' === bb_pusher_vars.is_live_messaging_enabled
			) {
				bp.Pusher_FrontCommon.pusher.signin();
				bp.Pusher_FrontCommon.bb_pusher_channels.push( 'private-bb-pro-global' );
				bp.Pusher_FrontCommon.bb_pusher_channels.push( 'private-bb-user-' + bb_pusher_vars.loggedin_user_id );

				if ( Object.keys( bb_pusher_vars.group_threads ).length > 0 ) {
					$.each(
						bb_pusher_vars.group_threads,
						function ( i, item ) {
							bp.Pusher_FrontCommon.bb_pusher_channels.push( 'private-bb-message-thread-' + item );
						}
					);
				}

				bp.Pusher_FrontCommon.pusherLiveMessage();

				if ( true !== bp.Pusher_FrontCommon.worker_enabled ) {
					bp.Pusher_FrontCommon.pusher.connection.bind( 'disconnected', function () {
						bp.Pusher_FrontCommon.removed_channels();
						if ( true === bp.Pusher_FrontCommon.try_reconnect ) {
							bp.Pusher_FrontCommon.pusher.connect();
						}
					} );

					bp.Pusher_FrontCommon.pusher.connection.bind( 'connected', function () {
						if ( true === bp.Pusher_FrontCommon.try_reconnect ) {
							bp.Pusher_FrontCommon.try_reconnect = false;
						}
					} );
				}
			}

			bp.Pusher_FrontCommon.subscribe_channels();
		},

		/**
		 * Subscribe to channels.
		 */
		subscribe_channels: function () {
			bp.Pusher_FrontCommon.bb_pusher_channels.forEach(
				function ( name ) {
					if ( name === 'private-bb-pro-global' ) {
						bp.Pusher_FrontCommon.pusherGlobalChannel( name );
					} else if ( name.search( 'private-bb-message-thread-' ) > -1 ) {
						var thread_id = name.replace( 'private-bb-message-thread-', '' );
						bp.Pusher_FrontCommon.pusherSubscribeThreadsChannels( parseInt( thread_id ) );
					} else if ( name.search( 'private-bb-user-' ) > -1 ) {
						bp.Pusher_FrontCommon.pusherSubscribeUserChannel( name );
					}
				}
			);
		},

		/**
		 * Remove channel objects.
		 */
		removed_channels: function () {
			jQuery.each( bb_pusher_vars, function ( key ) {
				if ( key.indexOf( 'live_message_' ) !== -1 ) {
					delete bb_pusher_vars[ key ];
				}
			} );
		},

		/**
		 * [addListeners description]
		 */
		addListeners: function () {
			$( document ).ajaxSuccess(
				function ( event, jqxhr, settings ) {
					var response_data = '';
					if (
						bp.Pusher_FrontCommon.pusher &&
						'undefined' !== typeof bp.Pusher_FrontCommon.pusher.channels &&
						'on' === bb_pusher_vars.is_live_messaging_enabled
					) {
						if (
							settings.hasOwnProperty( 'data' ) &&
							'string' === typeof settings.data &&
							settings.data.search( 'action=messages_get_user_message_threads' ) > -1 &&
							'undefined' !== typeof jqxhr.responseText
						) {
							response_data = $.parseJSON( jqxhr.responseText );
							if ( true === response_data.success && response_data.data ) {
								var threads = response_data.data.threads;
								if ( threads.length > 0 ) {
									threads.forEach(
										function ( thread ) {
											bp.Pusher_FrontCommon.pusherSubscribeThreadsChannels( parseInt( thread.id ) );
										}
									);
								}
							}
						}

						if (
							settings.hasOwnProperty( 'url' ) &&
							'string' === typeof settings.url &&
							settings.url.search( 'action=buddyboss_theme_get_header_unread_messages' ) > -1 &&
							'undefined' !== typeof jqxhr.responseText &&
							'on' === bb_pusher_vars.is_live_messaging_enabled
						) {
							response_data = $.parseJSON( jqxhr.responseText );
							if ( true === response_data.success && response_data.data ) {
								var contents = response_data.data.contents;
								if ( contents != '' ) {
									var tmpDiv       = document.createElement( 'div' );
									tmpDiv.innerHTML = contents;
									var list_items   = $( tmpDiv ).find( 'li' );
									if ( list_items.length > 0 ) {
										$.each(
											list_items,
											function ( i, item ) {
												var thread_id = $( item ).attr( 'data-thread-id' );
												bp.Pusher_FrontCommon.pusherSubscribeThreadsChannels( parseInt( thread_id ) );
											}
										);
									}
									tmpDiv.remove();
								}
							}
						}
					}
				}
			);
		},

		/* Fire the Toast Messages */
		setupToast: function() {
			var self = bp.Pusher_FrontCommon;

			var thread_deleted = self.readCookie( 'bb-thread-delete' );
			if ( thread_deleted ) {
				jQuery( document ).trigger(
					'bb_trigger_toast_message',
					[
						'',
						thread_deleted,
						'warning',
						null,
						true
					]
				);

				self.createCookie( 'bb-thread-delete', '', -1 );
			}

			var disabled_message_component = self.readCookie( 'bb-message-component-disabled' );
			if ( disabled_message_component ) {
				jQuery( document ).trigger(
					'bb_trigger_toast_message',
					[
						'',
						disabled_message_component,
						'warning',
						null,
						true
					]
				);

				self.createCookie( 'bb-message-component-disabled', '', -1 );
			}

			var group_thread_delete = self.readCookie( 'bb-message-group-thread-deleted' );
			if ( group_thread_delete ) {
				jQuery( document ).trigger(
					'bb_trigger_toast_message',
					[
						'',
						group_thread_delete,
						'info',
						null,
						true
					]
				);

				self.createCookie( 'bb-message-group-thread-deleted', '', -1 );
			}

		},

		pusherGlobalObject: function () {

			var authOptions = {
				params: {
					'bb_pusher_user_id': bb_pusher_vars.loggedin_user_id,
					'bb_pusher_user_data': bb_pusher_vars.user_data,
					'alien_hash': bb_pusher_vars.alien_hash,
				}
			};

			if ( 'undefined' === typeof ( window.SharedWorker ) || 'undefined' === typeof bp.bb_pusher_shared ) {
				bp.Pusher_FrontCommon.worker_enabled = false;
				return new Pusher(
					bb_pusher_vars.app_key,
					{
						authorizer        : PusherBatchAuthorizer,
						cluster           : bb_pusher_vars.app_cluster,
						authEndpoint      : bb_pusher_vars.auth_endpoint,
						auth              : authOptions,
						userAuthentication: {
							endpoint : bb_pusher_vars.user_auth_endpoint,
							transport: 'ajax',
						},
					}
				);
			} else {
				bp.Pusher_FrontCommon.worker_enabled = true;
				return bp.bb_pusher_shared.constructor(
					bb_pusher_vars.app_key,
					{
						encrypted         : true,
						authorizer        : bb_pusher_vars.bb_pro_pusher_auth,
						cluster           : bb_pusher_vars.app_cluster,
						authEndpoint      : bb_pusher_vars.auth_endpoint,
						auth              : authOptions,
						userAuthentication: {
							endpoint : bb_pusher_vars.user_auth_endpoint,
							transport: 'ajax',
						},
					}
				);
			}
		},

		pusherGlobalChannel: function( $name ) {
			bb_pusher_vars.global_channel = bp.Pusher_FrontCommon.pusher.subscribe( $name );

			// INFO : On pusher:subscription_succeeded.
			bb_pusher_vars.global_channel.bind(
				'pusher:subscription_succeeded',
				function () {
				}
			);

			// ERROR : On pusher:subscription_error.
			bb_pusher_vars.global_channel.bind(
				'pusher:subscription_error',
				function () {
				}
			);

			// Pusher actions regarding the moderation.
			bp.Pusher_FrontCommon.pusherModeration( bb_pusher_vars.global_channel );

			// Pusher actions regarding the components.
			bp.Pusher_FrontCommon.pusherComponents( bb_pusher_vars.global_channel );

			// Pusher actions regarding the pusher settings changes.
			bp.Pusher_FrontCommon.pusherSettingsUpdate( bb_pusher_vars.global_channel );

			// When changed Message access control settings.
			bb_pusher_vars.global_channel.bind(
				'client-bb-pro-message-access-control-update',
				function () {

					if ( 1 >= bb_pusher_vars.current_thread_recipients_count && 'no' === bb_pusher_vars.is_admin ) {
						var hash = new Date().getTime();
						window.location.href.replace( window.location.search, '' );
						bp.Nouveau.Messages.router.navigate( 'view/' + bb_pusher_vars.current_thread_id + '/?hash=' + hash, { trigger: true } );
					}

					if ( 'undefined' !== typeof window.Backbone.trigger && 'no' === bb_pusher_vars.is_admin ) {
						window.Backbone.trigger( 'relistelements' );
					}
				}
			);

			// When changed Member connection settings.
			bb_pusher_vars.global_channel.bind(
				'client-bb-pro-message-is-connected',
				function () {
					if ( parseInt( bb_pusher_vars.current_thread_id ) > 0 && 'no' === bb_pusher_vars.is_admin ) {
						var hash = new Date().getTime();
						window.location.href.replace( window.location.search, '' );
						bp.Nouveau.Messages.router.navigate( 'view/' + bb_pusher_vars.current_thread_id + '/?hash=' + hash, { trigger: true } );
					}

					if ( 'undefined' !== typeof window.Backbone.trigger && 'no' === bb_pusher_vars.is_admin ) {
						window.Backbone.trigger( 'relistelements' );
					}
				}
			);

			// When group messages has been disabled by admin.
			bb_pusher_vars.global_channel.bind(
				'client-bb-pro-update-group-messages',
				function ( data ) {
					if ( parseInt( data.previous ) !== parseInt( data.updated ) ) {
						if (
							bb_pusher_vars.current_thread.is_group_thread &&
							true === bb_pusher_vars.current_thread.is_group_thread
						) {
							var hash        = new Date().getTime();
							var next_thread = $( '.message-lists .thread-item:first-child' );
							var thread_id   = next_thread.find( '.bp-message-link' ).attr( 'data-thread-id' );
							if ( parseInt( thread_id ) === parseInt( bb_pusher_vars.current_thread_id ) ) {
								thread_id = next_thread.next( '.thread-item' ).find( '.bp-message-link' ).attr( 'data-thread-id' );
							}

							window.location.href.replace( window.location.search, '' );
							bp.Nouveau.Messages.router.navigate( 'view/' + thread_id + '/?hash=' + hash, { trigger: true } );

						}

						if ( 0 === parseInt( data.updated ) ) {
							jQuery( document ).trigger(
								'bb_trigger_toast_message',
								[
									'',
									bb_pusher_vars.i18n.group_message_disabled,
									'warning',
									null,
									true
								]
							);
						}

						if ( 'undefined' !== typeof window.Backbone.trigger ) {
							window.Backbone.trigger( 'relistelements' );
						}

						if ( 'undefined' !== typeof window.wp.heartbeat ) {
							window.wp.heartbeat.connectNow();
						}
					}
				}
			);
		},

		pusherSubscribeUserChannel: function( $name ) {
			bb_pusher_vars.user_channel = bp.Pusher_FrontCommon.pusher.subscribe( $name );

			// INFO : On pusher:subscription_succeeded.
			bb_pusher_vars.user_channel.bind(
				'pusher:subscription_succeeded',
				function () {
				}
			);

			// ERROR : On pusher:subscription_error.
			bb_pusher_vars.user_channel.bind(
				'pusher:subscription_error',
				function () {
				}
			);

			// When Member send connection request.
			bb_pusher_vars.user_channel.bind(
				'client-bb-pro-member-connection-requested',
				function ( data ) {

					// friend_user_id = request sender.
					bp.Nouveau.updateUserPresence( parseInt( data.friend_user_id ), 'online' );

					if ( parseInt( bb_pusher_vars.loggedin_user_id ) === parseInt( data.friend_user_id ) ) {
						var friend_user_threads_hash = data.thread_ids;
						var common_threads           = [];
						if ( friend_user_threads_hash.length > 0 ) {
							$.each(
								friend_user_threads_hash,
								function ( index, thread_hash ) {
									if (
										'undefined' !== typeof bb_pusher_vars.bb_pro_pusher_thread_ids &&
										'undefined' !== typeof bb_pusher_vars.bb_pro_pusher_thread_ids[ thread_hash ]
									) {
										common_threads.push( parseInt( bb_pusher_vars.bb_pro_pusher_thread_ids[ thread_hash ] ) );
									}
								}
							);
						}
						if ( common_threads.length > 0 ) {
							$.each(
								common_threads,
								function ( index, item_id ) {
									if ( parseInt( item_id ) === parseInt( bb_pusher_vars.current_thread_id ) ) {
										var hash = new Date().getTime();
										window.location.href.replace( window.location.search, '' );
										bp.Nouveau.Messages.router.navigate( 'view/' + item_id + '/?hash=' + hash, { trigger: true } );
									}
								}
							);
						}
					}
				}
			);

			// When Member withdrawn connection request.
			bb_pusher_vars.user_channel.bind(
				'client-bb-pro-member-withdrawn-connection-request',
				function ( data ) {

					// friend_user_id = request withdrawer.
					bp.Nouveau.updateUserPresence( parseInt( data.friend_user_id ), 'online' );

					if ( parseInt( bb_pusher_vars.loggedin_user_id ) === parseInt( data.friend_user_id ) ) {
						var friend_user_threads_hash = data.thread_ids;
						var common_threads           = [];

						if ( friend_user_threads_hash.length > 0 ) {
							$.each(
								friend_user_threads_hash,
								function ( index, thread_hash ) {
									if (
										'undefined' !== typeof bb_pusher_vars.bb_pro_pusher_thread_ids &&
										'undefined' !== typeof bb_pusher_vars.bb_pro_pusher_thread_ids[ thread_hash ]
									) {
										common_threads.push( parseInt( bb_pusher_vars.bb_pro_pusher_thread_ids[ thread_hash ] ) );
									}
								}
							);
						}
						if ( common_threads.length > 0 ) {
							$.each(
								common_threads,
								function ( index, item_id ) {
									if ( parseInt( item_id ) === parseInt( bb_pusher_vars.current_thread_id ) ) {
										var hash = new Date().getTime();
										window.location.href.replace( window.location.search, '' );
										bp.Nouveau.Messages.router.navigate( 'view/' + item_id + '/?hash=' + hash, { trigger: true } );
									}
								}
							);
						}
					}

				}
			);

			// When Member accepted connection request.
			bb_pusher_vars.user_channel.bind(
				'client-bb-pro-member-accepted-connection-request',
				function ( data ) {
					var friend_user_threads_hash = data.thread_ids;
					var common_threads           = [];

					bp.Nouveau.updateUserPresence( parseInt( data.initiator_user_id ), 'online' );

					if ( friend_user_threads_hash.length > 0 ) {
						$.each(
							friend_user_threads_hash,
							function ( index, thread_hash ) {
								if (
									'undefined' !== typeof bb_pusher_vars.bb_pro_pusher_thread_ids &&
									'undefined' !== typeof bb_pusher_vars.bb_pro_pusher_thread_ids[ thread_hash ]
								) {
									common_threads.push( parseInt( bb_pusher_vars.bb_pro_pusher_thread_ids[ thread_hash ] ) );
								}
							}
						);
					}

					if ( common_threads.length > 0 ) {
						$.each(
							common_threads,
							function ( index, item_id ) {
								if ( parseInt( item_id ) === parseInt( bb_pusher_vars.current_thread_id ) ) {
									var hash = new Date().getTime();
									window.location.href.replace( window.location.search, '' );
									bp.Nouveau.Messages.router.navigate( 'view/' + item_id + '/?hash=' + hash, { trigger: true } );
								}
							}
						);
					}

					if ( 'undefined' !== typeof window.Backbone.trigger ) {
						window.Backbone.trigger( 'relistelements' );
					}
				}
			);

			// When Member withdrawn connection request.
			bb_pusher_vars.user_channel.bind(
				'client-bb-pro-member-rejected-connection-request',
				function ( data ) {
					var friend_user_threads_hash = data.thread_ids;
					var common_threads           = [];
					var hash                     = new Date().getTime();

					bp.Nouveau.updateUserPresence( parseInt( data.initiator_user_id ), 'online' );

					if ( friend_user_threads_hash.length > 0 ) {
						$.each(
							friend_user_threads_hash,
							function ( index, thread_hash ) {
								if (
									'undefined' !== typeof bb_pusher_vars.bb_pro_pusher_thread_ids &&
									'undefined' !== typeof bb_pusher_vars.bb_pro_pusher_thread_ids[ thread_hash ]
								) {
									common_threads.push( parseInt( bb_pusher_vars.bb_pro_pusher_thread_ids[ thread_hash ] ) );
								}
							}
						);
					}

					if ( common_threads.length > 0 ) {
						$.each(
							common_threads,
							function ( index, item_id ) {
								if ( parseInt( item_id ) === parseInt( bb_pusher_vars.current_thread_id ) ) {
									hash = new Date().getTime();
									window.location.href.replace( window.location.search, '' );
									bp.Nouveau.Messages.router.navigate( 'view/' + item_id + '/?hash=' + hash, { trigger: true } );
								}

								if ( parseInt( item_id ) === parseInt( bb_pusher_vars.current_thread_id ) ) {
									hash = new Date().getTime();
									window.location.href.replace( window.location.search, '' );
									bp.Nouveau.Messages.router.navigate( 'view/' + item_id + '/?hash=' + hash, { trigger: true } );
								}
							}
						);
					}

					if ( 'undefined' !== typeof window.Backbone.trigger ) {
						window.Backbone.trigger( 'relistelements' );
					}
				}
			);

			// Member blocked.
			bb_pusher_vars.user_channel.bind(
				'client-bb-pro-member-blocked',
				function ( data ) {

					bp.Nouveau.updateUserPresence( parseInt( data.creator_id ), 'online' );

					if (
						'on' === bb_pusher_vars.is_live_messaging_enabled &&
						(
							(
								data.creator_id &&
								parseInt( data.creator_id ) === parseInt( bb_pusher_vars.loggedin_user_id )
							) ||
							(
								data.blocked_id &&
								parseInt( data.blocked_id ) === parseInt( bb_pusher_vars.loggedin_user_id )
							)
						)
					) {
						var blocked_user_threads_hash = data.thread_ids;
						var common_threads            = [];
						if ( blocked_user_threads_hash.length > 0 ) {
							$.each(
								blocked_user_threads_hash,
								function ( index, thread_hash ) {
									if (
										'undefined' !== typeof bb_pusher_vars.bb_pro_pusher_thread_ids &&
										'undefined' !== typeof bb_pusher_vars.bb_pro_pusher_thread_ids[ thread_hash ]
									) {
										common_threads.push( parseInt( bb_pusher_vars.bb_pro_pusher_thread_ids[ thread_hash ] ) );
									}
								}
							);
						}

						if ( common_threads.length > 0 ) {
							$.each(
								common_threads,
								function ( index, item_id ) {
									if ( parseInt( item_id ) === parseInt( bb_pusher_vars.current_thread_id ) ) {
										var hash = new Date().getTime();
										window.location.href.replace( window.location.search, '' );
										bp.Nouveau.Messages.router.navigate( 'view/' + item_id + '/?hash=' + hash, { trigger: true } );
									}
								}
							);

							if ( 'undefined' !== typeof window.Backbone.trigger ) {
								window.Backbone.trigger( 'relistelements' );
							}
						}
					}

					if (
						'on' === bb_pusher_vars.is_live_messaging_enabled &&
						(
							( data.creator_id && parseInt( data.creator_id ) === parseInt( bb_pusher_vars.loggedin_user_id ) ) ||
							( data.blocked_id && parseInt( data.blocked_id ) === parseInt( bb_pusher_vars.loggedin_user_id ) )
						)
					) {
						if ( data.blocked_id && parseInt( data.blocked_id ) === parseInt( bb_pusher_vars.loggedin_user_id ) ) {
							var blocked_hash = '';
							$.each(
								bb_pusher_vars.blocked_users_ids,
								function ( key, value ) {
									if ( parseInt( data.blocked_id ) === parseInt( value.id ) ) {
										blocked_hash = key;
									}
								}
							);

							if ( blocked_hash !== '' ) {
								delete bb_pusher_vars.blocked_users_ids[ blocked_hash ];
							}
						}

						if ( data.creator_id && parseInt( data.creator_id ) === parseInt( bb_pusher_vars.loggedin_user_id ) ) {
							var creator_hash = '';
							$.each(
								bb_pusher_vars.is_blocked_by_users,
								function ( key, value ) {
									if ( parseInt( data.creator_id ) === parseInt( value.id ) ) {
										creator_hash = key;
									}
								}
							);

							if ( creator_hash !== '' ) {
								delete bb_pusher_vars.is_blocked_by_users[ creator_hash ];
							}
						}

						if ( 'undefined' !== typeof window.wp.heartbeat ) {
							window.wp.heartbeat.connectNow();
						}
					}
				}
			);

			// Member Unblocked.
			bb_pusher_vars.user_channel.bind(
				'client-bb-pro-member-unblocked',
				function ( data ) {

					bp.Nouveau.updateUserPresence( parseInt( data.creator_id ), 'online' );

					if (
						'on' === bb_pusher_vars.is_live_messaging_enabled &&
						(
							(
								data.creator_id &&
								parseInt( data.creator_id ) === parseInt( bb_pusher_vars.loggedin_user_id )
							) ||
							(
								data.unblocked_id &&
								parseInt( data.unblocked_id ) === parseInt( bb_pusher_vars.loggedin_user_id )
							)
						)
					) {
						var unblocked_user_threads_hash = data.thread_ids;
						var common_threads              = [];
						if ( unblocked_user_threads_hash.length > 0 ) {
							$.each(
								unblocked_user_threads_hash,
								function ( index, thread_hash ) {
									if (
										'undefined' !== typeof bb_pusher_vars.bb_pro_pusher_thread_ids &&
										'undefined' !== typeof bb_pusher_vars.bb_pro_pusher_thread_ids[ thread_hash ]
									) {
										common_threads.push( parseInt( bb_pusher_vars.bb_pro_pusher_thread_ids[ thread_hash ] ) );
									}
								}
							);
						}

						if ( common_threads.length > 0 ) {
							$.each(
								common_threads,
								function ( index, item_id ) {
									if ( parseInt( item_id ) === parseInt( bb_pusher_vars.current_thread_id ) ) {
										var hash = new Date().getTime();
										window.location.href.replace( window.location.search, '' );
										bp.Nouveau.Messages.router.navigate( 'view/' + item_id + '/?hash=' + hash, { trigger: true } );
									}
								}
							);

							if ( 'undefined' !== typeof window.Backbone.trigger ) {
								window.Backbone.trigger( 'relistelements' );
							}
						}
					}

					if ( 'on' === bb_pusher_vars.is_live_messaging_enabled ) {

						var unblocked_hash = '';
						$.each(
							bb_pusher_vars.blocked_users_ids,
							function ( key, value ) {
								if ( parseInt( data.unblocked_id ) === parseInt( value.id ) ) {
									unblocked_hash = key;
								}
							}
						);

						var creator_hash = '';
						$.each(
							bb_pusher_vars.is_blocked_by_users,
							function ( key, value ) {
								if ( parseInt( data.creator_id ) === parseInt( value.id ) ) {
									creator_hash = key;
								}
							}
						);

						if (
							data.creator_id &&
							parseInt( data.creator_id ) === parseInt( bb_pusher_vars.loggedin_user_id ) &&
							data.unblocked_id &&
							unblocked_hash !== ''
						) {
							delete bb_pusher_vars.blocked_users_ids[ unblocked_hash ];
						}

						if (
							data.unblocked_id &&
							parseInt( data.unblocked_id ) === parseInt( bb_pusher_vars.loggedin_user_id ) &&
							data.creator_id &&
							creator_hash !== ''
						) {
							delete bb_pusher_vars.is_blocked_by_users[ creator_hash ];
						}
					}
				}
			);

			// Group message member joined/left/ban/unban/promote/demote.
			bb_pusher_vars.user_channel.bind(
				'client-bb-pro-group-message-group-update-notify',
				function ( data ) {
					var action = data.action;

					if ( '' === action ) {
						return;
					}

					var thread_hash = data.thread_id, thread_id = 0;
					var user_id     = data.sender_id, hash = '';

					bp.Nouveau.updateUserPresence( parseInt( user_id ), 'online' );

					if (
						'undefined' !== typeof bb_pusher_vars.bb_pro_pusher_thread_ids &&
						'undefined' !== typeof bb_pusher_vars.bb_pro_pusher_thread_ids[ thread_hash ]
					) {
						thread_id = parseInt( bb_pusher_vars.bb_pro_pusher_thread_ids[ thread_hash ] );
					}

					if (
						action === 'group_message_group_joined' ||
						action === 'group_message_group_un_ban'
					) {

						if ( 'undefined' !== typeof window.wp.heartbeat ) {
							window.wp.heartbeat.connectNow();
						}

						if ( 'undefined' !== typeof window.Backbone.trigger ) {
							window.Backbone.trigger( 'relistelements' );
						}

						if ( thread_id > 0 && parseInt( thread_id ) === parseInt( bb_pusher_vars.current_thread_id ) && 'undefined' !== typeof bp.Nouveau.Messages ) {
							hash = new Date().getTime();
							bp.Nouveau.Messages.router.navigate( 'view/' + thread_id + '/?hash=' + hash, { trigger: true } );
						}

					} else if (
						action === 'group_message_group_promoted' ||
						action === 'group_message_group_demoted'
					) {

						if ( thread_id > 0 && parseInt( thread_id ) === parseInt( bb_pusher_vars.current_thread_id ) && 'undefined' !== typeof bp.Nouveau.Messages ) {
							hash = new Date().getTime();
							bp.Nouveau.Messages.router.navigate( 'view/' + thread_id + '/?hash=' + hash, { trigger: true } );
						}

					} else if (
						action === 'group_message_group_left' ||
						action === 'group_message_group_ban' ||
						action === 'groups_remove_member'
					) {

						if ( parseInt( user_id ) === parseInt( bb_pusher_vars.loggedin_user_id ) && thread_id > 0 && 'undefined' !== typeof bp.Nouveau.Messages ) {
							bp.Nouveau.Messages.threads.remove( bp.Nouveau.Messages.threads.get( thread_id ) );

							if ( thread_id > 0 && bb_pusher_vars.bb_pro_pusher_thread_ids[ thread_hash ] ) {
								delete bb_pusher_vars.bb_pro_pusher_thread_ids[ thread_hash ];
							}
						}

						if ( thread_id > 0 && thread_id === parseInt( bb_pusher_vars.current_thread_id ) ) {
							hash = new Date().getTime();
							if ( parseInt( user_id ) === parseInt( bb_pusher_vars.loggedin_user_id ) ) {
								var next_thread    = $( '.message-lists .thread-item:first-child' );
								var next_thread_id = next_thread.find( '.bp-message-link' ).attr( 'data-thread-id' );

								if ( parseInt( next_thread_id ) === thread_id ) {
									next_thread_id = next_thread.next( '.thread-item' ).find( '.bp-message-link' ).attr( 'data-thread-id' );
								}

								var group_name    = $( '.single-message-thread-header .participants-name' ).text();
								var toast_message = '';

								if ( action === 'group_message_group_left' ) {
									toast_message = bb_pusher_vars.i18n.thread_left + group_name;
								} else if ( action === 'group_message_group_ban' ) {
									toast_message = bb_pusher_vars.i18n.group_banned + group_name;
								} else if ( action === 'groups_remove_member' ) {
									toast_message = bb_pusher_vars.i18n.remove_from_group + group_name;
								}

								jQuery( document ).trigger(
									'bb_trigger_toast_message',
									[
										'',
										toast_message,
										'info',
										null,
										true
									]
								);

								if ( 'undefined' !== typeof next_thread_id && 'undefined' !== typeof bp.Nouveau.Messages ) {
									bb_pusher_vars.current_thread_id = parseInt( next_thread_id );
									bp.Nouveau.Messages.router.navigate( 'view/' + next_thread_id + '/?hash=' + hash, { trigger: true } );
								} else if ( 'undefined' !== typeof bp.Nouveau.Messages ) {
									bb_pusher_vars.current_thread_id = 0;
									bp.Nouveau.Messages.router.navigate( 'compose/', { trigger: true } );
								}

							} else if ( 'undefined' !== typeof bp.Nouveau.Messages ) {
								bp.Nouveau.Messages.router.navigate( 'view/' + thread_id + '/?hash=' + hash, { trigger: true } );
							}
						}

						if ( parseInt( user_id ) === parseInt( bb_pusher_vars.loggedin_user_id ) ) {
							if ( 'undefined' !== typeof window.Backbone.trigger ) {
								window.Backbone.trigger( 'relistelements' );
							}

							if ( 'undefined' !== typeof window.wp.heartbeat ) {
								window.wp.heartbeat.connectNow();
							}
						}
					}
				}
			);

			// Message sent from group.
			bb_pusher_vars.user_channel.bind(
				'client-bb-pro-new-group-message',
				function ( data ) {
					var recepient_hash = bp.Pusher_FrontCommon.json2array( data.recipients );
					setTimeout(
						function () {
							if (
								'undefined' !== typeof recepient_hash &&
								-1 !== $.inArray( bb_pusher_vars.alien_hash, recepient_hash )
							) {
								var thread_id = 0;
								if (
									'undefined' !== typeof bb_pusher_vars.bb_pro_pusher_thread_ids &&
									'undefined' !== typeof bb_pusher_vars.bb_pro_pusher_thread_ids[data.thread_id]
								) {
									thread_id = parseInt( bb_pusher_vars.bb_pro_pusher_thread_ids[data.thread_id] );
								}
								if ( parseInt( thread_id ) > 0 ) {
									var hash = new Date().getTime();
									if ( parseInt( thread_id ) === parseInt( bb_pusher_vars.current_thread_id ) ) {
										bp.Nouveau.Messages.router.navigate( 'view/' + thread_id + '/?hash=' + hash, { trigger: true } );
									}
									if ( 'undefined' !== typeof window.Backbone.trigger ) {
										window.Backbone.trigger( 'relistelements' );
									}

									if ( 'undefined' !== typeof window.wp.heartbeat ) {
										window.wp.heartbeat.connectNow();
									}
								} else if ( 'undefined' !== typeof data.thread ) {
									bb_pusher_vars.group_threads[data.thread_id] = parseInt( data.thread );
									bp.Pusher_FrontCommon.pusherSubscribeThreadsChannels( parseInt( data.thread ) );
									if ( 'undefined' !== typeof window.Backbone.trigger ) {
										window.Backbone.trigger( 'relistelements' );
									}
								}
							}
						},
						500
					);
				}
			);

			// New message created.
			bb_pusher_vars.user_channel.bind(
				'client-bb-pro-new-thread-create',
				function ( data ) {
					var thread_hash    = data.thread_id;
					var recepient_hash = bp.Pusher_FrontCommon.json2array( data.recipients );
					setTimeout(
						function () {
							if (
								'undefined' !== typeof bb_pusher_vars.bb_pro_pusher_thread_ids &&
								'undefined' === typeof bb_pusher_vars.bb_pro_pusher_thread_ids[ thread_hash ] &&
								'undefined' !== typeof recepient_hash &&
								-1 !== $.inArray( bb_pusher_vars.alien_hash, recepient_hash )
							) {
								if ( 'undefined' !== typeof window.Backbone.trigger ) {
									window.Backbone.trigger( 'relistelements' );
								}

								if ( 'undefined' !== typeof window.wp.heartbeat ) {
									window.wp.heartbeat.connectNow();
								}
							}
						},
						500
					);
				}
			);

			// Reconnect.
			bb_pusher_vars.user_channel.bind(
				'client-bb-pro-reconnect',
				function () {
					bp.Pusher_FrontCommon.try_reconnect = true;
					if ( true === bp.Pusher_FrontCommon.worker_enabled ) {
						bp.bb_pusher_shared.sendMessage( 'updateReconnect', { try_reconnect: true } );
					}
				}
			);

		},

		pusherSubscribeThreadsChannels: function ( thread_id ) {
			var key          = 'live_message_' + thread_id;
			var channel_name = 'private-bb-message-thread-' + thread_id;
			if (  'undefined' !== typeof bb_pusher_vars[ key ] ) {
				return;
			}

			bb_pusher_vars[ key ] = bp.Pusher_FrontCommon.pusher.subscribe( channel_name );
			bp.Pusher_FrontCommon.pusherMessageListen( bb_pusher_vars[ key ] );
		},

		pusherLiveMessage: function () {

			if ( 'undefined' !== typeof bb_pusher_vars.current_thread_id && bb_pusher_vars.current_thread_id > 0 ) {
				bp.Pusher_FrontCommon.pusherSubscribeThreadsChannels( bb_pusher_vars.current_thread_id );
			}

			$( document ).on(
				'heartbeat-tick',
				function ( event, data ) {
					if ( 'undefined' !== typeof data.bb_pro_pusher_thread_ids ) {
						bb_pusher_vars.bb_pro_pusher_thread_ids = data.bb_pro_pusher_thread_ids;

						if ( Object.keys( data.bb_pro_pusher_thread_ids ).length > 0 ) {
							$.each(
								data.bb_pro_pusher_thread_ids,
								function ( i, item ) {
									bp.Pusher_FrontCommon.pusherSubscribeThreadsChannels( parseInt( item ) );
								}
							);
						}
					}

					if ( 'undefined' !== typeof data.blocked_users_ids ) {
						bb_pusher_vars.blocked_users_ids = data.blocked_users_ids;
					}

					if ( 'undefined' !== typeof data.suspended_users_ids ) {
						bb_pusher_vars.suspended_users_ids = data.suspended_users_ids;
					}

					if ( 'undefined' !== typeof data.is_blocked_by_users ) {
						bb_pusher_vars.is_blocked_by_users = data.is_blocked_by_users;
					}

					if ( 'undefined' !== typeof data.group_threads ) {
						bb_pusher_vars.group_threads = data.group_threads;
					}

					if ( 'undefined' !== typeof bb_pusher_vars.group_threads && Object.keys( bb_pusher_vars.group_threads ).length > 0 ) {
						$.each(
							bb_pusher_vars.group_threads,
							function ( i, item ) {
								bp.Pusher_FrontCommon.pusherSubscribeThreadsChannels( parseInt( item ) );
							}
						);
					}
				}
			);

			$( document ).on( 'click', '#message-threads .thread-item', bp.Pusher_FrontCommon.pusherUpdateChannel.bind( this ) );
			$( document ).on( 'click', '.bp-messages-nav-panel #compose', bp.Pusher_FrontCommon.pusherCompose.bind( this ) );
			$( document ).on( 'keyup', '#message_content', bp.Pusher_FrontCommon.pusherMessageTypingEvent.bind( this ) );

			$( document ).ajaxComplete(
				function ( event, jqxhr, settings ) {
					if (
						settings.hasOwnProperty( 'data' ) &&
						'string' === typeof settings.data &&
						settings.data.search( 'action=messages_send_reply' ) > 0 &&
						'undefined' !== typeof jqxhr.responseText
					) {
						var r_data = $.parseJSON( jqxhr.responseText );

						if ( 'undefined' !== typeof r_data.data.type && 'error' === r_data.data.type ) {
							var dataArr = bp.Pusher_FrontCommon.bbParseParams( bb_pusher_vars.ajax_url + '?' + settings.data );
							var data    = {
								hash: dataArr.hash,
								thread_id: dataArr.thread_id,
								sender_id: parseInt( bb_pusher_vars.loggedin_user_id ),
							};

							var key = 'live_message_' + bb_pusher_vars.current_thread_id;

							if ( bb_pusher_vars.current_thread_id && parseInt( bb_pusher_vars.current_thread_id ) === parseInt( dataArr.thread_id ) && bb_pusher_vars[key] ) {
								bb_pusher_vars[key].trigger( 'client-bb-pro-after-ajax-fail', data );
							}

							var dataupdate = {
								hash: dataArr.hash,
								actions: settings.data,
								canceltext: bb_pusher_vars.cancel_text,
								notdeliveredtext: bb_pusher_vars.not_delivered_text,
								tryagaintext: bb_pusher_vars.try_again_text,
								thread_id: dataArr.thread_id
							};

							bp.Pusher_FrontCommon.renderAjaxUpdateFailedMessage( dataupdate );
							if ( true === bp.Pusher_FrontCommon.worker_enabled ) {
								bp.bb_pusher_shared.sendMessage( 'renderAjaxUpdateFailedMessage', dataupdate );
							}

						}
					}

				}
			);

			$( document ).ajaxSuccess(
				function ( event, jqxhr, settings ) {
					var key = '';
					if (
						settings.hasOwnProperty( 'data' ) &&
						'string' === typeof settings.data &&
						settings.data.search( 'action=messages_get_thread_messages' ) > -1 &&
						'undefined' !== typeof jqxhr.responseText
					) {
						var response_data = $.parseJSON( jqxhr.responseText );
						if ( true === response_data.success && response_data.data ) {
							var thread_id                 = parseInt( response_data.data.thread.id );
							bb_pusher_vars.current_thread = response_data.data;
							if ( parseInt( bb_pusher_vars.current_thread_id ) === thread_id ) {

								if ( ! (thread_id in bp.Pusher_FrontCommon.typer_data) ) {
									bp.Pusher_FrontCommon.typer_data[ thread_id ] = [];
								}

								var filtered = bp.Pusher_FrontCommon.typer_data[ thread_id ].filter(
									function ( el ) {
										return el != null;
									}
								);

								bp.Pusher_FrontCommon.singleThreadTypingIndicator( filtered );

								bp.Pusher_FrontCommon.threadListTypingIndicator( thread_id, filtered );

								clearTimeout( bp.Pusher_FrontCommon.clearTimerId[thread_id] );
								bp.Pusher_FrontCommon.clearTimerId[thread_id] = setTimeout(
									function () {
										if ( parseInt( thread_id ) === parseInt( bb_pusher_vars.current_thread_id ) ) {
											bp.Pusher_FrontCommon.singleThreadTypingIndicator( [] );
										}

										bp.Pusher_FrontCommon.threadListTypingIndicator( thread_id, [] );
									},
									bp.Pusher_FrontCommon.clearInterval
								);
							}

							// Set current thread recipients count.
							bb_pusher_vars.current_thread_recipients_count = response_data.data.thread.recipients.current_count;

							// Set current thread group id.
							bb_pusher_vars.current_thread_group_id = ('' !== response_data.data.group_id) ? parseInt( response_data.data.group_id ) : 0;

							if ( 0 === parseInt( bb_pusher_vars.current_thread_id ) && thread_id ) {
								bb_pusher_vars.current_thread_id = thread_id;
							}
						}
					}

					if (
						settings.hasOwnProperty( 'data' ) &&
						'string' === typeof settings.data &&
						settings.data.search( 'action=messages_get_user_message_threads' ) > -1 &&
						'undefined' !== typeof jqxhr.responseText
					) {
						var response = $.parseJSON( jqxhr.responseText );
						if ( response.data.threads ) {
							var user_threads = response.data.threads;
							if ( user_threads.length > 0 ) {
								user_threads.forEach(
									function ( thread ) {
										var thread_id = parseInt( thread.id );
										if ( ! (thread_id in bp.Pusher_FrontCommon.typer_data) ) {
											bp.Pusher_FrontCommon.typer_data[ thread_id ] = [];
										}

										var filtered = bp.Pusher_FrontCommon.typer_data[ thread_id ].filter(
											function ( el ) {
												return el != null;
											}
										);

										bp.Pusher_FrontCommon.singleThreadTypingIndicator( filtered );

										bp.Pusher_FrontCommon.threadListTypingIndicator( thread_id, filtered );

										clearTimeout( bp.Pusher_FrontCommon.clearTimerId[thread_id] );
										bp.Pusher_FrontCommon.clearTimerId[thread_id] = setTimeout(
											function () {
												if ( parseInt( thread_id ) === parseInt( bb_pusher_vars.current_thread_id ) ) {
													bp.Pusher_FrontCommon.singleThreadTypingIndicator( [] );
												}

												bp.Pusher_FrontCommon.threadListTypingIndicator( thread_id, [] );
											},
											bp.Pusher_FrontCommon.clearInterval
										);
									}
								);
							}
						}
					}

					if (
						settings.hasOwnProperty( 'data' ) &&
						'string' === typeof settings.data &&
						settings.data.search( 'action=messages_send_reply' ) > -1 &&
						'undefined' !== typeof jqxhr.responseText
					) {
						var r_data = $.parseJSON( jqxhr.responseText );

						if ( 'undefined' !== typeof r_data.data.type && 'error' === r_data.data.type ) {
							var dataArr = bp.Pusher_FrontCommon.bbParseParams( bb_pusher_vars.ajax_url + '?' + settings.data );
							var data    = {
								hash: dataArr.hash,
								thread_id: dataArr.thread_id,
								sender_id: parseInt( bb_pusher_vars.loggedin_user_id ),
							};

							key = 'live_message_' + bb_pusher_vars.current_thread_id;

							if ( bb_pusher_vars.current_thread_id && parseInt( bb_pusher_vars.current_thread_id ) === parseInt( dataArr.thread_id ) && bb_pusher_vars[key] ) {
								bb_pusher_vars[key].trigger( 'client-bb-pro-after-ajax-fail', data );
							}

							var dataupdate = {
								hash: dataArr.hash,
								actions: settings.data,
								canceltext: bb_pusher_vars.cancel_text,
								notdeliveredtext: bb_pusher_vars.not_delivered_text,
								tryagaintext: bb_pusher_vars.try_again_text,
								thread_id: dataArr.thread_id
							};

							bp.Pusher_FrontCommon.renderAjaxUpdateFailedMessage( dataupdate );
							if ( true === bp.Pusher_FrontCommon.worker_enabled ) {
								bp.bb_pusher_shared.sendMessage( 'renderAjaxUpdateFailedMessage', dataupdate );
							}
						} else {
							var dataComplete = {
								hash: r_data.data.hash,
								message: r_data.data.messages[ 0 ],
								recipient_inbox_unread_counts: r_data.data.recipient_inbox_unread_counts,
								thread_id: r_data.data.thread_id
							};

							bp.Pusher_FrontCommon.renderAjaxUpdateMessage( dataComplete );
							bp.Pusher_FrontCommon.bb_update_header_messages();
						}
					}

					// Message delete.
					if (
						settings.hasOwnProperty( 'data' ) &&
						'string' === typeof settings.data &&
						settings.data.search( 'action=messages_delete' ) > -1 &&
						'undefined' !== typeof jqxhr.responseText
					) {
						var messagesDelete = $.parseJSON( jqxhr.responseText );
						if ( 'undefined' !== typeof messagesDelete.data.recipient_inbox_unread_counts ) {
							bp.Pusher_FrontCommon.updateInboxMessageUnreadCount( bp.Pusher_FrontCommon.json2array( messagesDelete.data.recipient_inbox_unread_counts ) );
						}
						bp.Pusher_FrontCommon.bb_update_header_messages();
					}

					// show typing indicator after heartbeat.
					if (
						settings.hasOwnProperty( 'data' ) &&
						'string' === typeof settings.data &&
						settings.data.search( 'action=heartbeat' ) > -1
					) {
						if ( bb_pusher_vars.bb_pro_pusher_thread_ids.length > 0 ) {
							bb_pusher_vars.bb_pro_pusher_thread_ids.forEach(
								function ( thread_id ) {
									thread_id = parseInt( thread_id );

									if ( thread_id === parseInt( bb_pusher_vars.current_thread_id ) ) {

										if ( ! (thread_id in bp.Pusher_FrontCommon.typer_data) ) {
											bp.Pusher_FrontCommon.typer_data[ thread_id ] = [];
										}

										var filtered = bp.Pusher_FrontCommon.typer_data[ thread_id ].filter(
											function ( el ) {
												return el != null;
											}
										);

										bp.Pusher_FrontCommon.singleThreadTypingIndicator( filtered );

										bp.Pusher_FrontCommon.threadListTypingIndicator( thread_id, filtered );

										clearTimeout( bp.Pusher_FrontCommon.clearTimerId[thread_id] );
										bp.Pusher_FrontCommon.clearTimerId[thread_id] = setTimeout(
											function () {
												if ( parseInt( thread_id ) === parseInt( bb_pusher_vars.current_thread_id ) ) {
													bp.Pusher_FrontCommon.singleThreadTypingIndicator( [] );
												}

												bp.Pusher_FrontCommon.threadListTypingIndicator( thread_id, [] );
											},
											bp.Pusher_FrontCommon.clearInterval
										);
									}
								}
							);
						}

					}

					// Update unread message count.
					if (
						settings.hasOwnProperty( 'data' ) &&
						'string' === typeof settings.data &&
						( settings.data.search( 'action=messages_unread' ) > -1 || settings.data.search( 'action=messages_read' ) > -1 ) &&
						'undefined' !== typeof jqxhr.responseText
					) {
						if ( 'undefined' !== typeof window.wp.heartbeat ) {
							window.wp.heartbeat.connectNow();
						}
					}

					// Update current thread ID when thread was archived.
					if (
						settings.hasOwnProperty( 'data' ) &&
						'string' === typeof settings.data &&
						( settings.data.search( 'action=messages_hide_thread' ) > -1 ) &&
						'undefined' !== typeof jqxhr.responseText
					) {
						if ( 'undefined' !== typeof bp.Nouveau.Messages.threads ) {
							if ( 'undefined' !== typeof bp.Nouveau.Messages.threads.length && 0 < bp.Nouveau.Messages.threads.length ) {
								bb_pusher_vars.current_thread_id = bp.Nouveau.Messages.threads.at( 0 ).id;
							} else {
								bb_pusher_vars.current_thread_id = 0;
							}
						}
					}

					// Update the notification drop-down when user join group first time.
					if (
						settings.hasOwnProperty( 'data' ) &&
						'string' === typeof settings.data &&
						settings.data.search( 'action=groups_join_group' ) > -1 &&
						'undefined' !== typeof jqxhr.responseText
					) {
						var group_join_response = $.parseJSON( jqxhr.responseText );
						if ( 'undefined' !== typeof group_join_response.success && true === group_join_response.success ) {
							bp.Pusher_FrontCommon.bb_update_header_messages();
						}
					}
				}
			);

			$( document ).ajaxError(
				function( event, jqxhr, settings ) {
					if (
						settings.hasOwnProperty( 'data' ) &&
						'string' === typeof settings.data &&
						settings.data.search( 'action=messages_send_reply' ) > 0
					) {
						var dataArr = bp.Pusher_FrontCommon.bbParseParams( bb_pusher_vars.ajax_url + '?' + settings.data );
						var data    = {
							hash: dataArr.hash,
							thread_id: dataArr.thread_id,
							sender_id: parseInt( bb_pusher_vars.loggedin_user_id ),
						};

						var key = 'live_message_' + bb_pusher_vars.current_thread_id;

						if ( bb_pusher_vars.current_thread_id && parseInt( bb_pusher_vars.current_thread_id ) === parseInt( dataArr.thread_id ) && bb_pusher_vars[key] ) {
							bb_pusher_vars[key].trigger( 'client-bb-pro-after-ajax-fail', data );
						}

						var dataupdate = {
							hash: dataArr.hash,
							actions: settings.data,
							canceltext: bb_pusher_vars.cancel_text,
							notdeliveredtext: bb_pusher_vars.not_delivered_text,
							tryagaintext: bb_pusher_vars.try_again_text,
							thread_id: dataArr.thread_id
						};

						bp.Pusher_FrontCommon.renderAjaxUpdateFailedMessage( dataupdate );
						if ( true === bp.Pusher_FrontCommon.worker_enabled ) {
							bp.bb_pusher_shared.sendMessage( 'renderAjaxUpdateFailedMessage', dataupdate );
						}

						if ( 'undefined' !== typeof window.Backbone.trigger ) {
							window.Backbone.trigger( 'relistelements' );
						}
					}
				}
			);

			$( document ).ajaxSend(
				function ( event, jqxhr, settings ) {
					if (
						settings.hasOwnProperty( 'data' ) &&
						'string' === typeof settings.data &&
						settings.data.search( 'action=messages_send_reply' ) > 0
					) {

						var param_data       = bp.Pusher_FrontCommon.bb_unserialize( settings.data );
						var mediasPusher     = [];
						var mediasPusherTemp = [];
						var documentsPusher  = [];
						var videosPusher     = [];
						var videosPusherTemp = [];
						var gif_datas        = {};
						var dataArr          = window.deparam( settings.data );
						var sendingHtml      = '<div class="message_send_sending"><span class="info-text-send-message">' + bb_pusher_vars.sending_text + '</span></div>';

						delete param_data.nonce;
						delete param_data.action;

						$.each(
							param_data,
							function ( i ) {
								if ( i.search( 'media' ) > -1 ) {
									delete param_data[ i ];
								} else if ( i.search( 'video' ) > -1 ) {
									delete param_data[ i ];
								} else if ( i.search( 'document' ) > -1 ) {
									delete param_data[ i ];
								} else if ( i.search( 'gif_data' ) > -1 ) {
									delete param_data[ i ];
								}
							}
						);

						if ( 'undefined' !== typeof dataArr.media ) {
							if ( dataArr.media ) {
								dataArr.media.forEach(
									function ( item ) {
										var previewURL = item.thumb;
										if ( 'undefined' !== typeof item.js_preview ) {
											previewURL = item.js_preview;
										}

										// Pass dimenstions of an image if it's a single image.
										var media_height = null;
										var media_width  = null;
										if ( dataArr.media.length === 1 ) {
											media_height = item.image_h;
											media_width  = item.image_w;
										}

										mediasPusher.push(
											{
												id              : item.id,
												attachment_id   : item.id,
												full            : item.url,
												privacy         : item.privacy,
												thumbnail       : item.msg_url || item.medium || item.thumb,
												title           : item.name,
												height			: media_height,
												width			: media_width,
												menu_order      : parseInt( item.menu_order )
											}
										);

										mediasPusherTemp.push(
											{
												id              : item.id,
												attachment_id   : item.id,
												full            : item.url,
												privacy         : item.privacy,
												thumbnail       : previewURL,
												title           : item.name,
												height          : media_height,
												width           : media_width,
												menu_order      : parseInt( item.menu_order )
											}
										);

									}
								);

								if ( mediasPusher.length > 1 ) {
									mediasPusher.sort(
										function ( a, b ) {
											return a.menu_order - b.menu_order;
										}
									);
								}

								if ( mediasPusherTemp.length > 1 ) {
									mediasPusherTemp.sort(
										function ( a, b ) {
											return a.menu_order - b.menu_order;
										}
									);
								}
							}
						}

						if ( 'undefined' !== typeof dataArr.video ) {
							if ( dataArr.video ) {
								dataArr.video.forEach(
									function ( item ) {
										if ( 'undefined' === typeof item.js_preview ) {
											item.js_preview = bb_pusher_vars.video_default_url;
										}

										videosPusher.push(
											{
												id              : item.id,
												attachment_id   : item.id,
												full            : item.url,
												privacy         : item.privacy,
												thumbnail       : bb_pusher_vars.video_default_url,
												title           : item.name,
												vid_ids_fake    : item.vid_msg_url,
												ext             : item.ext,
												menu_order      : parseInt( item.menu_order )
											}
										);

										videosPusherTemp.push(
											{
												id              : item.id,
												attachment_id   : item.id,
												full            : item.url,
												privacy         : item.privacy,
												thumbnail       : item.js_preview,
												title           : item.name,
												vid_ids_fake    : item.vid_msg_url,
												ext             : item.ext,
												menu_order      : parseInt( item.menu_order )
											}
										);

									}
								);

								if ( videosPusher.length > 1 ) {
									videosPusher.sort(
										function ( a, b ) {
											return a.menu_order - b.menu_order;
										}
									);
								}

								if ( videosPusherTemp.length > 1 ) {
									videosPusherTemp.sort(
										function ( a, b ) {
											return a.menu_order - b.menu_order;
										}
									);
								}
							}
						}

						if ( 'undefined' !== typeof dataArr.document ) {
							if ( dataArr.document ) {
								dataArr.document.forEach(
									function ( item ) {

										documentsPusher.push(
											{
												id                    : item.id,
												attachment_id         : item.id,
												full                  : item.url,
												privacy               : item.privacy,
												thumbnail             : '',
												title                 : item.name,
												url                   : item.url,
												svg_icon              : item.svg_icon,
												extension             : item.extension,
												author                : '',
												preview               : '',
												full_preview          : '',
												text_preview          : '',
												mp3_preview           : '',
												document_title        : item.name,
												mirror_text           : '',
												size                  : item.size,
												extension_description : '',
												download_text         : '',
												download              : '',
												copy_download_link    : '',
												msg_preview           : '',
												menu_order            : parseInt( item.menu_order )
											}
										);
									}
								);

								if ( documentsPusher.length > 1 ) {
									documentsPusher.sort(
										function ( a, b ) {
											return a.menu_order - b.menu_order;
										}
									);
								}
							}
						}

						if ( 'undefined' !== typeof dataArr.gif_data ) {
							if ( dataArr.gif_data ) {
								var videoPreviewUrl = '';
								if ( 'undefined' !== typeof  dataArr.gif_data.images['480w_still'].url ) {
									videoPreviewUrl = dataArr.gif_data.images['480w_still'].url;
								}
								gif_datas.video_url   = dataArr.gif_data.images.original_mp4.mp4;
								gif_datas.preview_url = videoPreviewUrl;

							}
						}

						var settings_params = new URLSearchParams( settings.data ); // jshint ignore:line
						var settings_obj    = Object.fromEntries( settings_params );

						// validate the text is empty or not.
						var text_content = $.trim( $( '<p>' + settings_obj.content + '<p>' ).text() ); // Wrap in p tag to fix android issue where content is without any parent tag.
							text_content = text_content.replace( /&nbsp;/g, ' ' );

						var hash            = ('undefined' !== typeof param_data.hash) ? param_data.hash : new Date().getTime();
						var originalContent = ( text_content === '' ? '' : settings_obj.content );
						var className       = hash;
						var contentModified = originalContent;
						if ( 'undefined' !== typeof dataArr.resend ) {
							className       = hash + ' sending';
							contentModified = contentModified + sendingHtml;
						}
						var excerptRegularExpression = /(<br\s*?\/?>|<\/(\w+)><(\w+)>)/g;

						if ( mediasPusher || videosPusher || documentsPusher ) {
							className = className + ' has-medias';
						}

						param_data.id                = hash;
						param_data.sender_id         = bb_pusher_vars.loggedin_user_id;
						param_data.sender_name       = bb_pusher_vars.loggedin_user_name;
						param_data.gif               = gif_datas;
						param_data.is_user_suspended = (param_data.is_user_suspended === 'true');
						param_data.is_user_blocked   = (param_data.is_user_blocked === 'true');
						param_data.is_user_deleted   = (param_data.is_user_deleted === 'true');
						param_data.sender_avatar     = bb_pusher_vars.sender_avatar;
						param_data.sender_link       = bb_pusher_vars.sender_link;
						param_data.display_date      = bp.Pusher_FrontCommon.getWPCurrentTime();
						param_data.display_date_list = bb_pusher_vars.display_date;
						param_data.className         = className;
						param_data.sender_is_you     = true;
						param_data.thread_id         = bb_pusher_vars.current_thread_id;
						param_data.content           = contentModified;
						param_data.excerpt           = '';
						if ( contentModified ) {
							param_data.excerpt = contentModified.replace( excerptRegularExpression, ' ' ).replace( /(<([^>]+)>)/ig, '' ).replace( /\s+/g, ' ' );
							if ( param_data.excerpt.length > 75 ) {
								param_data.excerpt = param_data.excerpt.substring( 0, 75 ) + '...';
							}
						}
						param_data.media    = mediasPusher;
						param_data.video    = videosPusher;
						param_data.document = documentsPusher;
						param_data.is_group = false;
						if (
							'undefined' !== typeof bb_pusher_vars.current_thread.group_id &&
							parseInt( bb_pusher_vars.current_thread.group_id ) > 0
						) {
							param_data.is_group = true;
						}

						param_data.has_media = false;
						if (
							mediasPusher.length > 0 ||
							videosPusher.length > 0 ||
							documentsPusher.length > 0 ||
							(
							'undefined' !== typeof gif_datas.video_url &&
							'undefined' !== typeof gif_datas.preview_url
							)
						) {
							param_data.has_media = true;
						}

						if ( 'undefined' !== typeof dataArr.resend ) {
							bp.Pusher_FrontCommon.renderUpdateSendingMessage( param_data );

							if ( true === bp.Pusher_FrontCommon.worker_enabled ) {
								bp.bb_pusher_shared.sendMessage( 'resend-message-ajax-send', param_data );
							}
						} else {
							param_data.media = mediasPusherTemp;
							param_data.video = videosPusherTemp;
							bp.Pusher_FrontCommon.renderMessage( param_data );

							if ( true === bp.Pusher_FrontCommon.worker_enabled ) {
								bp.bb_pusher_shared.sendMessage( 'before-message-ajax-send', param_data );
							}
						}

						param_data.media = mediasPusher;
						param_data.video = videosPusher;

						var key = 'live_message_' + bb_pusher_vars.current_thread_id;

						param_data.content = originalContent;

						if ( bb_pusher_vars[ key ] ) {
							param_data.sender_is_you = false;

							bp.Pusher_FrontCommon.triggerPusherChunked(
								bb_pusher_vars[ key ],
								'client-bb-pro-before-message-ajax-send',
								param_data
							);
						}

						if ( $( '#messages-media-button' ).length ) {
							$( '#messages-media-button' ).removeClass( 'active' );
						}
						if ( $( '#messages-video-button' ).length ) {
							$( '#messages-video-button' ).removeClass( 'active' );
						}
						if ( $( '#messages-document-button' ).length ) {
							$( '#messages-document-button' ).removeClass( 'active' );
						}
						if ( $( '#messages-gif-button' ).length ) {
							$( '#messages-gif-button' ).removeClass( 'active' );
						}

						$( window ).trigger( 'scroll' );

					}
				}
			);
		},

		pusherMessageListen: function( channel ) {
			// INFO : On pusher:subscription_succeeded.
			channel.bind(
				'pusher:subscription_succeeded',
				function () {
				}
			);

			// ERROR : On pusher:subscription_error.
			channel.bind(
				'pusher:subscription_error',
				function () {
				}
			);

			/* Listen Events */

			// When message send.
			channel.bind(
				'client-bb-pro-before-message-ajax-send',
				function ( data ) {

					if ( 'undefined' !== typeof data.sender_id ) {
						bp.Nouveau.updateUserPresence( parseInt( data.sender_id ), 'online' );

						if ( 'undefined' !== typeof data.thread_id ) {
							bp.Pusher_FrontCommon.threadTypingEnd( parseInt( data.thread_id ), parseInt( data.sender_id ) );
						}
					}

					if ( 'undefined' !== typeof data.message_chunk_id ) {

						// Chunks index is not set then define.
						if ( ! bp.Pusher_FrontCommon.chunks_event.hasOwnProperty( data.message_chunk_id ) ) {
							bp.Pusher_FrontCommon.chunks_event[ data.message_chunk_id ] = { chunks: [] };
						}

						// Get current event by chunk id.
						var current_event = bp.Pusher_FrontCommon.chunks_event[ data.message_chunk_id ];

						// Push all chunks in single object.
						current_event.chunks[ data.message_chunk_index ] = data.message_chunk;

						// Check the chunks is final chunk and received all chunks.
						if ( data.message_chunk_final && current_event.chunks.length === data.message_total_chunk ) {
							bp.Pusher_FrontCommon.renderMessage( JSON.parse( current_event.chunks.join( '' ) ) );
							delete bp.Pusher_FrontCommon.chunks_event[ data.message_chunk_id ];
						}

					} else {
						// If chunks is not available then it will call directly.
						bp.Pusher_FrontCommon.renderMessage( data );
					}

				}
			);

			// When message sent/compose thread.
			bp.Pusher_FrontCommon.bindWithPusherChunking(
				channel,
				'client-bb-pro-after-message-ajax-complete',
				function( data )
				{
					if ( undefined !== typeof data.message ) {
						bp.Pusher_FrontCommon.renderAjaxUpdateMessage( data );
						bp.Pusher_FrontCommon.updateInboxMessageUnreadCount( data.recipient_inbox_unread_counts );
						bp.Pusher_FrontCommon.bb_update_header_messages();

						$( window ).trigger( 'scroll' );
					} else {
						if ( 'undefined' !== typeof window.wp.heartbeat ) {
							window.wp.heartbeat.connectNow();
						}

						// Compose message update right sidebar if user is in current thread.
						if ( parseInt( data.thread_id ) > 0 && parseInt( data.thread_id ) === parseInt( bb_pusher_vars.current_thread_id ) ) {
							var hash = new Date().getTime();
							bp.Nouveau.Messages.router.navigate( 'view/' + parseInt( data.thread_id ) + '/?hash=' + hash, { trigger: true } );
						}

						// Compose message update left sidebar.
						if ( 'undefined' !== typeof window.Backbone.trigger ) {
							window.Backbone.trigger( 'relistelements' );
						}
					}
				}
			);

			channel.bind(
				'client-bb-pro-thread-delete-message',
				function ( data ) {
					if ( 'undefined' !== typeof window.Backbone.trigger && 'undefined' !== typeof data.thread_id ) {
						window.Backbone.trigger( 'onMessageDeleteSuccess', data );
						bp.Pusher_FrontCommon.bb_update_header_messages();
					}
				}
			);

			channel.bind(
				'client-bb-pro-after-ajax-fail',
				function ( data ) {
					if ( 'undefined' !== typeof window.Backbone.trigger && 'undefined' !== typeof data.thread_id && parseInt( data.thread_id ) === parseInt( bb_pusher_vars.current_thread_id ) ) {
						window.Backbone.trigger( 'onMessageAjaxFail', data );
					}
				}
			);

			// Member is typing.
			channel.bind(
				'client-bb-pro-typing-start',
				function ( data ) {
					var t_id = parseInt( data.thread_id );
					var u_id = parseInt( data.user_id );

					// Make the user online.
					bp.Nouveau.updateUserPresence( u_id, 'online' );

					if ( u_id === parseInt( bb_pusher_vars.loggedin_user_id ) ) {
						return;
					}

					if ( ! (t_id in bp.Pusher_FrontCommon.typer_data) ) {
						bp.Pusher_FrontCommon.typer_data[ t_id ] = [];
					}

					if ( ! (u_id in bp.Pusher_FrontCommon.typer_data[ t_id ]) ) {
						bp.Pusher_FrontCommon.typer_data[t_id][ u_id ] = data.username;
					}

					var filtered = bp.Pusher_FrontCommon.typer_data[t_id].filter(
						function ( el ) {
							return el != null;
						}
					);

					if ( parseInt( t_id ) === parseInt( bb_pusher_vars.current_thread_id ) ) {
						bp.Pusher_FrontCommon.singleThreadTypingIndicator( filtered );
					}

					bp.Pusher_FrontCommon.threadListTypingIndicator( t_id, filtered );

					clearTimeout( bp.Pusher_FrontCommon.clearTimerId[t_id] );
					bp.Pusher_FrontCommon.clearTimerId[t_id] = setTimeout(
						function () {
							bp.Pusher_FrontCommon.threadTypingEnd( t_id, u_id );
						},
						bp.Pusher_FrontCommon.clearInterval
					);
				}
			);

			// Member stop typing.
			channel.bind(
				'client-bb-pro-typing-end',
				function ( data ) {
					bp.Pusher_FrontCommon.threadTypingEnd( data.thread_id, data.user_id );
				}
			);

			// Group message member joined/left/ban/unban.
			channel.bind(
				'client-bb-pro-group-message-group-update-notify',
				function ( data ) {
					var action = data.action;

					if ( '' === action ) {
						return;
					}

					var thread_hash = data.thread_id, thread_id = 0;
					var user_id     = data.sender_id, hash = '';

					bp.Nouveau.updateUserPresence( parseInt( user_id ), 'online' );

					if (
						'undefined' !== typeof bb_pusher_vars.bb_pro_pusher_thread_ids &&
						'undefined' !== typeof bb_pusher_vars.bb_pro_pusher_thread_ids[ thread_hash ]
					) {
						thread_id = parseInt( bb_pusher_vars.bb_pro_pusher_thread_ids[ thread_hash ] );
					}

					if (
						action === 'group_message_group_joined' ||
						action === 'group_message_group_un_ban'
					) {

						if ( 'undefined' !== typeof window.wp.heartbeat ) {
							window.wp.heartbeat.connectNow();
						}

						if ( 'undefined' !== typeof window.Backbone.trigger ) {
							window.Backbone.trigger( 'relistelements' );
						}

						if ( thread_id > 0 && parseInt( thread_id ) === parseInt( bb_pusher_vars.current_thread_id ) && 'undefined' !== typeof bp.Nouveau.Messages ) {
							hash = new Date().getTime();
							bp.Nouveau.Messages.router.navigate( 'view/' + thread_id + '/?hash=' + hash, { trigger: true } );
						}

					} else if (
						action === 'group_message_group_promoted' ||
						action === 'group_message_group_demoted'
					) {

						if ( thread_id > 0 && parseInt( thread_id ) === parseInt( bb_pusher_vars.current_thread_id ) && 'undefined' !== typeof bp.Nouveau.Messages ) {
							hash = new Date().getTime();
							bp.Nouveau.Messages.router.navigate( 'view/' + thread_id + '/?hash=' + hash, { trigger: true } );
						}

					} else if (
						action === 'group_message_group_left' ||
						action === 'group_message_group_ban' ||
						action === 'groups_remove_member'
					) {

						if ( parseInt( user_id ) === parseInt( bb_pusher_vars.loggedin_user_id ) && thread_id > 0 && 'undefined' !== typeof bp.Nouveau.Messages ) {
							bp.Nouveau.Messages.threads.remove( bp.Nouveau.Messages.threads.get( thread_id ) );

							if ( thread_id > 0 && bb_pusher_vars.bb_pro_pusher_thread_ids[ thread_hash ] ) {
								delete bb_pusher_vars.bb_pro_pusher_thread_ids[ thread_hash ];
							}
						}

						if ( thread_id > 0 && thread_id === parseInt( bb_pusher_vars.current_thread_id ) ) {
							hash = new Date().getTime();
							if ( parseInt( user_id ) === parseInt( bb_pusher_vars.loggedin_user_id ) ) {
								var next_thread    = $( '.message-lists .thread-item:first-child' );
								var next_thread_id = next_thread.find( '.bp-message-link' ).attr( 'data-thread-id' );

								if ( parseInt( next_thread_id ) === thread_id ) {
									next_thread_id = next_thread.next( '.thread-item' ).find( '.bp-message-link' ).attr( 'data-thread-id' );
								}

								var group_name    = $( '.single-message-thread-header .participants-name' ).text();
								var toast_message = '';

								if ( action === 'group_message_group_left' ) {
									toast_message = bb_pusher_vars.i18n.thread_left + group_name;
								} else if ( action === 'group_message_group_ban' ) {
									toast_message = bb_pusher_vars.i18n.group_banned + group_name;
								} else if ( action === 'groups_remove_member' ) {
									toast_message = bb_pusher_vars.i18n.remove_from_group + group_name;
								}

								jQuery( document ).trigger(
									'bb_trigger_toast_message',
									[
										'',
										toast_message,
										'info',
										null,
										true
									]
								);

								if ( 'undefined' !== typeof next_thread_id && 'undefined' !== typeof bp.Nouveau.Messages ) {
									bb_pusher_vars.current_thread_id = parseInt( next_thread_id );
									bp.Nouveau.Messages.router.navigate( 'view/' + next_thread_id + '/?hash=' + hash, { trigger: true } );
								} else if ( 'undefined' !== typeof bp.Nouveau.Messages ) {
									bb_pusher_vars.current_thread_id = 0;
									bp.Nouveau.Messages.router.navigate( 'compose/', { trigger: true } );
								}

							} else if ( 'undefined' !== typeof bp.Nouveau.Messages ) {
								bp.Nouveau.Messages.router.navigate( 'view/' + thread_id + '/?hash=' + hash, { trigger: true } );
							}
						}

						if ( parseInt( user_id ) === parseInt( bb_pusher_vars.loggedin_user_id ) ) {
							if ( 'undefined' !== typeof window.Backbone.trigger ) {
								window.Backbone.trigger( 'relistelements' );
							}

							if ( 'undefined' !== typeof window.wp.heartbeat ) {
								window.wp.heartbeat.connectNow();
							}
						}
					}
				}
			);

			// When group thread was deleted with group delete.
			channel.bind(
				'client-bb-pro-group-thread-deleted',
				function ( data ) {
					var thread_hash = data.thread_id, thread_id = 0;

					if (
						'undefined' !== typeof bb_pusher_vars.bb_pro_pusher_thread_ids &&
						'undefined' !== typeof bb_pusher_vars.bb_pro_pusher_thread_ids[ thread_hash ]
					) {
						thread_id = parseInt( bb_pusher_vars.bb_pro_pusher_thread_ids[ thread_hash ] );
					}

					if ( thread_id > 0 && thread_id === parseInt( bb_pusher_vars.current_thread_id ) ) {
						var content = data.group_name + bb_pusher_vars.i18n.deleted;

						if ( bp.Nouveau.Messages.threads.length > 1 ) {
							bp.Nouveau.Messages.threads.remove( parseInt( thread_id ) );
							bb_pusher_vars.current_thread_id = parseInt( bp.Nouveau.Messages.threads.at( 0 ).id );
							bp.Nouveau.Messages.router.navigate( 'view/' + bb_pusher_vars.current_thread_id + '/', { trigger: true } );
						} else {
							BP_Nouveau.messages.hasThreads   = false;
							bb_pusher_vars.current_thread_id = 0;
							bp.Nouveau.Messages.router.navigate( 'compose/', { trigger: true } );
						}

						if ( 'undefined' !== typeof window.wp.heartbeat ) {
							window.wp.heartbeat.connectNow();
						}

						jQuery( document ).trigger(
							'bb_trigger_toast_message',
							[
								'',
								content,
								'info',
								null,
								true
							]
						);

					} else {
						bp.Nouveau.Messages.threads.remove( parseInt( thread_id ) );

						if ( 'undefined' !== typeof window.Backbone.trigger && 'no' === bb_pusher_vars.is_admin ) {
							window.Backbone.trigger( 'relistelements' );
						}

						if ( 'undefined' !== typeof window.wp.heartbeat ) {
							window.wp.heartbeat.connectNow();
						}
					}
				}
			);

			// When changed group message settings.
			channel.bind(
				'client-bb-pro-group-setting-update',
				function ( data ) {
					var thread_hash = data.thread_id, thread_id = 0;

					if (
						'undefined' !== typeof bb_pusher_vars.bb_pro_pusher_thread_ids &&
						'undefined' !== typeof bb_pusher_vars.bb_pro_pusher_thread_ids[thread_hash]
					) {
						thread_id = parseInt( bb_pusher_vars.bb_pro_pusher_thread_ids[thread_hash] );
					}

					if ( thread_id === 0 ) {
						return;
					}

					if (
						bb_pusher_vars.current_thread_id === thread_id &&
						'no' === bb_pusher_vars.is_admin &&
						-1 !== $.inArray( bb_pusher_vars.alien_hash, data.member_ids )
					) {
						var hash = new Date().getTime();
						window.location.href.replace( window.location.search, '' );
						bp.Nouveau.Messages.router.navigate( 'view/' + bb_pusher_vars.current_thread_id + '/?hash=' + hash, { trigger: true } );
					}
				}
			);

			// Admin Deletes thread.
			channel.bind(
				'client-bb-pro-thread-delete',
				function ( data ) {
					var thread_hash = data.thread_id, thread_id = 0;
					var recipients  = data.recipients;

					if (
						'undefined' !== typeof bb_pusher_vars.bb_pro_pusher_thread_ids &&
						'undefined' !== typeof bb_pusher_vars.bb_pro_pusher_thread_ids[ thread_hash ]
					) {
						thread_id = parseInt( bb_pusher_vars.bb_pro_pusher_thread_ids[ thread_hash ] );
					}

					if ( parseInt( thread_id ) === 0 ) {
						return;
					}

					if ( parseInt( thread_id ) === parseInt( bb_pusher_vars.current_thread_id ) ) {
						var content = '';
						if ( 'undefined' !== typeof data.group_name ) {
							content = data.group_name + bb_pusher_vars.i18n.deleted;
							bp.Pusher_FrontCommon.createCookie( 'bb-message-group-thread-deleted', content, 5 );
						} else {

							var recipients_object = bp.Pusher_FrontCommon.json2array( recipients );
							var names             = recipients_object.map(
								function ( row ) {
									if ( row.user_id !== bb_pusher_vars.alien_hash ) {
										return row.name;
									}
								}
							);

							names = names.filter(
								function ( el ) {
									return el != null;
								}
							);

							content = wp.i18n.sprintf( bb_pusher_vars.i18n.conversion_delete, names.join( ', ' ) );
							bp.Pusher_FrontCommon.createCookie( 'bb-thread-delete', content, 5 );
						}

						if ( 'undefined' !== typeof BP_Nouveau && 'undefined' !== typeof BP_Nouveau.messages && 'undefined' !== typeof BP_Nouveau.messages.message_url ) {
							window.location.href = BP_Nouveau.messages.message_url;
						} else {
							window.location.reload();
						}

					} else {
						var thread_list_element = $( '#message-threads li .bp-message-link[data-thread-id="' + thread_id + '"]' );
						if ( thread_list_element.length > 0 ) {
							thread_list_element.parents( '.thread-item' ).remove();
						}

						var header_dropdown = $( '#header-messages-dropdown-elem li[data-thread-id="' + thread_id + '"]' );
						if ( header_dropdown.length > 0 ) {
							header_dropdown.remove();
						}

						if ( recipients[ bb_pusher_vars.alien_hash ] ) {
							recipients = bp.Pusher_FrontCommon.json2array( recipients );
							bp.Pusher_FrontCommon.updateInboxMessageUnreadCount( recipients );
							bp.Pusher_FrontCommon.bb_update_header_messages();

							if ( thread_id > 0 && bb_pusher_vars.bb_pro_pusher_thread_ids[ thread_hash ] ) {
								delete bb_pusher_vars.bb_pro_pusher_thread_ids[thread_hash];
							}
						}
					}

				}
			);

			/* --Listen Events */
		},

		threadTypingEnd: function( thread_id, user_id ) {
			var t_id = parseInt( thread_id );
			var u_id = parseInt( user_id );

			// Make the user online.
			bp.Nouveau.updateUserPresence( u_id, 'online' );

			if ( ! (t_id in bp.Pusher_FrontCommon.typer_data) ) {
				bp.Pusher_FrontCommon.typer_data[ t_id ] = [];
			}

			if ( user_id in bp.Pusher_FrontCommon.typer_data[ t_id ] ) {
				delete bp.Pusher_FrontCommon.typer_data[ t_id ][ u_id ];
			}

			var filtered = bp.Pusher_FrontCommon.typer_data[ t_id ].filter(
				function ( el ) {
					return el != null;
				}
			);

			if ( parseInt( t_id ) === parseInt( bb_pusher_vars.current_thread_id ) ) {
				bp.Pusher_FrontCommon.singleThreadTypingIndicator( filtered );
			}

			bp.Pusher_FrontCommon.threadListTypingIndicator( t_id, filtered );
		},

		singleThreadTypingIndicator: function( filtered ) {

			if ( filtered.length > 0 && $( '.bp-messages-content .bp-messages-notice' ).find( '.bp-user-messages-feedback' ).length < 1 ) {
				var typing_text = filtered.length + bb_pusher_vars.i18n.two_typing;

				if ( 1 === filtered.length ) {
					typing_text = '<strong>' + filtered.join( '</strong>, <strong>' ) + '</strong>' + bb_pusher_vars.i18n.single_typing;
				} else if ( 2 === filtered.length ) {
					typing_text = '<strong>' + filtered.join( '</strong>' + bb_pusher_vars.i18n.separator + '<strong>' ) + '</strong>' + bb_pusher_vars.i18n.typing;
				} else if ( filtered.length > 2 ) {
					typing_text = '<strong>' + filtered[ 0 ] + '</strong>' + bb_pusher_vars.i18n.separator + (filtered.length - 1) + bb_pusher_vars.i18n.multiple_typing;
				}

				$( '.bb-pusher-typing-indicator .bb-pusher-typing-indicator-text-inner' ).html( typing_text );
				$( '.bb-pusher-typing-indicator' ).removeClass( 'bp-hide' );
			} else {
				$( '.bb-pusher-typing-indicator' ).addClass( 'bp-hide' );
			}
		},

		threadListTypingIndicator: function( thread_id, filtered ) {
			var list_element = $( '#message-threads li .bp-message-link[data-thread-id="' + thread_id + '"]' );
			if ( filtered.length > 0 && ( 0 === $( '.bp-messages-notice' ).find( '.bp-user-messages-feedback' ).length || 'unarchived' !== bp.Nouveau.Messages.threadType ) ) {
				var typing_text = filtered.length + bb_pusher_vars.i18n.two_typing;

				if ( 1 === filtered.length ) {
					typing_text = filtered.join( ', ' ) + bb_pusher_vars.i18n.single_typing;
				}

				list_element.find( '.typing-indicator' )
					.html( typing_text )
					.removeClass( 'bp-hide' );
				list_element.find( '.thread-excerpt' ).addClass( 'bp-hide' );
			} else {
				list_element.find( '.typing-indicator' ).html( '' ).addClass( 'bp-hide' );
				list_element.find( '.thread-excerpt' ).removeClass( 'bp-hide' );
			}

			// Adding the support for the header dropdown navigation.
			var header_dropdown = $( '#header-messages-dropdown-elem li[data-thread-id="' + thread_id + '"]' );
			if ( header_dropdown.length > 0 ) {
				if ( filtered.length > 0 && 0 === $( '.bp-messages-notice' ).find( '.bp-user-messages-feedback' ).length ) {
					var display_text = filtered.length + bb_pusher_vars.i18n.two_typing;

					if ( 1 === filtered.length ) {
						display_text = filtered.join( ', ' ) + bb_pusher_vars.i18n.single_typing;
					}

					header_dropdown.find( '.typing-indicator' )
						.html( display_text )
						.removeClass( 'bp-hide' );
					header_dropdown.find( '.posted' ).addClass( 'bp-hide' );
				} else {
					header_dropdown.find( '.typing-indicator' ).html( '' ).addClass( 'bp-hide' );
					header_dropdown.find( '.posted' ).removeClass( 'bp-hide' );
				}
			}
		},

		pusherUpdateChannel: function ( element ) {

			if ( $( element.currentTarget ).hasClass( 'optionsOpen' ) ) {
				return;
			}
			var thread_id = $( element.currentTarget ).find( '.bp-message-link' ).data( 'thread-id' );
			if ( thread_id ) {
				bb_pusher_vars.current_thread_id = parseInt( thread_id );
				bb_pusher_vars.thread_channel    = 'private-bb-message-thread-' + thread_id;
				if (
					parseInt( thread_id ) !== 0 &&
					-1 !== $.inArray( parseInt( thread_id ), bb_pusher_vars.bb_pro_pusher_thread_ids )
				) {
					bp.Pusher_FrontCommon.pusherSubscribeThreadsChannels( thread_id );
				}
			}
		},

		pusherCompose: function () {
			bb_pusher_vars.current_thread_id = 0;
			bb_pusher_vars.thread_channel    = '';
		},

		pusherMessageTypingEvent: function ( element ) {
			var context = $( element.currentTarget ).text();
			if (
				0 !== parseInt( bb_pusher_vars.current_thread_id ) &&
				element.keyCode !== 13 &&
				element.keyCode !== 9 &&
				element.keyCode !== 16
			) {
				var key = 'live_message_' + bb_pusher_vars.current_thread_id;

				var filter_html       = document.createElement( 'div' );
				filter_html.innerHTML = context;
				var content_text      = filter_html.textContent || filter_html.innerText || '';

				setTimeout(
					function () {
						if (
							'' !== content_text &&
							bb_pusher_vars[ key ]
						) {
							if ( bp.Pusher_FrontCommon.can_type ) {
								bp.Pusher_FrontCommon.can_type = false;
								bb_pusher_vars[ key ].trigger(
									'client-bb-pro-typing-start',
									{
										username: bb_pusher_vars.loggedin_user_name,
										user_id: bb_pusher_vars.loggedin_user_id,
										thread_id: bb_pusher_vars.current_thread_id,
									}
								);

								setTimeout(
									function () {
										bp.Pusher_FrontCommon.can_type = true;
									},
									bp.Pusher_FrontCommon.throttleTime
								);
							}

						} else if ( '' === content_text && bb_pusher_vars[ key ] ) {
							bp.Pusher_FrontCommon.can_type = true;
							bb_pusher_vars[ key ].trigger(
								'client-bb-pro-typing-end',
								{
									username: bb_pusher_vars.loggedin_user_name,
									user_id: bb_pusher_vars.loggedin_user_id,
									thread_id: bb_pusher_vars.current_thread_id,
								}
							);
						}
					},
					300
				);
			}
		},

		pusherModeration: function( globalChannel ) {

			// Member suspend.
			globalChannel.bind(
				'client-bb-pro-member-suspended',
				function ( data ) {

					if ( data.user_id ) {
						bb_pusher_vars.suspended_users_ids[data.user_id] = 0;

						if ( 'undefined' !== typeof window.wp.heartbeat ) {
							window.wp.heartbeat.connectNow();
						}
					}

					if ( data.user_id === bb_pusher_vars.alien_hash ) {
						window.location.reload();
					} else {

						var suspended_user_threads_hash = data.thread_ids;
						var common_threads              = [];
						if ( suspended_user_threads_hash.length > 0 ) {
							$.each(
								suspended_user_threads_hash,
								function ( index, thread_hash ) {
									if (
										'undefined' !== typeof bb_pusher_vars.bb_pro_pusher_thread_ids &&
										'undefined' !== typeof bb_pusher_vars.bb_pro_pusher_thread_ids[ thread_hash ]
									) {
										common_threads.push( parseInt( bb_pusher_vars.bb_pro_pusher_thread_ids[ thread_hash ] ) );
									}
								}
							);
						}

						if ( common_threads.length > 0 ) {
							$.each(
								common_threads,
								function ( index, item_id ) {
									if ( parseInt( item_id ) === parseInt( bb_pusher_vars.current_thread_id ) ) {
										var hash = new Date().getTime();
										window.location.href.replace( window.location.search, '' );
										bp.Nouveau.Messages.router.navigate( 'view/' + item_id + '/?hash=' + hash, { trigger: true } );
									}
								}
							);

							if ( 'undefined' !== typeof window.Backbone.trigger ) {
								window.Backbone.trigger( 'relistelements' );
							}
						}

					}

				}
			);

			// Member deleted.
			globalChannel.bind(
				'client-bb-pro-member-deleted',
				function ( data ) {

					if ( data.user_id === bb_pusher_vars.alien_hash ) {
						window.location.reload();
					} else {

						var deleted_user_threads_hash = data.thread_ids;
						var common_threads            = [];
						if ( deleted_user_threads_hash.length > 0 ) {
							$.each(
								deleted_user_threads_hash,
								function ( index, thread_hash ) {
									if (
										'undefined' !== typeof bb_pusher_vars.bb_pro_pusher_thread_ids &&
										'undefined' !== typeof bb_pusher_vars.bb_pro_pusher_thread_ids[ thread_hash ]
									) {
										common_threads.push( parseInt( bb_pusher_vars.bb_pro_pusher_thread_ids[ thread_hash ] ) );
									}
								}
							);
						}

						if ( common_threads.length > 0 ) {
							$.each(
								common_threads,
								function ( index, item_id ) {
									if ( parseInt( item_id ) === parseInt( bb_pusher_vars.current_thread_id ) ) {
										var hash = new Date().getTime();
										window.location.href.replace( window.location.search, '' );
										bp.Nouveau.Messages.router.navigate( 'view/' + item_id + '/?hash=' + hash, { trigger: true } );
									}
								}
							);

							if ( 'undefined' !== typeof window.Backbone.trigger ) {
								window.Backbone.trigger( 'relistelements' );
							}
						}
					}

				}
			);

			// Member unsuspend.
			globalChannel.bind(
				'client-bb-pro-member-unsuspended',
				function ( data ) {

					var unsuspended_user_threads_hash = data.thread_ids;
					var common_threads                = [];
					if ( unsuspended_user_threads_hash.length > 0 ) {
						$.each(
							unsuspended_user_threads_hash,
							function ( index, thread_hash ) {
								if (
									'undefined' !== typeof bb_pusher_vars.bb_pro_pusher_thread_ids &&
									'undefined' !== typeof bb_pusher_vars.bb_pro_pusher_thread_ids[ thread_hash ]
								) {
									common_threads.push( parseInt( bb_pusher_vars.bb_pro_pusher_thread_ids[ thread_hash ] ) );
								}
							}
						);
					}

					if ( data.user_id && 'undefined' !== typeof bb_pusher_vars.suspended_users_ids[data.user_id] ) {
						delete bb_pusher_vars.suspended_users_ids[data.user_id];
					}

					if ( common_threads.length > 0 ) {
						$.each(
							common_threads,
							function ( index, item_id ) {
								if ( parseInt( item_id ) === parseInt( bb_pusher_vars.current_thread_id ) ) {
									var hash = new Date().getTime();
									window.location.href.replace( window.location.search, '' );
									bp.Nouveau.Messages.router.navigate( 'view/' + item_id + '/?hash=' + hash, { trigger: true } );
								}
							}
						);

						if ( 'undefined' !== typeof window.Backbone.trigger ) {
							window.Backbone.trigger( 'relistelements' );
						}
					}

				}
			);
		},

		pusherComponents: function( globalChannel ) {
			// Component Active/Deactivate.
			globalChannel.bind(
				'client-bb-pro-active-components',
				function ( data ) {
					var previous_component = data.previous;
					var updated_component  = data.updated;

					// Deactivate "Private Messaging" component.
					if ( previous_component.messages && typeof updated_component.messages === 'undefined' ) {
						if ( typeof bb_pusher_vars.current_thread_id !== 'undefined' ) {

							var content = bb_pusher_vars.i18n.disabled_private_message;
							bp.Pusher_FrontCommon.createCookie( 'bb-message-component-disabled', content, 5 );

							window.location.href = bb_pusher_vars.home_url;
						} else {

							// Remove header dropdown with icon.
							if ( $( document.body ).find( '.bb-message-dropdown-notification' ).length ) {
								$( document.body ).find( '.bb-message-dropdown-notification' ).remove();
							}

							// Remove message link.
							if ( $( document.body ).find( '.bp-messages-nav' ).length ) {
								$( document.body ).find( '.bp-messages-nav' ).remove();
							}

							// Remove compose link.
							if ( $( document.body ).find( '.bp-compose-messages-nav' ).length ) {
								$( document.body ).find( '.bp-compose-messages-nav' ).remove();
							}

							// Remove inbox link.
							if ( $( document.body ).find( '.bp-inbox-sub-nav' ).length ) {
								$( document.body ).find( '.bp-inbox-sub-nav' ).remove();
							}

							// Remove items from the admin bar profile dropdown.
							if ( $( document.body ).find( '#wp-admin-bar-my-account-messages' ).length ) {
								$( document.body ).find( '#wp-admin-bar-my-account-messages' ).remove();
							}

							// Remove items from the user profile dropdown.
							if ( $( document.body ).find( '.header-aside-inner #wp-admin-bar-my-account-messages' ).length ) {
								$( document.body ).find( '.header-aside-inner #wp-admin-bar-my-account-messages' ).remove();
							}
						}
					}
				}
			);
		},

		pusherSettingsUpdate: function( globalChannel ) {
			// Component Active/Deactivate.
			globalChannel.bind(
				'client-bb-pro-pusher-settings-change',
				function () {
					window.location.reload();
				}
			);
		},

		bb_unserialize: function ( serializedString ) {
			var str   = encodeURIComponent( serializedString );
			str       = str.replace( '&', '%26' ).replace( '=', '%3D' );
			var pairs = str.split( '%26' );
			var obj   = {}, p, idx;
			for ( var i = 0, n = pairs.length; i < n; i++ ) {
				p   = pairs[ i ].split( '%3D' );
				idx = p[ 0 ];

				if ( idx.indexOf( '[]' ) === (idx.length - 2) ) {
					var ind = idx.substring( 0, idx.length - 2 );
					if ( obj[ ind ] === undefined ) {
						obj[ ind ] = [];
					}
					obj[ decodeURIComponent( ind ) ].push( decodeURIComponent( p[ 1 ] ) );
				} else {
					obj[ decodeURIComponent( idx ) ] = decodeURIComponent( p[ 1 ] );
				}
			}
			return obj;
		},

		renderMessage: function ( data ) {
			var senderdata  = [];
			data.is_deleted = 0;
			data.is_new     = true;
			data.is_starred = false;
			data.star_link  = '';
			data.date       = new Date();

			if ( typeof data.gif !== 'undefined' && Object.keys( data.gif ).length < 1 ) {
				delete data.gif;
			}

			if ( typeof data.video !== 'undefined' && data.video.length < 1 ) {
				delete data.video;
			}

			if ( typeof data.document !== 'undefined' && data.document.length < 1 ) {
				delete data.document;
			}

			if ( typeof data.media !== 'undefined' && data.media.length < 1 ) {
				delete data.media;
			}

			// remove typing event on sent.
			bp.Pusher_FrontCommon.threadTypingEnd( parseInt( data.thread_id ), parseInt( data.sender_id ) );

			var blocked_user_ids = [];
			Object.values( bb_pusher_vars.blocked_users_ids ).forEach(
				function ( val ) {
					blocked_user_ids[ parseInt( val.id ) ] = val;
				}
			);

			// Support for blocked members.
			if ( blocked_user_ids.length > 0 && 'undefined' !== typeof blocked_user_ids[parseInt( data.sender_id )] ) {
				data.is_user_blocked = true;
				data.sender_name     = blocked_user_ids[parseInt( data.sender_id )].blocked_user_name;
				data.sender_avatar   = blocked_user_ids[parseInt( data.sender_id )].blocked_avatar_url;
				data.sender_link     = '';
				if ( bb_pusher_vars.blocked_message_text ) {
					data.content = bb_pusher_vars.blocked_message_text;

					if ( typeof data.gif !== 'undefined' ) {
						delete data.gif;
					}

					if ( typeof data.video !== 'undefined' ) {
						delete data.video;
					}

					if ( typeof data.document !== 'undefined' ) {
						delete data.document;
					}

					if ( typeof data.media !== 'undefined' ) {
						delete data.media;
					}
				}
			}

			// Support for suspended members.
			if ( $.inArray( parseInt( data.sender_id ), bb_pusher_vars.suspended_users_ids ) > -1 ) {
				data.is_user_suspended = true;
				data.sender_name       = bb_pusher_vars.suspended_avatar;
				data.sender_avatar     = bb_pusher_vars.suspended_avatar;
				data.sender_link       = '';
				if ( bb_pusher_vars.suspended_message_text ) {
					data.content = bb_pusher_vars.suspended_message_text;

					if ( typeof data.gif !== 'undefined' ) {
						delete data.gif;
					}

					if ( typeof data.video !== 'undefined' ) {
						delete data.video;
					}

					if ( typeof data.document !== 'undefined' ) {
						delete data.document;
					}

					if ( typeof data.media !== 'undefined' ) {
						delete data.media;
					}
				}
			}

			var blocked_by_user_ids = [];
			Object.values( bb_pusher_vars.is_blocked_by_users ).forEach(
				function ( val ) {
					blocked_by_user_ids[ parseInt( val.id ) ] = val;
				}
			);

			// Is user blocked by.
			if ( blocked_by_user_ids.length > 0 && 'undefined' !== typeof blocked_by_user_ids[parseInt( data.sender_id )] ) {
				data.is_user_blocked_by = true;
				data.sender_name        = blocked_by_user_ids[parseInt( data.sender_id )].blocked_user_name;
				data.sender_avatar      = blocked_by_user_ids[parseInt( data.sender_id )].blocked_avatar_url;

				if ( bb_pusher_vars.blocked_by_message_text ) {
					data.content = bb_pusher_vars.blocked_by_message_text;

					if ( typeof data.gif !== 'undefined' ) {
						delete data.gif;
					}

					if ( typeof data.video !== 'undefined' ) {
						delete data.video;
					}

					if ( typeof data.document !== 'undefined' ) {
						delete data.document;
					}

					if ( typeof data.media !== 'undefined' ) {
						delete data.media;
					}
				}
			}

			senderdata.push( data );
			if ( 'undefined' !== typeof window.Backbone.trigger && 'undefined' !== typeof data.thread_id && parseInt( data.thread_id ) === parseInt( bb_pusher_vars.current_thread_id ) ) {
				window.Backbone.trigger( 'onSentMessage', senderdata );
			}

			if ( 'undefined' !== typeof window.Backbone.trigger ) {
				var dataComplete = {
					hash: data.hash,
					message: data,
					thread_id: data.thread_id
				};
				window.Backbone.trigger( 'relistelements', dataComplete );
			}

		},

		renderAjaxUpdateMessage: function ( data ) {
			var blocked_user_ids = [];
			Object.values( bb_pusher_vars.blocked_users_ids ).forEach(
				function ( val ) {
					blocked_user_ids[ parseInt( val.id ) ] = val;
				}
			);

			var blocked_by_user_ids = [];
			Object.values( bb_pusher_vars.is_blocked_by_users ).forEach(
				function ( val ) {
					blocked_by_user_ids[ parseInt( val.id ) ] = val;
				}
			);

			if ( 'undefined' !== typeof data.thread_id && 'undefined' !== typeof data.message ) {
				// Support for Blocked members.
				if ( blocked_user_ids.length > 0 && 'undefined' !== typeof blocked_user_ids[parseInt( data.message.sender_id )] ) {
					data.message.is_user_blocked = true;
					data.message.sender_name     = blocked_user_ids[parseInt( data.message.sender_id )].blocked_user_name; // bb_pusher_vars.blocked_user_name;
					data.message.sender_avatar   = blocked_user_ids[parseInt( data.message.sender_id )].blocked_avatar_url; // bb_pusher_vars.blocked_avatar;
					data.message.sender_link     = '';
					if ( bb_pusher_vars.blocked_message_text ) {
						data.message.content = bb_pusher_vars.blocked_message_text;

						if ( typeof data.message.gif !== 'undefined' ) {
							delete data.message.gif;
						}

						if ( typeof data.message.video !== 'undefined' ) {
							delete data.message.video;
						}

						if ( typeof data.message.document !== 'undefined' ) {
							delete data.message.document;
						}

						if ( typeof data.message.media !== 'undefined' ) {
							delete data.message.media;
						}
					}
				}

				// Support for suspended members.
				if ( $.inArray( parseInt( data.message.sender_id ), bb_pusher_vars.suspended_users_ids ) > -1 ) {
					data.message.is_user_suspended = true;
					data.message.sender_name       = bb_pusher_vars.suspended_avatar;
					data.message.sender_avatar     = bb_pusher_vars.suspended_avatar;
					data.message.sender_link       = '';
					if ( bb_pusher_vars.suspended_message_text ) {
						data.content = bb_pusher_vars.suspended_message_text;

						if ( typeof data.message.gif !== 'undefined' ) {
							delete data.message.gif;
						}

						if ( typeof data.message.video !== 'undefined' ) {
							delete data.message.video;
						}

						if ( typeof data.message.document !== 'undefined' ) {
							delete data.message.document;
						}

						if ( typeof data.message.media !== 'undefined' ) {
							delete data.message.media;
						}
					}
				}

				// Is user blocked by.
				if ( blocked_by_user_ids.length > 0 && 'undefined' !== typeof blocked_by_user_ids[parseInt( data.message.sender_id )] ) {
					data.message.is_user_blocked_by = true;
					data.message.sender_name        = blocked_by_user_ids[parseInt( data.message.sender_id )].blocked_user_name;
					data.message.sender_avatar      = blocked_by_user_ids[parseInt( data.message.sender_id )].blocked_avatar_url;

					if ( bb_pusher_vars.blocked_by_message_text ) {
						data.message.content = bb_pusher_vars.blocked_by_message_text;

						if ( typeof data.message.gif !== 'undefined' ) {
							delete data.message.gif;
						}

						if ( typeof data.message.video !== 'undefined' ) {
							delete data.message.video;
						}

						if ( typeof data.message.document !== 'undefined' ) {
							delete data.message.document;
						}

						if ( typeof data.message.media !== 'undefined' ) {
							delete data.message.media;
						}
					}
				}
			}

			if ( 'undefined' !== typeof window.Backbone.trigger && 'undefined' !== typeof data.thread_id && parseInt( data.thread_id ) === parseInt( bb_pusher_vars.current_thread_id ) ) {
				window.Backbone.trigger( 'onReplySentSuccess', data );
			}

			if ( 'undefined' !== typeof window.Backbone.trigger ) {
				window.Backbone.trigger( 'relistelements', data );
			}
		},

		renderUpdateSendingMessage: function ( data ) {

			var senderdata  = [];
			data.is_deleted = 0;
			data.is_new     = true;
			data.is_starred = false;
			data.star_link  = '';
			data.date       = new Date();

			if ( typeof data.gif !== 'undefined' && Object.keys( data.gif ).length < 1 ) {
				delete data.gif;
			}

			if ( typeof data.video !== 'undefined' && data.video.length < 1 ) {
				delete data.video;
			}

			if ( typeof data.document !== 'undefined' && data.document.length < 1 ) {
				delete data.document;
			}

			if ( typeof data.media !== 'undefined' && data.media.length < 1 ) {
				delete data.media;
			}

			senderdata.push( data );
			if ( 'undefined' !== typeof window.Backbone.trigger && 'undefined' !== typeof data.thread_id && parseInt( data.thread_id ) === parseInt( bb_pusher_vars.current_thread_id ) ) {
				window.Backbone.trigger( 'onReplyReSend', senderdata );
			}
		},

		renderAjaxUpdateFailedMessage: function ( data ) {
			if ( 'undefined' !== typeof window.Backbone.trigger && 'undefined' !== typeof data.thread_id && parseInt( data.thread_id ) === parseInt( bb_pusher_vars.current_thread_id ) ) {
				window.Backbone.trigger( 'onSentMessageError', data );
			}
		},

		updateInboxMessageUnreadCount: function ( data ) {
			if ( data ) {
				data.forEach(
					function ( item ) {

						if ( item.user_id === bb_pusher_vars.alien_hash ) {
							item.user_id = parseInt( bb_pusher_vars.loggedin_user_id );
						}

						var totalCount = parseInt( item.inbox_unread_count );

						if ( parseInt( bb_pusher_vars.current_thread_id ) === parseInt( item.thread_id ) && parseInt( bb_pusher_vars.loggedin_user_id ) === parseInt( item.user_id ) ) {
							totalCount = totalCount - parseInt( item.current_thread_unread_count );
							$.ajax(
								{
									type: 'POST',
									url: bb_pusher_vars.ajax_url,
									data: {
										'action': 'bb_pusher_update_current_thread_unread_count',
										'thread_id': item.thread_id,
										'current_user_id': bb_pusher_vars.loggedin_user_id,

									},
									success: function () {

									},
									error: function () {
										return false;
									},
								}
							);
						}

						if ( parseInt( bb_pusher_vars.current_thread_id ) !== parseInt( item.thread_id ) ) {
							if ( $( document.body ).find( '.bb-message-dropdown-notification' ).length && $( document.body ).find( '.bb-message-dropdown-notification .bb-member-unread-count-span-' + item.user_id ).length ) {
								if ( $( document.body ).find( '.bb-message-dropdown-notification .bb-member-unread-count-span-' + item.user_id + ' span.count' ).length && totalCount > 0 ) {
									$( document.body ).find( '.bb-message-dropdown-notification .bb-member-unread-count-span-' + item.user_id + ' span.count' ).text( totalCount );
								} else if ( $( document.body ).find( '.bb-message-dropdown-notification .bb-member-unread-count-span-' + item.user_id + ' span.count' ).length && 0 === totalCount ) {
									$( document.body ).find( '.bb-message-dropdown-notification .bb-member-unread-count-span-' + item.user_id + ' span.count' ).remove();
								} else if ( 0 === $( document.body ).find( '.bb-message-dropdown-notification .bb-member-unread-count-span-' + item.user_id + ' span.count' ).length && totalCount > 0 ) {
									$( document.body ).find( '.bb-message-dropdown-notification .bb-member-unread-count-span-' + item.user_id ).append( '<span class="count">' + totalCount + '</span>' );
								}
							}
							if ( $( document.body ).find( '.bp-buddypanel-menu-item-messages-count-' + item.user_id ).length && totalCount > 0 ) {
								$( document.body ).find( '.bp-buddypanel-menu-item-messages-count-' + item.user_id ).each(
									function () {
										if ( $( this ).find( '.bb-messages-inbox-unread-count' ).length ) {
											$( this ).find( '.bb-messages-inbox-unread-count' ).text( totalCount ).removeClass( 'bp-hide' );
										} else {
											$( this ).find( '> a' ).append( '<span class="count bb-messages-inbox-unread-count">' + totalCount + '</span>' );
										}
									}
								);

							}
						}
					}
				);
			}

		},

		bb_update_header_messages: function () {
			if ( $( document.body ).find( '#header-messages-dropdown-elem' ).length ) {
				if ( bp.Pusher_FrontCommon.xhr ) {
					return;
				}
				$( document.body ).find( '#header-messages-dropdown-elem ul.notification-list' ).html( '<p class="bb-header-loader"><i class="bb-icon-loader animate-spin"></i></p>' );
				bp.Pusher_FrontCommon.xhr = $.get(
					ajaxurl,
					{ action: 'buddyboss_theme_get_header_unread_messages' },
					function ( response ) {
						if ( response.success && typeof response.data !== 'undefined' && typeof response.data.contents !== 'undefined' && $( '#header-messages-dropdown-elem ul.notification-list' ).length ) {
							$( document.body ).find( '#header-messages-dropdown-elem ul.notification-list' ).html( response.data.contents );
							bp.Pusher_FrontCommon.xhr = false;
						}
					}
				);
			}
		},

		json2array: function ( json ) {
			var result = [];
			var keys   = Object.keys( json );
			keys.forEach(
				function ( key ) {
					result.push( json[ key ] );
				}
			);
			return result;
		},

		arrayIntersect: function ( first, second ) {
			$( first ).filter( second );
		},

		createCookie: function ( name, value, minutes ) {
			var expires = '';
			if ( minutes ) {
				var date = new Date();
				date.setTime( date.getTime() + (minutes * 60 * 1000) );
				expires = '; expires=' + date.toGMTString();
			}
			document.cookie = name + '=' + value + expires + '; path=/';
		},

		readCookie: function ( name ) {
			var nameEQ = name + '=';
			var ca     = document.cookie.split( ';' );

			var cookies_length = ca.length;
			for ( var i = 0; i < cookies_length; i++ ) {
				var c = ca[ i ];
				while ( c.charAt( 0 ) === ' ' ) {
					c = c.substring( 1, c.length );
				}
				if ( c.indexOf( nameEQ ) === 0 ) {
					return c.substring( nameEQ.length, c.length );
				}
			}
			return null;
		},

		/* jshint ignore:start */
		bbParseParams: function ( query ) {

			// recursive function to construct the result object.
			function createElement( params, key, value ) {
				key = key + '';

				// if the key is a property.
				if ( key.indexOf( '.' ) !== -1 ) {
					// extract the first part with the name of the object.
					var list = key.split( '.' );

					// the rest of the key.
					var new_key = key.split( /\.(.+)?/ )[ 1 ];

					// create the object if it doesnt exist.
					if ( ! params[ list[ 0 ] ] ) {
						params[ list[ 0 ] ] = {};
					}

					// if the key is not empty, create it in the object.
					if ( new_key !== '' ) {
						createElement( params[ list[ 0 ] ], new_key, value );
					}
				} else {
					// if the key is an array.
					if ( key.indexOf( '[' ) !== -1 ) {
						// extract the array name.
						var list = key.split( '[' );
						key      = list[ 0 ];

						// extract the index of the array.
						var list  = list[ 1 ].split( ']' );
						var index = list[ 0 ];

						// if index is empty, just push the value at the end of the array.
						if ( index == '' ) {
							if ( ! params ) {
								params = {};
							}
							if ( ! params[ key ] || ! $.isArray( params[ key ] ) ) {
								params[ key ] = [];
							}
							params[ key ].push( value );
							// add the value at the index (must be an integer).
						} else {
							if ( ! params ) {
								params = {};
							}
							if ( ! params[ key ] || ! $.isArray( params[ key ] ) ) {
								params[ key ] = [];
							}
							params[ key ][ parseInt( index ) ] = value;
						}
						// just normal key.
					} else {
						if ( ! params ) {
							params = {};
						}
						params[ key ] = value;
					}
				}
			}

			// be sure the query is a string.
			query = query + '';

			if ( query === '' ) {
				query = window.location + '';
			}

			var params = {}, e;
			if ( query ) {
				// remove # from end of query.
				if ( query.indexOf( '#' ) !== -1 ) {
					query = query.substr( 0, query.indexOf( '#' ) );
				}

				// remove ? at the begining of the query.
				if ( query.indexOf( '?' ) !== -1 ) {
					query = query.substr( query.indexOf( '?' ) + 1, query.length );
				} else {
					return {};
				}

				// empty parameters.
				if ( query == '' ) {
					return;
				}

				// execute a createElement on every key and value.
				while ( e = re.exec( query ) ) {
					var key   = decode( e[ 1 ] );
					var value = decode( e[ 2 ] );
					createElement( params, key, value );
				}
			}
			return params;
		},
		/* jshint ignore:end */

		/**
		 *  Secure Hash Algorithm (SHA1)
		 **/
		SHA1: function ( msg ) {
			function rotate_left( n, s ) {
				var t4 = (n << s) | (n >>> (32 - s));
				return t4;
			}

			function cvt_hex( val ) {
				var str = '';
				var i;
				var v;
				for ( i = 7; i >= 0; i-- ) {
					v    = (val >>> (i * 4)) & 0x0f;
					str += v.toString( 16 );
				}
				return str;
			}

			function Utf8Encode( string ) {
				string      = string.replace( /\r\n/g, '\n' );
				var utftext = '';
				for ( var n = 0; n < string.length; n++ ) {
					var c = string.charCodeAt( n );
					if ( c < 128 ) {
						utftext += String.fromCharCode( c );
					} else if ( (c > 127) && (c < 2048) ) {
						utftext += String.fromCharCode( (c >> 6) | 192 );
						utftext += String.fromCharCode( (c & 63) | 128 );
					} else {
						utftext += String.fromCharCode( (c >> 12) | 224 );
						utftext += String.fromCharCode( ((c >> 6) & 63) | 128 );
						utftext += String.fromCharCode( (c & 63) | 128 );
					}
				}
				return utftext;
			}

			var blockstart;
			var i, j;
			var W  = new Array( 80 );
			var H0 = 0x67452301;
			var H1 = 0xEFCDAB89;
			var H2 = 0x98BADCFE;
			var H3 = 0x10325476;
			var H4 = 0xC3D2E1F0;
			var A, B, C, D, E;
			var temp;
			msg            = Utf8Encode( msg );
			var msg_len    = msg.length;
			var word_array = [];
			for ( i = 0; i < msg_len - 3; i += 4 ) {
				j = msg.charCodeAt( i ) << 24 | msg.charCodeAt( i + 1 ) << 16 |
					msg.charCodeAt( i + 2 ) << 8 | msg.charCodeAt( i + 3 );
				word_array.push( j );
			}
			switch ( msg_len % 4 ) {
				case 0:
					i = 0x080000000;
					break;
				case 1:
					i = msg.charCodeAt( msg_len - 1 ) << 24 | 0x0800000;
					break;
				case 2:
					i = msg.charCodeAt( msg_len - 2 ) << 24 | msg.charCodeAt( msg_len - 1 ) << 16 | 0x08000;
					break;
				case 3:
					i = msg.charCodeAt( msg_len - 3 ) << 24 | msg.charCodeAt( msg_len - 2 ) << 16 | msg.charCodeAt( msg_len - 1 ) << 8 | 0x80;
					break;
			}
			word_array.push( i );
			while ( (word_array.length % 16) != 14 ) {
				word_array.push( 0 );
			}
			word_array.push( msg_len >>> 29 );
			word_array.push( (msg_len << 3) & 0x0ffffffff );
			for ( blockstart = 0; blockstart < word_array.length; blockstart += 16 ) {
				for ( i = 0; i < 16; i++ ) {
					W[ i ] = word_array[ blockstart + i ];
				}
				for ( i = 16; i <= 79; i++ ) {
					W[ i ] = rotate_left( W[ i - 3 ] ^ W[ i - 8 ] ^ W[ i - 14 ] ^ W[ i - 16 ], 1 );
				}
				A = H0;
				B = H1;
				C = H2;
				D = H3;
				E = H4;
				for ( i = 0; i <= 19; i++ ) {
					temp = (rotate_left( A, 5 ) + ((B & C) | (~B & D)) + E + W[ i ] + 0x5A827999) & 0x0ffffffff;
					E    = D;
					D    = C;
					C    = rotate_left( B, 30 );
					B    = A;
					A    = temp;
				}
				for ( i = 20; i <= 39; i++ ) {
					temp = (rotate_left( A, 5 ) + (B ^ C ^ D) + E + W[ i ] + 0x6ED9EBA1) & 0x0ffffffff;
					E    = D;
					D    = C;
					C    = rotate_left( B, 30 );
					B    = A;
					A    = temp;
				}
				for ( i = 40; i <= 59; i++ ) {
					temp = (rotate_left( A, 5 ) + ((B & C) | (B & D) | (C & D)) + E + W[ i ] + 0x8F1BBCDC) & 0x0ffffffff;
					E    = D;
					D    = C;
					C    = rotate_left( B, 30 );
					B    = A;
					A    = temp;
				}
				for ( i = 60; i <= 79; i++ ) {
					temp = (rotate_left( A, 5 ) + (B ^ C ^ D) + E + W[ i ] + 0xCA62C1D6) & 0x0ffffffff;
					E    = D;
					D    = C;
					C    = rotate_left( B, 30 );
					B    = A;
					A    = temp;
				}
				H0 = (H0 + A) & 0x0ffffffff;
				H1 = (H1 + B) & 0x0ffffffff;
				H2 = (H2 + C) & 0x0ffffffff;
				H3 = (H3 + D) & 0x0ffffffff;
				H4 = (H4 + E) & 0x0ffffffff;
			}
			temp = cvt_hex( H0 ) + cvt_hex( H1 ) + cvt_hex( H2 ) + cvt_hex( H3 ) + cvt_hex( H4 );
			return temp.toLowerCase();
		},

		triggerPusherChunked: function( pusher, event, data ) {
			var str       = JSON.stringify( data );
			var strSize   = ( new Blob( [ str ] ) ).size;
			var chunkSize = parseInt( bb_pusher_vars.content_size_limit ); // Size in bytes.
			var chunk     = Math.ceil( strSize / chunkSize );
			var msgId     = Math.random();

			if ( 1 < chunk ) {
				for ( var i = 1; i <= chunk; i++ ) {
					pusher.trigger(
						event,
						{
							message_chunk_id: msgId,
							message_chunk_index: ( i - 1 ),
							message_total_chunk: chunk,
							message_chunk: str.substr( ( i - 1 ) * chunkSize, chunkSize ),
							message_chunk_final: chunkSize * ( i ) >= strSize
						}
					);
				}
			} else {
				pusher.trigger( event, data );
			}
		},

		bindWithPusherChunking: function( channel, event, callback ) {
			channel.bind( event, callback ); // Allow normal unchunked events.

			// Now the chunked variation. Allows arbitrarily long messages.
			channel.bind(
				'chunked-' + event,
				function( data )
				{
					// Chunks index is not set then define.
					if ( ! bp.Pusher_FrontCommon.chunks_event.hasOwnProperty( data.message_chunk_id ) ) {
						bp.Pusher_FrontCommon.chunks_event[ data.message_chunk_id ] = { chunks: [] };
					}

					// Get current event by chunk id.
					var current_event = bp.Pusher_FrontCommon.chunks_event[ data.message_chunk_id ];

					// Push all chunks in single object.
					current_event.chunks[ data.message_chunk_index ] = data.message_chunk;

					// Check the chunks is final chunk and received all chunks.
					if ( data.message_chunk_final && current_event.chunks.length === data.message_total_chunk ) {
						callback( JSON.parse( current_event.chunks.join( '' ) ) );
						delete bp.Pusher_FrontCommon.chunks_event[ data.message_chunk_id ];
					}
				}
			);
		},

		getWPCurrentTime: function() {
			var now_date = new Date();

			// ET timezone offset in hours.
			var timezone = bb_pusher_vars.wp_offset;

			// Timezone offset in minutes + the desired offset in minutes, converted to ms.
			// This offset should be the same for ALL date calculations, so you should only need to calculate it once.
			var offset = ( now_date.getTimezoneOffset() + ( timezone * 60 ) ) * 60 * 1000;

			// Or update the timestamp to reflect the timezone offset.
			now_date.setTime( now_date.getTime() + offset );

			var hours   = now_date.getHours(),
				minutes = now_date.getMinutes(),
				ampm    = hours >= 12 ? bb_pusher_vars.i18n.post_meridiem : bb_pusher_vars.i18n.ante_meridiem;

			// Covert hours into the 12 hours format.
			hours = hours % 12;
			hours = hours ? hours : 12;

			// Adds '0' if minutes less than 10.
			if ( minutes < 10 ) {
				minutes = '0' + minutes;
			}

			// Return the formatted time.
			return hours + ':' + minutes + ' ' + ampm;
		},

		removeFailedMessage: function( data ) {
			if ( true === bp.Pusher_FrontCommon.worker_enabled ) {
				bp.bb_pusher_shared.sendMessage( 'onCancelRemoveMessage', data );
			}
		}
	};

	/* jshint ignore:start */
	var re     = /([^&=]+)=?([^&]*)/g;
	var decode = function (str) {
		return decodeURIComponent( str.replace( /\+/g, ' ' ) );
	};
	/* jshint ignore:end */

	// Launch Pusher Common.
	bp.Pusher_FrontCommon.start();

})( bp, jQuery );
