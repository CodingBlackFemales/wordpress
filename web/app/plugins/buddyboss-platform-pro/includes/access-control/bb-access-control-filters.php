<?php
/**
 * Access Control filters.
 *
 * @package BuddyBossPro
 *
 * @since   1.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

// Filters.
add_filter( 'bp_user_can_create_groups', 'bb_access_control_has_create_group_access', PHP_INT_MAX, 2 );
add_filter( 'bp_user_can_join_groups', 'bb_access_control_has_join_group_access', PHP_INT_MAX, 2 );
add_filter( 'bb_groups_user_can_send_membership_requests', 'bb_access_control_has_join_group_request_access_control_access', PHP_INT_MAX, 2 );
add_filter( 'bp_nouveau_get_groups_buttons', 'bb_access_control_group_accept_access_control_access', PHP_INT_MAX, 3 );
add_filter( 'bb_user_can_create_activity', 'bb_access_control_user_can_create_activity', PHP_INT_MAX, 1 );
// Note: We will disable comment later.
// add_filter( 'bp_activity_can_comment', 'bb_access_control_user_can_create_activity_comment', PHP_INT_MAX, 2 ); // phpcs:ignore.
add_filter( 'bb_user_can_create_document', 'bb_access_control_user_can_upload_document', PHP_INT_MAX, 1 );
add_filter( 'bb_user_can_create_media', 'bb_access_control_user_can_upload_media', PHP_INT_MAX, 1 );
add_filter( 'bb_user_can_create_video', 'bb_access_control_user_can_upload_video', PHP_INT_MAX, 1 );
add_filter( 'bp_get_add_friend_button', 'bb_access_control_user_can_send_friend_request', 11, 1 );
add_filter( 'bp_nouveau_get_members_buttons', 'bb_access_control_member_header_user_can_send_friend_request', PHP_INT_MAX, 1 );
add_filter( 'bp_nouveau_get_members_buttons', 'bb_access_control_member_header_user_can_send_message_request', PHP_INT_MAX, 1 );
add_filter( 'bb_user_can_send_messages', 'bb_access_control_member_can_send_message', PHP_INT_MAX, 3 );
add_filter( 'bb_can_user_send_message_in_thread', 'bb_access_control_bb_can_user_send_message_in_thread', PHP_INT_MAX, 3 );
add_filter( 'bb_user_can_send_group_message', 'bb_access_control_user_can_send_group_message', PHP_INT_MAX, 3 );
add_filter( 'bp_members_suggestions_results', 'bb_access_control_member_can_send_message_search_recipients', PHP_INT_MAX, 1 );
add_filter( 'bp_get_button', 'bb_access_control_member_can_see_send_message', PHP_INT_MAX, 3 );
add_filter( 'bp_nouveau_get_activity_entry_buttons', 'bb_access_control_member_can_edit_activity', PHP_INT_MAX, 2 );
add_filter( 'groups_get_group_potential_invites_requests_args', 'bb_access_control_groups_potential_invites', PHP_INT_MAX, 1 );
add_filter( 'bp_group_member_query_group_member_ids', 'bb_access_control_bp_group_member_query_group_member_ids', PHP_INT_MAX, 2 );

// Actions.
add_action( 'bp_before_group_request_membership_content', 'bb_access_control_before_has_group_request_access_control_access', PHP_INT_MAX, 1 );
add_action( 'bp_after_group_request_membership_content', 'bb_access_control_after_has_group_request_access_control_access', PHP_INT_MAX, 1 );

/**
 * Filters the member IDs for the current group member query.
 *
 * Use this filter to build a custom query (such as when you've
 * defined a custom 'type').
 *
 * @since 1.1.0
 *
 * @param array                 $group_member_ids          Array of associated member IDs.
 * @param BP_Group_Member_Query $group_member_query_object Current BP_Group_Member_Query instance.
 */
function bb_access_control_bp_group_member_query_group_member_ids( $group_member_ids, $group_member_query_object ) {

	if ( bp_is_active( 'groups' ) && bp_is_group_single() && bp_is_group_messages() && 'private-message' === bb_get_group_current_messages_tab() ) {

		$can_send_arr  = array();
		$cant_send_arr = array();

		foreach ( $group_member_ids as $member_id ) {
			$can_send_group_message = apply_filters( 'bb_user_can_send_group_message', true, $member_id, bp_loggedin_user_id() );
			if ( $can_send_group_message ) {
				$can_send_arr[] = $member_id;
			} else {
				$cant_send_arr[] = $member_id;
			}
		}

		$group_member_ids = array_merge( $can_send_arr, $cant_send_arr );

	}

	/**
	 * Filters the member IDs for the current group member query.
	 *
	 * Use this filter to build a custom query (such as when you've
	 * defined a custom 'type').
	 *
	 * @since 1.1.0
	 *
	 * @param array                 $group_member_ids          Array of associated member IDs.
	 * @param BP_Group_Member_Query $group_member_query_object Current BP_Group_Member_Query instance.
	 */
	return apply_filters( 'bb_access_control_bp_group_member_query_group_member_ids', $group_member_ids, $group_member_query_object );
}

/**
 * Function will hide the upload media button if user do not have access.
 *
 * @param boolean $create whether user can see the upload media button or not.
 *
 * @since 1.1.0
 *
 * @return boolean $has_access whether user can see the upload media button or not.
 */
function bb_access_control_user_can_upload_media( $create ) {

	$create_media_settings = bb_access_control_upload_photos_settings();
	$has_access            = false;
	if ( empty( $create_media_settings ) || ( isset( $create_media_settings['access-control-type'] ) && empty( $create_media_settings['access-control-type'] ) ) ) {
		$has_access = $create;
	} elseif ( is_array( $create_media_settings ) && isset( $create_media_settings['access-control-type'] ) && ! empty( $create_media_settings['access-control-type'] ) ) {

		$access_controls        = BB_Access_Control::bb_get_access_control_lists();
		$option_access_controls = $create_media_settings['access-control-type'];
		$can_accept             = bb_access_control_has_access( bp_loggedin_user_id(), $access_controls, $option_access_controls, $create_media_settings );

		if ( $can_accept ) {
			$has_access = $create;
		}
	}

	/**
	 * Filter which will return whether user can see the upload media button or not.
	 *
	 * @since 1.1.0
	 */
	return apply_filters( 'bb_access_control_user_can_upload_media', $has_access );
}

/**
 * Function will hide the upload video button if user do not have access.
 *
 * @param boolean $create whether user can see the upload video button or not.
 *
 * @since 1.1.4
 *
 * @return boolean $has_access whether user can see the upload video button or not.
 */
function bb_access_control_user_can_upload_video( $create ) {

	$create_video_settings = bb_access_control_upload_videos_settings();
	$has_access            = false;
	if ( empty( $create_video_settings ) || ( isset( $create_video_settings['access-control-type'] ) && empty( $create_video_settings['access-control-type'] ) ) ) {
		$has_access = $create;
	} elseif ( is_array( $create_video_settings ) && isset( $create_video_settings['access-control-type'] ) && ! empty( $create_video_settings['access-control-type'] ) ) {

		$access_controls        = BB_Access_Control::bb_get_access_control_lists();
		$option_access_controls = $create_video_settings['access-control-type'];
		$can_accept             = bb_access_control_has_access( bp_loggedin_user_id(), $access_controls, $option_access_controls, $create_video_settings );

		if ( $can_accept ) {
			$has_access = $create;
		}
	}

	/**
	 * Filter which will return whether user can see the upload video button or not.
	 *
	 * @since 1.1.4
	 */
	return apply_filters( 'bb_access_control_user_can_upload_video', $has_access );
}

