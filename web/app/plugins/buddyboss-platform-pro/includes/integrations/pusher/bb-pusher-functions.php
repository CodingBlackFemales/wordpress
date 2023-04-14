<?php
/**
 * Pusher integration helpers
 *
 * @since   2.1.6
 * @package BuddyBossPro\Pusher
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Returns Pusher Integration path.
 *
 * @since 2.1.6
 *
 * @param string $path Path to pusher integration.
 */
function bb_pusher_integration_path( $path = '' ) {
	return trailingslashit( bb_platform_pro()->integration_dir ) . 'pusher/' . trim( $path, '/\\' );
}

/**
 * Returns Pusher Integration url.
 *
 * @since 2.1.6
 *
 * @param string $path Path to pusher integration.
 */
function bb_pusher_integration_url( $path = '' ) {
	return trailingslashit( bb_platform_pro()->integration_url ) . 'pusher/' . trim( $path, '/\\' );
}

/**
 * Checks if Pusher is enabled.
 *
 * @since 2.1.6
 *
 * @param int $default Default option for pusher enable or not.
 *
 * @return bool Is pusher enabled or not.
 */
function bb_pusher_is_enabled( $default = false ) {
	return (bool) apply_filters( 'bb_pusher_is_enabled', (bool) bp_get_option( 'bb-pusher-enabled', $default ) );
}

/**
 * Return the Pusher App ID.
 *
 * @since 2.1.6
 *
 * @return mixed|void
 */
function bb_pusher_app_id() {
	return apply_filters( 'bb_pusher_app_id', bp_get_option( 'bb-pusher-app-id', '' ) );
}

/**
 * Return the Pusher App Key.
 *
 * @since 2.1.6
 *
 * @return mixed|void
 */
function bb_pusher_app_key() {
	return apply_filters( 'bb_pusher_app_key', bp_get_option( 'bb-pusher-app-key', '' ) );
}

/**
 * Return the Pusher App Secret.
 *
 * @since 2.1.6
 *
 * @return mixed|void
 */
function bb_pusher_app_secret() {
	return apply_filters( 'bb_pusher_app_secret', bp_get_option( 'bb-pusher-app-secret', '' ) );
}

/**
 * Return the Pusher App Cluster.
 *
 * @since 2.1.6
 *
 * @return mixed|void
 */
function bb_pusher_app_cluster() {
	return apply_filters( 'bb_pusher_app_cluster', bp_get_option( 'bb-pusher-app-cluster', '' ) );
}

/**
 * Return the Pusher App custom cluster.
 *
 * @since 2.1.6
 *
 * @return mixed|void
 */
function bb_pusher_app_custom_cluster() {
	return apply_filters( 'bb_pusher_app_custom_cluster', bp_get_option( 'bb-pusher-app-custom-cluster', '' ) );
}

/**
 * Return the cluster name.
 *
 * @since 2.1.6
 *
 * @return string
 */
function bb_pusher_cluster() {
	return ( 'custom' === bb_pusher_app_cluster() ? bb_pusher_app_custom_cluster() : bb_pusher_app_cluster() );
}

/**
 * Return Enabled Pusher features.
 *
 * @since 2.1.6
 *
 * @return mixed|void
 */
function bb_pusher_enabled_features() {
	return apply_filters( 'bb_pusher_enabled_features', bp_get_option( 'bb-pusher-enabled-features', array() ) );
}

/**
 * Get list of pusher features.
 *
 * @since 2.1.6
 *
 * @return array
 */
