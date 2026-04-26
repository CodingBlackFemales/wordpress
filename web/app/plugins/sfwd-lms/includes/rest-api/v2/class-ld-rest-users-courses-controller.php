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

use LearnDash\Core\Models\Product;
use LearnDash\Core\Utilities\Cast;
use LearnDash\Core\Models\Course;

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
			$this->register_fields();

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

			// Register route for individual course updates.
			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/(?P<id>[\d]+)/' . $this->rest_sub_base . '/(?P<course>[\d]+)',
				array(
					'args'   => array(
						'id'     => array(
							'description' => esc_html__( 'User ID', 'learndash' ),
							'required'    => true,
							'type'        => 'integer',
						),
						'course' => array(
							'description' => sprintf(
								// translators: placeholder: Course label.
								esc_html_x( '%s ID', 'placeholder: course label', 'learndash' ),
								LearnDash_Custom_Label::get_label( 'course' )
							),
							'required'    => true,
							'type'        => 'integer',
						),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => array( $this, 'update_user_course' ),
						'permission_callback' => array( $this, 'update_user_courses_permissions_check' ),
						'args'                => array(
							'enrolled_at' => array(
								'description' => sprintf(
									// translators: placeholder: Course label.
									esc_html_x(
										'The enrollment date for the %s.',
										'placeholder: course label',
										'learndash'
									),
									LearnDash_Custom_Label::get_label( 'course' )
								),
								'type'        => 'string',
								'format'      => 'date-time',
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
			$schema['parent'] = 'users';

			$schema['properties']['enrolled_at'] = [
				'description' => sprintf(
					// translators: placeholder: Course.
					esc_html_x(
						'The date the user was enrolled in the %s.',
						'placeholder: course',
						'learndash'
					),
					learndash_get_custom_label( 'course' )
				),
				'context'     => [ 'view' ],
				'type'        => 'string',
				'format'      => 'date-time',
			];

			$schema['properties']['enrolled_at_gmt'] = [
				'description' => sprintf(
					// translators: placeholder: Course.
					esc_html_x(
						'The date the user was enrolled in the %s in GMT.',
						'placeholder: course',
						'learndash'
					),
					learndash_get_custom_label( 'course' )
				),
				'context'     => [ 'view' ],
				'type'        => 'string',
				'format'      => 'date-time',
			];

			$schema['properties']['awarded_certificate_url'] = [
				'description' => sprintf(
					// translators: placeholder: Certificate, Course.
					esc_html_x(
						'URL to the %1$s if the %2$s has an attached %1$s and the %2$s is completed.',
						'placeholder: Certificate, Course',
						'learndash'
					),
					LearnDash_Custom_Label::get_label( 'certificate' ),
					LearnDash_Custom_Label::get_label( 'course' )
				),
				'context'     => [ 'view' ],
				'type'        => 'string',
			];

			return $schema;
		}

		/**
		 * Returns the user courses.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function get_user_courses( $request ) {
			$user_id = $request['id'];
			if ( empty( $user_id ) ) {
				return new WP_Error(
					'learndash_rest_invalid_user_id',
					esc_html__( 'Invalid User ID.', 'learndash' ),
					array(
						'status' => 404,
					)
				);
			}

			$user = get_user_by( 'id', $user_id );
			if ( ( ! $user ) || ( ! is_a( $user, 'WP_User' ) ) ) {
				return new WP_Error(
					'learndash_rest_invalid_user_id',
					esc_html__( 'Invalid User ID.', 'learndash' ),
					array(
						'status' => 404,
					)
				);
			}

			$course_ids = learndash_user_get_enrolled_courses( $user_id, array(), true );

			// Filter the include query param to only include user's courses.

			$include_courses = $request->get_param( 'include' );
			$include_courses = ( ! empty( $include_courses ) ) ? array_intersect( $include_courses, $course_ids ) : $course_ids;

			// Remove excluded courses from the include query param.

			$exclude_courses = $request->get_param( 'exclude' );
			$include_courses = array_diff( $include_courses, $exclude_courses );

			if ( empty( $include_courses ) ) {
				$include_courses = [ 0 ]; // Don't return anything.
			}

			// Add the include query param to the request.

			$request->set_query_params(
				array_merge(
					$request->get_query_params(),
					[ 'include' => $include_courses ]
				)
			);

			$response = $this->get_items( $request );

			if ( is_wp_error( $response ) ) {
				return $response;
			}

			// Inject the enrolled date for each course.

			/**
			 * The list of courses.
			 *
			 * @var array<int, array<string, mixed>>
			 */
			$courses = (array) $response->get_data();

			foreach ( $courses as &$course ) {
				$course_id = Cast::to_int( $course['id'] ?? 0 );

				$product = $course_id > 0
					? Product::find( $course_id )
					: null;

				if ( ! $product ) {
					continue;
				}

				$enrolled_date = Cast::to_int( $product->get_enrollment_date( $user_id ) );

				// Convert the timestamp to date.

				$enrolled_date_gmt = gmdate( 'Y-m-d H:i:s', $enrolled_date );

				// Set fields.

				$course_model            = Course::create_from_post( $product->get_post() );
				$awarded_certificate_url = '';
				$certificate             = $course_model->get_award_certificate();
				if (
					$certificate
					&& $course_model->is_complete( $user_id )
				) {
					$awarded_certificate_url = learndash_get_course_certificate_link( $course_id, $user_id );
				}

				$course['enrolled_at_gmt']         = $this->prepare_date_response( $enrolled_date_gmt );
				$course['enrolled_at']             = $this->prepare_date_response( $enrolled_date_gmt, get_date_from_gmt( $enrolled_date_gmt ) );
				$course['awarded_certificate_url'] = $awarded_certificate_url;
			}

			$response->set_data( $courses );

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
			} elseif ( get_current_user_id() === (int) $request['id'] ) {
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
			} else {
				return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
			}
		}

		/**
		 * Update a user courses.
		 *
		 * @since 3.3.0
		 * @since 5.0.0 Now only returns a WP_REST_Response object regardless of success or failure.
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return WP_REST_Response
		 */
		public function update_user_courses( $request ) {
			$user_id = $request['id'];
			if ( empty( $user_id ) ) {
				return new WP_REST_Response(
					[
						'code'    => 'learndash_rest_invalid_user_id',
						'message' => esc_html__( 'Invalid User ID.', 'learndash' ),
					],
					400
				);
			}

			$user = get_user_by( 'id', $user_id );
			if ( ( ! $user ) || ( ! is_a( $user, 'WP_User' ) ) ) {
				return new WP_REST_Response(
					[
						'code'    => 'learndash_rest_invalid_user_id',
						'message' => esc_html__( 'Invalid User ID.', 'learndash' ),
					],
					400
				);
			}

			// Check if Admin user and Admin auto-enroll is enabled.
			if ( ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Admin_User', 'courses_autoenroll_admin_users' ) ) && ( learndash_is_admin_user( $user_id ) ) ) {
				return new WP_REST_Response(
					[
						'code'    => 'learndash_rest_admin_auto_enroll',
						'message' => esc_html__( 'Admin users are auto-enrolled.', 'learndash' ),
					],
					400
				);
			}

			$course_ids = $request['course_ids'];
			if ( ( ! is_array( $course_ids ) ) || ( empty( $course_ids ) ) ) {
				return new WP_REST_Response(
					[
						'code'    => 'learndash_rest_invalid_course_id',
						'message' => sprintf(
							// translators: placeholder: Course.
							esc_html_x(
								'Missing %s ID',
								'placeholder: Course',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'course' )
						),
					],
					400
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
					$data_item->code      = 'learndash_rest_invalid_course_id';
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
		 * Update a specific user course.
		 *
		 * @since 5.0.0
		 *
		 * @param WP_REST_Request<array{enrolled_at:string, id: int, course: int}> $request Full details about the request.
		 *
		 * @return WP_REST_Response
		 */
		public function update_user_course( $request ) {
			$user_id   = $request['id'];
			$course_id = $request['course'];

			// Initialize the data item.

			$data_item            = new stdClass();
			$data_item->course_id = $course_id;
			$data_item->user_id   = $user_id;

			if (
				empty( $user_id )
				|| empty( $course_id )
			) {
				$data_item->code    = 'learndash_rest_invalid_params';
				$data_item->message = esc_html__( 'Required parameters are missing.', 'learndash' );
				$data_item->success = false;

				$response = rest_ensure_response( $data_item );
				$response->set_status( 400 );

				return $response;
			}

			$user = get_user_by( 'id', $user_id );
			if ( ! $user instanceof WP_User ) {
				$data_item->code    = 'learndash_rest_invalid_user_id';
				$data_item->message = esc_html__( 'Invalid User ID.', 'learndash' );
				$data_item->status  = 'failed';

				$response = rest_ensure_response( $data_item );
				$response->set_status( 400 );

				return $response;
			}

			$course_post = get_post( $course_id );
			if (
				! $course_post instanceof WP_Post
				|| learndash_get_post_type_slug( 'course' ) !== $course_post->post_type
			) {
				$data_item->code    = 'learndash_rest_invalid_course_id';
				$data_item->message = sprintf(
					// translators: placeholder: Course label.
					esc_html_x(
						'Invalid %s ID.',
						'placeholder: Course label',
						'learndash'
					),
					LearnDash_Custom_Label::get_label( 'course' )
				);
				$data_item->status = 'failed';

				$response = rest_ensure_response( $data_item );
				$response->set_status( 400 );

				return $response;
			}

			// Check if user is enrolled in the course.
			$user_course_ids = learndash_user_get_enrolled_courses( $user_id, array(), true );
			if ( ! in_array( $course_id, $user_course_ids, true ) ) {
				$data_item->code    = 'learndash_rest_user_not_enrolled';
				$data_item->message = sprintf(
					// translators: placeholder: Course label.
					esc_html_x(
						'User is not enrolled in this %s.',
						'placeholder: Course label',
						'learndash'
					),
					LearnDash_Custom_Label::get_label( 'course' )
				);
				$data_item->status = 'failed';

				$response = rest_ensure_response( $data_item );
				$response->set_status( 403 );

				return $response;
			}

			/**
			 * The enrollment date is a string in the format YYYY-MM-DD HH:MM:SS.
			 * We need to convert it to a timestamp.
			 *
			 * @var string $enrollment_date The enrollment date.
			 */
			$enrollment_date = $request->get_param( 'enrolled_at' );

			// If no enrollment date provided, return an empty response.
			if ( empty( $enrollment_date ) ) {
				// No enrollment date provided.
				$data_item->status  = 'failed';
				$data_item->code    = 'learndash_rest_empty_enrollment_date';
				$data_item->message = esc_html__( 'Enrollment date is empty.', 'learndash' );

				$response = rest_ensure_response( $data_item );
				$response->set_status( 400 );

				return $response;
			}

			// Parse the enrollment date handling both timezone-aware and timezone-agnostic formats.
			// Check if the date string includes timezone information (Z or +/- offset).
			$has_timezone = preg_match( '/[Zz]|[+-]\d{2}:?\d{2}$/', $enrollment_date );

			if ( $has_timezone ) {
				// Date has timezone information, use rest_parse_date for RFC 3339 compliance.
				$timestamp = rest_parse_date( $enrollment_date );
			} else {
				// Date is timezone-agnostic, parse with WordPress timezone.
				$wp_timezone = wp_timezone();
				$date_time   = date_create( $enrollment_date, $wp_timezone );
				$timestamp   = $date_time ? $date_time->getTimestamp() : false;
			}
			if ( $timestamp === false ) {
				$data_item->code    = 'learndash_rest_invalid_date';
				$data_item->message = esc_html__( 'Invalid enrollment date format.', 'learndash' );
				$data_item->status  = 'failed';

				$response = rest_ensure_response( $data_item );
				$response->set_status( 400 );

				return $response;
			}

			// Use the Product model to set the enrollment date.
			$product = Product::find( $course_id );
			if ( ! $product ) {
				$data_item->code    = 'learndash_rest_course_not_found';
				$data_item->message = sprintf(
					// translators: placeholder: Course label.
					esc_html_x(
						'%s not found.',
						'placeholder: Course label',
						'learndash'
					),
					LearnDash_Custom_Label::get_label( 'course' )
				);
				$data_item->status = 'failed';

				$response = rest_ensure_response( $data_item );
				$response->set_status( 404 );

				return $response;
			}

			// Check if the enrollment date the same as the current enrollment date.

			$current_enrollment_date = $product->get_enrollment_date( $user_id );
			if ( $current_enrollment_date === $timestamp ) {
				$data_item->status  = 'failed';
				$data_item->code    = 'learndash_rest_enrollment_date_same';
				$data_item->message = esc_html__( 'Enrollment date is the same as the current enrollment date.', 'learndash' );

				$response = rest_ensure_response( $data_item );
				$response->set_status( 200 );
				return $response;
			}

			// Update enrollment date if provided.
			$result = $product->set_enrollment_date( $user_id, $timestamp );
			if ( ! $result ) {
				$data_item->code    = 'learndash_rest_enrollment_date_update_failed';
				$data_item->message = esc_html__( 'Failed to update enrollment date.', 'learndash' );
				$data_item->status  = 'failed';

				$response = rest_ensure_response( $data_item );
				$response->set_status( 500 );

				return $response;
			}

			// Create success response data object.
			$data_item->status  = 'success';
			$data_item->code    = 'learndash_rest_enrollment_date_updated';
			$data_item->message = sprintf(
				// translators: placeholder: Course label.
				esc_html_x(
					'User enrollment date for %s updated successfully.',
					'placeholder: Course label',
					'learndash'
				),
				LearnDash_Custom_Label::get_label( 'course' )
			);

			// Format the response to match the GET endpoint structure.
			$enrolled_date_gmt          = gmdate( 'Y-m-d H:i:s', $timestamp );
			$data_item->enrolled_at_gmt = $this->prepare_date_response( $enrolled_date_gmt );
			$data_item->enrolled_at     = $this->prepare_date_response( $enrolled_date_gmt, get_date_from_gmt( $enrolled_date_gmt ) );

			// Create the response object.
			$response = rest_ensure_response( $data_item );
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
				return new WP_Error( 'learndash_rest_invalid_user_id', esc_html__( 'Invalid User ID.', 'learndash' ), array( 'status' => 404 ) );
			}

			$user = get_user_by( 'id', $user_id );
			if ( ( ! $user ) || ( ! is_a( $user, 'WP_User' ) ) ) {
				return new WP_Error(
					'learndash_rest_invalid_user_id',
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
					'learndash_rest_invalid_course_id',
					sprintf(
						// translators: placeholder: Course label.
						esc_html_x(
							'Missing %s ID',
							'placeholder: Course label',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					array( 'status' => 404 )
				);
			}
			$course_ids = array_map( 'absint', $course_ids );

			$data         = array();
			$has_success  = false;
			$has_failures = false;

			foreach ( $course_ids as $course_id ) {
				if ( empty( $course_id ) ) {
					continue;
				}

				$data_item = new stdClass();

				$course_post = get_post( $course_id );
				if ( ( ! $course_post ) || ( ! is_a( $course_post, 'WP_Post' ) ) || ( learndash_get_post_type_slug( 'course' ) !== $course_post->post_type ) ) {
					$data_item->course_id = $course_id;
					$data_item->status    = 'failed';
					$data_item->code      = 'learndash_rest_invalid_course_id';
					$data_item->message   = sprintf(
						// translators: placeholder: Course.
						esc_html_x(
							'Invalid %s ID.',
							'placeholder: Course',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' )
					);
					$data[]       = $data_item;
					$has_failures = true;

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
					$data[]       = $data_item;
					$has_failures = true;

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
							'User unenrolled from %s success.',
							'placeholder: Course',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' )
					);
					$has_success = true;
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
					$has_failures = true;
				}
				$data[] = $data_item;
			}

			// Create the response object.
			$response = rest_ensure_response( $data );

			// Set appropriate HTTP status code based on results.
			if (
				$has_success
				&& ! $has_failures
			) {
				// All operations successful.
				$response->set_status( 200 );
			} elseif ( $has_success ) {
				// Partial success - some operations failed.
				$response->set_status( 207 ); // Multi-Status.
			} else {
				// All operations failed.
				$response->set_status( 422 ); // Unprocessable Entity.
			}

			return $response;
		}

		/**
		 * Overrides the REST response links. This is needed when Course Shared Steps is enabled.
		 *
		 * @since 5.0.0
		 *
		 * @param WP_REST_Response              $response WP_REST_Response instance.
		 * @param WP_Post                       $post     WP_Post instance.
		 * @param WP_REST_Request<array{mixed}> $request  WP_REST_Request instance.
		 *
		 * @return WP_REST_Response
		 */
		public function rest_prepare_response_filter( WP_REST_Response $response, WP_Post $post, WP_REST_Request $request ) {
			if ( $this->post_type !== $post->post_type ) {
				return $response;
			}

			$base = sprintf( '/%s/%s', $this->namespace, $this->get_rest_base( 'courses' ) );

			$additional_links = [];
			$current_links    = $response->get_links();

			if ( ! isset( $current_links['price-type'] ) ) {
				$course_price_type = learndash_get_course_meta_setting( $post->ID, 'course_price_type' );
				if ( ! empty( $course_price_type ) ) {
					$additional_links[ $this->get_rest_base( 'price-types' ) ] = [
						'href'       => rest_url( trailingslashit( $this->namespace ) . $this->get_rest_base( 'price-types' ) . '/' . $course_price_type ),
						'embeddable' => true,
					];
				}
			}

			if ( ! isset( $current_links['prerequisites'] ) ) {
				$additional_links['prerequisites'] = [
					'href'       => rest_url( trailingslashit( $base ) . $post->ID ) . '/' . $this->get_rest_base( 'courses-prerequisites' ),
					'embeddable' => true,
				];
			}

			if ( ! isset( $current_links['steps'] ) ) {
				$additional_links['steps'] = [
					'href'       => rest_url( trailingslashit( $base ) . $post->ID ) . '/' . $this->get_rest_base( 'courses-steps' ),
					'embeddable' => true,
				];
			}

			if ( ! isset( $current_links['users'] ) ) {
				$additional_links['users'] = [
					'href'       => rest_url( trailingslashit( $base ) . $post->ID ) . '/' . $this->get_rest_base( 'courses-users' ),
					'embeddable' => true,
				];
			}

			if ( ! isset( $current_links['groups'] ) ) {
				$additional_links['groups'] = [
					'href'       => rest_url( trailingslashit( $base ) . $post->ID ) . '/' . $this->get_rest_base( 'courses-groups' ),
					'embeddable' => true,
				];
			}

			if ( ! empty( $additional_links ) ) {
				$response->add_links( $additional_links );
			}

			return $response;
		}

		/**
		 * Prepares the LearnDash Post Type Settings.
		 *
		 * @since 5.0.0
		 *
		 * @return void
		 */
		protected function register_fields() {
			$this->register_fields_metabox();

			/** This action is documented in includes/rest-api/v2/class-ld-rest-users-groups-controller.php */
			do_action( 'learndash_rest_register_fields', $this->post_type, $this );
		}

		/**
		 * Registers the Settings Fields from the Post Metaboxes.
		 *
		 * @since 5.0.0
		 *
		 * @return void
		 */
		protected function register_fields_metabox() {
			require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/settings/settings-metaboxes/class-ld-settings-metabox-course-enrollment.php';
			$this->metaboxes['LearnDash_Settings_Metabox_Course_Enrollment'] = LearnDash_Settings_Metabox_Course_Enrollment::add_metabox_instance();

			require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/settings/settings-metaboxes/class-ld-settings-metabox-course-display-content.php';
			$this->metaboxes['LearnDash_Settings_Metabox_Course_Display_Content'] = LearnDash_Settings_Metabox_Course_Display_Content::add_metabox_instance();

			require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php';
			$this->metaboxes['LearnDash_Settings_Metabox_Course_Access_Settings'] = LearnDash_Settings_Metabox_Course_Access_Settings::add_metabox_instance();

			require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/settings/settings-metaboxes/class-ld-settings-metabox-course-completion-awards.php';
			$this->metaboxes['LearnDash_Settings_Metabox_Course_Completion_Awards'] = LearnDash_Settings_Metabox_Course_Completion_Awards::add_metabox_instance();

			require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/settings/settings-metaboxes/class-ld-settings-metabox-course-navigation-settings.php';
			$this->metaboxes['LearnDash_Settings_Metabox_Course_Navigation_Settings'] = LearnDash_Settings_Metabox_Course_Navigation_Settings::add_metabox_instance();

			if ( ! empty( $this->metaboxes ) ) {
				foreach ( $this->metaboxes as $metabox ) {
					$metabox->load_settings_values();
					$metabox->load_settings_fields();
					$this->register_rest_fields( $metabox->get_settings_metabox_fields(), $metabox );
				}
			}
		}

		// End of functions.
	}
}