/**
 * Function will hide the upload document button if user do not have access.
 *
 * @param boolean $create whether user can see the upload document button or not.
 *
 * @since 1.1.0
 *
 * @return boolean $has_access whether user can see the upload document button or not.
 */
function bb_access_control_user_can_upload_document( $create ) {

	$create_document_settings = bb_access_control_upload_document_settings();
	$has_access               = false;

	if ( empty( $create_document_settings ) || ( isset( $create_document_settings['access-control-type'] ) && empty( $create_document_settings['access-control-type'] ) ) ) {
		$has_access = $create;
	} elseif ( is_array( $create_document_settings ) && isset( $create_document_settings['access-control-type'] ) && ! empty( $create_document_settings['access-control-type'] ) ) {

		$access_controls        = BB_Access_Control::bb_get_access_control_lists();
		$option_access_controls = $create_document_settings['access-control-type'];
		$can_accept             = bb_access_control_has_access( bp_loggedin_user_id(), $access_controls, $option_access_controls, $create_document_settings );

		if ( $can_accept ) {
			$has_access = $create;
		}
	}

	/**
	 * Filter which will return whether user can see the upload document button or not.
	 *
	 * @since 1.1.0
	 */
	return apply_filters( 'bb_access_control_user_can_upload_document', $has_access );
}

/**
 * Function will check if user can create a activity or not.
 *
 * @param boolean $create whether user can create a activity or not.
 *
 * @since 1.1.0
 *
 * @return boolean whether user can create a activity or not.
 */
function bb_access_control_user_can_create_activity( $create ) {

	$create_activity_settings = bb_access_control_create_activity_settings();
	$has_access               = false;
	if ( empty( $create_activity_settings ) || ( isset( $create_activity_settings['access-control-type'] ) && empty( $create_activity_settings['access-control-type'] ) ) ) {
		$has_access = $create;
	} elseif ( is_array( $create_activity_settings ) && isset( $create_activity_settings['access-control-type'] ) && ! empty( $create_activity_settings['access-control-type'] ) ) {

		$access_controls        = BB_Access_Control::bb_get_access_control_lists();
		$option_access_controls = $create_activity_settings['access-control-type'];
		$can_accept             = bb_access_control_has_access( bp_loggedin_user_id(), $access_controls, $option_access_controls, $create_activity_settings );

		if ( $can_accept ) {
			$has_access = $create;
		}
	}

	/**
	 * Filter which will return whether user can create a activity or not.
	 *
	 * @since 1.1.0
	 */
	return apply_filters( 'bb_access_control_user_can_create_activity', $has_access );
}

/**
 * Function will check if user can create a activity comment or not.
 *
 * @param bool   $can_comment Status on if activity reply can be commented on.
 * @param object $comment Current comment object being checked on.
 *
 * @since 1.1.0
 *
 * @return boolean whether user can create a activity comment or not.
 */
function bb_access_control_user_can_create_activity_comment( $can_comment, $comment ) {
	global $activities_template;
	if (
		bp_is_group_single() ||
		bp_is_group_activity() ||
		( ! empty( $activities_template->activity->component ) && 'groups' === $activities_template->activity->component )
	) {
		return $can_comment;
	}

	$create_activity_settings = bb_access_control_create_activity_settings();
	$has_access               = false;
	if ( empty( $create_activity_settings ) || ( isset( $create_activity_settings['access-control-type'] ) && empty( $create_activity_settings['access-control-type'] ) ) ) {
		$has_access = $can_comment;
	} elseif ( is_array( $create_activity_settings ) && isset( $create_activity_settings['access-control-type'] ) && ! empty( $create_activity_settings['access-control-type'] ) ) {

		$access_controls        = BB_Access_Control::bb_get_access_control_lists();
		$option_access_controls = $create_activity_settings['access-control-type'];
		$can_accept             = bb_access_control_has_access( bp_loggedin_user_id(), $access_controls, $option_access_controls, $create_activity_settings );

		if ( $can_accept ) {
			$has_access = $can_comment;
		}
	}

	/**
	 * Filter which will return whether user can create a activity comment or not.
	 *
	 * @since 1.1.0
	 */
	return apply_filters( 'bb_access_control_user_can_create_activity_comment', $has_access, $comment );
}

/**
 * Function will return the list of button if user can accept membership or not.
 *
 * @param array  $buttons The list of buttons.
 * @param int    $group   The current group object.
 * @param string $type    Whether we're displaying a groups loop or a groups single item.
 *
 * @since 1.1.0
 *
 * @return array The list of buttons.
 */
function bb_access_control_group_accept_access_control_access( $buttons, $group, $type ) {
	global $requests_template;
	$join_group_settings = bb_access_control_join_group_settings();
	$has_access          = $buttons;
	$user_id             = bp_loggedin_user_id();
	if ( 'request' === $type ) {
		$user_id = $requests_template->request->user_id;
	}

	if ( empty( $join_group_settings ) || ( isset( $join_group_settings['access-control-type'] ) && empty( $join_group_settings['access-control-type'] ) ) ) {
		$has_access = $buttons;
	} elseif ( is_array( $join_group_settings ) && isset( $join_group_settings['access-control-type'] ) && ! empty( $join_group_settings['access-control-type'] ) ) {

		$access_controls        = BB_Access_Control::bb_get_access_control_lists();
		$option_access_controls = $join_group_settings['access-control-type'];
		$can_accept             = bb_access_control_has_access( $user_id, $access_controls, $option_access_controls, $join_group_settings );

		if ( $can_accept ) {
			$has_access = $buttons;
		} else {
			unset( $buttons['accept_invite'] );
			unset( $buttons['membership_requested'] );
			unset( $buttons['group_membership_accept'] );
			$has_access = $buttons;
		}
	}

	/**
	 * Filter which return the list of button if user can accept membership or not.
	 *
	 * @since 1.1.0
	 */
	return apply_filters( 'bb_access_control_group_accept_access_control_access', $has_access, $group, $type );
}

/**
 * Function will check whether the person can create groups.
 *
 * @param bool $can_create Whether the person can create groups.
 * @param int  $restricted Whether or not group creation is restricted.
 *
 * @since 1.1.0
 *
 * @return bool Whether the person can create groups.
 */
function bb_access_control_has_create_group_access( $can_create, $restricted ) {

	$create_group_settings = bb_access_control_create_group_settings();
	$has_access            = $can_create;
	if ( empty( $create_group_settings ) || ( isset( $create_group_settings['access-control-type'] ) && empty( $create_group_settings['access-control-type'] ) ) ) {
		$has_access = $can_create;
	} elseif ( bp_restrict_group_creation() ) {
		$has_access = false;
	} elseif ( is_array( $create_group_settings ) && isset( $create_group_settings['access-control-type'] ) && ! empty( $create_group_settings['access-control-type'] ) ) {

		$access_controls        = BB_Access_Control::bb_get_access_control_lists();
		$option_access_controls = $create_group_settings['access-control-type'];
		$can_create             = bb_access_control_has_access( bp_loggedin_user_id(), $access_controls, $option_access_controls, $create_group_settings );
		$has_access             = $can_create;
	}

	/**
	 * Filter which return whether the person can create groups.
	 *
	 * @since 1.1.0
	 */
	return apply_filters( 'bb_access_control_has_create_group_access', $has_access, $restricted );

}

