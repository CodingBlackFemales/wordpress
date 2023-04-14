<?php
/**
 * OneSignal integration filters
 *
 * @package BuddyBoss\OneSignal
 * @since   2.0.3
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_filter( 'bb_notification_web_push_notification_settings', 'bb_onesignal_admin_settings_web_push', 20, 1 );
add_filter( 'bp_attachment_avatar_script_data', 'bb_onesignal_attachment_notification_script_data', 10, 2 );
add_filter( 'bp_core_avatar_ajax_upload_params', 'bb_onesignal_avatar_ajax_upload_params', 20, 1 );
add_filter( 'bp_attachments_current_user_can', 'bb_onesignal_attachments_current_user_can', 20, 3 );
add_filter( 'bp_core_avatar_dir', 'bb_onesignal_bp_core_avatar_dir', 20, 2 );
add_filter( 'bp_attachments_get_plupload_l10n', 'bb_onesignal_attachments_get_plupload_l10n', 20, 1 );
add_filter( 'bb_avatar_ajax_set_avatar_dir', 'bb_onesignal_notification_set_avatar_dir', 20, 2 );
add_filter( 'bb_avatar_crop_set_avatar_dir', 'bb_onesignal_notification_set_avatar_dir', 20, 2 );

// Save settings.
add_action( 'bp_admin_tab_setting_save', 'bb_onesignal_web_push_setting_fields_save', 99, 1 );

add_filter( 'bb_web_notification_enabled', 'bb_onesignal_web_push_enabled', 10, 1 );

add_filter( 'bp_core_do_avatar_handle_crop', 'bb_onesignal_custom_image_handle_crop_remove', 10, 2 );

add_filter( 'bb_pro_onesignal_notification_fire', 'bb_onesignal_manage_web_push_notification', 99, 2 );

/**
 * Added Web Push notification settings.
 *
 * @since 2.0.3
 *
 * @param array $fields Array of fieldsets.
 *
 * @return mixed
 */
function bb_onesignal_admin_settings_web_push( $fields ) {

	if (
		! function_exists( 'bb_enabled_legacy_email_preference' ) ||
		( function_exists( 'bb_enabled_legacy_email_preference' ) && bb_enabled_legacy_email_preference() )
	) {
		return $fields;
	}

	$fields['bb-onesignal-enabled-web-push'] = array(
		'title'             => esc_html__( 'Enable Web Push Notifications', 'buddyboss-pro' ),
		'callback'          => 'bb_onesignal_admin_setting_callback_push_notification_fields',
		'sanitize_callback' => 'intval',
		'args'              => array(),
	);

	$fields['bb-onesignal-default-notification-icon'] = array(
		'title'    => esc_html__( 'Default Notification Icon', 'buddyboss-pro' ),
		'callback' => 'bb_onesignal_admin_setting_callback_default_notification_icon_fields',
		'args'     => array( 'class' => 'bb-onesignal-default-notification-icon bp-hide' ),
	);

	$fields['bb_web_push_skip_active_members'] = array(
		'title'    => esc_html__( 'Skip Active Members', 'buddyboss-pro' ),
		'callback' => 'bb_onesignal_admin_setting_callback_web_push_skip_active_members',
		'args'     => array( 'class' => 'bb-onesignal-web-push-skip-active-members bp-hide' ),
	);

	$fields['bb-onesignal-request-permission'] = array(
		'title'    => esc_html__( 'Automatically Request Permission', 'buddyboss-pro' ),
		'callback' => 'bb_onesignal_admin_setting_callback_request_permission_fields',
		'args'     => array( 'class' => 'bb-onesignal-request-permission bp-hide' ),
	);

	$fields['bb-onesignal-enable-soft-prompt'] = array(
		'title'    => esc_html__( 'Enable Soft Prompt', 'buddyboss-pro' ),
		'callback' => 'bb_onesignal_admin_setting_callback_enable_soft_prompt_fields',
		'args'     => array( 'class' => 'bb-onesignal-enable-soft-prompt bp-hide' ),
	);

	$fields['bb-onesignal-enable-soft-prompt-message'] = array(
		'title'    => '',
		'callback' => 'bb_onesignal_admin_setting_callback_enable_soft_prompt_fields_message',
		'args'     => array( 'class' => 'bb-onesignal-enable-soft-prompt-extra-fields bp-hide' ),
	);

	$fields['bb-onesignal-enable-soft-prompt-image'] = array(
		'title'    => '',
		'callback' => 'bb_onesignal_admin_setting_callback_enable_soft_prompt_fields_image',
		'args'     => array( 'class' => 'bb-onesignal-enable-soft-prompt-extra-fields bp-hide' ),
	);

	$fields['bb-onesignal-enable-soft-prompt-buttons'] = array(
		'title'    => '',
		'callback' => 'bb_onesignal_admin_setting_callback_enable_soft_prompt_fields_buttons',
		'args'     => array( 'class' => 'bb-onesignal-enable-soft-prompt-extra-fields group-field bp-hide' ),
	);

	$fields['bb-onesignal-enable-soft-prompt-preview-box'] = array(
		'title'    => '',
		'callback' => 'bb_onesignal_admin_setting_callback_enable_soft_prompt_fields_preview_box',
		'args'     => array( 'class' => 'bb-onesignal-enable-soft-prompt-extra-fields bp-hide' ),
	);

	return $fields;
}

