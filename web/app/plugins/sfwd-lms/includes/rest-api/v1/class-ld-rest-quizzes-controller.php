<?php
/**
 * LearnDash REST API V1 Quizzes Post Controller.
 *
 * @since 2.5.8
 * @package LearnDash\REST\V1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LD_REST_Quizzes_Controller_V1' ) ) && ( class_exists( 'LD_REST_Posts_Controller_V1' ) ) ) {

	/**
	 * Class LearnDash REST API V1 Quizzes Post Controller.
	 *
	 * @since 2.5.8
	 */
	class LD_REST_Quizzes_Controller_V1 extends LD_REST_Posts_Controller_V1 /* phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound */ {

		/**
		 * LearnDash course steps object
		 *
		 * @var object
		 */
		protected $ld_course_steps_object = null;

		/**
		 * Public constructor for class
		 *
		 * @since 2.5.8
		 *
		 * @param string $post_type Post type.
		 */
		public function __construct( $post_type = '' ) {
			$this->post_type = 'sfwd-quiz';

			parent::__construct( $this->post_type );
			$this->namespace = LEARNDASH_REST_API_NAMESPACE . '/' . $this->version;
			$this->rest_base = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_REST_API', $this->post_type );
		}

		/**
		 * Registers the routes for the objects of the controller.
		 *
		 * @since 2.5.8
		 *
		 * @see register_rest_route() in WordPress core.
		 */
		public function register_routes() {
			$this->register_fields();

			parent::register_routes_wpv2();

			$collection_params = $this->get_collection_params();

			$schema        = $this->get_item_schema();
			$get_item_args = array(
				'context' => $this->get_context_param( array( 'default' => 'view' ) ),
			);

			if ( isset( $schema['properties']['password'] ) ) {
				$get_item_args['password'] = array(
					'description' => esc_html__( 'The password for the post if it is password protected.', 'learndash' ),
					'type'        => 'string',
				);
			}

			// Quiz Default.
			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base,
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
					'schema' => array( $this, 'get_schema' ),
				)
			);

			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base . '/(?P<id>[\d]+)',
				array(
					'args'   => array(
						'id' => array(
							'description' => sprintf(
								// translators: quiz.
								esc_html_x( 'Unique identifier for the %s object.', 'placeholder: quiz', 'learndash' ),
								learndash_get_custom_label_lower( 'quiz' )
							),
							'type'        => 'integer',
						),
					),
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_item' ),
						'permission_callback' => array( $this, 'get_item_permissions_check' ),
						'args'                => $get_item_args,
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
						'args'                => array(
							'force' => array(
								'type'        => 'boolean',
								'default'     => false,
								'description' => esc_html__( 'Whether to bypass trash and force deletion.', 'learndash' ),
							),
						),
					),
					'schema' => array( $this, 'get_schema' ),
				)
			);
		}

		/**
		 * Gets sfwd-quiz schema.
		 *
		 * @since 2.5.8
		 *
		 * @return array
		 */
		public function get_schema() {
			$schema = $this->get_public_item_schema();

			$schema['title'] = 'quiz';

			return $schema;
		}

		/**
		 * Filters collection parameters for the posts controller.
		 *
		 * @since 2.5.8
		 *
		 * @param array        $query_params Quest params array.
		 * @param WP_Post_Type $post_type    Post type object.
		 */
		public function rest_collection_params_filter( $query_params, $post_type ) {
			$query_params = parent::rest_collection_params_filter( $query_params, $post_type );

			if ( ! isset( $query_params['course'] ) ) {
				$query_params['course'] = array(
					'description' => sprintf(
						// translators: placeholder: course.
						esc_html_x(
							'Limit results to be within a specific %s. Required for non-admin users.',
							'placeholder: course',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'type'        => 'integer',
				);
			}
			if ( ! isset( $query_params['lesson'] ) ) {
				$query_params['lesson'] = array(
					'description' => sprintf(
						// translators: placeholder: lesson, course, quizzes, course.
						esc_html_x(
							'Limit results to be within a specific %1$s. Pass zero to show global %2$s %3$s. Must be used with %4$s parameter.',
							'placeholder: lesson, course, quizzes, course',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'lesson' ),
						LearnDash_Custom_Label::get_label( 'course' ),
						LearnDash_Custom_Label::get_label( 'quizzes' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'type'        => 'integer',
				);
			}
			if ( ! isset( $query_params['topic'] ) ) {
				$query_params['topic'] = array(
					'description' => sprintf(
						// translators: placeholder: topic, course.
						esc_html_x(
							'Limit results to be within a specific %1$s. Must be used with %2$s parameter.',
							'placeholder: topic, course',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'topic' ),
						LearnDash_Custom_Label::get_label( 'course' )
					),
					'type'        => 'integer',
				);
			}

			return $query_params;
		}

		/**
		 * Check Single Quiz Read Permissions.
		 *
		 * @since 2.5.8
		 *
		 * @param object $request WP_REST_Request instance.
		 */
		public function get_item_permissions_check( $request ) {
			$return = parent::get_item_permissions_check( $request );
			if ( ( true === $return ) && ( ! learndash_is_admin_user() ) ) {

				$course_id = (int) $request['course'];

				// If we don't have a course parameter we need to get all the courses the user has access to and all
				// the courses the lesson is available in and compare.
				if ( empty( $course_id ) ) {
					$user_enrolled_courses = learndash_user_get_enrolled_courses( get_current_user_id() );
					if ( empty( $user_enrolled_courses ) ) {
						return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
					}

					$step_courses = learndash_get_courses_for_step( $request['id'], true );
					if ( empty( $step_courses ) ) {
						return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
					}
					$user_enrolled_courses = array_intersect( $user_enrolled_courses, array_keys( $step_courses ) );

					if ( empty( $user_enrolled_courses ) ) {
						return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
					}
				} else {
					// But if the course parameter is provided we need to check the user has access and also
					// check the step is part of that course.
					$this->course_post = get_post( $course_id );
					if ( ( ! $this->course_post ) || ( ! is_a( $this->course_post, 'WP_Post' ) ) || ( 'sfwd-courses' !== $this->course_post->post_type ) ) {
						return new WP_Error(
							'rest_post_invalid_id',
							sprintf(
								// translators: placeholder: course.
								esc_html_x(
									'Invalid %s ID.',
									'placeholder: course',
									'learndash'
								),
								LearnDash_Custom_Label::get_label( 'course' )
							),
							array( 'status' => 404 )
						);
					}

					if ( ! sfwd_lms_has_access( $this->course_post->ID ) ) {
						return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
					}
					$this->ld_course_steps_object = LDLMS_Factory_Post::course_steps( $this->course_post->ID );
					$this->ld_course_steps_object->load_steps();
					$lesson_ids = $this->ld_course_steps_object->get_children_steps( $this->course_post->ID, $this->post_type );
					if ( empty( $lesson_ids ) ) {
						return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
					}

					if ( ! in_array( $request['id'], $lesson_ids, true ) ) {
						return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
					}
				}
			}

			return $return;
		}

		/**
		 * Check if a given request has access manage the item.
		 *
		 * @since 2.5.8
		 *
		 * @param WP_REST_Request $request Full data about the request.
		 *
		 * @return WP_Error|bool
		 */
		public function get_items_permissions_check( $request ) {
			$return = parent::get_items_permissions_check( $request );
			if ( ( true === $return ) && ( 'view' === $request['context'] ) ) {

				$course_id = (int) $request['course'];
				if ( ! empty( $course_id ) ) {
					$this->course_post = get_post( $course_id );
					if ( ( ! $this->course_post ) || ( ! is_a( $this->course_post, 'WP_Post' ) ) || ( 'sfwd-courses' !== $this->course_post->post_type ) ) {
						return new WP_Error(
							'rest_post_invalid_id',
							sprintf(
								// translators: placeholder: course.
								esc_html_x(
									'Invalid %s ID.',
									'placeholder: course',
									'learndash'
								),
								LearnDash_Custom_Label::get_label( 'course' )
							),
							array( 'status' => 404 )
						);
					}
				}

				$lesson_id = (int) $request['lesson'];
				if ( ! empty( $lesson_id ) ) {
					$this->lesson_post = get_post( $lesson_id );
					if ( ( ! $this->lesson_post ) || ( ! is_a( $this->lesson_post, 'WP_Post' ) ) || ( 'sfwd-lessons' !== $this->lesson_post->post_type ) ) {
						return new WP_Error(
							'rest_post_invalid_id',
							sprintf(
								// translators: placeholder: Lesson.
								esc_html_x(
									'Invalid %s ID.',
									'placeholder: Lesson',
									'learndash'
								),
								LearnDash_Custom_Label::get_label( 'lesson' )
							),
							array( 'status' => 404 )
						);
					}
				}

				$topic_id = (int) $request['topic'];
				if ( ! empty( $topic_id ) ) {
					$this->topic_post = get_post( $topic_id );
					if ( ( ! $this->topic_post ) || ( ! is_a( $this->topic_post, 'WP_Post' ) ) || ( 'sfwd-topic' !== $this->topic_post->post_type ) ) {
						return new WP_Error(
							'rest_post_invalid_id',
							sprintf(
								// translators: placeholder: Topic.
								esc_html_x(
									'Invalid %s ID.',
									'placeholder: Topic',
									'learndash'
								),
								LearnDash_Custom_Label::get_label( 'topic' )
							),
							array( 'status' => 404 )
						);
					}
				}

				if ( ! learndash_is_admin_user() ) {
					if ( $this->topic_post ) {
						if ( ! $this->course_post ) {
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

						if ( ! $this->lesson_post ) {
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
					}
					if ( $this->lesson_post ) {
						if ( ! $this->course_post ) {
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
					}

					if ( ( $this->course_post ) && ( ! sfwd_lms_has_access( $this->course_post->ID ) ) ) {
						return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
					}
				}
			}

			return $return;
		}

		/**
		 * Filter query args.
		 *
		 * @since 2.5.8
		 *
		 * @param array           $args Key value array of query var to query value.
		 * @param WP_REST_Request $request    The request used.
		 *
		 * @return array Key value array of query var to query value.
		 */
		public function rest_query_filter( $args, $request ) {
			if ( ! $this->is_rest_request( $request ) ) {
				return $args;
			}

			$step_ids = array();

			// The course_post should be set in the local method get_items_permissions_check().
			if ( ( $this->course_post ) && ( is_a( $this->course_post, 'WP_Post' ) ) && ( 'sfwd-courses' === $this->course_post->post_type ) ) {

				if ( $this->topic_post ) {
					$step_ids = learndash_course_get_children_of_step( $this->course_post->ID, $this->topic_post->ID, $this->post_type );
				} elseif ( $this->lesson_post ) {
					$step_ids = learndash_course_get_children_of_step( $this->course_post->ID, $this->lesson_post->ID, $this->post_type );
				} elseif ( $this->course_post ) {
					$lesson_id = $request['lesson'];
					if ( 0 === $lesson_id ) {
						$step_ids = learndash_course_get_children_of_step( $this->course_post->ID, $this->course_post->ID, $this->post_type );
					} else {
						$step_ids = learndash_course_get_steps_by_type( $this->course_post->ID, $this->post_type );
					}
				}

				if ( ! empty( $step_ids ) ) {
					$args['post__in'] = $args['post__in'] ? array_intersect( $step_ids, $args['post__in'] ) : $step_ids;

					$course_lessons_args = learndash_get_course_lessons_order( $this->course_post->ID );
					if ( ! isset( $_GET['orderby'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						if ( isset( $course_lessons_args['orderby'] ) ) {
							$args['orderby'] = $course_lessons_args['orderby'];
						} else {
							$args['orderby'] = 'title';
						}
					}

					if ( ! isset( $_GET['order'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
						if ( isset( $course_lessons_args['order'] ) ) {
							$args['order'] = $course_lessons_args['order'];
						} else {
							$args['order'] = 'ASC';
						}
					}
				} else {
					$args['post__in'] = array( 0 );
				}
			} else {
				if ( get_current_user_id() ) {
					/**
					 * If the user is logged in they can see all GLOBAL quizzes or those not
					 * associated with a course.
					 */
					$step_ids = learndash_get_non_course_qizzes(); // cspell:disable-line.
				} else {
					/**
					 * If the user is NOT logged in they can see all OPEN quizzes or those not
					 * associated with a course AND allowed to be viewed by non-logged in users.
					 */
					$step_ids = learndash_get_open_quizzes( true );
				}

				if ( ! empty( $step_ids ) ) {
					$args['post__in'] = $args['post__in'] ? array_intersect( $step_ids, $args['post__in'] ) : $step_ids;
				}
			}

			return $args;
		}

		// End of functions.
	}
}
