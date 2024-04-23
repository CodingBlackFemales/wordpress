<?php
/**
 * Zoom Group helpers
 *
 * @package BuddyBoss\Zoom
 * @since 1.0.7
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Send meeting notifications for group.
 *
 * @param object|int $meeting      BP_Zoom_Meeting Object or Meeting ID.
 * @param bool       $notification Whether notification or not.
 *
 * @since 1.0.7
 */
function bp_zoom_groups_send_meeting_notifications( $meeting, $notification = false ) {

	// Check notification component active or not.
	if ( ! bp_is_active( 'notifications' ) ) {
		return;
	}

	// Check meeting object or id.
	if ( ! $meeting instanceof BP_Zoom_Meeting ) {
		$meeting = new BP_Zoom_Meeting( $meeting );
	}

	// Check meeting.
	if ( empty( $meeting ) ) {
		return;
	}

	// Get meeting group.
	$group = groups_get_group( $meeting->group_id );

	// Check group exists.
	if ( empty( $group->id ) ) {
		return;
	}

	// Get members ids.
	$user_ids = BP_Groups_Member::get_group_member_ids( $group->id );

	if (
		function_exists( 'bb_notifications_background_enabled' ) &&
		true === bb_notifications_background_enabled() &&
		count( $user_ids ) > 20
	) {
		global $bb_background_updater, $bb_notifications_background_updater;
		$chunk_user_ids = array_chunk( $user_ids, 20 );
		if ( ! empty( $chunk_user_ids ) ) {
			foreach ( $chunk_user_ids as $key => $group_member_ids ) {
				$args_data = array(
					'callback' => 'bb_zoom_groups_meeting_notifications_details',
					'args'     => array(
						$group_member_ids,
						$meeting,
						$notification,
						$group,
					),
				);

				if ( ! empty( $bb_background_updater ) && $bb_background_updater instanceof BB_Background_Updater ) {

					$args_data['type']     = 'email';
					$args_data['group']    = 'zoom_groups_meeting_details';
					$args_data['data_id']  = $group->id;
					$args_data['priority'] = 5;

					$bb_background_updater->data( $args_data );
					$bb_background_updater->save();
				} else {
					$bb_notifications_background_updater->data( array( $args_data ) );
					$bb_notifications_background_updater->save();
				}
			}

			if ( ! empty( $bb_background_updater ) && $bb_background_updater instanceof BB_Background_Updater ) {
				$bb_background_updater->dispatch();
			} else {
				$bb_notifications_background_updater->dispatch();
			}
		}
	} else {
		bb_zoom_groups_meeting_notifications_details( $user_ids, $meeting, $notification, $group );
	}

}

/**
 * Send webinar notifications for group.
 *
 * @param object|int $webinar      BP_Zoom_Webinar Object or Webinar ID.
 * @param bool       $notification Whether notification or not.
 *
 * @since 1.0.9
 */
