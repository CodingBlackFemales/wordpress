<?php
/**
 * Membership Rest filters.
 *
 * @package BuddyBossPro
 *
 * @since   1.1.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_action( 'bb_rest_before_get_group_members', 'bb_access_control_rest_before_get_group_members', PHP_INT_MAX, 2 );

// Activity Support.
add_filter( 'bp_rest_activity_create_item_permissions_check', 'bb_access_control_rest_activity_item_permissions', PHP_INT_MAX, 2 );
add_filter( 'bp_rest_activity_update_item_permissions_check', 'bb_access_control_rest_activity_item_single_permissions', PHP_INT_MAX, 2 );
add_filter( 'bp_rest_activity_delete_item_permissions_check', 'bb_access_control_rest_activity_item_single_permissions', PHP_INT_MAX, 2 );
add_filter( 'bp_rest_activity_prepare_value', 'bb_access_control_rest_activity_prepare_value', PHP_INT_MAX, 3 );

// Group Support.
add_filter( 'bp_rest_groups_prepare_value', 'bb_access_control_rest_groups_prepare_value', PHP_INT_MAX, 3 );
add_filter( 'bp_rest_group_members_create_item_permissions_check', 'bb_access_control_rest_group_access_control_create_check', PHP_INT_MAX, 2 );
add_filter( 'bp_rest_group_membership_requests_create_item_permissions_check', 'bb_access_control_rest_group_access_control_create_check', PHP_INT_MAX, 2 );
add_filter( 'bp_rest_group_membership_requests_update_item_permissions_check', 'bb_access_control_rest_group_access_control_update_check', PHP_INT_MAX, 2 );
add_filter( 'bp_rest_user_can_join_group', 'bb_access_control_rest_user_can_join_group', PHP_INT_MAX, 2 );

// Messages Support.
add_filter( 'bp_rest_messages_create_item_permissions_check', 'bb_access_control_rest_messages_create_permissions_check', PHP_INT_MAX, 2 );
add_filter( 'bp_rest_messages_group_create_item_permissions_check', 'bb_access_control_rest_group_messages_create_permissions_check', PHP_INT_MAX, 2 );

// Friend Support.
add_filter( 'bp_rest_user_can_create_friendship', 'bb_access_control_rest_user_can_create_friendship', PHP_INT_MAX, 2 );
add_filter( 'bp_rest_friends_create_item_permissions_check', 'bb_access_control_rest_friends_permissions_check', PHP_INT_MAX, 2 );

// Message support for member directory.
add_filter( 'bp_rest_user_can_show_send_message_button', 'bb_access_control_rest_user_can_show_send_message_button', PHP_INT_MAX, 2 );
/**
 * Action to apply hooks on the group member query.
 *
 * @param array           $args    An array of arguments.
 * @param WP_REST_Request $request The request sent to the API.
 *
 * @since 1.1.0
 */
