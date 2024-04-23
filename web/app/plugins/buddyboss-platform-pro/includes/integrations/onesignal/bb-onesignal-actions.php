<?php
/**
 * OneSignal integration actions
 *
 * @package BuddyBoss\OneSignal
 * @since   2.0.3
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'notification_avatar_uploaded', 'bb_onesignal_notification_attachment_uploaded', 10, 3 );
add_action( 'prompt_avatar_uploaded', 'bb_onesignal_notification_attachment_uploaded', 10, 3 );
add_action( 'bp_core_delete_existing_avatar', 'bb_onesignal_delete_notification_attachment_uploaded', 10, 1 );

// Load prompt and frontend css/js.
add_action( 'wp_enqueue_scripts', 'bb_pro_onesignal_enqueue_scripts_and_styles', 99 );
add_action( 'login_head', 'bb_pro_onesignal_enqueue_scripts_and_styles', 99 );

// add web notification toggle in platform notification preference.
add_action( 'bp_members_notification_settings_after_submit', 'bb_onesignal_render_notification_browser_box', 10 );

add_action( 'wp_ajax_onesignal_update_device_info', 'bb_pro_onesignal_update_device_info' );
add_action( 'wp_ajax_nopriv_onesignal_update_device_info', 'bb_pro_onesignal_update_device_info' );

add_action( 'wp_logout', 'bb_logout_clear_player_cookie', 999 );

add_action( 'login_head', 'bb_clear_display_prompt_on_login_screen', 999 );

// Send Notifications Actions.
add_action( 'bp_notification_after_save', 'bb_pro_onesignal_notification_after_save', 999, 1 );

// OneSignal dismiss the site-wide notice.
add_action( 'wp_ajax_onesignal_dismiss_notice', 'bb_pro_onesignal_dismiss_sitewide_notice' );

/**
 * Updated options after uploading the notification attachments.
 *
 * @since 2.0.3
 *
 * @param int    $item_id Item ID.
 * @param string $type    Item Type.
 * @param array  $args    Array of arguments.
 *
 * @return void
 */
function bb_onesignal_notification_attachment_uploaded( $item_id, $type, $args ) {
	if (
		! empty( $args['object'] ) &&
		! empty( $args['avatar'] ) &&
		'notification' === $args['object']
	) {
		bp_update_option( 'bb-onesignal-default-notification-icon', $args['avatar'] );
	}
}

/**
 * Updated options after deleted the notification attachments.
 *
 * @since 2.0.3
 *
 * @param array $args Array of arguments used for avatar deletion.
 */
function bb_onesignal_delete_notification_attachment_uploaded( $args ) {
	if (
		! empty( $args['object'] ) &&
		'notification' === $args['object']
	) {
		bp_delete_option( 'bb-onesignal-default-notification-icon' );
	}
}

/**
 * Added async tag for the script tag.
 *
 * @since 2.0.3
 *
 * @param string $url Current script URL.
 *
 * @return array|mixed|string|string[]
 */
function bb_pro_onesignal_add_async_for_script( $url ) {
	if ( strpos( $url, '#asyncload' ) === false ) {
		return $url;
	} elseif ( is_admin() ) {
		return str_replace( '#asyncload', '', $url );
	} else {
		return str_replace( '#asyncload', '', $url ) . "' async='async";
	}
}

/**
 * Enqueue scripts and styles.
 *
 * @since 2.0.3
 */