/**
 * The default notification attachment script data.
 *
 * @since 2.0.3
 *
 * @param array  $script_data The avatar script data.
 * @param string $object whole object.
 *
 * @return mixed
 */
function bb_onesignal_attachment_notification_script_data( $script_data, $object = '' ) {

	if ( function_exists( 'bp_core_get_admin_active_tab' ) && 'bp-notifications' === bp_core_get_admin_active_tab() ) {
		$script_data['bp_params'] = array(
			'object'     => 'notification',
			'item_id'    => 0,
			'item_type'  => 'default',
			'has_avatar' => bb_has_default_custom_upload_profile_avatar(),
			'nonces'     => array(
				'set'    => wp_create_nonce( 'bp_avatar_cropstore' ),
				'remove' => wp_create_nonce( 'bp_delete_avatar_link' ),
			),
		);

		// Set feedback messages.
		$script_data['feedback_messages'] = array(
			1 => esc_html__( 'There was a problem cropping custom profile avatar.', 'buddyboss-pro' ),
			2 => esc_html__( 'The custom profile avatar was uploaded successfully.', 'buddyboss-pro' ),
			3 => esc_html__( 'There was a problem deleting custom profile avatar. Please try again.', 'buddyboss-pro' ),
			4 => esc_html__( 'The custom profile avatar was deleted successfully!', 'buddyboss-pro' ),
		);
	}

	return $script_data;
}

/**
 * Save registered settings to DB.
 *
 * @since 2.0.3
 *
 * @param string $current_tab Current setting tab.
 */
function bb_onesignal_web_push_setting_fields_save( $current_tab ) {
	if ( 'bp-notifications' !== $current_tab ) {
		return;
	}

	$bb_onesignal_permission_validate = bb_pro_filter_input_string( INPUT_POST, 'bb-onesignal-permission-validate' );
	$bb_onesignal_request_permission  = filter_input( INPUT_POST, 'bb-onesignal-request-permission', FILTER_VALIDATE_BOOLEAN );
	if ( $bb_onesignal_request_permission ) {
		bp_update_option( 'bb-onesignal-permission-validate', $bb_onesignal_permission_validate );
	}

	$bb_onesignal_allow_button  = bb_pro_filter_input_string( INPUT_POST, 'bb-onesignal-enable-soft-prompt-allow-button' );
	$bb_onesignal_cancel_button = bb_pro_filter_input_string( INPUT_POST, 'bb-onesignal-enable-soft-prompt-cancel-button' );

	bp_update_option( 'bb-onesignal-enable-soft-prompt-allow-button', $bb_onesignal_allow_button );
	bp_update_option( 'bb-onesignal-enable-soft-prompt-cancel-button', $bb_onesignal_cancel_button );
}