function bb_access_control_rest_before_get_group_members( $args, $request ) {
	$show_all = $request->get_param( 'show-all' );

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
	add_filter(
		'bp_group_member_query_group_member_ids',
		function( $group_member_ids, $group_member_query_object ) use ( $show_all ) {

			if ( bp_is_active( 'groups' ) && empty( $show_all ) ) {

				$can_send_arr = array();

				foreach ( $group_member_ids as $member_id ) {
					$can_send_group_message = apply_filters( 'bb_user_can_send_group_message', true, $member_id, bp_loggedin_user_id() );
					if ( $can_send_group_message ) {
						$can_send_arr[] = $member_id;
					}
				}

				$group_member_ids = $can_send_arr;

			} elseif ( bp_is_active( 'groups' ) && ! empty( $show_all ) ) {
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
		},
		PHP_INT_MAX,
		2
	);
}

/**
 * Restrict user to create activity based on access control settings.
 *
 * @param bool|WP_Error   $retval  Returned value.
 * @param WP_REST_Request $request The request sent to the API.
 *
 * @since 1.1.0
 *
 * @return bool|WP_Error
 */
function bb_access_control_rest_activity_item_permissions( $retval, $request ) {
	$id        = $request->get_param( 'id' );
	$component = $request->get_param( 'component' );
	if ( ! empty( $id ) ) {
		$activity = new BP_Activity_Activity( $id );
		if ( ! empty( $activity ) ) {
			$component = $activity->component;
		}
	}

	if (
		true === $retval &&
		function_exists( 'bb_user_can_create_activity' ) &&
		! bb_user_can_create_activity() &&
		'groups' !== $component
	) {
		$url = $request->get_route();

		$message = __( 'You don\'t have enough access to create an activity.', 'buddyboss-pro' );

		if ( strpos( $url, '/comment' ) !== false ) {
			$message = __( 'You don\'t have enough access to create an activity comment.', 'buddyboss-pro' );
		}

		$retval = new WP_Error(
			'bp_rest_authorization_required',
			$message,
			array(
				'status' => rest_authorization_required_code(),
			)
		);
	}

	return $retval;
}

/**
 * Restrict user to edit/delete activity based on access control settings.
 *
 * @param bool|WP_Error   $retval  Returned value.
 * @param WP_REST_Request $request The request sent to the API.
 *
 * @since 1.1.0
 *
 * @return bool|WP_Error
 */
function bb_access_control_rest_activity_item_single_permissions( $retval, $request ) {
	$id        = $request->get_param( 'id' );
	$component = $request->get_param( 'component' );
	$activity  = new BP_Activity_Activity( $id );
	if ( ! empty( $component ) && ! empty( $activity->id ) ) {
		$activity->component = $component;
	}

	if (
		true === $retval &&
		function_exists( 'bb_user_can_create_activity' ) &&
		! bb_user_can_create_activity() &&
		isset( $activity->component ) &&
		'groups' !== $activity->component
	) {

		$method = $request->get_method();
		$url    = $request->get_route();

		$message = __( 'Sorry, You don\'t have enough access to update this activity.', 'buddyboss-pro' );

		if ( WP_REST_Server::DELETABLE === $method ) {
			$message = __( 'Sorry, you are not allowed to delete this activity.', 'buddyboss-pro' );
		}

		if ( strpos( $url, '/comment' ) !== false ) {
			$message = __( 'Sorry, You don\'t have enough access to create an activity comment.', 'buddyboss-pro' );
		}

		$retval = new WP_Error(
			'bp_rest_authorization_required',
			$message,
			array(
				'status' => rest_authorization_required_code(),
			)
		);
	}

	return $retval;
}

/**
 * Filter an activity value returned from the API.
 *
 * @param WP_REST_Response     $response The response data.
 * @param WP_REST_Request      $request  Request used to generate the response.
 * @param BP_Activity_Activity $activity The activity object.
 *
 * @since 1.1.0
 *
 * @return WP_REST_Response
 */
function bb_access_control_rest_activity_prepare_value( $response, $request, $activity ) {
	if (
		! is_user_logged_in() ||
		! function_exists( 'bb_user_can_create_activity' ) ||
		'groups' === $activity->component
	) {
		return $response;
	}

	if ( 'activity_comment' === $activity->type && ! empty( $activity->item_id ) ) {
		$parent_activity = new BP_Activity_Activity( $activity->item_id );
		if ( ! empty( $parent_activity->id ) && 'groups' === $parent_activity->component ) {
			return $response;
		}
	}

	$data     = $response->get_data();
	$user_can = bb_user_can_create_activity();

	$data['can_edit']   = ( ! empty( $user_can ) && ! empty( $data['can_edit'] ) ) ? $data['can_edit'] : false;
	$data['can_delete'] = ( ! empty( $user_can ) && bp_activity_user_can_delete( $activity ) ) ? bp_activity_user_can_delete( $activity ) : false;

	if ( isset( $data['activity_data']['can_edit_privacy'] ) || true === $data['activity_data']['can_edit_privacy'] ) {
		$data['activity_data']['can_edit_privacy'] = $user_can;
	}

	$response->set_data( $data );

	return $response;
}

/**
 * Filter a group value returned from the API.
 *
 * @param WP_REST_Response $response The response data.
 * @param WP_REST_Request  $request  Request used to generate the response.
 * @param BP_Groups_Group  $group    Group object.
 *
 * @since 1.1.0
 *
 * @return WP_REST_Response
 */
function bb_access_control_rest_groups_prepare_value( $response, $request, $group ) {
	$data = $response->get_data();

	if ( isset( $data['can_join'] ) && true === $data['can_join'] ) {
		$data['can_join'] = bb_access_control_check_user_can_join_group( $data['can_join'] );
	}

	$response->set_data( $data );

	return $response;
}

/**
 * Filter the group join/membership request `create_item` permissions check.
 *
 * @param bool|WP_Error   $retval  Returned value.
 * @param WP_REST_Request $request The request sent to the API.
 *
 * @since 1.1.0
 *
 * @return bool|WP_Error
 */
function bb_access_control_rest_group_access_control_create_check( $retval, $request ) {
	if ( true !== $retval ) {
		return $retval;
	}

	$url = $request->get_route();

	$user_id = $request->get_param( 'user_id' );
	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}

	if ( ! bb_access_control_check_user_can_join_group( true, $user_id ) ) {

		// Group join.
		if ( strpos( $url, '/members' ) !== false ) {
			$retval = new WP_Error(
				'bp_rest_group_member_failed_to_join',
				__( 'Sorry, You don\'t have enough membership to join the group.', 'buddyboss-pro' ),
				array(
					'status' => 500,
				)
			);

			// Membership request.
		} else {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, You don\'t have enough membership to create a membership request.', 'buddyboss-pro' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}
	}

	return $retval;
}

