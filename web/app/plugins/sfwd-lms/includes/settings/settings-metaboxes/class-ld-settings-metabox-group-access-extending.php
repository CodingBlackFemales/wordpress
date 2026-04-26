<?php
/**
 * LearnDash Settings Metabox for Group Access Extending.
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
	&& ! class_exists( 'LearnDash_Settings_Metabox_Group_Access_Extending' )
) {
	/**
	 * Class LearnDash Settings Metabox for Group Access Extending.
	 *
	 * @since 4.8.0
	 */
	class LearnDash_Settings_Metabox_Group_Access_Extending extends LearnDash_Settings_Metabox {
		/**
		 * Constructor.
		 *
		 * @since 4.8.0
		 */
		public function __construct() {
			// What screen ID are we showing on.
			$this->settings_screen_id = LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::GROUP );

			// Used within the Settings API to uniquely identify this section.
			$this->settings_metabox_key = 'learndash-group-access-extending';

			// Section label/header.
			$this->settings_section_label = sprintf(
				// translators: placeholder: Group, Courses.
				esc_html_x( 'Extend %1$s %2$s Access', 'placeholder: Group, Courses', 'learndash' ),
				LearnDash_Custom_Label::get_label( 'group' ),
				LearnDash_Custom_Label::get_label( 'courses' )
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
				'new_expiration_date'            => [
					'name'  => 'new_expiration_date',
					'label' => esc_html__( 'New Expiration Date', 'learndash' ),
					'value' => '',
					'type'  => 'date-entry',
					'class' => 'learndash-datepicker-field',
				],
				'group_courses_to_extend_access' => [
					'name'             => 'group_courses_to_extend_access',
					'label'            => sprintf(
						// translators: placeholder: Course.
						esc_html_x( 'Extend Access To %s', 'Extend Access To Courses', 'learndash' ),
						esc_html( LearnDash_Custom_Label::get_label( 'courses' ) )
					),
					'value'            => null,
					'type'             => 'custom',
					'display_callback' => function (): void {
						$group_course_ids = learndash_group_enrolled_courses( (int) get_the_ID() );

						if ( empty( $group_course_ids ) ) {
							echo sprintf(
								// translators: placeholder: courses.
								esc_html_x( 'No enrolled %s found.', 'No enrolled courses found', 'learndash' ),
								esc_html( LearnDash_Custom_Label::label_to_lower( 'courses' ) )
							);
						} else {

							$selector = new Learndash_Binary_Selector_Group_Courses_Access_Extending(
								[
									'group_id'     => get_the_ID(),
									'included_ids' => $group_course_ids,
								]
							);

							$selector->show();
						}
					},
				],
				'group_users_to_extend_access'   => [
					'name'             => 'group_users_to_extend_access',
					'label'            => esc_html__( 'Extend Access For Users', 'learndash' ),
					'value'            => null,
					'type'             => 'custom',
					'display_callback' => function (): void {
						$group_user_ids = learndash_get_groups_user_ids( (int) get_the_ID() );

						if ( empty( $group_user_ids ) ) {
							esc_html_e( 'No enrolled users found.', 'learndash' );
						} else {
							$selector = new Learndash_Binary_Selector_Group_Users_Access_Extending(
								[
									'group_id'     => get_the_ID(),
									'included_ids' => $group_user_ids,
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
		 * @param mixed[]|null $settings_field_updates array of settings fields to update.
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
				! isset( $_POST['learndash-group-access-extending']['nonce'] )
				|| ! wp_verify_nonce(
					sanitize_text_field( wp_unslash( $_POST['learndash-group-access-extending']['nonce'] ) ),
					'learndash-group-access-extending'
				)
				|| ! isset( $_POST['group_users_to_extend_access'] )
				|| ! isset( $_POST['group_courses_to_extend_access'] )
			) {
				return;
			}

			$user_ids            = wp_parse_id_list(
				(array) json_decode(
					sanitize_text_field( wp_unslash( $_POST['group_users_to_extend_access'] ) ),
					true
				)
			);
			$course_ids          = wp_parse_id_list(
				(array) json_decode(
					sanitize_text_field( wp_unslash( $_POST['group_courses_to_extend_access'] ) ),
					true
				)
			);
			$new_expiration_date = $this->get_post_settings_field_updates( $post_id, $saved_post, $update )['new_expiration_date'];

			if (
				empty( $user_ids )
				|| empty( $course_ids )
				|| empty( $new_expiration_date )
			) {
				return;
			}

			foreach ( $course_ids as $course_id ) {
				learndash_course_extend_user_access( $course_id, $user_ids, $new_expiration_date, $post_id );
			}
		}
	}

	add_filter(
		'learndash_post_settings_metaboxes_init_' . learndash_get_post_type_slug( LDLMS_Post_Types::GROUP ),
		function ( $metaboxes = [] ) {
			if (
				! isset( $metaboxes['LearnDash_Settings_Metabox_Group_Access_Extending'] )
				&& class_exists( 'LearnDash_Settings_Metabox_Group_Access_Extending' )
			) {
				$metaboxes['LearnDash_Settings_Metabox_Group_Access_Extending'] = LearnDash_Settings_Metabox_Group_Access_Extending::add_metabox_instance();
			}

			return $metaboxes;
		},
		50
	);
}