function bb_pro_onesignal_enqueue_scripts_and_styles() {

	if ( ! bb_onesignal_enabled_web_push() ) {
		return;
	}

	global $wp;
	$rtl_css = is_rtl() ? '-rtl' : '';
	$min     = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';

	add_filter( 'clean_url', 'bb_pro_onesignal_add_async_for_script', 11, 1 );

	wp_register_script( 'bb-pro-onesignal-sdk', 'https://cdn.onesignal.com/sdks/OneSignalSDK.js#asyncload', array(), '151513', true );
	wp_enqueue_script( 'bb-pro-onesignal-sdk' );

	remove_filter( 'clean_url', 'bb_pro_onesignal_add_async_for_script', 11, 1 );

	wp_enqueue_style( 'bb-pro-onesignal-css', bb_onesignal_integration_url( '/assets/css/bb-onesignal' . $rtl_css . $min . '.css' ), array(), bb_platform_pro()->version );
	wp_enqueue_script( 'bb-pro-onesignal-js', bb_onesignal_integration_url( '/assets/js/bb-onesignal' . $min . '.js' ), array( 'bb-pro-onesignal-sdk' ), bb_platform_pro()->version, true );

	$data = array(
		'ajax_url'                       => bp_core_ajax_url(),
		'home_url'                       => home_url( $wp->request ),
		'is_component_active'            => (int) bp_is_active( 'notifications' ),
		'is_valid_licence'               => (int) bbp_pro_is_license_valid(),
		'is_web_push_enable'             => (int) bb_onesignal_enabled_web_push(),
		'auto_prompt_request_permission' => (int) bb_onesignal_request_permission(),
		'auto_prompt_validate'           => bb_onesignal_permission_validate(),
		'is_soft_prompt_enabled'         => (int) bb_onesignal_enable_soft_prompt(),
		'prompt_user_id'                 => (int) bp_loggedin_user_id(),
		'app_id'                         => bb_onesignal_app_id(),
		'safari_web_id'                  => ( ! empty( bb_onesignal_connected_app_details() ) ? bb_onesignal_connected_app_details()['safari_push_id'] : '' ),
		'path'                           => untrailingslashit( wp_parse_url( bb_onesignal_integration_url(), PHP_URL_PATH ) ),
		'http_path'                      => bb_onesignal_integration_url(),
		'subDomainName'                  => ( ! empty( bb_onesignal_connected_app_details() ) ? bb_onesignal_connected_app_details()['chrome_web_sub_domain'] : '' ),
	);

	if ( true === bb_onesignal_enable_soft_prompt() ) {
		$data['actionMessage']    = esc_html( bb_onesignal_soft_prompt_message_text() );
		$data['acceptButtonText'] = esc_html( bb_onesignal_soft_prompt_allow_btn_text() );
		$data['cancelButtonText'] = esc_html( bb_onesignal_soft_prompt_cancel_btn_text() );

		if ( empty( $data['actionMessage'] ) ) {
			$data['actionMessage'] = bb_onesignal_soft_prompt_message_placeholder_text();
		}
		if ( empty( $data['acceptButtonText'] ) ) {
			$data['acceptButtonText'] = bb_onesignal_soft_prompt_allow_btn_placeholder_text();
		}
		if ( empty( $data['cancelButtonText'] ) ) {
			$data['cancelButtonText'] = bb_onesignal_soft_prompt_cancel_btn_placeholder_text();
		}
	}

	wp_localize_script(
		'bb-pro-onesignal-js',
		'bb_onesignal_vars',
		$data
	);

}

/**
 * Callback function to render the browser notification settings box.
 *
 * @since 2.0.3
 *
 * @return void
 */
function bb_onesignal_render_notification_browser_box() {
	// phpcs:ignore
	$cookie_player_id = ( isset( $_COOKIE ) && ! empty( $_COOKIE['bbpro-player-id'] ) ? $_COOKIE['bbpro-player-id'] : '' );
	$browser_name     = bb_onesignal_get_current_browser();
	?>
	<div class="bb-onesignal-render-browser-block <?php echo ( empty( $browser_name ) || ! bb_onesignal_enabled_web_push() ? 'bp-hide' : '' ); ?>">
		<?php echo bb_onesignal_add_web_notification_toggle( $cookie_player_id, $browser_name, true, true ); // phpcs:ignore ?>
	</div>
	<?php
}

/**
 * Generate the browser preview to enable/disable notification.
 *
 * @since 2.0.3
 *
 * @param string $player_id      Player id.
 * @param string $browser        Browser name.
 * @param bool   $browser_status Current status of browser.
 * @param bool   $loader         Show loader or not.
 *
 * @return false|string|void
 */
