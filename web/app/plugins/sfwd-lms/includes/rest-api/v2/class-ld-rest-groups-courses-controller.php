<?php
/**
 * LearnDash REST API V2 Groups Courses Controller.
 *
 * This Controller class is used to GET/UPDATE/DELETE the association
 * between the LearnDash Groups (groups) and Courses (sfwd-courses)
 * custom post types.
 *
 * This class extends the LD_REST_Posts_Controller_V2 class.
 *
 * @since 3.3.0
 * @package LearnDash\REST\V2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LD_REST_Groups_Courses_Controller_V2' ) ) && ( class_exists( 'LD_REST_Posts_Controller_V2' ) ) ) {

	/**
	 * Class LearnDash REST API V2 Groups Courses Controller.
	 *
	 * @since 3.3.0
	 * @uses LD_REST_Posts_Controller_V2
	 */
	class LD_REST_Groups_Courses_Controller_V2 extends LD_REST_Posts_Controller_V2 /* phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound */ {

		/**
		 * Public constructor for class
		 *
		 * @since 3.3.0
		 *
		 * @param string $post_type Post type.
		 */
		public function __construct( $post_type = '' ) {
			if ( empty( $post_type ) ) {
				$post_type = learndash_get_post_type_slug( 'course' );
			}
			$this->post_type = $post_type;
			$this->metaboxes = array();

			parent::__construct( $this->post_type );

			/**
			 * Set the rest_base after the parent __constructor
			 * as it will set these var with WP specific details.
			 */
			$this->rest_base     = $this->get_rest_base( 'groups' );
			$this->rest_sub_base = $this->get_rest_base( 'groups-courses' );
		}

		/**
		 * Registers the routes for the objects of the controller.
		 *
		 * @since 3.3.0
		 *
		 * @see register_rest_route()
		 */
		public function register_routes() {
			$schema = $this->get_item_schema();

			$get_item_args = array(
				'context' => $this->get_context_param( array( 'default' => 'view' ) ),
			);
			if ( isset( $schema['properties']['password'] ) ) {
				$get_item_args['password'] = array(
					'description' => esc_html__( 'The password for the post if it is password protected.', 'learndash' ),
					'type'        => 'string',
				);
			}

			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/(?P<id>[\d]+)/' . $this->rest_sub_base,
				array(
					'args'   => array(
						'id' => array(
							// translators: placeholder: Group.
							'description' => sprintf( esc_html_x( '%s ID', 'placeholder: Group.', 'learndash' ), learndash_get_custom_label( 'group' ) ),
							'required'    => true,
							'type'        => 'integer',
						),
					),
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_groups_courses' ),
						'permission_callback' => array( $this, 'get_groups_courses_permissions_check' ),
						'args'                => $this->get_collection_params(),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => array( $this, 'update_groups_courses' ),
						'permission_callback' => array( $this, 'update_groups_courses_permissions_check' ),
						'args'                => array(
							'course_ids' => array(
								'description' => sprintf(
									// translators: placeholder: Course, Group.
									esc_html_x(
										'%1$s IDs to add to %2$s.',
										'placeholder: Course, Group',
										'learndash'
									),
									learndash_get_custom_label( 'course' ),
									learndash_get_custom_label( 'group' )
								),
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
						'callback'            => array( $this, 'delete_groups_courses' ),
						'permission_callback' => array( $this, 'delete_groups_courses_permissions_check' ),
						'args'                => array(
							'course_ids' => array(
								'description' => sprintf(
									// translators: placeholder: Course, Group.
									esc_html_x(
										'%1$s IDs to remove from %2$s.',
										'placeholder: Course, Group',
										'learndash'
									),
									learndash_get_custom_label( 'course' ),
									learndash_get_custom_label( 'group' )
								),
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

			$schema['title']  = 'group-courses';
			$schema['parent'] = 'groups';

			return $schema;
		}

		/**
		 * Checks permission to get group courses.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return true|WP_Error True if the request has access to update the item, WP_Error object otherwise.
		 */
		public function get_groups_courses_permissions_check( $request ) {
			if ( learndash_is_admin_user() ) {
				return true;
			} else {
				return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
			}
		}

		/**
		 * Checks permission to update group courses.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return true|WP_Error True if the request has access to update the item, WP_Error object otherwise.
		 */
		public function update_groups_courses_permissions_check( $request ) {
			if ( learndash_is_admin_user() ) {
				return true;
			} else {
				return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
			}
		}

		/**
		 * Checks permission to update delete courses.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return true|WP_Error True if the request has access to update the item, WP_Error object otherwise.
		 */
		public function delete_groups_courses_permissions_check( $request ) {
			if ( learndash_is_admin_user() ) {
				return true;
			} else {
				return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
			}
		}

		/**
		 * GET group courses.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function get_groups_courses( $request ) {
			return parent::get_items( $request );
		}

		/**
		 * Updates a group courses.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function update_groups_courses( $request ) {
			$group_id = $request['id'];
			if ( empty( $group_id ) ) {
				return new WP_Error(
					'rest_post_invalid_id',
					sprintf(
						// translators: placeholder: group.
						esc_html_x(
							'Invalid %s ID.',
							'placeholder: group',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'group' )
					),
					array( 'status' => 404 )
				);
			}

			$group_post = get_post( $group_id );
			if ( ( ! $group_post ) || ( ! is_a( $group_post, 'WP_Post' ) ) || ( learndash_get_post_type_slug( 'group' ) !== $group_post->post_type ) ) {
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
					array( 'status' => 404 )
				);
			}

			$course_ids = $request['course_ids'];
			if ( ( ! is_array( $course_ids ) ) || ( empty( $course_ids ) ) ) {
				return new WP_Error(
					'rest_post_invalid_id',
					sprintf(
						// translators: placeholder: Course.
						esc_html_x(
							'Missing %s IDs',
							'placeholder: Course',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' )
					) . ' ' . __CLASS__,
					array( 'status' => 404 )
				);
			}
			$course_ids = array_map( 'absint', $course_ids );

			$data = array();
			foreach ( $course_ids as $course_id ) {
				if ( empty( $group_id ) ) {
					continue;
				}

				$data_item = new stdClass();

				$course_post = get_post( $course_id );
				if ( ( ! $course_post ) || ( ! is_a( $course_post, 'WP_Post' ) ) || ( learndash_get_post_type_slug( 'course' ) !== $course_post->post_type ) ) {
					$data_item->course_id = $course_id;
					$data_item->status    = 'failed';
					$data_item->code      = 'learndash_rest_invalid_id';
					$data_item->message   = sprintf(
						// translators: placeholder: Course.
						esc_html_x(
							'Invalid %s ID.',
							'placeholder: Course',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' )
					);
					$data[] = $data_item;

					continue;
				}

				$ret = ld_update_course_group_access( $course_id, $group_id, false );
				if ( true === $ret ) {
					$data_item->course_id = $course_id;
					$data_item->status    = 'success';
					$data_item->code      = 'learndash_rest_enroll_success';
					$data_item->message   = sprintf(
						// translators: placeholder: Course, Group.
						esc_html_x(
							'%1$s enrolled in %2$s success.',
							'placeholder: Course, Group',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' ),
						LearnDash_Custom_Label::get_label( 'group' )
					);
				} else {
					$data_item->course_id = $course_id;
					$data_item->status    = 'failed';
					$data_item->code      = 'learndash_rest_enroll_failed';
					$data_item->message   = sprintf(
						// translators: placeholder: Course, Group.
						esc_html_x(
							'%1$s already enrolled in %2$s.',
							'placeholder: Course, Group',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' ),
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
		 * Delete a group courses.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function delete_groups_courses( $request ) {
			$group_id = $request['id'];
			if ( empty( $group_id ) ) {
				return new WP_Error(
					'rest_post_invalid_id',
					sprintf(
						// translators: placeholder: group.
						esc_html_x(
							'Invalid %s ID.',
							'placeholder: group',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'group' )
					),
					array( 'status' => 404 )
				);
			}

			$group_post = get_post( $group_id );
			if ( ( ! $group_post ) || ( ! is_a( $group_post, 'WP_Post' ) ) || ( learndash_get_post_type_slug( 'group' ) !== $group_post->post_type ) ) {
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
					array( 'status' => 404 )
				);
			}

			$course_ids = $request['course_ids'];
			if ( ( ! is_array( $course_ids ) ) || ( empty( $course_ids ) ) ) {
				return new WP_Error(
					'rest_post_invalid_id',
					sprintf(
						// translators: placeholder: Course.
						esc_html_x(
							'Missing %s IDs',
							'placeholder: Course',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' )
					) . ' ' . __CLASS__,
					array( 'status' => 404 )
				);
			}
			$course_ids = array_map( 'absint', $course_ids );

			$data = array();

			foreach ( $course_ids as $course_id ) {
				if ( empty( $group_id ) ) {
					continue;
				}

				$data_item = new stdClass();

				$course_post = get_post( $course_id );
				if ( ( ! $course_post ) || ( ! is_a( $course_post, 'WP_Post' ) ) || ( learndash_get_post_type_slug( 'course' ) !== $course_post->post_type ) ) {
					$data_item->course_id = $course_id;
					$data_item->status    = 'failed';
					$data_item->code      = 'learndash_rest_invalid_id';
					$data_item->message   = sprintf(
						// translators: placeholder: Course.
						esc_html_x(
							'Invalid %s ID.',
							'placeholder: Course',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' )
					);
					$data[] = $data_item;

					continue;
				}

				$ret = ld_update_course_group_access( $course_id, $group_id, true );
				if ( true === $ret ) {
					$data_item->course_id = $course_id;
					$data_item->status    = 'success';
					$data_item->code      = 'learndash_rest_unenroll_success';
					$data_item->message   = sprintf(
						// translators: placeholder: Course, Group.
						esc_html_x(
							'%1$s enrolled from %2$s success.',
							'placeholder: Course, Group',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' ),
						LearnDash_Custom_Label::get_label( 'group' )
					);
				} else {
					$data_item->course_id = $course_id;
					$data_item->status    = 'failed';
					$data_item->code      = 'learndash_rest_unenroll_failed';
					$data_item->message   = sprintf(
						// translators: placeholder: Course, Group.
						esc_html_x(
							'%1$s not unenrolled from %2$s.',
							'placeholder: Course, Group',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' ),
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
		 * Filter Groups Courses query args.
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

				$group_id = $request['id'];
				if ( empty( $group_id ) ) {
					return new WP_Error(
						'rest_post_invalid_id',
						sprintf(
							// translators: placeholder: group.
							esc_html_x(
								'Invalid %s ID.',
								'placeholder: group',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'group' )
						),
						array( 'status' => 404 )
					);
				}

				if ( is_user_logged_in() ) {
					$current_user_id = get_current_user_id();
				} else {
					$current_user_id = 0;
				}

				$query_args['post__in'] = array( 0 );
				if ( ! empty( $current_user_id ) ) {
					$course_ids = learndash_group_enrolled_courses( $group_id, true );
					if ( ! empty( $course_ids ) ) {
						$query_args['post__in'] = $course_ids;
					}
				}
			}

			return $query_args;
		}

		// End of functions.
	}
}
