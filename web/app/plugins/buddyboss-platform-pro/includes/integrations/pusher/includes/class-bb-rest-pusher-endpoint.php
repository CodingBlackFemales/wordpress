<?php
/**
 * BB REST: BB_REST_Pusher_Endpoint class
 *
 * @package BuddyBossPro
 * @since 2.1.6
 */

defined( 'ABSPATH' ) || exit;

/**
 * Pusher Authentication endpoints.
 *
 * @since 2.1.6
 */
class BB_REST_Pusher_Endpoint extends WP_REST_Controller {

	/**
	 * Allow batch.
	 *
	 * @var true[] $allow_batch
	 */
	protected $allow_batch = array( 'v1' => true );

	/**
	 * Constructor.
	 *
	 * @since 2.1.6
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'pusher';
	}

	/**
	 * Register the component routes.
	 *
	 * @since 2.1.6
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/data',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
				),
				'allow_batch' => $this->allow_batch,
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/auth',
			array(
				'args'        => array(
					'channel_name' => array(
						'required'    => true,
						'type'        => array( 'array', 'string' ),
						'description' => __( 'Channel Name', 'buddyboss-pro' ),
					),
					'socket_id'    => array(
						'required'    => true,
						'type'        => 'string',
						'description' => __( 'Socket ID', 'buddyboss-pro' ),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'create_items' ),
					'permission_callback' => array( $this, 'create_items_permissions_check' ),
				),
				'allow_batch' => $this->allow_batch,
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/user-auth',
			array(
				'args' => array(
					'socket_id'    => array(
						'required'    => true,
						'type'        => 'string',
						'description' => __( 'Socket ID', 'buddyboss-pro' ),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'user_authenticate' ),
					'permission_callback' => array( $this, 'user_authenticate_permissions_check' ),
				),
				'allow_batch' => $this->allow_batch,
			)
		);
	}

	/**
	 * Retrieve datas for the pusher.
	 *
	 * @since 2.1.6
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 *
	 * @api {GET} /wp-json/buddyboss/v1/pusher/data Pusher data.
	 */
	public function get_items( $request ) {
		$retval = array(
			'pusher_thread_ids'        => array(),
			'pusher_hidden_thread_ids' => array(),
			'group_thread_ids'         => array(),
			'global_private'           => 'private-bb-pro-global',
			'blocked_users_ids'        => array(),
			'suspended_users_ids'      => array(),
			'is_blocked_by_users'      => array(),
			'alien_hash'               => bb_pusher_get_user_hash( bp_loggedin_user_id() ),
			'hash'                     => bb_pusher_hash_key(),
		);

		if ( bb_pusher_is_feature_enabled( 'live-messaging' ) && bp_is_active( 'messages' ) ) {

			$results = BP_Messages_Thread::get_threads_for_user(
				array(
					'fields'    => 'ids',
					'user_id'   => bp_loggedin_user_id(),
					'is_hidden' => true,
				)
			);

			if ( ! empty( $results ) ) {
				array_walk(
					$results['threads'],
					function ( &$id, $key ) use ( &$retval ) {
						$retval['pusher_thread_ids'][ bb_pusher_string_hash( $id ) ] = $id;
					}
				);
			}

			// Get hidden/archived threads.
			add_filter( 'bp_messages_recipient_get_where_conditions', 'bb_pro_messages_set_hidden_where_query', 9, 2 );
			$hidden_threads = BP_Messages_Thread::get_current_threads_for_user(
				array(
					'fields'    => 'ids',
					'user_id'   => bp_loggedin_user_id(),
					'is_hidden' => true,
				)
			);
			remove_filter( 'bp_messages_recipient_get_where_conditions', 'bb_pro_messages_set_hidden_where_query', 9, 2 );

			if ( ! empty( $hidden_threads ) ) {
				array_walk(
					$hidden_threads['threads'],
					function ( &$id, $key ) use ( &$retval ) {
						$retval['pusher_hidden_thread_ids'][ bb_pusher_string_hash( $id ) ] = $id;
					}
				);
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
					),
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
				$retval['group_thread_ids'] = $group_threads;
			}
		}

