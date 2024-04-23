<?php
/**
 * TutorLMS integration admin tab
 *
 * @since   2.4.40
 *
 * @package BuddyBossPro/Integration/TutorLMS
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup TutorLMS integration admin tab class.
 *
 * @since 2.4.40
 */
class BB_TutorLMS_Admin_Integration_Tab extends BP_Admin_Integration_tab {

	/**
	 * Current section.
	 *
	 * @since 2.4.40
	 *
	 * @var $current_section
	 */
	protected $current_section;

	/**
	 * Initialize
	 *
	 * @since 2.4.40
	 */
	public function initialize() {
		$this->tab_order       = 52;
		$this->current_section = 'bb_tutorlms-integration';
		$this->intro_template  = $this->root_path . '/templates/admin/integration-tab-intro.php';

		add_filter( 'bb_admin_icons', array( $this, 'bb_tutorlms_admin_setting_icons' ), 10, 2 );
	}

	/**
	 * TutorLMS Integration is active?
	 *
	 * @since 2.4.40
	 *
	 * @return bool
	 */
	public function is_active() {
		$active = false;
		if (
			bbp_pro_is_license_valid() &&
			function_exists( 'tutor' ) &&
			(
				defined( 'BP_PLATFORM_VERSION' ) &&
				version_compare( BP_PLATFORM_VERSION, '2.5.00', '>=' )
			)
		) {
			$active = true;
		}

		return (bool) apply_filters( 'bb_tutorlms_integration_is_active', $active );
	}

	/**
	 * Method to save the fields.
	 *
	 * @since 2.4.40
	 */
	public function settings_save() {
		$bb_tutorlms_arr = array();
		$fields          = $this->bb_tutorlms_get_settings_fields();
		$settings        = bb_get_tutorlms_settings();
		foreach ( (array) $fields as $section_id => $section_fields ) {
			foreach ( (array) $section_fields as $field_id => $field ) {
				if ( is_callable( $field['sanitize_callback'] ) ) {
					$value = $field['sanitize_callback']( $value );
				}
				if ( 'bb_tutorlms_group_sync_settings_section' === $section_id ) {
					$bb_tutorlms_arr[ $field_id ] = isset( $_POST['bb-tutorlms'][ $field_id ] ) ? ( is_array( $_POST['bb-tutorlms'][ $field_id ] ) ? $_POST['bb-tutorlms'][ $field_id ] : $_POST['bb-tutorlms'][ $field_id ] ) : 0;
					// Unset key as not required in DB.
					if (
						'bb-tutorlms-migration-notice' === $field_id ||
						'bb-tutorlms-require-component' === $field_id
					) {
						unset( $bb_tutorlms_arr[ $field_id ] );
					}
				}
				if ( 'bb_tutorlms_posts_activity_settings_section' === $section_id ) {
					$value = isset( $_POST[ $field_id ] ) ? $_POST[ $field_id ] : 0;
					bp_update_option( $field_id, $value );
				}
			}
		}

		$bb_tutorlms_arr = bp_parse_args( $bb_tutorlms_arr, $settings );
		bp_update_option( 'bb-tutorlms', $bb_tutorlms_arr );

		if ( function_exists( 'bb_cpt_feed_enabled_disabled' ) ) {
			bb_cpt_feed_enabled_disabled();
		}
	}

	/**
	 * Register setting fields for TutorLMS integration.
	 *
	 * @since 2.4.40
	 */
	public function register_fields() {

		$sections = $this->bb_tutorlms_get_settings_sections();

		foreach ( (array) $sections as $section_id => $section ) {

			// Only add section and fields if section has fields.
			$fields = $this->bb_tutorlms_get_settings_fields_for_section( $section_id );

			if ( empty( $fields ) ) {
				continue;
			}

			$section_title     = ! empty( $section['title'] ) ? $section['title'] : '';
			$section_callback  = ! empty( $section['callback'] ) ? $section['callback'] : false;
			$tutorial_callback = ! empty( $section['tutorial_callback'] ) ? $section['tutorial_callback'] : false;

			// Add the section.
			$this->add_section( $section_id, $section_title, $section_callback, $tutorial_callback );

			// Loop through fields for this section.
			foreach ( (array) $fields as $field_id => $field ) {

				$field['args'] = isset( $field['args'] ) ? $field['args'] : array();

				if ( ! empty( $field['callback'] ) && ! empty( $field['title'] ) ) {
					$sanitize_callback = isset( $field['sanitize_callback'] ) ? $field['sanitize_callback'] : array();
					$this->add_field( $field_id, $field['title'], $field['callback'], $sanitize_callback, $field['args'] );
				}
			}
		}
	}

