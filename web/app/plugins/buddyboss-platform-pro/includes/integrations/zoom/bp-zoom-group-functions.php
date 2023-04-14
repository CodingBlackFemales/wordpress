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
		global $bb_notifications_background_updater;
		$chunk_user_ids = array_chunk( $user_ids, 20 );
		if ( ! empty( $chunk_user_ids ) ) {
			foreach ( $chunk_user_ids as $key => $group_member_ids ) {
				$bb_notifications_background_updater->data(
					array(
						array(
							'callback' => 'bb_zoom_groups_meeting_notifications_details',
							'args'     => array(
								$group_member_ids,
								$meeting,
								$notification,
								$group,
							),
						),
					)
				);
				$bb_notifications_background_updater->save();
			}
			$bb_notifications_background_updater->dispatch();
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
		global $bb_notifications_background_updater;
		$chunk_user_ids = array_chunk( $user_ids, 20 );
		if ( ! empty( $chunk_user_ids ) ) {
			foreach ( $chunk_user_ids as $key => $group_member_ids ) {
				$bb_notifications_background_updater->data(
					array(
						array(
							'callback' => 'bb_zoom_groups_webinar_notifications_details',
							'args'     => array(
								$group_member_ids,
								$webinar,
								$notification,
								$group,
							),
						),
					)
				);
				$bb_notifications_background_updater->save();
			}
			$bb_notifications_background_updater->dispatch();
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

	/* translators: %1$s - user link, %2$s - group link. */
	$action = sprintf( __( '%1$s scheduled a Zoom meeting in the group %2$s', 'buddyboss-pro' ), bp_core_get_userlink( $meeting->user_id ), '<a href="' . bp_get_group_permalink( $group ) . '">' . esc_attr( $group->name ) . '</a>' );

	$activity_id = groups_record_activity(
		array(
			'user_id'           => $meeting->user_id,
			'action'            => $action,
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

	/* translators: %1$s - user link, %2$s - group link. */
	$action = sprintf( __( '%1$s scheduled a Zoom webinar in the group %2$s', 'buddyboss-pro' ), bp_core_get_userlink( $webinar->user_id ), '<a href="' . bp_get_group_permalink( $group ) . '">' . esc_attr( $group->name ) . '</a>' );

	$activity_id = groups_record_activity(
		array(
			'user_id'           => $webinar->user_id,
			'action'            => $action,
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
 * @param array  $user_ids
 * @param object $meeting
 * @param object $notification
 * @param object $group
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
 * @param array  $user_ids
 * @param object $webinar
 * @param object $notification
 * @param object $group
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