/**
 * Filter the group membership request `update_item` permissions check.
 *
 * @param bool|WP_Error   $retval  Returned value.
 * @param WP_REST_Request $request The request sent to the API.
 *
 * @since 1.1.0
 *
 * @return bool|WP_Error
 */
function bb_access_control_rest_group_access_control_update_check( $retval, $request ) {
	if ( true !== $retval ) {
		return $retval;
	}

	$group_requests = groups_get_requests( array( 'id' => $request->get_param( 'request_id' ) ) );
	if ( empty( current( $group_requests ) ) ) {
		return $retval;
	}

	$request = current( $group_requests );

	if ( ! bb_access_control_check_user_can_join_group( true, $request->user_id ) ) {
		$retval = new WP_Error(
			'bp_rest_group_member_request_cannot_update_item',
			__( 'Requested User don\'t have enough membership to approve membership requests to this group.', 'buddyboss-pro' ),
			array(
				'status' => 500,
			)
		);
	}

	return $retval;
}

/**
 * Update user permission to check its able to logged in into group or not.
 *
 * @param bool $retval  Returned value.
 * @param int  $user_id Current Logged-in User ID.
 *
 * @since 1.1.0
 *
 * @return bool
 */
function bb_access_control_rest_user_can_join_group( $retval, $user_id ) {
	$join_group_settings = bb_access_control_join_group_settings();

	if (
		false === $retval ||
		empty( $join_group_settings ) ||
		empty( $user_id ) ||
		(
			isset( $join_group_settings['access-control-type'] ) &&
			empty( $join_group_settings['access-control-type'] )
		)
	) {
		return $retval;
	}

	if (
		is_array( $join_group_settings ) &&
		isset( $join_group_settings['access-control-type'] ) &&
		! empty( $join_group_settings['access-control-type'] )
	) {
		$access_controls        = BB_Access_Control::bb_get_access_control_lists();
		$option_access_controls = $join_group_settings['access-control-type'];
		$can_create             = bb_access_control_has_access( $user_id, $access_controls, $option_access_controls, $join_group_settings );

		if ( ! $can_create ) {
			return false;
		}
	}

	return $retval;
}