	/**
	 * Get setting sections for TutorLMS integration.
	 *
	 * @since 2.4.40
	 *
	 * @return array $settings Settings sections for TutorLMS integration.
	 */
	public function bb_tutorlms_get_settings_sections() {
		// TutorLMS group sync and Post activity feed sections.
		$settings['bb_tutorlms_group_sync_settings_section'] = array(
			'page'              => 'TutorLMS',
			'title'             => sprintf(
				/* translators: 1. Text. 2. Text. */
				'%1$s&nbsp;<span>&mdash; %2$s</span>',
				esc_html__( 'TutorLMS', 'buddyboss-pro' ),
				esc_html__( 'Social Groups', 'buddyboss-pro' )
			),
			'tutorial_callback' => array( $this, 'bb_tutorlms_tutorlms_group_sync_tutorial' ),
		);

		if ( bp_is_active( 'activity' ) ) {
			$settings['bb_tutorlms_posts_activity_settings_section'] = array(
				'page'              => 'TutorLMS',
				'title'             => __( 'Posts in Activity Feed', 'buddyboss-pro' ),
				'tutorial_callback' => array( $this, 'bb_tutorlms_tutorlms_posts_activity_tutorial' ),
			);
		}

		return (array) apply_filters( 'bb_tutorlms_get_settings_sections', $settings );
	}

	/**
	 * Get setting fields for section in TutorLMS integration.
	 *
	 * @since 2.4.40
	 *
	 * @param string $section_id Section ID.
	 *
	 * @return array|false $fields setting fields for section in TutorLMS integration false otherwise.
	 */
	public function bb_tutorlms_get_settings_fields_for_section( $section_id = '' ) {

		// Bail if section is empty.
		if ( empty( $section_id ) ) {
			return false;
		}

		$fields = $this->bb_tutorlms_get_settings_fields();
		$fields = isset( $fields[ $section_id ] ) ? $fields[ $section_id ] : false;

		return $fields;
	}

