<?php
/**
 * LearnDash Settings Metabox for Group Membership Settings for Post.
 *
 * @since 3.2.0
 * @package LearnDash\Settings\Metaboxes
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Metabox' ) ) && ( ! class_exists( 'LearnDash_Settings_Metabox_Group_Membership_Post_Settings' ) ) ) {
	/**
	 * Class LearnDash Settings Metabox for Group Membership Settings for Post.
	 *
	 * @since 3.2.0
	 */
	class LearnDash_Settings_Metabox_Group_Membership_Post_Settings extends LearnDash_Settings_Metabox {

		/**
		 * Array of post types
		 *
		 * @var array
		 */
		protected $post_types = array();

		/**
		 * Public constructor for class
		 *
		 * @since 3.2.0
		 */
		public function __construct() {
			global $typenow;

			$this->post_types = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Groups_Membership', 'groups_membership_post_types' );
			if ( ! is_array( $this->post_types ) ) {
				$this->post_types = array();
			}

			if ( ( ! empty( $typenow ) ) && ( in_array( $typenow, $this->post_types, true ) ) ) {
				// What screen ID are we showing on.
				$this->settings_screen_id = $typenow;
			} else {
				$this->settings_screen_id = '';
			}

			// Used within the Settings API to uniquely identify this section.
			$this->settings_metabox_key = 'learndash-group-membership-settings';

			// Section label/header.
			$this->settings_section_label = sprintf(
				// translators: placeholder: Group.
				esc_html_x( 'LearnDash %s Content Protection', 'placeholder: Group', 'learndash' ),
				learndash_get_custom_label( 'group' )
			);

			add_filter( 'learndash_metabox_save_fields_' . $this->settings_metabox_key, array( $this, 'filter_saved_fields' ), 30, 3 );

			// Map internal settings field ID to legacy field ID.
			$this->settings_fields_map = array(
				// New fields.
				'groups_membership_enabled'  => 'groups_membership_enabled',
				'groups_membership_groups'   => 'groups_membership_groups',
				'groups_membership_compare'  => 'groups_membership_compare',
				'groups_membership_children' => 'groups_membership_children',
			);

			parent::__construct();
		}

		/**
		 * Initialize the metabox settings values.
		 *
		 * @since 3.2.0
		 */
		public function load_settings_values() {
			$this->setting_option_values = learndash_get_post_group_membership_settings( $this->_post->ID );

			// Ensure all settings fields are present.
			foreach ( $this->settings_fields_map as $_internal => $_external ) {
				if ( ! isset( $this->setting_option_values[ $_internal ] ) ) {
					$this->setting_option_values[ $_internal ] = '';
				}
			}

		}

		/**
		 * Initialize the metabox settings fields.
		 *
		 * @since 3.2.0
		 */
		public function load_settings_fields() {
			global $sfwd_lms;

			$is_hierarchical = is_post_type_hierarchical( get_post_type( $this->_post->ID ) );

			$select_groups_options = $sfwd_lms->select_a_group();
			if ( empty( $select_groups_options ) ) {
				$this->setting_option_values['groups_membership_enabled'] = '';
			}

			if ( learndash_use_select2_lib() ) {
				$select_groups_options_default = sprintf(
					// translators: placeholder: Group.
					esc_html_x( 'Search or select a %s…', 'placeholder: Group', 'learndash' ),
					learndash_get_custom_label( 'group' )
				);
			} else {
				$select_groups_options_default = array(
					'' => sprintf(
						// translators: placeholder: Group.
						esc_html_x( 'Select %s', 'placeholder: Group', 'learndash' ),
						learndash_get_custom_label( 'group' )
					),
				);
				if ( ( is_array( $select_groups_options ) ) && ( ! empty( $select_groups_options ) ) ) {
					$select_groups_options = $select_groups_options_default + $select_groups_options;
				} else {
					$select_groups_options = $select_groups_options_default;
				}
				$select_groups_options_default = '';
			}

			if ( ( true === $is_hierarchical ) && ( isset( $this->setting_option_values['groups_membership_parent'] ) ) && ( ! empty( $this->setting_option_values['groups_membership_parent'] ) ) && ( $this->_post->ID !== $this->setting_option_values['groups_membership_parent'] ) ) {
				$post_type_object    = get_post_type_object( $this->_post->post_type );
				$display_parent_text = sprintf(
					// translators: placeholders: Post Type singular label, Parent post edit link.
					esc_html_x( 'The membership configuration for this %1$s is managed by the parent %2$s. You may override the parent by enabling the settings below.', 'placeholders: Post Type singular label, Parent post edit link', 'learndash' ),
					$post_type_object->labels->singular_name,
					'<a href="' . get_edit_post_link( $this->setting_option_values['groups_membership_parent'] ) . '">' . get_the_title( $this->setting_option_values['groups_membership_parent'] ) . '</a>'
				);

				echo wp_kses_post( $display_parent_text );
				$this->setting_option_values['groups_membership_enabled'] = '';
			}

			$this->setting_option_fields['groups_membership_enabled'] = array(
				'name'    => 'groups_membership_enabled',
				'label'   => '',
				'type'    => 'hidden',
				'value'   => $this->setting_option_values['groups_membership_enabled'],
				'default' => '',
			);

			$this->setting_option_fields['groups_membership_groups'] = array(
				'name'        => 'groups_membership_groups',
				'type'        => 'multiselect',
				'multiple'    => 'true',
				'default'     => '',
				'value'       => $this->setting_option_values['groups_membership_groups'],
				'placeholder' => $select_groups_options_default,
				'value_type'  => 'intval',
				'label'       => sprintf(
					// translators: placeholder: Groups.
					esc_html_x( 'Associated %s', 'placeholder: Groups', 'learndash' ),
					learndash_get_custom_label( 'groups' )
				),
				'options'     => $select_groups_options,
			);

			$this->setting_option_fields['groups_membership_compare'] = array(
				'name'    => 'groups_membership_compare',
				'label'   => esc_html__( 'Compare Mode', 'learndash' ),
				'type'    => 'radio',
				'default' => 'ANY',
				'value'   => $this->setting_option_values['groups_membership_compare'],
				'options' => array(
					'ANY' => array(
						'label'       => esc_html__( 'Any', 'learndash' ),
						'description' => sprintf(
							// translators: placeholder: group.
							esc_html_x( 'User can be a member of ANY %s to view content.', 'placeholder: group', 'learndash' ),
							learndash_get_custom_label_lower( 'group' )
						),
					),
					'ALL' => array(
						'label'       => esc_html__( 'All', 'learndash' ),
						'description' => sprintf(
							// translators: placeholder: groups.
							esc_html_x( 'User must be a member of ALL %s to view content.', 'placeholder: groups', 'learndash' ),
							learndash_get_custom_label_lower( 'groups' )
						),
					),
				),
			);

			if ( true === $is_hierarchical ) {
				$post_type_object = get_post_type_object( $this->_post->post_type );
				if ( ( $post_type_object ) && ( is_a( $post_type_object, 'WP_Post_Type' ) ) ) {
					$plural_label = $post_type_object->labels->name;
				} else {
					$plural_label = 'Post';
				}

				$this->setting_option_fields['groups_membership_children'] = array(
					'name'    => 'groups_membership_children',
					'label'   => sprintf(
						// translators: placeholder: Post type plural label.
						esc_html_x( 'Apply to sub-%s', 'placeholder: Post type plural label', 'learndash' ),
						$plural_label
					),
					'type'    => 'checkbox-switch',
					'value'   => $this->setting_option_values['groups_membership_children'],
					'default' => '',
					'options' => array(
						'on' => '',
					),
				);
			}

			/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
			$this->setting_option_fields = apply_filters( 'learndash_settings_fields', $this->setting_option_fields, $this->settings_metabox_key );

			parent::load_settings_fields();
		}

		/**
		 * Filter settings values for metabox before save to database.
		 *
		 * @since 3.2.0
		 *
		 * @param array  $settings_values Array of settings values.
		 * @param string $settings_metabox_key Metabox key.
		 * @param string $settings_screen_id Screen ID.
		 *
		 * @return array $settings_values.
		 */
		public function filter_saved_fields( $settings_values = array(), $settings_metabox_key = '', $settings_screen_id = '' ) {
			if ( ( $settings_screen_id === $this->settings_screen_id ) && ( $settings_metabox_key === $this->settings_metabox_key ) ) {

				if ( ! isset( $settings_values['groups_membership_enabled'] ) ) {
					$settings_values['groups_membership_enabled'] = '';
				}

				if ( ! isset( $settings_values['groups_membership_groups'] ) ) {
					$settings_values['groups_membership_groups'] = array();
				}

				if ( ! isset( $settings_values['groups_membership_compare'] ) ) {
					$settings_values['groups_membership_compare'] = 'ANY';
				}

				if ( ! isset( $settings_values['groups_membership_children'] ) ) {
					$settings_values['groups_membership_children'] = '';
				}

				// If we don't have any groups then disable the setting.
				if ( ! empty( $settings_values['groups_membership_groups'] ) ) {
					$settings_values['groups_membership_enabled'] = 'on';
				} else {
					$settings_values['groups_membership_enabled']  = '';
					$settings_values['groups_membership_children'] = '';
					$settings_values['groups_membership_compare']  = '';
				}

				/** This filter is documented in includes/settings/settings-metaboxes/class-ld-settings-metabox-course-access-settings.php */
				$settings_values = apply_filters( 'learndash_settings_save_values', $settings_values, $this->settings_metabox_key );
			}

			return $settings_values;
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
				if ( is_null( $settings_field_updates ) ) {
					$settings_field_updates = $this->get_post_settings_field_updates( $post_id, $saved_post, $update );
				}

				learndash_set_post_group_membership_settings( $post_id, $settings_field_updates );
			}
		}

		// End of functions.
	}
}
