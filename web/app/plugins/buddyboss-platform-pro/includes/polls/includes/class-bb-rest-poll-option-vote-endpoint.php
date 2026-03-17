<?php
/**
 * BB REST: BB_REST_Poll_Option_Vote_Endpoint class
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
class BB_REST_Poll_Option_Vote_Endpoint extends WP_REST_Controller {

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
		$this->rest_base = 'vote';
	}

	/**
	 * Register the component routes.
	 *
	 * @since 2.6.00
	 */
	public function register_routes() {
		$poll_endpoint = '/poll/(?P<id>[\d]+)/options/(?P<option_id>[\d]+)';

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
					'callback'            => array( $this, 'create_items' ),
					'permission_callback' => array( $this, 'create_items_permissions_check' ),
					'args'                => $this->get_endpoint_args_for_item_schema( WP_REST_Server::CREATABLE ),
				),
				'allow_batch' => $this->allow_batch,
				'schema'      => array( $this, 'get_item_schema' ),
			)
		);

		register_rest_route(
			$this->namespace,
			$poll_endpoint . '/' . $this->rest_base . '/(?P<vote_id>[\d]+)',
			array(
				'args'        => array(
					'id'        => array(
						'description' => __( 'A unique numeric ID for the Poll.', 'buddyboss-pro' ),
						'type'        => 'integer',
						'required'    => true,
					),
					'option_id' => array(
						'description' => __( 'A unique numeric ID for the Poll option.', 'buddyboss-pro' ),
						'type'        => 'integer',
						'required'    => true,
					),
					'vote_id'   => array(
						'description' => __( 'A unique numeric ID for the vote.', 'buddyboss-pro' ),
						'type'        => 'integer',
						'required'    => true,
					),
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
	 * Retrieve a votes.
	 *
	 * @since          2.6.00
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @api            {GET} /wp-json/buddyboss/v1/poll/:poll_id/options/:option_id/vote Get votes.
	 * @apiName        GetBBPollOptionsVotes
	 * @apiGroup       Polls
	 * @apiDescription Retrieve all votes for a poll option.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser.
	 * @apiParam {Number} id A unique numeric ID for the Poll.
	 * @apiParam {Number} option_id A unique numeric ID for the Poll option.
	 */
	public function get_items( $request ) {
		$option_votes = $this->get_vote_object( $request->get_param( 'id' ), $request );

		$retval = array();
		if ( ! empty( $option_votes['poll_votes'] ) ) {
			foreach ( $option_votes['poll_votes'] as $options ) {
				$retval[] = $this->prepare_response_for_collection(
					$this->prepare_item_for_response( $options, $request )
				);
			}
		}

		$response = rest_ensure_response( $retval );
		$response = bp_rest_response_add_total_headers( $response, $option_votes['total'], $request['per_page'] );

		/**
		 * Fires after a votes is fetched via the REST API.
		 *
		 * @since 2.6.00
		 *
		 * @param array            $option_votes Fetched votes.
		 * @param WP_REST_Response $response     The response data.
		 * @param WP_REST_Request  $request      The request sent to the API.
		 */
		do_action( 'bp_rest_option_votes_get_item', $option_votes['poll_votes'], $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to get information about option votes.
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
		} elseif ( true === $retval ) {
			$poll_option = bb_load_polls()->bb_get_poll_options(
				array(
					'id'      => $request['option_id'],
					'poll_id' => $request['id'],
				)
			);
			if ( empty( $poll_option ) || empty( $poll_option[0] ) || empty( $poll_option[0]['id'] ) ) {
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
		 * Filter the votes `get_items` permissions check.
		 *
		 * @since 2.6.00
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_option_votes_get_item_permissions_check', $retval, $request );
	}

	/**
	 * Create a vote.
	 *
	 * @since 2.6.00
	 *
	 * @param WP_REST_Request $request Full data about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @api            {POST} /wp-json/buddyboss/v1/poll/:poll_id/options/:option_id/vote Create Vote
	 * @apiName        CreatePollOptionVote
	 * @apiGroup       Poll
	 * @apiDescription Create a vote.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the poll.
	 * @apiParam {Number} option_id A unique numeric ID for the poll option.
	 */
	public function create_items( $request ) {
		$poll_id   = $request->get_param( 'id' );
		$option_id = $request->get_param( 'option_id' );
		$user_id   = $request->get_param( 'user_id' );

		$vote_args = array(
			'poll_id'   => $poll_id,
			'option_id' => $option_id,
			'user_id'   => ( empty( $user_id ) ? bp_loggedin_user_id() : $user_id ),
		);

		$vote_data = bb_load_polls()->update_poll_votes( $vote_args );
		$vote_data = ! empty( $vote_data ) && ! empty( $vote_data['poll_votes'] ) ? $vote_data['poll_votes'] : array();

		if ( ! $vote_data ) {
			return new WP_Error(
				'bp_rest_poll_vote_cannot_add',
				__( 'Could not add the vote.', 'buddyboss-pro' ),
				array(
					'status' => 500,
				)
			);
		}

		$retval = array();

		if ( ! empty( $vote_data ) ) {
			$vote_data = current( $vote_data );

			$retval[] = $this->prepare_response_for_collection(
				$this->prepare_item_for_response( $vote_data, $request )
			);
		}

		$response = rest_ensure_response( $retval );

		/**
		 * Fires after a poll vote is added via the REST API.
		 *
		 * @since 2.6.00
		 *
		 * @param BB_Polls         $vote_data The created a vote poll.
		 * @param WP_REST_Response $response  The response data.
		 * @param WP_REST_Request  $request   The request sent to the API.
		 */
		do_action( 'bp_rest_poll_vote_create_item', $vote_data, $response, $request );

		return $response;
	}

	/**
	 * Checks if a given request has access to add a poll vote.
	 *
	 * @since 2.6.00
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return bool|WP_Error
	 */
	public function create_items_permissions_check( $request ) {
		$retval = new WP_Error(
			'bp_rest_authorization_required',
			__( 'Sorry, you are not allowed to add a poll vote.', 'buddyboss-pro' ),
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

			$poll = bb_load_polls()->bb_get_poll( $request->get_param( 'id' ) );
			if ( empty( $poll->id ) ) {
				$retval = new WP_Error(
					'bp_rest_poll_invalid_id',
					__( 'Invalid poll ID.', 'buddyboss-pro' ),
					array(
						'status' => 404,
					)
				);
			} else {
				$vote_disabled_date = strtotime( $poll->vote_disabled_date );
				$current_time       = strtotime( bp_core_current_time() );
				if ( $vote_disabled_date < $current_time ) {
					$retval = new WP_Error(
						'bp_rest_poll_vote_closed',
						__( 'Sorry, Poll has been closed. you are not allowed to add vote.', 'buddyboss-pro' ),
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
									groups_is_user_admin( $user_id, $poll->secondary_item_id ) ||
									groups_is_user_mod( $user_id, $poll->secondary_item_id ) ||
									groups_is_user_member( $user_id, $poll->secondary_item_id )
								)
							) {
								$retval = new WP_Error(
									'bp_rest_poll_vote_group_members_only',
									__( 'Sorry, you are not allowed to add vote.', 'buddyboss-pro' ),
									array(
										'status' => 403,
									)
								);
							}
						}
					}
				}
			}
		}

		/**
		 * Filter the poll vote `create_item` permissions check.
		 *
		 * @since 2.6.00
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_polls_create_item_permissions_check', $retval, $request );
	}

	/**
	 * Delete a option vote.
	 *
	 * @since 2.6.00
	 *
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return WP_REST_Response | WP_Error
	 * @api            {DELETE} /wp-json/buddyboss/v1/poll/:poll_id/options/:option_id/vote/:vote_id Delete Option vote.
	 * @apiName        DeleteBBPollOptionsVotes
	 * @apiGroup       Polls
	 * @apiDescription Delete a option vote.
	 * @apiVersion     1.0.0
	 * @apiPermission  LoggedInUser
	 * @apiParam {Number} id A unique numeric ID for the Poll.
	 * @apiParam {Number} option_id A unique numeric ID for the Option.
	 */
	public function delete_item( $request ) {

		$option_vote = $this->get_vote_object( $request->get_param( 'id' ), $request );
		$option_vote = ! empty( $option_vote ) && ! empty( $option_vote['poll_votes'] ) ? current( $option_vote['poll_votes'] ) : array();
		$previous    = $this->prepare_item_for_response( $option_vote, $request );

		if (
			! bb_load_polls()->bb_remove_poll_votes(
				array(
					'id'        => $request->get_param( 'vote_id' ),
					'poll_id'   => $request->get_param( 'id' ),
					'option_id' => $request->get_param( 'option_id' ),
					'user_id'   => bp_loggedin_user_id(),
				)
			)
		) {
			return new WP_Error(
				'bp_rest_poll_option_vote_cannot_delete',
				__( 'Could not delete the poll option vote.', 'buddyboss-pro' ),
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
		 * Fires after a vote is deleted via the REST API.
		 *
		 * @since 2.6.00
		 *
		 * @param array            $option_vote Fetched Vote.
		 * @param WP_REST_Response $response    The response data.
		 * @param WP_REST_Request  $request     The request sent to the API.
		 */
		do_action( 'bp_rest_poll_option_delete_item', $option_vote, $response, $request );

		return $response;
	}

	/**
	 * Check if a given request has access to delete a poll option vote.
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
			__( 'Sorry, you need to be logged in to delete this vote.', 'buddyboss-pro' ),
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
				$vote_disabled_date = strtotime( $poll->vote_disabled_date );
				$current_time       = strtotime( bp_core_current_time() );
				if ( $vote_disabled_date < $current_time ) {
					$retval = new WP_Error(
						'bp_rest_poll_vote_disabled',
						__( 'Sorry, Poll has been closed. you are not allowed to delete this vote.', 'buddyboss-pro' ),
						array(
							'status' => 403,
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
				} else {
					$poll_option = bb_load_polls()->bb_get_poll_options(
						array(
							'id'      => $request->get_param( 'option_id' ),
							'poll_id' => $request->get_param( 'id' ),
						)
					);
					if ( empty( $poll_option ) || empty( $poll_option[0] ) || empty( $poll_option[0]['id'] ) ) {
						$retval = new WP_Error(
							'bp_rest_poll_invalid_option_id',
							__( 'Invalid poll option ID.', 'buddyboss-pro' ),
							array(
								'status' => 404,
							)
						);
					} else {
						$option_vote = $this->get_vote_object( $request->get_param( 'id' ), $request );
						$option_vote = ! empty( $option_vote ) && ! empty( $option_vote['poll_votes'] ) ? current( $option_vote['poll_votes'] ) : array();
						if ( empty( $option_vote ) ) {
							$retval = new WP_Error(
								'bp_rest_poll_invalid_option_vote_id',
								__( 'Invalid poll option vote ID.', 'buddyboss-pro' ),
								array(
									'status' => 404,
								)
							);
						} elseif ( bp_loggedin_user_id() !== (int) $option_vote['user_id'] ) {
							$retval = new WP_Error(
								'bp_rest_poll_unauthorized_vote_deletion',
								__( 'Sorry, you are not allowed to delete this vote.', 'buddyboss-pro' ),
								array(
									'status' => 403,
								)
							);
						}
					}
				}
			}
		}

		/**
		 * Filter the vote `delete_item` permissions check.
		 *
		 * @since 2.6.00
		 *
		 * @param bool|WP_Error   $retval  Returned value.
		 * @param WP_REST_Request $request The request sent to the API.
		 */
		return apply_filters( 'bp_rest_poll_option_vote_delete_item_permissions_check', $retval, $request );
	}

	/**
	 * Get a vote object.
	 *
	 * @since 2.6.00
	 *
	 * @param int             $poll_id Poll id.
	 * @param WP_REST_Request $request Full details about the request.
	 *
	 * @return array
	 */
	public function get_vote_object( $poll_id, $request ) {
		$args = array( 'count_total' => true );

		$order_by = $request->get_param( 'orderby' );
		$per_page = $request->get_param( 'per_page' );
		$order    = $request->get_param( 'order' );
		$page     = $request->get_param( 'page' );

		if ( ! empty( $per_page ) ) {
			$args['per_page'] = $per_page;
		}

		if ( ! empty( $page ) ) {
			$args['paged'] = $page;
		}

		if ( ! empty( $order_by ) ) {
			$args['order_by'] = $order_by;
		}

		if ( ! empty( $order ) ) {
			$args['order'] = $order;
		}

		if ( ! empty( $poll_id ) ) {
			$args['poll_id'] = (int) $poll_id;
		}

		if ( ! empty( $request->get_param( 'option_id' ) ) ) {
			$args['option_id'] = (int) $request->get_param( 'option_id' );
		}

		if ( ! empty( $request->get_param( 'vote_id' ) ) ) {
			$args['id'] = (int) $request->get_param( 'vote_id' );
		}

		return bb_load_polls()->bb_get_poll_votes( $args );
	}

	/**
	 * Prepare a votes output for response.
	 *
	 * @since 2.6.00
	 *
	 * @param array           $item    Vote object.
	 * @param WP_REST_Request $request Request object.
	 *
	 * @return WP_REST_Response
	 */
	public function prepare_item_for_response( $item, $request ) {
		$context = ! empty( $request['context'] ) ? $request['context'] : 'view';

		$data = array(
			'id'            => ! empty( $item['id'] ) ? $item['id'] : 0,
			'poll_id'       => ! empty( $item['poll_id'] ) ? $item['poll_id'] : 0,
			'option_id'     => ! empty( $item['option_id'] ) ? $item['option_id'] : 0,
			'user_id'       => ! empty( $item['user_id'] ) ? $item['user_id'] : 0,
			'date_recorded' => ! empty( $item['date_recorded'] ) ? $item['date_recorded'] : '',
		);

		$data     = $this->add_additional_fields_to_object( $data, $request );
		$data     = $this->filter_response_by_context( $data, $context );
		$response = rest_ensure_response( $data );

		$response->add_links( $this->prepare_links( $data ) );

		/**
		 * Filter a vote value returned from the API.
		 *
		 * @since 2.6.00
		 *
		 * @param WP_REST_Response $response The response data.
		 * @param WP_REST_Request  $request  Request used to generate the response.
		 * @param BB_Polls         $item     Vote object.
		 */
		return apply_filters( 'bp_rest_poll_option_vote_prepare_value', $response, $request, $item );
	}

	/**
	 * Prepare links for the option vote request.
	 *
	 * @since 2.6.00
	 *
	 * @param array $option_vote Poll option vote.
	 *
	 * @return array
	 */
	protected function prepare_links( $option_vote ) {
		$parent_base = sprintf( '/%s/%s/', $this->namespace, 'poll' );
		$base        = sprintf( '/%s/', $this->rest_base );

		// Entity meta.
		$links = array(
			'self'   => array(
				array(
					'href' => rest_url( $parent_base . $option_vote['poll_id'] . '/options/' . $option_vote['option_id'] . $base . $option_vote['id'] ),
				),
			),
			'option' => array(
				array(
					'href' => rest_url( $parent_base . $option_vote['poll_id'] . '/options/' . $option_vote['option_id'] ),
				),
			),
			'poll'   => array(
				array(
					'href' => rest_url( $parent_base . $option_vote['poll_id'] ),
				),
			),
			'user'   => array(
				array(
					'embeddable' => true,
					'href'       => rest_url( $this->namespace . '/members/' . $option_vote['user_id'] ),
				),
			),
		);

		/**
		 * Filter links prepared for the REST response.
		 *
		 * @since 2.6.00
		 *
		 * @param array $links       The prepared links of the REST response.
		 * @param array $option_vote Poll option array.
		 */
		return apply_filters( 'bp_rest_poll_option_vote_prepare_links', $links, $option_vote );
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
				'description'       => __( 'A unique numeric ID for the poll.', 'buddyboss-pro' ),
				'required'          => true,
				'type'              => 'integer',
				'validate_callback' => 'rest_validate_request_arg',
			);

			$args['user_id'] = array(
				'description'       => __( 'ID of the user who created the vote.', 'buddyboss-pro' ),
				'default'           => bp_loggedin_user_id(),
				'required'          => false,
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
		return apply_filters( "bp_rest_poll_vote_{$key}_query_arguments", $args, $method );
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
		unset( $params['search'] );

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
			'enum'              => array( 'id', 'poll_id', 'option_id', 'user_id', 'date_recorded' ),
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
	 * Get the poll option vote schema, conforming to JSON Schema.
	 *
	 * @since 2.6.00
	 * @return array
	 */
	public function get_item_schema() {
		$schema = array(
			'$schema'    => 'http://json-schema.org/draft-04/schema#',
			'title'      => 'bp_option_votes',
			'type'       => 'object',
			'properties' => array(
				'id'            => array(
					'description' => __( 'Unique identifier for the option vote.', 'buddyboss-pro' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'poll_id'       => array(
					'description' => __( 'ID of the poll.', 'buddyboss-pro' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
					'readonly'    => true,
				),
				'option_id'     => array(
					'description' => __( 'ID of the poll option.', 'buddyboss-pro' ),
					'type'        => 'integer',
					'context'     => array( 'view', 'edit' ),
				),
				'user_id'       => array(
					'description' => __( 'ID of the user who created the option.', 'buddyboss-pro' ),
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
			),
		);

		/**
		 * Filters the vote schema.
		 *
		 * @since 2.6.00
		 *
		 * @param array $schema The endpoint schema.
		 */
		return apply_filters( 'bp_rest_poll_option_vote_schema', $this->add_additional_fields_schema( $schema ) );
	}
}
