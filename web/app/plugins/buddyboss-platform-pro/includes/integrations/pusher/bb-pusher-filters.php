<?php
/**
 * Pusher integration filters
 *
 * @package BuddyBossPro\Pusher
 * @since   2.1.6
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

add_filter( 'thread_recipient_inbox_unread_counts', 'bb_pro_pusher_thread_recipient_inbox_unread_counts', 10, 2 );
add_filter( 'bb_on_screen_notification_query_string', 'bb_pro_pusher_on_screen_notification_query_string', 10, 1 );
add_filter( 'bb_nouveau_ajax_messages_send_reply_success', 'bb_pro_pusher_bb_nouveau_ajax_messages_send_reply_success', 10, 1 );
add_filter( 'bb_nouveau_ajax_messages_send_message_success_response', 'bb_pro_pusher_bb_nouveau_ajax_messages_send_reply_success', 10, 1 );

add_filter( 'bb_exclude_endpoints_from_restriction', 'bb_pro_pusher_exclude_endpoints_from_restriction', 10, 1 );

/**
 * Filter the thread recipient inbox unread counts.
 *
 * @since 2.1.6
 *
 * @param array $data             Recipients unread count array.
 * @param array $recipients_lists List of thread recipents.
 *
 * @return array
 */
function bb_pro_pusher_thread_recipient_inbox_unread_counts( $data, $recipients_lists ) {

	$inbox_unread_cnt = array();
	if ( is_user_logged_in() && bb_pusher_is_feature_enabled( 'live-messaging' ) && bp_is_active( 'messages' ) ) {
		if ( empty( $data ) ) {
			if ( $recipients_lists ) {
				foreach ( $recipients_lists as $key => $val ) {
					$inbox_unread_cnt[ $key ] = array(
						'message_id'                  => $val->id,
						'user_id'                     => $val->user_id,
						'thread_id'                   => $val->thread_id,
						'thread_unread_count'         => $val->unread_count,
						'is_deleted'                  => $val->is_deleted,
						'is_hidden'                   => $val->is_hidden,
						'inbox_unread_count'          => messages_get_unread_count( $key ),
						'current_thread_unread_count' => bb_get_thread_messages_unread_count( $val->thread_id, $key ),
					);
				}
			}
		}
	}

	$data = array_merge( $data, $inbox_unread_cnt );

	return $data;

}

/**
 * Fire an event for the group message thread.
 *
 * @since 2.1.6
 *
 * @param null|bool $check      Whether to allow updating metadata for the given type.
 * @param int       $object_id  ID of the object metadata is for.
 * @param string    $meta_key   Metadata key.
 * @param mixed     $meta_value Metadata value. Must be serializable if non-scalar.
 * @param mixed     $prev_value Optional. Previous value to check before updating.
 *
 * @return null|bool
 */
function bb_pro_pusher_group_events( $check, $object_id, $meta_key, $meta_value, $prev_value ) {
	if ( null === $check ) {

		// Compare existing value to new value if no prev value given and the key exists only once.
		if ( empty( $prev_value ) ) {
			$old_value = get_metadata_raw( 'message', $object_id, $meta_key );
			if ( is_countable( $old_value ) && count( $old_value ) === 1 ) {
				if ( $old_value[0] === $meta_value ) {
					return $check;
				}
			}
		}

		if (
			! empty( $meta_key ) &&
			in_array( $meta_key, array( 'group_message_group_joined', 'group_message_group_left', 'group_message_group_ban', 'group_message_group_un_ban' ), true ) &&
			bb_pusher_is_enabled() &&
			bb_pusher_is_feature_enabled( 'live-messaging' )
		) {
			$message   = new BP_Messages_Message( $object_id );
			$bb_pusher = bb_pusher();

			if ( ! empty( $message->thread_id ) ) {
				$channel = 'private-bb-message-thread-' . $message->thread_id;

				$event_data = array(
					'action'    => $meta_key,
					'thread_id' => bb_pusher_string_hash( $message->thread_id ),
					'sender_id' => (int) $message->sender_id,
				);

				if ( null !== $bb_pusher ) {
					bb_pusher_trigger_event( $bb_pusher, $channel, 'client-bb-pro-group-message-group-update-notify', $event_data );

					if (
						in_array(
							$meta_key,
							array(
								'group_message_group_left',
								'group_message_group_ban',
							),
							true
						)
					) {
						bb_pusher_trigger_event( $bb_pusher, 'private-bb-user-' . $message->sender_id, 'client-bb-pro-reconnect', array() );
						$bb_pusher->terminateUserConnections( $message->sender_id );
					}
				}
			}
		}
	}

	return $check;
}

add_filter( 'update_message_metadata', 'bb_pro_pusher_group_events', 999, 5 );

