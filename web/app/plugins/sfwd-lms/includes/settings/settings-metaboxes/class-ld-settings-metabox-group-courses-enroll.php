<?php
/**
 * LearnDash Settings Metabox for Group Courses Settings.
 *
 * @since 3.2.0
 * @package LearnDash\Settings\Metaboxes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Metabox' ) ) && ( ! class_exists( 'LearnDash_Settings_Metabox_Group_Courses_Enroll_Settings' ) ) ) {
	/**
	 * Class LearnDash Settings Metabox for Group Courses Settings.
	 *
	 * @since 3.2.0
	 */
	class LearnDash_Settings_Metabox_Group_Courses_Enroll_Settings extends LearnDash_Settings_Metabox {

		/**
		 * Public constructor for class
		 *
		 * @since 3.2.0
		 */
		public function __construct() {
			// What screen ID are we showing on.
			$this->settings_screen_id = 'groups';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_metabox_key = 'learndash_group_courses_enroll';

			// Section label/header.
			$this->settings_section_label = sprintf(
				// translators: placeholder: Group, Courses.
				esc_html_x( '%1$s %2$s Auto-enroll', 'placeholder: Group, Courses', 'learndash' ),
				learndash_get_custom_label( 'group' ),
				learndash_get_custom_label( 'courses' )
			);

			parent::__construct();
		}

		/**
		 * Show Settings Section Fields.
		 *
		 * @since 3.2.0
		 *
		 * @param object $metabox Metabox object.
		 */
		protected function show_settings_metabox_fields( $metabox = null ) {
			if ( ( is_object( $metabox ) ) && ( is_a( $metabox, 'LearnDash_Settings_Metabox' ) ) && ( $metabox->settings_metabox_key === $this->settings_metabox_key ) ) {
				if ( ( isset( $metabox->post ) ) && ( is_a( $metabox->post, 'WP_Post ' ) ) ) {
					$group_id = $metabox->post->ID;
				} else {
					$group_id = get_the_ID();
				}

				if ( ( ! empty( $group_id ) ) && ( get_post_type( $group_id ) === learndash_get_post_type_slug( 'group' ) ) ) {

					$ld_auto_enroll_group_courses = get_post_meta( $group_id, 'ld_auto_enroll_group_courses', true );
					?>
					<div id="learndash_course_users_page_box" class="learndash_course_users_page_box">
						<p><input type="checkbox" id="learndash_auto_enroll_group_courses" name="learndash_auto_enroll_group_courses" value="yes"
						<?php checked( $ld_auto_enroll_group_courses, 'yes' ); ?> />
							<?php
							printf(
								// translators: placeholder: group, group, course.
								esc_html_x( 'Enable automatic %1$s enrollment when a user enrolls into any associated %2$s %3$s', 'placeholder: group, group, course', 'learndash' ),
								esc_html( learndash_get_custom_label_lower( 'group' ) ),
								esc_html( learndash_get_custom_label_lower( 'group' ) ),
								esc_html( learndash_get_custom_label_lower( 'course' ) )
							);
							?>
						</p>
						<?php

						$ld_auto_enroll_group_course_ids = get_post_meta( $group_id, 'ld_auto_enroll_group_course_ids', true );
						if ( ! is_array( $ld_auto_enroll_group_course_ids ) ) {
							$ld_auto_enroll_group_course_ids = array();
						}
						$ld_auto_enroll_group_course_ids = array_map( 'absint', $ld_auto_enroll_group_course_ids );

						$group_selected_ids = learndash_group_enrolled_courses( $group_id, true );
						if ( ! empty( $group_selected_ids ) ) {
							$group_selected_ids              = array_map( 'absint', $group_selected_ids );
							$ld_auto_enroll_group_course_ids = array_intersect( $ld_auto_enroll_group_course_ids, $group_selected_ids );
						}

						$ld_binary_selector_group_courses_enroll = new Learndash_Binary_Selector_Group_Courses_Enroll(
							array(
								'html_title'   => '',
								'group_id'     => $group_id,
								'included_ids' => learndash_group_enrolled_courses( $group_id, true ),
								'selected_ids' => $ld_auto_enroll_group_course_ids,
							)
						);
						$ld_binary_selector_group_courses_enroll->show();
						?>
					</div>
					<script>
						// Coordinate change between the checkbox and binary selector.
						var learndash_auto_enroll_group_courses_checkbox = document.getElementById('learndash_auto_enroll_group_courses');
						learndash_auto_enroll_group_courses_checkbox.addEventListener('change', e => {
							learndash_auto_enroll_group_courses_checkbox_handle_change( e.target );
						});
						learndash_auto_enroll_group_courses_checkbox_handle_change( learndash_auto_enroll_group_courses_checkbox );
						function learndash_auto_enroll_group_courses_checkbox_handle_change( checkbox ) {
							if ( checkbox.checked ) {
								document.getElementById('learndash_group_courses_enroll-<?php echo esc_attr( $group_id ); ?>').style.visibility = 'hidden';
							} else {
								document.getElementById('learndash_group_courses_enroll-<?php echo esc_attr( $group_id ); ?>').style.visibility = 'visible';
							}
						}
					</script>
					<?php
				}
			}
		}

		/**
		 * Save Settings Metabox
		 *
		 * @since 3.2.0
		 *
		 * @param integer $post_id $Post ID is post being saved.
		 * @param object  $saved_post WP_Post object being saved.
		 * @param boolean $update If update true, otherwise false.
		 * @param array   $settings_field_updates array of settings fields to update.
		 */
		public function save_post_meta_box( $post_id = 0, $saved_post = null, $update = null, $settings_field_updates = null ) {
			if ( true === $this->verify_metabox_nonce_field() ) {

				if ( ( isset( $_POST[ $this->settings_metabox_key . '-' . $post_id . '-changed' ] ) ) && ( ! empty( $_POST[ $this->settings_metabox_key . '-' . $post_id . '-changed' ] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					if ( ( isset( $_POST[ $this->settings_metabox_key ][ $post_id ] ) ) && ( ! empty( $_POST[ $this->settings_metabox_key ][ $post_id ] ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
						$group_enroll_courses = (array) json_decode( stripslashes( $_POST[ $this->settings_metabox_key ][ $post_id ] ) ); // phpcs:ignore WordPress.Security.NonceVerification, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
						$group_enroll_courses = array_map( 'absint', $group_enroll_courses );
						if ( ! empty( $group_enroll_courses ) ) {
							update_post_meta( $post_id, 'ld_auto_enroll_group_course_ids', $group_enroll_courses );
						} else {
							delete_post_meta( $post_id, 'ld_auto_enroll_group_course_ids' );
						}
					}
				}

				if ( ( isset( $_POST['learndash_auto_enroll_group_courses'] ) ) && ( 'yes' == $_POST['learndash_auto_enroll_group_courses'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification
					update_post_meta( $post_id, 'ld_auto_enroll_group_courses', 'yes' );
				} else {
					delete_post_meta( $post_id, 'ld_auto_enroll_group_courses' );
				}
			}
		}

		// End of functions.
	}

	add_filter(
		'learndash_post_settings_metaboxes_init_' . learndash_get_post_type_slug( 'group' ),
		function( $metaboxes = array() ) {
			if ( ( ! isset( $metaboxes['LearnDash_Settings_Metabox_Group_Courses_Enroll_Settings'] ) ) && ( class_exists( 'LearnDash_Settings_Metabox_Group_Courses_Enroll_Settings' ) ) ) {
				$metaboxes['LearnDash_Settings_Metabox_Group_Courses_Enroll_Settings'] = LearnDash_Settings_Metabox_Group_Courses_Enroll_Settings::add_metabox_instance();
			}

			return $metaboxes;
		},
		50,
		1
	);
}