/**
 * Filter the messages `create_item` permissions check.
 *
 * @param bool|WP_Error   $retval  Returned value.
 * @param WP_REST_Request $request The request sent to the API.
 *
 * @since 1.1.0
 *
 * @return bool|WP_Error
 */
function bb_access_control_rest_messages_create_permissions_check( $retval, $request ) {
	$message_settings = bb_access_control_send_messages_settings();

	$message_thread_id = $request->get_param( 'id' );
	$is_group_thread   = ! empty( $request->get_param( 'group_thread' ) ) && (bool) $request->get_param( 'group_thread' );
	$first_message     = BP_Messages_Thread::get_first_message( (int) $message_thread_id );

	if ( isset( $first_message->id ) ) {
		$message_id = $first_message->id;

		$group = (int) bp_messages_get_meta( $message_id, 'group_id', true ); // group id.

		if ( ! empty( $group ) && bp_is_active( 'groups' ) && $group > 0 ) {
			$group_thread = (int) groups_get_groupmeta( $group, 'group_message_thread' );
			if ( (int) $message_thread_id === $group_thread ) {
				$is_group_thread = true;
			}
		} elseif ( ! empty( $group ) && ! bp_is_active( 'groups' ) && $group > 0 ) {
			global $wpdb;
			$prefix            = apply_filters( 'bp_core_get_table_prefix', $wpdb->base_prefix );
			$groups_meta_table = $prefix . 'bp_groups_groupmeta';
			$thread_id         = (int) $wpdb->get_var( $wpdb->prepare( "SELECT meta_value FROM {$groups_meta_table} WHERE meta_key = %s AND group_id = %d", 'group_message_thread', $group ) ); // db call ok; no-cache ok.
			if ( (int) $message_thread_id === $thread_id ) {
				$is_group_thread = true;
			}
		}
	}

	if (
		true !== $retval ||
		empty( $message_settings ) ||
		(
			isset( $message_settings['access-control-type'] ) &&
			empty( $message_settings['access-control-type'] )
		) ||
		$is_group_thread
	) {
		return $retval;
	}

	$recipients = (array) $request->get_param( 'recipients' );
	$recipients = wp_parse_id_list( $recipients );

	if ( is_array( $message_settings ) && isset( $message_settings['access-control-type'] ) && ! empty( $message_settings['access-control-type'] ) ) {
		$un_access_users        = array();
		$access_controls        = BB_Access_Control::bb_get_access_control_lists();
		$option_access_controls = $message_settings['access-control-type'];

		// Strip the sender from the recipient list, and unset them if they are
		// not alone. If they are alone, let them talk to themselves.
		if ( isset( $recipients[ bp_loggedin_user_id() ] ) && ( count( $recipients ) > 1 ) ) {
			unset( $recipients[ bp_loggedin_user_id() ] );
		}

		foreach ( $recipients as $recipient ) {
			if ( bp_loggedin_user_id() !== $recipient ) {
				$can_create = bb_access_control_has_access( $recipient, $access_controls, $option_access_controls, $message_settings, true );
				if ( ! $can_create ) {
					$un_access_users[] = bp_core_get_user_displayname( $recipient );
				}
			}
		}

		if ( empty( $un_access_users ) ) {
			return $retval;
		} else {
			$error = __( 'You can no longer send replies to this thread as you are restricted from sending messages to this member.', 'buddyboss-pro' );
			if ( count( $un_access_users ) > 1 ) {
				$error = __( 'You can no longer send replies to this thread as you are restricted from sending messages to these members: ', 'buddyboss-pro' ) . implode( ', ', $un_access_users );
				if ( count( $un_access_users ) > 3 ) {
					$error = __( 'You can no longer send replies to this thread as you are restricted from sending messages to these members: ', 'buddyboss-pro' ) . implode( ', ', array_slice( $un_access_users, -3 ) ) . __( '...', 'buddyboss-pro' );
				}
			}
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				$error,
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}
	}

	return $retval;
}

