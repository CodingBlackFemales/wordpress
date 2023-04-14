<?php

define( 'PLUGINIZE_LICENSE_PAGE_BUDDYPAGES', 'buddypages_plugin_dashboard' );

/**
 * Add our menu item.
 *
 * @since unsure
 */
function buddypages_license_menu() {

	/**
	 * Filters the minimum capability need to manage BuddyPages license.
	 *
	 * @since unsure
	 *
	 * @param string $value Minimum capability. Default 'manage_options'.
	 */
	$capability = apply_filters( 'buddypages_capabilities', 'manage_options' );
	add_options_page( __( 'BuddyPages License', 'buddypages' ), __( 'BuddyPages', 'buddypages' ), $capability, 'buddypages_plugin_dashboard', 'buddypages_plugin_dashboard' );
}
add_action( 'admin_menu', 'buddypages_license_menu', 10, 2 );

/**
 * Render our EDD-based license page.
 *
 * @since 1.4.0
 */
function buddypages_plugin_dashboard() {
	$license = get_option( 'buddypages_license_key' );
	$status  = get_option( 'buddypages_license_status' );
	$active = false;
	?>
	<div class="wrap">
		<h2><?php echo get_admin_page_title(); ?></h2>
		<form method="post" action="options.php">

			<?php settings_fields( 'buddypages_license' ); ?>

			<p><?php esc_html_e( 'Thank you for activating your BuddyPages license.', 'buddypages' ); ?></p>
			<table class="form-table">
				<tbody>
					<tr valign="top">
						<th scope="row" valign="top">
							<?php esc_html_e( 'License Key', 'buddypages' ); ?>
						</th>
						<td>
							<input id="buddypages_license_key" name="buddypages_license_key" type="text" class="regular-text" value="<?php esc_attr_e( $license ); ?>" />
							<label class="description" for="buddypages_license_key"><?php esc_html_e( 'Enter your license key', 'buddypages' ); ?></label>
						</td>
					</tr>
					<?php if ( false !== $license ) {
						$active = ( $status !== false && $status == 'valid' );
						?>
						<tr valign="top">
							<th scope="row" valign="top">
								<?php esc_html_e( 'Activate License', 'buddypages' ); ?>
							</th>
							<td>
								<?php wp_nonce_field( 'buddypages_license_nonce', 'buddypages_license_nonce' ); ?>
								<?php if ( $active ) { ?>
									<input type="submit" class="button-secondary" name="edd_license_deactivate" value="<?php esc_attr_e( 'Deactivate License', 'buddypages' ); ?>"/>
								<?php } else { ?>
									<input type="submit" class="button-secondary" name="buddypages_license_activate" value="<?php esc_attr_e( 'Activate License', 'buddypages' ); ?>"/>
								<?php } ?>
							</td>
						</tr>
					<?php }

					if ( $active ) { ?>
						<tr valign="top">
							<th scope="row" valign="top">
								<?php esc_html_e( 'Status:', 'buddypages' ); ?>
							</th>
							<td>
								<strong style="color:green;"><?php esc_html_e( 'active', 'buddypages' ); ?></strong>
							</td>
						</tr>
					<?php } ?>
				</tbody>
			</table>
			<?php submit_button(); ?>
		</form>
	<?php
}

/**
 * Register our setting.
 *
 * @since 1.4.0
 */
function buddypages_register_option() {
	// Creates our settings in the options table.
	register_setting( 'buddypages_license', 'buddypages_license_key', 'buddypages_sanitize_license' );
}
add_action( 'admin_init', 'buddypages_register_option' );

/**
 * Sanitize our license.
 *
 * @since 1.4.0
 *
 * @param string $new License key.
 * @return mixed
 */
function buddypages_sanitize_license( $new ) {
	$old = get_option( 'buddypages_license_key' );
	if ( $old && $old != $new ) {
		delete_option( 'buddypages_license_status' ); // New license has been entered, so must reactivate.
	}
	return $new;
}

/**
 * Activate our license.
 *
 * @since 1.4.0
 */
