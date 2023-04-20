<?php
/**
 * LearnDash V2 REST API Users Courses Controller.
 *
 * This Controller class is used to GET/UPDATE/DELETE the association
 * between a User and the enrolled Groups (groups).
 *
 * This class extends the LD_REST_Posts_Controller_V2 class.
 *
 * @since 3.3.0
 * @package LearnDash\REST\V2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LD_REST_Users_Groups_Controller_V2' ) ) && ( class_exists( 'LD_REST_Posts_Controller_V2' ) ) ) {

	/**
	 * Class LearnDash V2 REST API Users Courses Controller.
	 *
	 * @since 3.3.0
	 * @uses LD_REST_Posts_Controller_V2
	 */
	class LD_REST_Users_Groups_Controller_V2 extends LD_REST_Posts_Controller_V2 /* phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound */ {

		/**
		 * Public constructor for class
		 *
		 * @since 3.3.0
		 */
		public function __construct() {
			$this->post_type  = learndash_get_post_type_slug( 'group' );
			$this->taxonomies = array();

			parent::__construct( $this->post_type );

			/**
			 * Set the rest_base after the parent __constructor
			 * as it will set these var with WP specific details.
			 */
			$this->rest_base     = $this->get_rest_base( 'users' );
			$this->rest_sub_base = $this->get_rest_base( 'users-groups' );
		}

		/**
		 * Registers the routes for the objects of the controller.
		 *
		 * @since 3.3.0
		 *
		 * @see register_rest_route()
		 */
		public function register_routes() {

			$collection_params = $this->get_collection_params();
			$schema            = $this->get_item_schema();

			$get_item_args = array(
				'context' => $this->get_context_param( array( 'default' => 'view' ) ),
			);

			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/(?P<id>[\d]+)/' . $this->rest_sub_base,
				array(
					'args'   => array(
						'id' => array(
							'description' => esc_html__( 'User ID', 'learndash' ),
							'required'    => true,
							'type'        => 'integer',
						),
					),
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_user_groups' ),
						'permission_callback' => array( $this, 'get_user_groups_permissions_check' ),
						'args'                => $this->get_collection_params(),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => array( $this, 'update_user_groups' ),
						'permission_callback' => array( $this, 'update_user_groups_permissions_check' ),
						'args'                => array(
							'group_ids' => array(
								// translators: group.
								'description' => sprintf( esc_html_x( '%s IDs to add to User.', 'placeholder: group', 'learndash' ), learndash_get_custom_label( 'group' ) ),
								'required'    => true,
								'type'        => 'array',
								'items'       => array(
									'type' => 'integer',
								),
							),
						),
					),
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'callback'            => array( $this, 'delete_user_groups' ),
						'permission_callback' => array( $this, 'delete_user_groups_permissions_check' ),
						'args'                => array(
							'group_ids' => array(
								// translators: group.
								'description' => sprintf( esc_html_x( '%s IDs to remove from User.', 'placeholder: group', 'learndash' ), learndash_get_custom_label( 'group' ) ),
								'required'    => true,
								'type'        => 'array',
								'items'       => array(
									'type' => 'integer',
								),
							),
						),
					),
					'schema' => array( $this, 'get_public_item_schema' ),
				)
			);
		}

		/**
		 * Gets public schema.
		 *
		 * @since 3.3.0
		 *
		 * @return array
		 */
		public function get_public_item_schema() {
			$schema = parent::get_public_item_schema();

			$schema['title']  = 'user-groups';
			$schema['parent'] = '';

			return $schema;
		}

		/**
		 * Permissions check for getting user groups.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return true|WP_Error True if the request has read access, otherwise WP_Error object.
		 */
		public function get_user_groups_permissions_check( $request ) {
			if ( learndash_is_admin_user() ) {
				return true;
			} elseif ( get_current_user_id() == $request['id'] ) {
				return true;
			} else {
				return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
			}
		}

		/**
		 * Permissions check for updating user groups.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return true|WP_Error True if the request has read access, otherwise WP_Error object.
		 */
		public function update_user_groups_permissions_check( $request ) {
			if ( learndash_is_admin_user() ) {
				return true;
			} elseif ( get_current_user_id() == $request['id'] ) {
				return true;
			} else {
				return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
			}
		}

		/**
		 * Permissions check for deleting user groups.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return true|WP_Error True if the request has read access, otherwise WP_Error object.
		 */
		public function delete_user_groups_permissions_check( $request ) {
			if ( learndash_is_admin_user() ) {
				return true;
			} elseif ( get_current_user_id() == $request['id'] ) {
				return true;
			} else {
				return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
			}
		}

		/**
		 * Get a user groups.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function get_user_groups( $request ) {
			return $this->get_items( $request );
		}

		/**
		 * Update a user groups.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function update_user_groups( $request ) {
			$user_id = $request['id'];
			if ( empty( $user_id ) ) {
				return new WP_Error(
					'rest_post_invalid_id',
					esc_html__( 'Invalid User ID.', 'learndash' ),
					array(
						'status' => 404,
					)
				);
			}

			$user = get_user_by( 'id', $user_id );
			if ( ( ! $user ) || ( ! is_a( $user, 'WP_User' ) ) ) {
				return new WP_Error(
					'rest_user_invalid_id',
					esc_html__( 'Invalid User ID.', 'learndash' ),
					array(
						'status' => 404,
					)
				);
			}

			$group_ids = $request['group_ids'];
			if ( ( ! is_array( $group_ids ) ) || ( empty( $group_ids ) ) ) {
				return new WP_Error(
					'rest_post_invalid_id',
					sprintf(
						// translators: placeholder: Group.
						esc_html_x(
							'Invalid %s ID.',
							'placeholder: Group',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'group' )
					),
					array(
						'status' => 404,
					)
				);
			}
			$group_ids = array_map( 'absint', $group_ids );

			$data = array();
			foreach ( $group_ids as $group_id ) {
				if ( empty( $group_id ) ) {
					continue;
				}

				$data_item = new stdClass();

				$group_post = get_post( $group_id );
				if ( ( ! $group_post ) || ( ! is_a( $group_post, 'WP_Post' ) ) || ( learndash_get_post_type_slug( 'group' ) !== $group_post->post_type ) ) {
					$data_item->group_id = $group_id;
					$data_item->status   = 'failed';
					$data_item->code     = 'learndash_rest_invalid_id';
					$data_item->message  = sprintf(
						// translators: placeholder: Group.
						esc_html_x(
							'Invalid %s ID.',
							'placeholder: Group',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'group' )
					);
					$data[] = $data_item;

					continue;
				}

				$ret = ld_update_group_access( $user_id, $group_id, false );
				if ( true === $ret ) {
					$data_item->group_id = $group_id;
					$data_item->status   = 'success';
					$data_item->code     = 'learndash_rest_enroll_success';
					$data_item->message  = sprintf(
						// translators: placeholder: Group.
						esc_html_x(
							'User enrolled in %s success.',
							'placeholder: Group',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'group' )
					);
				} else {
					$data_item->group_id = $group_id;
					$data_item->status   = 'failed';
					$data_item->code     = 'learndash_rest_enroll_failed';
					$data_item->message  = sprintf(
						// translators: placeholder: Group.
						esc_html_x(
							'User already enrolled in %s.',
							'placeholder: Group',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'group' )
					);
				}
				$data[] = $data_item;

			}

			// Create the response object.
			$response = rest_ensure_response( $data );

			// Add a custom status code.
			$response->set_status( 200 );

			return $response;
		}

		/**
		 * Delete a user groups.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function delete_user_groups( $request ) {
			$user_id = $request['id'];
			if ( empty( $user_id ) ) {
				return new WP_Error( 'rest_post_invalid_id', esc_html__( 'Invalid User ID.', 'learndash' ) . ' ' . __CLASS__, array( 'status' => 404 ) );
			}

			$group_ids = $request['group_ids'];
			if ( ( ! is_array( $group_ids ) ) || ( empty( $group_ids ) ) ) {
				return new WP_Error(
					'rest_post_invalid_id',
					sprintf(
						// translators: placeholder: Group.
						esc_html_x(
							'Invalid %s ID.',
							'placeholder: Group',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'group' )
					),
					array(
						'status' => 404,
					)
				);
			}
			$group_ids = array_map( 'absint', $group_ids );

			$data = array();

			foreach ( $group_ids as $group_id ) {
				if ( empty( $group_id ) ) {
					continue;
				}

				$data_item = new stdClass();

				$group_post = get_post( $group_id );
				if ( ( ! $group_post ) || ( ! is_a( $group_post, 'WP_Post' ) ) || ( learndash_get_post_type_slug( 'group' ) !== $group_post->post_type ) ) {
					$data_item->group_id = $group_id;
					$data_item->status   = 'failed';
					$data_item->code     = 'learndash_rest_invalid_id';
					$data_item->message  = sprintf(
						// translators: placeholder: Group.
						esc_html_x(
							'Invalid %s ID.',
							'placeholder: Group',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'group' )
					);
					$data[] = $data_item;

					continue;
				}

				$ret = ld_update_group_access( $user_id, $group_id, true );
				if ( true === $ret ) {
					$data_item->group_id = $group_id;
					$data_item->status   = 'success';
					$data_item->code     = 'learndash_rest_unenroll_success';
					$data_item->message  = sprintf(
						// translators: placeholder: Group.
						esc_html_x(
							'User unenrolled from %s success.',
							'placeholder: Group',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'group' )
					);
				} else {
					$data_item->group_id = $group_id;
					$data_item->status   = 'failed';
					$data_item->code     = 'learndash_rest_unenroll_failed';
					$data_item->message  = sprintf(
						// translators: placeholder: Group.
						esc_html_x(
							'User not enrolled from %s.',
							'placeholder: Group',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'group' )
					);
				}
				$data[] = $data_item;
			}

			// Create the response object.
			$response = rest_ensure_response( $data );

			// Add a custom status code.
			$response->set_status( 200 );

			return $response;
		}

		/**
		 * Filter Users Groups query args.
		 *
		 * @since 3.3.0
		 *
		 * @param array           $query_args Key value array of query var to query value.
		 * @param WP_REST_Request $request    The request used.
		 *
		 * @return array Key value array of query var to query value.
		 */
		public function rest_query_filter( $query_args, $request ) {
			if ( ! $this->is_rest_request( $request ) ) {
				return $query_args;
			}

			$query_args = parent::rest_query_filter( $query_args, $request );

			$route_url    = $request->get_route();
			$ld_route_url = '/' . $this->namespace . '/' . $this->rest_base . '/' . absint( $request['id'] ) . '/' . $this->rest_sub_base;
			if ( ( ! empty( $route_url ) ) && ( $ld_route_url === $route_url ) ) {

				$user_id = $request['id'];
				if ( empty( $user_id ) ) {
					return new WP_Error( 'rest_user_invalid_id', esc_html__( 'Invalid User ID.', 'learndash' ), array( 'status' => 404 ) );
				}

				if ( is_user_logged_in() ) {
					$current_user_id = get_current_user_id();
				} else {
					$current_user_id = 0;
				}

				$query_args['post__in'] = array( 0 );
				if ( ! empty( $current_user_id ) ) {
					$group_ids = learndash_get_users_group_ids( $user_id );
					if ( ! empty( $group_ids ) ) {
						$query_args['post__in'] = $group_ids;
					}
				}
			}

			return $query_args;
		}
	}
}
