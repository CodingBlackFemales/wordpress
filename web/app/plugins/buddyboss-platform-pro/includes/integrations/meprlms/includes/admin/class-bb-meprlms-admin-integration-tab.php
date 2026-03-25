<?php
/**
 * MemberpressLMS integration admin tab
 *
 * @since 2.6.30
 *
 * @package BuddyBossPro\Integration\MemberpressLMS
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup MemberpressLMS integration admin tab class.
 *
 * @since 2.6.30
 */
class BB_MeprLMS_Admin_Integration_Tab extends BP_Admin_Integration_tab {

	/**
	 * Current section.
	 *
	 * @since 2.6.30
	 *
	 * @var $current_section
	 */
	protected $current_section;

	/**
	 * Initialize
	 *
	 * @since 2.6.30
	 */
	public function initialize() {
		$this->tab_order       = 52;
		$this->current_section = 'bb_meprlms-integration';
		$this->intro_template  = $this->root_path . '/templates/admin/integration-tab-intro.php';

		add_filter( 'bb_admin_icons', array( $this, 'bb_meprlms_admin_setting_icons' ), 10, 2 );
	}

	/**
	 * MemberpressLMS Integration is active?
	 *
	 * @since 2.6.30
	 *
	 * @return bool
	 */
	public function is_active() {

		$active = false;
		if (
            ! bb_pro_should_lock_features() &&
			class_exists( 'memberpress\courses\helpers\Courses' ) &&
			(
				defined( 'BP_PLATFORM_VERSION' ) &&
				version_compare( BP_PLATFORM_VERSION, '2.7.40', '>=' )
			)
		) {
			$active = true;
		}

		return (bool) apply_filters( 'bb_meprlms_integration_is_active', $active );
	}

	/**
	 * Method to save the fields.
	 *
	 * @since 2.6.30
	 */
	public function settings_save() {
		$bb_meprlms_arr = array();
		$fields         = $this->bb_meprlms_get_settings_fields();
		$settings       = bb_get_meprlms_settings();
		foreach ( (array) $fields as $section_id => $section_fields ) {
			foreach ( (array) $section_fields as $field_id => $field ) {
				if ( is_callable( $field['sanitize_callback'] ) ) {
					$value = $field['sanitize_callback']( $value );
				}
				if ( 'bb_meprlms_group_sync_settings_section' === $section_id ) {
                    // phpcs:ignore
					$bb_meprlms_arr[ $field_id ] = isset( $_POST['bb-meprlms'][ $field_id ] ) ? ( is_array( $_POST['bb-meprlms'][ $field_id ] ) ? $_POST['bb-meprlms'][ $field_id ] : $_POST['bb-meprlms'][ $field_id ] ) : 0;
					// Unset key as not required in DB.
					if (
						'bb-meprlms-migration-notice' === $field_id ||
						'bb-meprlms-require-component' === $field_id
					) {
						unset( $bb_meprlms_arr[ $field_id ] );
					}
				}
				if ( 'bb_meprlms_posts_activity_settings_section' === $section_id ) {
					$value = isset( $_POST[ $field_id ] ) ? $_POST[ $field_id ] : 0; // phpcs:ignore
					bp_update_option( $field_id, $value );
				}
			}
		}

		$bb_meprlms_arr = bp_parse_args( $bb_meprlms_arr, $settings );
		bp_update_option( 'bb-meprlms', $bb_meprlms_arr );

		if ( function_exists( 'bb_cpt_feed_enabled_disabled' ) ) {
			bb_cpt_feed_enabled_disabled();
		}
	}