/**
 * Filter the group messages `create_item` permissions check.
 *
 * @param bool|WP_Error   $retval  Returned value.
 * @param WP_REST_Request $request The request sent to the API.
 *
 * @since 1.1.0
 *
 * @return bool|WP_Error
 */
function bb_access_control_rest_group_messages_create_permissions_check( $retval, $request ) {
	$message_settings = bb_access_control_send_messages_settings();

	if (
		true !== $retval ||
		empty( $message_settings ) ||
		(
			isset( $message_settings['access-control-type'] ) &&
			empty( $message_settings['access-control-type'] )
		)
	) {
		return $retval;
	}

	$message_users = $request->get_param( 'users' );

	if ( 'all' === $message_users ) {
		return $retval;
	}

	$recipients = $request->get_param( 'users_list' );
	$recipients = wp_parse_id_list( $recipients );

	if ( is_array( $message_settings ) && isset( $message_settings['access-control-type'] ) && ! empty( $message_settings['access-control-type'] ) ) {
		$un_access_users        = array();
		$access_controls        = BB_Access_Control::bb_get_access_control_lists();
		$option_access_controls = $message_settings['access-control-type'];

		// Strip the sender from the recipient list, and unset them if they are
		// not alone. If they are alone, let them talk to themselves.
		if ( isset( $recipients[ bp_loggedin_user_id() ] ) && ( count( $recipients ) > 1 ) ) {
			unset( $recipients[ bp_loggedin_user_id() ] );
		}

		foreach ( $recipients as $recipient ) {
			if ( bp_loggedin_user_id() !== $recipient ) {
				$can_create = bb_access_control_has_access( $recipient, $access_controls, $option_access_controls, $message_settings, true );
				if ( ! $can_create ) {
					$un_access_users[] = bp_core_get_user_displayname( $recipient );
				}
			}
		}

		if ( empty( $un_access_users ) ) {
			return $retval;
		} else {
			$error = __( 'You can no longer send replies to this thread as you are restricted from sending messages to this member.', 'buddyboss-pro' );
			if ( count( $un_access_users ) > 1 ) {
				$error = __( 'You can no longer send replies to this thread as you are restricted from sending messages to these members: ', 'buddyboss-pro' ) . implode( ', ', $un_access_users );
				if ( count( $un_access_users ) > 3 ) {
					$error = __( 'You can no longer send replies to this thread as you are restricted from sending messages to these members: ', 'buddyboss-pro' ) . implode( ', ', array_slice( $un_access_users, -3 ) ) . __( '...', 'buddyboss-pro' );
				}
			}
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				$error,
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}
	}

	return $retval;
}

/**
 * Update the flag `create_friendship` in member endpoint.
 *
 * @param bool $retval  Return value.
 * @param int  $user_id Current Member ID.
 *
 * @since 1.1.0
 *
 * @return bool
 */
function bb_access_control_rest_user_can_create_friendship( $retval, $user_id ) {
	$friend_settings = bb_access_control_friends_settings();
	if (
		empty( $user_id ) ||
		empty( $friend_settings ) ||
		(
			isset( $friend_settings['access-control-type'] ) &&
			empty( $friend_settings['access-control-type'] )
		)
	) {
		return $retval;
	}

	if ( is_array( $friend_settings ) && isset( $friend_settings['access-control-type'] ) && ! empty( $friend_settings['access-control-type'] ) && ! empty( $user_id ) ) {

		$access_controls        = BB_Access_Control::bb_get_access_control_lists();
		$option_access_controls = $friend_settings['access-control-type'];
		$can_create             = bb_access_control_has_access( $user_id, $access_controls, $option_access_controls, $friend_settings, true );

		if ( $can_create ) {
			return true;
		}
	}

	return false;
}

