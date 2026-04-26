<?php
/**
 * LearnDash Settings Section for Group Leader Users Metabox.
 *
 * @since 3.2.0
 * @package LearnDash\Settings\Sections
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) && ( ! class_exists( 'LearnDash_Settings_Section_Groups_Group_Leader_User' ) ) ) {
	/**
	 * Class LearnDash Settings Section for Group Leader Users Metabox.
	 *
	 * @since 3.2.0
	 */
	class LearnDash_Settings_Section_Groups_Group_Leader_User extends LearnDash_Settings_Section {

		/**
		 * Array or role capabilities ties to the settings fields.
		 *
		 * @since 3.2.0
		 *
		 * @var array $role_caps.
		 */
		private $role_caps = array();

		/**
		 * Protected constructor for class
		 *
		 * @since 3.2.0
		 */
		protected function __construct() {
			$this->settings_screen_id = 'groups_page_groups-options';

			// This is the 'option_name' key used in the wp_options table.
			$this->settings_page_id = 'groups-options';

			// This is the 'option_name' key used in the wp_options table.
			$this->setting_option_key = 'learndash_groups_group_leader_user';

			// This is the HTML form field prefix used.
			$this->setting_field_prefix = 'learndash_groups_group_leader_user';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'settings_group_leader_user';

			// Section label/header.
			$this->settings_section_label = sprintf(
				// translators: placeholder: Group Leader.
				esc_html_x( '%s User Settings', 'placeholder: Group Leader', 'learndash' ),
				learndash_get_custom_label( 'group_leader' )
			);

			$this->settings_section_description = wp_kses_post(
				sprintf(
					// translators: placeholder: Group Leader.
					esc_html_x( 'Controls the %s capabilities.', 'placeholder: Group Leader', 'learndash' ),
					esc_html( learndash_get_custom_label( 'group_leader' ) )
				)
			);

			add_filter( 'learndash_settings_row_outside_before', array( $this, 'learndash_settings_row_outside_before' ), 30, 2 );

			parent::__construct();
		}

		/**
		 * Initialize the metabox settings values.
		 *
		 * @since 3.2.0
		 */
		public function load_settings_values() {
			global $sfwd_lms;

			parent::load_settings_values();

			if ( ! isset( $this->setting_option_values['bypass_course_limits'] ) ) {
				$this->setting_option_values['bypass_course_limits'] = '';
			}

			if ( ! isset( $this->setting_option_values['groups_autoenroll_managed'] ) ) {
				$this->setting_option_values['groups_autoenroll_managed'] = '';
			}

			if ( ! isset( $this->setting_option_values['courses_autoenroll'] ) ) {
				$this->setting_option_values['courses_autoenroll'] = '';
			}

			if ( ! isset( $this->setting_option_values['manage_groups_enabled'] ) ) {
				$this->setting_option_values['manage_groups_enabled'] = '';
			}

			if ( ! isset( $this->setting_option_values['manage_groups_capabilities'] ) ) {
				$this->setting_option_values['manage_groups_capabilities'] = 'basic';
			}

			if ( ! isset( $this->setting_option_values['manage_courses_enabled'] ) ) {
				$this->setting_option_values['manage_courses_enabled'] = '';
			}

			if ( ! isset( $this->setting_option_values['manage_courses_capabilities'] ) ) {
				$this->setting_option_values['manage_courses_capabilities'] = 'basic';
			}

			if ( ! isset( $this->setting_option_values['manage_users_enabled'] ) ) {
				$this->setting_option_values['manage_users_enabled'] = '';
			}

			if ( ! isset( $this->setting_option_values['manage_users_capabilities'] ) ) {
				$this->setting_option_values['manage_users_capabilities'] = 'basic';
			}

			$this->role_caps = array(
				'manage_groups_capabilities'  => array(
					'basic'    => array(
						'edit_groups',
						'edit_published_groups',
						'delete_group',
						'delete_groups',
						'delete_published_groups',
						'publish_groups',
						'upload_files',
					),
					'advanced' => array(
						'delete_others_groups',
						'edit_others_groups',
						'delete_private_groups',
						'edit_private_groups',
						'read_private_groups',
					),
				),
				'manage_courses_capabilities' => array(
					'basic'    => array(
						'edit_courses',
						'edit_published_courses',
						'delete_course',
						'delete_courses',
						'delete_published_courses',
						'publish_courses',
						'upload_files',

						'wpProQuiz_show',
						'wpProQuiz_add_quiz',
						'wpProQuiz_edit_quiz',
						'wpProQuiz_delete_quiz',
						'wpProQuiz_show_statistics',
						'wpProQuiz_toplist_edit',
					),
					'advanced' => array(
						'delete_others_courses',
						'edit_others_courses',
						'delete_private_courses',
						'edit_private_courses',
						'read_private_courses',

						'wpProQuiz_reset_statistics',
						'wpProQuiz_import',
						'wpProQuiz_export',
					),
				),
				'manage_users_capabilities'   => array(
					'basic'    => array(
						'edit_users',
					),
					'advanced' => array(
						'list_users',
						'create_users',
						'delete_users',
					),
				),
			);

			foreach (
				array(
					learndash_get_post_type_slug( 'group' )  => 'manage_groups_capabilities',
					learndash_get_post_type_slug( 'course' ) => 'manage_courses_capabilities',
				) as $post_type => $cap_section ) {

				$post_taxonomies = $sfwd_lms->get_post_args_section( $post_type, 'taxonomies' );
				if ( ! empty( $post_taxonomies ) ) {
					foreach ( $post_taxonomies as $tax ) {
						if ( isset( $tax['capabilities'] ) ) {
							foreach ( $tax['capabilities'] as $key => $cap ) {
								if ( in_array( $key, array( 'manage_terms', 'assign_terms' ), true ) ) {
									$this->role_caps[ $cap_section ]['basic'][] = $cap;
								} else {
									$this->role_caps[ $cap_section ]['advanced'][] = $cap;
								}
							}
						}
					}

					$this->role_caps[ $cap_section ]['basic']    = array_unique( $this->role_caps[ $cap_section ]['basic'] );
					$this->role_caps[ $cap_section ]['advanced'] = array_unique( $this->role_caps[ $cap_section ]['advanced'] );
				}
			}

			/**
			 * Filter Group Leader Role Capabilities.
			 *
			 * @since 3.2.0
			 *
			 * @param array  $role_caps            Group Leader Role Capabilities.
			 * @param string $settings_section_key Settings Section Key.
			 */
			$this->role_caps = apply_filters( 'learndash_group_leader_role_capabilities', $this->role_caps, $this->settings_section_key );
		}

		/**
		 * Initialize the metabox settings fields.
		 *
		 * @since 3.2.0
		 */
		public function load_settings_fields() {

			$this->setting_option_fields = array(
				'groups_autoenroll_managed'   => array(
					'name'      => 'groups_autoenroll_managed',
					'type'      => 'checkbox-switch',
					'label'     => sprintf(
						// translators: placeholder: Groups.
						esc_html_x( 'Managed %s Auto-enrollment', 'placeholder: Groups', 'learndash' ),
						learndash_get_custom_label( 'Groups' )
					),
					'help_text' => sprintf(
						// translators: placeholder: groups.
						esc_html_x( 'Enroll in %s managed by the Group Leader.', 'placeholder: groups', 'learndash' ),
						learndash_get_custom_label_lower( 'groups' )
					),
					'value'     => $this->setting_option_values['groups_autoenroll_managed'],
					'options'   => array(
						''    => sprintf(
							// translators: placeholder: groups.
							esc_html_x( 'Group Leader not enrolled in managed %s.', 'placeholder: groups', 'learndash' ),
							learndash_get_custom_label_lower( 'groups' )
						),
						'yes' => sprintf(
							// translators: placeholder: groups.
							esc_html_x( 'Group Leader enrolled in managed %s.', 'placeholder: groups', 'learndash' ),
							learndash_get_custom_label_lower( 'groups' )
						),
					),
				),
				'courses_autoenroll'          => array(
					'name'      => 'courses_autoenroll',
					'type'      => 'checkbox-switch',
					'label'     => sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s Auto-enrollment', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'help_text' => sprintf(
						// translators: placeholder: group leader, courses, course.
						esc_html_x( 'Allow %1$s users to have access to %2$s automatically without requiring %3$s enrollment.', 'placeholder: group leader, courses, course', 'learndash' ),
						learndash_get_custom_label_lower( 'group_leader' ),
						learndash_get_custom_label_lower( 'courses' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'value'     => $this->setting_option_values['courses_autoenroll'],
					'options'   => array(
						''    => sprintf(
							// translators: placeholder: Group Leader, courses.
							esc_html_x( '%1$s has access to enrolled %2$s only', 'placeholder: Group Leader, courses', 'learndash' ),
							learndash_get_custom_label( 'group_leader' ),
							learndash_get_custom_label_lower( 'courses' )
						),
						'yes' => sprintf(
							// translators: placeholder: Group Leader, courses.
							esc_html_x( '%1$s has access to all %2$s automatically', 'placeholder: Group Leader, courses', 'learndash' ),
							learndash_get_custom_label( 'group_leader' ),
							learndash_get_custom_label_lower( 'courses' )
						),
					),
				),
				'bypass_course_limits'        => array(
					'name'      => 'bypass_course_limits',
					'type'      => 'checkbox-switch',
					'label'     => sprintf(
						// translators: placeholder: Course.
						esc_html_x( 'Bypass %s limits', 'placeholder: Course', 'learndash' ),
						learndash_get_custom_label( 'course' )
					),
					'help_text' => sprintf(
						// translators: placeholder:  group leader, course.
						esc_html_x( 'Allow %1$s users to access %2$s content in any order bypassing progression and access limitations', 'placeholder: course', 'learndash' ),
						learndash_get_custom_label_lower( 'group_leader' ),
						learndash_get_custom_label_lower( 'course' )
					),
					'value'     => $this->setting_option_values['bypass_course_limits'],
					'options'   => array(
						''    => sprintf(
							// translators: placeholder: Group Leader.
							esc_html_x( '%s must follow the progression and access rules', 'placeholder: Group Leader', 'learndash' ),
							learndash_get_custom_label( 'group_leader' )
						),
						'yes' => sprintf(
							// translators: placeholder:  Group leader, course.
							esc_html_x( '%1$s can access %2$s content in any order', 'placeholder: group leader, course', 'learndash' ),
							learndash_get_custom_label( 'group_leader' ),
							learndash_get_custom_label_lower( 'course' )
						),
					),
				),

				'manage_groups_enabled'       => array(
					'name'                => 'manage_groups_enabled',
					'type'                => 'checkbox-switch',
					'label'               => sprintf(
						// translators: placeholder: Groups.
						esc_html_x( 'Manage %s', 'placeholder: Groups', 'learndash' ),
						learndash_get_custom_label( 'groups' )
					),
					'help_text'           => sprintf(
						// translators: placeholder: group leader, groups.
						esc_html_x( 'Allow %1$s to create and manage %2$s.', 'placeholder: group leader, groups', 'learndash' ),
						learndash_get_custom_label_lower( 'group_leader' ),
						learndash_get_custom_label_lower( 'groups' )
					),
					'value'               => $this->setting_option_values['manage_groups_enabled'],
					'options'             => array(
						'yes' => '',
					),
					'child_section_state' => ( 'yes' === $this->setting_option_values['manage_groups_enabled'] ) ? 'open' : 'closed',
				),
				'manage_groups_capabilities'  => array(
					'name'           => 'manage_groups_capabilities',
					'type'           => 'radio',
					'label'          => '',
					'value'          => $this->setting_option_values['manage_groups_capabilities'],
					'options'        => array(
						'basic'    => array(
							'label'       => esc_html__( 'Basic', 'learndash' ),
							'description' => sprintf(
								// translators: placeholder: Group leader, groups.
								esc_html_x( '%1$s can only create, manage, and delete their own %2$s only.', 'placeholder: Group leader, Groups', 'learndash' ),
								learndash_get_custom_label( 'group_leader' ),
								learndash_get_custom_label_lower( 'groups' )
							),
						),
						'advanced' => array(
							'label'       => esc_html__( 'Advanced', 'learndash' ),
							'description' => sprintf(
								// translators: placeholder: Group leader, groups.
								esc_html_x( '%1$s can create, manage and delete ANY %2$s on the site.', 'placeholder: Group leader, groups', 'learndash' ),
								learndash_get_custom_label( 'group_leader' ),
								learndash_get_custom_label_lower( 'groups' )
							),
						),
					),
					'parent_setting' => 'manage_groups_enabled',
				),

				'manage_courses_enabled'      => array(
					'name'                => 'manage_courses_enabled',
					'type'                => 'checkbox-switch',
					'label'               => sprintf(
						// translators: placeholder: Courses.
						esc_html_x( 'Manage %s', 'placeholder: Courses', 'learndash' ),
						learndash_get_custom_label( 'courses' )
					),
					'help_text'           => sprintf(
						// translators: placeholder: group leader, courses.
						esc_html_x( 'Allow %1$s to create and manage %2$s.', 'placeholder: group leader, courses', 'learndash' ),
						learndash_get_custom_label_lower( 'group_leader' ),
						learndash_get_custom_label_lower( 'courses' )
					),
					'value'               => $this->setting_option_values['manage_courses_enabled'],
					'options'             => array(
						'yes' => '',
					),
					'child_section_state' => ( 'yes' === $this->setting_option_values['manage_courses_enabled'] ) ? 'open' : 'closed',
				),
				'manage_courses_capabilities' => array(
					'name'           => 'manage_courses_capabilities',
					'type'           => 'radio',
					'label'          => '',
					'value'          => $this->setting_option_values['manage_courses_capabilities'],
					'options'        => array(
						'basic'    => array(
							'label'       => esc_html__( 'Basic', 'learndash' ),
							'description' => sprintf(
								// translators: placeholder: Group leader, courses.
								esc_html_x( '%1$s can only create, manage, and delete their own %2$s only.', 'placeholder: Group leader, courses', 'learndash' ),
								learndash_get_custom_label( 'group_leader' ),
								learndash_get_custom_label_lower( 'courses' )
							),
						),
						'advanced' => array(
							'label'       => esc_html__( 'Advanced', 'learndash' ),
							'description' => sprintf(
								// translators: placeholder: Group leader, courses.
								esc_html_x( '%1$s can create, manage and delete ANY %2$s on the site.', 'placeholder: Group leader, courses', 'learndash' ),
								learndash_get_custom_label( 'group_leader' ),
								learndash_get_custom_label_lower( 'courses' )
							),
						),
					),
					'parent_setting' => 'manage_courses_enabled',
				),

				'manage_users_enabled'        => array(
					'name'                => 'manage_users_enabled',
					'type'                => 'checkbox-switch',
					'label'               => esc_html__( 'Manage Users', 'learndash' ),
					'help_text'           => sprintf(
						// translators: Group leader.
						esc_html_x( 'Allow %s to create and manage users.', 'placeholder: Group leader', 'learndash' ),
						learndash_get_custom_label_lower( 'group_leader' )
					),
					'value'               => $this->setting_option_values['manage_users_enabled'],
					'options'             => array(
						'yes' => '',
					),
					'child_section_state' => ( 'yes' === $this->setting_option_values['manage_users_enabled'] ) ? 'open' : 'closed',
				),
				'manage_users_capabilities'   => array(
					'name'           => 'manage_users_capabilities',
					'type'           => 'radio',
					'label'          => '',
					'value'          => $this->setting_option_values['manage_users_capabilities'],
					'options'        => array(
						'basic'    => array(
							'label'       => esc_html__( 'Basic', 'learndash' ),
							'description' => sprintf(
								// translators: placeholder: Group leader, courses.
								esc_html_x( '%1$s can only manage users within their assigned %2$s only.', 'placeholder: Group leader, courses', 'learndash' ),
								learndash_get_custom_label( 'group_leader' ),
								learndash_get_custom_label_lower( 'courses' )
							),
						),
						'advanced' => array(
							'label'       => esc_html__( 'Advanced', 'learndash' ),
							'description' => sprintf(
								// translators: placeholder: Group leader.
								esc_html_x( '%s can create, manage and delete ANY users on the site.', 'placeholder: Group leader', 'learndash' ),
								learndash_get_custom_label( 'group_leader' )
							),
						),
					),
					'parent_setting' => 'manage_users_enabled',
				),
			);

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
			global $wpdb;

			$group_leader_role = get_role( 'group_leader' );
			if ( is_null( $group_leader_role ) ) {
				return;
			}

			if ( $setting_option_key === $this->setting_option_key ) {

				// First make sure we have all the needed value keys.
				foreach ( $this->setting_option_fields as $setting_key => $setting_field ) {
					if ( ! isset( $new_values[ $setting_key ] ) ) {
						$new_values[ $setting_key ] = '';
					}

					if ( ! isset( $old_values[ $setting_key ] ) ) {
						$old_values[ $setting_key ] = '';
					}
				}

				$new_values = parent::section_pre_update_option( $new_values, $old_values, $setting_option_key );

				/**
				 * Added to correct issues with Group Leader User capabilities.
				 * See LEARNDASH-5707.
				 *
				 * If $gl_user_activate is not empty it will force changes needed.
				 *
				 * @since 3.4.0.2
				 */
				$gl_user_activate = get_option( 'learndash_groups_group_leader_user_activate', '' );

				foreach ( array( 'groups', 'courses', 'users' ) as $type ) {
					if ( ( ! isset( $old_values[ 'manage_' . $type . '_enabled' ] ) ) || ( $new_values[ 'manage_' . $type . '_enabled' ] !== $old_values[ 'manage_' . $type . '_enabled' ] ) || ( 'yes' === $new_values[ 'manage_' . $type . '_enabled' ] ) || ( ! empty( $gl_user_activate ) ) ) {
						if ( 'yes' !== $new_values[ 'manage_' . $type . '_enabled' ] ) {
							$new_values[ 'manage_' . $type . '_capabilities' ] = 'basic';
							foreach ( $this->role_caps[ 'manage_' . $type . '_capabilities' ]['basic'] as $cap ) {
								$group_leader_role->remove_cap( $cap );
							}

							foreach ( $this->role_caps[ 'manage_' . $type . '_capabilities' ]['advanced'] as $cap ) {
								$group_leader_role->remove_cap( $cap );
							}
						} elseif ( 'yes' === $new_values[ 'manage_' . $type . '_enabled' ] ) {
							foreach ( $this->role_caps[ 'manage_' . $type . '_capabilities' ]['basic'] as $cap ) {
								$group_leader_role->add_cap( $cap, true );
							}

							foreach ( $this->role_caps[ 'manage_' . $type . '_capabilities' ]['advanced'] as $cap ) {
								if ( 'advanced' === $new_values[ 'manage_' . $type . '_capabilities' ] ) {
									$group_leader_role->add_cap( $cap, true );
								} else {
									$group_leader_role->remove_cap( $cap );
								}
							}
						}
					}
				}

				if ( ! empty( $gl_user_activate ) ) {
					update_option( 'learndash_groups_group_leader_user_activate', '' );
				}

				/**
				 * Filters to bypass Group Leader meta update.
				 *
				 * @since 3.1.7
				 *
				 * @param boolean $process_users True to process used. Else false.
				 */
				if ( apply_filters( 'learndash_update_existing_group_leader_users', true ) ) {
					$gl_user_query_args = array(
						'role'         => 'group_leader',
						'number'       => 500,
						'meta_key'     => $wpdb->prefix . 'user_level', // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_key
						'meta_compare' => '=',
					);

					if ( ( 'yes' === $new_values['manage_groups_enabled'] ) || ( 'yes' === $new_values['manage_courses_enabled'] ) ) {
						$gl_user_query_args['meta_value'] = 0; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					} else {
						$gl_user_query_args['meta_value'] = 1; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_value
					}

					$wp_user_query = new WP_User_Query( $gl_user_query_args ); // @phpstan-ignore-line -- legacy code.

					$users = $wp_user_query->get_results();
					if ( ! empty( $users ) ) {
						foreach ( $users as $user ) {
							if ( 0 === $gl_user_query_args['meta_value'] ) {
								update_user_meta( $user->ID, $wpdb->prefix . 'user_level', '1' );
							} else {
								update_user_meta( $user->ID, $wpdb->prefix . 'user_level', '0' );
							}
						}
					}
				}
			}

			return $new_values;
		}

		/**
		 * Settings row outside before
		 *
		 * @since 3.2.0
		 *
		 * @param string $content    Content to show before row.
		 * @param array  $field_args Row field Args.
		 */
		public function learndash_settings_row_outside_before( $content = '', $field_args = array() ) {
			if ( ( isset( $field_args['name'] ) ) && ( in_array( $field_args['name'], array( 'manage_groups_enabled' ), true ) ) ) {

				$content .= '<div class="ld-settings-info-banner ld-settings-info-banner-alert">';

				$message = sprintf(
					// translators: placeholder: anchor to support page and label.
					esc_html_x( 'Activating these options can interfere with customized user roles. If you have customized user roles, please review our support %s before proceeding.', 'placeholder: anchor to support page and label', 'learndash' ),
					'<a target="_blank" href="https://www.learndash.com/support/docs/users-groups/groups/global-group-settings/group-leader-capabilities/">page</a>'
				);
				$content .= wpautop( wptexturize( do_shortcode( $message ) ) );
				$content .= '</div>';
			}
			return $content;
		}

		// End of functions.
	}
}
add_action(
	'learndash_settings_sections_init',
	function() {
		LearnDash_Settings_Section_Groups_Group_Leader_User::add_section_instance();
	}
);
