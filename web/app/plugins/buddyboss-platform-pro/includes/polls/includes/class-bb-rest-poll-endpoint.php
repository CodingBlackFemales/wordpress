<?php
/**
 * BB REST: BB_REST_Poll_Endpoint class
 *
 * @package BuddyBossPro
 * @since 2.6.00
 */

defined( 'ABSPATH' ) || exit;

/**
 * Poll endpoints.
 *
 * @since 2.6.00
 */
class BB_REST_Poll_Endpoint extends WP_REST_Controller {

	/**
	 * Allow batch.
	 *
	 * @var true[] $allow_batch
	 */
	protected $allow_batch = array( 'v1' => true );

	/**
	 * Reuse some parts of the BB_REST_Poll_Option_Endpoint class.
	 *
	 * @since 2.6.00
	 *
	 * @var BB_REST_Poll_Option_Endpoint
	 */
	protected $poll_option_endpoint;

	/**
	 * Constructor.
	 *
	 * @since 2.6.00
	 */
	public function __construct() {
		$this->namespace            = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base            = 'poll';
		$this->poll_option_endpoint = new BB_REST_Poll_Option_Endpoint();

		$this->bb_rest_poll_support();

		add_filter( 'bp_rest_activity_create_item_query_arguments', array( $this, 'bb_rest_activity_query_arguments' ), 99, 3 );
	}

	/**
	 * Register the component routes.
	 *
	 * @since 2.6.00
	 */
	public function register_routes() {
		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( $this, 'create_item' ),
					'permission_callback' => array( $this, 'create_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'allow_batch' => $this->allow_batch,
				'schema'      => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			'/' . $this->rest_base . '/(?P<id>[\d]+)',
			array(
				'args'        => array(
					'id' => array(
						'description' => __( 'A unique numeric ID for the Poll.', 'buddyboss-pro' ),
						'type'        => 'integer',
						'required'    => true,
					),
				),
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_item' ),
					'permission_callback' => array( $this, 'get_item_permissions_check' ),
					'args'                => array(
						'context' => $this->get_context_param(
							array(
								'default' => 'view',
							)
						),
					),
				),
				array(
					'methods'             => WP_REST_Server::EDITABLE,
					'callback'            => array( $this, 'update_item' ),
					'permission_callback' => array( $this, 'update_item_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::EDITABLE ),
				),
				array(
					'methods'             => WP_REST_Server::DELETABLE,
					'callback'            => array( $this, 'delete_item' ),
					'permission_callback' => array( $this, 'delete_item_permissions_check' ),
				),
				'allow_batch' => $this->allow_batch,
				'schema'      => array( $this, 'get_item_schema' ),
			)
		);
	}