function bb_get_pusher_features() {
	$pusher_features = array(
		'live-messaging' => array(
			'value'       => 1,
			'component'   => 'messages',
			'disabled'    => ! function_exists( 'bp_is_active' ) || ! bp_is_active( 'messages' ),
			'label'       => esc_html__( 'Live Messaging', 'buddyboss-pro' ),
			'description' => esc_html__( 'When enabled, members will send and receive private messages in realtime across their devices.', 'buddyboss-pro' ),
		),
	);

	if ( ! function_exists( 'bp_is_active' ) || ! bp_is_active( 'messages' ) ) {
		$pusher_features['live-messaging']['description'] = sprintf(
			wp_kses_post(
			/* translators: BuddyBoss components link */
				__( 'To use Live Messaging, please enable the %s component.', 'buddyboss-pro' )
			),
			'<a href="' . esc_url(
				bp_get_admin_url(
					add_query_arg(
						array( 'page' => 'bp-components' ),
						'admin.php'
					)
				)
			) . '">' . esc_html__( 'Private Messaging', 'buddyboss-pro' ) . '</a>'
		);
	}

	return apply_filters( 'bb_get_pusher_features', $pusher_features );
}

/**
 * Function to check Pusher feature is enabled or not.
 *
 * @param string $key Feature key.
 *
 * @return bool
 */
function bb_pusher_is_feature_enabled( $key ) {
	$enabled_features = bb_pusher_enabled_features();
	$pusher_features  = bb_get_pusher_features();

	if ( empty( $enabled_features ) || empty( $key ) || ! isset( $enabled_features[ $key ] ) ) {
		return false;
	}

	$components = array_filter( array_combine( array_keys( $pusher_features ), array_column( $pusher_features, 'component' ) ) );

	if (
		! empty( $components ) &&
		isset( $components[ $key ] ) &&
		! empty( $components[ $key ] ) &&
		! bp_is_active( $components[ $key ] )
	) {
		return false;
	}

	return (bool) $enabled_features[ $key ];
}

/**
 * Pusher Object.
 *
 * @since 2.1.6
 *
 * @return mixed
 *
 * @throws \GuzzleHttp\Exception\GuzzleException Client Exception.
 * @throws \Pusher\PusherException Pusher Exception.
 */
function bb_pusher() {
	static $bb_pusher = null;
	if (
		class_exists( 'Pusher\Pusher' ) &&
		bb_pusher_app_key() &&
		bb_pusher_app_secret() &&
		bb_pusher_app_id() &&
		bb_pusher_cluster()
	) {
		$bb_pusher = new Pusher\Pusher( bb_pusher_app_key(), bb_pusher_app_secret(), bb_pusher_app_id(), array( 'cluster' => bb_pusher_cluster() ) );
	}

	return $bb_pusher;
}

/**
 * Check and validate the pusher credentials.
 *
 * @since 2.1.6
 *
 * @return void
 *
 * @throws \GuzzleHttp\Exception\GuzzleException Client Exception.
 * @throws \Pusher\PusherException Pusher Exception.
 */
function bb_pusher_credential_validate() {
	$bb_pusher = bb_pusher();
	if ( null !== $bb_pusher ) {
		try {
			$bb_pusher->trigger( 'bb-pusher-authenticate', 'bb-authenticate', array( 'message' => 'hello world' ) );
		} catch ( Exception $e ) {
			$error = sprintf(
			/* translators: Error Message. */
				__( 'There was a problem connecting to your Pusher Channels app: %s', 'buddyboss-pro' ),
				'[' . ( is_array( $e->getMessage() ) ? esc_html( implode( '<br/>', $e->getMessage() ) ) : $e->getMessage() ) . ']'
			);
			set_transient( 'bb_pusher_error', $error, HOUR_IN_SECONDS );
		}
	}

	$errors  = get_transient( 'bb_pusher_error' );
	$warning = get_transient( 'bb_pusher_warning' );

	if (
		empty( $errors ) &&
		empty( $warning ) &&
		! empty( bb_pusher_app_key() ) &&
		! empty( bb_pusher_app_secret() ) &&
		! empty( bb_pusher_app_id() ) &&
		! empty( bb_pusher_app_cluster() )
	) {
		bp_update_option( 'bb-pusher-enabled', true );
	} else {
		bp_delete_option( 'bb-pusher-enabled' );
	}
}

/**
 * Function to generate the hash key.
 *
 * @since 2.1.6
 *
 * @return false|mixed|string
 */
