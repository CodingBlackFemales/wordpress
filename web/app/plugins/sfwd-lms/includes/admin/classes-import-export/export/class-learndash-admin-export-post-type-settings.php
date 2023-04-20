<?php
/**
 * LearnDash Admin Export Post Type Settings.
 *
 * @since   4.3.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (
	class_exists( 'Learndash_Admin_Export' ) &&
	trait_exists( 'Learndash_Admin_Import_Export_Post_Type_Settings' ) &&
	! class_exists( 'Learndash_Admin_Export_Post_Type_Settings' )
) {
	/**
	 * Class LearnDash Admin Export Post Type Settings.
	 *
	 * @since 4.3.0
	 */
	class Learndash_Admin_Export_Post_Type_Settings extends Learndash_Admin_Export {
		use Learndash_Admin_Import_Export_Post_Type_Settings;

		const POST_TYPE_SETTING_SECTIONS = array(
			LDLMS_Post_Types::COURSE      => array(
				'LearnDash_Settings_Courses_Management_Display',
				'LearnDash_Settings_Courses_Taxonomies',
				'LearnDash_Settings_Courses_CPT',
			),
			LDLMS_Post_Types::LESSON      => array(
				'LearnDash_Settings_Lessons_Taxonomies',
				'LearnDash_Settings_Lessons_CPT',
			),
			LDLMS_Post_Types::TOPIC       => array(
				'LearnDash_Settings_Topics_Taxonomies',
				'LearnDash_Settings_Topics_CPT',
			),
			LDLMS_Post_Types::QUIZ        => array(
				'LearnDash_Settings_Quizzes_Email',
				'LearnDash_Settings_Quizzes_Management_Display',
				'LearnDash_Settings_Quizzes_Taxonomies',
				'LearnDash_Settings_Quizzes_CPT',
			),
			LDLMS_Post_Types::QUESTION    => array(
				'LearnDash_Settings_Questions_Management_Display',
				'LearnDash_Settings_Questions_Taxonomies',
			),
			LDLMS_Post_Types::CERTIFICATE => array(
				'LearnDash_Settings_Certificates_Styles',
				'LearnDash_Settings_Certificates_CPT',
			),
			LDLMS_Post_Types::GROUP       => array(
				'LearnDash_Settings_Section_Groups_Group_Leader_User',
				'LearnDash_Settings_Groups_Membership',
				'LearnDash_Settings_Groups_Management_Display',
				'LearnDash_Settings_Groups_Taxonomies',
				'LearnDash_Settings_Groups_CPT',
			),
			LDLMS_Post_Types::ASSIGNMENT  => array(
				'LearnDash_Settings_Assignments_CPT',
			),
		);

		/**
		 * Constructor.
		 *
		 * @since 4.3.0
		 * @since 4.5.0   Changed the $logger param to the `Learndash_Import_Export_Logger` class.
		 *
		 * @param string                              $post_type    Post type.
		 * @param Learndash_Admin_Export_File_Handler $file_handler File Handler class instance.
		 * @param Learndash_Import_Export_Logger      $logger       Logger class instance.
		 *
		 * @return void
		 */
		public function __construct(
			string $post_type,
			Learndash_Admin_Export_File_Handler $file_handler,
			Learndash_Import_Export_Logger $logger
		) {
			$this->post_type = $post_type;

			parent::__construct( $file_handler, $logger );
		}

		/**
		 * Returns the list of settings associated with the post type.
		 *
		 * @since 4.3.0
		 *
		 * @return string
		 */
		public function get_data(): string {
			$section_key = LDLMS_Post_Types::get_post_type_key( $this->post_type );

			if ( empty( $section_key ) ) {
				$section_key = $this->post_type;
			}

			$sections = $this->get_sections( $section_key );

			if ( empty( $sections ) ) {
				return wp_json_encode( array() );
			}

			$result = array();

			foreach ( $sections as $section ) {
				$data = array(
					'name'   => $section,
					'fields' => $section::get_settings_all(),
				);

				/**
				 * Filters the post type settings object to export.
				 *
				 * @since 4.3.0
				 *
				 * @param array $data Settings object.
				 *
				 * @return array Settings object.
				 */
				$data = apply_filters( 'learndash_export_post_type_settings_object', $data );

				$result[] = $data;
			}

			return wp_json_encode( $result );
		}

		/**
		 * Returns post type settings sections.
		 *
		 * @since 4.3.0
		 *
		 * @param string $section_key Section Key.
		 *
		 * @return array
		 */
		protected function get_sections( string $section_key ): array {
			if ( ! array_key_exists( $section_key, self::POST_TYPE_SETTING_SECTIONS ) ) {
				return array();
			}

			/**
			 * Filters the list of post type settings sections to export.
			 *
			 * @since 4.3.0
			 *
			 * @param array $sections Post type settings sections.
			 *
			 * @return array Post type settings sections.
			 */
			$sections = apply_filters(
				'learndash_export_post_type_settings_sections',
				self::POST_TYPE_SETTING_SECTIONS[ $section_key ]
			);

			return array_filter(
				$sections,
				function ( $section ) {
					return is_subclass_of( $section, 'LearnDash_Settings_Section' );
				}
			);
		}
	}
}