	/**
	 * Create a poll.
	 *
	 * @since          2.6.00
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @api            {POST} /wp-json/buddyboss/v1/poll Create Poll
	 * @apiName        CreatePoll
	 * @apiGroup       Poll
	 * @apiDescription Create a poll with activity.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the poll.
	 * @apiParam {Number} group_id A unique numeric ID for the group.
	 * @apiParam {Number} item_id Item id for a poll.
	 * @apiParam {Number} item_type Item type for a poll.
	 * @apiParam {String} question The question for the poll.
	 * @apiParam {Boolean} settings_allow_multiple_options Allow multiple selections.
	 * @apiParam {Boolean} settings_allow_new_option Allow users to add options.
	 * @apiParam {Number} settings_duration Duration of the poll.
	 * @apiParam {String} status
	 * @apiParam {Array} options. Poll options.
	 */
	public function create_item( $request ) {
		$question = $request->get_param( 'question' );
		if ( empty( $question ) ) {
			return new WP_Error(
				'bp_rest_poll_question_blank',
				__( 'Please do not leave the question blank.', 'buddyboss-pro' ),
				array(
					'status' => 400,
				)
			);
		}

		$item_id   = (int) $request->get_param( 'item_id' );
		$item_type = $request->get_param( 'item_type' );
		$user_id   = $request->get_param( 'user_id' );
		if ( empty( $user_id ) ) {
			$user_id = bp_loggedin_user_id();
		}

		// Settings.
		$settings                           = array();
		$settings['allow_multiple_options'] = (bool) $request->get_param( 'settings_allow_multiple_options' );
		$settings['allow_new_option']       = (bool) $request->get_param( 'settings_allow_new_option' );
		$settings['duration']               = ! empty( $request->get_param( 'settings_duration' ) ) ? $request->get_param( 'settings_duration' ) : 3;
		$status                             = 'draft';

		// Check options.
		$poll_options           = $request->get_param( 'options' );
		$sanitized_poll_options = array();
		$flat_options           = array();
		foreach ( $poll_options as $key => $option ) {
			$title = sanitize_text_field( wp_unslash( wp_strip_all_tags( $option['option_title'] ) ) );
			// Flatten the array for validation checks.
			$flat_options[ $key ] = $title;

			// Sanitize the poll options.
			$sanitized_poll_options[ $key ] = array(
				'id'           => ! empty( $option['id'] ) ? $option['id'] : 0,
				'option_title' => $title,
				'option_order' => ! empty( $option['option_order'] ) ? $option['option_order'] : $key,
			);

			// Check the length of each option.
			if ( strlen( $title ) > 50 ) {
				return new WP_Error(
					'bp_rest_option_max_length',
					__( 'Poll option must be between 1 and 50 characters long.', 'buddyboss-pro' ),
					array(
						'status' => 400,
					)
				);
			}
		}

		// Check if the poll option is empty.
		if ( in_array( '', $flat_options, true ) ) {
			return new WP_Error(
				'bp_rest_options_required',
				__( 'Poll options are required.', 'buddyboss-pro' ),
				array(
					'status' => 400,
				)
			);
		} elseif ( ! is_array( $flat_options ) ) {
			return new WP_Error(
				'bp_rest_option_required_array',
				__( 'Poll options must be an array.', 'buddyboss-pro' ),
				array(
					'status' => 400,
				)
			);
		} elseif ( count( $flat_options ) < 2 ) {
			return new WP_Error(
				'bp_rest_min_two_option_required',
				__( 'Poll options must be at least 2.', 'buddyboss-pro' ),
				array(
					'status' => 400,
				)
			);
		} elseif ( count( $flat_options ) > 10 ) {
			return new WP_Error(
				'bp_rest_max_ten_option_required',
				__( 'Poll options must be at most 10.', 'buddyboss-pro' ),
				array(
					'status' => 400,
				)
			);
		} elseif ( count( $flat_options ) !== count( array_unique( $flat_options ) ) ) {
			return new WP_Error(
				'bp_rest_unique_option',
				__( 'Poll options must be unique.', 'buddyboss-pro' ),
				array(
					'status' => 400,
				)
			);
		}

		$poll_args = array(
			'item_id'            => $item_id,
			'item_type'          => $item_type,
			'user_id'            => $user_id,
			'question'           => $question,
			'settings'           => $settings,
			'status'             => $status,
			'vote_disabled_date' => '',
		);

		$poll_data = bb_load_polls()->bb_update_poll( $poll_args );

		if ( ! $poll_data ) {
			return new WP_Error(
				'bp_rest_poll_cannot_update',
				__( 'Could not update the poll.', 'buddyboss-pro' ),
				array(
					'status' => 500,
				)
			);
		}

		$poll_options_data = array();
		if ( ! empty( $poll_data ) ) {
			$poll_options_data['poll_id'] = $poll_data->id;
			if ( '0000-00-00 00:00:00' !== $poll_data->vote_disabled_date ) {
				$vote_disabled_date = strtotime( $poll_data->vote_disabled_date );
			} else {
				$duration           = bb_poll_get_duration( $poll_data );
				$vote_disabled_date = intval( bp_core_current_time( true, 'timestamp' ) ) + ( intval( $duration ) * DAY_IN_SECONDS ); // Calculate the future timestamp.
			}
			$poll_data->vote_disabled_date = gmdate( 'Y-m-d H:i:s', $vote_disabled_date );

			$fetch_poll_data = array();
			if ( ! empty( $sanitized_poll_options ) ) {
				// Add/Update options.
				foreach ( $sanitized_poll_options as $key => $option ) {
					$poll_options_data['id']              = $option['id'];
					$poll_options_data['option_title']    = $option['option_title'];
					$poll_options_data['option_order']    = ! empty( $option['option_order'] ) ? $option['option_order'] : $key;
					$poll_options_data['user_id']         = ! empty( $poll_id ) ? $poll_data->user_id : bp_loggedin_user_id();
					$updated_poll_option                  = bb_load_polls()->bb_update_poll_option( $poll_options_data );
					$current_poll_option                  = ! empty( $updated_poll_option[0] ) ? current( $updated_poll_option ) : array();
					$fetch_poll_data[ $key ]              = $current_poll_option;
					$fetch_poll_data[ $key ]['user_data'] = array(
						'username'    => bp_core_get_user_displayname( $current_poll_option['user_id'] ),
						'user_domain' => bp_core_get_user_domain( $current_poll_option['user_id'] ),
					);
				}
			}

			if ( ! empty( $fetch_poll_data ) ) {
				$poll_data->options = $fetch_poll_data;
			}
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $poll_data, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a poll is created via the REST API.
		 *
		 * @since 2.6.00
		 *
		 * @param BB_Polls         $poll_data The created poll.
		 * @param WP_REST_Response $response  The response data.
		 * @param WP_REST_Request  $request   The request sent to the API.
		 */
		do_action( 'bp_rest_poll_option_create_item', $poll_data, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to create a poll.
	 *
	 * @since 2.6.00
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function create_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to create a poll.', 'buddyboss-pro' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;

			$user_id = $request->get_param( 'user_id' );
			if ( empty( $user_id ) ) {
				$user_id = bp_loggedin_user_id();
			}

			$secondary_item_id = $request->get_param( 'secondary_item_id' );
			if (
				empty( $secondary_item_id ) &&
				! bb_can_user_create_poll_activity( array( 'user_id' => $user_id ) )
			) {
				$retval = new WP_Error(
					'bp_rest_poll_cannot_create',
					__( 'Sorry, you are not allowed to create a poll.', 'buddyboss-pro' ),
					array(
						'status' => 403,
					)
				);
			} elseif (
				! empty( $secondary_item_id ) &&
				! bb_can_user_create_poll_activity(
					array(
						'object'   => 'group',
						'group_id' => $secondary_item_id,
						'user_id'  => $user_id,
					)
				)
			) {
				$retval = new WP_Error(
					'bp_rest_poll_cannot_create_group_permission',
					__( 'Sorry, you are not allowed to create a poll.', 'buddyboss-pro' ),
					array(
						'status' => 403,
					)
				);
			}
		}

		/**
		 * Filter the polls `create_item` permissions check.
		 *
		 * @since 2.6.00
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_polls_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve a poll.
	 *
	 * @since 2.6.00
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @api            {GET} /wp-json/buddyboss/v1/poll/:poll_id Get Poll
	 * @apiName        GetBBPoll
	 * @apiGroup       Polls
	 * @apiDescription Retrieve single poll
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser.
	 * @apiParam {Number} id A unique numeric ID for the Poll.
	 */
	public function get_item( $request ) {
		$poll = $this->get_poll_object( $request );

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $poll, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a poll is fetched via the REST API.
		 *
		 * @since 2.6.00
		 *
		 * @param BB_Polls         $poll     Fetched Poll.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'bp_rest_polls_get_item', $poll, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to get information about a specific poll.
	 *
	 * @since 2.6.00
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|bool
	 */
	public function get_item_permissions_check( $request ) {
		$retval = true;

		if ( function_exists( 'bp_rest_enable_private_network' ) && true === bp_rest_enable_private_network() && ! is_user_logged_in() ) {
			$retval = new WP_Error(
				'bp_rest_authorization_required',
				__( 'Sorry, Restrict access to only logged-in members.', 'buddyboss-pro' ),
				array(
					'status' => rest_authorization_required_code(),
				)
			);
		}

		$poll = $this->get_poll_object( $request );

		if ( empty( $poll->id ) && true === $retval ) {
			$retval = new WP_Error(
				'bp_rest_poll_invalid_id',
				__( 'Invalid poll ID.', 'buddyboss-pro' ),
				array(
					'status' => 404,
				)
			);
		}

		/**
		 * Filter the polls `get_item` permissions check.
		 *
		 * @since 2.6.00
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_polls_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Update a poll.
	 *
	 * @since          2.6.00
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @api            {PATCH} /wp-json/buddyboss/v1/poll/:poll_id Update Poll
	 * @apiName        UpdateBBPoll
	 * @apiGroup       Polls
	 * @apiDescription Update a poll.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the Poll.
	 */
	public function update_item( $request ) {
		$poll = $this->get_poll_object( $request );
		if ( empty( $poll ) || empty( $poll->id ) ) {
			return new WP_Error(
				'bp_rest_poll_invalid_id',
				__( 'Invalid poll ID.', 'buddyboss-pro' ),
				array(
					'status' => 404,
				)
			);
		}

		// Settings.
		$settings                           = array();
		$settings['allow_multiple_options'] = $request->get_param( 'settings_allow_multiple_options' ) !== null ? $request->get_param( 'settings_allow_multiple_options' ) : bb_poll_allow_multiple_options( $poll );
		$settings['allow_new_option']       = $request->get_param( 'settings_allow_new_option' ) !== null ? $request->get_param( 'settings_allow_new_option' ) : bb_poll_allow_new_options( $poll );
		$settings['duration']               = $request->get_param( 'settings_duration' ) !== null ? $request->get_param( 'settings_duration' ) : bb_poll_get_duration( $poll );

		$status = $request->get_param( 'status' ) ?? 'draft';
		if ( ! empty( $poll->item_id ) ) {
			$activity_id      = $poll->item_id;
			$activity_poll_id = bb_poll_get_activity_meta_poll_id( $activity_id );
			if ( $activity_poll_id === (int) $poll->id ) {
				$activity = new BP_Activity_Activity( $activity_id );
				$status   = $activity->status;
			}

			// Update votes for a poll if the setting is disabled to disallow multiple options.
			bb_update_votes_after_disable_allow_multiple_options(
				array(
					'poll_id'                => $poll->id,
					'allow_multiple_options' => $settings['allow_multiple_options'],
				)
			);

			$settings['duration'] = bb_poll_get_duration( $poll );
		}

		$poll_args = array(
			'id'       => $poll->id,
			'question' => $request->get_param( 'question' ),
			'status'   => $status,
			'settings' => $settings,
		);

		// Check options.
		$poll_options           = $request->get_param( 'options' );
		$sanitized_poll_options = array();
		$flat_options           = array();
		foreach ( $poll_options as $key => $option ) {
			$title = sanitize_text_field( wp_unslash( wp_strip_all_tags( $option['option_title'] ) ) );
			// Flatten the array for validation checks.
			$flat_options[ $key ] = $title;

			// Sanitize the poll options.
			$sanitized_poll_options[ $key ] = array(
				'id'           => ! empty( $option['id'] ) ? $option['id'] : 0,
				'option_title' => $title,
				'option_order' => ! empty( $option['option_order'] ) ? $option['option_order'] : $key,
			);

			// Check the length of each option.
			if ( strlen( $title ) > 50 ) {
				return new WP_Error(
					'bp_rest_option_max_length',
					__( 'Poll option must be between 1 and 50 characters long.', 'buddyboss-pro' ),
					array(
						'status' => 400,
					)
				);
			}
		}

		// Check if the poll option is empty.
		if ( in_array( '', $flat_options, true ) ) {
			return new WP_Error(
				'bp_rest_options_required',
				__( 'Poll options are required.', 'buddyboss-pro' ),
				array(
					'status' => 400,
				)
			);
		} elseif ( ! is_array( $flat_options ) ) {
			return new WP_Error(
				'bp_rest_option_required_array',
				__( 'Poll options must be an array.', 'buddyboss-pro' ),
				array(
					'status' => 400,
				)
			);
		} elseif ( count( $flat_options ) < 2 ) {
			return new WP_Error(
				'bp_rest_min_two_option_required',
				__( 'Poll options must be at least 2.', 'buddyboss-pro' ),
				array(
					'status' => 400,
				)
			);
		} elseif ( count( $flat_options ) > 10 ) {
			return new WP_Error(
				'bp_rest_max_ten_option_required',
				__( 'Poll options must be at most 10.', 'buddyboss-pro' ),
				array(
					'status' => 400,
				)
			);
		} elseif ( count( $flat_options ) !== count( array_unique( $flat_options ) ) ) {
			return new WP_Error(
				'bp_rest_unique_option',
				__( 'Poll options must be unique.', 'buddyboss-pro' ),
				array(
					'status' => 400,
				)
			);
		}

		$poll_data = bb_load_polls()->bb_update_poll( $poll_args );

		if ( ! $poll_data ) {
			return new WP_Error(
				'bp_rest_poll_cannot_update',
				__( 'Could not update the poll.', 'buddyboss-pro' ),
				array(
					'status' => 500,
				)
			);
		}

		$poll_options_data = array();
		if ( ! empty( $poll_data ) ) {
			$poll_options_data['poll_id'] = $poll_data->id;
			if ( '0000-00-00 00:00:00' !== $poll_data->vote_disabled_date ) {
				$vote_disabled_date = strtotime( $poll_data->vote_disabled_date );
			} else {
				$vote_disabled_date = intval( bp_core_current_time( true, 'timestamp' ) ) + ( intval( $settings['duration'] ) * DAY_IN_SECONDS );                         // Calculate the future timestamp.
			}
			$poll_data->vote_disabled_date = date( 'Y-m-d H:i:s', $vote_disabled_date );
			// Remove deleted options.
			$option_ids            = array_column( $sanitized_poll_options, 'id' );
			$existing_poll_options = bb_load_polls()->bb_get_poll_options(
				array(
					'poll_id' => $poll_data->id,
					'fields'  => 'id',
				)
			);
			if ( ! empty( $existing_poll_options ) ) {
				$diff_option_ids = array_diff( $existing_poll_options, $option_ids );
				if ( ! empty( $diff_option_ids ) ) {
					foreach ( $diff_option_ids as $option_id ) {
						bb_load_polls()->bb_remove_poll_options(
							array(
								'id'      => $option_id,
								'poll_id' => $poll_data->id,
							)
						);
					}
				}
			}

			$fetch_poll_data              = array();
			if ( ! empty( $sanitized_poll_options ) ) {
				// Add/Update options.
				foreach ( $sanitized_poll_options as $key => $option ) {
					$poll_options_data['id']              = $option['id'];
					$poll_options_data['option_title']    = $option['option_title'];
					$poll_options_data['option_order']    = ! empty( $option['option_order'] ) ? $option['option_order'] : $key;
					$poll_options_data['user_id']         = ! empty( $poll_id ) ? $poll_data->user_id : bp_loggedin_user_id();
					$updated_poll_option                  = bb_load_polls()->bb_update_poll_option( $poll_options_data );
					$current_poll_option                  = ! empty( $updated_poll_option[0] ) ? current( $updated_poll_option ) : array();
					$fetch_poll_data[ $key ]              = $current_poll_option;
					$fetch_poll_data[ $key ]['user_data'] = array(
						'username'    => bp_core_get_user_displayname( $current_poll_option['user_id'] ),
						'user_domain' => bp_core_get_user_domain( $current_poll_option['user_id'] ),
					);
				}
			}

			if ( ! empty( $fetch_poll_data ) ) {
				$poll_data->options = $fetch_poll_data;
			}
		}

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $poll_data, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a poll is updated via the REST API.
		 *
		 * @since 2.6.00
		 *
		 * @param BB_Polls         $poll     Updated poll.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */

		do_action( 'bp_rest_polls_update_item', $poll, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to update a poll.
	 *
	 * @since 2.6.00
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|bool
	 */
	public function update_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to update this poll.', 'buddyboss-pro' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
			$poll   = $this->get_poll_object( $request );

			if ( empty( $poll->id ) ) {
				$retval = new WP_Error(
					'bp_rest_poll_invalid_id',
					__( 'Invalid poll ID.', 'buddyboss-pro' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( bp_loggedin_user_id() !== (int) $poll->user_id && empty( $poll->secondary_item_id ) ) {
				$retval = new WP_Error(
					'bp_rest_poll_update_authorization_required',
					__( 'You are not authorized to update this poll option. Only the poll creator can update it.', 'buddyboss-pro' ),
					array(
						'status' => 403,
					)
				);
			} elseif ( ! empty( $poll->secondary_item_id ) && bp_is_active( 'groups' ) ) {
				if ( ! bb_is_enabled_activity_post_polls( false ) ) {
					$retval = new WP_Error(
						'bp_rest_poll_update_activity_disabled',
						__( 'Activity post polls are not enabled. Please enable them to update this poll.', 'buddyboss-pro' ),
						array(
							'status' => 403,
						)
					);
				} elseif (
					! (
						groups_is_user_admin( bp_loggedin_user_id(), $poll->secondary_item_id ) ||
						groups_is_user_mod( bp_loggedin_user_id(), $poll->secondary_item_id )
					)
				) {
					$retval = new WP_Error(
						'bp_rest_poll_update_group_permission_required',
						__( 'You must be an admin or a moderator of the group to update this poll.', 'buddyboss-pro' ),
						array(
							'status' => 403,
						)
					);
				}
			}
		}

		/**
		 * Filter the polls `update_item` permissions check.
		 *
		 * @since 2.6.00
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_polls_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Delete a poll.
	 *
	 * @since          2.6.00
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @api            {DELETE} /wp-json/buddyboss/v1/poll/:poll_id Delete Poll
	 * @apiName        DeleteBBPoll
	 * @apiGroup       Polls
	 * @apiDescription Delete a poll.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the Poll.
	 */
	public function delete_item( $request ) {
		// Setting context.
		$request->set_param( 'context', 'edit' );

		// Get the poll before it's deleted.
		$poll     = $this->get_poll_object( $request );
		$previous = $this->prepare_item_for_response( $poll, $request );

		if ( ! bb_load_polls()->bb_remove_poll( $poll->id ) ) {
			return new WP_Error(
				'bp_rest_poll_cannot_delete',
				__( 'Could not delete the poll.', 'buddyboss-pro' ),
				array(
					'status' => 500,
				)
			);
		}

		// Build the response.
		$response = new WP_REST_Response();
		$response->set_data(
			array(
				'deleted'  => true,
				'previous' => $previous->get_data(),
			)
		);

		/**
		 * Fires after a poll is deleted via the REST API.
		 *
		 * @since 2.6.00
		 *
		 * @param object           $poll     The deleted poll.
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  The request sent to the API.
		 */
		do_action( 'bp_rest_polls_delete_item', $poll, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to delete a poll.
	 *
	 * @since 2.6.00
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function delete_item_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you need to be logged in to delete this poll.', 'buddyboss-pro' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
			$poll   = $this->get_poll_object( $request );

			if ( empty( $poll->id ) ) {
				$retval = new WP_Error(
					'bp_rest_poll_invalid_id',
					__( 'Invalid poll ID.', 'buddyboss-pro' ),
					array(
						'status' => 403,
					)
				);
			} elseif ( bp_loggedin_user_id() !== (int) $poll->user_id && empty( $poll->secondary_item_id ) ) {
				$retval = new WP_Error(
					'bp_rest_poll_delete_authorization_required',
					__( 'You are not authorized to delete this poll. Only the poll creator can delete it.', 'buddyboss-pro' ),
					array(
						'status' => 403,
					)
				);
			} elseif ( ! empty( $poll->secondary_item_id ) && bp_is_active( 'groups' ) ) {
				if ( ! bb_is_enabled_activity_post_polls( false ) ) {
					$retval = new WP_Error(
						'bp_rest_poll_delete_activity_disabled',
						__( 'Activity post polls are not enabled. Please enable them to update this poll.', 'buddyboss-pro' ),
						array(
							'status' => 403,
						)
					);
				} elseif (
					! (
						groups_is_user_admin( bp_loggedin_user_id(), $poll->secondary_item_id ) ||
						groups_is_user_mod( bp_loggedin_user_id(), $poll->secondary_item_id )
					)
				) {
					$retval = new WP_Error(
						'bp_rest_poll_delete_group_permission_required',
						__( 'You must be an admin or a moderator of the group to update this poll.', 'buddyboss-pro' ),
						array(
							'status' => 403,
						)
					);
				}
			}
		}

		/**
		 * Filter the polls `delete_item` permissions check.
		 *
		 * @since 2.6.00
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_polls_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Prepare a single poll output for response.
	 *
	 * @since 2.6.00
	 *
	 * @param object          $item    Poll object.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $item, $request ) {
		$data = array(
			'id'                 => $item->id,
			'item_id'            => $item->item_id,
			'item_type'          => $item->item_type,
			'secondary_item_id'  => $item->secondary_item_id,
			'user_id'            => $item->user_id,
			'question'           => $item->question,
			'settings'           => maybe_unserialize( $item->settings ),
			'options'            => array(),
			'date_recorded'      => function_exists( 'bp_rest_prepare_date_response' ) ? bp_rest_prepare_date_response( $item->date_recorded ) : $item->date_recorded,
			'date_updated'       => function_exists( 'bp_rest_prepare_date_response' ) ? bp_rest_prepare_date_response( $item->date_updated ) : $item->date_updated,
			'vote_disabled_date' => function_exists( 'bp_rest_prepare_date_response' ) ? bp_rest_prepare_date_response( $item->vote_disabled_date ) : $item->vote_disabled_date,
			'status'             => $item->status,
		);

		$data['total_votes'] = bb_load_polls()->bb_get_poll_option_vote_count(
			array(
				'poll_id' => $item->id,
			)
		);

		$data['user_permissions'] = $this->get_poll_user_permissions( $item, $request );

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		$object = new WP_REST_Request();
		$object->set_param( 'id', $item->id );
		$object->set_param( 'context', $context );

		$options = $this->poll_option_endpoint->get_poll_options( $object );

		foreach ( $options as $option ) {
			$data['options'][] = $this->poll_option_endpoint->prepare_response_for_collection(
				$this->poll_option_endpoint->prepare_item_for_response( $option, $object )
			);
		}

		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $item ) );

		/**
		 * Filter a poll value returned from the API.
		 *
		 * @since 2.6.00
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 * @param BB_Polls         $item     Poll object.
		 */
		return apply_filters( 'bp_rest_polls_prepare_value', $response, $request, $item );
	}

	/**
	 * Get poll permissions based on current user.
	 *
	 * @since 2.6.00
	 *
	 * @param Object          $poll    The Poll data.
	 * @param WP_REST_Request $request The request data.
	 *
	 * @return array
	 */
	public function get_poll_user_permissions( $poll, $request ) {
		$retval   = array(
			'edit'   => false,
			'delete' => false,
		);
		$group_id = ! empty( $poll->secondary_item_id ) ? $poll->secondary_item_id : $request['secondary_item_id'];
		if ( is_user_logged_in() && bp_loggedin_user_id() === (int) $poll->user_id ) {
			if ( bp_current_user_can( 'administrator' ) && empty( $group_id ) ) {
				$retval['edit']   = true;
				$retval['delete'] = true;
			} elseif (
				bp_is_active( 'groups' ) &&
				! empty( $group_id ) &&
				(
					groups_is_user_admin( bp_loggedin_user_id(), $group_id ) ||
					groups_is_user_mod( bp_loggedin_user_id(), $group_id )
				)
			) {
				$retval['edit']   = true;
				$retval['delete'] = true;
			}
		}

		return $retval;
	}

	/**
	 * Prepare links for the request.
	 *
	 * @since 2.6.00
	 *
	 * @param object $poll Poll object.
	 *
	 * @return array
	 */
	protected function prepare_links( $poll ) {
		$base = sprintf( '/%s/%s/', $this->namespace, $this->rest_base );

		// Entity meta.
		$links = array(
			'self' => array(
				'href' => rest_url( $base . $poll->id ),
			),
			'user' => array(
				'href'       => rest_url( bp_rest_get_user_url( $poll->user_id ) ),
				'embeddable' => true,
			),
		);

		/**
		 * Filter links prepared for the REST response.
		 *
		 * @since 2.6.00
		 *
		 * @param array    $links The prepared links of the REST response.
		 * @param BB_Polls $poll  Poll object.
		 */
		return apply_filters( 'bp_rest_polls_prepare_links', $links, $poll );
	}

	/**
	 * Get poll object.
	 *
	 * @since 2.6.00
	 *
	 * @param WP_REST_Request|int $request Full details about the request.
	 *
	 * @return object
	 */
	public function get_poll_object( $request ) {
		if ( is_numeric( $request ) ) {
			$poll_id = $request;
		} elseif ( ! empty( $request->get_param( 'poll_id' ) ) ) {
				$poll_id = $request->get_param( 'poll_id' );
		} else {
			$poll_id = $request->get_param( 'id' );
		}
		$poll = bb_load_polls()->bb_get_poll( $poll_id );

		if ( empty( $poll ) || empty( $poll->id ) ) {
			return new stdClass();
		}

		return $poll;
	}

	/**
	 * Edit some arguments for the endpoint's CREATABLE and EDITABLE methods.
	 *
	 * @since 2.6.00
	 *
	 * @param string $method Optional. HTTP method of the request.
	 *
	 * @return array Endpoint arguments.
	 */
	public function get_endpoint_args_for_item_schema( $method = WP_REST_Server::CREATABLE ) {
		$key  = 'get_item';
		$args = WP_REST_Controller::get_endpoint_args_for_item_schema( $method );

		if ( WP_REST_Server::CREATABLE === $method || WP_REST_Server::EDITABLE === $method ) {
			$key = 'create_item';

			$args['question'] = array(
				'description'       => __( 'Question.', 'buddyboss-pro' ),
				'required'          => true,
				'type'              => 'string',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$args['settings_allow_multiple_options'] = array(
				'description'       => __( 'Allow multiple selections.', 'buddyboss-pro' ),
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$args['settings_allow_new_option'] = array(
				'description'       => __( 'Allow users to add options.', 'buddyboss-pro' ),
				'type'              => 'boolean',
				'sanitize_callback' => 'rest_sanitize_boolean',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$args['settings_duration'] = array(
				'description'       => __( 'Duration of the poll.', 'buddyboss-pro' ),
				'default'           => 3,
				'type'              => 'integer',
				'enum'              => array( 1, 3, 7, 14 ),
				'sanitize_callback' => 'sanitize_key',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$args['options'] = array(
				'description'       => __( 'Poll options.', 'buddyboss-pro' ),
				'type'              => 'array',
				'minItems'          => 2,
				'maxItems'          => 10,
				'validate_callback' => 'rest_validate_request_arg',
			);
		}

		if ( WP_REST_Server::EDITABLE === $method ) {
			$key = 'update_item';

			$args['id']                   = array(
				'description' => __( 'A unique numeric ID for the poll.', 'buddyboss-pro' ),
				'type'        => 'integer',
				'required'    => true,
			);
			$args['question']['required'] = true;
		}

		/**
		 * Filters the method query arguments.
		 *
		 * @since 2.6.00
		 *
		 * @param array  $args   Query arguments.
		 * @param string $method HTTP method of the request.
		 */
		return apply_filters( "bp_rest_poll_{$key}_query_arguments", $args, $method );
	}

	/**
	 * Get the poll schema, conforming to JSON Schema.
	 *
	 * @since 2.6.00
	 * @return array
	 */
	public function get_item_schema() {
		$option_schema = $this->poll_option_endpoint->get_item_schema();

		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_polls',
			'type'       => 'object',
			'properties' => array(
				'id'                 => array(
					'context'     => array( 'embed', 'view', 'edit' ),
					'description' => __( 'A unique numeric ID for the Poll.', 'buddyboss-pro' ),
					'readonly'    => true,
					'type'        => 'integer',
				),
				'item_id'            => array(
					'description' => __( 'Item ID.', 'buddyboss-pro' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'item_type'          => array(
					'description' => __( 'Item Type.', 'buddyboss-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'secondary_item_id'  => array(
					'description' => __( 'Secondary Item ID.', 'buddyboss-pro' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'user_id'            => array(
					'description' => __( 'User ID who created poll.', 'buddyboss-pro' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'question'           => array(
					'description' => __( 'The question for the poll.', 'buddyboss-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'settings'           => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'Settings for the poll.', 'buddyboss-pro' ),
					'type'        => 'object',
					'readonly'    => true,
					'arg_options' => array(
						'sanitize_callback' => null,
						// Note: sanitization implemented in self::prepare_item_for_database().
						'validate_callback' => null,
						// Note: validation implemented in self::prepare_item_for_database().
					),
					'properties'  => array(
						'allow_multiple_options' => array(
							'description' => __( 'Allow multiple selections.', 'buddyboss-pro' ),
							'type'        => 'boolean',
							'context'     => array( 'view', 'edit' ),
						),
						'allow_new_option'       => array(
							'description' => __( 'Allow users to add options.', 'buddyboss-pro' ),
							'type'        => 'boolean',
							'context'     => array( 'view', 'edit' ),
						),
						'duration'               => array(
							'description' => __( 'Duration of the poll.', 'buddyboss-pro' ),
							'type'        => 'integer',
							'enums'       => array( 1, 3, 7, 14 ),
						),
					),
				),
				'options'            => array(
					'description' => __( 'The options for the poll.', 'buddyboss-pro' ),
					'type'        => 'array',
					'context'     => array( 'view', 'edit' ),
					'items'       => array(
						'type'       => 'array',
						'properties' => $option_schema['properties'],
					),
				),
				'date_recorded'      => array(
					'description' => __( 'The date the option was recorded.', 'buddyboss-pro' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_updated'       => array(
					'description' => __( 'The date the option was updated.', 'buddyboss-pro' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'vote_disabled_date' => array(
					'description' => __( 'Vote disabled date for poll.', 'buddyboss-pro' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'status'             => array(
					'description' => __( 'The status of the poll.', 'buddyboss-pro' ),
					'type'        => 'string',
					'enum'        => array( 'draft', 'published', 'scheduled' ),
					'context'     => array( 'view', 'edit' ),
					'default'     => 'draft',
				),
				'total_votes'        => array(
					'description' => __( 'Total votes for poll.', 'buddyboss-pro' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'user_permissions'   => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'Current user\'s permission with the media.', 'buddyboss-pro' ),
					'readonly'    => true,
					'type'        => 'object',
				),
			),
		);

		/**
		 * Filters the poll schema.
		 *
		 * @since 2.6.00
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_poll_schema', $this->add_additional_fields_schema( $schema ) );
	}

	/**
	 * Register custom field for the activity api.
	 *
	 * @since 2.6.00
	 */
	public function bb_rest_poll_support() {
		bp_rest_register_field(
			'activity',
			'bb_poll',
			array(
				'get_callback' => array( $this, 'bb_poll_id_get_rest_field_callback' ),
				'schema'       => array(
					'description' => 'Activity Poll Data.',
					'type'        => 'array',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
			)
		);

		bp_rest_register_field(
			'activity',
			'bb_poll_id',
			array(
				'update_callback' => array( $this, 'bb_poll_id_update_rest_field_callback' ),
				'schema'          => array(
					'description' => 'Activity Poll ID.',
					'type'        => 'array',
					'context'     => array( 'embed', 'view', 'edit' ),
				),
			)
		);
	}

	/**
	 * Filter Query argument for the activity for poll support.
	 *
	 * @since 2.6.00
	 *
	 * @param array  $args   Query arguments.
	 * @param string $method HTTP method of the request.
	 *
	 * @return array
	 */
	public function bb_rest_activity_query_arguments( $args, $method ) {

		$args['bb_poll_id'] = array(
			'description'       => __( 'Activity poll id.', 'buddyboss-pro' ),
			'type'              => 'array',
			'items'             => array( 'type' => 'integer' ),
			'sanitize_callback' => 'wp_parse_id_list',
			'validate_callback' => 'rest_validate_request_arg',
		);

		return $args;
	}

	/**
	 * The function to use to get a poll of the activity REST Field.
	 *
	 * @since 2.6.00
	 *
	 * @param BP_Activity_Activity $activity  Activity Array.
	 * @param string               $attribute The REST Field key used into the REST response.
	 *
	 * @return array|false|void   The value of the REST Field to include in the REST response.
	 */
	public function bb_poll_id_get_rest_field_callback( $activity, $attribute ) {
		$activity_id = $activity['id'];

		if ( empty( $activity_id ) ) {
			return;
		}

		$value = new BP_Activity_Activity( $activity_id );

		$group_id = 0;
		if ( 'groups' === $value->component ) {
			$group_id = $value->item_id;
		}

		if (
			bp_is_active( 'groups' ) &&
			! empty( $group_id ) &&
			! bb_is_enabled_activity_post_polls( false )
		) {
			return false;
		}

		$activity_metas = bb_activity_get_metadata( $activity_id );

		$poll_id = $activity_metas['bb_poll_id'][0] ?? '';

		if ( empty( $poll_id ) ) {
			return;
		}

		$poll = bb_load_polls()->bb_get_poll( $poll_id );

		$retval = array();
		$object = new WP_REST_Request();
		$object->set_param( 'context', 'view' );

		$retval[] = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $poll, $object )
		);

		return $retval;
	}

	/**
	 * The function to use to update the poll id's value of the activity REST Field.
	 *
	 * @since 2.6.00
	 *
	 * @param int    $object     The BuddyPress component's object that was just created/updated during the request.
	 *                           (in this case, the BP_Activity_Activity object).
	 * @param object $value      The value of the REST Field to save.
	 * @param string $attribute  The REST Field key used into the REST response.
	 *
	 * @return false|void
	 */
	protected function bb_poll_id_update_rest_field_callback( $object, $value, $attribute ) {
		// Bail if the value is empty.
		if ( empty( $value ) ) {
			return;
		}

		if ( empty( $object ) ) {
			return false;
		}

		global $bp_activity_edit;

		// Get the poll ID.
		$poll_id = (int) $object;

		$get_poll = bb_load_polls()->bb_get_poll( $poll_id );

		if ( empty( $get_poll ) ) {
			return false;
		}

		$activity_poll_duration = bb_poll_get_duration( $get_poll );
		$activity_recorded      = strtotime( $value->date_recorded );                                                                            // Get the current timestamp in UTC.
		$future_timestamp       = intval( $activity_recorded ) + ( intval( $activity_poll_duration ) * DAY_IN_SECONDS );                         // Calculate the future timestamp.
		$translated_date        = gmdate( 'Y-m-d H:i:s', $future_timestamp );

		$args = array(
			'id'                 => $poll_id,
			'item_id'            => $value->id,
			'item_type'          => 'activity',
			'vote_disabled_date' => $translated_date,
			'status'             => $value->status,
		);
		if ( isset( $value->component ) && 'groups' === $value->component ) {
			$args['secondary_item_id'] = $value->item_id;
		}
		if ( $bp_activity_edit ) {
			$args['user_id'] = $get_poll->user_id;
		}
		bb_load_polls()->bb_update_poll( $args );

		// update poll id here in the activity meta.
		bp_activity_update_meta( $value->id, $attribute, $poll_id );
	}
}
