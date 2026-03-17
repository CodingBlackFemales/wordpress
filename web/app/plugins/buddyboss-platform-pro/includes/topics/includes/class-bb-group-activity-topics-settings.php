<?php
/**
 * BuddyBoss Groups Activity Topics Settings.
 *
 * @package BuddyBoss\Groups\Activity Topics
 * @since 2.7.40
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BB_Group_Activity_Topics_Setting
 *
 * @since 2.7.40
 */
class BB_Group_Activity_Topics_Setting extends BP_Group_Extension {
	/**
	 * Your __construct() method will contain configuration options for
	 * Activity Topics extension.
	 *
	 * @since 2.7.40
	 */
	public function __construct() {
		$can_allow_tab              = ( function_exists( 'bb_is_enabled_group_activity_topics' ) && bb_is_enabled_group_activity_topics() );
		$this->name                 = apply_filters( 'bb_group_activity_topics_tab_name', __( 'Topics', 'buddyboss-pro' ) );
		$this->slug                 = 'topics';
		$this->create_step_position = 12;
		$this->nav_item_position    = -30;
		$this->enable_nav_item      = false;

		$args = array(
			'access'  => apply_filters( 'bb_group_activity_topics_tab_enabled', $this->enable_nav_item ),
			'screens' => array(
				'create' => array(
					'enabled'  => apply_filters( 'bb_group_activity_topics_tab_enabled/screen=create', $can_allow_tab ),
					'name'     => apply_filters( 'bb_group_activity_topics_tab_name/screen=create', $this->name ),
					'slug'     => $this->slug,
					'position' => apply_filters( 'bb_group_activity_topics_tab_position/screen=create', $this->create_step_position ),
				),

				'edit'   => array(
					'enabled'  => apply_filters( 'bb_group_activity_topics_tab_enabled/screen=edit', $can_allow_tab ),
					'name'     => apply_filters( 'bb_group_activity_topics_tab_name/screen=edit', $this->name ),
					'slug'     => $this->slug,
					'position' => apply_filters( 'bb_group_activity_topics_tab_position/screen=edit', $this->nav_item_position ),
				),
			),
		);

		parent::init( $args );

		$this->setup_actions();
	}

	/**
	 * Set up the group topics, class actions.
	 *
	 * @since 2.7.40
	 */
	private function setup_actions() {
		add_action( 'bb_admin_setting_activity_topic_register_fields', array( $this, 'bb_admin_setting_activity_topic_register_fields_callback' ) );

		if ( ! bb_is_enabled_group_activity_topics() ) {
			return;
		}

		// Adds the Activity Topics metabox to the new BuddyBoss Group Admin UI.
		add_action( 'bp_groups_admin_meta_boxes', array( $this, 'bb_group_activity_topics_admin_ui_edit_screen' ) );

		add_action( 'wp_ajax_bb_get_global_activity_topics', array( $this, 'bb_get_global_activity_topics' ) );
		add_action( 'wp_ajax_nopriv_bb_get_global_activity_topics', array( $this, 'bb_get_global_activity_topics' ) );

		add_filter( 'bb_topics_js_strings', array( $this, 'bb_topics_js_strings' ) );
		add_action( 'bb_topic_before_added', array( $this, 'bb_validate_group_activity_topic_before_added' ) );
		add_action( 'bp_after_group_activity_post_form', array( $this, 'bb_group_activity_topics_after_post_form' ) );

		add_action( 'bp_activity_get_edit_data', array( $this, 'bb_activity_get_edit_group_topic_data' ), 11, 1 );
	}

	/**
	 * Adds a Group Topics field to the Activity Topics settings.
	 *
	 * @since 2.7.40
	 *
	 * @param BP_Admin_Setting_Activity $field_obj The field object.
	 */
	public function bb_admin_setting_activity_topic_register_fields_callback( $field_obj ) {
		$is_enabled_activity_topics       = true === bb_is_enabled_activity_topics();
		$is_enabled_group_activity_topics = true === bb_is_enabled_group_activity_topics();
		$field_obj->add_field(
			'bb-group-activity-topics-options',
			esc_html__( 'Group Topic Options', 'buddyboss-pro' ),
			array( $this, 'bb_admin_setting_activity_topic_register_fields_callback_field' ),
			'string',
			array(
				'class' => 'bb_enable_activity_topics_required bb_enable_group_activity_topics_required ' . ( $is_enabled_activity_topics && $is_enabled_group_activity_topics ? '' : 'bp-hide' ),
			)
		);
	}

