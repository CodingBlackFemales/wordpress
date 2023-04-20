<?php
/**
 * LearnDash Course Bulk Edit.
 *
 * @since 4.2.0
 *
 * @package LearnDash\Bulk_Edit
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (
	class_exists( 'Learndash_Admin_Bulk_Edit_Action' ) &&
	! class_exists( 'Learndash_Admin_Bulk_Edit_Action_Courses' )
) {
	/**
	 * Courses Bulk Edit Class.
	 *
	 * @since 4.2.0
	 */
	class Learndash_Admin_Bulk_Edit_Action_Courses extends Learndash_Admin_Bulk_Edit_Action {
		/**
		 * Course access metabox.
		 *
		 * @since 4.2.0
		 *
		 * @var LearnDash_Settings_Metabox_Course_Access_Settings
		 */
		private $metabox_access_settings;

		/**
		 * Constructor.
		 *
		 * @since 4.2.0
		 *
		 * @param LearnDash_Settings_Metabox_Course_Access_Settings $metabox_access_settings Course access metabox.
		 */
		public function __construct( LearnDash_Settings_Metabox_Course_Access_Settings $metabox_access_settings ) {
			$this->metabox_access_settings = $metabox_access_settings;
			$this->metabox_access_settings->load_settings_values();
			$this->metabox_access_settings->load_settings_fields();
		}

		/**
		 * Returns a tab name.
		 *
		 * @since 4.2.0
		 *
		 * @return string
		 */
		public function get_tab_name(): string {
			return LearnDash_Custom_Label::get_label( 'courses' );
		}

		/**
		 * Returns a post type.
		 *
		 * @since 4.2.0
		 *
		 * @return string
		 */
		public function get_post_type(): string {
			return LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::COURSE );
		}

		/**
		 * Returns supported field names.
		 *
		 * @since 4.2.0
		 *
		 * @return array
		 */
		protected function get_supported_field_names(): array {
			return array(
				'course_price_type',
			);
		}

		/**
		 * Updates the post field.
		 *
		 * @since 4.2.0
		 *
		 * @param int    $post_id     Post ID.
		 * @param string $field_name  Field name.
		 * @param string $field_value Field value.
		 *
		 * @return void
		 */
		protected function update_post_field( int $post_id, string $field_name, string $field_value ): void {
			$fields_mapping_hash = $this->metabox_access_settings->get_save_settings_fields_map_form_post_values();

			learndash_update_setting(
				$post_id,
				$fields_mapping_hash[ $field_name ] ?? $field_name,
				$field_value
			);
		}

		/**
		 * Inits filters.
		 *
		 * @since 4.2.0
		 *
		 * @return void
		 */
		protected function init_filters(): void {
			$course_label = LearnDash_Custom_Label::get_label( LDLMS_Post_Types::COURSE );

			$this->filters = array(
				Learndash_Admin_Filter_Factory::create_filter(
					Learndash_Admin_Filters::TYPE_POST_ID,
					$course_label,
					$this->get_select_ajax_query_data_for_post_type( $this->get_post_type() )
				),
				Learndash_Admin_Filter_Factory::create_filter(
					Learndash_Admin_Filters::TYPE_POST_TITLE,
					$course_label
				),
				Learndash_Admin_Filter_Factory::create_filter(
					Learndash_Admin_Filters::TYPE_POST_STATUS,
					$course_label
				),
			);

			// Add the access mode filter.

			$access_settings_metabox_fields = $this->metabox_access_settings->get_settings_metabox_fields();

			$access_modes = array();
			foreach ( $access_settings_metabox_fields['course_price_type']['args']['options'] as $option_name => $option ) {
				$access_modes[ $option_name ] = $option['label'];
			}

			$this->filters[] = Learndash_Admin_Filter_Factory::create_filter(
				Learndash_Admin_Filters::TYPE_META_SELECT,
				'_ld_price_type',
				esc_html__( 'Access Mode', 'learndash' ),
				$access_modes
			);
		}

		/**
		 * Inits fields.
		 *
		 * @since 4.2.0
		 *
		 * @return void
		 */
		protected function init_fields(): void {
			$metabox_fields = array_intersect_key(
				$this->metabox_access_settings->get_settings_metabox_fields(),
				array_flip( $this->get_supported_field_names() )
			);

			$this->fields = array();

			foreach ( $metabox_fields as $field ) {
				$this->fields[] = new Learndash_Admin_Bulk_Edit_Field( $field['args'] );
			}
		}
	}
}