/**
 * Ajax notification attachment upload dir.
 *
 * @since 2.0.3
 *
 * @param array $bp_params Array of upload params.
 *
 * @return array
 */
function bb_onesignal_avatar_ajax_upload_params( $bp_params ) {
	if ( empty( $bp_params['object'] ) || ( 'notification' !== $bp_params['object'] && 'prompt' !== $bp_params['object'] ) ) {
		return $bp_params;
	}

	if ( 'notification' === $bp_params['object'] ) {
		$bp_params['upload_dir_filter'] = 'bb_onesignal_notification_attachment_upload_dir';
	} elseif ( 'prompt' === $bp_params['object'] ) {
		$bp_params['upload_dir_filter'] = 'bb_onesignal_prompt_attachment_upload_dir';
	}

	return $bp_params;
}

/**
 * Setup the notification upload directory for a user.
 *
 * @since 2.0.3
 *
 * @param string $directory The root directory name. Optional.
 * @param int    $user_id   The user ID. Optional.
 *
 * @return array Array containing the path, URL, and other helpful settings.
 */
function bb_onesignal_notification_attachment_upload_dir( $directory = 'notification/icon', $user_id = 0 ) {

	// Use displayed user if no user ID was passed.
	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id();
	}

	// Failsafe against accidentally nooped $directory parameter.
	if ( empty( $directory ) ) {
		$directory = 'notification/icon';
	}

	$path      = bp_core_avatar_upload_path() . '/' . $directory . '/' . $user_id;
	$newbdir   = $path;
	$newurl    = bp_core_avatar_url() . '/' . $directory . '/' . $user_id;
	$newburl   = $newurl;
	$newsubdir = '/' . $directory . '/' . $user_id;

	/**
	 * Filters the avatar upload directory for a user.
	 *
	 * @since 2.0.3
	 *
	 * @param array $value Array containing the path, URL, and other helpful settings.
	 */
	return apply_filters(
		'bb_onesignal_notification_attachment_upload_dir',
		array(
			'path'    => $path,
			'url'     => $newurl,
			'subdir'  => $newsubdir,
			'basedir' => $newbdir,
			'baseurl' => $newburl,
			'error'   => false,
		)
	);
}

/**
 * Setup the soft prompt image upload directory for a user.
 *
 * @since 2.0.3
 *
 * @param string $directory The root directory name. Optional.
 * @param int    $user_id   The user ID. Optional.
 *
 * @return array Array containing the path, URL, and other helpful settings.
 */
function bb_onesignal_prompt_attachment_upload_dir( $directory = 'notification/prompt', $user_id = 0 ) {

	// Use displayed user if no user ID was passed.
	if ( empty( $user_id ) ) {
		$user_id = bp_displayed_user_id();
	}

	// Failsafe against accidentally nooped $directory parameter.
	if ( empty( $directory ) ) {
		$directory = 'notification/prompt';
	}

	$path      = bp_core_avatar_upload_path() . '/' . $directory . '/' . $user_id;
	$newbdir   = $path;
	$newurl    = bp_core_avatar_url() . '/' . $directory . '/' . $user_id;
	$newburl   = $newurl;
	$newsubdir = '/' . $directory . '/' . $user_id;

	/**
	 * Filters the avatar upload directory for a user.
	 *
	 * @since 2.0.3
	 *
	 * @param array $value Array containing the path, URL, and other helpful settings.
	 */
	return apply_filters(
		'bb_onesignal_prompt_attachment_upload_dir',
		array(
			'path'    => $path,
			'url'     => $newurl,
			'subdir'  => $newsubdir,
			'basedir' => $newbdir,
			'baseurl' => $newburl,
			'error'   => false,
		)
	);
}

/**
 * Check the current user's capability to edit an avatar for a given object.
 *
 * @since 2.0.3
 *
 * @param bool   $can        Whether to check permission granted or not.
 * @param string $capability The capability to check.
 * @param array  $args       An array containing the item_id and the object to check.
 *
 * @return bool
 */