	/**
	 * Adds a global Group Topics field to the Activity Topics settings.
	 *
	 * @since 2.7.40
	 *
	 * @param BP_Admin_Setting_Activity $args The field arguments.
	 */
	public function bb_admin_setting_activity_topic_register_fields_callback_field( $args ) {
		$saved_value = bb_get_group_activity_topic_options();
		?>
		<div class="bb-radio-options-field">
			<input id="bb-group-topic-only-from-activity-topics" name="bb-group-activity-topics-options" type="radio" value="only_from_activity_topics" <?php checked( $saved_value, 'only_from_activity_topics' ); ?> />
			<label for="bb-group-topic-only-from-activity-topics">
				<?php esc_html_e( 'Allow group organizers to use topics from only activity topics.', 'buddyboss-pro' ); ?>
			</label>
		</div>

		<div class="bb-radio-options-field">
			<input id="bb-group-topic-create-own-topics" name="bb-group-activity-topics-options" type="radio" value="create_own_topics" <?php checked( $saved_value, 'create_own_topics' ); ?> />
			<label for="bb-group-topic-create-own-topics">
				<?php esc_html_e( 'Allow group organizers to create own topics', 'buddyboss-pro' ); ?>
			</label>
		</div>

		<div class="bb-radio-options-field">
			<input id="bb-group-topic-allow-both" name="bb-group-activity-topics-options" type="radio" value="allow_both" <?php checked( $saved_value, 'allow_both' ); ?> />
			<label for="bb-group-topic-allow-both">
				<?php esc_html_e( 'Allow both', 'buddyboss-pro' ); ?>
			</label>
		</div>
		<?php
	}

	/**
	 * Adds a BuddyBoss Group Activity Topics metabox to BuddyBoss Group Admin UI.
	 *
	 * @since 2.4.40
	 *
	 * @uses add_meta_box
	 */
	public function bb_group_activity_topics_admin_ui_edit_screen() {
		add_meta_box(
			'bb_group_activity_topics_group_admin_ui_meta_box',
			esc_html__( 'Topics', 'buddyboss-pro' ),
			array( $this, 'bb_group_activity_topics_admin_ui_display_metabox' ),
			get_current_screen()->id,
			'advanced',
			'low'
		);
	}

	/**
	 * Displays the Activity Topics metabox in BuddyBoss Group Admin UI.
	 *
	 * @param object $item (group object).
	 *
	 * @since 2.7.40
	 */
	public function bb_group_activity_topics_admin_ui_display_metabox( $item = false ) {
		$this->edit_screen( $item );
	}

	/**
	 * The primary display function for group topics.
	 *
	 * @since 2.7.40
	 *
	 * @param int|null $group_id ID of the group to display.
	 */
	public function display( $group_id = null ) {

		/**
		 * Fires before the group activity topics page content is displayed.
		 *
		 * @since 2.7.40
		 */
		do_action( 'template_notices' );

		/**
		 * Fires before the group activity topics page content is displayed.
		 *
		 * @since 2.7.40
		 */
		do_action( 'bb_group_activity_topics_before_page_content' );

		bp_get_template_part( 'groups/single/activity-topics' );

		/**
		 * Fires after the group activity topics page content is displayed.
		 *
		 * @since 2.7.40
		 */
		do_action( 'bb_group_activity_topics_after_page_content' );
	}

	/**
	 * Show the topic settings when creating a group.
	 *
	 * @since 2.7.40
	 *
	 * @param int $group_id Group ID.
	 *
	 * @return void
	 */
	public function create_screen( $group_id = 0 ) {
		// Bail if not looking at this screen.
		if ( ! bp_is_group_creation_step( $this->slug ) ) {
			return;
		}

		$group_id = $group_id ? bp_get_new_group_id() : $group_id;

		bp_locate_template(
			'groups/single/admin/activity-topics.php',
			true,
			true,
			array(
				'action'   => 'create',
				'group_id' => $group_id,
			)
		);
	}

	/**
	 * Displays the settings for group activity topics.
	 *
	 * @since 2.7.40
	 *
	 * @param int|object $group Group object.
	 */
	public function edit_screen( $group = null ) {
		$group_id = empty( $group->id ) ? bp_get_new_group_id() : $group->id;
		if ( empty( $group_id ) ) {
			$group_id = $group;
		}

		bp_locate_template(
			'groups/single/admin/activity-topics.php',
			true,
			true,
			array(
				'action'   => 'edit',
				'group_id' => $group_id,
			)
		);
	}

	/**
	 * Save the Group activity topics data on create.
	 *
	 * @since 2.7.40
	 *
	 * @param int $group_id Group ID.
	 */
	public function create_screen_save( $group_id = 0 ) {

		/**
		 * Fire before saving the topics settings in the create group screen.
		 *
		 * @since 2.7.40
		 *
		 * @param int $group_id Group ID.
		 */
		do_action( 'bb_topics_create_group_screen_before_save', $group_id );

		// Nonce check.
		check_admin_referer( 'groups_create_save_' . $this->slug );

		/**
		 * Fire after saving the topics settings in the create group screen.
		 *
		 * @since 2.7.40
		 *
		 * @param int $group_id Group ID.
		 */
		do_action( 'bb_topics_create_group_screen_after_save', $group_id );
	}