function bb_onesignal_add_web_notification_toggle( $player_id = '', $browser = '', $browser_status = true, $loader = false ) {

	if ( ! bb_onesignal_enabled_web_push() ) {
		return;
	}

    // phpcs:ignore
	$cookie_player_id = ( empty( $player_id ) && isset( $_COOKIE ) && ! empty( $_COOKIE['bbpro-player-id'] ) ? $_COOKIE['bbpro-player-id'] : '' );
	if ( ! empty( $cookie_player_id ) ) {
		$player_id = $cookie_player_id;
	}

	if ( empty( $browser ) ) {
		return;
	}

	$browser_name             = sanitize_title( $browser );
	$disabled_class           = ( empty( $player_id ) ? 'bb-disabled-browser-box' : '' );
	$enable_instruction_steps = array(
		sprintf(
			/* translators: Image src. */
			__( 'Choose <img src="%s"> in your browser\'s address bar', 'buddyboss-pro' ),
			bb_onesignal_integration_url( '/assets/img/icons/fontawesome/solid/lock.svg' )
		),
		sprintf(
			/* translators: Image src. */
			__( 'Enable <img src="%s"> Notifications', 'buddyboss-pro' ),
			bb_onesignal_integration_url( '/assets/img/icons/fontawesome/solid/bell.svg' )
		),
		__( 'Refresh this page', 'buddyboss-pro' ),
	);

	$disable_instruction_steps = array(
		sprintf(
			/* translators: Image src. */
			__( 'Choose <img src="%s"> in your browser\'s address bar', 'buddyboss-pro' ),
			bb_onesignal_integration_url( '/assets/img/icons/fontawesome/solid/lock.svg' )
		),
		sprintf(
			/* translators: Image src. */
			__( 'Disable <img src="%s"> Notifications', 'buddyboss-pro' ),
			bb_onesignal_integration_url( '/assets/img/icons/fontawesome/solid/bell.svg' )
		),
		__( 'Refresh this page', 'buddyboss-pro' ),
	);

	if ( 'Opera' === $browser ) {
		$browser_text = esc_html__( 'Enable Opera Notifications', 'buddyboss-pro' );
	} elseif ( 'Chrome' === $browser ) {
		$browser_text = esc_html__( 'Enable Chrome Notifications', 'buddyboss-pro' );
	} elseif ( 'Safari' === $browser ) {
		$browser_text             = esc_html__( 'Enable Safari Notifications', 'buddyboss-pro' );
		$enable_instruction_steps = array(
			__( 'Open the Safari settings', 'buddyboss-pro' ),
			__( 'In the Websites tab, open the Notifications section', 'buddyboss-pro' ),
			__( 'Change the permission for this site to Allow', 'buddyboss-pro' ),
			__( 'Close the settings', 'buddyboss-pro' ),
			__( 'Refresh this page', 'buddyboss-pro' ),
		);

		$disable_instruction_steps = array(
			__( 'Open the Safari settings', 'buddyboss-pro' ),
			__( 'In the Websites tab, open the Notifications section', 'buddyboss-pro' ),
			__( 'Change the permission for this site to Deny', 'buddyboss-pro' ),
			__( 'Close the settings', 'buddyboss-pro' ),
			__( 'Refresh this page', 'buddyboss-pro' ),
		);
	} elseif ( 'Firefox' === $browser ) {
		$browser_text             = esc_html__( 'Enable Firefox Notifications', 'buddyboss-pro' );
		$enable_instruction_steps = array(
			__( 'Open the Firefox settings', 'buddyboss-pro' ),
			__( 'In the Privacy & Security tab, open the Notifications settings (found under Permissions)', 'buddyboss-pro' ),
			__( 'Change the Status for this site to Allow', 'buddyboss-pro' ),
			__( 'Click Save Changes and close the settings', 'buddyboss-pro' ),
			__( 'Refresh this page', 'buddyboss-pro' ),
		);

		$disable_instruction_steps = array(
			__( 'Open the Firefox settings', 'buddyboss-pro' ),
			__( 'In the Privacy & Security tab, open the Notifications settings (found under Permissions)', 'buddyboss-pro' ),
			__( 'Change the Status for this site to Block', 'buddyboss-pro' ),
			__( 'Click Save Changes and close the settings', 'buddyboss-pro' ),
			__( 'Refresh this page', 'buddyboss-pro' ),
		);
	} elseif ( 'Edge' === $browser ) {
		$browser_text = esc_html__( 'Enable Edge Notifications', 'buddyboss-pro' );
	} elseif ( 'IE' === $browser ) {
		$browser_text = esc_html__( 'Enable IE Notifications', 'buddyboss-pro' );
	} else {
		$browser_text = esc_html__( 'Enable Browser Notifications', 'buddyboss-pro' );
	}

	ob_start();
	?>
	<div class="onesignal-user-preference <?php echo esc_attr( $disabled_class ); ?>">
		<div class="web-notification-toggle">
			<div class="web-notification-icon-text">
				<div class="web-browser-text">
					<div class="web-notification-icon <?php echo esc_attr( $browser_name ); ?>"></div>
					<div class="web-notification-text">
						<?php echo esc_html( $browser_text ); ?>
					</div>
				</div>
				<p><?php esc_html_e( 'Receive web notifications through this browser, even when you\'re not on this site.', 'buddyboss-pro' ); ?></p>
			</div>
			<div class="web-notification-field">
				<?php if ( true === $loader ) { ?>
					<i class="bb-icons bb-icon-spinner animate-spin"></i>
				<?php } else { ?>
					<label class="switch" for="<?php echo esc_attr( $browser_name ); ?>">
						<input type="checkbox" class="notification-toggle current-browser" id="<?php echo esc_attr( $browser_name ); ?>" name="web-notification-toggle" value="<?php echo esc_attr( $browser_name ); ?>" <?php checked( $browser_status ); ?>/>
						<div class="slider round"></div>
					</label>
				<?php } ?>
			</div>
		</div>
	</div>

	<div id="permission-helper-modal" class="bb-onesignal-popup notification-popup mfp-hide">
		<div class="bb-onesignal-popup-header">
			<?php esc_html_e( 'Enable notifications', 'buddyboss-pro' ); ?>
		</div>
		<p><?php esc_html_e( 'To enable notifications, please allow notifications for this site in your browser.', 'buddyboss-pro' ); ?></p>
		<div class="bb-onesignal-popup-content">
			<?php
			if ( ! empty( $enable_instruction_steps ) ) {
				?>
				<ul>
					<?php
					foreach ( $enable_instruction_steps as $instruction_step ) {
						?>
						<li><?php echo wp_kses_post( $instruction_step ); ?></li>
						<?php
					}
					?>
				</ul>
				<?php
			}
			?>
		</div>
	</div>

	<div id="permission-helper-modal-close" class="bb-onesignal-popup-close notification-popup-close mfp-hide">
		<div class="bb-onesignal-popup-header">
			<?php esc_html_e( 'Disable notifications', 'buddyboss-pro' ); ?>
		</div>
		<p><?php esc_html_e( 'To disable notifications, please block notifications for this site in your browser.', 'buddyboss-pro' ); ?></p>
		<div class="bb-onesignal-popup-content">
			<?php
			if ( ! empty( $disable_instruction_steps ) ) {
				?>
				<ul>
					<?php
					foreach ( $disable_instruction_steps as $instruction_step ) {
						?>
						<li><?php echo wp_kses_post( $instruction_step ); ?></li>
						<?php
					}
					?>
				</ul>
				<?php
			}
			?>
		</div>
	</div>
	<?php
	return ob_get_clean();

}

