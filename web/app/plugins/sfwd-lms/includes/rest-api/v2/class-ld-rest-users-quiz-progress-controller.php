<?php
/**
 * LearnDash V2 REST API Users Quiz Progress Controller.
 *
 * @since 3.3.0
 * @package LearnDash\REST\V2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LD_REST_Users_Quiz_Progress_Controller_V2' ) ) && ( class_exists( 'LD_REST_Posts_Controller_V2' ) ) ) {

	/**
	 * Class LearnDash V2 REST API Users Quiz Progress Controller.
	 *
	 * @since 3.3.0
	 * @uses LD_REST_Posts_Controller_V2
	 */
	class LD_REST_Users_Quiz_Progress_Controller_V2 extends LD_REST_Posts_Controller_V2 /* phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound */ {

		/**
		 * Supported Collection Parameters.
		 *
		 * @since 3.3.0
		 *
		 * @var array $supported_collection_params.
		 */

		public $supported_collection_params = array(
			'quiz'     => 'quiz_id',
			'course'   => 'course_id',
			'lesson'   => 'lesson_id',
			'topic'    => 'topic_id',
			'offset'   => 'offset',
			'order'    => 'order',
			'orderby'  => 'orderby',
			'per_page' => 'posts_per_page',
			'page'     => 'paged',
			'search'   => 's',
		);

		/**
		 * Public constructor for class
		 *
		 * @since 3.3.0
		 *
		 * @param string $post_type Post type.
		 */
		public function __construct( $post_type = '' ) {
			if ( empty( $post_type ) ) {
				$post_type = learndash_get_post_type_slug( 'quiz' );
			}
			$this->post_type  = $post_type;
			$this->metaboxes  = array();
			$this->taxonomies = array();

			parent::__construct( $this->post_type );

			/**
			 * Set the rest_base after the parent __constructor
			 * as it will set these var with WP specific details.
			 */
			$this->rest_base     = $this->get_rest_base( 'users' );
			$this->rest_sub_base = $this->get_rest_base( 'users-quiz-progress' );
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
						'id'     => array(
							'description' => esc_html__( 'User ID', 'learndash' ),
							'required'    => true,
							'type'        => 'integer',
						),
						'quiz'   => array(
							'description' => sprintf(
								// translators: placeholder: Quiz.
								esc_html_x(
									'%s ID',
									'placeholder: Quiz',
									'learndash'
								),
								LearnDash_Custom_Label::get_label( 'quiz' )
							),
							'required'    => false,
							'type'        => 'integer',
						),
						'course' => array(
							'description' => sprintf(
								// translators: placeholder: Course.
								esc_html_x(
									'%s ID',
									'placeholder: Course',
									'learndash'
								),
								LearnDash_Custom_Label::get_label( 'course' )
							),
							'required'    => false,
							'type'        => 'integer',
						),
						'lesson' => array(
							'description' => sprintf(
								// translators: placeholder: Lesson.
								esc_html_x(
									'%s ID',
									'placeholder: Lesson',
									'learndash'
								),
								LearnDash_Custom_Label::get_label( 'lesson' )
							),
							'required'    => false,
							'type'        => 'integer',
						),
						'topic'  => array(
							'description' => sprintf(
								// translators: placeholder: Topic.
								esc_html_x(
									'%s ID',
									'placeholder: Topic',
									'learndash'
								),
								LearnDash_Custom_Label::get_label( 'topic' )
							),
							'required'    => false,
							'type'        => 'integer',
						),
					),
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_items' ),
						'permission_callback' => array( $this, 'get_items_permissions_check' ),
						'args'                => $this->get_collection_params(),
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
			$schema = array(
				'$schema'    => 'http://json-schema.org/draft-04/schema#',
				'title'      => 'user-quiz-progress',
				'type'       => 'object',
				'properties' => array(
					'quiz'          => array(
						'description' => sprintf(
							// translators: placeholder: Quiz.
							esc_html_x(
								'%s ID',
								'placeholder: Quiz',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'quiz' )
						),
						'type'        => 'integer',
						'context'     => array( 'embed', 'view' ),
						'readonly'    => true,
					),
					'course'        => array(
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
					'lesson'        => array(
						'description' => sprintf(
							// translators: placeholder: Lesson.
							esc_html_x(
								'%s ID',
								'placeholder: Lesson',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'lesson' )
						),
						'type'        => 'integer',
						'context'     => array( 'embed', 'view' ),
						'readonly'    => true,
					),
					'topic'         => array(
						'description' => sprintf(
							// translators: placeholder: Topic.
							esc_html_x(
								'%s ID',
								'placeholder: Topic',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'topic' )
						),
						'type'        => 'integer',
						'context'     => array( 'embed', 'view' ),
						'readonly'    => true,
					),
					'user'          => array(
						'description' => esc_html__( 'User ID', 'learndash' ),
						'type'        => 'integer',
						'context'     => array( 'embed', 'view' ),
						'readonly'    => true,
					),
					'percentage'    => array(
						'description' => esc_html__( 'Percentage passed', 'learndash' ),
						'type'        => 'number',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'timespent'     => array(
						'description' => esc_html__( 'Timespent', 'learndash' ),
						'type'        => 'number',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'has_graded'    => array(
						'description' => esc_html__( 'Has Graded', 'learndash' ),
						'type'        => 'boolean',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'started'       => array(
						'description' => esc_html__( 'Started timestamp', 'learndash' ),
						'type'        => array(
							'string',
							'null',
						),
						'format'      => 'date-time',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'completed'     => array(
						'description' => esc_html__( 'Completed timestamp', 'learndash' ),
						'type'        => array(
							'string',
							'null',
						),
						'format'      => 'date-time',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'points_scored' => array(
						'description' => esc_html__( 'Points scored', 'learndash' ),
						'type'        => 'integer',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'points_total'  => array(
						'description' => esc_html__( 'Points total', 'learndash' ),
						'type'        => 'integer',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
					'statistic'     => array(
						'description' => esc_html__( 'Statistic ID', 'learndash' ),
						'type'        => 'integer',
						'context'     => array( 'view' ),
						'readonly'    => true,
					),
				),
			);
			return $schema;
		}

		/**
		 * Permissions check for getting user progress.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return true|WP_Error True if the request has read access, otherwise WP_Error object.
		 */
		public function get_items_permissions_check( $request ) {
			$user_id = $request['id'];
			if ( empty( $user_id ) ) {
				return new WP_Error( 'rest_user_invalid_id', esc_html__( 'Invalid User ID.', 'learndash' ), array( 'status' => 404 ) );
			}

			if ( is_user_logged_in() ) {
				$current_user_id = get_current_user_id();
			} else {
				$current_user_id = 0;
			}

			if ( empty( $current_user_id ) ) {
				if ( ! current_user_can( 'edit_user', $user_id ) ) {
					return new WP_Error( 'rest_user_invalid_id', __( 'Invalid current user.', 'learndash' ), array( 'status' => 404 ) );
				}
			}

			if ( ( $user_id != $current_user_id ) && ( ! learndash_is_admin_user( $current_user_id ) ) ) {
				if ( ! current_user_can( 'edit_user', $user_id ) ) {
					return new WP_Error( 'rest_cannot_edit', __( 'Sorry, you are not allowed to edit this user.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
				}
			}

			return true;
		}

		/**
		 * Get user course progress.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function get_items( $request ) {
			$user_id = $request['id'];
			$user_id = absint( $user_id );

			if ( empty( $user_id ) ) {
				return new WP_Error( 'rest_user_invalid_id', esc_html__( 'Invalid User ID.', 'learndash' ), array( 'status' => 404 ) );
			}
			$user = get_user_by( 'ID', $user_id );
			if ( ! is_a( $user, 'WP_User' ) ) {
				return new WP_Error( 'rest_user_invalid_id', esc_html__( 'Invalid or unknown user.', 'learndash' ), array( 'status' => 404 ) );
			}

			if ( is_user_logged_in() ) {
				$current_user_id = get_current_user_id();
			} else {
				$current_user_id = 0;
			}

			if ( empty( $current_user_id ) ) {
				return new WP_Error( 'rest_user_invalid_id', esc_html__( 'Invalid current user ID.', 'learndash' ), array( 'status' => 404 ) );
			}

			$user_course_ids = array();
			if ( ( $current_user_id !== $user_id ) && ( ! learndash_is_admin_user( $current_user_id ) ) ) {
				if ( learndash_is_group_leader_user( $current_user_id ) ) {
					if ( ! learndash_is_group_leader_of_user( $current_user_id, $user_id ) ) {
						return new WP_Error( 'rest_user_invalid_id', esc_html__( 'Not allowed to view other user content.', 'learndash' ), array( 'status' => 401 ) );
					}

					$group_leader_group_ids = learndash_get_administrators_group_ids( $current_user_id );
					if ( empty( $group_leader_group_ids ) ) {
						return new WP_Error( 'rest_user_invalid_id', esc_html__( 'Not allowed to view other user content.', 'learndash' ), array( 'status' => 401 ) );
					}
					$user_group_ids = learndash_get_users_group_ids( $user_id );
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

					if ( empty( $user_course_ids ) ) {
						return array();
					} else {
						$user_course_ids = array_map( 'absint', $user_course_ids );
					}
				} else {
					return new WP_Error( 'rest_user_invalid_id', esc_html__( 'Not allowed to view other user content.', 'learndash' ), array( 'status' => 401 ) );
				}
			}

			// Retrieve the list of registered collection query parameters.
			$registered = $this->get_collection_params();

			$atts = array(
				'return' => true,
				'type'   => array( 'quiz' ),
			);

			$request_params = $request->get_query_params();

			/*
			 * For each known parameter which is both registered and present in the request,
			 * set the parameter's value on the query $args.
			 */
			foreach ( $this->supported_collection_params as $api_param => $wp_param ) {
				if ( isset( $registered[ $api_param ], $request_params[ $api_param ] ) ) {
					$atts[ $wp_param ] = $request[ $api_param ];
				}
			}

			if ( isset( $atts['quiz_id'] ) ) {
				$atts['quiz_filter_quiz'] = $atts['quiz_id'];
				unset( $atts['quiz_id'] );

				if ( ! is_array( $atts['quiz_filter_quiz'] ) ) {
					$atts['quiz_filter_quiz'] = explode( ',', $atts['quiz_filter_quiz'] );
				}
				$atts['quiz_filter_quiz'] = array_map( 'absint', $atts['quiz_filter_quiz'] );
			}

			if ( isset( $atts['course_id'] ) ) {
				$atts['quiz_filter_course'] = $atts['course_id'];
				unset( $atts['course_id'] );

				if ( ! is_array( $atts['quiz_filter_course'] ) ) {
					$atts['quiz_filter_course'] = explode( ',', $atts['quiz_filter_course'] );
				}
				$atts['quiz_filter_course'] = array_map( 'absint', $atts['quiz_filter_course'] );
			}

			// If the course_id is set then we can also search for lessons and topics.
			if ( ( isset( $atts['course_id'] ) ) && ( ! empty( $atts['course_id'] ) ) ) {
				if ( isset( $atts['lesson_id'] ) ) {
					$atts['quiz_filter_lesson'] = $atts['lesson_id'];
					unset( $atts['lesson_id'] );

					if ( ! is_array( $atts['quiz_filter_lesson'] ) ) {
						$atts['quiz_filter_lesson'] = explode( ',', $atts['quiz_filter_lesson'] );
					}
					$atts['quiz_filter_lesson'] = array_map( 'absint', $atts['quiz_filter_lesson'] );
				}

				if ( isset( $atts['topic_id'] ) ) {
					$atts['quiz_filter_topic'] = $atts['topic_id'];
					unset( $atts['topic_id'] );

					if ( ! is_array( $atts['quiz_filter_topic'] ) ) {
						$atts['quiz_filter_topic'] = explode( ',', $atts['quiz_filter_topic'] );
					}
					$atts['quiz_filter_topic'] = array_map( 'absint', $atts['quiz_filter_topic'] );
				}
			} else {
				$atts['lesson_id'] = null;
				$atts['topic_id']  = null;
			}

			if ( isset( $atts['posts_per_page'] ) ) {
				$atts['quiz_num'] = $atts['posts_per_page'];
				unset( $atts['posts_per_page'] );
			}
			if ( isset( $atts['orderby'] ) ) {
				$atts['quiz_orderby'] = $atts['orderby'];
				unset( $atts['orderby'] );
			}
			if ( isset( $atts['order'] ) ) {
				$atts['quiz_order'] = $atts['order'];
				unset( $atts['order'] );
			}

			$course_info = SFWD_LMS::get_course_info( $user_id, $atts );

			$quiz_defaults = array(
				'key'           => '',
				'user'          => $user_id,
				'quiz'          => 0,
				'course'        => 0,
				'lesson'        => 0,
				'topic'         => 0,
				'score'         => 0,
				'pass'          => 0,
				'points_scored' => 0,
				'points_total'  => 0,
				'percentage'    => 0.0,
				'timespent'     => 0.0,
				'has_graded'    => false,
				'statistic'     => 0,
				'started'       => '',
				'completed'     => '',
			);

			if ( ( isset( $course_info['quizzes'] ) ) && ( ! empty( $course_info['quizzes'] ) ) ) {
				// Need to convert the timestamp integer value to proper YYYY-MM-DD HH:MM:SS values for response.
				$data_quizzes = array();
				foreach ( $course_info['quizzes'] as $quiz ) {
					foreach ( $quiz_defaults as $_key => $_val ) {
						if ( ! isset( $quiz[ $_key ] ) ) {
							$quiz[ $_key ] = $_val;
						}
					}

					$quiz['key'] = $quiz['time'] . '-' . $quiz['quiz'] . '-' . $quiz['course'];

					if ( isset( $quiz['points'] ) ) {
						$quiz['points_scored'] = $quiz['points'];
						unset( $quiz['points'] );
					}

					if ( isset( $quiz['total_points'] ) ) {
						$quiz['points_total'] = $quiz['total_points'];
						unset( $quiz['total_points'] );
					}

					if ( isset( $quiz['statistic_ref_id'] ) ) {
						$quiz['statistic'] = $quiz['statistic_ref_id'];
						unset( $quiz['statistic_ref_id'] );
					}

					foreach ( $quiz as $_key => $_val ) {
						if ( ! isset( $quiz_defaults[ $_key ] ) ) {
							unset( $quiz[ $_key ] );
						}
					}

					if ( ! empty( $quiz['m_edit_time'] ) ) {
						$quiz['m_edit_time'] = $this->prepare_date_response( gmdate( 'Y-m-d H:i:s', $quiz['m_edit_time'] ) );
					}

					if ( ! empty( $quiz['started'] ) ) {
						$quiz['started'] = $this->prepare_date_response( gmdate( 'Y-m-d H:i:s', $quiz['started'] ) );
					}

					if ( ! empty( $quiz['completed'] ) ) {
						$quiz['completed'] = $this->prepare_date_response( gmdate( 'Y-m-d H:i:s', $quiz['completed'] ) );
					}

					$quiz_key = $quiz['key'];
					unset( $quiz['key'] );
					$quiz = array_merge( array( 'id' => $quiz_key ), $quiz );

					$links = array();

					if ( ! empty( $quiz['quiz'] ) ) {
						$links['quiz'] = array(
							array(
								'href'       => rest_url( $this->namespace . '/' . $this->get_rest_base( 'quizzes' ) . '/' . absint( $quiz['quiz'] ) ),
								'embeddable' => true,
							),
						);
					}

					if ( ! empty( $quiz['course'] ) ) {
						$links['course'] = array(
							array(
								'href'       => rest_url( $this->namespace . '/' . $this->get_rest_base( 'courses' ) . '/' . absint( $quiz['course'] ) ),
								'embeddable' => true,
							),
						);
					}

					if ( ! empty( $quiz['lesson'] ) ) {
						$links['lesson'] = array(
							array(
								'href'       => rest_url( $this->namespace . '/' . $this->get_rest_base( 'lessons' ) . '/' . absint( $quiz['lesson'] ) ),
								'embeddable' => true,
							),
						);
					}

					if ( ! empty( $quiz['topic'] ) ) {
						$links['topic'] = array(
							array(
								'href'       => rest_url( $this->namespace . '/' . $this->get_rest_base( 'topics' ) . '/' . absint( $quiz['topic'] ) ),
								'embeddable' => true,
							),
						);
					}

					if ( ! empty( $quiz['statistic_ref_id'] ) ) {
						$links['statistic_ref_id'] = array(
							array(
								'href'       => rest_url( $this->namespace . '/' . $this->get_rest_base( 'quizzes' ) . '/' . absint( $quiz['quiz'] ) . '/' . $this->get_rest_base( 'quizzes-statistics' ) . '/' . absint( $quiz['statistic_ref_id'] ) ),
								'embeddable' => true,
							),
						);
					}

					if ( ! empty( $links ) ) {
						$quiz['_links'] = $links;
					}

					$data_quizzes[] = $quiz;
				}
				$response = rest_ensure_response( $data_quizzes );

				if ( isset( $course_info['quizzes_pager'] ) ) {
					$response->header( 'X-WP-Total', (int) $course_info['quizzes_pager']['total_items'] );
					$response->header( 'X-WP-TotalPages', (int) $course_info['quizzes_pager']['total_pages'] );

					$request_params = $request->get_query_params();
					$base           = add_query_arg( $request_params, rest_url( sprintf( '%s/%s', $this->namespace, $this->rest_base ) ) );

					$max_pages = (int) $course_info['quizzes_pager']['total_pages'];
					$page      = (int) $course_info['quizzes_pager']['paged'];

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
				}
			} else {
				$response = rest_ensure_response( array() );
			}

			return $response;
		}

		/**
		 * Retrieves the query params for the posts collection.
		 *
		 * @since 3.3.0
		 *
		 * @return array Collection parameters.
		 */
		public function get_collection_params() {
			$query_params_default = parent::get_collection_params();

			$query_params_default['context']['default'] = 'view';

			if ( ! isset( $query_params_default['quiz'] ) ) {
				$query_params_default['quiz'] = array(
					'description' => sprintf(
						// translators: placeholder: quiz.
						esc_html_x(
							'Limit results to be within a specific %s. Required for non-admin users.',
							'placeholder: quiz',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'quiz' )
					),
					'type'        => 'integer',
				);
			}

			$query_params            = array();
			$query_params['context'] = $query_params_default['context'];

			$query_params['orderby']['default'] = 'taken';
			$query_params['orderby']['enum']    = array(
				'taken',
				'title',
				'id',
				'date',
			);

			foreach ( $this->supported_collection_params as $external_key => $internal_key ) {
				if ( ( ! isset( $query_params[ $external_key ] ) ) && ( isset( $query_params_default[ $external_key ] ) ) ) {
					$query_params[ $external_key ] = $query_params_default[ $external_key ];
				}
			}
			return $query_params;
		}

		// End of functions.
	}
}