function bp_zoom_groups_send_webinar_notifications( $webinar, $notification = false ) {

	// Check notification component active or not.
	if ( ! bp_is_active( 'notifications' ) ) {
		return;
	}

	// Check webinar object or id.
	if ( ! $webinar instanceof BP_Zoom_Webinar ) {
		$webinar = new BP_Zoom_Webinar( $webinar );
	}

	// Check webinar.
	if ( empty( $webinar ) ) {
		return;
	}

	// Get webinar group.
	$group = groups_get_group( $webinar->group_id );

	// Check group exists.
	if ( empty( $group->id ) ) {
		return;
	}

	// Get members ids.
	$user_ids = BP_Groups_Member::get_group_member_ids( $group->id );

	if (
		function_exists( 'bb_notifications_background_enabled' ) &&
		true === bb_notifications_background_enabled() &&
		count( $user_ids ) > 20
	) {
		global $bb_background_updater, $bb_notifications_background_updater;
		$chunk_user_ids = array_chunk( $user_ids, 20 );
		if ( ! empty( $chunk_user_ids ) ) {
			foreach ( $chunk_user_ids as $key => $group_member_ids ) {
				$args_data = array(
					'callback' => 'bb_zoom_groups_webinar_notifications_details',
					'args'     => array(
						$group_member_ids,
						$webinar,
						$notification,
						$group,
					),
				);

				if ( ! empty( $bb_background_updater ) && $bb_background_updater instanceof BB_Background_Updater ) {

					$args_data['type']     = 'email';
					$args_data['group']    = 'zoom_groups_webinar_details';
					$args_data['data_id']  = $group->id;
					$args_data['priority'] = 5;

					$bb_background_updater->data( $args_data );
					$bb_background_updater->save();
				} else {
					$bb_notifications_background_updater->data( array( $args_data ) );
					$bb_notifications_background_updater->save();
				}
			}

			if ( ! empty( $bb_background_updater ) && $bb_background_updater instanceof BB_Background_Updater ) {
				$bb_background_updater->dispatch();
			} else {
				$bb_notifications_background_updater->dispatch();
			}
		}
	} else {
		bb_zoom_groups_webinar_notifications_details( $user_ids, $webinar, $notification, $group );
	}
}

/**
 * Create meeting activity for group.
 *
 * @since 1.0.7
 *
 * @param object|int $meeting BP_Zoom_Meeting Object or Meeting ID.
 * @param string     $type    Activity Type.
 */
function bp_zoom_groups_create_meeting_activity( $meeting, $type = '' ) {
	// Check activity component active or not.
	if ( ! bp_is_active( 'activity' ) ) {
		return;
	}

	// Check meeting object or id.
	if ( ! $meeting instanceof BP_Zoom_Meeting ) {
		$meeting = new BP_Zoom_Meeting( $meeting );
	}

	// Check meeting.
	if ( empty( $meeting ) ) {
		return;
	}

	// Get meeting group.
	$group = groups_get_group( $meeting->group_id );

	// Check group exists.
	if ( empty( $group->id ) ) {
		return;
	}

	$meeting_activity = 0;

	if ( $meeting->activity_id ) {
		$meeting_activity = $meeting->activity_id;
	}

	if ( empty( $type ) ) {
		$type = 'zoom_meeting_create';
	}

	$activity_id = groups_record_activity(
		array(
			'user_id'           => $meeting->user_id,
			'content'           => '',
			'type'              => $type,
			'item_id'           => $meeting->group_id,
			'secondary_item_id' => $meeting->id,
		)
	);

	if ( $activity_id ) {

			// save activity id in meeting.
		if ( ! empty( $meeting_activity ) ) {
			// update meta for simple meeting notification.
			bp_zoom_meeting_update_meta( $meeting->id, 'zoom_notification_activity_id', $activity_id );

			// setup activity meta for notification activity.
			bp_activity_update_meta( $activity_id, 'zoom_notification_activity', true );
		} else {
			remove_action( 'bp_zoom_meeting_after_save', 'bp_zoom_meeting_after_save_update_meeting_data', 1 );

			$meeting->activity_id = $activity_id;
			$meeting->save();

			add_action( 'bp_zoom_meeting_after_save', 'bp_zoom_meeting_after_save_update_meeting_data', 1 );

			// setup activity meta for notification activity.
			if ( 'meeting_occurrence' === $meeting->zoom_type ) {
				bp_activity_update_meta( $activity_id, 'zoom_notification_activity', true );
			}
		}

		// update activity meta.
		bp_activity_update_meta( $activity_id, 'bp_meeting_id', $meeting->id );

		groups_update_groupmeta( $meeting->group_id, 'last_activity', bp_core_current_time() );
	}
}

/**
 * Create webinar activity for group.
 *
 * @since 1.0.9
 * @param object|int $webinar BP_Zoom_Webinar Object or Webinar ID.
 * @param string     $type    Activity Type.
 */