/**
 * Filter to change where for get hidden threads.
 *
 * @since 2.1.6
 *
 * @param array $where Where conditions SQL statement.
 * @param array $args  Array of parsed arguments for the get method.
 *
 * @return string
 */
function bb_pro_messages_set_hidden_where_query( $where, $args ) {

	$user_id = bp_loggedin_user_id();
	if ( $args['user_id'] ) {
		$user_id = $args['user_id'];
	}

	return 'WHERE r.is_deleted = 0 AND r.user_id = ' . $user_id . ' AND r.is_hidden = 1 ';
}

/**
 * Receive Heartbeat data and respond.
 * Processes data received via a Heartbeat request, and returns additional data to pass back to the front end.
 *
 * @since 2.1.6
 *
 * @param array $response Heartbeat response data to pass back to front end.
 * @param array $data     Data received from the front end (unslashed).
 *
 * @return array
 */
function bb_pro_pusher_receive_heartbeat( array $response, array $data ) {

	if ( is_user_logged_in() && bb_pusher_is_feature_enabled( 'live-messaging' ) && bp_is_active( 'messages' ) ) {

		$results = BP_Messages_Thread::get_threads_for_user(
			array(
				'fields'    => 'ids',
				'user_id'   => bp_loggedin_user_id(),
				'is_hidden' => true,
			)
		);

		$bb_pro_pusher_thread_ids = array();

		if ( ! empty( $results ) ) {
			array_walk(
				$results['threads'],
				function ( &$id, $key ) use ( &$bb_pro_pusher_thread_ids ) {
					$bb_pro_pusher_thread_ids[ bb_pusher_string_hash( $id ) ] = $id;
				}
			);
		}

		$response['bb_pro_pusher_thread_ids']         = $bb_pro_pusher_thread_ids;
		$response['bb_pro_pusher_inbox_unread_count'] = messages_get_unread_count( bp_loggedin_user_id() );

		if ( bp_is_active( 'moderation' ) ) {
			$response['is_blocked_by_users'] = array();
			$response['blocked_users_ids']   = array();
			$response['suspended_users_ids'] = array();

			$blocked_members = bp_moderation_get(
				array(
					'user_id'           => bp_loggedin_user_id(),
					'per_page'          => 0,
					'in_types'          => BP_Moderation_Members::$moderation_type,
					'update_meta_cache' => true,
				)
			);

			$blocked_members_ids = ( ! empty( $blocked_members['moderations'] ) ? array_column( $blocked_members['moderations'], 'item_id' ) : array() );

			if ( ! empty( $blocked_members_ids ) ) {
				array_walk(
					$blocked_members_ids,
					function ( &$id, $key ) use ( &$response ) {
						$avatar = bp_core_fetch_avatar(
							array(
								'item_id' => $id,
								'object'  => 'user',
								'type'    => 'thumb',
								'width'   => BP_AVATAR_THUMB_WIDTH,
								'height'  => BP_AVATAR_THUMB_HEIGHT,
								'html'    => false,
							)
						);

						$response['blocked_users_ids'][ bb_pusher_get_user_hash( $id ) ] = array(
							'id'                 => $id,
							'blocked_user_name'  => bb_moderation_has_blocked_label( bp_core_get_user_displayname( $id ), $id ),
							'blocked_avatar_url' => bb_moderation_has_blocked_avatar( $avatar, $id ),
						);
					}
				);
			}

			$suspended_users = BP_Moderation::get(
				array(
					'per_page'          => 0,
					'hidden'            => 1,
					'fields'            => 'ids',
					'in_types'          => BP_Moderation_Members::$moderation_type,
					'update_meta_cache' => true,
				)
			);

			$suspended_users_ids = ( ! empty( $suspended_users['moderations'] ) ? array_column( $suspended_users['moderations'], 'item_id' ) : array() );

			if ( ! empty( $suspended_users_ids ) ) {
				array_walk(
					$suspended_users_ids,
					function ( &$id, $key ) use ( &$response ) {
						$response['suspended_users_ids'][ bb_pusher_get_user_hash( $id ) ] = $id;
					}
				);
			}

			// Is blocked by members.
			$is_blocked_by_members = bb_moderation_get_blocked_by_user_ids( get_current_user_id() );
			if ( ! empty( $is_blocked_by_members ) ) {
				foreach ( $is_blocked_by_members as $id ) {
					$avatar = bp_core_fetch_avatar(
						array(
							'item_id' => $id,
							'object'  => 'user',
							'type'    => 'thumb',
							'width'   => BP_AVATAR_THUMB_WIDTH,
							'height'  => BP_AVATAR_THUMB_HEIGHT,
							'html'    => false,
						)
					);

					$response['is_blocked_by_users'][ bb_pusher_get_user_hash( $id ) ] = array(
						'id'                 => $id,
						'blocked_user_name'  => bb_moderation_is_blocked_label( bp_core_get_user_displayname( $id ), $id ),
						'blocked_avatar_url' => bb_moderation_is_blocked_avatar( $avatar, $id ),
					);
				}
			}
		}

		if ( bp_is_active( 'groups' ) && function_exists( 'bp_disable_group_messages' ) && true === bp_disable_group_messages() ) {
			// Determine groups of user.
			$groups = groups_get_groups(
				array(
					'fields'      => 'ids',
					'per_page'    => - 1,
					'user_id'     => bp_loggedin_user_id(),
					'show_hidden' => true,
					'meta_query'  => array( // phpcs:ignore
						'relation' => 'AND',
						array(
							'key'     => 'group_message_thread',
							'compare' => 'EXISTS',
						),
					),
				)
			);

			$group_ids = ( isset( $groups['groups'] ) ? $groups['groups'] : array() );

			$group_threads = array();
			if ( ! empty( $group_ids ) ) {
				array_walk(
					$group_ids,
					function ( &$group_id, $key ) use ( &$group_threads ) {
						$thread_id = (int) groups_get_groupmeta( (int) $group_id, 'group_message_thread' );
						if ( ! empty( $thread_id ) ) {
							$group_threads[ bb_pusher_string_hash( $thread_id ) ] = $thread_id;
						}
					}
				);
			}
			$response['group_threads'] = $group_threads;

		}
	}

	return $response;
}

