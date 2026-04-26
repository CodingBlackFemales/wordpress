<?php
/**
 * LearnDash Settings Metabox for Course Access Extending.
 *
 * @since 4.8.0
 *
 * @package LearnDash\Settings\Metaboxes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (
	class_exists( 'LearnDash_Settings_Metabox' )
	&& ! class_exists( 'LearnDash_Settings_Metabox_Course_Access_Extending' )
) {
	/**
	 * Class LearnDash Settings Metabox for Course Access Extending.
	 *
	 * @since 4.8.0
	 */
	class LearnDash_Settings_Metabox_Course_Access_Extending extends LearnDash_Settings_Metabox {
		/**
		 * Constructor.
		 *
		 * @since 4.8.0
		 */
		public function __construct() {
			// What screen ID are we showing on.
			$this->settings_screen_id = LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::COURSE );

			// Used within the Settings API to uniquely identify this section.
			$this->settings_metabox_key = 'learndash-course-access-extending';

			// Section label/header.
			$this->settings_section_label = sprintf(
				// translators: placeholder: Course.
				esc_html_x( 'Extend %1$s Access', 'placeholder: Course', 'learndash' ),
				learndash_get_custom_label( 'course' )
			);

			$this->settings_fields_map = [
				'new_expiration_date' => 'new_expiration_date',
			];

			parent::__construct();
		}

		/**
		 * Initializes the metabox settings fields.
		 *
		 * @since 4.8.0
		 *
		 * @return void
		 */
		public function load_settings_fields() {
			$this->setting_option_fields = [
				'new_expiration_date'           => [
					'name'  => 'new_expiration_date',
					'label' => esc_html__( 'New Expiration Date', 'learndash' ),
					'value' => '',
					'type'  => 'date-entry',
					'class' => 'learndash-datepicker-field',
				],
				'course_users_to_extend_access' => [
					'name'             => 'course_users_to_extend_access',
					'label'            => esc_html__( 'Extend Access For Users', 'learndash' ),
					'value'            => null,
					'type'             => 'custom',
					'display_callback' => function (): void {
						$course_user_ids = array_merge(
							learndash_get_course_users_access_from_meta( (int) get_the_ID() ),
							learndash_get_course_expired_access_from_meta( (int) get_the_ID() )
						);

						if ( empty( $course_user_ids ) ) {
							esc_html_e( 'No enrolled users found.', 'learndash' );
						} else {
							$selector = new Learndash_Binary_Selector_Course_Users_Access_Extending(
								[
									'course_id'    => get_the_ID(),
									'included_ids' => $course_user_ids,
								]
							);

							$selector->show();
						}
					},
				],
			];

			parent::load_settings_fields();
		}

		/**
		 * Save Settings Metabox
		 *
		 * @since 4.8.0
		 *
		 * @param int          $post_id                Post ID being saved.
		 * @param WP_Post|null $saved_post             WP_Post object being saved.
		 * @param bool|null    $update                 If update true, otherwise false.
		 * @param mixed[]|null $settings_field_updates Array of settings fields to update.
		 *
		 * @return void
		 */
		public function save_post_meta_box( $post_id = 0, $saved_post = null, $update = null, $settings_field_updates = null ) {
			if (
				! $post_id
				|| ! $saved_post
			) {
				return;
			}

			if (
				! isset( $_POST['learndash-course-access-extending']['nonce'] )
				|| ! wp_verify_nonce(
					sanitize_text_field( wp_unslash( $_POST['learndash-course-access-extending']['nonce'] ) ),
					'learndash-course-access-extending'
				)
				|| ! isset( $_POST['course_users_to_extend_access'] )
			) {
				return;
			}

			$user_ids            = wp_parse_id_list(
				(array) json_decode(
					sanitize_text_field( wp_unslash( $_POST['course_users_to_extend_access'] ) ),
					true
				)
			);
			$new_expiration_date = $this->get_post_settings_field_updates( $post_id, $saved_post, $update )['new_expiration_date'];

			if (
				empty( $user_ids )
				|| empty( $new_expiration_date )
			) {
				return;
			}

			learndash_course_extend_user_access( $post_id, $user_ids, $new_expiration_date );
		}
	}

	add_filter(
		'learndash_post_settings_metaboxes_init_' . learndash_get_post_type_slug( LDLMS_Post_Types::COURSE ),
		function( $metaboxes = [] ) {
			if (
				! isset( $metaboxes['LearnDash_Settings_Metabox_Course_Access_Extending'] )
				&& class_exists( 'LearnDash_Settings_Metabox_Course_Access_Extending' )
			) {
				$metaboxes['LearnDash_Settings_Metabox_Course_Access_Extending'] = LearnDash_Settings_Metabox_Course_Access_Extending::add_metabox_instance();
			}

			return $metaboxes;
		},
		50
	);
}