function bb_pusher_hash_key() {
	if ( defined( 'AUTH_KEY' ) && AUTH_KEY && ! empty( AUTH_KEY ) ) {
		$hash = AUTH_KEY;
	} else {
		$hash = bp_get_option( 'bb_pusher_hash_key', '' );
		if ( empty( $hash ) ) {
			$hash = wp_generate_password( 64, true, true );
			bp_update_option( 'bb_pusher_hash_key', $hash );
		}
	}

	return $hash;
}

/**
 * Function to return the thread/user hash from the id.
 *
 * @since 2.1.6
 *
 * @param int $id thread/user id.
 *
 * @return string
 */
function bb_pusher_string_hash( $id ) {
	return sha1( bb_pusher_hash_key() . ':' . $id );
}

/**
 * Function to return the user hash from the user id.
 *
 * @since 2.1.6
 *
 * @param int $user_id User id.
 *
 * @return string User hash.
 */
function bb_pusher_get_user_hash( $user_id ) {

	if ( empty( $user_id ) ) {
		return '';
	}

	$user_data = get_userdata( $user_id );
	$user_hash = '';
	if ( ! empty( $user_data ) ) {
		$user_hash = $user_data->user_pass;
		$user_hash = sha1( $user_id . $user_hash );
	}

	return $user_hash;
}

/**
 * Fire the event to pusher.
 *
 * @since 2.1.6
 *
 * @param object       $pusher         Pusher object.
 * @param string|array $pusher_channel Pusher channels.
 * @param string       $pusher_event   Pusher event.
 * @param array        $pusher_data    Pusher data.
 */
function bb_pusher_trigger_event( $pusher, $pusher_channel, $pusher_event, $pusher_data ) {
	if ( null === $pusher ) {
		$pusher = bb_pusher();
	}

	try {
		$trigger_response = $pusher->trigger( $pusher_channel, $pusher_event, $pusher_data );
	} catch ( Exception $trigger_response ) { //phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
		// @todo Send meesage to admin.
	}

}

/**
 * Function will return data limit to send via pusher.
 *
 * @since 2.1.6
 *
 * @return int
 */
function bb_pusher_get_content_size_limit() {
	return (int) apply_filters( 'bb_pusher_get_content_size_limit', 8000 );
}

/**
 * Function to get the string size in KB.
 *
 * @since 2.1.6
 *
 * @param string $string The string.
 *
 * @return string
 */
function bb_pro_get_string_size_in_kb( $string ) {
	$byte = mb_strlen( $string );
	$kb   = number_format( $byte / 1024, 4 );

	return round( $kb, 0 );
}

/**
 * Trigger an event with chunk when data is big.
 *
 * @since 2.1.6
 *
 * @param object $pusher      Pusher object.
 * @param string $channel     Pusher channel.
 * @param string $event       Pusher event.
 * @param array  $notify_data Data to send on any event.
 *
 * @return void
 */
function bb_pro_pusher_trigger_chunked_event( $pusher, $channel, $event, $notify_data ) {
	$content          = wp_json_encode( $notify_data );
	$content_size     = bb_pro_get_string_size_in_kb( $content ) * 1024;
	$chunk_size       = bb_pusher_get_content_size_limit();
	$chunk            = ceil( $content_size / $chunk_size );
	$message_chunk_id = wp_rand();

	if ( 1 < $chunk ) {
		for ( $i = 1; $i <= $chunk; $i ++ ) {
			$chunk_data                        = array();
			$chunk_data['message_chunk_id']    = $message_chunk_id;
			$chunk_data['message_chunk_index'] = ( $i - 1 );
			$chunk_data['message_total_chunk'] = $chunk;
			$chunk_data['message_chunk']       = substr( $content, ( ( $i - 1 ) * $chunk_size ), $chunk_size );
			$chunk_data['message_chunk_final'] = ( $chunk_size * $i ) >= $content_size;

			bb_pusher_trigger_event( $pusher, $channel, 'chunked-' . $event, $chunk_data );
		}
	} else {
		bb_pusher_trigger_event( $pusher, $channel, $event, $notify_data );
	}
}
