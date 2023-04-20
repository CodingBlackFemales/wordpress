<?php
/**
 * LearnDash REST API V2 Lessons Post Controller.
 *
 * This Controller class is used to GET/UPDATE/DELETE the LearnDash
 * custom post type Lessons (sfwd-lessons).
 *
 * This class extends the LD_REST_Posts_Controller_V2 class.
 *
 * @since 3.3.0
 * @package LearnDash\REST\V2
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LD_REST_Lessons_Controller_V2' ) ) && ( class_exists( 'LD_REST_Posts_Controller_V2' ) ) ) {

	/**
	 * Class LearnDash REST API V2 Lessons Post Controller.
	 *
	 * @since 3.3.0
	 * @uses LD_REST_Posts_Controller_V2
	 */
	class LD_REST_Lessons_Controller_V2 extends LD_REST_Posts_Controller_V2 /* phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound */ {

		/**
		 * LearnDash course steps object
		 *
		 * @var object
		 */
		protected $ld_course_steps_object = null;

		/**
		 * Public constructor for class
		 *
		 * @since 3.3.0
		 *
		 * @param string $post_type Post type.
		 */
		public function __construct( $post_type = '' ) {
			if ( empty( $post_type ) ) {
				$post_type = learndash_get_post_type_slug( 'lesson' );
			}
			$this->post_type = $post_type;
			$this->metaboxes = array();

			parent::__construct( $this->post_type );

			/**
			 * Set the rest_base after the parent __constructor
			 * as it will set these var with WP specific details.
			 */
			$this->rest_base = $this->get_rest_base( 'lessons' );
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

			require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/settings/settings-metaboxes/class-ld-settings-metabox-lesson-display-content.php';
			$this->metaboxes['LearnDash_Settings_Metabox_Lesson_Display_Content'] = LearnDash_Settings_Metabox_Lesson_Display_Content::add_metabox_instance();

			require_once LEARNDASH_LMS_PLUGIN_DIR . '/includes/settings/settings-metaboxes/class-ld-settings-metabox-lesson-access-settings.php';
			$this->metaboxes['LearnDash_Settings_Metabox_Lesson_Access_Settings'] = LearnDash_Settings_Metabox_Lesson_Access_Settings::add_metabox_instance();

			if ( ! empty( $this->metaboxes ) ) {
				foreach ( $this->metaboxes as $metabox ) {
					$metabox->load_settings_values();
					$metabox->load_settings_fields();

					$this->register_rest_fields( $metabox->get_settings_metabox_fields(), $metabox );
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

			$schema['title'] = 'lesson';

			return $schema;
		}

		/**
		 * Check user permission to get/access single Lesson.
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
				if ( ! empty( $course_id ) ) {
					// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
					$GLOBALS['course_id'] = $course_id;
				}

				$lesson_id = (int) $request['id'];
				if ( ( $lesson_id ) && ( sfwd_lms_has_access( $lesson_id ) ) ) {
					return true;
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
		 * Check user permission to get/access Lessons.
		 *
		 * @since 3.3.0
		 *
		 * @param object $request  WP_REST_Request instance.
		 *
		 * @return bool|WP_Error True if the request has read access for the item, WP_Error object otherwise.
		 */
		public function get_items_permissions_check( $request ) {
			$return = parent::get_items_permissions_check( $request );
			$this->rest_init_request_posts( $request );
			if ( ( true === $return ) && ( 'view' === $request['context'] ) && ( ! learndash_is_admin_user() ) ) {
				// If the archive setting is enabled we allow full listing.
				if ( ! $this->rest_post_type_has_archive( $this->post_type ) ) {
					if ( is_null( $this->course_post ) ) {
						return new WP_Error(
							'rest_post_invalid_id',
							sprintf(
								// translators: placeholder: Course.
								esc_html_x( 'Invalid %s ID', 'placeholder: Course', 'learndash' ),
								learndash_get_custom_label( 'course' )
							),
							array( 'status' => 404 )
						);
					}

					if ( ! sfwd_lms_has_access( $this->course_post->ID ) ) {
						return new WP_Error( 'ld_rest_cannot_view', esc_html__( 'Sorry, you are not allowed to view this item.', 'learndash' ), array( 'status' => rest_authorization_required_code() ) );
					}
				}
			}

			return $return;
		}

		/**
		 * Filter Lessons query args.
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

			if ( ! is_null( $this->course_post ) ) {
				$step_ids = learndash_course_get_steps_by_type( $this->course_post->ID, $this->post_type );
				if ( ! empty( $step_ids ) ) {
					$query_args['post__in'] = $query_args['post__in'] ? array_intersect( $step_ids, $query_args['post__in'] ) : $step_ids;
					if ( ! empty( $query_args['post__in'] ) ) {
						$course_lessons_args = learndash_get_course_lessons_order( $this->course_post->ID );
						if ( isset( $course_lessons_args['orderby'] ) ) {
							$query_args['orderby'] = $course_lessons_args['orderby'];
						}
						if ( isset( $course_lessons_args['order'] ) ) {
							$query_args['order'] = $course_lessons_args['order'];
						}

						/**
						 * Set the 'posts_per_page' to the course values only if
						 * not passed as a request parameter.
						 */
						if ( is_null( $request['posts_per_page'] ) ) {
							$query_args['posts_per_page'] = absint( learndash_get_course_lessons_per_page( $this->course_post->ID ) ); // phpcs:ignore WordPress.WP.PostsPerPage.posts_per_page_posts_per_page
						}
					} else {
						$query_args['post__in'] = array( 0 );
					}
				} else {
					$query_args['post__in'] = array( 0 );
				}
			}

			return $query_args;
		}

		// End of functions.
	}
}