function bp_zoom_groups_create_webinar_activity( $webinar, $type = '' ) {
	// Check activity component active or not.
	if ( ! bp_is_active( 'activity' ) ) {
		return;
	}

	// Check webinar object or id.
	if ( ! $webinar instanceof BP_Zoom_Webinar ) {
		$webinar = new BP_Zoom_Webinar( $webinar );
	}

	// Check webinar.
	if ( empty( $webinar ) ) {
		return;
	}

	// Get webinar group.
	$group = groups_get_group( $webinar->group_id );

	// Check group exists.
	if ( empty( $group->id ) ) {
		return;
	}

	$webinar_activity = 0;

	if ( $webinar->activity_id ) {
		$webinar_activity = $webinar->activity_id;
	}

	if ( empty( $type ) ) {
		$type = 'zoom_webinar_create';
	}

	$activity_id = groups_record_activity(
		array(
			'user_id'           => $webinar->user_id,
			'content'           => '',
			'type'              => $type,
			'item_id'           => $webinar->group_id,
			'secondary_item_id' => $webinar->id,
		)
	);

	if ( $activity_id ) {

		// save activity id in webinar.
		if ( ! empty( $webinar_activity ) ) {
			// update meta for simple webinar notification.
			bp_zoom_webinar_update_meta( $webinar->id, 'zoom_notification_activity_id', $activity_id );

			// setup activity meta for notification activity.
			bp_activity_update_meta( $activity_id, 'zoom_notification_activity', true );
		} else {
			remove_action( 'bp_zoom_webinar_after_save', 'bp_zoom_webinar_after_save_update_webinar_data', 1 );

			$webinar->activity_id = $activity_id;
			$webinar->save();

			add_action( 'bp_zoom_webinar_after_save', 'bp_zoom_webinar_after_save_update_webinar_data', 1 );

			// setup activity meta for notification activity.
			if ( 'webinar_occurrence' === $webinar->zoom_type ) {
				bp_activity_update_meta( $activity_id, 'zoom_notification_activity', true );
			}
		}

		// update activity meta.
		bp_activity_update_meta( $activity_id, 'bp_webinar_id', $webinar->id );

		groups_update_groupmeta( $webinar->group_id, 'last_activity', bp_core_current_time() );
	}
}

/**
 * Create notification meta based on zoom.
 *
 * @since 1.2.1
 *
 * @param object $notification Notification object.
 */
function bb_groups_zoom_add_notification_metas( $notification ) {
	if (
		! function_exists( 'bb_enabled_legacy_email_preference' ) ||
		( function_exists( 'bb_enabled_legacy_email_preference' ) && bb_enabled_legacy_email_preference() ) ||
		empty( $notification->id ) ||
		empty( $notification->item_id ) ||
		empty( $notification->secondary_item_id ) ||
		empty( $notification->component_action ) ||
		! in_array( $notification->component_action, array( 'bb_groups_new_zoom' ), true )
	) {
		return;
	}

	global $bb_zoom_type;
	global $bb_zoom_is_created;

	if ( $bb_zoom_type ) {
		bp_notifications_update_meta( $notification->id, 'type', $bb_zoom_type );
		$bb_zoom_type = '';
	}

	if ( $bb_zoom_is_created ) {
		bp_notifications_update_meta( $notification->id, 'is_created', $bb_zoom_is_created );
		$bb_zoom_is_created = '';
	}
}

/**
 * Function will run zoom meeting notifications and emails.
 *
 * @since 2.0.5
 *
 * @param array  $user_ids     Array of user Ids.
 * @param object $meeting      Meeting Object.
 * @param bool   $notification Whether notification or not.
 * @param object $group        Group Object.
 */