	/**
	 * Fetch global activity topics.
	 *
	 * @since 2.7.40
	 */
	public function bb_get_global_activity_topics() {
		check_ajax_referer( 'bb_get_global_activity_topics', 'nonce' );
		$topics = bb_topics_manager_instance()->bb_get_topics(
			array(
				'item_id'   => 0,
				'item_type' => 'activity',
				'per_page'  => -1,
			)
		);
		wp_send_json_success( array( 'topics' => $topics['topics'] ) );
	}

	/**
	 * Add topics limit for the current group to the topics manager JS strings.
	 *
	 * @since 2.7.40
	 *
	 * @param array $strings The strings array.
	 *
	 * @return array The string array.
	 */
	public function bb_topics_js_strings( $strings ) {
		$curreent_group_id                = function_exists( 'bp_get_current_group_id' ) ? bp_get_current_group_id() : 0;
		$strings['topics_limit']          = bb_topics_manager_instance()->bb_topics_limit(
			array(
				'item_id'   => $curreent_group_id,
				'item_type' => 'groups',
			)
		);
		$strings['create_new_topic_text'] = __( 'New Topic', 'buddyboss-pro' );
		$strings['error_message']         = __( 'Please enter a valid topic name.', 'buddyboss-pro' );
		return $strings;
	}

	/**
	 * Validate the group activity topic before adding.
	 *
	 * @since 2.7.40
	 *
	 * @param array $args The arguments array.
	 */
	public function bb_validate_group_activity_topic_before_added( $args ) {
		if ( 'groups' === $args['item_type'] && ! empty( $args['item_id'] ) ) {
			$group = groups_get_group( $args['item_id'] );
			if ( ! $group ) {
				$error_message = __( 'Group not found.', 'buddyboss-pro' );
				if ( 'wp_error' === $args['error_type'] ) {
					return new WP_Error( 'bb_topic_not_allowed', $error_message );
				}

				wp_send_json_error( array( 'error' => $error_message ) );
			}

			if (
				! bp_current_user_can( 'administrator' ) &&
				! groups_is_user_admin( bp_loggedin_user_id(), $group->id )
			) {
				$error_message = __( 'You are not allowed to add a topic.', 'buddyboss-pro' );
				if ( 'wp_error' === $args['error_type'] ) {
					return new WP_Error( 'bb_topic_not_allowed', $error_message );
				}

				wp_send_json_error( array( 'error' => $error_message ) );
			}
		}
	}

	/**
	 * Add group activity topics selectors after the post form.
	 *
	 * @since 2.7.40
	 */
	public function bb_group_activity_topics_after_post_form() {
		// If group activity topics are not enabled, then the selector will not be shown.
		if ( ! bb_is_enabled_activity_topics() || ! bb_is_enabled_group_activity_topics() ) {
			return;
		}
		$item_id = bp_get_current_group_id();
		$topics  = function_exists( 'bb_get_group_activity_topics' ) ? bb_get_group_activity_topics(
			array(
				'item_id' => $item_id,
			)
		) : array();
		if ( ! empty( $topics ) ) {
			$group               = groups_get_group( $item_id );
			$directory_permalink = bp_get_group_permalink( $group ) . bp_get_activity_slug();
			$current_slug        = function_exists( 'bb_topics_manager_instance' ) ? bb_topics_manager_instance()->bb_get_topic_slug_from_url() : '';
			?>
			<div class="activity-topic-selector">
				<ul>
					<li>
						<a href="<?php echo esc_url( $directory_permalink ); ?>"><?php esc_html_e( 'All', 'buddyboss-pro' ); ?></a>
					</li>
					<?php
					foreach ( $topics as $topic ) {
						$li_class = '';
						$a_class  = '';
						if ( ! empty( $current_slug ) && $current_slug === $topic['slug'] ) {
							$li_class = 'selected';
							$a_class  = 'selected active';
						}
						echo '<li class="bb-topic-selector-item ' . esc_attr( $li_class ) . '"><a href="' . esc_url( add_query_arg( 'bb-topic', $topic['slug'] ) ) . '" data-topic-id="' . esc_attr( $topic['topic_id'] ) . '" class="bb-topic-selector-link ' . esc_attr( $a_class ) . '">' . esc_html( $topic['name'] ) . '</a></li>';
					}
					?>
				</ul>
			</div>
			<?php
		}
	}

	/**
	 * Add the group activity topic data to the activity data.
	 *
	 * @since 2.7.40
	 *
	 * @param array $args The arguments array.
	 */
	public function bb_activity_get_edit_group_topic_data( $args ) {
		if ( 'groups' === $args['object'] ) {
			$topics = bb_get_group_activity_topics(
				array(
					'item_id'   => $args['group_id'],
					'item_type' => $args['object'],
					'fields'    => 'name,topic_id',
					'can_post'  => true,
				)
			);

			if ( ! empty( $topics ) ) {
				$args['topics']['topic_lists'] = $topics;
			}
		}

		return $args;
	}
}
