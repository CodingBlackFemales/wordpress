<?php
/**
 * LearnDash V2 REST API User Course Progress Controller.
 *
 * This Controller class is used to GET/UPDATE/DELETE the user
 * course progress.
 *
 * This class extends the LD_REST_Posts_Controller_V2 class.
 *
 * @since 3.3.0
 * @package LearnDash\REST\V2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LearnDash\Core\Mappers\Progress\Post_Type_Status;
use LearnDash\Core\Models\Quiz;
use LearnDash\Core\Utilities\Cast;

if (
	! class_exists( 'LD_REST_Users_Course_Progress_Controller_V2' )
	&& class_exists( 'LD_REST_Posts_Controller_V2' )
) {
	/**
	 * Class LearnDash V2 REST API User Course Progress Controller.
	 *
	 * @since 3.3.0
	 * @uses LD_REST_Posts_Controller_V2
	 */
	class LD_REST_Users_Course_Progress_Controller_V2 extends LD_REST_Posts_Controller_V2 /* phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound */ {
		/**
		 * User course activity
		 *
		 * @var array
		 */
		protected $user_course_activity = array();

		/**
		 * Supported Collection Parameters.
		 *
		 * @since 3.3.0
		 *
		 * @var array $supported_collection_params.
		 */
		private $supported_collection_params = array(
			'exclude'  => 'post__not_in',
			'include'  => 'post__in',
			'per_page' => 'posts_per_page',
			'page'     => 'paged',
		);

		/**
		 * Request User ID.
		 *
		 * @var integer $user_id;
		 */
		private $user_id = null;

		/**
		 * Request Current User ID.
		 *
		 * @var object $current_user_id;
		 */
		private $current_user_id = null;

		/**
		 * Public constructor for class
		 *
		 * 3.3.0
		 */
		public function __construct() {
			$this->post_type  = learndash_get_post_type_slug( 'course' );
			$this->taxonomies = array();

			parent::__construct( $this->post_type );

			$this->rest_base     = $this->get_rest_base( 'users' );
			$this->rest_sub_base = $this->get_rest_base( 'users-course-progress' );
		}

		/**
		 * Registers the routes for the objects of the controller.
		 *
		 * @since 3.3.0
		 *
		 * @see register_rest_route()
		 */
		public function register_routes() {
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
						'callback'            => array( $this, 'get_header_items' ),
						'permission_callback' => array( $this, 'get_header_items_permissions_check' ),
						'args'                => $this->get_collection_params_header(),
					),
					'schema' => array( $this, 'get_public_item_header_schema' ),
				)
			);

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
						'course' => [
							'description' => sprintf(
								// translators: placeholder: Course label.
								esc_html_x(
									'%s ID',
									'placeholder: Course label',
									'learndash'
								),
								LearnDash_Custom_Label::get_label( LDLMS_Post_Types::COURSE )
							),
							'required'    => true,
							'type'        => 'integer',
						],
					),
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_header_item' ),
						'permission_callback' => array( $this, 'get_header_item_permissions_check' ),
						'args'                => [],
					),
					'schema' => array( $this, 'get_public_item_header_schema' ),
				)
			);

			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/(?P<id>[\d]+)/' . $this->rest_sub_base . '/(?P<course>[\d]+)/steps',
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
								esc_html_x(
									'%s ID',
									'placeholder: Course label',
									'learndash'
								),
								LearnDash_Custom_Label::get_label( LDLMS_Post_Types::COURSE )
							),
							'required'    => true,
							'type'        => 'integer',
						),
					),
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_step_items' ),
						'permission_callback' => array( $this, 'get_step_items_permissions_check' ),
						'args'                => $this->get_collection_params_steps(),
					),
					'schema' => array( $this, 'get_public_item_step_schema' ),
				)
			);

			register_rest_route(
				$this->namespace,
				"/{$this->rest_base}/(?P<id>[\\d]+)/{$this->rest_sub_base}/(?P<course>[\\d]+)/exam",
				[
					'args'   => [
						'id'     => [
							'description' => esc_html__( 'User ID', 'learndash' ),
							'required'    => true,
							'type'        => 'integer',
						],
						'course' => [
							'description' => sprintf(
								// translators: placeholder: Course label.
								esc_html_x(
									'%s ID',
									'placeholder: Course label',
									'learndash'
								),
								LearnDash_Custom_Label::get_label( LDLMS_Post_Types::COURSE )
							),
							'required'    => true,
							'type'        => 'integer',
						],
					],
					[
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => [ $this, 'get_exam_item' ],
						'permission_callback' => [ $this, 'get_exam_item_permissions_check' ],
						'args'                => [],
					],
					'schema' => [ $this, 'get_public_item_exam_schema' ],
				]
			);
		}

		/**
		 * Gets public schema for progression header.
		 *
		 * @since 3.3.0
		 *
		 * @return array
		 */
		public function get_public_item_header_schema() {
			$schema = array(
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => 'user-course-progress',
				'type'       => 'object',
				'properties' => array(
					'course'             => array(
						'description' => sprintf(
							// translators: placeholder: Course.
							esc_html_x(
								'%s ID',
								'placeholder: Course',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'course' )
						),
						'type'        => 'integer',
						'context'     => array( 'embed', 'view' ),
						'readonly'    => true,
					),
					'progress_status'    => array(
						'description' => sprintf(
							// translators: placeholder: Course.
							esc_html_x(
								'%s Progress status value',
								'placeholder: Course',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'course' )
						),
						'type'        => 'string',
						'context'     => [ 'embed', 'view' ],
						'enum'        => array_keys( Post_Type_Status::get_statuses( $this->post_type ) ),
						'readonly'    => true,
					),
					'last_step'          => array(
						'description' => esc_html__( 'Last completed step', 'learndash' ),
						'type'        => 'integer',
						'context'     => array( 'embed', 'view' ),
						'readonly'    => true,
					),
					'steps_completed'    => array(
						'description' => esc_html__( 'Total completed steps', 'learndash' ),
						'type'        => 'integer',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'steps_total'        => array(
						'description' => sprintf(
							// translators: placeholder: Course.
							esc_html_x(
								'Total %s steps',
								'placeholder: Course',
								'learndash'
							),
							learndash_get_custom_label_lower( LDLMS_Post_Types::COURSE )
						),
						'type'        => 'integer',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'date_started_gmt'   => array(
						'description' => esc_html__( 'Date started in GMT', 'learndash' ),
						'type'        => 'string',
						'format'      => 'date-time',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'date_started'       => array(
						'description' => esc_html__( 'Date started', 'learndash' ),
						'type'        => 'string',
						'format'      => 'date-time',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'date_completed_gmt' => array(
						'description' => esc_html__( 'Date completed in GMT', 'learndash' ),
						'type'        => 'string',
						'format'      => 'date-time',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'date_completed'     => array(
						'description' => esc_html__( 'Date completed', 'learndash' ),
						'type'        => 'string',
						'format'      => 'date-time',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
				),
			);
			return $schema;
		}

		/**
		 * Gets public schema for progression steps.
		 *
		 * @since 3.3.0
		 *
		 * @return array
		 */
		public function get_public_item_step_schema() {
			$schema = array(
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => 'user-course-progress-steps',
				'parent'     => 'user-course-progress',
				'type'       => 'object',
				'properties' => array(
					'step'                    => array(
						'description' => esc_html__( 'Step ID', 'learndash' ),
						'type'        => 'integer',
						'context'     => array( 'embed', 'view' ),
						'readonly'    => true,
					),
					'post_type'               => array(
						'description' => esc_html__( 'Post type for step', 'learndash' ),
						'type'        => 'string',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'step_name'               => array(
						'description' => esc_html__( 'Step name', 'learndash' ),
						'type'        => 'string',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'step_status'             => array(
						'description' => esc_html__( 'Step status value', 'learndash' ),
						'type'        => 'string',
						'context'     => [ 'embed', 'view' ],
						'enum'        => array_keys( Post_Type_Status::get_statuses( $this->post_type ) ),
						'readonly'    => true,
					),
					'date_started_gmt'        => array(
						'description' => esc_html__( 'Date started in GMT', 'learndash' ),
						'type'        => 'string',
						'format'      => 'date-time',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'date_started'            => array(
						'description' => esc_html__( 'Date started', 'learndash' ),
						'type'        => 'string',
						'format'      => 'date-time',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'date_completed_gmt'      => array(
						'description' => esc_html__( 'Date completed in GMT', 'learndash' ),
						'type'        => 'string',
						'format'      => 'date-time',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'date_completed'          => array(
						'description' => esc_html__( 'Date completed', 'learndash' ),
						'type'        => 'string',
						'format'      => 'date-time',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'awarded_certificate_url' => array(
						'description' => sprintf(
							// translators: placeholder: Certificate, Quiz.
							esc_html_x(
								'URL to the %1$s if the step is a %2$s with an attached %1$s and the %2$s is passed.',
								'placeholder: Certificate, Quiz',
								'learndash'
							),
							learndash_get_custom_label_lower( LDLMS_Post_Types::CERTIFICATE ),
							learndash_get_custom_label_lower( LDLMS_Post_Types::QUIZ )
						),
						'type'        => 'string',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
				),
			);
			return $schema;
		}

		/**
		 * Retrieves the schema for a single user course progress exam.
		 *
		 * @since 5.0.0
		 *
		 * @return array<string, mixed> The schema for a single user course progress exam.
		 */
		public function get_public_item_exam_schema() {
			return [
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => 'user-course-progress-exam',
				'parent'     => 'user-course-progress',
				'type'       => 'object',
				'properties' => [
					'id'                        => [
						'description' => sprintf(
							// translators: placeholder: Exam.
							esc_html__( '%s ID', 'learndash' ),
							learndash_get_custom_label( LDLMS_Post_Types::EXAM )
						),
						'type'        => 'integer',
						'context'     => [ 'view' ],
						'readonly'    => true,
					],
					'course_id'                 => [
						'description' => sprintf(
							// translators: placeholder: Course.
							esc_html_x(
								'%s ID',
								'placeholder: Course',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'course' )
						),
						'type'        => 'integer',
						'context'     => [ 'view' ],
						'readonly'    => true,
					],
					'user_id'                   => [
						'description' => esc_html__( 'User ID', 'learndash' ),
						'type'        => 'integer',
						'context'     => [ 'view' ],
						'readonly'    => true,
					],
					'title'                     => [
						'description' => sprintf(
						// translators: placeholder: Exam.
							esc_html__( '%s title', 'learndash' ),
							learndash_get_custom_label( LDLMS_Post_Types::EXAM )
						),
						'type'        => 'string',
						'context'     => [ 'view' ],
						'readonly'    => true,
					],
					'status'                    => [
						'description' => sprintf(
						// translators: placeholder: Exam.
							esc_html__( '%s status', 'learndash' ),
							learndash_get_custom_label( LDLMS_Post_Types::EXAM )
						),
						'type'        => 'string',
						'context'     => [ 'view' ],
						'readonly'    => true,
					],
					'status_label'              => [
						'description' => sprintf(
						// translators: placeholder: Exam.
							esc_html__( '%s status label', 'learndash' ),
							learndash_get_custom_label( LDLMS_Post_Types::EXAM )
						),
						'type'        => 'string',
						'context'     => [ 'view' ],
						'readonly'    => true,
					],
					'date_started_gmt'          => [
						'description' => esc_html__( 'Date started in GMT', 'learndash' ),
						'type'        => [ 'string', 'null' ],
						'format'      => 'date-time',
						'context'     => [ 'view' ],
						'readonly'    => true,
					],
					'date_started'              => [
						'description' => esc_html__( 'Date started', 'learndash' ),
						'type'        => [ 'string', 'null' ],
						'format'      => 'date-time',
						'context'     => [ 'view' ],
						'readonly'    => true,
					],
					'date_completed_gmt'        => [
						'description' => esc_html__( 'Date completed in GMT', 'learndash' ),
						'type'        => [ 'string', 'null' ],
						'format'      => 'date-time',
						'context'     => [ 'view' ],
						'readonly'    => true,
					],
					'date_completed'            => [
						'description' => esc_html__( 'Date completed', 'learndash' ),
						'type'        => [ 'string', 'null' ],
						'format'      => 'date-time',
						'context'     => [ 'view' ],
						'readonly'    => true,
					],
					'questions_amount'          => [
						'description' => sprintf(
							// translators: placeholder: Questions.
							esc_html__( 'Total of %s', 'learndash' ),
							learndash_get_custom_label( 'questions' )
						),
						'type'        => 'integer',
						'context'     => [ 'view' ],
						'readonly'    => true,
					],
					'questions_total_correct'   => [
						'description' => sprintf(
							// translators: placeholder: Questions.
							esc_html__( 'Total of %s answered correctly', 'learndash' ),
							learndash_get_custom_label( 'questions' )
						),
						'type'        => 'integer',
						'context'     => [ 'view' ],
						'readonly'    => true,
					],
					'questions_total_incorrect' => [
						'description' => sprintf(
							// translators: placeholder: Questions.
							esc_html__( 'Total of %s answered incorrectly', 'learndash' ),
							learndash_get_custom_label( 'questions' )
						),
						'type'        => 'integer',
						'context'     => [ 'view' ],
						'readonly'    => true,
					],
					'questions_success_rate'    => [
						'description' => sprintf(
							// translators: placeholder: Questions.
							esc_html__( 'Success rate of %s answered correctly', 'learndash' ),
							learndash_get_custom_label( 'questions' )
						),
						'type'        => 'number',
						'context'     => [ 'view' ],
						'readonly'    => true,
					],
				],
			];
		}

		/**
		 * Retrieves the query params for the header posts collection.
		 *
		 * @since 3.3.0
		 *
		 * @return array Collection parameters.
		 */
		public function get_collection_params_header() {
			$query_params_default = parent::get_collection_params();

			$query_params_default['context']['default'] = 'view';
			$query_params_default['context']['enum']    = array( 'view' );

			$query_params            = array();
			$query_params['context'] = $query_params_default['context'];

			foreach ( $this->supported_collection_params as $external_key => $internal_key ) {
				if ( isset( $query_params_default[ $external_key ] ) ) {
					$query_params[ $external_key ] = $query_params_default[ $external_key ];
				}
			}
			return $query_params;
		}

		/**
		 * Retrieves the query params for the steps posts collection.
		 *
		 * @since 3.3.0
		 *
		 * @return array Collection parameters.
		 */
		public function get_collection_params_steps() {
			$query_params = $this->get_collection_params_header();

			// Remove unsupported query params.
			unset( $query_params['context'] );

			return $query_params;
		}

		/**
		 * Common function to check and set request params used by the endpoints.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request WP_REST_Request Object.
		 */
		protected function check_request_params( $request ) {
			$this->user_id = $request['id'];
			$this->user_id = absint( $this->user_id );
			if ( empty( $this->user_id ) ) {
				return new WP_Error( 'rest_user_invalid_id', esc_html__( 'Invalid User ID.', 'learndash' ), array( 'status' => 404 ) );
			}
			$user = get_user_by( 'ID', $this->user_id );
			if ( ! is_a( $user, 'WP_User' ) ) {
				return new WP_Error( 'rest_user_invalid_id', esc_html__( 'Invalid or unknown User.', 'learndash' ), array( 'status' => 404 ) );
			}

			if ( is_user_logged_in() ) {
				$this->current_user_id = get_current_user_id();
			} else {
				$this->current_user_id = 0;
			}

			if ( empty( $this->current_user_id ) ) {
				return new WP_Error( 'rest_user_invalid_id', esc_html__( 'Invalid current user.', 'learndash' ), array( 'status' => 404 ) );
			}

			if ( ( $this->current_user_id !== $this->user_id ) && ( ! learndash_is_admin_user( $this->current_user_id ) ) ) {
				if ( learndash_is_group_leader_user( $this->current_user_id ) ) {
					if ( ! learndash_is_group_leader_of_user( $this->current_user_id, $this->user_id ) ) {
						return new WP_Error( 'rest_user_invalid_id', esc_html__( 'Not allowed to view other user content.', 'learndash' ), array( 'status' => 401 ) );
					}
				} else {
					$this->user_id = $this->current_user_id;
				}

				$user_group_ids = learndash_get_users_group_ids( $this->user_id );
				if ( empty( $user_group_ids ) ) {
					return new WP_Error( 'rest_user_invalid_id', esc_html__( 'Not allowed to view other user content.', 'learndash' ), array( 'status' => 401 ) );
				}
			}

			return true;
		}

		/**
		 * Common function to get the displayable Courses.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request WP_REST_Request Object.
		 *
		 * @return array $user_course_ids.
		 */
		protected function get_request_courses( $request ) {
			$user_course_ids = array();

			if ( ( $this->current_user_id !== $this->user_id ) && ( ! learndash_is_admin_user( $this->current_user_id ) ) ) {
				if ( learndash_is_group_leader_user( $this->current_user_id ) ) {
					if ( ! learndash_is_group_leader_of_user( $this->current_user_id, $this->user_id ) ) {
						return new WP_Error( 'rest_user_invalid_id', esc_html__( 'Not allowed to view other user content.', 'learndash' ), array( 'status' => 401 ) );
					}

					$group_leader_group_ids = learndash_get_administrators_group_ids( $this->current_user_id );
					if ( empty( $group_leader_group_ids ) ) {
						return new WP_Error( 'rest_user_invalid_id', esc_html__( 'Not allowed to view other user content.', 'learndash' ), array( 'status' => 401 ) );
					}
					$user_group_ids = learndash_get_users_group_ids( $this->user_id );
					if ( empty( $user_group_ids ) ) {
						return new WP_Error( 'rest_user_invalid_id', esc_html__( 'Not allowed to view other user content.', 'learndash' ), array( 'status' => 401 ) );
					}

					$group_ids = array_intersect( $group_leader_group_ids, $user_group_ids );

					if ( ! empty( $group_ids ) ) {
						foreach ( $group_ids as $group_id ) {
							$course_ids = learndash_group_enrolled_courses( $group_id );
							if ( ! empty( $course_ids ) ) {
								$user_course_ids = array_merge( $user_course_ids, $course_ids );
							}
						}
					}
				} else {
					return new WP_Error( 'rest_user_invalid_id', esc_html__( 'Not allowed to view other user content.', 'learndash' ), array( 'status' => 401 ) );
				}
			} else {
				$user_course_progress = get_user_meta( $this->user_id, '_sfwd-course_progress', true );
				$user_course_progress = ! empty( $user_course_progress ) ? $user_course_progress : array();

				$courses_registered = ld_get_mycourses( $this->user_id );
				$courses_registered = ! empty( $courses_registered ) ? $courses_registered : array();

				$user_course_ids = array_keys( $user_course_progress );
				$user_course_ids = array_merge( $user_course_ids, $courses_registered );
				$user_course_ids = array_unique( $user_course_ids );
			}

			return $user_course_ids;
		}

		/**
		 * Common function to get the query_args.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request WP_REST_Request Object.
		 * @param array           $user_course_ids         Course IDs.
		 *
		 * @return array $args.
		 */
		protected function get_query_args( $request, $user_course_ids = array() ) {
			// Retrieve the list of registered collection query parameters.
			$registered = $this->get_collection_params();
			$args       = array();

			/*
			 * This array defines mappings between public API query parameters whose
			 * values are accepted as-passed, and their internal WP_Query parameter
			 * name equivalents (some are the same). Only values which are also
			 * present in $registered will be set.
			 */
			$parameter_mappings = array(
				'page'     => 'paged',
				'per_page' => 'posts_per_page',
				'search'   => 's',
			);

			/*
				* For each known parameter which is both registered and present in the request,
				* set the parameter's value on the query $args.
				*/
			foreach ( $parameter_mappings as $api_param => $wp_param ) {
				if ( isset( $registered[ $api_param ], $request[ $api_param ] ) ) {
					$args[ $wp_param ] = $request[ $api_param ];
				}
			}

			// Force the post_type argument, since it's not a user input variable.
			$args['post_type'] = $this->post_type;
			$args['post__in']  = $user_course_ids;
			$args['fields']    = 'ids';

			/** This filter is documented in includes/rest-api/v1/class-ld-rest-users-course-progress-controller.php */
			$args = apply_filters( 'learndash_rest_users_course_progress_query', $args, $request );

			return $args;
		}

		/**
		 * Permissions check for getting user progress item.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return true|WP_Error True if the request has read access, otherwise WP_Error object.
		 */
		public function get_header_item_permissions_check( $request ) {
			$course_id = $request['course'];
			if ( ! $course_id ) {
				return false;
			}

			$user_id = $request['id'];
			if ( learndash_is_admin_user() ) {
				return true;
			} elseif ( get_current_user_id() === $user_id ) {
				return true;
			} elseif ( learndash_is_group_leader_user() ) {
				if ( learndash_is_group_leader_of_user( get_current_user_id(), $user_id ) ) {
					return true;
				}
			}
			return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
		}

		/**
		 * Permissions check for getting user progress item.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return true|WP_Error True if the request has read access, otherwise WP_Error object.
		 */
		public function get_step_items_permissions_check( $request ) {
			$course_id = $request['course'];
			if ( ! $course_id ) {
				return false;
			}

			$user_id = $request['id'];
			if ( learndash_is_admin_user() ) {
				return true;
			} elseif ( get_current_user_id() === $user_id ) {
				return true;
			} elseif ( learndash_is_group_leader_user() ) {
				if ( learndash_is_group_leader_of_user( get_current_user_id(), $user_id ) ) {
					return true;
				}
			}
			return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
		}

		/**
		 * Checks if the user has permission to view the exam item.
		 *
		 * @since 5.0.0
		 *
		 * @param WP_REST_Request<array{id: int}> $request Full details about the request.
		 *
		 * @return true|WP_Error True if the request has read access, otherwise WP_Error object.
		 */
		public function get_exam_item_permissions_check( $request ) {
			$user_id = $request->get_param( 'id' );

			// Admin users can view all exam items.

			if ( learndash_is_admin_user() ) {
				return true;
			}

			// Current user can view their own exam items.

			if ( get_current_user_id() === $user_id ) {
				return true;
			}

			// Group leader users can view exam items for users they are group leaders for.

			if (
				$user_id > 0
				&& learndash_is_group_leader_user()
				&& learndash_is_group_leader_of_user( get_current_user_id(), $user_id )
			) {
				return true;
			}

			return new WP_Error(
				'ld_rest_cannot_view',
				esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ),
				[ 'status' => rest_authorization_required_code() ]
			);
		}

		/**
		 * Permissions check for getting user progress items.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return true|WP_Error True if the request has read access, otherwise WP_Error object.
		 */
		public function get_header_items_permissions_check( $request ) {
			$user_id = $request['id'];

			if ( learndash_is_admin_user() ) {
				return true;
			} elseif ( get_current_user_id() === $user_id ) {
				return true;
			} elseif ( learndash_is_group_leader_user() ) {
				if ( learndash_is_group_leader_of_user( get_current_user_id(), $user_id ) ) {
					return true;
				}
			}
			return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
		}

		/**
		 * Get user course progress header items.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function get_header_items( $request ) {
			$this->check_request_params( $request );
			$user_course_ids = $this->get_request_courses( $request );

			if ( ! empty( $user_course_ids ) ) {
				$include_course_ids = $request['include'];
				if ( ! empty( $include_course_ids ) ) {
					$user_course_ids = array_intersect( $user_course_ids, $include_course_ids );
				}

				$exclude_course_ids = $request['exclude'];
				if ( ! empty( $exclude_course_ids ) ) {
					$user_course_ids = array_diff( $user_course_ids, $exclude_course_ids );
				}
			}

			if ( ! empty( $user_course_ids ) ) {
				$args = $this->get_query_args( $request, $user_course_ids );

				$query_args = $this->prepare_items_query( $args, $request );

				$posts_query = new WP_Query( $query_args );

				$data = array();
				foreach ( $posts_query->posts as $course_id ) {
					$this->user_course_activity = $this->get_user_course_activity( $this->user_id, $course_id, [ Cast::to_int( $course_id ) ] );
					$data[]                     = $this->get_user_course_progress_header( $this->user_id, $course_id );
				}

				$page        = (int) $query_args['paged'];
				$total_posts = $posts_query->found_posts;

				if ( $total_posts < 1 ) {
					// Out-of-bounds, run the query again without LIMIT for total count.
					unset( $query_args['paged'] );

					$count_query = new WP_Query();
					$count_query->query( $query_args );
					$total_posts = $count_query->found_posts;
				}

				$max_pages = ceil( $total_posts / (int) $posts_query->query_vars['posts_per_page'] );

				if ( $page > $max_pages && $total_posts > 0 ) {
					return new WP_Error( 'rest_post_invalid_page_number', __( 'The page number requested is larger than the number of pages available.', 'learndash' ), array( 'status' => 400 ) );
				}

				$response = rest_ensure_response( $data );

				$response->header( 'X-WP-Total', (int) $total_posts );
				$response->header( 'X-WP-TotalPages', (int) $max_pages );

				$request_params = $request->get_query_params();
				$base           = add_query_arg( $request_params, rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ) );

				if ( $page > 1 ) {
					$prev_page = $page - 1;

					if ( $prev_page > $max_pages ) {
						$prev_page = $max_pages;
					}

					$prev_link = add_query_arg( 'page', $prev_page, $base );
					$response->link_header( 'prev', $prev_link );
				}
				if ( $max_pages > $page ) {
					$next_page = $page + 1;
					$next_link = add_query_arg( 'page', $next_page, $base );

					$response->link_header( 'next', $next_link );
				}

				return $response;
			} else {
				$response = rest_ensure_response( array() );
				return $response;
			}
		}

		/**
		 * Get user course progress header items.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function get_header_item( $request ) {
			$this->check_request_params( $request );
			$user_course_ids = $this->get_request_courses( $request );

			$course_id = $request['course'];
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
					) . ' ' . __CLASS__,
					array( 'status' => 404 )
				);
			}

			if ( ! empty( $user_course_ids ) ) {
				$include_course_ids = array( $course_id );
				if ( ! empty( $include_course_ids ) ) {
					$user_course_ids = array_intersect( $user_course_ids, $include_course_ids );
				}
			}

			$data = [];

			if ( ! empty( $user_course_ids ) ) {
				$args = $this->get_query_args( $request, $user_course_ids );

				$query_args = $this->prepare_items_query( $args, $request );

				$posts_query = new WP_Query( $query_args );

				foreach ( $posts_query->posts as $course_id ) {
					$this->user_course_activity = $this->get_user_course_activity( $this->user_id, $course_id, [ Cast::to_int( $course_id ) ] );
					$data[]                     = $this->get_user_course_progress_header( $this->user_id, $course_id );
				}
			}

			if ( empty( $data ) ) {
				return new WP_Error(
					'rest_post_not_found',
					esc_html__( 'No item found.', 'learndash' ),
					[ 'status' => 404 ]
				);
			}

			return rest_ensure_response( $data[0] ); // Return the first item in the array.
		}

		/**
		 * Get user course progress header items.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function get_step_items( $request ) {
			$this->check_request_params( $request );
			$user_course_ids = $this->get_request_courses( $request );

			// Validate parameters.

			$course_id = $request->get_param( 'course' );

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
					) . ' ' . __CLASS__,
					[ 'status' => 404 ]
				);
			}

			if (
				empty( $user_course_ids )
				|| empty( array_intersect( $user_course_ids, [ $course_id ] ) )
			) {
				return new WP_Error(
					'rest_post_access_denied',
					sprintf(
						// translators: placeholder: Course.
						__(
							'Sorry, you are not allowed to access this item, or the user is not enrolled in this %s.',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					[ 'status' => rest_authorization_required_code() ]
				);
			}

			// Pagination parameters.

			$page     = $request->get_param( 'page' );
			$per_page = $request->get_param( 'per_page' );

			// Get steps.

			$course_steps_object = LDLMS_Factory_Post::course_steps( $course_id );

			if ( ! $course_steps_object ) {
				// No steps found for this course.
				return rest_ensure_response( [] );
			}

			$course_steps_object->load_steps();
			$course_steps = $course_steps_object->get_steps( 'l' );

			// Filter the include query param to only include specified steps.
			$include_steps = $request->get_param( 'include' );
			if ( ! empty( $include_steps ) ) {
				$all_step_ids = array();
				foreach ( $course_steps as $step_key ) {
					list( $step_type, $step_id ) = explode( ':', $step_key );
					$all_step_ids[]              = (int) $step_id;
				}
				$include_steps = array_intersect( $include_steps, $all_step_ids );

				// Filter course steps to only include specified step IDs.
				$filtered_steps = array();
				foreach ( $course_steps as $step_key ) {
					list( $step_type, $step_id ) = explode( ':', $step_key );
					if ( in_array( (int) $step_id, $include_steps, true ) ) {
						$filtered_steps[] = $step_key;
					}
				}
				$course_steps = $filtered_steps;
			}

			// Remove excluded steps from the course steps.
			$exclude_steps = $request->get_param( 'exclude' );
			if ( ! empty( $exclude_steps ) ) {
				$filtered_steps = array();
				foreach ( $course_steps as $step_key ) {
					list( $step_type, $step_id ) = explode( ':', $step_key );
					if ( ! in_array( (int) $step_id, $exclude_steps, true ) ) {
						$filtered_steps[] = $step_key;
					}
				}
				$course_steps = $filtered_steps;
			}

			$total_posts = count( $course_steps );
			$max_pages   = ceil( $total_posts / $per_page );

			if (
				$page > $max_pages
				&& $total_posts > 0
			) {
				return new WP_Error(
					'rest_post_invalid_page_number',
					__( 'The page number requested is larger than the number of pages available.', 'learndash' ),
					[ 'status' => 400 ]
				);
			}

			$paginated_steps = array_slice( $course_steps, ( $page - 1 ) * $per_page, $per_page );

			$response = rest_ensure_response(
				$this->get_steps_progress_data(
					$this->user_id,
					$course_id,
					$paginated_steps
				)
			);

			$response->header( 'X-WP-Total', (int) $total_posts );
			$response->header( 'X-WP-TotalPages', (int) $max_pages );

			$request_params = $request->get_query_params();
			$base           = add_query_arg( $request_params, rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ) );

			if ( $page > 1 ) {
				$prev_page = $page - 1;

				if ( $prev_page > $max_pages ) {
					$prev_page = $max_pages;
				}

				$prev_link = add_query_arg( 'page', $prev_page, $base );
				$response->link_header( 'prev', $prev_link );
			}

			if ( $max_pages > $page ) {
				$next_page = $page + 1;
				$next_link = add_query_arg( 'page', $next_page, $base );

				$response->link_header( 'next', $next_link );
			}

			return $response;
		}

		/**
		 * Returns the exam item for the given user and course.
		 *
		 * @since 5.0.0
		 *
		 * @param WP_REST_Request<array{course: int, id: int}> $request Full details about the request.
		 *
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function get_exam_item( $request ) {
			$this->check_request_params( $request );
			$user_course_ids = $this->get_request_courses( $request );

			// Validate parameters.

			$course_id = $request->get_param( 'course' );

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
					) . ' ' . __CLASS__,
					[ 'status' => 404 ]
				);
			}

			if (
				empty( $user_course_ids )
				|| empty( array_intersect( $user_course_ids, [ $course_id ] ) )
			) {
				return new WP_Error(
					'rest_post_access_denied',
					sprintf(
						// translators: placeholder: Course.
						__(
							'Sorry, you are not allowed to access this item, or the user is not enrolled in this %s.',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					[ 'status' => rest_authorization_required_code() ]
				);
			}

			$exam_id = (int) learndash_get_course_exam_challenge( $course_id );

			// No exam found for this course.

			if ( empty( $exam_id ) ) {
				return rest_ensure_response( [] );
			}

			$exam_activity = learndash_get_user_course_exam_activity( $this->user_id, $course_id, $exam_id );

			// Set fields.

			$date_started_gmt   = ! empty( $exam_activity->activity_started )
				? gmdate( 'Y-m-d H:i:s', $exam_activity->activity_started )
				: '';
			$date_completed_gmt = ! empty( $exam_activity->activity_completed )
				? gmdate( 'Y-m-d H:i:s', $exam_activity->activity_completed )
				: '';

			$exam_status_slug  = learndash_grade_user_course_exam_activity( $exam_activity );
			$exam_status_label = learndash_course_exam_challenge_status_label( $exam_status_slug );

			$question_stats = learndash_course_challenge_exam_get_questions_stats( $exam_id, $exam_activity );

			$response = rest_ensure_response(
				[
					'id'                        => $exam_id,
					'course_id'                 => $course_id,
					'user_id'                   => $this->user_id,
					'title'                     => get_the_title( $exam_id ),
					'status'                    => $exam_status_slug,
					'status_label'              => $exam_status_label,
					'date_started_gmt'          => ! empty( $date_started_gmt )
						? $this->prepare_date_response( $date_started_gmt ) : '',
					'date_started'              => ! empty( $date_started_gmt )
						? $this->prepare_date_response( $date_started_gmt, get_date_from_gmt( $date_started_gmt ) ) : '',
					'date_completed_gmt'        => ! empty( $date_completed_gmt )
						? $this->prepare_date_response( $date_completed_gmt ) : '',
					'date_completed'            => ! empty( $date_completed_gmt )
						? $this->prepare_date_response( $date_completed_gmt, get_date_from_gmt( $date_completed_gmt ) ) : '',
					'questions_amount'          => $question_stats['total'],
					'questions_total_correct'   => $question_stats['correct'],
					'questions_total_incorrect' => $question_stats['incorrect'],
					'questions_success_rate'    => $question_stats['percentage'],
				],
			);

			return $response;
		}

		/**
		 * Get the user course progress header data.
		 *
		 * @since 3.3.0
		 *
		 * @param integer $user_id   User ID.
		 * @param integer $course_id Course ID.
		 *
		 * @return array of header data.
		 */
		protected function get_user_course_progress_header( $user_id = 0, $course_id = 0 ) {
			if ( ( empty( $user_id ) ) || ( empty( $course_id ) ) ) {
				return array();
			}

			$user_course_progress_header = array();

			$user_course_progress_meta = get_user_meta( $user_id, '_sfwd-course_progress', true );
			if ( isset( $user_course_progress_meta[ $course_id ] ) ) {
				$user_course_progress_header = (array) $user_course_progress_meta[ $course_id ];
				if ( isset( $user_course_progress_header['lessons'] ) ) {
					unset( $user_course_progress_header['lessons'] );
				}
				if ( isset( $user_course_progress_header['topics'] ) ) {
					unset( $user_course_progress_header['topics'] );
				}
				if ( isset( $user_course_progress_header['total'] ) ) {
					unset( $user_course_progress_header['total'] );
				}
			}

			if ( ( isset( $this->user_course_activity[ $course_id ] ) ) && ( 'sfwd-courses' === $this->user_course_activity[ $course_id ]['post_type'] ) ) {
				$step_item = $this->user_course_activity[ $course_id ];
			} else {
				$step_item = array();
			}
			$user_course_progress_header['course'] = $course_id;

			if ( isset( $user_course_progress_header['last_id'] ) ) {
				$user_course_progress_header['last_step'] = $user_course_progress_header['last_id'];
				unset( $user_course_progress_header['last_id'] );
			} else {
				$user_course_progress_header['last_step'] = 0;
			}

			$user_course_progress_header['steps_total'] = learndash_get_course_steps_count( $course_id );

			if ( isset( $user_course_progress_header['completed'] ) ) {
				$user_course_progress_header['steps_completed'] = $user_course_progress_header['completed'];
				unset( $user_course_progress_header['completed'] );
			} else {
				$user_course_progress_header['steps_completed'] = 0;
			}

			// Date started.

			$user_course_started = get_user_meta( $user_id, 'course_' . $course_id . '_access_from', true );

			if ( ! empty( $user_course_started ) ) {
				$date_started                                    = gmdate( 'Y-m-d H:i:s', $user_course_started );
				$user_course_progress_header['date_started_gmt'] = $this->prepare_date_response( $date_started );
				$user_course_progress_header['date_started']     = $this->prepare_date_response(
					$date_started,
					get_date_from_gmt( $date_started )
				);
			} elseif (
				isset( $step_item['date_started'] )
				&& ! empty( $step_item['date_started'] )
			) {
				$user_course_progress_header['date_started_gmt'] = $step_item['date_started_gmt'];
				$user_course_progress_header['date_started']     = $step_item['date_started'];
			} else {
				$user_course_progress_header['date_started_gmt'] = '';
				$user_course_progress_header['date_started']     = '';
			}

			// Date completed.

			$user_course_completed = get_user_meta( $user_id, 'course_completed_' . $course_id, true );

			if ( ! empty( $user_course_completed ) ) {
				$date_completed                                    = gmdate( 'Y-m-d H:i:s', $user_course_completed );
				$user_course_progress_header['date_completed_gmt'] = $this->prepare_date_response( $date_completed );
				$user_course_progress_header['date_completed']     = $this->prepare_date_response(
					$date_completed,
					get_date_from_gmt( $date_completed )
				);
			} elseif (
				isset( $step_item['date_completed'] )
				&& ! empty( $step_item['date_completed'] )
			) {
				$user_course_progress_header['date_completed_gmt'] = $step_item['date_completed_gmt'];
				$user_course_progress_header['date_completed']     = $step_item['date_completed'];
			} else {
				$user_course_progress_header['date_completed_gmt'] = '';
				$user_course_progress_header['date_completed']     = '';
			}

			$user_course_progress_header['status']          = learndash_course_status( $course_id, $user_id, true );
			$user_course_progress_header['progress_status'] = $user_course_progress_header['status'];
			unset( $user_course_progress_header['status'] );

			$user_course_progress_header['_links'] = array(
				'self'       => array(
					array(
						'href' => rest_url( $this->namespace . '/' . $this->rest_base . '/' . $user_id . '/' . $this->rest_sub_base . '/' . $course_id ),
					),
				),
				'collection' => array(
					array(
						'href' => rest_url( $this->namespace . '/' . $this->rest_base . '/' . $user_id . '/' . $this->rest_sub_base ),
					),
				),
				'steps'      => array(
					array(
						'href'       => rest_url( $this->namespace . '/' . $this->rest_base . '/' . $user_id . '/' . $this->rest_sub_base . '/' . $course_id . '/steps' ),
						'embeddable' => true,
					),
				),
			);

			$progress_status_rest_base = $this->get_rest_base( 'progress-status', 'progress-status' );
			if ( ! empty( $progress_status_rest_base ) ) {
				$user_course_progress_header['_links']['progress_status'] = array(
					array(
						'href'       => rest_url( $this->namespace . '/' . $progress_status_rest_base . '/' . str_replace( '_', '-', $user_course_progress_header['progress_status'] ) ),
						'embeddable' => false,
					),
				);
			}

			return $user_course_progress_header;
		}

		/**
		 * Get the user course progress steps data.
		 *
		 * @since 3.3.0
		 * @deprecated 5.0.0 This method is not used anywhere.
		 *
		 * @param integer $user_id   User ID.
		 * @param integer $course_id Course ID.
		 *
		 * @return array of steps data.
		 */
		public function get_user_course_progress_steps( $user_id = 0, $course_id = 0 ) {
			_deprecated_function( __METHOD__, '5.0.0' );

			$user_id   = absint( $user_id );
			$course_id = absint( $course_id );

			if ( ( empty( $user_id ) ) || ( empty( $course_id ) ) ) {
				return array();
			}

			$user_course_progress_steps = array();

			$ld_course_steps_object = LDLMS_Factory_Post::course_steps( $course_id );
			$ld_course_steps_object->load_steps();
			$course_steps_l = $ld_course_steps_object->get_steps( 'l' );
			if ( ! empty( $course_steps_l ) ) {
				foreach ( $course_steps_l as $step_key ) {
					list( $step_type, $step_id ) = explode( ':', $step_key );

					$step_item = array();
					if ( ( isset( $this->user_course_activity[ $step_id ] ) ) && ( $step_type === $this->user_course_activity[ $step_id ]['post_type'] ) ) {
						$step_item = $this->user_course_activity[ $step_id ];
					} else {
						$step_item = array();
					}

					if ( ! isset( $step_item['step'] ) ) {
						$step_item['step'] = absint( $step_id );
					}

					if ( ! isset( $step_item['post_type'] ) ) {
						$step_item['post_type'] = esc_attr( $step_type );
					}

					if ( ! isset( $step_item['step_status'] ) ) {
						$step_item['step_status'] = '';
					}

					if ( ! isset( $step_item['date_started'] ) ) {
						$step_item['step_started'] = '';
					}

					if ( ! isset( $step_item['date_completed'] ) ) {
						$step_item['step_completed'] = '';
					}

					$user_course_progress_steps[] = $step_item;
				}
			}

			return $user_course_progress_steps;
		}

		/**
		 * Convert user meta course progress nested lesson/topic structure into
		 * a flat array.
		 *
		 * @since 3.3.0
		 * @deprecated 5.0.0 This method is not used anywhere.
		 *
		 * @param array $progress Array of nested lesson/topic user meta progress.
		 *
		 * @return array Array of combined course steps.
		 */
		protected function convert_user_progress_meta_normalized( $progress = array() ) {
			_deprecated_function( __METHOD__, '5.0.0' );

			$converted = array();

			if ( ( isset( $progress['lessons'] ) ) && ( ! empty( $progress['lessons'] ) ) ) {
				foreach ( $progress['lessons']  as $lesson_id => $lesson_complete ) {
					$converted[ $lesson_id ] = $lesson_complete;
					if ( ( isset( $progress['topics'][ $lesson_id ] ) ) && ( ! empty( $progress['topics'][ $lesson_id ] ) ) ) {
						foreach ( $progress['topics'][ $lesson_id ]  as $topic_id => $topic_complete ) {
							$converted[ $topic_id ] = $topic_complete;
						}
					}
				}
			}

			return $converted;
		}

		/**
		 * Gets user course activities from DB.
		 *
		 * @since 3.3.0
		 * @5.0.0 Added the $post_ids parameter.
		 *
		 * @param int        $user_id   User ID.
		 * @param int        $course_id Course ID.
		 * @param array<int> $post_ids  Array of post IDs. Default empty (all post IDs).
		 *
		 * @return array of steps data.
		 */
		public function get_user_course_activity( $user_id = 0, $course_id = 0, $post_ids = [] ) {
			$user_course_activity = array();

			if ( ( empty( $user_id ) ) || ( empty( $course_id ) ) ) {
				return $user_course_activity;
			}

			$activity_query_args = array(
				'user_ids'   => array( absint( $user_id ) ),
				'course_ids' => array( absint( $course_id ) ),
				'per_page'   => 0,
			);

			if ( ! empty( $post_ids ) ) {
				$activity_query_args['post_ids'] = $post_ids;
			}

			$user_courses_reports = learndash_reports_get_activity( $activity_query_args );

			if ( ( isset( $user_courses_reports['results'] ) ) && ( ! empty( $user_courses_reports['results'] ) ) ) {
				foreach ( $user_courses_reports['results'] as $result ) {
					$user_course_activity_row              = array();
					$user_course_activity_row['step']      = absint( $result->post_id );
					$user_course_activity_row['post_type'] = esc_attr( $result->post_type );

					if ( ! empty( $result->activity_started ) ) {
						$date_started                                 = gmdate( 'Y-m-d H:i:s', $result->activity_started );
						$user_course_activity_row['date_started_gmt'] = $this->prepare_date_response( $date_started );
						$user_course_activity_row['date_started']     = $this->prepare_date_response( $date_started, get_date_from_gmt( $date_started ) );
					} else {
						$user_course_activity_row['date_started_gmt'] = '';
						$user_course_activity_row['date_started']     = '';
					}

					if ( ! empty( $result->activity_completed ) ) {
						$date_completed                                 = gmdate( 'Y-m-d H:i:s', $result->activity_completed );
						$user_course_activity_row['date_completed_gmt'] = $this->prepare_date_response( $date_completed );
						$user_course_activity_row['date_completed']     = $this->prepare_date_response( $date_completed, get_date_from_gmt( $date_completed ) );
					} else {
						$user_course_activity_row['date_completed_gmt'] = '';
						$user_course_activity_row['date_completed']     = '';
					}

					// Determine step status based on post type.
					switch ( $result->post_type ) {
						case LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::QUIZ ):
							$quiz        = Quiz::find( $result->post_id );
							$quiz_status =
								(
									$quiz
									&& $quiz->is_complete( $user_id )
								)
									? 'passed'
									: 'failed';
							// Quiz-specific status logic.
							if ( $result->activity_status ) {
								$user_course_activity_row['step_status'] = $quiz_status;
							} elseif ( empty( $user_course_activity_row['date_started'] ) ) {
								$user_course_activity_row['step_status'] = 'not_started';
							} elseif ( empty( $user_course_activity_row['date_completed'] ) ) {
								$user_course_activity_row['step_status'] = 'in_progress';
							} else {
								$user_course_activity_row['step_status'] = $quiz_status;
							}
							break;
						default:
							// Default status logic for other post types.
							if ( $result->activity_status ) {
								$user_course_activity_row['step_status'] = 'completed';
							} elseif ( empty( $user_course_activity_row['date_started'] ) ) {
								$user_course_activity_row['step_status'] = 'not_started';
							} elseif ( empty( $user_course_activity_row['date_completed'] ) ) {
								$user_course_activity_row['step_status'] = 'in_progress';
							} else {
								$user_course_activity_row['step_status'] = 'completed';
							}
							break;
					}

					$user_course_activity[ absint( $result->post_id ) ] = $user_course_activity_row;
				}
			}

			return $user_course_activity;
		}

		/**
		 * Returns the steps progress data from a list of steps.
		 *
		 * @since 5.0.0
		 *
		 * @param int           $user_id      User ID.
		 * @param int           $course_id    Course ID.
		 * @param array<string> $course_steps List of course steps.
		 *
		 * @return array{step: int, post_type: string, step_status: string, date_started: string, date_completed: string, awarded_certificate_url: string}[]
		 */
		private function get_steps_progress_data( int $user_id, int $course_id, array $course_steps ): array {
			if ( empty( $course_steps ) ) {
				return [];
			}

			$steps_progress_data = [];
			$step_ids            = [];

			// Get all step ids and types.

			foreach ( $course_steps as $step_key ) {
				[ $step_type, $step_id ]    = explode( ':', $step_key );
				$step_ids[ (int) $step_id ] = $step_type;
			}

			// Load user course activities and names for the step ids.

			$this->user_course_activity = $this->get_user_course_activity( $user_id, $course_id, array_keys( $step_ids ) );

			// Loop through the step ids and get the step progress data.

			foreach ( $step_ids as $step_id => $step_type ) {
				$step_data = [];

				if (
					isset( $this->user_course_activity[ $step_id ] )
					&& $step_type === $this->user_course_activity[ $step_id ]['post_type']
				) {
					$step_data = $this->user_course_activity[ $step_id ];
				}

				// Check for a completed certificate if this is a quiz step.
				$awarded_certificate_url = '';
				if ( LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::QUIZ ) === $step_type ) {
					$awarded_certificate_url = learndash_get_certificate_link( $step_id, $user_id, true );
				}

				$steps_progress_data[] = wp_parse_args(
					$step_data,
					[
						'step'                    => absint( $step_id ),
						'post_type'               => esc_attr( $step_type ),
						'step_name'               => get_the_title( $step_id ),
						/**
						 * The step status is set in the get_user_course_activity() method for steps that have user activity.
						 * For steps without user activity, the step status is set to 'not_started' here.
						 *
						 * Note that, by design, "Open courses" and "Admin Auto-Enroll" do not save any data to the activity table.
						 * Therefore, the step status will always be 'not_started' for these cases.
						 */
						'step_status'             => 'not_started',
						'date_started_gmt'        => '',
						'date_started'            => '',
						'date_completed_gmt'      => '',
						'date_completed'          => '',
						'awarded_certificate_url' => $awarded_certificate_url,
					],
				);
			}

			/**
			 * The steps progress data.
			 *
			 * @var array{step: int, post_type: string, step_status: string, date_started: string, date_completed: string, awarded_certificate_url: string}[]
			 */
			return $steps_progress_data;
		}

		// End of functions.
	}
}
