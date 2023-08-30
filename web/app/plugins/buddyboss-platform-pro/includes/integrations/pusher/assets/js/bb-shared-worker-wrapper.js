/* global bp, bb_pusher_vars */
/* @version 2.2.0 */

window.bp       = window.bp || {};
window.Backbone = window.Backbone || [];


(function () {
	bp.bb_pusher_shared = {
		constructor: function ( app_key, options ) {

			this.app_key        = app_key;
			this.options        = options;
			this.worker         = false;
			this.bind_callbacks = [];
			this.channels       = '';
			bp.bb_pusher_shared.startWorker();

			return bp.bb_pusher_shared;
		},

		/**
		 * Responsible for init the worker for the current client.
		 */
		startWorker: function() {
			if ( ! this.worker ) {
				// Start our new worker.
				this.worker = new SharedWorker( bb_pusher_vars.bb_pro_pusher_shared_worker_url + '?v=' + bb_pusher_vars.bb_pro_version, 'Shared Worker' );
				this.worker.port.start();
				var that = this;

				// Listen to messages from the worker.
				// Listen from the bb-pro-shared-worker.js file.
				this.worker.port.onmessage = function ( e ) {
					that.onMessage( e );
				};

				// Send Message to worker to init pusher.
				bp.bb_pusher_shared.sendMessage(
					'pusher_init',
					{
						app_key         : this.app_key,
						options         : this.options,
					}
				);
			}
		},

		/**
		 * Send Message to Worker.
		 * It will send message to bb-pro-shared-worker.js file.
		 *
		 * @param {*} type
		 * @param {*} data
		 */
		sendMessage: function( type, data ) {
			this.worker.port.postMessage(
				{
					type            : type,
					data            : data,
					current_user_id : bb_pusher_vars.loggedin_user_id,
				}
			);
		},

		/**
		 * Listen to Worker Messages.
		 * It will listen to messages from bb-pro-shared-worker.js file.
		 */
		onMessage: function( e ) {

			var data = e.data;

			switch ( data.type ) {
				case 'pusher_init':
					break;
				case 'pusher_event':
					bp.bb_pusher_shared.triggerBinds( data );
					break;
				case 'pusher_channels':
					bp.bb_pusher_shared.updateChannels( data );
					break;
				case 'before-message-ajax-send':
					bp.bb_pusher_shared.onSendMessage( data );
					break;
				case 'resend-message-ajax-send':
					bp.bb_pusher_shared.onResendMessage( data );
					break;
				case 'renderAjaxUpdateFailedMessage':
					bp.bb_pusher_shared.renderAjaxUpdateFailedMessage( data );
					break;
				case 'onCancelRemoveMessage':
					bp.bb_pusher_shared.onCancelRemoveMessage( data );
					break;
				case 'updateReconnect':
					bp.bb_pusher_shared.updateReconnect( data.data );
					break;
				case 'pusher_disconnected':
					bp.bb_pusher_shared.onDisconnected();
					break;
			}

		},

		/**
		 * Update Channels.
		 * It will update the channels variable.
		 *
		 * @param {array} d
		 */
		updateChannels: function( d ) {
			this.channels = d.data.channels;
		},

		/**
		 * Update Reconnect.
		 *
		 * @param {array} d
		 */
		updateReconnect: function( d ) {
			bp.bb_pusher_shared.sendMessage(
				'pusher_reconnect',
				{
					try_reconnect: d.data.try_reconnect,
				}
			);
		},

		/**
		 * Remove channel from channels variable.
		 * It will remove the channel from channels variable.
		 */
		onDisconnected: function () {
			bp.Pusher_FrontCommon.removed_channels();
		},

		/**
		 * Update message on sender side when send from another tab.
		 *
		 * @param {array} d
		 */
		onSendMessage: function( d ) {
			bp.Pusher_FrontCommon.renderMessage( d.data.data );
		},

		/**
		 * Render message on sender side while resend the message from another tab.
		 *
		 * @param {array} d
		 */
		onResendMessage: function( d ) {
			bp.Pusher_FrontCommon.renderUpdateSendingMessage( d.data.data );
		},

		/**
		 * Render failed message sender side while sent from another tab.
		 *
		 * @param {array} d
		 */
		renderAjaxUpdateFailedMessage: function( d ) {
			bp.Pusher_FrontCommon.renderAjaxUpdateFailedMessage( d.data.data );
		},

		/**
		 * Remove failed message on sender side while remove from another tab.
		 *
		 * @param {array} d
		 */
		onCancelRemoveMessage: function( d ) {
			window.Backbone.trigger( 'onCancelRemoveMessage', d.data.data );
			window.Backbone.trigger( 'relistelements' );
		},

		/**
		 * Trigger all callbacks which are bind with incoming events.
		 * check channel.bind() function.
		 * It will trigger all the bind callbacks which are bind with the incoming event.
		 * It will trigger all the bind callbacks from bb-pro-shared-worker.js file.
		 */
		triggerBinds: function( d ) {
			if ( 'undefined' !== typeof this.bind_callbacks[d.data.channel_name] && 'undefined' !== typeof this.bind_callbacks[d.data.channel_name][d.data.event] ) {
				if ( d.data.user_id === bb_pusher_vars.loggedin_user_id ) {
					this.bind_callbacks[d.data.channel_name][d.data.event].forEach(
						function ( callback ) {
							callback( d.data.data );
						}
					);
				}
			}
		},

		/**
		 * ====================================================================================
		 * API Methods. All Method below are required for Socket Service to Work.
		 * ====================================================================================
		 */

		/**
		 * Allow to subscribe the channels and bind the events.
		 * It will send message to bb-pro-shared-worker.js file.
		 *
		 * @param {string} channel_name
		 * @returns
		 */
		subscribe: function( channel_name ) {
			// Send Message.
			bp.bb_pusher_shared.sendMessage(
				'pusher_subscribe',
				{
					channel_name: channel_name,
				}
			);

			var that = this;

			return {
				// function used to listen event from channel.
				bind: function ( event, callback ) {
					// put this bind into the bind callback variable.
					if ( 'undefined' === typeof that.bind_callbacks[channel_name] ) {
						that.bind_callbacks[channel_name] = [];
					}
					if ( 'undefined' === typeof that.bind_callbacks[channel_name][event] ) {
						that.bind_callbacks[channel_name][event] = [];
					}
					that.bind_callbacks[channel_name][event].push( callback );
				},
				// function used to send event to channel.
				trigger: function ( event, data ) {
					that.sendMessage(
						'pusher_send_event',
						{
							channel_name    : channel_name,
							event           : event,
							data            : data,
							loggedin_user_id: bb_pusher_vars.loggedin_user_id,
						}
					);
				},
			};

		},

		/**
		 * Unsubscribe Channel.
		 * It will send message to bb-pro-shared-worker.js file.
		 *
		 * @param {*} channel_name
		 */
		unsubscribe: function( channel_name ) {
			// Send Message.
			bp.bb_pusher_shared.sendMessage(
				'pusher_unsubscribe',
				{
					channel_name    : channel_name,
					loggedin_user_id: bb_pusher_vars.loggedin_user_id,
				}
			);
		},

		/**
		 * Responsible for user authentication.
		 */
		signin: function() {
			// Send Message.
			bp.bb_pusher_shared.sendMessage(
				'pusher_signin',
				{}
			);
		},

		/**
		 * Connect to pusher.
		 */
		connect: function() {
			// Send Message.
			bp.bb_pusher_shared.sendMessage(
				'pusher_connect',
				{}
			);
		},

	};

})( bp, jQuery );