	/**
	 * Register setting fields for MemberpressLMS integration.
	 *
	 * @since 2.6.30
	 */
	public function register_fields() {

		$sections = $this->bb_meprlms_get_settings_sections();

		foreach ( (array) $sections as $section_id => $section ) {

			// Only add section and fields if section has fields.
			$fields = $this->bb_meprlms_get_settings_fields_for_section( $section_id );

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
	 * Get setting sections for MemberpressLMS integration.
	 *
	 * @since 2.6.30
	 *
	 * @return array $settings Settings sections for MemberpressLMS integration.
	 */
	public function bb_meprlms_get_settings_sections() {
		// MemberpressLMS group sync and Post activity feed sections.
		$settings['bb_meprlms_group_sync_settings_section'] = array(
			'page'              => 'MemberPress Courses',
			'title'             => sprintf(
				/* translators: 1. Text. 2. Text. */
				'%1$s&nbsp;<span>&mdash; %2$s</span>',
				esc_html__( 'MemberPress Courses', 'buddyboss-pro' ),
				esc_html__( 'Social Groups', 'buddyboss-pro' )
			),
			'tutorial_callback' => array( $this, 'bb_meprlms_meprlms_group_sync_meprlms_tutorial' ),
		);

		if ( bp_is_active( 'activity' ) ) {
			$settings['bb_meprlms_posts_activity_settings_section'] = array(
				'page'              => 'MemberPress Courses',
				'title'             => sprintf(
					/* translators: 1. Text. 2. Text. */
					'%1$s&nbsp;<span>&mdash; %2$s</span>',
					esc_html__( 'MemberPress Courses', 'buddyboss-pro' ),
					esc_html__( 'Posts in Activity Feed', 'buddyboss-pro' )
				),
				'tutorial_callback' => array( $this, 'bb_meprlms_meprlms_posts_activity_meprlms_tutorial' ),
			);
		}

		return (array) apply_filters( 'bb_meprlms_get_settings_sections', $settings );
	}

	/**
	 * Get setting fields for section in MemberpressLMS integration.
	 *
	 * @since 2.6.30
	 *
	 * @param string $section_id Section ID.
	 *
	 * @return array|false $fields setting fields for section in MemberpressLMS integration false otherwise.
	 */
	public function bb_meprlms_get_settings_fields_for_section( $section_id = '' ) {

		// Bail if section is empty.
		if ( empty( $section_id ) ) {
			return false;
		}

		$fields = $this->bb_meprlms_get_settings_fields();
		$fields = isset( $fields[ $section_id ] ) ? $fields[ $section_id ] : false;

		return $fields;
	}

	/**
	 * Register setting fields for MemberpressLMS integration.
	 *
	 * @since 2.6.30
	 *
	 * @return array $fields setting fields for meprlms integration.
	 */
	public function bb_meprlms_get_settings_fields() {
		$fields = array();

		if ( ! class_exists( 'memberpress\courses\helpers\Courses' ) ) {
			return $fields;
		}

		$bb_meprlms_group_sync_field['bb-meprlms-enable'] = array(
			'title'             => __( 'Enable Integration', 'buddyboss-pro' ),
			'callback'          => array( $this, 'bb_meprlms_integration_sync_callback' ),
			'sanitize_callback' => 'string',
			'args'              => array(),
		);
		if ( bb_meprlms_enable() ) {
			$bb_meprlms_group_sync_field['bb-meprlms-course-visibility'] = array(
				'title'             => __( 'Course Visibility', 'buddyboss-pro' ) . $this->bb_meprlms_require_component_notice(),
				'callback'          => array( $this, 'bb_meprlms_course_visibility_callback' ),
				'sanitize_callback' => 'string',
				'args'              => array( 'class' => bb_meprlms_get_inactive_class() ),
			);
			if ( bp_is_active( 'activity' ) ) {
				$bb_meprlms_group_sync_field['bb-meprlms-course-activity'] = array(
					'title'             => __( 'Display Course Activity', 'buddyboss-pro' ) . $this->bb_meprlms_require_component_notice(),
					'callback'          => array( $this, 'bb_meprlms_display_course_activity_callback' ),
					'sanitize_callback' => 'string',
					'args'              => array( 'class' => bb_meprlms_get_inactive_class() ),
				);
			}

			if ( function_exists( 'bb_meprlms_get_post_types' ) ) {
				$meprlms_post_types = bb_meprlms_get_post_types();
				if ( ! empty( $meprlms_post_types ) ) {
					$fields['bb_meprlms_posts_activity_settings_section']['information'] = array(
						'title'             => esc_html__( 'Custom Posts', 'buddyboss-pro' ),
						'callback'          => array( $this, 'bb_meprlms_posts_activity_callback' ),
						'sanitize_callback' => 'string',
						'args'              => array( 'class' => 'hidden-header' ),
					);
					foreach ( $meprlms_post_types as $post_type ) {
						$option_name         = bb_post_type_feed_option_name( $post_type );
						$post_type_obj       = get_post_type_object( $post_type );
						$child_comment_class = ! bp_is_post_type_feed_enable( $post_type ) ? 'bp-display-none' : '';
						$child_option_name   = bb_post_type_feed_comment_option_name( $post_type );

						// Main post type.
						$fields['bb_meprlms_posts_activity_settings_section'][ $option_name ] = array(
							'title'             => ' ',
							'callback'          => array( $this, 'bb_meprlms_posts_activity_field_callback' ),
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
						$fields['bb_meprlms_posts_activity_settings_section'][ $child_option_name ] = array(
							'title'             => ' ',
							'callback'          => array( $this, 'bb_meprlms_posts_activity_field_callback' ),
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
		$fields['bb_meprlms_group_sync_settings_section'] = $bb_meprlms_group_sync_field;

		return (array) apply_filters( 'bb_meprlms_get_settings_fields', $fields );
	}

	/**
	 * Link to Memberpress LMS Group Sync Settings tutorial.
	 *
	 * @since 2.6.30
	 */
	public function bb_meprlms_meprlms_group_sync_meprlms_tutorial() {
		?>
		<p>
			<a class="button" target="_blank" href="
			<?php
			echo esc_url(
				bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
							'article' => '127882',
						),
						'admin.php'
					)
				)
			);
			?>
			">
				<?php esc_html_e( 'View Tutorial', 'buddyboss-pro' ); ?>
			</a>
		</p>
		<?php
	}

	/**
	 * Link to MemberpressLMS Posts Activity tutorial.
	 *
	 * @since 2.6.30
	 */
	public function bb_meprlms_meprlms_posts_activity_meprlms_tutorial() {
		?>
		<p>
			<a class="button" target="_blank" href="
			<?php
			echo esc_url(
				bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
							'article' => '127882',
						),
						'admin.php'
					)
				)
			);
			?>
			">
				<?php esc_html_e( 'View Tutorial', 'buddyboss-pro' ); ?>
			</a>
		</p>
		<?php
	}

	/**
	 * Callback function MemberpressLMS integration.
	 *
	 * @since 2.6.30
	 */
	public function bb_meprlms_integration_sync_callback() {
		?>
		<input name="bb-meprlms[bb-meprlms-enable]" id="bb-meprlms-enable" type="checkbox" value="1" <?php checked( bb_meprlms_enable() ); ?>/>
		<label for="bb-meprlms-enable">
			<?php esc_html_e( 'Enable MemberPress Courses integration', 'buddyboss-pro' ); ?>
		</label>
		<?php
	}

	/**
	 * Callback function MemberpressLMS course visibility.
	 *
	 * @since 2.6.30
	 */
	public function bb_meprlms_course_visibility_callback() {
		?>
		<input name="bb-meprlms[bb-meprlms-course-visibility]" id="bb-meprlms-course-visibility" type="checkbox" value="1" <?php checked( bb_meprlms_course_visibility() ); ?>/>
		<label for="bb-meprlms-course-visibility">
			<?php esc_html_e( 'Allow administrators to link their courses to groups during group creation and group manage screens.', 'buddyboss-pro' ); ?>
		</label>
		<?php
	}

	/**
	 * Callback function MemberpressLMS display course activity.
	 *
	 * @since 2.6.30
	 */
	public function bb_meprlms_display_course_activity_callback() {
		?>
		<p class="description">
			<?php esc_html_e( 'Any option selected below will appear in group creation and management screens, allowing only site admins to enable or disable course activity posts for groups.', 'buddyboss-pro' ); ?>
		</p>
		<?php
		$meprlms_course_activities = bb_meprlms_course_activities();
		if ( ! empty( $meprlms_course_activities ) ) {
			foreach ( $meprlms_course_activities as $key => $value ) {
				$checked = bb_get_enabled_meprlms_course_activities( $key );
				?>
				<tr class="child-no-padding bb-has-no-label <?php echo esc_attr( bb_meprlms_get_inactive_class() ); ?>">
					<th scope="row"></th>
					<td>
						<input name="bb-meprlms[bb-meprlms-course-activity][<?php echo esc_attr( $key ); ?>]" id="<?php echo esc_attr( $key ); ?>" type="checkbox" value="1" <?php checked( $checked, '1' ); ?>/>
						<label for="<?php echo esc_attr( $key ); ?>"><?php echo esc_html( $value ); ?></label>
					</td>
				</tr>
				<?php
			}
		}
	}

	/**
	 * Callback function MemberpressLMS post types.
	 *
	 * @since 2.6.30
	 */
	public function bb_meprlms_posts_activity_callback() {
		?>
		<p class="description">
			<?php esc_html_e( 'Select which custom post types show in the activity feed when site owners publish them, you can select whether or not to show comments in these activity posts.', 'buddyboss-pro' ); ?>
		</p>
		<?php
	}

	/**
	 * Memberpress LMS posts activity feed fields.
	 *
	 * @since 2.6.30
	 *
	 * @param array $args Array of args.
	 *
	 * @return void
	 */
	public function bb_meprlms_posts_activity_field_callback( $args ) {
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

			remove_filter( 'bb_feed_excluded_post_types', 'bb_feed_not_allowed_meprlms_post_types' );

			if ( in_array( $post_type, bb_feed_not_allowed_comment_post_types(), true ) ) {
				?>
				<p class="description <?php echo esc_attr( 'bp-feed-post-type-comment-' . $post_type ); ?>">
					<?php
						/* translators: %s: Post type name. */
						echo esc_html( sprintf( esc_html__( 'Comments are not supported for %s', 'buddyboss-pro' ), esc_html( $post_type_obj->labels->name ) ) );
					?>
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
					<?php
						/* translators: %s: Post type name. */
						printf( esc_html__( 'Enable %s comments in the activity feed.', 'buddyboss-pro' ), esc_html( $post_type_obj->labels->name ) );
					?>
				</label>
				<?php
			}

			add_filter( 'bb_feed_excluded_post_types', 'bb_feed_not_allowed_meprlms_post_types' );
		}
	}

	/**
	 * Added icon for the MemberpressLMS admin settings.
	 *
	 * @since 2.6.30
	 *
	 * @param string $meta_icon Icon class.
	 * @param string $id        Section ID.
	 *
	 * @return string
	 */
	public function bb_meprlms_admin_setting_icons( $meta_icon, $id = '' ) {
		if (
			'bb_meprlms_group_sync_settings_section' === $id ||
			'bb_meprlms_posts_activity_settings_section' === $id ||
			'bp_meprlms-integration' === $id
		) {
			$meta_icon = 'bb-icon-bf bb-icon-brand-memberpress';
		}

		return $meta_icon;
	}

	/**
	 * Output the form html on the setting page (not including submit button).
	 *
	 * @since 2.6.30
	 */
	public function form_html() {
		// Check license is valid, MemberpressLMS plugin activate, and Platform plugin version dependency.
		if ( ! $this->is_active() ) {
			if ( is_file( $this->intro_template ) ) {
				require $this->intro_template;
			}
		} else {
			parent::form_html();
		}
	}

	/**
	 * Function will add notice about social group required.
	 *
	 * @since 2.6.30
	 */
	public function bb_meprlms_require_component_notice() {
		ob_start();
		if ( ! bp_is_active( 'groups' ) ) {

			printf(
				'<br/><span class="bb-head-notice"> %1$s <strong>%2$s</strong> %3$s</span>',
				esc_html__( 'Require', 'buddyboss-pro' ),
				'<a href="' . esc_url( add_query_arg( array( 'page' => 'bp-components' ), admin_url( 'admin.php' ) ) ) . '">' . esc_html__( 'Social Groups', 'buddyboss-pro' ) . '</a>',
				esc_html__( 'component to be active', 'buddyboss-pro' )
			);
		}
		return ob_get_clean();
	}
}