function bb_zoom_groups_meeting_notifications_details( $user_ids, $meeting, $notification, $group ) {
	// bail if any one empty from the User ids, Meeting and Group.
	if (
		empty( $user_ids ) ||
		empty( $meeting ) ||
		empty( $group )
	) {
		return;
	}

	global $bb_zoom_type, $bb_zoom_is_created;

	foreach ( (array) $user_ids as $user_id ) {

		// Do not sent notification for meeting creator.
		if ( (int) $meeting->user_id === (int) $user_id ) {
			continue;
		}

		$action     = 'zoom_meeting_created';
		$is_created = true;

		if ( true === $notification ) {
			$action     = 'zoom_meeting_notified';
			$is_created = false;
		}

		if ( function_exists( 'bb_enabled_legacy_email_preference' ) && ! bb_enabled_legacy_email_preference() ) {
			$action = 'bb_groups_new_zoom';
		}

		$bb_zoom_type       = 'meeting';
		$bb_zoom_is_created = $is_created;

		add_action( 'bp_notification_after_save', 'bb_groups_zoom_add_notification_metas', 5 );

		// Trigger a BuddyPress Notification.
		bp_notifications_add_notification(
			array(
				'user_id'           => $user_id,
				'item_id'           => $meeting->group_id,
				'secondary_item_id' => $meeting->id,
				'component_name'    => buddypress()->groups->id,
				'component_action'  => $action,
				'allow_duplicate'   => true,
			)
		);

		remove_action( 'bp_notification_after_save', 'bb_groups_zoom_add_notification_metas', 5 );

		// Now email the user with the contents of the zoom meeting (if they have enabled email notifications).
		if (
			(
				function_exists( 'bb_enabled_legacy_email_preference' ) &&
				(
					(
						! bb_enabled_legacy_email_preference() &&
						true === bb_is_notification_enabled( (int) $user_id, 'bb_groups_new_zoom' )
					) ||
					(
						bb_enabled_legacy_email_preference() &&
						'no' !== bp_get_user_meta( $user_id, 'notification_zoom_meeting_scheduled', true )
					)
				)
			) || (
				! function_exists( 'bb_is_notification_enabled' ) &&
				'no' !== bp_get_user_meta( $user_id, 'notification_zoom_meeting_scheduled', true )
			)
		) {

			$unsubscribe_args = array(
				'user_id'           => $user_id,
				'notification_type' => 'zoom-scheduled-meeting-email',
			);

			$poster_name = bp_core_get_user_displayname( $meeting->user_id );

			$args = array(
				'tokens' => array(
					'zoom_meeting'     => $meeting,
					'zoom_meeting.id'  => $meeting->id,
					'group.name'       => $group->name,
					'group.url'        => bp_get_group_permalink( $group ),
					'poster.name'      => $poster_name,
					'receiver-user.id' => $user_id,
					'unsubscribe'      => esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) ),
				),
			);

			bp_send_email( 'zoom-scheduled-meeting-email', $user_id, $args );
		}
	}
}

/**
 * Function will run zoom webinar notifications and emails.
 *
 * @since 2.0.5
 *
 * @param array  $user_ids     Array of user Ids.
 * @param object $webinar      Webinar Object.
 * @param bool   $notification Whether notification or not.
 * @param object $group        Group Object.
 */
