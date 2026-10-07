<?php
/**
 * LearnDash Settings Section for Groups Taxonomies Metabox.
 *
 * @since 3.2.0
 * @package LearnDash\Settings\Sections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Groups_Membership' ) ) ) {
	/**
	 * Class LearnDash Settings Section for Groups Taxonomies Metabox.
	 *
	 * @since 3.2.0
	 */
	class LearnDash_Settings_Groups_Membership extends LearnDash_Settings_Section {

		/**
		 * Default Access Message.
		 *
		 * @var string $groups_membership_message_default.
		 */
		private $groups_membership_message_default = '';

		/**
		 * Protected constructor for class
		 *
		 * @since 3.2.0
		 */
		protected function __construct() {

			// What screen ID are we showing on.
			$this->settings_screen_id = 'groups_page_groups-options';

			// The page ID (different than the screen ID).
			$this->settings_page_id = 'groups-options';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_settings_groups_membership';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_settings_groups_membership';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'groups_membership';

			// Section label/header.
			$this->settings_section_label = sprintf(
				// translators: placeholder: Group.
				esc_html_x( '%s Content Protection', 'placeholder: Group', 'learndash' ),
				learndash_get_custom_label( 'group' )
			);

			// Used to show the section description above the fields. Can be empty.
			$this->settings_section_description = sprintf(
				// translators: placeholder: groups.
				esc_html_x( 'Access to certain post types can be controlled via the associated %s enrollment.', 'placeholder: group', 'learndash' ),
				learndash_get_custom_label_lower( 'group' )
			);

			$this->groups_membership_message_default = sprintf(
				// translators: placeholder: group.
				esc_html_x( 'This page is protected and requires %s access', 'placeholder: group', 'learndash' ),
				learndash_get_custom_label_lower( 'group' )
			);

			parent::__construct();
		}

		/**
		 * Initialize the metabox settings values.
		 *
		 * @since 3.2.0
		 */
		public function load_settings_values() {
			parent::load_settings_values();

			if ( true === $this->settings_values_loaded ) {

				if ( ! isset( $this->setting_option_values['groups_membership_enabled'] ) ) {
					$this->setting_option_values['groups_membership_enabled'] = '';
				}

				if ( ( ! isset( $this->setting_option_values['groups_membership_message'] ) ) || ( empty( $this->setting_option_values['groups_membership_message'] ) ) ) {
					$this->setting_option_values['groups_membership_message'] = wp_kses_post( $this->groups_membership_message_default );
				}

				if ( ! isset( $this->setting_option_values['groups_membership_post_types'] ) ) {
					$this->setting_option_values['groups_membership_post_types'] = array();
				}

				if ( ! isset( $this->setting_option_values['groups_membership_user_roles'] ) ) {
					$this->setting_option_values['groups_membership_user_roles'] = array();
				}

				if ( ( empty( $this->setting_option_values['groups_membership_post_types'] ) ) || ( empty( $this->setting_option_values['groups_membership_message'] ) ) ) {
					$this->setting_option_values['groups_membership_enabled'] = '';
				}
			}
		}

		/**
		 * Initialize the metabox settings fields.
		 *
		 * @since 3.2.0
		 */
		public function load_settings_fields() {
			$this->setting_option_fields['groups_membership_enabled'] = array(
				'name'    => 'groups_membership_enabled',
				'label'   => '',
				'type'    => 'hidden',
				'value'   => $this->setting_option_values['groups_membership_enabled'],
				'default' => '',
			);

			$post_types['builtin_true']  = get_post_types(
				array(
					'public'   => true,
					'_builtin' => true,
				),
				'object'
			);
			$post_types['builtin_false'] = get_post_types(
				array(
					'public'   => true,
					'_builtin' => false,
				),
				'object'
			);
			$post_types['all']           = array_merge( $post_types['builtin_true'], $post_types['builtin_false'] );

			if ( isset( $post_types['all']['attachment'] ) ) {
				unset( $post_types['all']['attachment'] );
			}

			$post_types_labels = array();
			if ( ! empty( $post_types['all'] ) ) {
				foreach ( $post_types['all'] as $post_type_slug => $post_type_object ) {
					if ( ! in_array( $post_type_slug, learndash_get_post_types(), true ) ) {
						$post_types_labels[ $post_type_slug ] = $post_type_object->labels->singular_name;
					}
				}
			}

			if ( ! empty( $post_types_labels ) ) {
				$this->setting_option_fields['groups_membership_post_types'] = array(
					'name'      => 'groups_membership_post_types',
					'label'     => esc_html__( 'Supported Post Types', 'learndash' ),
					'help_text' => sprintf(
						// translators: placeholder: group.
						esc_html_x( 'Specify which post types can allow for %s membership protection.', 'placeholder: group', 'learndash' ),
						learndash_get_custom_label_lower( 'group' )
					),
					'type'      => 'multiselect',
					'value'     => $this->setting_option_values['groups_membership_post_types'],
					'options'   => $post_types_labels,
				);
			}

			$this->setting_option_fields['groups_membership_message'] = array(
				'name'        => 'groups_membership_message',
				'label'       => esc_html__( 'Access Denied Message', 'learndash' ),
				'help_text'   => esc_html__( 'Set a global message for any user trying to access the protected post / page without permission.', 'learndash' ),
				'type'        => 'wpeditor',
				'value'       => $this->setting_option_values['groups_membership_message'],
				'default'     => '',
				'editor_args' => array(
					'textarea_name' => $this->setting_option_key . '[groups_membership_message]',
					'textarea_rows' => 5,
				),
			);

			$wp_user_roles = wp_roles();
			if ( ( isset( $wp_user_roles->role_names ) ) && ( ! empty( $wp_user_roles->role_names ) ) ) {

				$this->setting_option_fields['groups_membership_user_roles'] = array(
					'name'      => 'groups_membership_user_roles',
					'label'     => esc_html__( 'Bypass User Roles', 'learndash' ),
					'help_text' => sprintf(
						// translators: placeholder: group.
						esc_html_x( 'Allow specific user roles to bypass the %s membership protection.', 'placeholder: group', 'learndash' ),
						learndash_get_custom_label_lower( 'group' )
					),
					'type'      => 'multiselect',
					'value'     => $this->setting_option_values['groups_membership_user_roles'],
					'options'   => $wp_user_roles->role_names,
				);
			}

			/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_section_key );

			parent::load_settings_fields();
		}

		/**
		 * Intercept the WP options save logic and check that we have a valid nonce.
		 *
		 * @since 3.2.0
		 *
		 * @param array  $new_values         Array of section fields values.
		 * @param array  $old_values         Array of old values.
		 * @param string $setting_option_key Section option key should match $this->setting_option_key.
		 */
		public function section_pre_update_option( $new_values = '', $old_values = '', $setting_option_key = '' ) {
			if ( $setting_option_key === $this->setting_option_key ) {
				if ( ! isset( $new_values['groups_membership_enabled'] ) ) {
					$new_values['groups_membership_enabled'] = '';
				}

				if ( ( ! isset( $new_values['groups_membership_message'] ) ) || ( empty( $new_values['groups_membership_message'] ) ) ) {
					$new_values['groups_membership_message'] = wp_kses_post( $this->groups_membership_message_default );
				}

				if ( ! isset( $new_values['groups_membership_post_types'] ) ) {
					$new_values['groups_membership_post_types'] = array();
				}

				if ( ! isset( $new_values['groups_membership_user_roles'] ) ) {
					$new_values['groups_membership_user_roles'] = array();
				}

				if ( ( ! empty( $new_values['groups_membership_post_types'] ) ) && ( ! empty( $new_values['groups_membership_message'] ) ) ) {
					$new_values['groups_membership_enabled'] = 'on';
				} else {
					$new_values['groups_membership_enabled'] = '';
				}
			}

			return $new_values;
		}

		// End of functions.
	}
}
add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Groups_Membership::add_section_instance();
	}
);
