<?php
/**
 * Zoom integration filters
 *
 * @package BuddyBoss\Zoom
 * @since   1.0.4
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_filter( 'bbp_pro_core_install', 'bp_zoom_pro_core_install_zoom_integration' );
add_filter( 'bp_email_set_tokens', 'bp_zoom_set_email_tokens', 99, 3 );

add_filter( 'bp_rest_account_settings_notifications_groups', 'bb_zoom_rest_account_settings_notifications', 99 );

add_filter( 'bp_zoom_meeting_timezone_before_save', 'bb_zoom_get_server_allowed_timezone', 99, 1 );
add_filter( 'bp_zoom_webinar_timezone_before_save', 'bb_zoom_get_server_allowed_timezone', 99, 1 );
add_filter( 'bp_get_zoom_meeting_timezone', 'bb_zoom_get_server_allowed_timezone', 99, 1 );
add_filter( 'bp_get_zoom_webinar_timezone', 'bb_zoom_get_server_allowed_timezone', 99, 1 );
add_filter( 'bb_notification_is_read_only', 'bb_zoom_group_notification_linkable', 99, 2 );
add_filter( 'bp_activity_get_where_conditions', 'bp_zoom_exclude_activities_when_locked', 10, 5 );

/**
 * Install or upgrade zoom integration.
 *
 * @since 1.0.4
 */
function bp_zoom_pro_core_install_zoom_integration() {
	require_once ABSPATH . 'wp-admin/includes/upgrade.php';
	$switched_to_root_blog = false;

	// Make sure the current blog is set to the root blog.
	if ( ! bp_is_root_blog() ) {
		switch_to_blog( bp_get_root_blog_id() );
		$switched_to_root_blog = true;
	}

	$sql             = array();
	$charset_collate = $GLOBALS['wpdb']->get_charset_collate();
	$bp_prefix       = bp_core_get_table_prefix();

	$sql[] = "CREATE TABLE {$bp_prefix}bp_zoom_meetings (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				group_id bigint(20) NOT NULL,
				activity_id bigint(20) NOT NULL,
				user_id bigint(20) NOT NULL,
				host_id varchar(150) NOT NULL,
				type int(10) NOT NULL DEFAULT 2,
				title varchar(300) NOT NULL,
				description varchar(800) NULL,
				start_date_utc datetime NOT NULL,
				timezone varchar(150) NOT NULL,
				password varchar(150) NOT NULL,
				duration int(11) NOT NULL,
				join_before_host bool DEFAULT 0,
				host_video bool DEFAULT 0,
				participants_video bool DEFAULT 0,
				mute_participants bool DEFAULT 0,
				waiting_room bool DEFAULT 0,
				meeting_authentication bool DEFAULT 0,
				recurring bool DEFAULT 0,
				auto_recording varchar(75) DEFAULT 'none',
				alternative_host_ids text NULL,
				meeting_id varchar(150) NOT NULL,
				hide_sitewide bool DEFAULT 0,
				parent varchar(150) DEFAULT 0,
				zoom_type varchar(150) DEFAULT 'meeting',
				alert int(11) DEFAULT 0,
				KEY group_id (group_id),
				KEY activity_id (activity_id),
				KEY meeting_id (meeting_id)
			) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_zoom_meeting_meta (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				meeting_id bigint(20) NOT NULL,
				meta_key varchar(255) DEFAULT NULL,
				meta_value longtext DEFAULT NULL,
				KEY meeting_id (meeting_id),
				KEY meta_key (meta_key(191))
			) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_zoom_recordings (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				recording_id varchar(255) NOT NULL,
				meeting_id bigint(20) NOT NULL,
				uuid varchar(255) NOT NULL,
				details varchar(800) NULL,
				file_type varchar(800) NULL,
				password varchar(150) NOT NULL,
				start_time datetime NOT NULL,
				KEY recording_id (recording_id),
				KEY meeting_id (meeting_id)
			) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_zoom_webinars (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				group_id bigint(20) NOT NULL,
				activity_id bigint(20) NOT NULL,
				user_id bigint(20) NOT NULL,
				host_id varchar(150) NOT NULL,
				type int(10) NOT NULL DEFAULT 2,
				title varchar(300) NOT NULL,
				description varchar(800) NULL,
				start_date_utc datetime NOT NULL,
				timezone varchar(150) NOT NULL,
				password varchar(150) NOT NULL,
				duration int(11) NOT NULL,
				host_video bool DEFAULT 0,
				panelists_video bool DEFAULT 0,
				meeting_authentication bool DEFAULT 0,
				practice_session bool DEFAULT 0,
				on_demand bool DEFAULT 0,
				recurring bool DEFAULT 0,
				auto_recording varchar(75) DEFAULT 'none',
				alternative_host_ids text NULL,
				webinar_id varchar(150) NOT NULL,
				hide_sitewide bool DEFAULT 0,
				parent varchar(150) DEFAULT 0,
				zoom_type varchar(150) DEFAULT 'webinar',
				alert int(11) DEFAULT 0,
				KEY group_id (group_id),
				KEY activity_id (activity_id),
				KEY webinar_id (webinar_id)
			) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_zoom_webinar_meta (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				webinar_id bigint(20) NOT NULL,
				meta_key varchar(255) DEFAULT NULL,
				meta_value longtext DEFAULT NULL,
				KEY webinar_id (webinar_id),
				KEY meta_key (meta_key(191))
			) {$charset_collate};";

	$sql[] = "CREATE TABLE {$bp_prefix}bp_zoom_webinar_recordings (
				id bigint(20) NOT NULL AUTO_INCREMENT PRIMARY KEY,
				recording_id varchar(255) NOT NULL,
				webinar_id bigint(20) NOT NULL,
				uuid varchar(255) NOT NULL,
				details varchar(800) NULL,
				file_type varchar(800) NULL,
				password varchar(150) NOT NULL,
				start_time datetime NOT NULL,
				KEY recording_id (recording_id),
				KEY webinar_id (webinar_id)
			) {$charset_collate};";

	dbDelta( $sql );

	if ( $switched_to_root_blog ) {
		restore_current_blog();
	}
}