/**
 * Function will check whether user can join group or not.
 *
 * @param array  $button button lists.
 * @param object $group  group object.
 *
 * @since 1.1.0
 *
 * @return array available buttons.
 */
function bb_access_control_has_join_group_access( $button, $group ) {

	$exclude_buttons = apply_filters(
		'bp_groups_exclude_button_lists',
		array(
			'accept_invite',
			'leave_group',
		)
	);

	if ( in_array( $button['id'], $exclude_buttons ) ) { // phpcs:ignore WordPress.PHP.StrictInArray.MissingTrueStrict
		return $button;
	}

	$join_group_settings = bb_access_control_join_group_settings();
	$has_access          = array();
	if ( empty( $join_group_settings ) || ( isset( $join_group_settings['access-control-type'] ) && empty( $join_group_settings['access-control-type'] ) ) ) {
		$has_access = $button;
	} elseif ( is_array( $join_group_settings ) && isset( $join_group_settings['access-control-type'] ) && ! empty( $join_group_settings['access-control-type'] ) ) {

		$access_controls        = BB_Access_Control::bb_get_access_control_lists();
		$option_access_controls = $join_group_settings['access-control-type'];
		$can_create             = bb_access_control_has_access( bp_loggedin_user_id(), $access_controls, $option_access_controls, $join_group_settings );

		if ( $can_create ) {
			$has_access = $button;
		}
	}

	/**
	 * Filter which return whether the person can create groups.
	 *
	 * @since 1.1.0
	 */
	return apply_filters( 'bb_access_control_has_join_group_access', $has_access, $group );

}

/**
 * Check whetehr user has access based on given membership.
 *
 * @param int     $user_id            user id to check.
 * @param string  $access_controls        membership name.
 * @param string  $option_access_controls membership name.
 * @param array   $settings           DB settings.
 * @param boolean $threaded           check threaded.
 *
 * @since 1.1.0
 *
 * @return boolean user has access or not.
 */
function bb_access_control_has_access( $user_id, $access_controls, $option_access_controls, $settings, $threaded = false ) {

	$can_create = true;
	if ( 'membership' === $option_access_controls ) {
		$plugin_lists           = $access_controls[ $option_access_controls ]['class']::instance()->bb_get_access_control_plugins_lists();
		$option_access_controls = $settings['plugin-access-control-type'];
		if ( isset( $plugin_lists ) && isset( $plugin_lists[ $option_access_controls ] ) && $plugin_lists[ $option_access_controls ]['is_enabled'] ) {
			$can_create = $plugin_lists[ $option_access_controls ]['class']::instance()->has_access( $user_id, $settings, $threaded );
		}
	} elseif ( 'gamipress' === $option_access_controls ) {
		$gamipress_lists        = $access_controls[ $option_access_controls ]['class']::instance()->bb_get_access_control_gamipress_lists();
		$option_access_controls = $settings['gamipress-access-control-type'];
		if ( isset( $gamipress_lists ) && isset( $gamipress_lists[ $option_access_controls ] ) && $gamipress_lists[ $option_access_controls ]['is_enabled'] ) {
			$can_create = $gamipress_lists[ $option_access_controls ]['class']::instance()->has_access( $user_id, $settings, $threaded );
		}
	} else {
		if ( isset( $access_controls ) && isset( $access_controls[ $option_access_controls ] ) && $access_controls[ $option_access_controls ]['is_enabled'] ) {
			$can_create = $access_controls[ $option_access_controls ]['class']::instance()->has_access( $user_id, $settings, $threaded );
		}
	}

	return $can_create;
}

/**
 * Function will check whether or not user can send the group join request or not.
 *
 * @param boolean $can_send     user can send request.
 * @param object  $group_object group object.
 *
 * @since 1.1.0
 *
 * @return boolean whether or not user can send the group join request or not.
 */
function bb_access_control_has_join_group_request_access_control_access( $can_send, $group_object ) {

	$join_group_settings = bb_access_control_join_group_settings();
	$has_access          = false;
	if ( empty( $join_group_settings ) || ( isset( $join_group_settings['access-control-type'] ) && empty( $join_group_settings['access-control-type'] ) ) ) {
		$has_access = $can_send;
	} elseif ( is_array( $join_group_settings ) && isset( $join_group_settings['access-control-type'] ) && ! empty( $join_group_settings['access-control-type'] ) ) {

		$access_controls        = BB_Access_Control::bb_get_access_control_lists();
		$option_access_controls = $join_group_settings['access-control-type'];
		$can_create             = bb_access_control_has_access( bp_loggedin_user_id(), $access_controls, $option_access_controls, $join_group_settings );

		if ( $can_create ) {
			$has_access = $can_send;
		}
	}

	/**
	 * Filter will return whether or not user can send the group join request or not.
	 *
	 * @since 1.1.0
	 */
	return apply_filters( 'bb_access_control_has_join_group_request_access_control_access', $has_access, $group_object );

}

/**
 * Check user have access to send request membership.
 *
 * @since 1.1.0
 */
function bb_access_control_before_has_group_request_access_control_access() {

	$join_group_settings = bb_access_control_join_group_settings();

	if ( isset( $join_group_settings ) && is_array( $join_group_settings ) && isset( $join_group_settings['access-control-type'] ) && ! empty( $join_group_settings['access-control-type'] ) ) {

		$access_controls        = BB_Access_Control::bb_get_access_control_lists();
		$option_access_controls = $join_group_settings['access-control-type'];
		$can_create             = bb_access_control_has_access( bp_loggedin_user_id(), $access_controls, $option_access_controls, $join_group_settings );

		if ( ! $can_create ) {
			esc_html_e( 'You don\'t have access to send request membership.', 'buddyboss-pro' );
			add_filter( 'bp_nouveau_user_feedback_template', 'bb_access_control_user_feedback_template' );
		}
	}
}

/**
 * Check user have access to send request membership.
 *
 * @since 1.1.0
 */
function bb_access_control_after_has_group_request_access_control_access() {

	$join_group_settings = bb_access_control_join_group_settings();

	if ( isset( $join_group_settings ) && is_array( $join_group_settings ) && isset( $join_group_settings['access-control-type'] ) && ! empty( $join_group_settings['access-control-type'] ) ) {

		$access_controls        = BB_Access_Control::bb_get_access_control_lists();
		$option_access_controls = $join_group_settings['access-control-type'];
		$can_create             = bb_access_control_has_access( bp_loggedin_user_id(), $access_controls, $option_access_controls, $join_group_settings );

		if ( ! $can_create ) {
			remove_filter( 'bp_nouveau_user_feedback_template', 'bb_access_control_user_feedback_template' );
		}
	}
}

/**
 * Check user can see the friend request button.
 *
 * @param string $button HTML markup for add friend button.
 *
 * @since 1.1.0
 *
 * @return BP_Button $button Request buttons.
 */
