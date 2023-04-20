<?php
/**
 * LearnDash REST API V2 Essays Post Controller.
 *
 * This Controller class is used to GET/UPDATE/DELETE the LearnDash
 * custom post type essays (sfwd-essay).
 *
 * This class extends the LD_REST_Posts_Controller_V2 class.
 *
 * @since 3.3.0
 * @package LearnDash\REST\V2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LD_REST_Essays_Controller_V2' ) ) && ( class_exists( 'LD_REST_Posts_Controller_V2' ) ) ) {

	/**
	 * Class LearnDash REST API V2 Essays Post Controller.
	 *
	 * @since 3.3.0
	 * @uses LD_REST_Posts_Controller_V2
	 */
	class LD_REST_Essays_Controller_V2 extends LD_REST_Posts_Controller_V2 /* phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound */ {

		/**
		 * LearnDash course steps object
		 *
		 * @var object
		 */
		protected $ld_course_steps_object = null;

		/**
		 * Essay Post data
		 *
		 * @var array $essay_post_data.
		 */
		private $essay_post_data = array();

		/**
		 * Public constructor for class
		 *
		 * @since 3.3.0
		 *
		 * @param string $post_type Post type.
		 */
		public function __construct( $post_type = '' ) {
			if ( empty( $post_type ) ) {
				$post_type = learndash_get_post_type_slug( 'essay' );
			}
			$this->post_type = $post_type;
			$this->metaboxes = array();

			$this->route_methods_singular   = array( WP_REST_Server::READABLE, WP_REST_Server::EDITABLE );
			$this->route_methods_collection = array( WP_REST_Server::READABLE, WP_REST_Server::EDITABLE, WP_REST_Server::DELETABLE );

			parent::__construct( $this->post_type );

			add_filter( "rest_{$post_type}_collection_params", array( $this, 'rest_collection_params_filter' ), 20, 2 );

			/**
			 * Set the rest_base after the parent __constructor
			 * as it will set these var with WP specific details.
			 */
			$this->rest_base = $this->get_rest_base( 'essays' );
		}

		/**
		 * Registers the routes for the objects of the controller.
		 *
		 * @since 3.3.0
		 *
		 * @see register_rest_route() in WordPress core.
		 */
		public function register_routes() { // phpcs:ignore Generic.CodeAnalysis.UselessOverridingMethod.Found
			parent::register_routes();
		}

		/**
		 * Prepare the LearnDash Post Type Settings.
		 *
		 * @since 3.3.0
		 */
		protected function register_fields() {

			register_rest_field(
				$this->post_type,
				'course',
				array(
					'schema'          => array(
						'field_key'   => 'course',
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
						'default'     => 0,
						'required'    => false,
						'context'     => array( 'view' ),
					),
					'get_callback'    => array( $this, 'get_rest_settings_field_value' ),
					'update_callback' => array( $this, 'update_rest_settings_field_value' ),
				)
			);

			register_rest_field(
				$this->post_type,
				'lesson',
				array(
					'schema'          => array(
						'field_key'   => 'lesson',
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
						'default'     => 0,
						'required'    => false,
						'context'     => array( 'view' ),
					),
					'get_callback'    => array( $this, 'get_rest_settings_field_value' ),
					'update_callback' => array( $this, 'update_rest_settings_field_value' ),
				)
			);

			register_rest_field(
				$this->post_type,
				'topic',
				array(
					'schema'          => array(
						'field_key'   => 'topic',
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
						'default'     => 0,
						'required'    => false,
						'context'     => array( 'view' ),
					),
					'get_callback'    => array( $this, 'get_rest_settings_field_value' ),
					'update_callback' => array( $this, 'update_rest_settings_field_value' ),
				)
			);

			register_rest_field(
				$this->post_type,
				'points_max',
				array(
					'schema'          => array(
						'field_key'   => 'points_max',
						'description' => esc_html__( 'Essay Points Maximum', 'learndash' ),
						'type'        => 'integer',
						'default'     => 0,
						'required'    => false,
						'context'     => array( 'view' ),
					),
					'get_callback'    => array( $this, 'get_rest_settings_field_value' ),
					'update_callback' => array( $this, 'update_rest_settings_field_value' ),
				)
			);
			register_rest_field(
				$this->post_type,
				'points_awarded',
				array(
					'schema'          => array(
						'field_key'   => 'points_awarded',
						'description' => esc_html__( 'Essay Points Awarded', 'learndash' ),
						'type'        => 'integer',
						'default'     => 0,
						'required'    => false,
						'context'     => array( 'view', 'edit' ),
					),
					'get_callback'    => array( $this, 'get_rest_settings_field_value' ),
					'update_callback' => array( $this, 'update_rest_settings_field_value' ),
				)
			);

			$this->register_fields_metabox();

			do_action( 'learndash_rest_register_fields', $this->post_type, $this );

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

			$schema['title'] = 'essay';

			/**
			 * Limit the post status for Essays to 'graded' and 'not_graded'
			 *
			 * @since 3.4.1
			 */
			if ( ( isset( $schema['properties']['status']['enum'] ) ) && ( ! empty( $schema['properties']['status']['enum'] ) ) ) {
				$schema['properties']['status']['enum'] = array_intersect( array( 'graded', 'not_graded' ), $schema['properties']['status']['enum'] );
			}

			return $schema;
		}

		/**
		 * Register the Settings Fields from the Post Metaboxes.
		 *
		 * @since 3.3.0
		 */
		protected function register_fields_metabox() {
			return true;
		}

		/**
		 * Check user permission to get/access single item.
		 *
		 * @since 3.3.0
		 *
		 * @param object $request  WP_REST_Request instance.
		 *
		 * @return bool|WP_Error True if the request has read access for the item, WP_Error object otherwise.
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
					/**
					 * But if the course parameter is provided we need to check the user has access and
					 * also check the step is part of that course.
					 */
					$this->course_post = get_post( $course_id );
					if ( ( ! $this->course_post ) || ( ! is_a( $this->course_post, 'WP_Post' ) ) || ( 'sfwd-courses' !== $this->course_post->post_type ) ) {
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
							) . ' ' . __CLASS__,
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
		 * Check user permission to get/access items.
		 *
		 * @since 3.3.0
		 *
		 * @param object $request  WP_REST_Request instance.
		 *
		 * @return bool|WP_Error True if the request has read access for the item, WP_Error object otherwise.
		 */
		public function get_items_permissions_check( $request ) {
			$return = parent::get_items_permissions_check( $request );
			if ( ! is_user_logged_in() ) {
				$return = false;
			}

			return $return;
		}

		/**
		 * Filter essays query args.
		 *
		 * @since 3.3.0
		 *
		 * @param array           $query_args Key value array of query var to query value.
		 * @param WP_REST_Request $request    The request used.
		 *
		 * @return array          $query_args Key value array of query var to query value.
		 */
		public function rest_query_filter( $query_args, $request ) {
			if ( ! $this->is_rest_request( $request ) ) {
				return $query_args;
			}

			$query_args = parent::rest_query_filter( $query_args, $request );

			$meta_query = array();

			$filters = array(
				'status'    => '',
				'course_id' => 0,
				'lesson_id' => 0,
				'topic_id'  => 0,
			);

			if ( is_user_logged_in() ) {

				$filters['status'] = $request['status'];
				$filters['status'] = array_intersect( array( 'graded', 'not_graded' ), $request['status'] );
				if ( empty( $filters['status'] ) ) {
					$filters['status'] = array( 'graded', 'not_graded' );
				}
				$query_args['post_status'] = $filters['status'];

				$filters['course_id'] = $request['course'];
				$filters['course_id'] = absint( $filters['course_id'] );

				if ( ! empty( $filters['course_id'] ) ) {
					$filters['lesson_id'] = $request['lesson'];
					$filters['lesson_id'] = absint( $filters['lesson_id'] );

					$filters['topic_id'] = $request['topic'];
					$filters['topic_id'] = absint( $filters['topic_id'] );

					if ( ( ! empty( $filters['topic_id'] ) ) && ( learndash_get_post_type_slug( 'topic' ) === get_post_type( $filters['topic_id'] ) ) ) {
						$filters['lesson_id'] = absint( $filters['topic_id'] );
					}
				}

				if ( ! current_user_can( 'edit_others_essays' ) ) {
					if ( learndash_is_group_leader_user() ) {
						$gl_course_ids = array();
						$gl_user_ids   = array();

						$gl_group_ids = learndash_get_administrators_group_ids( get_current_user_id() );
						if ( ! empty( $gl_group_ids ) ) {
							foreach ( $gl_group_ids as $group_id ) {
								$course_ids = learndash_group_enrolled_courses( $group_id );
								if ( ! empty( $course_ids ) ) {
									$gl_course_ids = array_merge( $gl_course_ids, $course_ids );
								}

								$user_ids = learndash_get_groups_user_ids( $group_id );
								if ( ! empty( $user_ids ) ) {
									$gl_user_ids = array_merge( $gl_user_ids, $user_ids );
								}
							}
						}

						if ( ( ! empty( $gl_course_ids ) ) && ( ! empty( $gl_user_ids ) ) ) {
							$gl_course_ids = array_map( 'absint', $gl_course_ids );
							$gl_user_ids   = array_map( 'absint', $gl_user_ids );

							if ( ! empty( $filters['course_id'] ) ) {
								if ( ! in_array( $filters['course_id'], $gl_course_ids, true ) ) {
									$query_args['post__in'] = array( 0 );
								} else {
									$filters['course_id'] = $gl_course_ids;
								}
							} else {
								$filters['course_id'] = $gl_course_ids;
							}

							$query_args['author__in'] = $gl_user_ids;
						} else {
							$query_args['post__in'] = array( 0 );
						}
					} else {
						$query_args['author__in'] = array( get_current_user_id() );

						if ( ! empty( $filters['course_id'] ) ) {
							$user_group_ids = learndash_get_users_group_ids( get_current_user_id(), true );
							$user_group_ids = array_map( 'absint', $user_group_ids );
							if ( ( empty( $user_group_ids ) ) || ( ! in_array( $filters['course_id'], $user_group_ids, true ) ) ) {
								$query_args['post__in'] = array( 0 );
							}
						}
					}
				}
			} else {
				$query_args['post__in'] = array( 0 );
			}

			if ( ! empty( $filters['course_id'] ) ) {
				$meta_query[] = array(
					'key'     => 'course_id',
					'value'   => $filters['course_id'],
					'compare' => is_array( $filters['course_id'] ) ? 'IN' : '=',
				);

				if ( ! empty( $filters['lesson_id'] ) ) {
					$meta_query[] = array(
						'key'     => 'lesson_id',
						'value'   => $filters['lesson_id'],
						'compare' => is_array( $filters['lesson_id'] ) ? 'IN' : '=',
					);
				}

				if ( ! empty( $filters['topic_id'] ) ) {
					$meta_query[] = array(
						'key'     => 'lesson_id',
						'value'   => $filters['topic_id'],
						'compare' => is_array( $filters['topic_id'] ) ? 'IN' : '=',
					);
				}
			}

			if ( ! empty( $meta_query ) ) {
				if ( ( ! isset( $query_args['meta_query'] ) ) || ( empty( $query_args['meta_query'] ) ) ) {
					$query_args['meta_query']   = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					$query_args['meta_query'][] = array( 'relation' => 'AND' );
				} else {
					// Get the 'relation' and set to 'AND'.
					$relation_item = array();
					foreach ( $query_args['meta_query'] as $meta_idx => $meta_item ) {
						if ( ( isset( $meta_item['relation'] ) ) && ( 'AND' !== strtoupper( $meta_item['relation'] ) ) ) {
							$query_args['meta_query'][ $meta_idx ]['relation'] = 'AND';
						}
					}
				}

				$query_args['meta_query'] = array_merge( $query_args['meta_query'], $meta_query ); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
			}

			return $query_args;
		}

		/**
		 * Override the REST response links. This is needed when Course Shared Steps is enabled.
		 *
		 * @since 3.3.0
		 *
		 * @param WP_REST_Response $response WP_REST_Response instance.
		 * @param WP_Post          $post     WP_Post instance.
		 * @param WP_REST_Request  $request  WP_REST_Request instance.
		 */
		public function rest_prepare_response_filter( WP_REST_Response $response, WP_Post $post, WP_REST_Request $request ) {
			if ( $this->post_type === $post->post_type ) {
				$base          = sprintf( '/%s/%s', $this->namespace, $this->rest_base );
				$request_route = $request->get_route();

				if ( ( ! empty( $request_route ) ) && ( strpos( $request_route, $base ) !== false ) ) {

					$links = array();

					$current_links = $response->get_links();

					list( $course_id, $lesson_id, $topic_id, $parent_id ) = $this->get_essay_post_data( $post->ID );

					if ( ( ! isset( $response->links['course'] ) ) && ( ! empty( $course_id ) ) ) {
						$links['course'] = array(
							'href'       => rest_url( trailingslashit( $this->namespace ) . $this->get_rest_base( 'courses' ) . '/' . $course_id ),
							'embeddable' => true,
						);
					}

					if ( ( ! isset( $response->links['lesson'] ) ) && ( ! empty( $lesson_id ) ) ) {
						$lesson_url = rest_url( trailingslashit( $this->namespace ) . $this->get_rest_base( 'lessons' ) . '/' . $lesson_id );

						if ( ! empty( $course_id ) ) {
							$lesson_url = add_query_arg( 'course', $course_id, $lesson_url );
						}

						$links['lesson'] = array(
							'href'       => $lesson_url,
							'embeddable' => true,
						);
					}

					if ( ( ! isset( $response->links['topic'] ) ) && ( ! empty( $topic_id ) ) ) {
						$topic_url = rest_url( trailingslashit( $this->namespace ) . $this->get_rest_base( 'topics' ) . '/' . $topic_id );

						if ( ! empty( $course_id ) ) {
							$topic_url = add_query_arg( 'course', $course_id, $topic_url );
						}

						if ( ! empty( $lesson_id ) ) {
							$topic_url = add_query_arg( 'lesson', $lesson_id, $topic_url );
						}

						$links['topic'] = array(
							'href'       => $topic_url,
							'embeddable' => true,
						);
					}

					if ( ! empty( $links ) ) {
						$response->add_links( $links );
					}
				}
			}

			return $response;
		}

		/**
		 * Add our collection parameters.
		 *
		 * This is added only for GET/OPTIONS Requests.
		 *
		 * @since 3.3.0
		 *
		 * @param array        $query_params Quest params array.
		 * @param WP_Post_Type $post_type    Post type string.
		 */
		public function rest_collection_params_filter( array $query_params, WP_Post_Type $post_type ) {
			if ( $post_type->name === $this->post_type ) {
				if ( isset( $query_params['status'] ) ) {
					$query_params['status']['default']       = 'any';
					$query_params['status']['items']['enum'] = array_intersect( array( 'graded', 'not_graded' ), $query_params['status']['items']['enum'] );
				}

				// We add 'course' to the filtering as an option to filter course steps by.
				if ( ! isset( $query_params['course'] ) ) {
					$query_params['course'] = array(
						'description' => sprintf(
							// translators: placeholder: course.
							esc_html_x(
								'Filter by %s ID',
								'placeholder: course',
								'learndash'
							),
							LearnDash_Custom_Label::get_label( 'course' )
						),
						'type'        => 'integer',
						'required'    => false,
						'context'     => array( 'view', 'edit' ),
					);
				}

				if ( ! isset( $query_params['lesson'] ) ) {
					$query_params['lesson'] = array(
						'description' => sprintf(
							// translators: placeholder: Lesson.
							esc_html_x( 'Filter by %s ID', 'placeholder: Lesson', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'lesson' )
						),
						'type'        => 'integer',
						'default'     => 0,
						'context'     => array( 'view' ),
						'required'    => false,
					);
				}

				if ( ! isset( $query_params['topic'] ) ) {
					$query_params['topic'] = array(
						'description' => sprintf(
							// translators: placeholder: Topic.
							esc_html_x( 'Filter by %s ID', 'placeholder: Topic', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'topic' )
						),
						'type'        => 'integer',
						'default'     => 0,
						'context'     => array( 'view' ),
						'required'    => false,
					);
				}
			}

			return $query_params;
		}

		/**
		 * Get REST Setting Field value.
		 *
		 * @since 3.3.0
		 *
		 * @param array           $postdata   Post data array.
		 * @param string          $field_name Field Name for $postdata value.
		 * @param WP_REST_Request $request    Request object.
		 * @param string          $post_type  Post Type for request.
		 */
		public function get_rest_settings_field_value( array $postdata, $field_name, WP_REST_Request $request, $post_type ) {

			if ( ( isset( $postdata['id'] ) ) && ( ! empty( $postdata['id'] ) ) && ( $post_type == $this->post_type ) ) {
				$field_value = '';

				$essay = get_post( $postdata['id'] );

				list( $course_id, $lesson_id, $topic_id, $parent_id ) = $this->get_essay_post_data( $postdata['id'] );

				$quiz_id     = get_post_meta( $postdata['id'], 'quiz_id', true );
				$question_id = get_post_meta( $postdata['id'], 'question_id', true );

				if ( ! empty( $quiz_id ) ) {
					$question_mapper = new WpProQuiz_Model_QuestionMapper();
					$question        = $question_mapper->fetchById( intval( $question_id ), null );
				}

				$submitted_essay_data = null;
				if ( $question && is_a( $question, 'WpProQuiz_Model_Question' ) ) {
					$submitted_essay_data = learndash_get_submitted_essay_data( $quiz_id, $question->getId(), $essay );
				}

				switch ( $field_name ) {
					case 'course':
						if ( ! empty( $course_id ) ) {
							$course_post = get_post( $course_id );
							if ( ( ! $course_post ) && ( ! is_a( $course_post, 'WP_Post' ) ) ) {
								$course_id = 0;
							}
						} else {
							$course_id = 0;
						}
						$field_value = (int) $course_id;
						break;

					case 'lesson':
						if ( ! empty( $lesson_id ) ) {
							$lesson_post = get_post( $lesson_id );
							if ( ( ! $lesson_post ) && ( ! is_a( $lesson_post, 'WP_Post' ) ) ) {
								$lesson_id = 0;
							}
						} else {
							$lesson_id = 0;
						}
						$field_value = (int) $lesson_id;
						break;

					case 'topic':
						if ( ! empty( $topic_id ) ) {
							$topic_post = get_post( $topic_id );
							if ( ( ! $topic_post ) && ( ! is_a( $topic_post, 'WP_Post' ) ) ) {
								$topic_id = 0;
							}
						} else {
							$topic_id = 0;
						}
						$field_value = (int) $topic_id;
						break;

					case 'points_max':
						if ( is_a( $question, 'WpProQuiz_Model_Question' ) ) {
							$field_value = $question->getPoints();
						}
						break;

					case 'points_awarded':
						if ( isset( $submitted_essay_data['points_awarded'] ) ) {
							$field_value = $submitted_essay_data['points_awarded'];
						} else {
							$field_value = 0;
						}
						break;

					default:
						break;
				}

				return $field_value;
			}
		}

		/**
		 * Initialize the essay Post data elements used by many class functions.
		 *
		 * @since 3.3.0
		 *
		 * @param int $post_id essay Post ID.
		 */
		protected function get_essay_post_data( $post_id = 0 ) {
			$course_id = 0;
			$lesson_id = 0;
			$topic_id  = 0;
			$parent_id = 0;

			if ( ! empty( $post_id ) ) {
				if ( ! isset( $this->essay_post_data[ $post_id ] ) ) {

					$course_id = (int) get_post_meta( $post_id, 'course_id', true );
					$lesson_id = (int) get_post_meta( $post_id, 'lesson_id', true );

					if ( learndash_get_post_type_slug( 'topic' ) === get_post_type( $lesson_id ) ) {
						$topic_id  = absint( $lesson_id );
						$lesson_id = (int) learndash_course_get_single_parent_step( $course_id, $topic_id );
					}

					$this->essay_post_data[ $post_id ]['course_id'] = $course_id;
					$this->essay_post_data[ $post_id ]['lesson_id'] = $lesson_id;
					$this->essay_post_data[ $post_id ]['topic_id']  = $topic_id;
				} else {
					if ( isset( $this->essay_post_data[ $post_id ]['course_id'] ) ) {
						$course_id = $this->essay_post_data[ $post_id ]['course_id'];
					}
					if ( isset( $this->essay_post_data[ $post_id ]['lesson_id'] ) ) {
						$lesson_id = $this->essay_post_data[ $post_id ]['lesson_id'];
					}
					if ( isset( $this->essay_post_data[ $post_id ]['topic_id'] ) ) {
						$topic_id = $this->essay_post_data[ $post_id ]['topic_id'];
					}
				}

				if ( ! empty( $topic_id ) ) {
					$parent_id = $topic_id;
				} elseif ( ! empty( $lesson_id ) ) {
					$parent_id = $lesson_id;
				}
			}

			return array( $course_id, $lesson_id, $topic_id, $parent_id );
		}

		/**
		 * Update REST Settings Field value.
		 *
		 * @since 3.3.0
		 *
		 * @param mixed           $post_value  Value of setting to update.
		 * @param WP_Post         $post        Post object being updated.
		 * @param string          $field_name  Settings file name/key.
		 * @param WP_REST_Request $request     Request object.
		 * @param string          $post_type   Post type string.
		 */
		public function update_rest_settings_field_value( $post_value, WP_Post $post, $field_name, WP_REST_Request $request, $post_type ) {
			if ( ( is_a( $post, 'WP_Post' ) ) && ( $post->post_type == $this->post_type ) ) {

				switch ( $field_name ) {
					case 'points_awarded':
						$quiz_id     = get_post_meta( $post->ID, 'quiz_id', true );
						$question_id = get_post_meta( $post->ID, 'question_id', true );

						if ( ! empty( $quiz_id ) ) {
							if ( 'graded' !== $post->post_status ) {
								$quiz_score_difference = 1;
							}

							$question_mapper = new WpProQuiz_Model_QuestionMapper();
							$question        = $question_mapper->fetchById( intval( $question_id ), null );
							if ( $question instanceof WpProQuiz_Model_Question ) {
								$submitted_essay_data = learndash_get_submitted_essay_data( $quiz_id, $question_id, $post );

								$max_points = $question->getPoints();
								$max_points = absint( $max_points );

								if ( isset( $submitted_essay_data['points_awarded'] ) ) {
									$original_points_awarded = intval( $submitted_essay_data['points_awarded'] );
								} else {
									$original_points_awarded = 0;
								}

								$awarded_points = absint( $post_value );

								// Check that award points is not greater then max points.
								if ( $awarded_points > $max_points ) {
									$awarded_points = $max_points;
								}

								if ( $awarded_points !== $original_points_awarded ) {
									$submitted_essay_data['status'] = 'graded';

									// set the new assigned points.
									$submitted_essay_data['points_awarded'] = $awarded_points;

									/** This filter is documented in includes/quiz/ld-quiz-essays.php */
									$submitted_essay_data = apply_filters( 'learndash_essay_status_data', $submitted_essay_data );
									learndash_update_submitted_essay_data( $quiz_id, $question_id, $post, $submitted_essay_data );

									if ( ! is_null( $original_points_awarded ) && ! is_null( $submitted_essay_data['points_awarded'] ) ) {
										if ( $submitted_essay_data['points_awarded'] > $original_points_awarded ) {
											$points_awarded_difference = intval( $submitted_essay_data['points_awarded'] ) - intval( $original_points_awarded );
										} else {
											$points_awarded_difference = ( intval( $original_points_awarded ) - intval( $submitted_essay_data['points_awarded'] ) ) * -1;
										}

										$updated_scoring_data = array(
											'updated_question_score' => $submitted_essay_data['points_awarded'],
											'points_awarded_difference' => $points_awarded_difference,
											'score_difference' => $quiz_score_difference,
										);

										/** This filter is documented in includes/quiz/ld-quiz-essays.php */
										$updated_scoring = apply_filters( 'learndash_updated_essay_scoring', $updated_scoring_data );
										learndash_update_quiz_data( $quiz_id, $question_id, $updated_scoring_data, $post );

										/** This action is documented in includes/quiz/ld-quiz-essays.php */
										do_action( 'learndash_essay_all_quiz_data_updated', $quiz_id, $question_id, $updated_scoring_data, $post );
									}
								}
							}
						}

						break;

					// We don't allow updates to Course, Lesson, Lesson Points enabled, Lesson Points max.
					case 'course':
					case 'lesson':
					case 'points_enabled':
					case 'points_max':
					default:
						break;
				}
			}
		}

		// End of functions.
	}
}