/**
 * Zoom set email tokens
 *
 * @param array     $formatted_tokens Formatted tokens.
 * @param array     $tokens           Tokens.
 * @param \BP_Email $bp_email         Email class.
 *
 * @return array
 * @since 1.0.9
 */
function bp_zoom_set_email_tokens( $formatted_tokens, $tokens, $bp_email ) {
	if ( isset( $tokens['zoom_meeting'] ) ) {
		$email_content_html      = $bp_email->get_content_html();
		$email_content_plaintext = $bp_email->get_content_plaintext();

		if ( false !== strpos( $email_content_html, 'zoom_meeting' ) || false !== strpos( $email_content_plaintext, 'zoom_meeting' ) ) {
			$token_output = call_user_func( 'bp_zoom_meeting_email_token_zoom_meeting', $bp_email, $formatted_tokens, $tokens );
			$formatted_tokens[ sanitize_text_field( 'zoom_meeting' ) ] = $token_output;
		}
	}

	if ( isset( $tokens['zoom_webinar'] ) ) {
		$email_content_html      = $bp_email->get_content_html();
		$email_content_plaintext = $bp_email->get_content_plaintext();

		if ( false !== strpos( $email_content_html, 'zoom_webinar' ) || false !== strpos( $email_content_plaintext, 'zoom_webinar' ) ) {
			$token_output = call_user_func( 'bp_zoom_webinar_email_token_zoom_webinar', $bp_email, $formatted_tokens, $tokens );
			$formatted_tokens[ sanitize_text_field( 'zoom_webinar' ) ] = $token_output;
		}
	}

	return $formatted_tokens;
}

/**
 * Zoom notifications into the api.
 *
 * @since 1.2.1
 *
 * @param array $fields Array of fields sets.
 *
 * @return array
 */
function bb_zoom_rest_account_settings_notifications( $fields ) {
	$fields[] = array(
		'name'        => 'notification_zoom_meeting_scheduled',
		'label'       => esc_html__( 'A Zoom meeting is scheduled in a group', 'buddyboss-pro' ),
		'field'       => 'radio',
		'value'       => ( ! empty( bp_get_user_meta( bp_loggedin_user_id(), 'notification_zoom_meeting_scheduled', true ) ) ? bp_get_user_meta( bp_loggedin_user_id(), 'notification_group_messages_new_message', true ) : 'yes' ),
		'options'     => array(
			'yes' => esc_html__( 'Yes', 'buddyboss-pro' ),
			'no'  => esc_html__( 'No', 'buddyboss-pro' ),
		),
		'group_label' => '',
	);

	$fields[] = array(
		'name'        => 'notification_zoom_webinar_scheduled',
		'label'       => esc_html__( 'A Zoom webinar is scheduled in a group', 'buddyboss-pro' ),
		'field'       => 'radio',
		'value'       => ( ! empty( bp_get_user_meta( bp_loggedin_user_id(), 'notification_zoom_webinar_scheduled', true ) ) ? bp_get_user_meta( bp_loggedin_user_id(), 'notification_group_messages_new_message', true ) : 'yes' ),
		'options'     => array(
			'yes' => esc_html__( 'Yes', 'buddyboss-pro' ),
			'no'  => esc_html__( 'No', 'buddyboss-pro' ),
		),
		'group_label' => '',
	);

	return $fields;
}

/**
 * Determines if a Zoom group notification is linkable based on user and moderation status.
 *
 * @since 2.5.50
 *
 * @param bool   $retval       The current return value indicating if the notification is linkable.
 * @param object $notification The notification object being evaluated.
 *
 * @return bool Whether the notification is linkable based on user and moderation status.
 */
function bb_zoom_group_notification_linkable( $retval, $notification ) {

	if (
		'groups' !== $notification->component_name ||
		! in_array(
			$notification->component_action,
			array(
				'bb_groups_new_zoom',
			),
			true
		)
	) {
		return $retval;
	}

	$meeting = new BP_Zoom_Meeting( $notification->secondary_item_id );
	$user_id = $meeting->user_id ?? 0;

	return ! empty( $user_id ) &&
		(
			bp_is_user_inactive( $user_id ) ||
			(
				bp_is_active( 'moderation' ) &&
				bb_moderation_moderated_user_ids( $user_id )
			)
		);
}

/**
 * Exclude zoom meeting and webinar activities when DRM features are locked.
 *
 * @since 2.11.0
 *
 * @param array  $where_conditions Current WHERE conditions.
 * @param array  $r                Query arguments.
 * @param string $select_sql       SELECT clause.
 * @param string $from_sql         FROM clause.
 * @param string $join_sql         JOIN clause.
 *
 * @return array Modified WHERE conditions.
 */
function bp_zoom_exclude_activities_when_locked( $where_conditions, $r, $select_sql, $from_sql, $join_sql ) {
	if ( function_exists( 'bb_pro_should_lock_features' ) && bb_pro_should_lock_features() ) {
		$excluded_zoom_types = array(
			'zoom_meeting_create',
			'zoom_meeting_notify',
			'zoom_webinar_create',
			'zoom_webinar_notify',
		);

		$not_in                                  = "'" . implode( "', '", esc_sql( $excluded_zoom_types ) ) . "'";
		$where_conditions['excluded_zoom_types'] = "a.type NOT IN ({$not_in})";
	}

	return $where_conditions;
}