/**
 * Filter the friends `create_item` permissions check.
 *
 * @param bool|WP_Error   $retval  Returned value.
 * @param WP_REST_Request $request The request sent to the API.
 *
 * @since 1.1.0
 *
 * @return bool|WP_Error
 */
function bb_access_control_rest_friends_permissions_check( $retval, $request ) {
	$friend_settings = bb_access_control_friends_settings();

	if (
		true !== $retval ||
		empty( $friend_settings ) ||
		(
			isset( $friend_settings['access-control-type'] ) &&
			empty( $friend_settings['access-control-type'] )
		)
	) {
		return $retval;
	}

	$user_id = $request->get_param( 'friend_id' );

	if ( is_array( $friend_settings ) && isset( $friend_settings['access-control-type'] ) && ! empty( $friend_settings['access-control-type'] ) && ! empty( $user_id ) ) {

		$access_controls        = BB_Access_Control::bb_get_access_control_lists();
		$option_access_controls = $friend_settings['access-control-type'];
		$can_create             = bb_access_control_has_access( $user_id, $access_controls, $option_access_controls, $friend_settings, true );

		if ( ! $can_create ) {
			$retval = new WP_Error(
				'bp_rest_friends_create_item_failed',
				__( 'You don\'t have enough access to send the friend request to this member.', 'buddyboss-pro' ),
				array(
					'status' => 403,
				)
			);
		}
	}

	return $retval;
}

/**
 * Checked the current user has permission to join the group or not.
 *
 * @param boolean $bool    Boolean parameter.
 * @param integer $user_id Checked User ID.
 *
 * @since 1.1.0
 *
 * @return false|mixed
 */
function bb_access_control_check_user_can_join_group( $bool = true, $user_id = 0 ) {
	$join_group_settings = bb_access_control_join_group_settings();
	if ( empty( $user_id ) ) {
		$user_id = bp_loggedin_user_id();
	}

	if (
		empty( $join_group_settings ) ||
		! is_array( $join_group_settings ) ||
		(
			isset( $join_group_settings['access-control-type'] ) &&
			empty( $join_group_settings['access-control-type'] )
		)
	) {
		return $bool;
	}

	$access_controls        = BB_Access_Control::bb_get_access_control_lists();
	$option_access_controls = $join_group_settings['access-control-type'];
	$can_create             = bb_access_control_has_access( $user_id, $access_controls, $option_access_controls, $join_group_settings );

	if ( ! $can_create ) {
		$bool = false;
	} else {
		$bool = true;
	}

	return $bool;
}

/**
 * Update the flag `send_message` in member endpoint.
 *
 * @since 2.3.60
 *
 * @param bool $retval  Return value.
 * @param int  $user_id Current Member ID.
 *
 * @return bool
 */
function bb_access_control_rest_user_can_show_send_message_button( $retval, $user_id ) {
	$message_settings = bb_access_control_send_messages_settings();
	if (
		empty( $user_id ) ||
		empty( $message_settings ) ||
		(
			isset( $message_settings['access-control-type'] ) &&
			empty( $message_settings['access-control-type'] )
		)
	) {
		return $retval;
	}

	if ( is_array( $message_settings ) && isset( $message_settings['access-control-type'] ) && ! empty( $message_settings['access-control-type'] ) && ! empty( $user_id ) ) {

		$access_controls        = BB_Access_Control::bb_get_access_control_lists();
		$option_access_controls = $message_settings['access-control-type'];
		$can_create             = bb_access_control_has_access( $user_id, $access_controls, $option_access_controls, $message_settings, true );

		if ( $can_create ) {
			return true;
		}
	}

	return false;
}
