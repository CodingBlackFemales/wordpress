/* eslint complexity: "off" */
/* globals WPMailSMTPPushNotificationsSettings */
'use strict';

( async function( window, settings ) {

	const currentSubscriptionKey = 'WPMailSMTPPushNotificationsSubscription';

	// Bail if push notifications are not supported.
	if (
		! ( 'serviceWorker' in navigator ) ||
		! ( 'PushManager' in window ) ||
		! ( 'Notification' in window )
	) {
		return;
	}

	// Bail if permissions aren't granted.
	if ( Notification.permission !== 'granted' ) {
		return;
	}

	let localSubscription = localStorage.getItem( currentSubscriptionKey );

	// Bail if there is no cached subscription.
	if ( ! localSubscription ) {
		return;
	}

	try {
		localSubscription = JSON.parse( localSubscription );

		// Bail if cached subscription data is invalid or empty.
		if ( ! localSubscription ) {
			throw new Error();
		}

		const serviceWorker = await navigator.serviceWorker.register( settings.serviceWorkerUrl );
		const subscription = await serviceWorker.pushManager.getSubscription();

		// Bail if subscription endpoint hasn't changed.
		if (
			subscription === null ||
			subscription.endpoint === localSubscription.endpoint
		) {
			return;
		}

		let data = new FormData();

		data.set( 'task', 'update_subscription' );
		data.set( 'user_agent', navigator.userAgent );
		data.set( 'subscription_id', localSubscription.id );
		data.set( 'details', JSON.stringify( subscription.toJSON() ) );

		const response = await fetch( settings.apiUrl, {
			method: 'POST',
			body: data,
		} );

		const json = await response.json();

		// Bail if API request failed.
		if ( ! json.success ) {
			throw new Error();
		}

		localStorage.setItem( currentSubscriptionKey, JSON.stringify( json.data ) );
	} catch ( error ) {

		// Bail if there is no registered service worker,
		// or if there is no device subscription,
		// or the device subscription endpoint hasn't changed.
		// Remove cached subscription data.
		localStorage.removeItem( currentSubscriptionKey );
	}

}( window, WPMailSMTPPushNotificationsSettings ) );