/**
 * AJAX callback to updated device information.
 *
 * @since 2.0.3
 *
 * @return void
 */
function bb_pro_onesignal_update_device_info() {
	if ( ! bp_is_post_request() ) {
		return;
	}

	$user_id         = filter_input( INPUT_POST, 'user_id', FILTER_SANITIZE_NUMBER_INT );
	$player_id       = bb_pro_filter_input_string( INPUT_POST, 'player_id' );
	$active          = filter_input( INPUT_POST, 'active', FILTER_VALIDATE_BOOLEAN );
	$update_via_curl = filter_input( INPUT_POST, 'update_via_curl', FILTER_VALIDATE_BOOLEAN );
	$app_id          = bb_onesignal_app_id();
	$rest_api        = bb_onesignal_rest_api_key();

	if ( $player_id && $active && $update_via_curl && ! empty( $app_id ) && ! empty( $rest_api ) ) {
		$args_remote = array(
			'sslverify' => false,
			'method'    => 'PUT',
		);

		$fields = array(
			'app_id'             => $app_id,
			'notification_types' => 1,
		);

		$args_remote['body'] = wp_json_encode( $fields );
		bbpro_remote_post( bb_onesignal_api_endpoint() . 'players/' . $player_id, $args_remote );
	}

	wp_cache_delete( 'bb_pro_browser_' . $user_id . '_' . $player_id, 'bb_onesignal_get_browser_for_user' );

	if ( $player_id ) {
        // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
		@setcookie( 'bbpro-player-id', $player_id, time() + 60 * 60 * 24 * 365, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );
	} elseif ( isset( $_COOKIE['bbpro-player-id'] ) ) {
		unset( $_COOKIE['bbpro-player-id'] );
	}

	wp_send_json_success(
		array(
			'player_id'   => $player_id,
			'browser_box' => bb_onesignal_add_web_notification_toggle( $player_id, bb_get_browser_name( $_SERVER['HTTP_USER_AGENT'] ), $active ),
		)
	);

}

/**
 * Clear the player cookie.
 *
 * @since 2.0.3
 */