function buddypages_activate_license() {

	if ( empty( $_POST ) || ! isset( $_POST['buddypages_license_activate'] ) ) {
		return;
	}

	// Run a quick security check.
	if ( ! check_admin_referer( 'buddypages_license_nonce', 'buddypages_license_nonce' ) ) {
		return;
	}

	$response = buddypages_activate_deactivate( 'activate_license' );

	// Make sure the response came back okay.
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {

		if ( is_wp_error( $response ) ) {
			$message = $response->get_error_message();
		} else {
			$message = __( 'An error occurred, please try again.', 'buddypages' );
		}
	} else {

		$license_data = json_decode( wp_remote_retrieve_body( $response ) );

		if ( false === $license_data->success ) {
			switch ( $license_data->error ) {

				case 'expired' :
					$message = sprintf(
						__( 'Your license key expired on %s.', 'buddypages' ),
						date_i18n( get_option( 'date_format' ), strtotime( $license_data->expires, current_time( 'timestamp' ) ) )
					);
					break;

				case 'revoked' :
					$message = __( 'Your license key has been disabled.', 'buddypages' );
					break;

				case 'missing' :
					$message = __( 'Invalid license.', 'buddypages' );
					break;

				case 'invalid' :
				case 'site_inactive' :
					$message = __( 'Your license is not active for this URL.', 'buddypages' );
					break;

				case 'item_name_mismatch' :
					$message = sprintf( __( 'This appears to be an invalid license key for %s.', 'buddypages' ), buddypages()->plugin_name );
					break;

				case 'no_activations_left':
					$message = __( 'Your license key has reached its activation limit.', 'buddypages' );
					break;

				default :
					$message = __( 'An error occurred, please try again.', 'buddypages' );
					break;
			}
		}
	}

	if ( ! empty( $message ) ) {
		$base_url = admin_url( 'admin.php?page=' . PLUGINIZE_LICENSE_PAGE_BUDDYPAGES );
		$redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

		wp_redirect( $redirect );
		exit();
	}

	update_option( 'buddypages_license_status', $license_data->license );
	wp_redirect( admin_url( 'admin.php?page=' . PLUGINIZE_LICENSE_PAGE_BUDDYPAGES ) );
	exit();
}
add_action( 'admin_init', 'buddypages_activate_license' );

/**
 * Deactivate our license.
 *
 * @since 1.4.0
 */
function buddypages_deactivate_license() {

	if ( empty( $_POST ) || ! isset( $_POST['edd_license_deactivate'] ) ) {
		return;
	}

	// Run a quick security check.
	if ( ! check_admin_referer( 'buddypages_license_nonce', 'buddypages_license_nonce' ) ) {
		return;
	}

	$response = buddypages_activate_deactivate( 'deactivate_license' );

	// Make sure the response came back okay.
	if ( is_wp_error( $response ) || 200 !== wp_remote_retrieve_response_code( $response ) ) {
		if ( is_wp_error( $response ) ) {
			$message = $response->get_error_message();
		} else {
			$message = __( 'An error occurred, please try again.', 'buddypages' );
		}

		$base_url = admin_url( 'plugins.php?page=' . PLUGINIZE_LICENSE_PAGE_BUDDYPAGES );
		$redirect = add_query_arg( array( 'sl_activation' => 'false', 'message' => urlencode( $message ) ), $base_url );

		wp_redirect( $redirect );
		exit();
	}

	// Decode the license data.
	$license_data = json_decode( wp_remote_retrieve_body( $response ) );

	// $license_data->license will be either "deactivated" or "failed"
	if ( $license_data->license == 'deactivated' ) {
		delete_option( 'buddypages_license_status' );
	}

	wp_redirect( admin_url( 'admin.php?page=' . PLUGINIZE_LICENSE_PAGE_BUDDYPAGES ) );
	exit();
}
add_action( 'admin_init', 'buddypages_deactivate_license' );

/**
 * Process a license request.
 *
 * @since 1.4.0
 *
 * @param string $action Action being performed. Either deactivate or activate. Default activate.
 * @return array|WP_Error
 */
function buddypages_activate_deactivate( $action = 'activate_license' ) {
	// Retrieve the license from the database.
	$license = trim( get_option( 'buddypages_license_key' ) );

	// Data to send in our API request.
	$api_params = array(
		'edd_action' => $action,
		'license'    => $license,
		'item_name'  => urlencode( buddypages()->plugin_name ), // The name of our product in EDD.
		'url'        => home_url(),
	);

	return wp_remote_post( buddypages()->store_url, array( 'timeout' => 15, 'sslverify' => false, 'body' => $api_params ) );
}

/**
 * This is a means of catching errors from the activation method above and displaying it to the customer.
 *
 * @since 1.4.0
 */
function buddypages_admin_notices() {
	if ( isset( $_GET['sl_activation'] ) && ! empty( $_GET['message'] ) ) {
		if ( isset( $_GET['page'] ) && PLUGINIZE_LICENSE_PAGE_BUDDYPAGES === $_GET['page'] ) {
			switch ( $_GET['sl_activation'] ) {
				case 'false':
					$message = urldecode( $_GET['message'] );
					?>
					<div class="error">
						<p><?php echo $message; ?></p>
					</div>
					<?php
					break;

				case 'true':
				default:
					break;
			}
		}
	}
}
add_action( 'admin_notices', 'buddypages_admin_notices' );