	/**
	 * Register setting fields for TutorLMS integration.
	 *
	 * @since 2.4.40
	 *
	 * @return array $fields setting fields for tutorlms integration.
	 */
	public function bb_tutorlms_get_settings_fields() {
		$fields = array();

		if ( ! function_exists( 'tutor' ) ) {
			return $fields;
		}

		if ( bp_is_active( 'groups' ) ) {
			if (
				bb_tutorlms_enable() &&
				! bp_get_option( 'bb_migration_tutorlms_buddypress_group_course' )
			) {
				global $wpdb, $bp;
				$group_datas = $wpdb->get_row(
					$wpdb->prepare(
						"SELECT COUNT( DISTINCT g.id ) as total_count
                        FROM {$bp->groups->table_name} g 
                        LEFT JOIN {$bp->groups->table_name_groupmeta} gm ON g.id = gm.group_id
                        LEFT JOIN {$bp->groups->table_name_groupmeta} gm1 ON g.id = gm1.group_id
                        WHERE gm.meta_key = '%s' OR gm1.meta_key = '%s'
                        ORDER BY g.id DESC", '_tutor_attached_course', '_tutor_bp_group_activities'
					),
					ARRAY_A
				);
				if ( ! empty( $group_datas['total_count'] ) ) {
					$bb_tutorlms_group_sync_field['bb-tutorlms-migration-notice'] = array(
						'title'             => ' ',
						'callback'          => array( $this, 'bb_tutorlms_migration_notice' ),
						'sanitize_callback' => 'string',
						'args'              => array(),
					);
				}
			}
			$bb_tutorlms_group_sync_field['bb-tutorlms-enable'] = array(
				'title'             => __( 'TutorLMS Group Sync', 'buddyboss-pro' ),
				'callback'          => array( $this, 'bb_tutorlms_group_sync_callback' ),
				'sanitize_callback' => 'string',
				'args'              => array(),
			);
			if ( bb_tutorlms_enable() ) {
				$bb_tutorlms_group_sync_field['bb-tutorlms-course-visibility'] = array(
					'title'             => __( 'Course Visibility', 'buddyboss-pro' ),
					'callback'          => array( $this, 'bb_tutorlms_course_visibility_callback' ),
					'sanitize_callback' => 'string',
					'args'              => array(),
				);
				if ( bp_is_active( 'activity' ) ) {
					$bb_tutorlms_group_sync_field['bb-tutorlms-course-activity'] = array(
						'title'             => __( 'Display Course Activity', 'buddyboss-pro' ),
						'callback'          => array( $this, 'bb_tutorlms_display_course_activity_callback' ),
						'sanitize_callback' => 'string',
						'args'              => array(),
					);
				}

				if ( function_exists( 'bb_tutorlms_get_post_types' ) ) {
					$tutorlms_post_types = bb_tutorlms_get_post_types();
					if ( ! empty( $tutorlms_post_types ) ) {
						$fields['bb_tutorlms_posts_activity_settings_section']['information'] = array(
							'title'             => esc_html__( 'Custom Posts', 'buddyboss-pro' ),
							'callback'          => array( $this, 'bb_tutorlms_posts_activity_callback' ),
							'sanitize_callback' => 'string',
							'args'              => array( 'class' => 'hidden-header' ),
						);
						foreach ( $tutorlms_post_types as $post_type ) {
							$option_name         = bb_post_type_feed_option_name( $post_type );
							$post_type_obj       = get_post_type_object( $post_type );
							$child_comment_class = ! bp_is_post_type_feed_enable( $post_type ) ? 'bp-display-none' : '';
							$child_option_name   = bb_post_type_feed_comment_option_name( $post_type );

							// Main post type.
							$fields['bb_tutorlms_posts_activity_settings_section'][ $option_name ] = array(
								'title'             => ' ',
								'callback'          => array( $this, 'bb_tutorlms_posts_activity_field_callback' ),
								'sanitize_callback' => 'string',
								'args'              => array(
									'action'        => 'post',
									'post_type'     => $post_type,
									'option_name'   => $option_name,
									'post_type_obj' => $post_type_obj,
									'class'         => 'th-hide child-no-padding',
								),
							);

							// Comment of post type.
							$fields['bb_tutorlms_posts_activity_settings_section'][ $child_option_name ] = array(
								'title'             => ' ',
								'callback'          => array( $this, 'bb_tutorlms_posts_activity_field_callback' ),
								'sanitize_callback' => 'string',
								'args'              => array(
									'action'        => 'comment',
									'post_type'     => $post_type,
									'option_name'   => $child_option_name,
									'post_type_obj' => $post_type_obj,
									'class'         => 'th-hide child-no-padding child-custom-post-type bp-child-post-type ' . esc_attr( $child_comment_class ),
								),
							);
						}
					}
				}
			}
		} else {
			$bb_tutorlms_group_sync_field['bb-tutorlms-require-component'] = array(
				'title'             => ' ',
				'callback'          => array( $this, 'bb_tutorlms_require_component_notice' ),
				'sanitize_callback' => 'string',
				'args'              => array( 'class' => 'hidden-header' ),
			);
		}
		$fields['bb_tutorlms_group_sync_settings_section'] = $bb_tutorlms_group_sync_field;

		return (array) apply_filters( 'bb_tutorlms_get_settings_fields', $fields );
	}

