<?php
/**
 * LearnDash Admin Course Edit.
 *
 * @since 2.2.1
 * @package LearnDash\Course\Edit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'Learndash_Admin_Post_Edit' ) ) && ( ! class_exists( 'Learndash_Admin_Course_Edit' ) ) ) {

	/**
	 * Class LearnDash Admin Course Edit.
	 *
	 * @since 2.2.1
	 * @uses Learndash_Admin_Post_Edit
	 */
	class Learndash_Admin_Course_Edit extends Learndash_Admin_Post_Edit {

		/**
		 * Object level flag to contain setting is Course Builder
		 * is to be used.
		 *
		 * @var boolean $use_course_builder
		 */
		private $use_course_builder = false;

		/**
		 * Instance of Course Builder Metabox object used
		 * throughout this class.
		 *
		 * @var object $course_builder Instance of Learndash_Admin_Metabox_Course_Builder
		 */
		private $course_builder = null;

		/**
		 * Public constructor for class.
		 *
		 * @since 2.2.1
		 */
		public function __construct() {
			$this->post_type = learndash_get_post_type_slug( 'course' );

			parent::__construct();
		}

		/**
		 * On Load handler function for this post type edit.
		 * This function is called by a WP action when the admin
		 * page 'post.php' or 'post-new.php' are loaded.
		 *
		 * @since 2.2.1
		 */
		public function on_load() {
			if ( $this->post_type_check() ) {

				require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/settings/settings-metaboxes/class-ld-settings-metabox-course-display-content.php';
				require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php';
				require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/settings/settings-metaboxes/class-ld-settings-metabox-course-navigation-settings.php';

				if ( learndash_get_total_post_count( learndash_get_post_type_slug( 'group' ) ) !== 0 ) {
					require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/settings/settings-metaboxes/class-ld-settings-metabox-course-groups.php';
				}

				if ( false === learndash_use_legacy_course_access_list() ) {
					/**
					 * Filters Whether to show course users metabox or not.
					 *
					 * @since 3.1.0
					 *
					 * @param boolean $show_metabox Whether to show metabox or not.
					 */
					if ( true === apply_filters( 'learndash_show_metabox_course_users', true ) ) {
						require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/settings/settings-metaboxes/class-ld-settings-metabox-course-users.php';
					}
				}

				parent::on_load();

				if ( LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'enabled' ) == 'yes' ) {
					$this->use_course_builder = true;
					/**
					 * Filters Whether to show course builder metabox or not.
					 *
					 * @since 2.5.0
					 *
					 * @param boolean $show_course_builder Whether to show course builder or not.
					 */
					if ( apply_filters( 'learndash_show_course_builder', $this->use_course_builder ) === true ) {
						$this->course_builder = Learndash_Admin_Metabox_Course_Builder::add_instance();
						$this->course_builder->builder_on_load();
					}
				}
				/** This filter is documented in includes/admin/class-learndash-admin-posts-edit.php */
				$this->_metaboxes = apply_filters( 'learndash_post_settings_metaboxes_init_' . $this->post_type, $this->_metaboxes );
				add_filter( 'learndash_header_data', 'LearnDash\Admin\CourseBuilderHelpers\learndash_get_course_data', 100 );
			}
		}

		/**
		 * Registers the course builder metabox for the admin
		 *
		 * @since 2.4.0
		 *
		 * @param string $post_type Post Type being edited.
		 * @param object $post WP_Post Post being edited.
		 */
		public function add_metaboxes( $post_type = '', $post = null ) {
			if ( $this->post_type_check( $post_type ) ) {

				/** This filter is documented in includes/admin/classes-posts-edits/class-learndash-admin-course-edit.php */
				if ( true === apply_filters( 'learndash_show_course_builder', $this->use_course_builder ) ) {
					add_meta_box(
						'learndash_course_builder',
						sprintf(
							// translators: placeholder: Course.
							esc_html_x( 'LearnDash %s Builder', 'placeholder: Course', 'learndash' ),
							LearnDash_Custom_Label::get_label( 'course' )
						),
						array( $this->course_builder, 'show_builder_box' ),
						$this->post_type,
						'normal',
						'high'
					);
				}

				parent::add_metaboxes( $post_type, $post );
			}
		}

		/**
		 * Save metabox handler function.
		 *
		 * @since 2.6.0
		 *
		 * @param integer $post_id Post ID Question being edited.
		 * @param object  $post WP_Post Question being edited.
		 * @param boolean $update If update true, else false.
		 */
		public function save_post( $post_id = 0, $post = null, $update = false ) {
			if ( ! $this->post_type_check( $post ) ) {
				return false;
			}

			if ( ! parent::save_post( $post_id, $post, $update ) ) {
				return false;
			}

			if ( ! empty( $this->_metaboxes ) ) {
				foreach ( $this->_metaboxes as $_metaboxes_instance ) {
					$settings_fields = array();
					$settings_fields = $_metaboxes_instance->get_post_settings_field_updates( $post_id, $post, $update );
					$_metaboxes_instance->save_post_meta_box( $post_id, $post, $update, $settings_fields );
				}
			}

			/** This filter is documented in includes/admin/classes-posts-edits/class-learndash-admin-course-edit.php */
			if ( apply_filters( 'learndash_show_course_builder', $this->use_course_builder ) === true ) {
				$this->course_builder->save_course_builder( $post_id, $post, $update );
			}
		}

		// End of functions.
	}
}
new Learndash_Admin_Course_Edit();