		if ( bp_is_active( 'moderation' ) ) {
			$blocked_members = bp_moderation_get(
				array(
					'user_id'           => get_current_user_id(),
					'per_page'          => 0,
					'in_types'          => BP_Moderation_Members::$moderation_type,
					'update_meta_cache' => true,
				)
			);

			$blocked_members_ids = ( ! empty( $blocked_members['moderations'] ) ? array_column( $blocked_members['moderations'], 'item_id' ) : array() );

			if ( ! empty( $blocked_members_ids ) ) {
				array_walk(
					$blocked_members_ids,
					function ( &$id, $key ) use ( &$retval ) {
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

						$retval['blocked_users_ids'][ bb_pusher_get_user_hash( $id ) ] = array(
							'id'                 => $id,
							'blocked_user_name'  => bb_moderation_has_blocked_label( bp_core_get_user_displayname( $id ), $id ),
							'blocked_avatar_url' => bb_moderation_has_blocked_avatar( $avatar, $id ),
						);
					}
				);
			}

			$retval['blocked_message_text'] = esc_html__( 'This content has been hidden as you have blocked this member.', 'buddyboss-pro' );

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
					function ( &$id, $key ) use ( &$retval ) {
						$retval['suspended_users_ids'][ bb_pusher_get_user_hash( $id ) ] = $id;
					}
				);
			}

			$retval['suspended_avatar']       = bb_moderation_is_suspended_avatar();
			$retval['suspended_user_name']    = bb_moderation_is_suspended_label();
			$retval['suspended_message_text'] = esc_html__( 'This content has been hidden from site admin.', 'buddyboss-pro' );

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

					$retval['is_blocked_by_users'][ bb_pusher_get_user_hash( $id ) ] = array(
						'id'                 => $id,
						'blocked_user_name'  => bb_moderation_is_blocked_label( bp_core_get_user_displayname( $id ), $id ),
						'blocked_avatar_url' => bb_moderation_is_blocked_avatar( $avatar, $id ),
					);
				}
			}
		}

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a list of pusher data is fetched via the REST API.
		 *
		 * @since 2.1.6
		 *
		 * @param WP_REST_Response $response  The response data.
		 * @param WP_REST_Request  $request   The request sent to the API.
		 */
		do_action( 'bp_rest_pusher_get_items', $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to pusher data or not.
	 *
	 * @since 2.1.6
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function get_items_permissions_check( $request ) {

		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, Restrict access to only logged-in members.', 'buddyboss-pro' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;

			if ( ! bb_pusher_is_enabled() ) {
				$retval = new WP_Error(
					'bp_rest_authorization_required',
					__( 'Pusher does not setup properly.', 'buddyboss-pro' ),
					array(
						'status' => rest_authorization_required_code(),
					)
				);
			}
		}

		/**
		 * Filter the pusher auth permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 2.1.6
		 */
		return apply_filters( 'bb_rest_pusher_get_items_permissions_check', $retval, $request );
	}

	/**
	 * Create authentication token.
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @since 2.1.6
	 *
	 * @api {POST} /wp-json/buddyboss/v1/pusher/auth Pusher authentication
	 */
	public function create_items( $request ) {
		$channel_name = $request->get_param( 'channel_name' );
		$socket_id    = $request->get_param( 'socket_id' );

		if ( empty( $channel_name ) ) {
			return new \WP_Error( 'rest_pusher_channel_name_req', __( 'a valid channel_name param is required.', 'buddyboss-pro' ), array( 'status' => 500 ) );
		}
		if ( empty( $socket_id ) ) {
			return new \WP_Error( 'rest_pusher_socket_id_req', __( 'a valid socket_id param is required.', 'buddyboss-pro' ), array( 'status' => 500 ) );
		}

		$current_user_id = $request->get_param( 'bb_pusher_user_id' );
		$current_data    = $request->get_param( 'bb_pusher_user_data' );
		$alien_hash      = $request->get_param( 'alien_hash' );

		if ( is_array( $channel_name ) ) {
			$retval = array();

			foreach ( $channel_name as $channel ) {
				$response = $this->subscribe_channel( $channel, $socket_id, $current_user_id, $current_data );

				if ( ! is_wp_error( $response ) ) {
					$data               = $response->get_data();
					$retval[ $channel ] = array(
						'status' => 200,
						'data'   => $data,
					);
				} else {
					$retval[ $channel ] = array(
						'status' => ( ! empty( $response->get_error_code() ) ? $response->get_error_code() : 400 ),
						'data'   => array(),
					);
				}
			}

			return rest_ensure_response( $retval );

		} else {
			if ( ! empty( $current_user_id ) ) {
				$user_data = get_userdata( $current_user_id );
				if ( ! empty( $user_data ) ) {
					$user_hash = $user_data->user_pass;
					$user_hash = sha1( $current_user_id . $user_hash );
				}
			}

			if ( empty( $alien_hash ) || $alien_hash !== $user_hash ) {
				return new \WP_Error( 'rest_pusher_unauthorized', __( 'You are not authorized to subscribe this channel.', 'buddyboss-pro' ), array( 'status' => 500 ) );
			}

			return $this->subscribe_channel( $channel_name, $socket_id, $current_user_id, $current_data );
		}

		return new WP_Error(
			'bb_rest_authorization_required',
			__( 'Sorry, you are not allowed to access this endpoint', 'buddyboss-pro' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);
	}

	/**
	 * Check if a given request has access to pusher auth.
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 * @since 2.1.6
	 */
	public function create_items_permissions_check( $request ) {

		$retval = true;

		/**
		 * Filter the pusher auth permissions check.
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 *
		 * @since 2.1.6
		 */
		return apply_filters( 'bb_rest_pusher_auth_create_items_permissions_check', $retval, $request );
	}

	/**
	 * Function for subscribe the user channels.
	 *
	 * @since 2.1.6
	 *
	 * @param string $channel_name    Channel name.
	 * @param string $socket_id       Pusher socket id.
	 * @param int    $current_user_id Current User id.
	 * @param array  $user_data       Array of user info.
	 *
	 * @return void|WP_Error|WP_HTTP_Response|WP_REST_Response
	 * @throws \GuzzleHttp\Exception\GuzzleException
	 */
	protected function subscribe_channel( $channel_name, $socket_id, $current_user_id, $user_data ) {
		$parts        = explode( '-', $channel_name );
		$channel_type = current( $parts );

		switch ( $channel_type ) {
			case 'private':
				if ( 0 !== $current_user_id ) {
					if ( strpos( $channel_name, 'private-bb-message-thread-' ) !== false ) {
						$channel_thread_id = (int) str_replace( 'private-bb-message-thread-', '', $channel_name );
						if ( ! messages_check_thread_access( $channel_thread_id, (int) $current_user_id ) ) {
							return new \WP_Error( 'rest_pusher_channel_name_invalid', __( 'You are not authorized to subscribe this channel.', 'buddyboss-pro' ), array( 'status' => 500 ) );
						}
					} elseif ( strpos( $channel_name, 'private-bb-user-' ) !== false ) {
						$channel_user_id = (int) str_replace( 'private-bb-user-', '', $channel_name );
						if ( $channel_user_id !== (int) $current_user_id ) {
							return new \WP_Error( 'rest_pusher_user_invalid', __( 'You are not authorized to subscribe this channel.', 'buddyboss-pro' ), array( 'status' => 500 ) );
						}
					}

					try {
						$pusher = bb_pusher();
						$data   = $pusher->socketAuth( $channel_name, $socket_id, $current_user_id );
						$data   = json_decode( $data, true );

						return rest_ensure_response( $data );
					} catch ( Exception $error ) {
						return new \WP_Error( 'rest_pusher_invalid_auth', $error->getMessage(), array( 'status' => $error->getCode() ) );
					}
				} else {
					return new WP_Error(
						'bb_rest_authorization_required',
						__( 'Sorry, you are not allowed to access this endpoint', 'buddyboss-pro' ),
						array(
							'status' => rest_authorization_required_code(),
						)
					);
				}
			case 'presence':
				try {
					$pusher    = bb_pusher();
					$user_data = json_decode( $user_data, true );
					$data      = $pusher->presenceAuth( $channel_name, $socket_id, $current_user_id, $user_data );
					$data      = json_decode( $data, true );

					return rest_ensure_response( $data );
				} catch ( Exception $error ) {
					return new \WP_Error( 'rest_pusher_invalid_auth', $error->getMessage(), array( 'status' => $error->getCode() ) );
				}
		}
	}

	/**
	 * Authenticate user for the pusher.
	 *
	 * @since 2.3.50
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 *
	 * @api {POST} /wp-json/buddyboss/v1/pusher/user-auth Pusher data.
	 */
	public function user_authenticate( $request ) {
		$socket_id = $request->get_param( 'socket_id' );
		$user_id   = bp_loggedin_user_id();

		if ( empty( $user_id ) ) {
			return new WP_Error(
				'rest_pusher_invalid_auth',
				__( 'There is an error while authorizing the user.', 'buddyboss-pro' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		try {
			$pusher          = bb_pusher();
			$user_info       = array();
			$user_info['id'] = (string) $user_id;

			echo $pusher->authenticateUser( $socket_id, $user_info );
			exit;
		} catch ( Exception $error ) {
			return new WP_Error(
				'rest_pusher_invalid_auth',
				__( 'There is an error while authorizing the user.', 'buddyboss-pro' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}
	}

	/**
	 * Check if a given request has access to pusher user-auth.
	 *
	 * @since 2.3.50
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function user_authenticate_permissions_check( $request ) {

		$retval = true;

		/**
		 * Filter the pusher user auth permissions check.
		 *
		 * @since 2.3.50
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bb_rest_pusher_user_authenticate_permissions_check', $retval, $request );
	}
}