function bb_zoom_groups_webinar_notifications_details( $user_ids, $webinar, $notification, $group ) {
	// bail if any one empty from the User ids, Webinar and Group.
	if (
		empty( $user_ids ) ||
		empty( $webinar ) ||
		empty( $group )
	) {
		return;
	}

	global $bb_zoom_type, $bb_zoom_is_created;

	foreach ( (array) $user_ids as $user_id ) {

		// Do not sent notification for meeting creator.
		if ( (int) $webinar->user_id === (int) $user_id ) {
			continue;
		}

		$action     = 'zoom_webinar_created';
		$is_created = true;

		if ( true === $notification ) {
			$action     = 'zoom_webinar_notified';
			$is_created = false;
		}

		if ( function_exists( 'bb_enabled_legacy_email_preference' ) && ! bb_enabled_legacy_email_preference() ) {
			$action = 'bb_groups_new_zoom';
		}

		$bb_zoom_type       = 'webinar';
		$bb_zoom_is_created = $is_created;

		add_action( 'bp_notification_after_save', 'bb_groups_zoom_add_notification_metas', 5 );

		// Trigger a BuddyPress Notification.
		bp_notifications_add_notification(
			array(
				'user_id'           => $user_id,
				'item_id'           => $webinar->group_id,
				'secondary_item_id' => $webinar->id,
				'component_name'    => buddypress()->groups->id,
				'component_action'  => $action,
				'allow_duplicate'   => true,
			)
		);

		remove_action( 'bp_notification_after_save', 'bb_groups_zoom_add_notification_metas', 5 );

		// Now email the user with the contents of the zoom webinar (if they have enabled email notifications).
		if (
			(
				function_exists( 'bb_enabled_legacy_email_preference' ) &&
				(
					(
						! bb_enabled_legacy_email_preference() &&
						true === bb_is_notification_enabled( (int) $user_id, 'bb_groups_new_zoom' )
					) ||
					(
						bb_enabled_legacy_email_preference() &&
						'no' !== bp_get_user_meta( $user_id, 'notification_zoom_webinar_scheduled', true )
					)
				)
			) || (
				! function_exists( 'bb_is_notification_enabled' ) &&
				'no' !== bp_get_user_meta( $user_id, 'notification_zoom_webinar_scheduled', true )
			)
		) {
			$unsubscribe_args = array(
				'user_id'           => $user_id,
				'notification_type' => 'zoom-scheduled-webinar-email',
			);

			$poster_name = bp_core_get_user_displayname( $webinar->user_id );

			$args = array(
				'tokens' => array(
					'zoom_webinar'     => $webinar,
					'zoom_webinar.id'  => $webinar->id,
					'group.name'       => $group->name,
					'group.url'        => bp_get_group_permalink( $group ),
					'poster.name'      => $poster_name,
					'receiver-user.id' => $user_id,
					'unsubscribe'      => esc_url( bp_email_get_unsubscribe_link( $unsubscribe_args ) ),
				),
			);

			bp_send_email( 'zoom-scheduled-webinar-email', $user_id, $args );
		}
	}
}

/**
 * Function to connect with zoom API using S2S/JWT credentials.
 *
 * @since 2.3.91
 *
 * @param int $group_id ID of a group.
 */
function bb_zoom_group_connect_api( $group_id ) {
	if ( bb_zoom_group_is_s2s_connected( $group_id ) ) {

		$connection_type = bb_zoom_group_get_connection_type( $group_id );
		if ( 'site' === $connection_type ) {
			bp_zoom_conference()->zoom_api_account_id    = bb_zoom_account_id();
			bp_zoom_conference()->zoom_api_client_id     = bb_zoom_client_id();
			bp_zoom_conference()->zoom_api_client_secret = bb_zoom_client_secret();
		} elseif ( 'group' === $connection_type ) {
			bp_zoom_conference()->zoom_api_account_id    = groups_get_groupmeta( $group_id, 'bb-group-zoom-s2s-account-id' );
			bp_zoom_conference()->zoom_api_client_id     = groups_get_groupmeta( $group_id, 'bb-group-zoom-s2s-client-id' );
			bp_zoom_conference()->zoom_api_client_secret = groups_get_groupmeta( $group_id, 'bb-group-zoom-s2s-client-secret' );
		}

		BP_Zoom_Conference_Api::$group_id = $group_id;
	}
}

/**
 * Get group zoom account email.
 *
 * @since 2.3.91
 *
 * @param int $group_id ID of a group.
 *
 * @return string|bool
 */
function bb_zoom_group_get_email_account( $group_id ) {
	$host_email = false;

	$connection_type = bb_zoom_group_get_connection_type( $group_id );
	if ( 'site' === $connection_type ) {
		$host_email = bb_zoom_account_email();
	} elseif ( 'group' === $connection_type ) {
		$host_email = groups_get_groupmeta( $group_id, 'bb-group-zoom-s2s-api-email' );
	}

	return $host_email;
}

