/* global clients */

'use strict';

self.addEventListener( 'push', function( event ) {
	if ( ! event.data ) {
		return;
	}

	const notification = event.data?.json() ?? {};

	if ( ! notification.title || ! notification.body ) {
		return;
	}

	event.waitUntil( self.registration.showNotification( notification.title, {
		body: notification.body,
		data: notification.data ?? {}
	} ) );
} );

self.addEventListener( 'notificationclick', function( event ) {

	// Close notification on Android.
	event.notification.close();

	const url = event.notification.data.url;

	if ( ! url ) {
		return;
	}

	event.waitUntil(
		( async() => {
			const allClients = await clients.matchAll( {
				includeUncontrolled: true,
			} );

			for ( const client of allClients ) {
				if ( client.url === url ) {
					return client.focus();
				}
			}

			return await clients.openWindow( url );
		} )()
	);
} );
