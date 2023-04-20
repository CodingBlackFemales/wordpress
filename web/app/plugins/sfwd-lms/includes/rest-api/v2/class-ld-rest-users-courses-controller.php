<?php
/**
 * LearnDash V2 REST API Users Courses Controller.
 *
 * This Controller class is used to GET/UPDATE/DELETE the association
 * between a User and Courses (sfwd-courses).
 *
 * This class extends the LD_REST_Posts_Controller_V2 class.
 *
 * @since 3.3.0
 * @package LearnDash\REST\V2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LD_REST_Users_Courses_Controller_V2' ) ) && ( class_exists( 'LD_REST_Posts_Controller_V2' ) ) ) {

	/**
	 * Class LearnDash V2 REST API Users Courses Controller.
	 *
	 * @since 3.3.0
	 * @uses LD_REST_Posts_Controller_V2
	 */
	class LD_REST_Users_Courses_Controller_V2 extends LD_REST_Posts_Controller_V2 /* phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound */ {

		/**
		 * Public constructor for class
		 *
		 * @since 3.3.0
		 */
		public function __construct() {
			$this->post_type  = learndash_get_post_type_slug( 'course' );
			$this->taxonomies = array();

			parent::__construct( $this->post_type );

			/**
			 * Set the rest_base after the parent __constructor
			 * as it will set these var with WP specific details.
			 */
			$this->rest_base     = $this->get_rest_base( 'users' );
			$this->rest_sub_base = $this->get_rest_base( 'users-courses' );
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
						'callback'            => array( $this, 'get_user_courses' ),
						'permission_callback' => array( $this, 'get_user_courses_permissions_check' ),
						'args'                => $this->get_collection_params(),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => array( $this, 'update_user_courses' ),
						'permission_callback' => array( $this, 'update_user_courses_permissions_check' ),
						'args'                => array(
							'course_ids' => array(
								'description' => sprintf(
									// translators: placeholder: Course.
									esc_html_x(
										'%s IDs to add to User.',
										'placeholder: course',
										'learndash'
									),
									LearnDash_Custom_Label::get_label( 'course' )
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
						'callback'            => array( $this, 'delete_user_courses' ),
						'permission_callback' => array( $this, 'delete_user_courses_permissions_check' ),
						'args'                => array(
							'course_ids' => array(
								'description' => sprintf(
									// translators: placeholder: Course.
									esc_html_x(
										'%s IDs to remove from User.',
										'placeholder: course',
										'learndash'
									),
									LearnDash_Custom_Label::get_label( 'course' )
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

			$schema['title']  = 'user-courses';
			$schema['parent'] = '';

			return $schema;
		}

		/**
		 * Get user courses.
		 *
		 * @since 3.3.0
		 * @param WP_REST_Request $request Full details about the request.
		 */
		public function get_user_courses( $request ) {
			$user_id = $request['id'];
			if ( empty( $user_id ) ) {
				return new WP_Error(
					'rest_user_invalid_id',
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

			$user_courses = array();

			$course_ids = learndash_user_get_enrolled_courses( $user_id, array(), true );
			if ( ! empty( $course_ids ) ) {

				$route_url = '/' . $this->namespace . '/' . $this->get_rest_base( 'courses' );
				$request   = new WP_REST_Request( 'GET', $route_url );
				$request->set_query_params( array( 'include' => $course_ids ) );

				$response     = rest_do_request( $request );
				$server       = rest_get_server();
				$user_courses = $server->response_to_data( $response, false );
			}

			// Create the response object.
			$response = rest_ensure_response( $user_courses );

			// Add a custom status code.
			$response->set_status( 200 );

			return $response;
		}

		/**
		 * Checks if a given request has access to read user courses.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
		 */
		public function get_user_courses_permissions_check( $request ) {
			if ( learndash_is_admin_user() ) {
				return true;
			} elseif ( get_current_user_id() == $request['id'] ) {
				return true;
			} else {
				return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
			}
		}

		/**
		 * Checks if a given request has access to update user courses.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
		 */
		public function update_user_courses_permissions_check( $request ) {
			if ( learndash_is_admin_user() ) {
				return true;
			} elseif ( get_current_user_id() == $request['id'] ) {
				return true;
			} else {
				return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
			}
		}

		/**
		 * Checks if a given request has access to delete user courses.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
		 */
		public function delete_user_courses_permissions_check( $request ) {
			if ( learndash_is_admin_user() ) {
				return true;
			} elseif ( get_current_user_id() == $request['id'] ) {
				return true;
			} else {
				return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
			}
		}

		/**
		 * Update a user courses.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function update_user_courses( $request ) {
			$user_id = $request['id'];
			if ( empty( $user_id ) ) {
				return new WP_Error(
					'rest_user_invalid_id',
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

			// Check if Admin user and Admin auto-enroll is enabled.
			if ( ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'courses_autoenroll_admin_users' ) ) && ( learndash_is_admin_user( $user_id ) ) ) {
				return new WP_Error(
					'learndash_rest_admin_auto_enroll',
					esc_html__( 'Admin users are auto-enrolled.', 'learndash' ),
					array(
						'status' => 404,
					)
				);
			}

			$course_ids = $request['course_ids'];
			if ( ( ! is_array( $course_ids ) ) || ( empty( $course_ids ) ) ) {
				return new WP_Error(
					'rest_post_invalid_id',
					sprintf(
						// translators: placeholder: Course.
						esc_html_x(
							'Missing %s ID',
							'placeholder: Course',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					array( 'status' => 404 )
				);
			}
			$course_ids = array_map( 'absint', $course_ids );

			$data = array();

			foreach ( $course_ids as $course_id ) {
				if ( empty( $course_id ) ) {
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

				$course_price_type = learndash_get_setting( $course_id, 'course_price_type' );
				if ( 'open' === $course_price_type ) {
					$data_item->course_id = $course_id;
					$data_item->status    = 'failed';
					$data_item->code      = 'learndash_rest_rejected_course_open';
					$data_item->message   = sprintf(
						// translators: placeholder: Course.
						esc_html_x(
							'Cannot enroll users when %s price type is open.',
							'placeholder: Course',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' )
					);
					$data[] = $data_item;

					continue;
				}

				$ret = ld_update_course_access( $user_id, $course_id, false );
				if ( true === $ret ) {
					$data_item->course_id = $course_id;
					$data_item->status    = 'success';
					$data_item->code      = 'learndash_rest_enroll_success';
					$data_item->message   = sprintf(
						// translators: placeholder: Course.
						esc_html_x(
							'User enrolled in %s success.',
							'placeholder: Course',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' )
					);
				} else {
					$data_item->course_id = $course_id;
					$data_item->status    = 'failed';
					$data_item->code      = 'learndash_rest_enroll_failed';
					$data_item->message   = sprintf(
						// translators: placeholder: Course.
						esc_html_x(
							'User already enrolled in %s.',
							'placeholder: Course',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' )
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
		 * Delete a user courses.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function delete_user_courses( $request ) {
			$user_id = $request['id'];
			if ( empty( $user_id ) ) {
				return new WP_Error( 'rest_post_invalid_id', esc_html__( 'Invalid User ID.', 'learndash' ), array( 'status' => 404 ) );
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

			// Check if Admin user and Admin auto-enroll is enabled.
			if ( ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'courses_autoenroll_admin_users' ) ) && ( learndash_is_admin_user( $user_id ) ) ) {
				return new WP_Error(
					'learndash_rest_admin_auto_enroll',
					esc_html__( 'Admin users are auto-enrolled.', 'learndash' ),
					array(
						'status' => 404,
					)
				);
			}

			$course_ids = $request['course_ids'];
			if ( ( ! is_array( $course_ids ) ) || ( empty( $course_ids ) ) ) {
				return new WP_Error(
					'rest_post_invalid_id',
					sprintf(
						// translators: placeholder: Course.
						esc_html_x(
							'Missing %s ID',
							'placeholder: Course',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					array( 'status' => 404 )
				);
			}
			$course_ids = array_map( 'absint', $course_ids );

			$data = array();

			foreach ( $course_ids as $course_id ) {
				if ( empty( $course_id ) ) {
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

				$course_price_type = learndash_get_setting( $course_id, 'course_price_type' );
				if ( 'open' === $course_price_type ) {
					$data_item->course_id = $course_id;
					$data_item->status    = 'failed';
					$data_item->code      = 'learndash_rest_rejected_course_open';
					$data_item->message   = sprintf(
						// translators: placeholder: Course.
						esc_html_x(
							'Cannot unenroll users when %s price type is open.',
							'placeholder: Course',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' )
					);
					$data[] = $data_item;

					continue;
				}

				$ret = ld_update_course_access( $user_id, $course_id, true );
				if ( true === $ret ) {
					$data_item->course_id = $course_id;
					$data_item->status    = 'success';
					$data_item->code      = 'learndash_rest_unenroll_success';
					$data_item->message   = sprintf(
						// translators: placeholder: Course.
						esc_html_x(
							'User enrolled from %s success.',
							'placeholder: Course',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' )
					);
				} else {
					$data_item->course_id = $course_id;
					$data_item->status    = 'failed';
					$data_item->code      = 'learndash_rest_unenroll_failed';
					$data_item->message   = sprintf(
						// translators: placeholder: Course.
						esc_html_x(
							'User not enrolled from %s.',
							'placeholder: Course',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' )
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

		// End of functions.
	}
}