/**
 * Get group zoom host type.
 *
 * @since 2.3.91
 *
 * @param int $group_id ID of a group.
 *
 * @return int
 */
function bb_zoom_group_get_host_type( $group_id ) {
	$host_type = '';

	$bb_group_zoom   = array();
	$connection_type = bb_zoom_group_get_connection_type( $group_id );
	if ( 'site' === $connection_type ) {
		$bb_group_zoom = bb_get_zoom_block_settings();
	} elseif ( 'group' === $connection_type ) {
		$bb_group_zoom = groups_get_groupmeta( $group_id, 'bb-group-zoom' );
	}

	if (
		! empty( $bb_group_zoom ) &&
		! empty( $bb_group_zoom['account_host_user'] ) &&
		! empty( $bb_group_zoom['account_host_user']->type )
	) {
		$host_type = $bb_group_zoom['account_host_user']->type;
	}

	return $host_type;
}

/**
 * Get group zoom host user.
 *
 * @since 2.3.91
 *
 * @param int $group_id ID of a group.
 *
 * @return mixed
 */
function bb_zoom_group_get_api_host_user( $group_id ) {
	$bb_group_zoom   = array();
	$connection_type = bb_zoom_group_get_connection_type( $group_id );
	if ( 'site' === $connection_type ) {
		$bb_group_zoom = bb_get_zoom_block_settings();
	} elseif ( 'group' === $connection_type ) {
		$bb_group_zoom = groups_get_groupmeta( $group_id, 'bb-group-zoom' );
	}

	$api_host_user = $bb_group_zoom['account_host_user'] ?? '';

	return $api_host_user;
}

/**
 * Hide/Un-hide group zoom meetings/webinars.
 *
 * @since 2.3.91
 *
 * @param int    $group_id      ID of a group.
 * @param string $api_email     Account email of new zoom connection.
 * @param string $old_api_email Account email of old zoom connection.
 */