	/**
	 * Link to TutorLMS Group Sync Settings tutorial.
	 *
	 * @since 2.4.40
	 */
	public function bb_tutorlms_tutorlms_group_sync_tutorial() {
		?>
		<p>
			<a class="button" href="
			<?php echo esc_url(
				bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
							'article' => '87907',
						),
						'admin.php'
					)
				)
			);
			?>">
				<?php esc_html_e( 'View Tutorial', 'buddyboss-pro' ); ?>
			</a>
		</p>
		<?php
	}

	/**
	 * Link to TutorLMS Posts Activity tutorial.
	 *
	 * @since 2.4.40
	 */
	public function bb_tutorlms_tutorlms_posts_activity_tutorial() {
		?>
		<p>
			<a class="button" href="
			<?php echo esc_url(
				bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
							'article' => '87907',
						),
						'admin.php'
					)
				)
			);
			?>">
				<?php esc_html_e( 'View Tutorial', 'buddyboss-pro' ); ?>
			</a>
		</p>
		<?php
	}

	/**
	 * Callback function TutorLMS group sync.
	 *
	 * @since 2.4.40
	 */
	public function bb_tutorlms_group_sync_callback() {
		?>
		<input name="bb-tutorlms[bb-tutorlms-enable]" id="bb-tutorlms-enable" type="checkbox" value="1" <?php checked( bb_tutorlms_enable() ); ?>/>
		<label for="bb-tutorlms-enable">
			<?php esc_html_e( 'Enable TutorLMS integration settings', 'buddyboss-pro' ); ?>
		</label>
		<?php
	}

	/**
	 * Callback function TutorLMS course visibility.
	 *
	 * @since 2.4.40
	 */
	public function bb_tutorlms_course_visibility_callback() {
		?>
		<input name="bb-tutorlms[bb-tutorlms-course-visibility]" id="bb-tutorlms-course-visibility" type="checkbox" value="1" <?php checked( bb_tutorlms_course_visibility() ); ?>/>
		<label for="bb-tutorlms-course-visibility">
			<?php esc_html_e( 'Allow course instructors to link their courses to groups during group creation and group manage screens.', 'buddyboss-pro' ); ?>
		</label>
		<?php
	}

	/**
	 * Callback function TutorLMS display course activity.
	 *
	 * @since 2.4.40
	 */
	public function bb_tutorlms_display_course_activity_callback() {
		?>
		<p class="description">
			<?php esc_html_e( 'Any option selected below will show in group creation and group manage screens to allow group organizer to enable or disable course activity posts for their own group.', 'buddyboss-pro' ); ?>
		</p>
		<?php
		$tutorlms_course_activities = bb_tutorlms_course_activities();
		if ( ! empty( $tutorlms_course_activities ) ) {
			foreach ( $tutorlms_course_activities as $key => $value ) {
				$checked = bb_get_enabled_tutorlms_course_activities( $key );
				?>
				<tr class="child-no-padding">
					<th scope="row"></th>
					<td>
						<input name="bb-tutorlms[bb-tutorlms-course-activity][<?php echo esc_attr( $key ); ?>]" id="<?php echo esc_attr( $key ); ?>" type="checkbox" value="1" <?php checked( $checked, '1' ); ?>/>
						<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value ); ?></label>
					</td>
				</tr>
				<?php
			}
		}
	}

	/**
	 * Callback function TutorLMS post types.
	 *
	 * @since 2.4.40
	 */
	public function bb_tutorlms_posts_activity_callback() {
		?>
		<p class="description">
			<?php esc_html_e( 'Select which custom post types show in the activity feed when members instructors and site owners publish them, you can select whether or not to show comments in these activity posts.', 'buddyboss-pro' ); ?>
		</p>
		<?php
	}

	/**
	 * Tutor LMS posts activity feed fields.
	 *
	 * @since 2.4.40
	 *
	 * @param array $args Array of args.
	 *
	 * @return void
	 */
	public function bb_tutorlms_posts_activity_field_callback( $args ) {
		$action        = $args['action'];
		$post_type     = $args['post_type'];
		$option_name   = $args['option_name'];
		$post_type_obj = $args['post_type_obj'];
		if ( 'post' === $action ) {
			?>
			<input class="bp-feed-post-type-checkbox <?php echo esc_attr( $option_name ); ?>"
				data-post_type="<?php echo esc_attr( $post_type ); ?>"
				name="<?php echo esc_attr( $option_name ); ?>"
				id="<?php echo esc_attr( $option_name ); ?>"
				type="checkbox"
				value="1"
				<?php checked( bp_is_post_type_feed_enable( $post_type, false ) ); ?>
			/>
			<label for="<?php echo esc_attr( $option_name ); ?>">
				<?php echo esc_html( $post_type_obj->labels->name ); ?>
			</label>
			<?php
		} else {

			remove_filter( 'bb_feed_excluded_post_types', 'bb_feed_not_allowed_tutorlms_post_types' );

			if ( in_array( $post_type, bb_feed_not_allowed_comment_post_types(), true ) ) {
				?>
				<p class="description <?php echo esc_attr( 'bp-feed-post-type-comment-' . $post_type ); ?>">
					<?php echo esc_html( sprintf( esc_html__( 'Comments are not supported for %s', 'buddyboss-pro' ), esc_html( $post_type_obj->labels->name ) ) ); ?>
				</p>
				<?php
			} else {
				$is_cpt_comment_enabled = bb_activity_is_enabled_cpt_global_comment( $post_type );
				?>
				<input class="bp-feed-post-type-commenet-checkbox bp-feed-post-type-comment-<?php echo esc_attr( $post_type ); ?> <?php echo esc_attr( $option_name ); ?>"
					data-post_type="<?php echo esc_attr( $post_type ); ?>"
					name="<?php echo esc_attr( $option_name ); ?>"
					id="<?php echo esc_attr( $option_name ); ?>"
					type="checkbox"
					value="1"
					<?php checked( bb_is_post_type_feed_comment_enable( $post_type, false ) ); ?>
					<?php disabled( $is_cpt_comment_enabled, false ); ?>
				/>
				<label for="<?php echo esc_attr( $option_name ); ?>">
					<?php echo sprintf( esc_html__( 'Enable %s comments in the activity feed.', 'buddyboss-pro' ), esc_html( $post_type_obj->labels->name ) ); ?>
				</label>
				<?php
			}

			add_filter( 'bb_feed_excluded_post_types', 'bb_feed_not_allowed_tutorlms_post_types' );
		}
	}

	/**
	 * Added icon for the TutorLMS admin settings.
	 *
	 * @since 2.4.40
	 *
	 * @param string $meta_icon Icon class.
	 * @param string $id        Section ID.
	 *
	 * @return string
	 */
	public function bb_tutorlms_admin_setting_icons( $meta_icon, $id = '' ) {
		if (
			'bb_tutorlms_group_sync_settings_section' === $id ||
			'bb_tutorlms_posts_activity_settings_section' === $id ||
			'bp_tutor-integration' === $id
		) {
			$meta_icon = 'bb-icon-bf bb-icon-brand-tutorlms';
		}

		return $meta_icon;
	}

	/**
	 * Output the form html on the setting page (not including submit button).
	 *
	 * @since 2.4.40
	 */
	public function form_html() {
		// Check license is valid, TutorLMS plugin activate, and Platform plugin version dependency.
		if ( ! $this->is_active() ) {
			if ( is_file( $this->intro_template ) ) {
				require $this->intro_template;
			}
		} else {
			parent::form_html();
		}
	}

	/**
	 * Function will add migration notice about BuddyPress group course to BuddyBoss group course.
	 *
	 * @since 2.4.40
	 */
	public function bb_tutorlms_migration_notice() {
		?>
		<div class="bbpro-tutorlms-warning bb-warning-section">
			<?php
			echo sprintf(
				__( 'To migrate BuddyPress group courses to BuddyBoss group courses. <a href="%s">Click Here</a>.', 'buddyboss-pro' ),
				esc_url(
					bp_get_admin_url(
						add_query_arg(
							array(
								'page'     => 'bp-repair-community',
								'tab'      => 'bp-repair-community',
								'tool'     => 'bp-migrate-tutorlms-buddypress-group-course',
								'scrollto' => 'bpmigratetutorgroupcourse',
							),
							'admin.php'
						)
					)
				)
			);
			?>
		</div>
		<?php
	}

	/**
	 * Function will add notice about social group required.
	 *
	 * @since 2.4.40
	 */
	public function bb_tutorlms_require_component_notice() {
		?>
		<p class="show-full-width">
			<?php
			printf(
				__( 'You need to activate the <a href="%s">Social Groups Component</a> in order to sync TutorLMS with Social Groups.', 'buddyboss-pro' ),
				add_query_arg(
					array(
						'page' => 'bp-components',
					),
					admin_url( 'admin.php' )
				)
			)
			?>
		</p>
		<?php
	}
}
