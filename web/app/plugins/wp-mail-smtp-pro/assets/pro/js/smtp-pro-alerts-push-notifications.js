/* global WPMailSMTPPushNotificationsSettings */
'use strict';

var WPMailSMTP = window.WPMailSMTP || {};
WPMailSMTP.Admin = WPMailSMTP.Admin || {};
WPMailSMTP.Admin.Alerts = WPMailSMTP.Admin.Alerts || {};

/**
 * WP Mail SMTP Admin area Push Notifications Alerts module.
 *
 * @since 4.4.0
 */
WPMailSMTP.Admin.Alerts.PushNotifications = WPMailSMTP.Admin.Alerts.PushNotifications || ( function( document, window, $, settings ) {

	/**
	 * Public functions and properties.
	 *
	 * @since 4.4.0
	 *
	 * @type {object}
	 */
	var app = {

		/**
		 * Local storage key holding local subscription details.
		 *
		 * @since 4.4.0
		 */
		localSubscriptionKey: 'WPMailSMTPPushNotificationsSubscription',

		/**
		 * Public server key provided by the remote API.
		 *
		 * @since 4.4.0
		 */
		publicKey: settings.publicKey,

		/**
		 * Start the engine. DOM is not ready yet, use only to init something.
		 *
		 * @since 4.4.0
		 */
		init: function() {

			// Do that when DOM is ready.
			$( app.ready );
		},

		/**
		 * DOM is fully loaded.
		 *
		 * @since 4.4.0
		 */
		ready: async function() {

			if ( $( '#wp-mail-smtp-setting-alert-push_notifications-enabled' ).is( ':checked' ) ) {
				await app.refreshSubscriptions();

				app.highlightCurrentSubscription();
				app.refreshSubscribeButton();
			}

			app.bindActions();
		},

		/**
		 * Process all generic actions/events, mostly custom that were fired by our API.
		 *
		 * @since 4.4.0
		 */
		bindActions: function() {

			$( '.wp-mail-smtp-tab-alerts' )
				.on( 'click', '#wp-mail-smtp-setting-alert-push_notifications-enabled', app.toggleSettings )
				.on( 'click', '#wp-mail-smtp-setting-alert-push_notifications-subscribe', app.addSubscription )
				.on( 'click', '.js-wp-mail-smtp-setting-alert-push_notifications-remove-subscription', app.removeSubscription );
		},

		/**
		 * Check requirements.
		 *
		 * @since 4.4.0
		 */
		checkRequirements: function() {

			// Bail if push notifications are not supported.
			if (
				! ( 'serviceWorker' in navigator ) ||
				! ( 'PushManager' in window ) ||
				! ( 'Notification' in window )
			) {
				throw new Error( `${settings.notices.unsupported}.` );
			}

			// Bail if push notifications aren't granted.
			if ( Notification.permission === 'denied' ) {
				throw new Error( `${settings.notices.permission_denied}.` );
			}
		},

		/**
		 * Get local subscription details.
		 *
		 * @since 4.4.0
		 *
		 * @returns {object|null} Local subscription, or null if not found.
		 */
		getLocalSubscription() {

			let subscription = localStorage.getItem( app.localSubscriptionKey );

			try {
				return subscription ? JSON.parse( subscription ) : null;
			} catch ( error ) {
				return null;
			}
		},

		/**
		 * Store local subscription details.
		 *
		 * @since 4.4.0
		 *
		 * @param {object} subscription Subscription data.
		 */
		setLocalSubscription: function( subscription ) {

			localStorage.setItem( app.localSubscriptionKey, JSON.stringify( subscription ) );
		},

		/**
		 * Get push manager from service worker.
		 *
		 * @since 4.4.0
		 */
		getPushManager: async function() {

			try {
				const serviceWorker = await navigator.serviceWorker.register( settings.serviceWorkerUrl );

				await navigator.serviceWorker.ready;

				return serviceWorker.pushManager;
			} catch ( error ) {
				throw new Error( `${settings.notices.service_worker}: ${error.message}.` );
			}
		},

		/**
		 * Get the current device subscription, if any.
		 *
		 * @since 4.4.0
		 *
		 * @param {object} pushManager pushManager instance.
		 */
		getSubscription: async function( pushManager ) {

			try {
				return await pushManager.getSubscription();
			} catch ( error ) {
				throw new Error( `${settings.notices.subscription}: ${error.message}.` );
			}
		},

		/**
		 * Convert a base 64 encoded URL to Uint8 array.
		 *
		 * @since 4.4.0
		 *
		 * @param {string} base64String Base-64 string.
		 *
		 * @returns {Uint8Array} Converted string.
		 */
		urlBase64ToUint8Array: function( base64String ) {

			const padding = '='.repeat( ( 4 - base64String.length % 4 ) % 4 );
			const base64 = ( base64String + padding )
				.replace( /-/g, '+' )
				.replace( /_/g, '/' );

			const rawData = window.atob( base64 );
			const outputArray = new Uint8Array( rawData.length );

			for ( let i = 0; i < rawData.length; ++i ) {
				outputArray[i] = rawData.charCodeAt( i );
			}

			return outputArray;
		},

		/**
		 * Subscribe the current device.
		 *
		 * @since 4.4.0
		 */
		subscribe: async function() {

			const pushManager = await app.getPushManager();

			try {
				const subscribeOptions = {
					userVisibleOnly: true,
					applicationServerKey: app.urlBase64ToUint8Array( app.publicKey ),
				};

				return await pushManager.subscribe( subscribeOptions );
			} catch ( error ) {
				throw new Error( `${settings.notices.subscribe}: ${error.message}` );
			}
		},

		/**
		 * Unsubscribe the current device.
		 *
		 * @since 4.4.0
		 */
		unsubscribe: async function() {

			const pushManager = await app.getPushManager();
			const subscription = await app.getSubscription( pushManager );

			if ( subscription === null ) {
				return;
			}

			try {
				await subscription.unsubscribe();
			} catch ( error ) {
				throw new Error( `${settings.notices.unsubscribe}: ${error.message}` );
			}

			const localSubscription = app.getLocalSubscription();

			if ( localSubscription === null ) {
				return;
			}

			localStorage.removeItem( app.localSubscriptionKey );
		},

		/**
		 * Toggle settings.
		 *
		 * @since 4.4.0
		 *
		 * @param {Event} e DOM event.
		 */
		toggleSettings: async function( e ) {

			e.preventDefault();

			var $toggle = $( this );
			var $row = $( this ).closest( '.wp-mail-smtp-setting-row-alert' );
			var $options = $row.find( '.wp-mail-smtp-setting-row-alert-options' );
			var response;

			$toggle.prop( 'disabled', true );

			if ( $( this ).is( ':checked' ) ) {
				response = await app.enablePushAlertsSite();

				if ( response.success ) {
					$( this ).prop( 'checked', true );

					app.publicKey = response.data.public_key;
					$( '#wp-mail-smtp-push-notifications-public-key' ).val( response.data.public_key );

					app.hideNotices();
					$options.show();

					await app.refreshSubscriptions();

					app.highlightCurrentSubscription();
					app.refreshSubscribeButton();
				} else {
					app.displayNotice( 'site', `${settings.notices.request}: ${response.data.message}` );
				}
			} else {
				response = await app.disablePushAlertsSite();

				if ( response.success ) {
					$( this ).prop( 'checked', false );
					$options.hide();
					app.hideNotices();
				} else {
					app.displayNotice( 'site', `${settings.notices.request}: ${response.data.message}` );
				}
			}

			$toggle.prop( 'disabled', false );
		},

		/**
		 * Subscribe and create the remote subscription.
		 *
		 * @since 4.4.0
		 *
		 * @param {Event} e DOM event.
		 */
		addSubscription: async function( e ) {

			e.preventDefault();

			var $button = $( this );

			app.hideNotices();

			try {
				if ( await Notification.requestPermission() !== 'granted' ) {
					return;
				}

				$button.addClass( 'loading' );

				var subscription = await app.subscribe();
				var response = await app.createSubscription( subscription );

				if ( response.success ) {
					app.setLocalSubscription( response.data );
				} else {
					app.displayNotice( 'subscription', `${settings.notices.request}: ${response.data.message}` );
				}

				await app.refreshSubscriptions();

				app.highlightCurrentSubscription();
				app.refreshSubscribeButton();
			} catch ( error ) {
				app.displayNotice( 'subscription', error.message );
			} finally {
				$button.removeClass( 'loading' );

				app.refreshSubscribeButton();
			}
		},

		/**
		 * Unsubscribe and remove the remote subscription.
		 *
		 * @since 4.4.0
		 *
		 * @param {Event} e DOM event.
		 */
		removeSubscription: async function( e ) {

			e.preventDefault();

			var $button = $( this );
			var id = $( this ).closest( '.wp-mail-smtp-setting-row' ).attr( 'data-subscription-id' );
			var subscription = app.getLocalSubscription();

			app.hideNotices();

			$button.addClass( 'loading' );

			if ( subscription && subscription.id === id ) {
				try {
					await app.unsubscribe();
				} catch ( error ) {
					app.displayNotice( 'subscription', error.message );
					$button.removeClass( 'loading' );

					return;
				}
			}

			var response = await app.deleteSubscription( id );

			if ( response.success ) {
				await app.refreshSubscriptions();
				app.refreshSubscribeButton();
			} else {
				app.displayNotice( 'subscription', `${settings.notices.request}: ${response.data.message}` );
			}

			$button.removeClass( 'loading' );
		},

		/**
		 * Refresh the subscriptions list.
		 *
		 * @since 4.4.0
		 */
		refreshSubscriptions: async function() {

			var $subscriptions = $( '#wp-mail-smtp-setting-row-alert-push_notifications-subscriptions' );

			var response = await app.getSubscriptions();

			if ( response.success ) {
				$subscriptions.html( response.data );
			} else {
				app.displayNotice( 'subscription', `${settings.notices.request}: ${response.data.message}` );
			}
		},

		/**
		 * Refresh the subscribe button state.
		 *
		 * @since 4.4.0
		 */
		refreshSubscribeButton: async function() {

			try {
				app.checkRequirements();
			} catch ( error ) {
				app.displayNotice( 'subscription', error.message );

				return;
			}

			var $button = $( '#wp-mail-smtp-setting-alert-push_notifications-subscribe' );
			var subscription = app.getLocalSubscription();

			if (
				subscription &&
				$( `[data-subscription-id="${subscription.id}"]` ).length > 0
			) {
				$button.prop( 'disabled', true );
			} else {
				$button.prop( 'disabled', false );
			}
		},

		/**
		 * Highlight the current subscription.
		 *
		 * @since 4.4.0
		 */
		highlightCurrentSubscription: function() {

			var subscription = app.getLocalSubscription();

			if ( ! subscription ) {
				return;
			}

			$( `[data-subscription-id="${subscription.id}"]` ).addClass( 'current-subscription' );
		},

		/**
		 * Display an error notice.
		 *
		 * @since 4.4.0
		 *
		 * @param {string} scope Notice scope.
		 * @param {string} message Notice message.
		 */
		displayNotice: function( scope, message ) {

			var $notice = $( `#wp-mail-smtp-push-notifications-${scope}-notice` );

			$( 'p', $notice ).html( message );
			$notice.show();
		},

		/**
		 * Hide all error notices.
		 *
		 * @since 4.4.0
		 */
		hideNotices: function() {

			var $notice = $( '.wp-mail-smtp-push-notifications-notice' );

			$( 'p', $notice ).html( '' );
			$notice.hide();
		},

		/**
		 * Enable the current site on the remote API.
		 *
		 * @since 4.4.0
		 */
		enablePushAlertsSite: async function() {

			return await $.post( settings.apiUrl, {
				task: 'enable_site',
			} );
		},

		/**
		 * Disable the current site on the remote API.
		 *
		 * @since 4.4.0
		 */
		disablePushAlertsSite: async function() {

			return await $.post( settings.apiUrl, {
				task: 'disable_site',
			} );
		},

		/**
		 * Fetch subscriptions from the remote API.
		 *
		 * @since 4.4.0
		 */
		getSubscriptions: async function() {

			return await $.post( settings.apiUrl, {
				task: 'get_subscriptions',
			} );
		},

		/**
		 * Create a subscription on the remote API.
		 *
		 * @since 4.4.0
		 *
		 * @param {object} subscription Subscription data.
		 */
		createSubscription: async function( subscription ) {
			return await $.post( settings.apiUrl, {
				task: 'create_subscription',
				'user_agent': navigator.userAgent,
				details: JSON.stringify( subscription.toJSON() ),
			} );
		},

		/**
		 * Delete a subscription from the remote API.
		 *
		 * @since 4.4.0
		 *
		 * @param {string} id Subscription ID.
		 */
		deleteSubscription: async function( id ) {
			return await $.post( settings.apiUrl, {
				task: 'delete_subscription',
				'subscription_id': id,
			} );
		},
	};

	// Provide access to public functions/properties.
	return app;
}( document, window, jQuery, WPMailSMTPPushNotificationsSettings ) );

// Initialize.
WPMailSMTP.Admin.Alerts.PushNotifications.init();