add_filter( 'heartbeat_received', 'bb_pro_pusher_receive_heartbeat', 10, 2 );
add_filter( 'thread_recipient_inbox_unread_counts', 'bb_pro_pusher_thread_recipient_inbox_unread_counts', 10, 2 );

/**
 * Query string for the notification query
 *
 * @since 2.1.6
 *
 * @param string $query_string Query string for the notification query.
 *
 * @return string Query string for the notification query.
 */
function bb_pro_pusher_on_screen_notification_query_string( $query_string ) {

	if ( ! ( bbp_pro_is_license_valid() && bb_pusher_is_enabled() && bb_pusher_is_feature_enabled( 'live-messaging' ) && bp_is_user_messages() ) ) {
		return $query_string;

	}

	$query_string = bp_parse_args( $query_string );

	if ( ! empty( $query_string['excluded_action'] ) ) {
		if ( ! is_array( $query_string['excluded_action'] ) ) {
			$query_string['excluded_action'] = explode( ',', $query_string['excluded_action'] );
		}
		$query_string['excluded_action'] = array_filter( array_merge( $query_string['excluded_action'], array( 'new_message', 'bb_groups_new_message', 'bb_messages_new' ) ) );
	} else {
		$query_string['excluded_action'] = array( 'new_message', 'bb_groups_new_message', 'bb_messages_new' );
	}

	return http_build_query( $query_string );

}

/**
 * Trigger an event to pusher of new message or reply ajax success.
 *
 * @since 2.1.6
 *
 * @param array $response response of the ajax success.
 *
 * @return array Response of the ajax success.
 */
function bb_pro_pusher_bb_nouveau_ajax_messages_send_reply_success( $response ) {
	if (
		! bbp_pro_is_license_valid() ||
		! bb_pusher_is_enabled() ||
		! bb_pusher_is_feature_enabled( 'live-messaging' )
	) {
		return $response;
	}

	$bb_pusher = bb_pusher();

	if ( ! empty( $response['thread_id'] ) && (int) $response['thread_id'] > 0 && null !== $bb_pusher && bp_is_active( 'messages' ) ) {

		$notify_data = array(
			'hash'                          => $response['hash'],
			'thread_id'                     => $response['thread_id'],
			'recipient_inbox_unread_counts' => $response['recipient_inbox_unread_counts'],
		);

		if ( isset( $response['messages'] ) ) {
			$notify_data['message'] = $response['messages'][0];
		}

		bb_pro_pusher_trigger_chunked_event( $bb_pusher, 'private-bb-message-thread-' . $response['thread_id'], 'client-bb-pro-after-message-ajax-complete', $notify_data );

	}

	return $response;
}

/**
 * Exclude the pusher auth endpoint from private apis.
 *
 * @since 2.2.1
 *
 * @param array $exclude_endpoint Array of endpoints.
 *
 * @return mixed
 */
function bb_pro_pusher_exclude_endpoints_from_restriction( $exclude_endpoint ) {
	if (
		! bbp_pro_is_license_valid() ||
		! bb_pusher_is_enabled()
	) {
		return $exclude_endpoint;
	}

	$exclude_endpoint[] = '/buddyboss/v1/pusher/auth';

	return $exclude_endpoint;
}