function bb_zoom_group_hide_unhide_meetings( $group_id, $api_email, $old_api_email = '' ) {
	global $wpdb, $bp;

	// Hide old host meetings.
	if ( ! empty( $old_api_email ) ) {
		$query = $wpdb->prepare( "UPDATE {$bp->table_prefix}bp_zoom_meetings SET hide_sitewide = %d WHERE group_id = %d", '1', $group_id ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( ! empty( $api_email ) ) {
			$query .= $wpdb->prepare( ' AND ( host_id != %s OR host_id = %s )', $api_email, $old_api_email ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
		} else {
			$query .= $wpdb->prepare( ' AND host_id = %s', $old_api_email ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
		}
		$wpdb->query( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
	}

	// Un-hide current host meetings.
	if ( ! empty( $api_email ) ) {
		$wpdb->query( $wpdb->prepare( "UPDATE {$bp->table_prefix}bp_zoom_meetings SET hide_sitewide = %d WHERE group_id = %d AND host_id = %s", '0', $group_id, $api_email ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}

	// Hide old host webinars.
	if ( ! empty( $old_api_email ) ) {
		$query = $wpdb->prepare( "UPDATE {$bp->table_prefix}bp_zoom_webinars SET hide_sitewide = %d WHERE group_id = %d", '1', $group_id ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery

		if ( ! empty( $api_email ) ) {
			$query .= $wpdb->prepare( ' AND ( host_id != %s OR host_id = %s )', $api_email, $old_api_email ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
		} else {
			$query .= $wpdb->prepare( ' AND host_id = %s', $old_api_email ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery
		}
		$wpdb->query( $query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
	}

	// Un-hide current host webinars.
	if ( ! empty( $api_email ) ) {
		$wpdb->query( $wpdb->prepare( "UPDATE {$bp->table_prefix}bp_zoom_webinars SET hide_sitewide = %d WHERE group_id = %d AND host_id = %s", '0', $group_id, $api_email ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
	}
}

/**
 * Generate the signature based on connected with zoom.
 *
 * @since 2.3.91
 *
 * @param array $args Array of group ID, Meeting ID and Role.
 *
 * @return array Return signature and Client ID if connected with S2S.
 */
function bb_zoom_group_generate_browser_credential( $args = array() ) {
	$result = array(
		'sign'          => '',
		'sdk_client_id' => '',
	);

	$args = bp_parse_args(
		$args,
		array(
			'group_id'       => 0,
			'meeting_number' => '',
			'role'           => '',
		)
	);

	if ( empty( $args['group_id'] ) || empty( $args['meeting_number'] ) ) {
		return $result;
	}

	$api_key    = '';
	$api_secret = '';
	if ( bb_zoom_is_meeting_sdk() ) {
		$api_key                 = bb_zoom_sdk_client_id();
		$api_secret              = bb_zoom_sdk_client_secret();
		$result['sdk_client_id'] = $api_key;
	}

	if ( ! empty( $api_key ) && ! empty( $api_secret ) ) {
		$result['sign'] = bb_get_meeting_signature( $api_key, $api_secret, $args['meeting_number'], $args['role'] );
	}

	return $result;
}

/**
 * Display notice based on a type.
 *
 * @since 2.3.91
 *
 * @param array|string $messages Notice Message.
 * @param string       $type     Notice Type (error, success and warning).
 */
function bb_zoom_group_display_feedback_notice( $messages, $type = 'error' ) {
	$feedback = ( is_array( $messages ) ? implode( '<br/>', $messages ) : $messages );
	?>
	<div class="bp-messages-feedback">
		<aside class="bp-feedback bp-feedback-v2 bp-messages <?php echo esc_html( $type ); ?>">
			<span class="bp-icon" aria-hidden="true"></span>
			<p><?php echo wp_kses_post( $feedback ); ?></p>
		</aside>
	</div>
	<?php
}

/**
 * Save and validate s2s credentials for group zoom.
 *
 * @since 2.3.91
 *
 * @param array $args Array of s2s credentials.
 */
function bb_zoom_group_save_s2s_credentials( $args = array() ) {
	$r = bp_parse_args(
		$args,
		array(
			'account_id'    => '',
			'client_id'     => '',
			'client_secret' => '',
			'account_email' => '',
			'secret_token'  => '',
			'group_id'      => 0,
		)
	);

	// Retrieve old settings.
	$old_s2s_api_email = groups_get_groupmeta( $r['group_id'], 'bb-group-zoom-s2s-api-email' );
	$bb_group_zoom     = groups_get_groupmeta( $r['group_id'], 'bb-group-zoom' );
	if ( empty( $bb_group_zoom ) ) {
		$bb_group_zoom = array();
	}

	groups_update_groupmeta( $r['group_id'], 'bb-group-zoom-s2s-account-id', $r['account_id'] );
	groups_update_groupmeta( $r['group_id'], 'bb-group-zoom-s2s-client-id', $r['client_id'] );
	groups_update_groupmeta( $r['group_id'], 'bb-group-zoom-s2s-client-secret', $r['client_secret'] );
	groups_update_groupmeta( $r['group_id'], 'bb-group-zoom-s2s-api-email', $r['account_email'] );
	groups_update_groupmeta( $r['group_id'], 'bb-group-zoom-s2s-secret-token', $r['secret_token'] );

	$bb_group_zoom['zoom_success']               = '';
	$bb_group_zoom['zoom_errors']                = array();
	$bb_group_zoom['zoom_warnings']              = array();
	$bb_group_zoom['sidewide_errors']            = array();
	$bb_group_zoom['account_host']               = '';
	$bb_group_zoom['account_host_user']          = array();
	$bb_group_zoom['account_host_user_settings'] = array();
	$bb_group_zoom['zoom_is_connected']          = false;

	if (
		! empty( $r['account_id'] ) &&
		! empty( $r['client_id'] ) &&
		! empty( $r['client_secret'] )
	) {
		$fetch_data = bb_zoom_fetch_account_emails(
			array(
				'account_id'    => $r['account_id'],
				'client_id'     => $r['client_id'],
				'client_secret' => $r['client_secret'],
				'account_email' => $r['account_email'],
				'group_id'      => $r['group_id'],
				'force_api'     => true,
			)
		);

		if ( is_wp_error( $fetch_data ) ) {
			$bb_group_zoom['zoom_errors'][] = $fetch_data;
		} elseif ( ! empty( $fetch_data ) && ! is_wp_error( $fetch_data ) ) {
			$bb_group_zoom['zoom_is_connected'] = true;

			if ( ! array_key_exists( $r['account_email'], $fetch_data ) ) {
				$bb_group_zoom['zoom_warnings'][] = new WP_Error( 'email_not_found', __( 'Email not found in Zoom account.', 'buddyboss-pro' ) );
			} else {
				$bb_group_zoom['zoom_success'] = sprintf(
				/* translators: %s: Account Email. */
					esc_html__( 'Connected to Zoom %s', 'buddyboss-pro' ),
					'(' . $r['account_email'] . ')'
				);
			}

			$bb_group_zoom['account_host_user']          = get_transient( 'bp_zoom_account_host_user_' . $r['group_id'] );
			$bb_group_zoom['account_host_user_settings'] = get_transient( 'bp_zoom_account_host_user_settings_' . $r['group_id'] );
			$is_webinar_enabled                          = get_transient( 'bp_zoom_is_webinar_enabled_' . $r['group_id'] );

			// Check webinar is enabled or not.
			if ( true === $is_webinar_enabled ) {
				groups_update_groupmeta( $r['group_id'], 'bp-group-zoom-enable-webinar', true );
			} else {
				groups_delete_groupmeta( $r['group_id'], 'bp-group-zoom-enable-webinar' );
			}

			// If old and new accounts are not the same, then update meetings.
			if ( $old_s2s_api_email !== $r['account_email'] ) {
				bb_zoom_group_hide_unhide_meetings( $r['group_id'], $r['account_email'], $old_s2s_api_email );
			}

			// Delete transient.
			delete_transient( 'bp_zoom_account_host_user_' . $r['group_id'] );
			delete_transient( 'bp_zoom_account_host_user_settings_' . $r['group_id'] );
			delete_transient( 'bp_zoom_is_webinar_enabled_' . $r['group_id'] );
		}
	} else {
		groups_delete_groupmeta( $r['group_id'], 'bb-zoom-account-emails' );
		groups_delete_groupmeta( $r['group_id'], 'bb-group-zoom-s2s-api-email' );

		$all_s2s_blank = false;
		if (
			empty( $r['account_id'] ) &&
			empty( $r['client_id'] ) &&
			empty( $r['client_secret'] )
		) {
			$all_s2s_blank = true;
		}

		if ( ! $all_s2s_blank ) {
			if ( empty( $r['account_id'] ) ) {
				$bb_group_zoom['zoom_errors'][] = new WP_Error( 'no_zoom_account_id', __( 'The Account ID is required.', 'buddyboss-pro' ) );
			} elseif ( empty( $r['client_id'] ) ) {
				$bb_group_zoom['zoom_errors'][] = new WP_Error( 'no_zoom_client_id', __( 'The Client ID is required.', 'buddyboss-pro' ) );
			} elseif ( empty( $r['client_secret'] ) ) {
				$bb_group_zoom['zoom_errors'][] = new WP_Error( 'no_zoom_client_secret', __( 'The Client Secret is required.', 'buddyboss-pro' ) );
			}
		}
	}
	groups_update_groupmeta( $r['group_id'], 'bb-group-zoom', $bb_group_zoom );
}
