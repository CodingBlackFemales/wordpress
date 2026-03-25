<?php
/**
 * BB REST: BB_REST_Poll_Option_Endpoint class
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
class BB_REST_Poll_Option_Endpoint extends WP_REST_Controller {

	/**
	 * Allow batch.
	 *
	 * @var true[] $allow_batch
	 */
	protected $allow_batch = array( 'v1' => true );

	/**
	 * Constructor.
	 *
	 * @since 2.6.00
	 */
	public function __construct() {
		$this->namespace = bp_rest_namespace() . '/' . bp_rest_version();
		$this->rest_base = 'options';
	}

	/**
	 * Register the component routes.
	 *
	 * @since 2.6.00
	 */
	public function register_routes() {
		$poll_endpoint = '/poll/(?P<id>[\d]+)';

		register_rest_route(
			$this->namespace,
			$poll_endpoint . '/' . $this->rest_base,
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( $this, 'get_items' ),
					'permission_callback' => array( $this, 'get_items_permissions_check' ),
					'args'                => $this->get_collection_params(),
				),
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
			$poll_endpoint . '/' . $this->rest_base . '/(?P<option_id>[\d]+)',
			array(
				'args'        => array(
					'id'        => array(
						'description' => __( 'A unique numeric ID for the Poll.', 'buddyboss-pro' ),
						'type'        => 'integer',
					),
					'option_id' => array(
						'description' => __( 'A unique numeric ID for the Poll option.', 'buddyboss-pro' ),
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
	 * Retrieve a poll options.
	 *
	 * @since 2.6.00
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @api            {GET} /wp-json/buddyboss/v1/poll/:poll_id/options Get Options from a poll.
	 * @apiName        GetBBPollOptions
	 * @apiGroup       Polls
	 * @apiDescription Retrieve all poll option
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser.
	 * @apiParam {Number} id A unique numeric ID for the Poll.
	 */
	public function get_items( $request ) {
		$poll_id = $request->get_param( 'id' );
		$poll    = bb_load_polls()->bb_get_poll( $poll_id );

		if ( ! $poll ) {
			return new WP_Error(
				'bp_rest_poll_invalid',
				__( 'Invalid poll.', 'buddyboss-pro' ),
				array(
					'status' => 404,
				)
			);
		}

		$poll_options = $this->get_poll_options( $request );

		$retval = array();

		foreach ( $poll_options as $options ) {
			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $options, $request )
			);
		}

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a poll options is fetched via the REST API.
		 *
		 * @since 2.6.00
		 *
		 * @param array            $poll_options Fetched Poll options.
		 * @param WP_REST_Response $response     The response data.
		 * @param WP_REST_Request  $request      The request sent to the API.
		 */
		do_action( 'bp_rest_poll_options_get_items', $poll_options, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to get information about poll options.
	 *
	 * @since 2.6.00
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_Error|bool
	 */
	public function get_items_permissions_check( $request ) {
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

		$poll = bb_load_polls()->bb_get_poll( $request->get_param( 'id' ) );

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
		 * Filter the poll options `get_items` permissions check.
		 *
		 * @since 2.6.00
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_poll_options_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Create a poll option.
	 *
	 * @since 2.6.00
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @api            {POST} /wp-json/buddyboss/v1/poll/poll_id/options Create Poll Option.
	 * @apiName        CreatePollOption
	 * @apiGroup       Poll
	 * @apiDescription Create a poll option.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the poll.
	 * @apiParam {String} option_title The title for the option.
	 * @apiParam {String} option_order. The order for the option.
	 * @apiParam {Number} user_id Logged in user ID.
	 */
	public function create_item( $request ) {
		$poll_id      = $request->get_param( 'id' );
		$option_title = $request->get_param( 'option_title' );

		if ( empty( $option_title ) ) {
			return new WP_Error(
				'bp_rest_poll_invalid_option_title',
				__( 'Invalid poll option title.', 'buddyboss-pro' ),
				array(
					'status' => 400,
				)
			);
		}
		$option_order = $request->get_param( 'option_order' );
		$user_id      = $request->get_param( 'user_id' );
		if ( empty( $user_id ) ) {
			$user_id = bp_loggedin_user_id();
		}

		$updated_option = bb_load_polls()->bb_update_poll_option(
			array(
				'poll_id'      => $poll_id,
				'user_id'      => $user_id,
				'option_title' => $option_title,
				'option_order' => $option_order,
			)
		);

		$current_poll_option = ! empty( $updated_option ) ? current( $updated_option ) : array();

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $current_poll_option, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a poll option item is created via the REST API.
		 *
		 * @since 2.6.00
		 *
		 * @param BB_Polls         $updated_option The created poll option.
		 * @param WP_REST_Response $response       The response data.
		 * @param WP_REST_Request  $request        The request sent to the API.
		 */
		do_action( 'bp_rest_poll_option_create_item', $updated_option, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to create a poll option.
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
			__( 'Sorry, you are not allowed to create an option.', 'buddyboss-pro' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval       = true;
			$poll         = bb_load_polls()->bb_get_poll( $request->get_param( 'id' ) );
			$option_title = sanitize_text_field( wp_unslash( trim( $request->get_param( 'option_title' ) ) ) );

			if ( ! $poll ) {
				$retval = new WP_Error(
					'bp_rest_poll_invalid_id',
					__( 'Invalid poll.', 'buddyboss-pro' ),
					array(
						'status' => 404,
					)
				);
			} else {
				$allow_add_option = bb_poll_allow_new_options( $poll );
				if ( ! $allow_add_option ) {
					$retval = new WP_Error(
						'bp_rest_poll_add_option_not_allowed',
						__( 'Adding new options is not allowed for this poll.', 'buddyboss-pro' ),
						array(
							'status' => 403,
						)
					);
				} elseif ( ! empty( $poll->secondary_item_id ) && bp_is_active( 'groups' ) ) {
					if ( ! bb_is_enabled_activity_post_polls( false ) ) {
						$retval = new WP_Error(
							'bp_rest_poll_setting_disabled',
							__( 'Activity post polls are not enabled.', 'buddyboss-pro' ),
							array(
								'status' => 403,
							)
						);
					} else {
						$group_object = groups_get_group( $poll->secondary_item_id );
						$group_status = bp_get_group_status( $group_object );
						if ( 'public' !== $group_status ) {
							if (
								! (
									groups_is_user_admin( bp_loggedin_user_id(), $poll->secondary_item_id ) ||
									groups_is_user_mod( bp_loggedin_user_id(), $poll->secondary_item_id ) ||
									groups_is_user_member( bp_loggedin_user_id(), $poll->secondary_item_id )
								)
							) {
								$retval = new WP_Error(
									'bp_rest_poll_add_option_not_group_member',
									__( 'You must be an admin, moderator, or member of the group to add new options.', 'buddyboss-pro' ),
									array(
										'status' => 403,
									)
								);
							}
						}
					}
				} elseif ( strlen( $option_title ) > 50 ) {
					$retval = new WP_Error(
						'bp_rest_poll_option_max_length',
						__( 'Poll option must be between 1 and 50 characters long.', 'buddyboss-pro' ),
						array(
							'status' => 403,
						)
					);
				} else {
					$validate_option_title = bb_load_polls()->bb_get_poll_options(
						array(
							'poll_id'      => $poll->id,
							'option_title' => $option_title,
						)
					);
					if ( ! empty( $validate_option_title ) ) {
						$retval = new WP_Error(
							'bp_rest_poll_option_title_exists',
							__( 'This is already an option', 'buddyboss-pro' ),
							array(
								'status' => 403,
							)
						);
					}
				}
			}
		}

		/**
		 * Filter the poll option `create_item` permissions check.
		 *
		 * @since 2.6.00
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_poll_option_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Retrieve a poll option.
	 *
	 * @since 2.6.00
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @api            {GET} /wp-json/buddyboss/v1/poll/:poll_id/options/:option_id Get Option from a poll.
	 * @apiName        GetBBPollOption
	 * @apiGroup       Polls
	 * @apiDescription Retrieve single poll option
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser.
	 * @apiParam {Number} id A unique numeric ID for the Poll.
	 * @apiParam {Number} option_id A unique numeric ID for the Option.
	 */
	public function get_item( $request ) {
		$poll_option = $this->get_poll_options( $request );

		$poll_option = current( $poll_option );

		$retval = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $poll_option, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a poll option is fetched via the REST API.
		 *
		 * @since 2.6.00
		 *
		 * @param array            $poll_option Fetched Poll Option.
		 * @param WP_REST_Response $response    The response data.
		 * @param WP_REST_Request  $request     The request sent to the API.
		 */
		do_action( 'bp_rest_poll_option_get_item', $poll_option, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to get information about a specific poll option.
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

		$poll = bb_load_polls()->bb_get_poll( $request->get_param( 'id' ) );

		if ( empty( $poll->id ) && true === $retval ) {
			$retval = new WP_Error(
				'bp_rest_poll_invalid_id',
				__( 'Invalid poll ID.', 'buddyboss-pro' ),
				array(
					'status' => 404,
				)
			);
		} else {
			$poll_option = $this->get_poll_options( $request );
			if ( empty( $poll_option ) && true === $retval ) {
				$retval = new WP_Error(
					'bp_rest_poll_invalid_option_id',
					__( 'Invalid poll option ID.', 'buddyboss-pro' ),
					array(
						'status' => 404,
					)
				);
			}
		}

		/**
		 * Filter the poll option `get_item` permissions check.
		 *
		 * @since 2.6.00
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_poll_option_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Update a pol option.
	 *
	 * @since 2.6.00
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @api            {PATCH} /wp-json/buddyboss/v1/poll/:poll_id/options/:option_id Update Poll Option.
	 * @apiName        UpdateBBPollOption
	 * @apiGroup       Polls
	 * @apiDescription Update a poll option.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the Poll.
	 * @apiParam {Number} option_id A unique numeric ID for the Option.
	 * @apiParam {String} title The title of the option.
	 * @apiParam {Number} order. The Order of the option.
	 */
	public function update_item( $request ) {

		$poll_option_args = array(
			'id'           => $request->get_param( 'option_id' ),
			'poll_id'      => $request->get_param( 'id' ),
			'option_title' => $request->get_param( 'option_title' ),
			'option_order' => $request->get_param( 'option_order' ),
		);

		$poll_option_args['user_id'] = $request->get_param( 'user_id' );
		if ( empty( $poll_option_args['user_id'] ) ) {
			$poll_option_args['user_id'] = bp_loggedin_user_id();
		}

		$poll_option_data = bb_load_polls()->bb_update_poll_option( $poll_option_args );

		if ( ! $poll_option_data ) {
			return new WP_Error(
				'bp_rest_poll_cannot_update',
				__( 'Could not update the poll option.', 'buddyboss-pro' ),
				array(
					'status' => 500,
				)
			);
		}

		$current_poll_option = ! empty( $poll_option_data ) ? current( $poll_option_data ) : array();
		$retval              = $this->prepare_response_for_collection(
			$this->prepare_item_for_response( $current_poll_option, $request )
		);

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a poll option is updated via the REST API.
		 *
		 * @since 2.6.00
		 *
		 * @param BB_Polls         $poll_option_data Updated poll option.
		 * @param WP_REST_Response $response         The response data.
		 * @param WP_REST_Request  $request          The request sent to the API.
		 */
		do_action( 'bp_rest_poll_option_update_item', $poll_option_data, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to update a poll option.
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
			$retval       = true;
			$poll         = bb_load_polls()->bb_get_poll( $request->get_param( 'id' ) );
			$option_title = $request->get_param( 'option_title' );
			if ( ! empty( $option_title ) ) {
				$option_title = sanitize_text_field( wp_unslash( trim( $option_title ) ) );
			}

			$user_id = $request->get_param( 'user_id' );
			if ( empty( $user_id ) ) {
				$user_id = bp_loggedin_user_id();
			}

			if ( ! $poll ) {
				return new WP_Error(
					'bp_rest_poll_invalid_id',
					__( 'Invalid poll.', 'buddyboss-pro' ),
					array(
						'status' => 404,
					)
				);
			} elseif ( ! empty( $poll->secondary_item_id ) && bp_is_active( 'groups' ) && ! bb_is_enabled_activity_post_polls( false ) ) {
				$retval = new WP_Error(
					'bp_rest_poll_setting_disabled',
					__( 'Activity post polls are not enabled.', 'buddyboss-pro' ),
					array(
						'status' => 403,
					)
				);
			} elseif ( $user_id !== (int) $poll->user_id ) {
				$retval = new WP_Error(
					'bp_rest_poll_update_not_authorized',
					__( 'You are not authorized to update this poll option. Only the poll creator can update it.', 'buddyboss-pro' ),
					array(
						'status' => 403,
					)
				);
			} elseif ( ! empty( $option_title ) && strlen( $option_title ) > 50 ) {
				$retval = new WP_Error(
					'bp_rest_poll_option_max_length',
					__( 'Poll option must be between 1 and 50 characters long.', 'buddyboss-pro' ),
					array(
						'status' => 403,
					)
				);
			} else {
				$poll_option = $this->get_poll_options( $request );
				$poll_option = ! empty( $poll_option ) ? current( $poll_option ) : array();

				if ( empty( $poll_option ) ) {
					$retval = new WP_Error(
						'bp_rest_poll_invalid_option_id',
						__( 'Invalid poll option ID.', 'buddyboss-pro' ),
						array(
							'status' => 404,
						)
					);
				} elseif ( $user_id !== (int) $poll_option['user_id'] ) {
					$retval = new WP_Error(
						'bp_rest_poll_update_option_not_authorized',
						__( 'You can not update this poll option.', 'buddyboss-pro' ),
						array(
							'status' => 403,
						)
					);
				} elseif ( $option_title === $poll_option['option_title'] ) {
					$retval = new WP_Error(
						'bp_rest_poll_option_title_exists',
						__( 'This is already an option', 'buddyboss-pro' ),
						array(
							'status' => 403,
						)
					);
				} elseif ( 0 !== $poll_option['total_votes'] ) {
					$retval = new WP_Error(
						'bp_rest_poll_update_option_has_votes',
						__( 'You cannot update this poll option because it has votes.', 'buddyboss-pro' ),
						array(
							'status' => 403,
						)
					);
				}
			}
		}

		/**
		 * Filter the poll option `update_item` permissions check.
		 *
		 * @since 2.6.00
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_poll_option_update_item_permissions_check', $retval, $request );
	}

	/**
	 * Delete a poll option.
	 *
	 * @since 2.6.00
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @api            {DELETE} /wp-json/buddyboss/v1/poll/:poll_id/options/:option_id Delete Poll Option.
	 * @apiName        DeleteBBPollOption
	 * @apiGroup       Polls
	 * @apiDescription Delete a poll option.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the Poll.
	 * @apiParam {Number} option_id A unique numeric ID for the Option.
	 */
	public function delete_item( $request ) {
		// Setting context.
		$request->set_param( 'context', 'edit' );

		// Get the option before it's deleted.
		$poll_option = $this->get_poll_options( $request );
		$poll_option = ! empty( $poll_option ) ? current( $poll_option ) : array();
		$previous    = $this->prepare_item_for_response( $poll_option, $request );

		if (
			! bb_load_polls()->bb_remove_poll_options(
				array(
					'id'      => $request->get_param( 'option_id' ),
					'poll_id' => $request->get_param( 'id' ),
				)
			)
		) {
			return new WP_Error(
				'bp_rest_poll_option_cannot_delete',
				__( 'Could not delete the poll option.', 'buddyboss-pro' ),
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
		 * Fires after a poll option is deleted via the REST API.
		 *
		 * @since 2.6.00
		 *
		 * @param array            $poll_option Fetched Poll Option.
		 * @param WP_REST_Response $response    The response data.
		 * @param WP_REST_Request  $request     The request sent to the API.
		 */
		do_action( 'bp_rest_poll_option_delete_item', $poll_option, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to delete a poll option.
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
			__( 'Sorry, you need to be logged in to delete this poll option.', 'buddyboss-pro' ),
			array(
				'status' => rest_authorization_required_code(),
			)
		);

		if ( is_user_logged_in() ) {
			$retval = true;
			$poll   = bb_load_polls()->bb_get_poll( $request->get_param( 'id' ) );

			if ( empty( $poll->id ) ) {
				$retval = new WP_Error(
					'bp_rest_poll_invalid_id',
					__( 'Invalid poll ID.', 'buddyboss-pro' ),
					array(
						'status' => 404,
					)
				);
			} else {
				$poll_option = $this->get_poll_options( $request );
				$poll_option = ! empty( $poll_option ) ? current( $poll_option ) : array();

				if ( empty( $poll_option ) ) {
					$retval = new WP_Error(
						'bp_rest_poll_invalid_option_id',
						__( 'Invalid poll option ID.', 'buddyboss-pro' ),
						array(
							'status' => 404,
						)
					);
				} elseif ( ! empty( $poll->secondary_item_id ) && bp_is_active( 'groups' ) && ! bb_is_enabled_activity_post_polls( false ) ) {
					$retval = new WP_Error(
						'bp_rest_poll_setting_disabled',
						__( 'Activity post polls are not enabled.', 'buddyboss-pro' ),
						array(
							'status' => 403,
						)
					);
				} elseif (
					bp_loggedin_user_id() !== (int) $poll_option['user_id'] &&
					bp_loggedin_user_id() !== $poll->user_id
				) {
					$retval = new WP_Error(
						'bp_rest_poll_option_delete_not_authorized',
						__( 'You are not authorized to delete this poll option. Only the option creator can delete it.', 'buddyboss-pro' ),
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
		return apply_filters( 'bp_rest_poll_option_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Prepare a single poll option output for response.
	 *
	 * @since 2.6.00
	 *
	 * @param array           $item    Poll option object.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $item, $request ) {
		$data = array(
			'id'            => ! empty( $item['id'] ) ? $item['id'] : 0,
			'poll_id'       => ! empty( $item['poll_id'] ) ? $item['poll_id'] : 0,
			'user_id'       => ! empty( $item['user_id'] ) ? $item['user_id'] : 0,
			'option_title'  => ! empty( $item['option_title'] ) ? $item['option_title'] : '',
			'option_order'  => ! empty( $item['option_order'] ) ? $item['option_order'] : 0,
			'date_recorded' => ! empty( $item['date_recorded'] ) ? $item['date_recorded'] : '',
			'date_updated'  => ! empty( $item['date_updated'] ) ? $item['date_updated'] : '',
			'total_votes'   => ! empty( $item['total_votes'] ) ? $item['total_votes'] : 0,
			'user_data'     => ! empty( $item['user_data'] ) ? $item['user_data'] : array(
				'username'    => '',
				'user_domain' => '',
			),
			'is_selected'   => ! empty( $item['is_selected'] ) ? $item['is_selected'] : 0,
			'vote_id'       => ! empty( $item['vote_id'] ) ? $item['vote_id'] : 0,
		);

		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $data ) );

		/**
		 * Filter a poll option value returned from the API.
		 *
		 * @since 2.6.00
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 * @param BB_Polls         $item     Poll object.
		 */
		return apply_filters( 'bp_rest_poll_option_prepare_value', $response, $request, $item );
	}

	/**
	 * Prepare links for the option request.
	 *
	 * @since 2.6.00
	 *
	 * @param array $poll_option Poll option.
	 *
	 * @return array
	 */
	protected function prepare_links( $poll_option ) {
		$parent_base = sprintf( '/%s/%s/', $this->namespace, 'poll' );
		$base        = sprintf( '/%s/', $this->rest_base );

		// Entity meta.
		$links = array(
			'self' => array(
				array(
					'href' => rest_url( $parent_base . $poll_option['poll_id'] . $base . $poll_option['id'] ),
				),
			),
			'poll' => array(
				array(
					'href' => rest_url( $parent_base . $poll_option['poll_id'] ),
				),
			),
			'user' => array(
				array(
					'embeddable' => true,
					'href'       => rest_url( $this->namespace . '/members/' . $poll_option['user_id'] ),
				),
			),
		);

		/**
		 * Filter links prepared for the REST response.
		 *
		 * @since 2.6.00
		 *
		 * @param array $links       The prepared links of the REST response.
		 * @param array $poll_option Poll option array.
		 */
		return apply_filters( 'bp_rest_poll_option_prepare_links', $links, $poll_option );
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
		$args = WP_REST_Controller::get_endpoint_args_for_item_schema( $method );
		$key  = 'get_item';

		if ( WP_REST_Server::CREATABLE === $method ) {
			$key = 'create_item';

			$args['id'] = array(
				'description'       => __( 'Poll ID.', 'buddyboss-pro' ),
				'required'          => true,
				'type'              => 'integer',
				'validate_callback' => 'rest_validate_request_arg',
			);
		}

		if ( WP_REST_Server::EDITABLE === $method ) {
			$key = 'update_item';

			$args['id'] = array(
				'description'       => __( 'Poll ID.', 'buddyboss-pro' ),
				'required'          => true,
				'type'              => 'integer',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$args['option_id'] = array(
				'description'       => __( 'Option ID.', 'buddyboss-pro' ),
				'required'          => true,
				'type'              => 'integer',
				'validate_callback' => 'rest_validate_request_arg',
			);
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
	 * Fetch the poll options.
	 *
	 * @since 2.6.00
	 *
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return array
	 */
	public function get_poll_options( $request ) {
		$poll_id   = $request->get_param( 'id' );
		$option_id = $request->get_param( 'option_id' );
		$order_by  = $request->get_param( 'orderby' );
		$order     = $request->get_param( 'order' );

		$args = array(
			'poll_id' => $poll_id,
		);

		if ( ! empty( $option_id ) ) {
			$args['id'] = $option_id;
		}

		if ( ! empty( $order_by ) ) {
			$args['order_by'] = $order_by;
		}

		if ( ! empty( $order ) ) {
			$args['order'] = $order;
		}

		return bb_load_polls()->bb_get_poll_options( $args );
	}

	/**
	 * Get the query params for collections of plugins.
	 *
	 * @since 2.6.00
	 * @return array
	 */
	public function get_collection_params() {
		$params                       = parent::get_collection_params();
		$params['context']['default'] = 'view';

		// Removing unused params.
		unset( $params['search'], $params['page'], $params['per_page'] );

		$params['id'] = array(
			'description' => __( 'A unique numeric ID for the poll.', 'buddyboss-pro' ),
			'type'        => 'integer',
			'required'    => true,
		);

		$params['order'] = array(
			'description'       => __( 'Order sort attribute ascending or descending.', 'buddyboss-pro' ),
			'default'           => 'asc',
			'type'              => 'string',
			'enum'              => array( 'asc', 'desc' ),
			'sanitize_callback' => 'sanitize_key',
			'validate_callback' => 'rest_validate_request_arg',
		);

		$params['orderby'] = array(
			'description'       => __( 'Order by a specific parameter.', 'buddyboss-pro' ),
			'default'           => 'id',
			'type'              => 'string',
			'enum'              => array( 'id', 'option_title', 'option_order', 'date_recorded', 'date_updated' ),
			'sanitize_callback' => 'sanitize_key',
		);

		/**
		 * Filters the collection query params.
		 *
		 * @since 2.6.00
		 *
		 * @param array $params Query params.
		 */
		return apply_filters( 'bp_rest_poll_options_collection_params', $params );
	}

	/**
	 * Get the poll option schema, conforming to JSON Schema.
	 *
	 * @since 2.6.00
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_poll_options',
			'type'       => 'object',
			'properties' => array(
				'id'            => array(
					'description' => __( 'Unique identifier for the option.', 'buddyboss-pro' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'poll_id'       => array(
					'description' => __( 'ID of the poll this option belongs to.', 'buddyboss-pro' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'user_id'       => array(
					'description' => __( 'ID of the user who created the option.', 'buddyboss-pro' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'option_title'  => array(
					'description' => __( 'The title of the option.', 'buddyboss-pro' ),
					'type'        => 'string',
					'context'     => array( 'view', 'edit' ),
				),
				'option_order'  => array(
					'description' => __( 'Order of the option.', 'buddyboss-pro' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'date_recorded' => array(
					'description' => __( 'The date the option was recorded.', 'buddyboss-pro' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'date_updated'  => array(
					'description' => __( 'The date the option was last updated.', 'buddyboss-pro' ),
					'type'        => 'string',
					'format'      => 'date-time',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'total_votes'   => array(
					'description' => __( 'The total votes for poll option.', 'buddyboss-pro' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'user_data'     => array(
					'context'     => array( 'view', 'edit' ),
					'description' => __( 'Option user data.', 'buddyboss-pro' ),
					'type'        => 'object',
					'readonly'    => true,
					'arg_options' => array(
						'sanitize_callback' => null,
						// Note: sanitization implemented in self::prepare_item_for_database().
						'validate_callback' => null,
						// Note: validation implemented in self::prepare_item_for_database().
					),
					'properties'  => array(
						'username'    => array(
							'description' => __( 'Poll option username.', 'buddyboss-pro' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
						'user_domain' => array(
							'description' => __( 'Poll option user link.', 'buddyboss-pro' ),
							'type'        => 'string',
							'context'     => array( 'view', 'edit' ),
						),
					),
				),
				'is_selected'   => array(
					'description' => __( 'Whether to check user voted the option or not.', 'buddyboss-pro' ),
					'type'        => 'boolean',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'vote_id'       => array(
					'description' => __( 'User\'s vote id.', 'buddyboss-pro' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
			),
		);

		/**
		 * Filters the poll option schema.
		 *
		 * @since 2.6.00
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_poll_option_schema', $this->add_additional_fields_schema( $schema ) );
	}
}
