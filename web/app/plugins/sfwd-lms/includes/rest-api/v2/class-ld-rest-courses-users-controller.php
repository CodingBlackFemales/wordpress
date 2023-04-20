<?php
/**
 * LearnDash REST API V2 Courses Users Controller.
 *
 * This Controller class is used to GET/UPDATE/DELETE the association
 * between a Course (sfwd-courses) and Users enrolled.
 *
 * This class extends the LD_REST_Posts_Controller_V2 class.
 *
 * @since 3.3.0
 * @package LearnDash\REST\V2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LD_REST_Courses_Users_Controller_V2' ) ) && ( class_exists( 'LD_REST_Users_Controller_V2' ) ) ) {

	/**
	 * Class LearnDash REST API V2 Courses Users Controller.
	 *
	 * @since 3.3.0
	 * @uses LD_REST_Users_Controller_V2
	 */
	class LD_REST_Courses_Users_Controller_V2 extends LD_REST_Users_Controller_V2 /* phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound */ {

		/**
		 * Public constructor for class
		 *
		 * @since 3.3.0
		 */
		public function __construct() {
			$this->rest_sub_base = $this->get_rest_base( 'courses-users' );
			parent::__construct();
		}

		/**
		 * Registers the routes for the objects of the controller.
		 *
		 * @since 3.3.0
		 *
		 * @see register_rest_route() in WordPress core.
		 */
		public function register_routes() {
			$collection_params = $this->get_collection_params();
			$schema            = $this->get_item_schema();

			$get_item_args = array(
				'context' => $this->get_context_param( array( 'default' => 'view' ) ),
			);
			if ( isset( $schema['properties']['password'] ) ) {
				$get_item_args['password'] = array(
					'description' => esc_html__( 'The password for the post if it is password protected.', 'learndash' ),
					'type'        => 'string',
				);
			}

			/**
			 * Set the rest_base after the parent __constructor
			 * as it will set these var with WP specific details.
			 */
			$courses_rest_base = $this->get_rest_base( 'courses' );

			register_rest_route(
				$this->namespace,
				'/' . $courses_rest_base . '/(?P<id>[\d]+)/' . $this->rest_sub_base,
				array(
					'args'   => array(
						'id' => array(
							'description' => sprintf(
								// translators: placeholder: Course.
								esc_html_x(
									'%s ID.',
									'placeholder: Course',
									'learndash'
								),
								LearnDash_Custom_Label::get_label( 'course' )
							),
							'required'    => true,
							'type'        => 'integer',
						),
					),
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_courses_users' ),
						'permission_callback' => array( $this, 'get_courses_users_permissions_check' ),
						'args'                => $this->get_collection_params(),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => array( $this, 'update_courses_users' ),
						'permission_callback' => array( $this, 'update_courses_users_permissions_check' ),
						'args'                => array(
							'user_ids' => array(
								'description' => esc_html__( 'User IDs to update in Course. Limit 50 per request.', 'learndash' ),
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
						'callback'            => array( $this, 'delete_courses_users' ),
						'permission_callback' => array( $this, 'delete_courses_users_permissions_check' ),
						'args'                => array(
							'user_ids' => array(
								'description' => esc_html__( 'User IDs to remove from Course. Limit 50 per request.', 'learndash' ),
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

			$schema['title']  = 'course-users';
			$schema['parent'] = 'course';

			return $schema;
		}

		/**
		 * Filter Course Users query args.
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

			$route_url         = $request->get_route();
			$courses_rest_base = $this->get_rest_base( 'courses' );

			$ld_route_url = '/' . $this->namespace . '/' . $courses_rest_base . '/' . absint( $request['id'] ) . '/' . $this->rest_sub_base;
			if ( ( ! empty( $route_url ) ) && ( $ld_route_url === $route_url ) ) {
				$course_id = (int) $request['id'];
				if ( ! empty( $course_id ) ) {

					if ( true === learndash_use_legacy_course_access_list() ) {
						$query_args['include'] = array( 0 );

						if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'courses_autoenroll_admin_users' ) === 'yes' ) {
							$exclude_admin = true;
						} else {
							$exclude_admin = false;
						}

						$course_users_query = learndash_get_users_for_course( $course_id, array(), $exclude_admin );
						if ( is_a( $course_users_query, 'WP_User_Query' ) ) {
							$query_args['include'] = $course_users_query->get_results();
						}
					} else {
						if ( ! isset( $query_args['meta_query'] ) ) {
							$query_args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						}

						$query_args['meta_query'][] = array(
							'key'     => 'course_' . $course_id . '_access_from',
							'compare' => 'EXISTS',
						);
					}
				}
			}

			return $query_args;
		}

		/**
		 * Checks if a given request has access to read course users.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return true|WP_Error True if the request has read access, WP_Error object otherwise.
		 */
		public function get_courses_users_permissions_check( $request ) {
			if ( learndash_is_admin_user() ) {
				return true;
			} else {
				return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
			}
		}

		/**
		 * Checks if a given request has access to update a course users.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return true|WP_Error True if the request has access to update the item, WP_Error object otherwise.
		 */
		public function update_courses_users_permissions_check( $request ) {
			if ( learndash_is_admin_user() ) {
				return true;
			} else {
				return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
			}
		}

		/**
		 * Checks if a given request has access to delete a course users.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return true|WP_Error True if the request has access to delete the item, WP_Error object otherwise.
		 */
		public function delete_courses_users_permissions_check( $request ) {
			if ( learndash_is_admin_user() ) {
				return true;
			} else {
				return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
			}
		}

		/**
		 * Retrieves a course users.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function get_courses_users( $request ) {
			return $this->get_items( $request );
		}

		/**
		 * Updates a course users.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function update_courses_users( $request ) {
			$course_id = $request['id'];
			if ( empty( $course_id ) ) {
				return new WP_Error(
					'rest_post_invalid_id',
					sprintf(
						// translators: placeholder: Course.
						esc_html_x(
							'Invalid %s ID.',
							'placeholder: Course',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					array( 'status' => 404 )
				);
			}

			$course_post = get_post( $course_id );
			if ( ( ! $course_post ) || ( ! is_a( $course_post, 'WP_Post' ) ) || ( learndash_get_post_type_slug( 'course' ) !== $course_post->post_type ) ) {
				return new WP_Error(
					'rest_post_invalid_id',
					sprintf(
						// translators: placeholder: Course.
						esc_html_x(
							'Invalid %s ID.',
							'placeholder: Course',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					array( 'status' => 404 )
				);
			}

			$course_price_type = learndash_get_setting( $course_id, 'course_price_type' );
			if ( 'open' === $course_price_type ) {
				return new WP_Error(
					'learndash_rest_rejected_course_open',
					sprintf(
						// translators: placeholder: Course.
						esc_html_x(
							'Cannot enroll users when %s price type is open.',
							'placeholder: Course',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					array( 'status' => 406 )
				);
			}

			$user_ids = $request['user_ids'];
			if ( ( ! is_array( $user_ids ) ) || ( empty( $user_ids ) ) ) {
				return new WP_Error(
					'rest_post_invalid_id',
					esc_html__( 'Missing User IDs.', 'learndash' ),
					array(
						'status' => 404,
					)
				);
			}

			$user_ids = array_map( 'absint', $user_ids );

			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'courses_autoenroll_admin_users' ) === 'yes' ) {
				$ignore_admin_users = true;
			} else {
				$ignore_admin_users = false;
			}

			$data = array();

			foreach ( $user_ids as $user_id ) {
				if ( empty( $user_id ) ) {
					continue;
				}

				$data_item = new stdClass();

				$user = get_user_by( 'id', $user_id );
				if ( ( ! $user ) || ( ! is_a( $user, 'WP_User' ) ) ) {
					$data_item->user_id = $user_id;
					$data_item->status  = 'failed';
					$data_item->code    = 'rest_user_invalid_id';
					$data_item->message = esc_html__( 'Invalid User ID.', 'learndash' );
					$data[]             = $data_item;

					continue;
				}

				if ( ( true === $ignore_admin_users ) && ( learndash_is_admin_user( $user_id ) ) ) {
					$data_item->user_id = $user_id;
					$data_item->status  = 'failed';
					$data_item->code    = 'learndash_rest_admin_auto_enroll';
					$data_item->message = esc_html__( 'Admin users are auto-enrolled.', 'learndash' );
					$data[]             = $data_item;

					continue;
				}

				$ret = ld_update_course_access( $user_id, $course_id );
				if ( true === $ret ) {
					$data_item->user_id = $user_id;
					$data_item->status  = 'success';
					$data_item->code    = 'learndash_rest_enroll_success';
					$data_item->message = sprintf(
						// translators: placeholder: Course.
						esc_html_x(
							'User enrolled in %s success.',
							'placeholder: Course',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' )
					);
				} else {
					$data_item->user_id = $user_id;
					$data_item->status  = 'failed';
					$data_item->code    = 'learndash_rest_enroll_failed';
					$data_item->message = sprintf(
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
		 * Delete course users.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function delete_courses_users( $request ) {
			$course_id = $request['id'];
			if ( empty( $course_id ) ) {
				return new WP_Error(
					'rest_post_invalid_id',
					sprintf(
						// translators: placeholder: Course.
						esc_html_x(
							'Invalid %s ID.',
							'placeholder: Course',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					array( 'status' => 404 )
				);
			}

			$course_post = get_post( $course_id );
			if ( ( ! $course_post ) || ( ! is_a( $course_post, 'WP_Post' ) ) || ( learndash_get_post_type_slug( 'course' ) !== $course_post->post_type ) ) {
				return new WP_Error(
					'rest_post_invalid_id',
					sprintf(
						// translators: placeholder: Course.
						esc_html_x(
							'Invalid %s ID.',
							'placeholder: Course',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					array( 'status' => 404 )
				);
			}

			$course_price_type = learndash_get_setting( $course_id, 'course_price_type' );
			if ( 'open' === $course_price_type ) {
				return new WP_Error(
					'learndash_rest_rejected_course_open',
					sprintf(
						// translators: placeholder: Course.
						esc_html_x(
							'Cannot unenroll users when %s price type is open.',
							'placeholder: Course',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					array( 'status' => 406 )
				);
			}

			$user_ids = $request['user_ids'];
			if ( ( ! is_array( $user_ids ) ) || ( empty( $user_ids ) ) ) {
				return new WP_Error( 'rest_post_invalid_id', esc_html__( 'Missing User IDs.', 'learndash' ), array( 'status' => 404 ) );
			}

			$user_ids = array_map( 'absint', $user_ids );

			if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'courses_autoenroll_admin_users' ) === 'yes' ) {
				$ignore_admin_users = true;
			} else {
				$ignore_admin_users = false;
			}

			$data = array();

			foreach ( $user_ids as $user_id ) {
				if ( empty( $user_id ) ) {
					continue;
				}

				$data_item = new stdClass();

				$user = get_user_by( 'id', $user_id );
				if ( ( ! $user ) || ( ! is_a( $user, 'WP_User' ) ) ) {
					$data_item->user_id = $user_id;
					$data_item->status  = 'failed';
					$data_item->code    = 'rest_post_invalid_id';
					$data_item->message = esc_html__( 'Invalid User ID.', 'learndash' );
					$data[]             = $data_item;

					continue;
				}

				if ( ( true === $ignore_admin_users ) && ( learndash_is_admin_user( $user_id ) ) ) {
					$data_item->user_id = $user_id;
					$data_item->status  = 'failed';
					$data_item->code    = 'learndash_rest_admin_auto_enroll';
					$data_item->message = esc_html__( 'Admin users are auto-enrolled.', 'learndash' );
					$data[]             = $data_item;

					continue;
				}

				$ret = ld_update_course_access( $user_id, $course_id, true );
				if ( true === $ret ) {
					$data_item->user_id = $user_id;
					$data_item->status  = 'success';
					$data_item->code    = 'learndash_rest_unenroll_success';
					$data_item->message = sprintf(
						// translators: placeholder: Course.
						esc_html_x(
							'User enrolled from %s success.',
							'placeholder: Course',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' )
					);
				} else {
					$data_item->user_id = $user_id;
					$data_item->status  = 'failed';
					$data_item->code    = 'learndash_rest_unenroll_failed';
					$data_item->message = sprintf(
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