function bb_onesignal_attachments_current_user_can( $can, $capability, $args ) {
	if ( empty( $args['object'] ) || ( 'notification' !== $args['object'] && 'prompt' !== $args['object'] ) ) {
		return $can;
	}

	return bp_core_can_edit_settings();
}

/**
 * Notification icon/soft prompt image upload directory.
 *
 * @since 2.0.3
 *
 * @param string $avatar_dir Avatar directory.
 * @param array  $object     The object to check.
 *
 * @return mixed|string
 */
function bb_onesignal_bp_core_avatar_dir( $avatar_dir, $object ) {
	if ( empty( $object ) || ( 'notification' !== $object && 'prompt' !== $object ) ) {
		return $avatar_dir;
	}

	if ( 'notification' === $object ) {
		return 'notification/icon';
	} elseif ( 'prompt' === $object ) {
		return 'notification/prompt';
	}

	return $avatar_dir;
}

/**
 * Updated attachment arguments.
 *
 * @since 2.0.3
 *
 * @param array $strings Attachment data.
 *
 * @return mixed
 */
function bb_onesignal_attachments_get_plupload_l10n( $strings ) {
	if ( function_exists( 'bp_core_get_admin_active_tab' ) && 'bp-notifications' !== bp_core_get_admin_active_tab() ) {
		return $strings;
	}

	$strings['has_avatar_warning'] = '';

	return $strings;
}

/**
 * Notification icon/soft prompt image upload directory.
 *
 * @since 2.0.3
 *
 * @param string $avatar_dir  Avatar directory name.
 * @param array  $avatar_data Avatar data.
 *
 * @return mixed|string
 */
function bb_onesignal_notification_set_avatar_dir( $avatar_dir, $avatar_data ) {
	if ( empty( $avatar_data['object'] ) || ( 'notification' !== $avatar_data['object'] && 'prompt' !== $avatar_data['object'] ) ) {
		return $avatar_dir;
	}

	if ( 'notification' === $avatar_data['object'] ) {
		return 'notification/icon';
	} elseif ( 'prompt' === $avatar_data['object'] ) {
		return 'notification/prompt';
	}

	return $avatar_dir;
}

/**
 * Check push notification is enabled or not.
 *
 * @since 2.0.3
 *
 * @param bool $is_enabled Whether the push notification is enabled or not.
 *
 * @return bool|mixed
 */
function bb_onesignal_web_push_enabled( $is_enabled ) {

	if ( ! $is_enabled && bp_is_active( 'notifications' ) && bbp_pro_is_license_valid() && bb_onesignal_enabled_web_push() && (int) bb_onesignal_request_permission() ) {
		$is_enabled = true;
	}

	return $is_enabled;
}

/**
 * Prevent to crop the avatar image.
 *
 * @since 2.0.3
 *
 * @param bool   $value Whether to crop the avatar image or not.
 * @param object $r     The avatar image object.
 *
 * @return false|mixed
 */
function bb_onesignal_custom_image_handle_crop_remove( $value, $r ) {
	if ( ! empty( $r['object'] ) && ( 'notification' === $r['object'] || 'prompt' === $r['object'] ) && ! empty( $r['item_type'] ) && 'default' === $r['item_type'] ) {
		return false;
	}
	return $value;
}

/**
 * Needs to check the web push needs to fired or not.
 *
 * @since 2.2.3
 *
 * @param bool                          $retval       Return value.
 * @param BP_Notifications_Notification $notification Notification object.
 *
 * @return mixed
 */
function bb_onesignal_manage_web_push_notification( $retval, $notification ) {
	if (
		! empty( $notification->id ) &&
		! empty( $notification->component_action ) &&
		in_array(
			$notification->component_action,
			array(
				'bb_activity_following_post',
				'bb_groups_subscribed_activity',
				'bb_groups_subscribed_discussion',
				'bb_forums_subscribed_reply',
				'bb_forums_subscribed_discussion',
				'bbp_new_reply',
			),
			true
		) &&
		true === (bool) bp_notifications_get_meta( $notification->id, 'not_send_web', true )
	) {
		return false;
	}

	return $retval;
}