function bb_access_control_user_can_send_friend_request( $button ) {

	$friend_settings = bb_access_control_friends_settings();
	$has_access      = array();

	if ( empty( $button ) ) {
		return $button;
	}

	if ( is_array( $button ) && isset( $button['id'] ) && 'not_friends' !== $button['id'] ) {
		return $button;
	}

	if ( empty( $friend_settings ) || ( isset( $friend_settings['access-control-type'] ) && empty( $friend_settings['access-control-type'] ) ) ) {
		$has_access = $button;
	} elseif ( is_array( $friend_settings ) && isset( $friend_settings['access-control-type'] ) && ! empty( $friend_settings['access-control-type'] ) ) {

		$access_controls        = BB_Access_Control::bb_get_access_control_lists();
		$option_access_controls = $friend_settings['access-control-type'];
		$can_create             = bb_access_control_has_access( $button['potential_friend_id'], $access_controls, $option_access_controls, $friend_settings, true );

		if ( $can_create ) {
			$has_access = $button;
		}
	}

	/**
	 * Filter will return user can see the friend request button.
	 *
	 * @since 1.1.0
	 */
	return apply_filters( 'bb_access_control_user_can_send_friend_request', $has_access );

}

/**
 * Check user can see the friend request button.
 *
 * @param array $buttons The list of buttons.
 *
 * @since 1.1.0
 *
 * @return array $buttons The list of buttons.
 */
function bb_access_control_member_header_user_can_send_friend_request( $buttons ) {

	$friend_settings = bb_access_control_friends_settings();
	$hac_access      = $buttons;
	if ( empty( $buttons ) ) {
		return $buttons;
	}

	if ( is_array( $buttons ) && isset( $buttons['member_friendship'] ) && isset( $buttons['member_friendship']['key'] ) && 'not_friends' !== $buttons['member_friendship']['key'] ) {
		return $buttons;
	}

	if ( empty( $friend_settings ) || ( isset( $friend_settings['access-control-type'] ) && empty( $friend_settings['access-control-type'] ) ) ) {
		$hac_access = $buttons;
	} elseif ( is_array( $friend_settings ) && isset( $friend_settings['access-control-type'] ) && ! empty( $friend_settings['access-control-type'] ) && isset( $buttons['member_friendship'] ) ) {

		$access_controls        = BB_Access_Control::bb_get_access_control_lists();
		$option_access_controls = $friend_settings['access-control-type'];
		$can_create             = bb_access_control_has_access( $buttons['member_friendship']['potential_friend_id'], $access_controls, $option_access_controls, $friend_settings, true );

		if ( $can_create ) {
			$hac_access = $buttons;
		} else {
			unset( $buttons['member_friendship'] );
			$hac_access = $buttons;
		}
	}

	/**
	 * Filter will return user can see the friend request button.
	 *
	 * @since 1.1.0
	 */
	return apply_filters( 'bb_access_control_member_header_user_can_send_friend_request', $hac_access );

}

/**
 * Check user can see the send message button.
 *
 * @param array $buttons The list of buttons.
 *
 * @since 1.1.0
 *
 * @return array $buttons The list of buttons.
 */
function bb_access_control_member_header_user_can_send_message_request( $buttons ) {

	$message_settings = bb_access_control_send_messages_settings();

	if ( empty( $buttons ) ) {
		return $buttons;
	}

	// Bail if user is not logged in.
	if ( ! is_user_logged_in() ) {
		return $buttons;
	}

	if ( function_exists( 'bp_is_my_profile' ) && bp_is_my_profile() ) {
		return $buttons;
	}

	if ( is_array( $buttons ) && isset( $buttons['private_message'] ) && isset( $buttons['private_message']['key'] ) && 'private_message' !== $buttons['private_message']['id'] ) {
		return $buttons;
	}

	$has_access = $buttons;

	if ( empty( $message_settings ) || ( isset( $message_settings['access-control-type'] ) && empty( $message_settings['access-control-type'] ) ) ) {
		$has_access = $buttons;
	} elseif ( is_array( $message_settings ) && isset( $message_settings['access-control-type'] ) && ! empty( $message_settings['access-control-type'] ) && isset( $buttons['private_message'] ) ) {

		$access_controls        = BB_Access_Control::bb_get_access_control_lists();
		$option_access_controls = $message_settings['access-control-type'];
		$can_create             = bb_access_control_has_access( $buttons['private_message']['message_receiver_user_id'], $access_controls, $option_access_controls, $message_settings, true );

		if ( $can_create ) {
			$has_access = $buttons;
		} else {
			$buttons['private_message'] = array();
			$has_access                 = $buttons;
		}
	}

	/**
	 * Filter which will return the access buttons.
	 *
	 * @since 1.1.0
	 */
	return apply_filters( 'bb_access_control_member_header_user_can_send_message_request', $has_access );

}

/**
 * Function will send the error message if user don't have access to send a message.
 *
 * @param object $thread     message thread.
 * @param array  $recipients thread recipients.
 * @param string $error_type Return error type.
 *
 * @since 1.1.0
 *
 * @return object $thread thread object.
 */
function bb_access_control_member_can_send_message( $thread, $recipients, $error_type ) {

	$message_settings = bb_access_control_send_messages_settings();

	if ( empty( $message_settings ) || ( isset( $message_settings['access-control-type'] ) && empty( $message_settings['access-control-type'] ) ) ) {
		if ( '' === $error_type ) {
			return $thread;
		} elseif ( 'wp_error' === $error_type ) {
			return '';
		}
	} elseif ( is_array( $message_settings ) && isset( $message_settings['access-control-type'] ) && ! empty( $message_settings['access-control-type'] ) ) {

		$un_access_users        = array();
		$access_controls        = BB_Access_Control::bb_get_access_control_lists();
		$option_access_controls = $message_settings['access-control-type'];

		// Strip the sender from the recipient list, and unset them if they are
		// not alone. If they are alone, let them talk to themselves.
		if ( isset( $recipients[ bp_loggedin_user_id() ] ) && ( count( $recipients ) > 1 ) ) {
			unset( $recipients[ bp_loggedin_user_id() ] );
		}

		foreach ( $recipients as $recipient ) {
			$user_id = (int) ( is_int( $recipient ) ? $recipient : ( $recipient->user_id ?? 0 ) );
			if ( bp_loggedin_user_id() !== $user_id && 0 !== $user_id ) {
				$can_create = bb_access_control_has_access( $user_id, $access_controls, $option_access_controls, $message_settings, true );
				if ( ! $can_create ) {
					$un_access_users[] = bp_core_get_user_displayname( $user_id );
				}
			}
		}

		if ( empty( $un_access_users ) ) {
			if ( '' === $error_type ) {
				return $thread;
			} elseif ( 'wp_error' === $error_type ) {
				return '';
			}
		} else {
			$error = __( 'You are restricted from sending new messages to this member.', 'buddyboss-pro' );
			if ( count( $un_access_users ) > 1 ) {
				$error = __( 'You are restricted from sending new messages to these members: ', 'buddyboss-pro' ) . implode( ', ', $un_access_users );
				if ( count( $un_access_users ) > 3 ) {
					$error = __( 'You are restricted from sending new messages to these members: ', 'buddyboss-pro' ) . implode( ', ', array_slice( $un_access_users, -3 ) ) . __( '...', 'buddyboss-pro' );
				}
			}
			if ( '' === $error_type ) {
				if ( 'boolean' !== gettype( $thread ) ) {
					$thread->feedback_error = array(
						'feedback' => $error,
						'type'     => 'info',
						'from'     => 'access-control',
					);
				}

				return $thread;
			} elseif ( 'wp_error' === $error_type ) {
				return new WP_Error( 'message_generic_error', $error );
			}
		}
	}

	return $thread;
}

