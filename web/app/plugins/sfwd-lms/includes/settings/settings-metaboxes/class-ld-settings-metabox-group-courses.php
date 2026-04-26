<?php
/**
 * LearnDash Settings Metabox for Group Courses Settings.
 *
 * @since 3.2.0
 *
 * @package LearnDash\Settings\Metaboxes
 */

use LearnDash\Core\Validations\Validators\Metaboxes\Group_Courses;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Metabox' ) ) && ( ! class_exists( 'LearnDash_Settings_Metabox_Group_Courses_Settings' ) ) ) {
	/**
	 * Class LearnDash Settings Metabox for Group Courses Settings.
	 *
	 * @since 3.2.0
	 */
	class LearnDash_Settings_Metabox_Group_Courses_Settings extends LearnDash_Settings_Metabox {

		/**
		 * Public constructor for class
		 *
		 * @since 3.2.0
		 */
		public function __construct() {
			// What screen ID are we showing on.
			$this->settings_screen_id = 'groups';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_metabox_key = 'learndash_group_courses';

			// Section label/header.
			$this->settings_section_label = sprintf(
				// translators: placeholder: Group, Courses.
				esc_html_x( '%1$s %2$s', 'placeholder: Group, Courses', 'learndash' ),
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
					?>
					<div id="learndash_course_users_page_box" class="learndash_course_users_page_box">
						<?php

						$ld_binary_selector_group_courses = new Learndash_Binary_Selector_Group_Courses(
							array(
								'html_title'   => '',
								'group_id'     => $group_id,
								'selected_ids' => learndash_group_enrolled_courses( $group_id ),
							)
						);
						$ld_binary_selector_group_courses->show();
						?>
					</div>
					<?php
				}
			}
		}

		/**
		 * Save Settings Metabox
		 *
		 * @since 3.2.0
		 *
		 * @param integer      $post_id                ID of the post being saved.
		 * @param object       $saved_post             WP_Post object being saved.
		 * @param boolean      $update                 True if this is an existing post being updated, false if it's a new one.
		 * @param array<mixed> $settings_field_updates Array of settings fields to update.
		 *
		 * @return void
		 */
		public function save_post_meta_box( $post_id = 0, $saved_post = null, $update = null, $settings_field_updates = null ) {
			if ( ! $this->verify_metabox_nonce_field() ) {
				return;
			}

			if (
				! isset( $_POST[ $this->settings_metabox_key . '-' . $post_id . '-changed' ] ) // phpcs:ignore WordPress.Security.NonceVerification -- Nonce is checked above.
				|| ! isset( $_POST[ $this->settings_metabox_key ][ $post_id ] ) // phpcs:ignore WordPress.Security.NonceVerification -- Nonce is checked above.
			) {
				return;
			}

			$group_courses = (array) json_decode(
				sanitize_text_field( wp_unslash( $_POST[ $this->settings_metabox_key ][ $post_id ] ) ) // phpcs:ignore WordPress.Security.NonceVerification -- Nonce is checked above.
			);

			// Validate the group courses.

			$validator = ( new Group_Courses( $post_id ) )->validate(
				[ Group_Courses::$field_courses => $group_courses ]
			);

			if ( $validator->fails() ) {
				return;
			}

			// Update the group courses.

			learndash_set_group_enrolled_courses(
				$post_id,
				$validator->validated()[ Group_Courses::$field_courses ]
			);
		}

		// End of functions.
	}

	add_filter(
		'learndash_post_settings_metaboxes_init_' . learndash_get_post_type_slug( 'group' ),
		function( $metaboxes = array() ) {
			if ( ( ! isset( $metaboxes['LearnDash_Settings_Metabox_Group_Courses_Settings'] ) ) && ( class_exists( 'LearnDash_Settings_Metabox_Group_Courses_Settings' ) ) ) {
				$metaboxes['LearnDash_Settings_Metabox_Group_Courses_Settings'] = LearnDash_Settings_Metabox_Group_Courses_Settings::add_metabox_instance();
			}

			return $metaboxes;
		},
		50,
		1
	);
}