function bb_logout_clear_player_cookie() {

	if ( isset( $_COOKIE['bbpro-player-id'] ) ) {
		$player_id = $_COOKIE['bbpro-player-id'];
		unset( $_COOKIE['bbpro-player-id'] );
		@setcookie( 'bbpro-player-id', null, time() - 1000, COOKIEPATH, COOKIE_DOMAIN, is_ssl() );

		$app_id   = bb_onesignal_app_id();
		$rest_api = bb_onesignal_rest_api_key();

		if ( $player_id && ! empty( $app_id ) && ! empty( $rest_api ) ) {

			$args_remote = array(
				'sslverify' => false,
				'headers'   => array( 'Authorization' => 'Basic ' . $rest_api ),
				'method'    => 'PUT',
			);

			$fields = array(
				'app_id'           => $app_id,
				'external_user_id' => '',
			);

			$args_remote['body'] = $fields;
			bbpro_remote_post( bb_onesignal_api_endpoint() . 'players/' . $player_id, $args_remote );
		}
	}

}

/**
 * Clear session storage on login page.
 *
 * @since 2.0.3
 *
 * @return void
 */
function bb_clear_display_prompt_on_login_screen() {
	ob_start();
	?>
	<script type="text/javascript">sessionStorage.removeItem( 'ONESIGNAL_HTTP_PROMPT_SHOWN' );</script>
	<?php
	echo ob_get_clean(); // phpcs:ignore
}

/**
 * Fires after the current notification item gets saved.
 *
 * @since 2.0.3
 *
 * @param BP_Notifications_Notification $notification Notification object.
 *
 * @return void
 */
function bb_pro_onesignal_notification_after_save( $notification ) {
	if (
		! bbp_pro_is_license_valid() ||
		! function_exists( 'bb_enabled_legacy_email_preference' ) ||
		( function_exists( 'bb_enabled_legacy_email_preference' ) && bb_enabled_legacy_email_preference() ) ||
		empty( bb_onesignal_app_id() ) ||
		empty( bb_onesignal_rest_api_key() ) ||
		empty( bb_onesignal_app_is_connected() ) ||
		empty( bb_onesignal_connected_app_name() )
	) {
		return;
	}

	if (
		isset( $notification->inserted ) &&
		true === $notification->inserted &&
		bp_can_send_notification( $notification->user_id, $notification->component_name, $notification->component_action, 'web' )
	) {

		if ( true !== (bool) apply_filters( 'bb_pro_onesignal_notification_fire', bb_pro_onesignal_user_presence_check( true, $notification ), $notification ) ) {
			return;
		}

		if ( function_exists( 'bb_notification_get_renderable_notifications' ) ) {
			$content = array(
				'title'   => '',
				'content' => '',
				'href'    => '',
				'image'   => bb_onesignal_default_notification_icon(),
			);

			$notification_content = bb_notification_get_renderable_notifications( $notification, 'object', 'web_push' );
		} else {
			$content = array(
				'title'       => '',
				'description' => bp_get_the_notification_description( $notification ),
				'link'        => '',
				'image'       => bb_onesignal_default_notification_icon(),
			);

			// Do not use we will remove after some time.
			$notification_content = apply_filters_ref_array(
				'bb_notifications_get_push_notifications_content',
				array(
					$content,
					$notification,
				)
			);
		}

		$notification_content = bp_parse_args(
			$notification_content,
			$content
		);

		bb_onesingnal_send_notification(
			array(
				'user_id' => $notification->user_id,
				'title'   => $notification_content['title'],
				'content' => ( isset( $notification_content['content'] ) ? $notification_content['content'] : $notification_content['description'] ),
				'link'    => ( isset( $notification_content['href'] ) ? $notification_content['href'] : $notification_content['link'] ),
				'image'   => $notification_content['image'],
			)
		);

	}
}

/**
 * Hide site-wise notice.
 *
 * @since 2.3.41
 *
 * @return void
 */
function bb_pro_onesignal_dismiss_sitewide_notice() {
	$wp_nonce = bb_pro_filter_input_string( INPUT_POST, 'nonce' );

	// Nonce check!
	if ( empty( $wp_nonce ) || ! wp_verify_nonce( $wp_nonce, 'bb-pro-onesignal-dismiss-notice' ) ) {
		wp_send_json_error( array( 'error' => __( 'Sorry, something goes wrong please try again.', 'buddyboss-pro' ) ) );
	}

	$settings                         = array();
	$settings['hide_sidewide_errors'] = true;

	$old_settings = bb_onesignal_get_settings();
	if ( ! empty( $old_settings ) ) {
		$settings = array_merge( $old_settings, $settings );
	}

	bb_onesignal_update_settings( $settings );

	wp_send_json_success(
		array(
			'success' => true,
		)
	);
}