/**
 * Check whether users have access to send message in thread or not.
 *
 * @param int   $default    Default value.
 * @param int   $thread     The thread id.
 * @param array $recipients list of users to send message.
 *
 * @since 1.1.0
 *
 * @return bool
 */
function bb_access_control_bb_can_user_send_message_in_thread( $default, $thread, $recipients ) {

	$message_settings = bb_access_control_send_messages_settings();

	if ( empty( $message_settings ) || ( isset( $message_settings['access-control-type'] ) && empty( $message_settings['access-control-type'] ) ) ) {
		return $default;
	} elseif ( is_array( $message_settings ) && isset( $message_settings['access-control-type'] ) && ! empty( $message_settings['access-control-type'] ) ) {

		$un_access_users        = array();
		$access_controls        = BB_Access_Control::bb_get_access_control_lists();
		$option_access_controls = $message_settings['access-control-type'];

		// Strip the sender from the recipient list, and unset them if they are
		// not alone. If they are alone, let them talk to themselves.
		if ( isset( $recipients[ bp_loggedin_user_id() ] ) && ( count( $recipients ) > 1 ) ) {
			unset( $recipients[ bp_loggedin_user_id() ] );
		}

		foreach ( $recipients as $recipient ) {
			if ( bp_loggedin_user_id() !== $recipient->user_id ) {
				$can_create = bb_access_control_has_access( $recipient->user_id, $access_controls, $option_access_controls, $message_settings, true );
				if ( ! $can_create ) {
					$un_access_users[] = bp_core_get_user_displayname( $recipient->user_id );
				}
			}
		}

		if ( empty( $un_access_users ) ) {
			return $default;
		} else {
			return false;
		}
	}

	return false;
}

/**
 * Function which return user can select to send the private group message.
 *
 * @param bool $has_access        Whether user has access to receive the group private messsage.
 * @param int  $reciver_member_id Receiver member id.
 * @param int  $current_user_id   Current logged in user id.
 *
 * @since 1.1.0
 *
 * @return bool|mixed
 */
function bb_access_control_user_can_send_group_message( $has_access, $reciver_member_id, $current_user_id ) {

	$message_settings = bb_access_control_send_messages_settings();

	if ( empty( $message_settings ) || ( isset( $message_settings['access-control-type'] ) && empty( $message_settings['access-control-type'] ) ) ) {
		return $has_access;
	} elseif ( is_array( $message_settings ) && isset( $message_settings['access-control-type'] ) && ! empty( $message_settings['access-control-type'] ) ) {

		$access_controls        = BB_Access_Control::bb_get_access_control_lists();
		$option_access_controls = $message_settings['access-control-type'];
		$has_access             = bb_access_control_has_access( $reciver_member_id, $access_controls, $option_access_controls, $message_settings, true );

	}

	return $has_access;
}

/**
 * Function will show the recipients only user have access to send message.
 *
 * @param array $results thread recipients.
 *
 * @since 1.1.0
 *
 * @return array $thread thread object.
 */
function bb_access_control_member_can_send_message_search_recipients( $results ) {

	$message_settings = bb_access_control_send_messages_settings();

	if ( empty( $message_settings ) || ( isset( $message_settings['access-control-type'] ) && empty( $message_settings['access-control-type'] ) ) ) {
		return $results;
	} elseif ( is_array( $message_settings ) && isset( $message_settings['access-control-type'] ) && ! empty( $message_settings['access-control-type'] ) ) {

		$access_controls        = BB_Access_Control::bb_get_access_control_lists();
		$option_access_controls = $message_settings['access-control-type'];

		foreach ( $results as $key => $recipient ) {

			$can_create = bb_access_control_has_access( $recipient->user_id, $access_controls, $option_access_controls, $message_settings, true );
			if ( ! $can_create ) {
				unset( $results[ $key ] );
			}
		}
	}

	$results = array_values( array_filter( $results ) );

	return $results;
}

/**
 * Check user can see the message button.
 *
 * @param string    $button_contents Button context to be used.
 * @param array     $args            Array of args for the button.
 * @param BP_Button $button          BP_Button object.
 *
 * @since 1.1.0
 *
 * @return BP_Button $button BP_Button object.
 */
function bb_access_control_member_can_see_send_message( $button_contents, $args, $button ) {

	$message_settings = bb_access_control_send_messages_settings();

	if ( empty( $button ) ) {
		return $button_contents;
	}

	if ( is_object( $button ) && isset( $button->id ) && 'private_message' !== $button->id ) {
		return $button_contents;
	}

	$has_access = '';

	if ( empty( $message_settings ) || ( isset( $message_settings['access-control-type'] ) && empty( $message_settings['access-control-type'] ) ) ) {
		$has_access = $button_contents;
	} elseif ( is_array( $message_settings ) && isset( $message_settings['access-control-type'] ) && ! empty( $message_settings['access-control-type'] ) ) {

		$access_controls        = BB_Access_Control::bb_get_access_control_lists();
		$option_access_controls = $message_settings['access-control-type'];
		$can_create             = bb_access_control_has_access( $args['message_receiver_user_id'], $access_controls, $option_access_controls, $message_settings, true );

		if ( $can_create ) {
			$has_access = $button_contents;
		}
	}

	/**
	 * Filter which check user can see the message button.
	 *
	 * @since 1.1.0
	 */
	return apply_filters( 'bb_access_control_member_can_see_send_message', $has_access, $args, $button );

}

/**
 * Check user can see the edit activity button.
 *
 * @param array $buttons     The list of buttons.
 * @param int   $activity_id The current activity ID.
 *
 * @since 1.1.0
 *
 * @return BP_Button $button BP_Button object.
 */
function bb_access_control_member_can_edit_activity( $buttons, $activity_id ) {
	global $activities_template;
	if (
		bp_is_group_single() ||
		bp_is_group_activity() ||
		( bp_is_single_activity() && ! empty( $activities_template->activity->component ) && 'groups' === $activities_template->activity->component )
	) {
		return $buttons;
	}

	if ( 'groups' === $activities_template->activity->component ) {
		return $buttons;
	}

	$create_activity_settings = bb_access_control_create_activity_settings();
	$has_access               = $buttons;
	if ( empty( $create_activity_settings ) || ( isset( $create_activity_settings['access-control-type'] ) && empty( $create_activity_settings['access-control-type'] ) ) ) {
		$has_access = $buttons;
	} elseif ( is_array( $create_activity_settings ) && isset( $create_activity_settings['access-control-type'] ) && ! empty( $create_activity_settings['access-control-type'] ) ) {

		$access_controls        = BB_Access_Control::bb_get_access_control_lists();
		$option_access_controls = $create_activity_settings['access-control-type'];
		$can_create             = bb_access_control_has_access( bp_loggedin_user_id(), $access_controls, $option_access_controls, $create_activity_settings );

		if ( ! $can_create ) {
			unset( $buttons['activity_edit'] );
			if (
				empty( $activities_template->activity->component ) ||
				'groups' !== $activities_template->activity->component
			) {
				unset( $buttons['activity_delete'] );
			}
			$has_access = $buttons;
		}
	}

	/**
	 * Filter which return the buttons which has access of edit activity.
	 *
	 * @since 1.1.0
	 */
	return apply_filters( 'bb_access_control_member_can_edit_activity', $has_access, $activity_id );

}

