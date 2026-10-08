<?php
/**
 * LearnDash REST API V2 Quizzes Post Controller.
 *
 * This Controller class is used to GET/UPDATE/DELETE the LearnDash
 * custom post type Quizzes (sfwd-quiz).
 *
 * This class extends the LD_REST_Posts_Controller_V2 class.
 *
 * @since 3.3.0
 * @package LearnDash\REST\V2
 */

use LearnDash\Core\Mappers\Models\Quiz_Id_Mapper;
use LearnDash\Core\Utilities\Cast;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LD_REST_Quizzes_Controller_V2' ) ) && ( class_exists( 'LD_REST_Posts_Controller_V2' ) ) ) {

	/**
	 * Class LearnDash REST API V2 Quizzes Post Controller.
	 *
	 * @since 3.3.0
	 * @uses LD_REST_Posts_Controller_V2
	 */
	class LD_REST_Quizzes_Controller_V2 extends LD_REST_Posts_Controller_V2 /* phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound */ {

		/**
		 * LearnDash course steps object
		 *
		 * @var object
		 */
		protected $ld_course_steps_object = null;

		/**
		 * WP ProQuiz Post placeholder
		 *
		 * @var array
		 */
		protected $_post = array(); // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

		/**
		 * WP ProQuiz get arguments
		 *
		 * @var array
		 */
		protected $_get = array(); // phpcs:ignore PSR2.Classes.PropertyDeclaration.Underscore

		/**
		 * WPProQuiz Quiz instance.
		 * This is used to bridge the WPProQuiz to WP systems.
		 *
		 * @var object $pro_quiz_edit WPProQuiz instance.
		 */
		private $pro_quiz_edit = null;

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
			$this->post_type = $post_type;
			$this->metaboxes = array();

			parent::__construct( $this->post_type );

			/**
			 * Set the rest_base after the parent __constructor
			 * as it will set these var with WP specific details.
			 */
			$this->rest_base = $this->get_rest_base( 'quizzes' );
		}

		/**
		 * Prepare the LearnDash Post Type Settings.
		 *
		 * @since 3.3.0
		 */
		protected function register_fields() {
			$this->register_fields_metabox();

			do_action( 'learndash_rest_register_fields', $this->post_type, $this );
		}

		/**
		 * Register the Settings Fields from the Post Metaboxes.
		 *
		 * @since 3.3.0
		 */
		protected function register_fields_metabox() {
			require_once LEARNDASH_LMS_PLUGIN_DIR . '/includes/settings/settings-metaboxes/class-ld-settings-metabox-quiz-access-settings.php';
			$this->metaboxes['LearnDash_Settings_Metabox_Quiz_Access_Settings'] = LearnDash_Settings_Metabox_Quiz_Access_Settings::add_metabox_instance();

			require_once LEARNDASH_LMS_PLUGIN_DIR . '/includes/settings/settings-metaboxes/class-ld-settings-metabox-quiz-progress-settings.php';
			$this->metaboxes['LearnDash_Settings_Metabox_Quiz_Progress_Settings'] = LearnDash_Settings_Metabox_Quiz_Progress_Settings::add_metabox_instance();

			require_once LEARNDASH_LMS_PLUGIN_DIR . '/includes/settings/settings-metaboxes/class-ld-settings-metabox-quiz-display-content.php';
			$this->metaboxes['LearnDash_Settings_Metabox_Quiz_Display_Content'] = LearnDash_Settings_Metabox_Quiz_Display_Content::add_metabox_instance();

			require_once LEARNDASH_LMS_PLUGIN_DIR . '/includes/settings/settings-metaboxes/class-ld-settings-metabox-quiz-results-display-content-options.php';
			$this->metaboxes['LearnDash_Settings_Metabox_Quiz_Results_Options'] = LearnDash_Settings_Metabox_Quiz_Results_Options::add_metabox_instance();

			require_once LEARNDASH_LMS_PLUGIN_DIR . '/includes/settings/settings-metaboxes/class-ld-settings-metabox-quiz-admin-data-handling-settings.php';
			$this->metaboxes['LearnDash_Settings_Metabox_Quiz_Admin_Data_Handling_Settings'] = LearnDash_Settings_Metabox_Quiz_Admin_Data_Handling_Settings::add_metabox_instance();

			if ( ! empty( $this->metaboxes ) ) {
				foreach ( $this->metaboxes as $metabox ) {
					$metabox->load_settings_values();
					$metabox->load_settings_fields();

					$this->register_rest_fields( $metabox->get_rest_api_fields(), $metabox );
				}
			}
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

			$schema['title'] = 'quiz';

			return $schema;
		}

		/**
		 * For LearnDash post type we override the default order/orderby
		 * to ASC/title instead of the WP default DESC/date.
		 *
		 * @since 3.3.0
		 *
		 * @param array        $query_params Quest params array.
		 * @param WP_Post_Type $post_type    Post type string.
		 */
		public function rest_collection_params_filter( array $query_params, WP_Post_Type $post_type ) {
			$query_params = parent::rest_collection_params_filter( $query_params, $post_type );

			if ( ! isset( $query_params['course'] ) ) {
				$query_params['course'] = array(
					'description' => sprintf(
						// translators: placeholder: course.
						esc_html_x(
							'Limit results to be within a specific %s.',
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
						// translators: placeholder: lesson.
						esc_html_x(
							'Limit results to be within a specific %s.',
							'placeholder: lesson',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'lesson' )
					),
					'type'        => 'integer',
				);
			}
			if ( ! isset( $query_params['topic'] ) ) {
				$query_params['topic'] = array(
					'description' => sprintf(
						// translators: placeholder: topic.
						esc_html_x(
							'Limit results to be within a specific %s.',
							'placeholder: topic',
							'learndash'
						),
						LearnDash_Custom_Label::get_label( 'topic' )
					),
					'type'        => 'integer',
				);
			}

			return $query_params;
		}

		/**
		 * Check user permission to get/access single Quiz.
		 *
		 * @since 3.3.0
		 *
		 * @param object $request  WP_REST_Request instance.
		 * @return bool True is used can get item.
		 */
		public function get_item_permissions_check( $request ) {
			$return = parent::get_item_permissions_check( $request );
			if ( ( true === $return ) && ( ! learndash_is_admin_user() ) ) {

				$course_id = (int) $request['course'];
				if ( ! empty( $course_id ) ) {
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
					$GLOBALS['course_id'] = $course_id;
				}

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

					if ( ! sfwd_lms_has_access( $this->course_post->ID ) ) {
						return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
					}
					$this->ld_course_steps_object = LDLMS_Factory_Post::course_steps( $this->course_post->ID );
					$this->ld_course_steps_object->load_steps();
					$quiz_ids = $this->ld_course_steps_object->get_children_steps( $this->course_post->ID, $this->post_type, 'ids', true );
					if ( empty( $quiz_ids ) ) {
						return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
					}

					if ( ! in_array( absint( $request['id'] ), $quiz_ids, true ) ) {
						return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
					}
				}
			}

			return $return;
		}

		/**
		 * Check user permission to get/access Quizzes.
		 *
		 * @since 3.3.0
		 * @since 5.0.0 Corrected the type of $request to WP_REST_Request<array<string,mixed>>.
		 * @since 5.0.0 Corrected the return type to true|WP_Error.
		 * @since 5.0.0 Non-Admins can now provide only a Lesson or Topic if desired without specifying a Course.
		 *
		 * @param WP_REST_Request<array<string,mixed>> $request WP_REST_Request instance.
		 *
		 * @return true|WP_Error True is used can get item, WP_Error on failure.
		 */
		public function get_items_permissions_check( $request ) {
			$return = parent::get_items_permissions_check( $request );
			$this->rest_init_request_posts( $request );

			if ( learndash_is_admin_user() ) {
				return $return;
			}

			$has_course_parameter = $this->course_post !== null
				&& $this->course_post instanceof WP_Post
				&& $this->course_post->post_type === learndash_get_post_type_slug( LDLMS_Post_Types::COURSE );

			$has_lesson_parameter = $this->lesson_post !== null
				&& $this->lesson_post instanceof WP_Post
				&& $this->lesson_post->post_type === learndash_get_post_type_slug( LDLMS_Post_Types::LESSON );

			$has_topic_parameter = $this->topic_post !== null
				&& $this->topic_post instanceof WP_Post
				&& $this->topic_post->post_type === learndash_get_post_type_slug( LDLMS_Post_Types::TOPIC );

			$course_id = $has_course_parameter ? $this->course_post->ID : 0;
			$lesson_id = $has_lesson_parameter ? $this->lesson_post->ID : 0;
			$topic_id  = $has_topic_parameter ? $this->topic_post->ID : 0;

			if (
				! $has_course_parameter
				&& ! $has_lesson_parameter
				&& ! $has_topic_parameter
			) {
				return new WP_Error(
					'rest_post_invalid_id',
					esc_html(
						sprintf(
							// translators: placeholder: %1$s course label, %2$s lesson label, %3$s topic label.
							__( 'You must specify either a valid %1$s, %2$s, or %3$s ID.', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'course' ),
							LearnDash_Custom_Label::get_label( 'lesson' ),
							LearnDash_Custom_Label::get_label( 'topic' )
						)
					)
				);
			}

			if (
				(
					$has_topic_parameter
					&& ! sfwd_lms_has_access( $topic_id )
				)
				|| (
					$has_lesson_parameter
					&& ! sfwd_lms_has_access( $lesson_id )
				)
				|| (
					$has_course_parameter
					&& ! sfwd_lms_has_access( $course_id )
				)
			) {
				return new WP_Error(
					'ld_rest_cannot_view',
					esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ),
					[
						'status' => rest_authorization_required_code(),
					]
				);
			}

			return $return;
		}

		/**
		 * Filter query args.
		 *
		 * @since 3.3.0
		 *
		 * @param array           $query_args  Key value array of query var to query value.
		 * @param WP_REST_Request $request     The request used.
		 *
		 * @return array          $query_args  Key value array of query var to query value.
		 */
		public function rest_query_filter( $query_args, $request ) {
			if ( ! $this->is_rest_request( $request ) ) {
				return $query_args;
			}

			$query_args = parent::rest_query_filter( $query_args, $request );

			$has_course_parameter = $this->course_post !== null
				&& $this->course_post instanceof WP_Post
				&& $this->course_post->post_type === learndash_get_post_type_slug( LDLMS_Post_Types::COURSE );

			$has_lesson_parameter = $this->lesson_post !== null
				&& $this->lesson_post instanceof WP_Post
				&& $this->lesson_post->post_type === learndash_get_post_type_slug( LDLMS_Post_Types::LESSON );

			$has_topic_parameter = $this->topic_post !== null
				&& $this->topic_post instanceof WP_Post
				&& $this->topic_post->post_type === learndash_get_post_type_slug( LDLMS_Post_Types::TOPIC );

			// The course_post should be set in the local method get_items_permissions_check() when applicable.
			if (
				! $has_course_parameter
				&& ! $has_lesson_parameter
				&& ! $has_topic_parameter
			) {
				return $query_args;
			}

			$step_ids = [];

			$course_id = $has_course_parameter ? $this->course_post->ID : 0;
			$lesson_id = $has_lesson_parameter ? $this->lesson_post->ID : 0;
			$topic_id  = $has_topic_parameter ? $this->topic_post->ID : 0;

			if ( $has_topic_parameter ) {
				// Attempt to get the course ID from the topic post if the Course parameter is not set.
				$course_id = $course_id ? $course_id : Cast::to_int( learndash_get_course_id( $topic_id ) );

				$step_ids = learndash_course_get_children_of_step( $course_id, $topic_id, $this->post_type );
			} elseif ( $has_lesson_parameter ) {
				// Attempt to get the course ID from the lesson post if the Course parameter is not set.
				$course_id = $course_id ? $course_id : Cast::to_int( learndash_get_course_id( $lesson_id ) );

				$step_ids = learndash_course_get_children_of_step( $course_id, $lesson_id, $this->post_type );
			} else {
				$step_ids = learndash_course_get_steps_by_type( $course_id, $this->post_type );
			}

			if ( ! empty( $step_ids ) ) {
				$query_args['post__in'] = isset( $query_args['post__in'] ) && is_array( $query_args['post__in'] )
					? array_intersect( $step_ids, $query_args['post__in'] )
					: $step_ids;

				$course_lessons_args = learndash_get_course_lessons_order( $course_id );

				if ( $request->get_param( 'orderby' ) === null ) {
					if ( ! empty( $course_lessons_args['orderby'] ) ) {
						$query_args['orderby'] = $course_lessons_args['orderby'];
					} else {
						$query_args['orderby'] = 'title';
					}
				}

				if ( $request->get_param( 'order' ) === null ) {
					if ( ! empty( $course_lessons_args['order'] ) ) {
						$query_args['order'] = $course_lessons_args['order'];
					} else {
						$query_args['order'] = 'ASC';
					}
				}
			} else {
				$query_args['post__in'] = [ 0 ];
			}

			return $query_args;
		}

		/**
		 * Get REST Setting Field value.
		 *
		 * @since 3.3.0
		 * @since 5.0.0 The `prerequisites` field is now converted to Post IDs (from Pro Quiz IDs). We don't want to manipulate Pro Quiz IDs in REST API.
		 *
		 * @param array           $postdata   Post data array.
		 * @param string          $field_name Field Name for $postdata value.
		 * @param WP_REST_Request $request    Request object.
		 * @param string          $post_type  Post Type for request.
		 */
		public function get_rest_settings_field_value( array $postdata, $field_name, WP_REST_Request $request, $post_type ) {
			$field_value = parent::get_rest_settings_field_value( $postdata, $field_name, $request, $post_type );

			if ( 'lesson' === $field_name ) {
				$field_value = learndash_course_get_single_parent_step( $postdata['course'], $postdata['id'], learndash_get_post_type_slug( 'lesson' ) );
			} elseif ( 'topic' === $field_name ) {
				$field_value = learndash_course_get_single_parent_step( $postdata['course'], $postdata['id'], learndash_get_post_type_slug( 'topic' ) );
			} elseif (
				'prerequisites' === $field_name
				&& is_array( $field_value )
			) {
				$field_value = Quiz_Id_Mapper::to_post_ids( $field_value );
			}

			return $field_value;
		}

		/**
		 * Update REST Settings Field value.
		 *
		 * @since 5.0.0 The `prerequisites` field is now converted to Pro Quiz IDs (from Post IDs). We don't want to manipulate Pro Quiz IDs in REST API.
		 *
		 * @param mixed                                $post_value Value of setting to update.
		 * @param WP_Post                              $post       Post object being updated.
		 * @param string                               $field_name Settings file name/key.
		 * @param WP_REST_Request<array<string,mixed>> $request    Request object.
		 * @param string                               $post_type  Post type string.
		 *
		 * @return bool|int
		 */
		public function update_rest_settings_field_value( $post_value, WP_Post $post, $field_name, $request, $post_type ) {
			if (
				'prerequisites' === $field_name
				&& is_array( $post_value )
			) {
				$post_value = Quiz_Id_Mapper::to_pro_quiz_ids( $post_value );
			}

			return parent::update_rest_settings_field_value( $post_value, $post, $field_name, $request, $post_type );
		}

		/**
		 * Override the REST response links.
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

					if ( ! isset( $response->links['statistics'] ) ) {
						$quiz_pro_id = get_post_meta( $post->ID, 'quiz_pro_id', true );
						$quiz_pro_id = absint( $quiz_pro_id );
						if ( ! empty( $quiz_pro_id ) ) {
							$quiz_pro_statistics_on = learndash_get_setting( $post, 'statisticsOn', true );
							if ( $quiz_pro_statistics_on ) {
								$links['statistics'] = array(
									'href'       => rest_url( trailingslashit( $base ) . $post->ID . '/' . $this->get_rest_base( 'quizzes-statistics' ) ),
									'embeddable' => true,
								);
							}
						}
					}

					if ( ! isset( $response->links['users'] ) ) {
						$links['users'] = array(
							'href'       => rest_url( trailingslashit( $base ) . $post->ID ) . '/users',
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
		 * Fires after a single post is completely created or updated via the REST API.
		 *
		 * @since 3.4.0
		 *
		 * @param WP_Post         $post     Inserted or updated post object.
		 * @param WP_REST_Request $request  Request object.
		 * @param bool            $creating True when creating a post, false when updating.
		 */
		public function rest_after_insert_action( $post, $request, $creating ) {
			if ( ( $post ) && ( is_a( $post, 'WP_Post' ) ) && ( $post->post_type === $this->post_type ) ) {
				$this->init_quiz_edit( $post );

				parent::rest_after_insert_action( $post, $request, $creating );

				$quiz_post_data     = array();
				$quiz_post_data_tmp = get_post_meta( $post->ID, '_' . $this->post_type, true );
				if ( ! empty( $quiz_post_data_tmp ) ) {
					foreach ( $quiz_post_data_tmp as $_key => $_val ) {
						if ( substr( $_key, 0, strlen( $this->post_type . '_' ) ) === $this->post_type . '_' ) {
							$_key                    = str_replace( $this->post_type . '_', '', $_key );
							$quiz_post_data[ $_key ] = $_val;
						}
					}
				}

				/**
				 * Clear ouf the form array as it will be set when saving
				 * the Quiz post and is handled in
				 * includes/admin/classes-posts-edits/class-learndash-admin-quiz-edit.php
				 */
				$quiz_post_data['form'] = array();

				if ( ! isset( $quiz_post_data['post_ID'] ) ) {
					$quiz_post_data['post_ID'] = $post->ID;
				}

				$quiz_id  = absint( learndash_get_setting( $post->ID, 'quiz_pro', true ) );
				$pro_quiz = new WpProQuiz_Controller_Quiz();
				$pro_quiz->route(
					array(
						'action'  => 'addUpdateQuiz',
						'quizId'  => $quiz_id,
						'post_id' => $post->ID,
					),
					$quiz_post_data
				);

				foreach ( $this->metaboxes as $metabox ) {
					$metabox->init( $post, true );
				}
			}
		}

		/**
		 * Initialize the ProQuiz Quiz being edited.
		 *
		 * @since 3.4.1
		 * @param object $post WP_Post Question being edited.
		 */
		public function init_quiz_edit( $post ) {
			if ( is_null( $this->pro_quiz_edit ) ) {
				$quiz_pro_id = (int) learndash_get_setting( $post->ID, 'quiz_pro' );

				$this->_post = array( '1' );
				$this->_get  = array(
					'action'  => 'getEdit',
					'quizId'  => $quiz_pro_id,
					'post_id' => $post->ID,
				);

				if ( ( isset( $_GET['templateLoadId'] ) ) && ( ! empty( $_GET['templateLoadId'] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$this->_get['templateLoad']   = 'yes';
					$this->_get['templateLoadId'] = absint( $_GET['templateLoadId'] ); // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				}

				$pro_quiz            = new WpProQuiz_Controller_Quiz();
				$this->pro_quiz_edit = $pro_quiz->route(
					$this->_get,
					$this->_post
				);
			}
		}

		// End of functions.
	}
}