/**
 * Restrict to send invites based on the group join settings.
 *
 * @param array $requests request parameter of invites.
 *
 * @since 1.1.0
 *
 * @return array|object|string
 */
function bb_access_control_groups_potential_invites( $requests ) {

	global $wpdb;

	$join_group_settings = bb_access_control_join_group_settings();
	$user_ids            = array();

	if ( empty( $join_group_settings ) || ( isset( $join_group_settings['access-control-type'] ) && empty( $join_group_settings['access-control-type'] ) ) ) {
		return $requests;
	} elseif ( is_array( $join_group_settings ) && isset( $join_group_settings['access-control-type'] ) && ! empty( $join_group_settings['access-control-type'] ) ) {

		$access_controls        = BB_Access_Control::bb_get_access_control_lists();
		$option_access_controls = $join_group_settings['access-control-type'];

		if ( 'membership' === $option_access_controls && ! empty( $join_group_settings['access-control-options'] ) ) {

			if ( 'memberpress' === $join_group_settings['plugin-access-control-type'] && $access_controls[ $option_access_controls ]['is_enabled'] ) {
				foreach ( $join_group_settings['access-control-options'] as $level ) {

					$access_control_id = (int) $level;
					$offset            = get_option( 'gmt_offset' );
					$expires_at_select = $wpdb->prepare( 'DATE_ADD(max(expires_at), INTERVAL %d HOUR)', $offset );
					$current_time_gmt  = current_time( 'mysql', 1 );
					$capabilities      = $wpdb->get_blog_prefix() . 'capabilities';

					$created_at_select = $wpdb->prepare( 'DATE_ADD(max(created_at), INTERVAL %d HOUR)', $offset );

					$allowed_statuses = array_keys( get_post_stati( array( 'exclude_from_search' => false ) ) );

					$new_arr = array();
					foreach ( $allowed_statuses as $value ) {
						$new_arr [] = "'" . $value . "'";
					}

					$allowed_statuses = implode( ',', $new_arr );

					$allowed_post_types = array_keys( get_post_types() );
					$exclude_post_types = array( 'revision', 'attachment', 'nav_menu_item' );

					$allowed_post_types = array_merge( array_diff( $allowed_post_types, $exclude_post_types ) );

					$new_arr = array();
					foreach ( $allowed_post_types as $value ) {
						$new_arr [] = "'" . $value . "'";
					}

					$allowed_post_types = implode( ',', $new_arr );

					$sql_query = "SELECT 
								{$wpdb->users}.ID, 
								{$wpdb->users}.user_login AS `username`, 
								{$wpdb->users}.user_email AS `email`, 
								{$wpdb->users}.user_registered AS `registered`, 
								COUNT( 
									DISTINCT {$wpdb->posts}.ID) AS `posts`, 
									
									mepr_members.total_spent AS `mepr_ltv`, 
									
								COUNT(DISTINCT mepr_transactions.id) AS `mepr_transaction_count` 
								
								FROM {$wpdb->users} 
								
								INNER JOIN (
								
									SELECT id, user_id, product_id, subscription_id, {$created_at_select} AS created_at_local,
								
								CASE 
									WHEN MIN(expires_at) = '0000-00-00 00:00:00' 
									THEN MIN(expires_at) 
									ELSE {$expires_at_select}
									END AS expires_at_local,

								COUNT(DISTINCT CASE 
												WHEN (expires_at = '0000-00-00 00:00:00' OR expires_at > '{$current_time_gmt}') THEN 'active' END ) AS is_active
								
								FROM {$wpdb->prefix}mepr_transactions
				
								WHERE `status` IN ('confirmed','complete')
								
								GROUP BY user_id, product_id, subscription_id) AS mepr_memberships_0 
								
								ON mepr_memberships_0.user_id={$wpdb->users}.ID LEFT JOIN {$wpdb->usermeta} AS role_meta 
								ON ({$wpdb->users}.ID = role_meta.user_id AND role_meta.meta_key = '{$capabilities}') 
								
								LEFT JOIN 
								
									{$wpdb->posts} on {$wpdb->users}.ID = {$wpdb->posts}.post_author
									
								AND 
								
								{$wpdb->posts}.post_status IN 
								({$allowed_statuses}) 
								
								AND {$wpdb->posts}.post_type IN ({$allowed_post_types}) 
								
								LEFT JOIN {$wpdb->prefix}mepr_members AS mepr_members ON mepr_members.user_id={$wpdb->users}.ID 
								
								LEFT JOIN (SELECT id, user_id, `status`, product_id, total, {$created_at_select} AS created_at_local, coupon_id,
								
								CASE 
									WHEN expires_at = '0000-00-00 00:00:00' 
									THEN expires_at 
									ELSE {$expires_at_select} 
									END AS expires_at_local
								
								FROM {$wpdb->prefix}mepr_transactions AS t 
								
								WHERE txn_type = 'payment' 
								
									AND `status` != 'confirmed' 
									
									ORDER BY created_at DESC) AS mepr_transactions 
									
									ON mepr_transactions.user_id={$wpdb->users}.ID 
									
								WHERE 1=1 
								
									AND mepr_memberships_0.product_id = {$access_control_id} 
									AND mepr_memberships_0.is_active = 1 
									
								GROUP BY {$wpdb->users}.ID 
								
								ORDER BY {$wpdb->users}.user_registered DESC, {$wpdb->users}.user_login ASC";

					$sql_table_data = $wpdb->get_results( $sql_query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared

					if ( $sql_table_data ) {
						$results    = wp_list_pluck( $sql_table_data, 'ID' );
						$user_ids[] = $results;
					}
				}

				$admins   = get_users(
					array(
						'fields' => 'ids',
						'role'   => 'administrator',
					)
				);
				$user_ids = array_merge( $user_ids, $admins );

				if ( $user_ids ) {
					$user_ids = array_unique( bb_access_control_array_flatten( $user_ids ) );
					$user_ids = implode( ',', $user_ids );

					return wp_parse_args(
						$requests,
						array(
							'include' => $user_ids,
						)
					);
				}

				return wp_parse_args(
					$requests,
					array(
						'include' => PHP_INT_MAX,
					)
				);
			} elseif ( 'memberium' === $join_group_settings['plugin-access-control-type'] && $access_controls[ $option_access_controls ]['is_enabled'] ) {
				if ( class_exists( 'm4is_cf4q' ) ) {
					$m4is_spk  = MEMBERIUM_DB_CONTACTS;
					$m4is_lc7m = m4is_cf4q::m4is_kmvn( 'appname' );
					$m4is_k4yu = '';
					if ( ! empty( $join_group_settings['access-control-options'] ) ) {
						foreach ( $join_group_settings['access-control-options'] as $level ) {
							$m4is_b6yk = "SELECT DISTINCT `id` FROM `{$m4is_spk}` WHERE `appname` = '{$m4is_lc7m}' AND `value` = %s ORDER BY `id` ASC;";
							$m4is_b6yk = $wpdb->prepare( $m4is_b6yk, $level ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
							$m4is_u_z5 = $wpdb->get_results( $m4is_b6yk ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
							if ( $m4is_u_z5 ) {
								$m4is_u_z5 = wp_list_pluck( $m4is_u_z5, 'id' );
								if ( $m4is_u_z5 ) {
									$implode_user_id = implode( ',', $m4is_u_z5 );
									$m4is_b6yk       = "SELECT `user_id` FROM `{$wpdb->usermeta}` WHERE `meta_key` = 'infusionsoft_user_id' AND `meta_value` IN ({$implode_user_id}) ";
									$m4is_k4yu       = $wpdb->get_results( $m4is_b6yk ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
								}
							}
						}

						if ( $m4is_k4yu ) {
							$results    = wp_list_pluck( $m4is_k4yu, 'user_id' );
							$user_ids[] = $results;
						}

						$admins   = get_users(
							array(
								'fields' => 'ids',
								'role'   => 'administrator',
							)
						);
						$user_ids = array_merge( $user_ids, $admins );

						if ( $user_ids ) {
							$user_ids = array_unique( bb_access_control_array_flatten( $user_ids ) );
							$user_ids = implode( ',', $user_ids );

							return wp_parse_args(
								$requests,
								array(
									'include' => $user_ids,
								)
							);
						}

						return wp_parse_args(
							$requests,
							array(
								'include' => PHP_INT_MAX,
							)
						);

					}
				}

				return $requests;
			} elseif ( 'pm-pro-membership' === $join_group_settings['plugin-access-control-type'] && $access_controls[ $option_access_controls ]['is_enabled'] ) {
				foreach ( $join_group_settings['access-control-options'] as $level ) {

					$sql_query =
						"
				SELECT u.ID, u.user_login, u.user_email, u.display_name,
				UNIX_TIMESTAMP(CONVERT_TZ(u.user_registered, '+00:00', @@global.time_zone)) as joindate, mu.membership_id, mu.initial_payment, mu.billing_amount, SUM(mu.initial_payment+ mu.billing_amount) as fee, mu.cycle_period, mu.cycle_number, mu.billing_limit, mu.trial_amount, mu.trial_limit,
				UNIX_TIMESTAMP(CONVERT_TZ(mu.startdate, '+00:00', @@global.time_zone)) as startdate,
				UNIX_TIMESTAMP(CONVERT_TZ(max(mu.enddate), '+00:00', @@global.time_zone)) as enddate, m.name as membership
				";

					$sql_query .=
						"	
			FROM $wpdb->users u 
			LEFT JOIN $wpdb->pmpro_memberships_users mu
			ON u.ID = mu.user_id
			LEFT JOIN $wpdb->pmpro_membership_levels m
			ON mu.membership_id = m.id
			";

					$sql_query .= ' WHERE mu.membership_id > 0 ';

					$sql_query .= " AND mu.status = 'active' AND mu.membership_id = '" . esc_sql( $level ) . "' ";

					$sql_query .= ' GROUP BY u.ID ';

					$sql_table_data = $wpdb->get_results( $sql_query ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared

					if ( $sql_table_data ) {
						$results    = wp_list_pluck( $sql_table_data, 'ID' );
						$user_ids[] = $results;
					}
				}

				$admins   = get_users(
					array(
						'fields' => 'ids',
						'role'   => 'administrator',
					)
				);
				$user_ids = array_merge( $user_ids, $admins );

				if ( $user_ids ) {
					$user_ids = array_unique( bb_access_control_array_flatten( $user_ids ) );
					$user_ids = implode( ',', $user_ids );

					return wp_parse_args(
						$requests,
						array(
							'include' => $user_ids,
						)
					);
				}

				return wp_parse_args(
					$requests,
					array(
						'include' => PHP_INT_MAX,
					)
				);
			} elseif ( 'restrict-content-pro' === $join_group_settings['plugin-access-control-type'] && $access_controls[ $option_access_controls ]['is_enabled'] && function_exists( 'rcp_get_memberships' ) ) {
				foreach ( $join_group_settings['access-control-options'] as $level ) {
					$args    = array(
						'number'    => PHP_INT_MAX,
						'object_id' => $level,
						'status'    => 'active',
					);
					$results = rcp_get_memberships( $args );
					foreach ( $results as $access_control ) {
						$user_ids[] = $access_control->get_user_id();
					}
				}

				$admins   = get_users(
					array(
						'fields' => 'ids',
						'role'   => 'administrator',
					)
				);
				$user_ids = array_merge( $user_ids, $admins );

				if ( $user_ids ) {
					$user_ids = array_unique( bb_access_control_array_flatten( $user_ids ) );
					$user_ids = implode( ',', $user_ids );

					return wp_parse_args(
						$requests,
						array(
							'include' => $user_ids,
						)
					);
				}

				return wp_parse_args(
					$requests,
					array(
						'include' => PHP_INT_MAX,
					)
				);
			} elseif ( 'lifter' === $join_group_settings['plugin-access-control-type'] && $access_controls[ $option_access_controls ]['is_enabled'] && function_exists( 'llms_is_user_enrolled' ) ) {

				foreach ( $join_group_settings['access-control-options'] as $level ) {
					$membership_id = (int) $level;
					$results       = $wpdb->get_results( "SELECT u.ID FROM {$wpdb->users} AS u JOIN {$wpdb->prefix}lifterlms_user_postmeta AS m ON m.user_id = u.ID AND m.meta_key = '_status' AND m.meta_value = 'enrolled' AND m.post_id ={$membership_id}" ); // phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
					if ( $results ) {
						$results    = wp_list_pluck( $results, 'ID' );
						$user_ids[] = $results;
					}
				}

				$admins   = get_users(
					array(
						'fields' => 'ids',
						'role'   => 'administrator',
					)
				);
				$user_ids = array_merge( $user_ids, $admins );

				if ( $user_ids ) {
					$user_ids = array_unique( bb_access_control_array_flatten( $user_ids ) );
					$user_ids = implode( ',', $user_ids );

					return wp_parse_args(
						$requests,
						array(
							'include' => $user_ids,
						)
					);
				}
				return wp_parse_args(
					$requests,
					array(
						'include' => PHP_INT_MAX,
					)
				);
			} elseif ( 'learndash' === $join_group_settings['plugin-access-control-type'] && $access_controls[ $option_access_controls ]['is_enabled'] && function_exists( 'learndash_is_user_in_group' ) ) {

				foreach ( $join_group_settings['access-control-options'] as $level ) {
					$user_ids[] = learndash_get_groups_user_ids( $level );
				}

				$admins   = get_users(
					array(
						'fields' => 'ids',
						'role'   => 'administrator',
					)
				);
				$user_ids = array_merge( $user_ids, $admins );

				if ( $user_ids ) {
					$user_ids = array_unique( bb_access_control_array_flatten( $user_ids ) );
					$user_ids = implode( ',', $user_ids );

					return wp_parse_args(
						$requests,
						array(
							'include' => $user_ids,
						)
					);
				}

				return wp_parse_args(
					$requests,
					array(
						'include' => PHP_INT_MAX,
					)
				);
			} elseif ( 's2member' === $join_group_settings['plugin-access-control-type'] && $access_controls[ $option_access_controls ]['is_enabled'] ) {
				foreach ( $join_group_settings['access-control-options'] as $level ) {

					$args = array(
						'order'          => 'DESC',
						'orderby'        => 'registered',
						'number'         => PHP_INT_MAX,
						'blog_id'        => $GLOBALS['blog_id'],
						'search'         => '',
						'search_columns' => array(),
						'include'        => array(),
						'exclude'        => array(),
					);

					$args['meta_query'][] = array(
						'key'     => $wpdb->get_blog_prefix() . 'capabilities',
						'value'   => 0 === (int) $level ? '"subscriber"' : '"s2member_level' . $level . '"',
						'compare' => 'LIKE',
					);

					$member_list_query = c_ws_plugin__s2member_pro_member_list::query( $args );
					$query             = $member_list_query['query'];
					$users             = $query->get_results();
					if ( $users ) {
						$results    = wp_list_pluck( $users, 'ID' );
						$user_ids[] = $results;
					}
				}

				$admins   = get_users(
					array(
						'fields' => 'ids',
						'role'   => 'administrator',
					)
				);
				$user_ids = array_merge( $user_ids, $admins );

				if ( $user_ids ) {
					$user_ids = array_unique( bb_access_control_array_flatten( $user_ids ) );
					$user_ids = implode( ',', $user_ids );

					return wp_parse_args(
						$requests,
						array(
							'include' => $user_ids,
						)
					);
				}

				return wp_parse_args(
					$requests,
					array(
						'include' => PHP_INT_MAX,
					)
				);
			} elseif ( 'woo-membership' === $join_group_settings['plugin-access-control-type'] && $access_controls[ $option_access_controls ]['is_enabled'] ) {
				foreach ( $join_group_settings['access-control-options'] as $level ) {
					$results = $wpdb->get_results( "SELECT DISTINCT um.user_id, u.user_email, u.display_name, p2.post_title, p2.post_type FROM {$wpdb->prefix}posts AS p LEFT JOIN {$wpdb->prefix}posts AS p2 ON p2.ID = p.post_parent LEFT JOIN {$wpdb->prefix}users AS u ON u.id = p.post_author LEFT JOIN {$wpdb->prefix}usermeta AS um ON u.id = um.user_id WHERE p.post_type = 'wc_user_membership' AND p.post_status IN ('wcm-active') AND p2.post_type = 'wc_membership_plan' AND p.post_parent = {$level}" ); // phpcs:ignore
					if ( $results ) {
						$results    = wp_list_pluck( $results, 'user_id' );
						$user_ids[] = $results;
					}
				}

				$admins   = get_users(
					array(
						'fields' => 'ids',
						'role'   => 'administrator',
					)
				);
				$user_ids = array_merge( $user_ids, $admins );

				if ( $user_ids ) {
					$user_ids = array_unique( bb_access_control_array_flatten( $user_ids ) );
					$user_ids = implode( ',', $user_ids );

					return wp_parse_args(
						$requests,
						array(
							'include' => $user_ids,
						)
					);
				}

				return wp_parse_args(
					$requests,
					array(
						'include' => PHP_INT_MAX,
					)
				);
			} elseif ( 'wishlist-member' === $join_group_settings['plugin-access-control-type'] && $access_controls[ $option_access_controls ]['is_enabled'] ) {
				foreach ( $join_group_settings['access-control-options'] as $level ) {
					global $WishListMemberInstance; // phpcs:ignore
					if ( $WishListMemberInstance ) { // phpcs:ignore
						$active_ids = $WishListMemberInstance->ActiveMemberIDs( $level, false, false ); // phpcs:ignore
					}
					if ( ! empty( $active_ids ) ) {
						$user_ids[] = $active_ids;
					}

					$admins   = get_users(
						array(
							'fields' => 'ids',
							'role'   => 'administrator',
						)
					);
					$user_ids = array_merge( $user_ids, $admins );

					if ( $user_ids ) {
						$user_ids = array_unique( bb_access_control_array_flatten( $user_ids ) );
						$user_ids = implode( ',', $user_ids );

						return wp_parse_args(
							$requests,
							array(
								'include' => $user_ids,
							)
						);
					}

					return wp_parse_args(
						$requests,
						array(
							'include' => PHP_INT_MAX,
						)
					);
				}
			}
		} elseif ( 'gamipress' === $option_access_controls && $access_controls[ $option_access_controls ]['is_enabled'] ) {
			if ( ! empty( $join_group_settings['access-control-options'] ) ) {
				foreach ( $join_group_settings['access-control-options'] as $level ) {
					if ( 'achievement' === $join_group_settings['gamipress-access-control-type'] ) {
						$get_earners = gamipress_get_achievement_earners( $level );
					} else {
						$get_earners = gamipress_get_rank_earners( $level );
					}
					if ( ! empty( $get_earners ) ) {
						$user_ids[] = wp_list_pluck( $get_earners, 'ID' );
					}
				}

				$admins   = get_users(
					array(
						'fields' => 'ids',
						'role'   => 'administrator',
					)
				);
				$user_ids = array_merge( $user_ids, $admins );

				if ( $user_ids ) {
					$user_ids = array_unique( bb_access_control_array_flatten( $user_ids ) );
					$user_ids = implode( ',', $user_ids );

					return wp_parse_args(
						$requests,
						array(
							'include' => $user_ids,
						)
					);
				}

				return wp_parse_args(
					$requests,
					array(
						'include' => PHP_INT_MAX,
					)
				);
			}
		} else {
			if ( isset( $access_controls ) && isset( $access_controls[ $option_access_controls ] ) && $access_controls[ $option_access_controls ]['is_enabled'] && ! empty( $join_group_settings['access-control-options'] ) ) {
				if ( 'wp_role' === $option_access_controls ) {
					$args    = array(
						'fields'   => array( 'ID' ),
						'role__in' => $join_group_settings['access-control-options'],
					);
					$user_id = get_users( $args );
					if ( $user_id ) {
						$user_ids[] = wp_list_pluck( $user_id, 'ID' );
					}
				} elseif ( 'gender' === $option_access_controls ) {
					$get_gender_field_id = bp_get_xprofile_gender_type_field_id();
					if ( $get_gender_field_id ) {
						foreach ( $join_group_settings['access-control-options'] as $option ) {
							$user_id = bp_xprofile_get_users_by_field_value( $get_gender_field_id, $option );
							if ( $user_id ) {
								$user_ids[] = wp_list_pluck( $user_id, 'ID' );
							}
						}
					}
				} elseif ( 'bp_member_type' === $option_access_controls ) {
					return wp_parse_args(
						$requests,
						array(
							'member_type' => implode( ',', $join_group_settings['access-control-options'] ),
						)
					);
				}

				$admins   = get_users(
					array(
						'fields' => 'ids',
						'role'   => 'administrator',
					)
				);
				$user_ids = array_merge( $user_ids, $admins );

				if ( ! empty( $user_ids ) ) {
					$user_ids = bb_access_control_array_flatten( $user_ids );
					$user_ids = implode( ',', $user_ids );

					return wp_parse_args(
						$requests,
						array(
							'include' => $user_ids,
						)
					);
				} else {
					return wp_parse_args(
						$requests,
						array(
							'include' => PHP_INT_MAX,
						)
					);
				}
			}
		}
	}

	return $requests;
}

/**
 * Remove feedback template for the group membership.
 *
 * @param string $template_path Template path to show feedback.
 *
 * @since 1.1.0
 *
 * @return string
 */
function bb_access_control_user_feedback_template( $template_path ) {
	return '';
}
