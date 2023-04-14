<?php
/**
 * BuddyBoss Groups Zoom.
 *
 * @package BuddyBoss\Groups\Zoom
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BP_Group_Zoom
 */
class BP_Zoom_Group {
	/**
	 * Your __construct() method will contain configuration options for
	 * your extension.
	 *
	 * @since 1.0.0
	 */
	public function __construct() {
		if ( ! bbp_pro_is_license_valid() || ! bp_is_active( 'groups' ) || ! bp_zoom_is_zoom_enabled() || ! bp_zoom_is_zoom_groups_enabled() ) {
			return false;
		}

		$this->includes();
		$this->setup_filters();
		$this->setup_actions();
	}

	/**
	 * Includes
	 *
	 * @since 1.0.7
	 */
	private function includes() {
		require bp_zoom_integration_path() . 'bp-zoom-group-functions.php';
	}

	/**
	 * Setup the group zoom class filters
	 *
	 * @since 1.0.0
	 */
	private function setup_filters() {
		add_filter( 'bp_nouveau_customizer_group_nav_items', array( $this, 'customizer_group_nav_items' ), 10, 2 );
	}

	/**
	 * Setup actions.
	 *
	 * @since 1.0.0
	 */
	public function setup_actions() {
		add_action( 'bp_setup_nav', array( $this, 'setup_nav' ), 100 );
		add_filter( 'document_title_parts', array( $this, 'bp_nouveau_group_zoom_set_page_title' ) );
		add_filter( 'pre_get_document_title', array( $this, 'bp_nouveau_group_zoom_set_title_tag' ), 999, 1 );

		add_action( 'bp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		// Adds a zoom metabox to the new BuddyBoss Group Admin UI.
		add_action( 'bp_groups_admin_meta_boxes', array( $this, 'group_admin_ui_edit_screen' ) );

		// Saves the zoom options if they come from the BuddyBoss Group Admin UI.
		add_action( 'bp_group_admin_edit_after', array( $this, 'edit_screen_save' ) );

		add_action( 'bp_zoom_meeting_add', array( $this, 'create_meeting_activity' ), 10, 2 );
		add_action( 'bp_zoom_webinar_add', array( $this, 'create_webinar_activity' ), 10, 2 );
		add_action( 'bp_zoom_meeting_add', array( $this, 'groups_notification_meeting_created' ), 20, 2 );
		add_action( 'bp_zoom_webinar_add', array( $this, 'groups_notification_webinar_created' ), 20, 2 );

		add_action( 'bp_groups_zoom_meeting_created_notification', array( $this, 'groups_format_create_meeting_notification' ), 10, 5 );
		add_action( 'bp_groups_zoom_meeting_notified_notification', array( $this, 'groups_format_notified_meeting_notification' ), 10, 5 );
		add_action( 'bp_groups_zoom_webinar_created_notification', array( $this, 'groups_format_create_webinar_notification' ), 10, 5 );
		add_action( 'bp_groups_zoom_webinar_notified_notification', array( $this, 'groups_format_notified_webinar_notification' ), 10, 5 );

		add_action( 'bp_get_request', array( $this, 'zoom_meeting_mark_notifications' ), 1 );
		add_action( 'bp_get_request', array( $this, 'zoom_webinar_mark_notifications' ), 1 );
		add_action( 'bp_zoom_meeting_deleted_meetings', array( $this, 'delete_meeting_notifications' ) );
		add_action( 'bp_zoom_webinar_deleted_webinars', array( $this, 'delete_webinar_notifications' ) );

		add_action( 'bp_zoom_meeting_mark_notifications_handler', array( $this, 'bb_mark_modern_meeting_notifications' ), 10, 5 );
		add_action( 'bp_zoom_webinar_mark_notifications_handler', array( $this, 'bb_mark_modern_webinar_notifications' ), 10, 5 );

		add_action( 'bp_activity_entry_content', array( $this, 'embed_meeting' ), 10 );
		add_action( 'bp_activity_entry_content', array( $this, 'embed_webinar' ), 10 );
		// Register the activity stream actions.
		add_action( 'bp_register_activity_actions', array( $this, 'register_activity_actions' ) );

		add_action( 'bp_init', array( $this, 'zoom_webhook' ), 10 );
		add_action( 'bp_init', array( $this, 'check_webinar_option' ), 10 );

		add_action( 'groups_delete_group', array( $this, 'delete_group_delete_all_meetings' ), 10 );
		add_action( 'groups_delete_group', array( $this, 'delete_group_delete_all_webinars' ), 10 );
	}

	/**
	 * Setup navigation for group zoom tabs.
	 *
	 * @since 1.0.0
	 */
	public function setup_nav() {
		// return if no group.
		if ( ! bp_is_group() ) {
			return;
		}

		$current_group = groups_get_current_group();
		$group_link    = bp_get_group_permalink( $current_group );
		$sub_nav       = array();

		// if current group has zoom enable then return.
		if ( bp_zoom_is_group_setup( $current_group->id ) ) {
			$sub_nav[] = array(
				'name'            => __( 'Zoom', 'buddyboss-pro' ),
				'slug'            => 'zoom',
				'parent_url'      => $group_link,
				'parent_slug'     => $current_group->slug,
				'screen_function' => array( $this, 'zoom_page' ),
				'item_css_id'     => 'zoom',
				'position'        => 100,
				'user_has_access' => $current_group->user_has_access,
				'no_access_url'   => $group_link,
			);

			$default_args = array(
				'parent_url'      => trailingslashit( $group_link . 'zoom' ),
				'parent_slug'     => $current_group->slug . '_zoom',
				'screen_function' => array( $this, 'zoom_page' ),
				'user_has_access' => $current_group->user_has_access,
				'no_access_url'   => $group_link,
			);

			$sub_nav[] = array_merge(
				array(
					'name'     => __( 'Upcoming Meetings', 'buddyboss-pro' ),
					'slug'     => 'meetings',
					'position' => 10,
				),
				$default_args
			);

			$sub_nav[] = array_merge(
				array(
					'name'     => __( 'Past Meetings', 'buddyboss-pro' ),
					'slug'     => 'past-meetings',
					'position' => 20,
				),
				$default_args
			);

			$webinar_enabled = groups_get_groupmeta( $current_group->id, 'bp-group-zoom-enable-webinar', true );

			if ( ! empty( $webinar_enabled ) ) {
				$sub_nav[] = array_merge(
					array(
						'name'     => __( 'Upcoming Webinars', 'buddyboss-pro' ),
						'slug'     => 'webinars',
						'position' => 40,
					),
					$default_args
				);

				$sub_nav[] = array_merge(
					array(
						'name'     => __( 'Past Webinars', 'buddyboss-pro' ),
						'slug'     => 'past-webinars',
						'position' => 50,
					),
					$default_args
				);
			}

			if ( bp_zoom_groups_can_user_manage_zoom( bp_loggedin_user_id(), $current_group->id ) ) {
				$sub_nav[] = array_merge(
					array(
						'name'     => __( 'Create Meeting', 'buddyboss-pro' ),
						'slug'     => 'create-meeting',
						'position' => 30,
					),
					$default_args
				);

				if ( ! empty( $webinar_enabled ) ) {
					$sub_nav[] = array_merge(
						array(
							'name'     => __( 'Create Webinars', 'buddyboss-pro' ),
							'slug'     => 'create-webinar',
							'position' => 60,
						),
						$default_args
					);
				}
			}
		}

		// If the user is a group admin, then show the group admin nav item.
		if ( bp_is_item_admin() ) {
			$admin_link = trailingslashit( $group_link . 'admin' );

			$sub_nav[] = array(
				'name'              => __( 'Zoom', 'buddyboss-pro' ),
				'slug'              => 'zoom',
				'position'          => 100,
				'parent_url'        => $admin_link,
				'parent_slug'       => $current_group->slug . '_manage',
				'screen_function'   => 'groups_screen_group_admin',
				'user_has_access'   => bp_is_item_admin(),
				'show_in_admin_bar' => true,
			);
		}

		foreach ( $sub_nav as $nav ) {
			bp_core_new_subnav_item( $nav, 'groups' );
		}

		// save edit screen options.
		if ( bp_is_groups_component() && bp_is_current_action( 'admin' ) && bp_is_action_variable( 'zoom', 0 ) ) {
			$this->edit_screen_save( $current_group->id );

			// Load zoom admin page.
			add_action( 'bp_screens', array( $this, 'zoom_admin_page' ) );
		}
	}

	/**
	 * Zoom page callback
	 *
	 * @since 1.0.0
	 */
	public function zoom_page() {
		$sync_meeting_done = filter_input( INPUT_GET, 'sync_meeting_done', FILTER_DEFAULT );

		// when sync completes.
		if ( ! empty( $sync_meeting_done ) ) {
			bp_core_add_message( __( 'Group meetings were successfully synced with Zoom.', 'buddyboss-pro' ), 'success' );
		}

		$sync_webinar_done = filter_input( INPUT_GET, 'sync_webinar_done', FILTER_DEFAULT );

		// when sync completes.
		if ( ! empty( $sync_webinar_done ) ) {
			bp_core_add_message( __( 'Group webinars were successfully synced with Zoom.', 'buddyboss-pro' ), 'success' );
		}

		// 404 if webinar is not enabled.
		if ( ! bp_zoom_groups_is_webinars_enabled( bp_get_current_group_id() ) && ( bp_zoom_is_webinars() || bp_zoom_is_past_webinars() || bp_zoom_is_single_webinar() || bp_zoom_is_create_webinar() ) ) {
			bp_do_404();

			return;
		}

		// if single meeting page and meeting does not exists return 404.
		if ( bp_zoom_is_single_meeting() && false === bp_zoom_get_current_meeting() ) {
			bp_do_404();

			return;
		}

		// if single webinar page and webinar does not exists return 404.
		if ( bp_zoom_is_single_webinar() && false === bp_zoom_get_current_webinar() ) {
			bp_do_404();

			return;
		}

		$group_id = bp_is_group() ? bp_get_current_group_id() : false;

		$zoom_web_meeting = filter_input( INPUT_GET, 'wm', FILTER_VALIDATE_INT );
		$meeting_id       = bb_pro_filter_input_string( INPUT_GET, 'mi' );

		// Check access before starting web meeting.
		if ( ! empty( $meeting_id ) && 1 === $zoom_web_meeting ) {
			$current_group = groups_get_current_group();

			// get meeting data.
			$meeting = BP_Zoom_Meeting::get_meeting_by_meeting_id( $meeting_id );

			if (
				empty( $meeting ) ||
				(
					! bp_current_user_can( 'bp_moderate' ) &&
					in_array( $current_group->status, array( 'private', 'hidden' ), true ) &&
					! groups_is_user_member( bp_loggedin_user_id(), $group_id ) &&
					! groups_is_user_admin( bp_loggedin_user_id(), $group_id ) &&
					! groups_is_user_mod( bp_loggedin_user_id(), $group_id )
				)
			) {
				bp_do_404();

				return;
			}

			add_action( 'wp_footer', 'bp_zoom_pro_add_zoom_web_meeting_append_div' );
		}

		$webinar_id = bb_pro_filter_input_string( INPUT_GET, 'wi' );

		// Check access before starting web meeting.
		if ( ! empty( $webinar_id ) && 1 === $zoom_web_meeting ) {
			$current_group = groups_get_current_group();

			// get webinar data.
			$webinar = BP_Zoom_Webinar::get_webinar_by_webinar_id( $webinar_id );

			if (
				empty( $webinar ) ||
				(
					! bp_current_user_can( 'bp_moderate' ) &&
					in_array( $current_group->status, array( 'private', 'hidden' ), true ) &&
					! groups_is_user_member( bp_loggedin_user_id(), $group_id ) &&
					! groups_is_user_admin( bp_loggedin_user_id(), $group_id ) &&
					! groups_is_user_mod( bp_loggedin_user_id(), $group_id )
				)
			) {
				bp_do_404();

				return;
			}

			add_action( 'wp_footer', 'bp_zoom_pro_add_zoom_web_meeting_append_div' );
		}

		$recording_id = filter_input( INPUT_GET, 'zoom-recording', FILTER_VALIDATE_INT );

		if ( ! empty( $group_id ) && ! empty( $recording_id ) && ( bp_zoom_is_meetings() || bp_zoom_is_webinars() ) ) {
			$current_group = groups_get_current_group();

			if (
				! bp_current_user_can( 'bp_moderate' ) &&
				in_array( $current_group->status, array( 'private', 'hidden' ), true ) &&
				! groups_is_user_member( bp_loggedin_user_id(), $group_id ) &&
				! groups_is_user_admin( bp_loggedin_user_id(), $group_id ) &&
				! groups_is_user_mod( bp_loggedin_user_id(), $group_id )
			) {
				bp_do_404();

				return;
			}

			// get recording data.
			$meeting_recordings = bp_zoom_recording_get( array(), array( 'id' => $recording_id ) );
			$webinar_recordings = bp_zoom_webinar_recording_get( array(), array( 'id' => $recording_id ) );

			// check if exists in the system and has meeting/webinar id.
			if ( empty( $meeting_recordings[0]->meeting_id ) && empty( $webinar_recordings[0]->webinar_id ) ) {
				bp_do_404();

				return;
			}

			// get meeting data.
			$meeting = BP_Zoom_Meeting::get_meeting_by_meeting_id( $meeting_recordings[0]->meeting_id );
			$webinar = BP_Zoom_Webinar::get_webinar_by_webinar_id( $webinar_recordings[0]->webinar_id );

			// check meeting exists.
			if ( empty( $meeting->id ) && empty( $webinar->id ) ) {
				bp_do_404();

				return;
			}

			// check current group is same as recording group.
			if ( (int) $meeting->group_id !== (int) $group_id && (int) $webinar->group_id !== (int) $group_id ) {
				bp_do_404();

				return;
			}

			if ( ! empty( $meeting_recordings[0]->details ) ) {
				$recording_file = json_decode( $meeting_recordings[0]->details );

				$download_url = filter_input( INPUT_GET, 'download', FILTER_VALIDATE_INT );

				// download url if download option true.
				if ( ! empty( $recording_file->download_url ) && ! empty( $download_url ) && 1 === $download_url ) {
					wp_redirect( $recording_file->download_url ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
					exit;
				}

				if ( ! empty( $recording_file->play_url ) ) {
					wp_redirect( $recording_file->play_url ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
					exit;
				}
			} elseif ( ! empty( $webinar_recordings[0]->details ) ) {
				$recording_file = json_decode( $webinar_recordings[0]->details );

				$download_url = filter_input( INPUT_GET, 'download', FILTER_VALIDATE_INT );

				// download url if download option true.
				if ( ! empty( $recording_file->download_url ) && ! empty( $download_url ) && 1 === $download_url ) {
					wp_redirect( $recording_file->download_url ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
					exit;
				}

				if ( ! empty( $recording_file->play_url ) ) {
					wp_redirect( $recording_file->play_url ); // phpcs:ignore WordPress.Security.SafeRedirect.wp_redirect_wp_redirect
					exit;
				}
			}

			bp_do_404();

			return;
		}

		// if edit meeting page and meeting does not exists return 404.
		if (
			( bp_zoom_is_edit_meeting() && false === bp_zoom_get_edit_meeting() )
			|| ( ! bp_zoom_groups_can_user_manage_zoom( bp_loggedin_user_id(), $group_id ) && bp_zoom_is_create_meeting() )
		) {
			bp_do_404();
			return;
		}

		// if edit webinar page and webinar does not exists return 404.
		if (
			( bp_zoom_is_edit_webinar() && false === bp_zoom_get_edit_webinar() )
			|| ( ! bp_zoom_groups_can_user_manage_zoom( bp_loggedin_user_id(), $group_id ) && bp_zoom_is_create_webinar() )
		) {
			bp_do_404();
			return;
		}

		if ( ( bp_zoom_is_groups_zoom() || bp_zoom_is_meetings() || bp_zoom_is_past_meetings() ) && ! bp_zoom_is_webinars() && ! bp_zoom_is_past_webinars() && ! bp_zoom_is_single_meeting() && ! bp_zoom_is_create_meeting() ) {
			$param = array(
				'per_page' => 1,
			);

			if ( 'past-meetings' === bp_action_variable( 0 ) ) {
				$param['from']  = wp_date( 'Y-m-d H:i:s', null, new DateTimeZone( 'UTC' ) );
				$param['since'] = false;
				$param['sort']  = 'DESC';
			}

			if ( bp_has_zoom_meetings( $param ) ) {
				while ( bp_zoom_meeting() ) {
					bp_the_zoom_meeting();

					$group_link   = bp_get_group_permalink( groups_get_group( bp_get_zoom_meeting_group_id() ) );
					$redirect_url = trailingslashit( $group_link . 'zoom/meetings/' . bp_get_zoom_meeting_id() );
					wp_safe_redirect( $redirect_url );
					exit;
				}
			}
		} elseif ( ( bp_zoom_is_webinars() || bp_zoom_is_past_webinars() ) && ! bp_zoom_is_single_webinar() && ! bp_zoom_is_create_webinar() ) {
			$param = array(
				'per_page' => 1,
			);

			if ( 'past-webinars' === bp_action_variable( 0 ) ) {
				$param['from']  = wp_date( 'Y-m-d H:i:s', null, new DateTimeZone( 'UTC' ) );
				$param['since'] = false;
				$param['sort']  = 'DESC';
			}

			if ( bp_has_zoom_webinars( $param ) ) {
				while ( bp_zoom_webinar() ) {
					bp_the_zoom_webinar();

					$group_link   = bp_get_group_permalink( groups_get_group( bp_get_zoom_webinar_group_id() ) );
					$redirect_url = trailingslashit( $group_link . 'zoom/webinars/' . bp_get_zoom_webinar_id() );
					wp_safe_redirect( $redirect_url );
					exit;
				}
			}
		}

		add_action( 'bp_template_content', array( $this, 'zoom_page_content' ) );
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'groups/single/home' ) );
	}

	/**
	 * Zoom admin page callback
	 *
	 * @since 1.0.0
	 */
	public function zoom_admin_page() {
		if ( 'zoom' !== bp_get_group_current_admin_tab() ) {
			return false;
		}

		if ( ! bp_is_item_admin() && ! bp_current_user_can( 'bp_moderate' ) ) {
			return false;
		}
		add_action( 'groups_custom_edit_steps', array( $this, 'edit_screen' ) );
		bp_core_load_template( apply_filters( 'bp_core_template_plugin', 'groups/single/home' ) );
	}

	/**
	 * Display zoom page content.
	 *
	 * @since 1.0.0
	 */
	public function zoom_page_content() {
		do_action( 'template_notices' );
		bp_get_template_part( 'groups/single/zoom' );
	}

	/**
	 * Enqueue scripts for zoom meeting pages.
	 *
	 * @since 1.0.0
	 */
	public function enqueue_scripts() {
		if ( ! bp_zoom_is_groups_zoom() ) {
			return;
		}
		wp_enqueue_style( 'jquery-datetimepicker' );
		wp_enqueue_script( 'jquery-datetimepicker' );
		wp_enqueue_script( 'bp-select2' );
		if ( wp_script_is( 'bp-select2-local', 'registered' ) ) {
			wp_enqueue_script( 'bp-select2-local' );
		}
		wp_enqueue_style( 'bp-select2' );
	}

	/**
	 * Adds a zoom metabox to BuddyBoss Group Admin UI
	 *
	 * @since 1.0.0
	 *
	 * @uses add_meta_box
	 */
	public function group_admin_ui_edit_screen() {
		add_meta_box(
			'bp_zoom_group_admin_ui_meta_box',
			__( 'Zoom Conference', 'buddyboss-pro' ),
			array( $this, 'group_admin_ui_display_metabox' ),
			get_current_screen()->id,
			'advanced',
			'high'
		);
	}

	/**
	 * Displays the zoom metabox in BuddyBoss Group Admin UI
	 *
	 * @param object $item (group object).
	 *
	 * @since 1.0.0
	 */
	public function group_admin_ui_display_metabox( $item ) {
		$this->edit_screen( $item );
	}

	/**
	 * Show zoom option form when editing a group
	 *
	 * @param object|bool $group (the group to edit if in Group Admin UI).
	 *
	 * @since 1.0.0
	 * @uses is_admin() To check if we're in the Group Admin UI
	 */
	public function edit_screen( $group = false ) {
		$group_id = empty( $group->id ) ? bp_get_new_group_id() : $group->id;

		if ( empty( $group->id ) ) {
			$group_id = bp_get_new_group_id();
		}

		if ( empty( $group_id ) ) {
			$group_id = bp_get_group_id();
		}

		if ( empty( $group_id ) ) {
			$group_id = $group->id;
		}

		$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		wp_enqueue_script( 'bp-zoom-meeting-common', bp_zoom_integration_url( '/assets/js/bp-zoom-meeting-common' . $min . '.js' ), array( 'jquery' ), bb_platform_pro()->version, true );
		wp_localize_script(
			'bp-zoom-meeting-common',
			'bpZoomMeetingCommonVars',
			array(
				'ajax_url' => admin_url( 'admin-ajax.php' ),
			)
		);

		// Should box be checked already?
		$checked       = bp_zoom_group_is_zoom_enabled( $group_id );
		$api_key       = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-key', true );
		$api_secret    = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-secret', true );
		$api_email     = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-email', true );
		$webhook_token = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-webhook-token', true );

		$notice_exists = get_transient( 'bb_group_zoom_notice_' . $group_id );
		?>

		<div class="bb-group-zoom-settings-container">

			<?php if ( ! empty( $notice_exists ) ) { ?>
				<div class="bp-messages-feedback">
					<div class="bp-feedback <?php echo esc_attr( $notice_exists['type'] ); ?>-notice">
						<span class="bp-icon" aria-hidden="true"></span>
						<p><?php echo esc_html( $notice_exists['message'] ); ?></p>
					</div>
				</div>
				<?php
				delete_transient( 'bb_group_zoom_notice_' . $group_id );
			}
			?>

			<h4 class="bb-section-title"><?php esc_html_e( 'Group Zoom Settings', 'buddyboss-pro' ); ?></h4>

			<fieldset>
				<legend class="screen-reader-text"><?php esc_html_e( 'Group Zoom Settings', 'buddyboss-pro' ); ?></legend>
				<?php if ( ! is_admin() ) : ?>
					<p class="bb-section-info"><?php esc_html_e( 'Connect this group to a Zoom account, to allow meetings to be scheduled from this group and synced with Zoom. Once enabled, enter your Zoom API Credentials below, using the "Setup Wizard" button to guide you step by step.', 'buddyboss-pro' ); ?></p>
				<?php else : ?>
					<p class="bb-section-info"><?php esc_html_e( 'Connect this group to a Zoom account, to allow meetings to be scheduled from this group and synced with Zoom. Once enabled, enter your Zoom API Credentials below.', 'buddyboss-pro' ); ?></p>
				<?php endif; ?>

				<div class="field-group">
					<p class="checkbox bp-checkbox-wrap bp-group-option-enable">
						<input type="checkbox" name="bp-edit-group-zoom" id="bp-edit-group-zoom" class="bs-styled-checkbox" value="1" <?php checked( $checked ); ?> />
						<label for="bp-edit-group-zoom"><span><?php esc_html_e( 'Yes, I want to connect this group to Zoom.', 'buddyboss-pro' ); ?></span></label>
					</p>
				</div>

			</fieldset>

			<div id="bp-group-zoom-settings-additional" class="group-settings-selections <?php echo ! $checked ? 'bp-hide' : ''; ?>">

				<hr class="bb-sep-line" />
				<h4 class="bb-section-title"><?php esc_html_e( 'Group Permissions', 'buddyboss-pro' ); ?></h4>

				<fieldset class="radio group-media">
					<legend class="screen-reader-text"><?php esc_html_e( 'Group Permissions', 'buddyboss-pro' ); ?></legend>
					<p class="group-setting-label" tabindex="0"><?php esc_html_e( 'Which members of this group are allowed to create, edit and delete Zoom meetings? The "Zoom Account Email" (below) will be assigned as the default host for every meeting in this group, regardless of who created the meeting.', 'buddyboss-pro' ); ?></p>

					<div class="bp-radio-wrap">
						<input type="radio" name="bp-group-zoom-manager" id="group-zoom-manager-members" class="bs-styled-radio" value="members"<?php bp_zoom_group_show_manager_setting( 'members', $group ); ?> />
						<label for="group-zoom-manager-members"><?php esc_html_e( 'All group members', 'buddyboss-pro' ); ?></label>
					</div>

					<div class="bp-radio-wrap">
						<input type="radio" name="bp-group-zoom-manager" id="group-zoom-manager-mods" class="bs-styled-radio" value="mods"<?php bp_zoom_group_show_manager_setting( 'mods', $group ); ?> />
						<label for="group-zoom-manager-mods"><?php esc_html_e( 'Organizers and Moderators only', 'buddyboss-pro' ); ?></label>
					</div>

					<div class="bp-radio-wrap">
						<input type="radio" name="bp-group-zoom-manager" id="group-zoom-manager-admins" class="bs-styled-radio" value="admins"<?php bp_zoom_group_show_manager_setting( 'admins', $group ); ?> />
						<label for="group-zoom-manager-admins"><?php esc_html_e( 'Organizers only', 'buddyboss-pro' ); ?></label>
					</div>
				</fieldset>

				<hr class="bb-sep-line" />
			</div>

			<div id="bp-group-zoom-settings" class="bp-group-zoom-settings <?php echo ! $checked ? 'bp-hide' : ''; ?>">

				<h4 class="bb-section-title"><?php esc_html_e( 'Zoom API Credentials', 'buddyboss-pro' ); ?></h4>
				<legend class="screen-reader-text"><?php esc_html_e( 'Zoom API Credentials', 'buddyboss-pro' ); ?></legend>
				<div class="bb-field-wrap">
					<label for="bp-group-zoom-api-key" class="group-setting-label"><?php esc_html_e( 'API Key', 'buddyboss-pro' ); ?>*</label>

					<div class="bp-input-wrap">
						<input <?php echo ! empty( $checked ) ? 'required' : ''; ?> type="text" name="bp-group-zoom-api-key" id="bp-group-zoom-api-key" class="zoom-group-instructions-main-input" value="<?php echo esc_attr( $api_key ); ?>"/>
					</div>
				</div>

				<div class="bb-field-wrap">
					<label for="bp-group-zoom-api-secret" class="group-setting-label"><?php esc_html_e( 'API Secret', 'buddyboss-pro' ); ?>*</label>

					<div class="bp-input-wrap">
						<input <?php echo ! empty( $checked ) ? 'required' : ''; ?> type="text" name="bp-group-zoom-api-secret" id="bp-group-zoom-api-secret" class="zoom-group-instructions-main-input" value="<?php echo esc_attr( $api_secret ); ?>"/>
					</div>
				</div>

				<div class="bb-field-wrap">
					<label for="bp-group-zoom-api-email" class="group-setting-label"><?php esc_html_e( 'Zoom Account Email', 'buddyboss-pro' ); ?>*</label>

					<div class="bp-input-wrap">
						<input <?php echo ! empty( $checked ) ? 'required' : ''; ?> type="text" name="bp-group-zoom-api-email" id="bp-group-zoom-api-email" class="zoom-group-instructions-main-input" value="<?php echo esc_attr( $api_email ); ?>"/>
					</div>
				</div>

				<div class="bb-field-wrap">
					<label for="bp-group-zoom-api-webhook-token" class="group-setting-label"><?php esc_html_e( 'Security Token', 'buddyboss-pro' ); ?></label>

					<div class="bp-input-wrap">
						<input type="text" name="bp-group-zoom-api-webhook-token" id="bp-group-zoom-api-webhook-token" class="zoom-group-instructions-main-input" value="<?php echo esc_attr( $webhook_token ); ?>"/>
						<div class="bb-description-info">
							<span class="bb-url-text"><?php echo esc_url( bp_get_groups_directory_permalink() . '?zoom_webhook=1&group_id=' . $group_id ); ?></span>
							<a href="#" id="copy-webhook-link" class="copy-webhook-link" data-balloon-pos="down" data-balloon="<?php esc_html_e( 'Copy', 'buddyboss-pro' ); ?>" data-copied="<?php esc_html_e( 'Copied', 'buddyboss-pro' ); ?>" data-webhook-link="<?php echo esc_url( bp_get_groups_directory_permalink() . '?zoom_webhook=1&group_id=' . $group_id ); ?>">
								<span class="bb-icon-l bb-icon-duplicate"></span>
							</a>
						</div>
					</div>
				</div>
				<hr class="bb-sep-line" />
			</div>

			<div class="bp-zoom-group-button-wrap">
				<?php if ( ! empty( $checked ) && ! empty( $api_key ) && ! empty( $api_secret ) && ! empty( $api_email ) ) { ?>
					<a class="bp-zoom-group-check-connection" href="#" id="bp-zoom-group-check-connection">
						<i class="bb-icon-l bb-icon-radio"></i>
						<span><?php esc_html_e( 'Check Connection', 'buddyboss-pro' ); ?></span>
					</a>
				<?php } ?>

				<?php if ( ! is_admin() ) : ?>
					<a href="#bp-zoom-group-show-instructions-popup-<?php echo esc_attr( $group_id ); ?>" id="bp-zoom-group-show-instructions" class="button outline show-zoom-instructions
					<?php
					if ( empty( $checked ) ) {
						echo 'bp-hide'; }
					?>
					">
						<?php esc_html_e( 'Setup Wizard', 'buddyboss-pro' ); ?>
					</a>
					<div id="bp-zoom-group-show-instructions-popup-<?php echo esc_attr( $group_id ); ?>" class="bzm-white-popup bp-zoom-group-show-instructions mfp-hide">
						<header class="bb-zm-model-header"><?php esc_html_e( 'Connect a Zoom Account', 'buddyboss-pro' ); ?></header>

						<div class="bp-step-nav-main">

							<div class="bp-step-nav">
								<ul>
									<li class="selected"><a href="#step-1"><?php esc_html_e( 'Zoom Login', 'buddyboss-pro' ); ?></a></li>
									<li><a href="#step-2"><?php esc_html_e( 'Create App', 'buddyboss-pro' ); ?></a></li>
									<li><a href="#step-3"><?php esc_html_e( 'App Credentials', 'buddyboss-pro' ); ?></a></li>
									<li><a href="#step-4"><?php esc_html_e( 'Security Token', 'buddyboss-pro' ); ?></a></li>
									<li><a href="#step-5"><?php esc_html_e( 'Finish', 'buddyboss-pro' ); ?></a></li>
								</ul>
							</div> <!-- .bp-step-nav -->

							<div class="bp-step-blocks">

								<div class="bp-step-block selected" id="step-1">
									<div id="zoom-instruction-container">
										<p><?php esc_html_e( 'To use Zoom, we will need you to create an "app" in your Zoom account and connect it to this group so we can sync meeting data with Zoom. This should only take a few minutes if you already have a Zoom account. Note that cloud recordings and alternate hosts will only work if you have a "Pro" or "Business" Zoom account.', 'buddyboss-pro' ); ?></p>
										<?php /* translators: %s is buddyboss marketplace link. */ ?>
										<p><?php printf( esc_html__( 'Start by going to the %s and clicking the "Sign In" link in the titlebar. You can sign in using your existing Zoom credentials. If you do not yet have a Zoom account, just click the "Sign Up" link in the titlebar. Once you have successfully signed into Zoom App Marketplace you can move to the next step.', 'buddyboss-pro' ), '<a href="https://marketplace.zoom.us/" target="_blank">' . esc_html__( 'Zoom App Marketplace', 'buddyboss-pro' ) . '</a>' ); ?></p>
										<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-sign_in.png' ) ); ?>" />
									</div>
								</div>

								<div class="bp-step-block" id="step-2">
									<div id="zoom-instruction-container">
										<?php /* translators: %s is build app link in zoom. */ ?>
										<p><?php printf( esc_html__( 'Once you are signed into Zoom App Marketplace, you need to %s. You can always find the Build App link by going to "Develop" &#8594; "Build App" from the titlebar.', 'buddyboss-pro' ), '<a href="https://marketplace.zoom.us/develop/create" target="_blank">' . esc_html__( 'build an app', 'buddyboss-pro' ) . '</a>' ); ?></p>
										<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-build_app.png' ) ); ?>" />
										<p><?php esc_html_e( 'On the next page, select the first option "JWT" as the app type and click the "Create" button. If you see the message "Your account already has JWT credentials" you can use the existing app. In that case, click the "View here" link to modify the existing JWT app.', 'buddyboss-pro' ); ?></p>
										<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-app_type.png' ) ); ?>" />
										<p><?php esc_html_e( 'After clicking "Create App" you will get a popup asking you to enter an App Name. Enter any name that will remind you the app is being used for this website. Then click the "Create" button.', 'buddyboss-pro' ); ?></p>
										<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-app_name.png' ) ); ?>" />
										<p><?php esc_html_e( 'After clicking "Create" you will be redirected to a form asking for some basic personal information. Fill out all required fields and click the "Continue" button. Once you see "App Credentials" move to the next step.', 'buddyboss-pro' ); ?></p>
									</div>
								</div>

								<div class="bp-step-block" id="step-3">
									<div id="zoom-instruction-container">
										<?php /* translators: %1$s - API Key, %2$s - API Secret, %3$s - API Email */ ?>
										<p><?php printf( esc_html__( 'Once you get to the "App Credentials" page, copy the %1$s and %2$s and paste them into the fields in the form below. Then you will need to decide which of the Zoom users in your account should be the default host for all meetings in this group. Enter their email address into the %3$s field below. The email must exist as a Host in your Zoom account.', 'buddyboss-pro' ), '<strong>' . esc_html__( 'API Key', 'buddyboss-pro' ) . '</strong>', '<strong>' . esc_html__( 'API Secret', 'buddyboss-pro' ) . '</strong>', '<strong>' . esc_html__( 'Zoom Account Email', 'buddyboss-pro' ) . '</strong>' ); ?></p>
										<div class="bb-group-zoom-settings-container">
											<div class="bb-field-wrap">
												<label for="bp-group-zoom-api-key-popup" class="group-setting-label"><?php esc_html_e( 'API Key', 'buddyboss-pro' ); ?>*</label>
												<div class="bp-input-wrap">
													<input type="text" name="bp-group-zoom-api-key-popup" class="zoom-group-instructions-cloned-input" value="<?php echo esc_attr( $api_key ); ?>" />
												</div>
											</div>

											<div class="bb-field-wrap">
												<label for="bp-group-zoom-api-secret-popup" class="group-setting-label"><?php esc_html_e( 'API Secret', 'buddyboss-pro' ); ?>*</label>
												<div class="bp-input-wrap">
													<input type="text" name="bp-group-zoom-api-secret-popup" class="zoom-group-instructions-cloned-input" value="<?php echo esc_attr( $api_secret ); ?>" />
												</div>
											</div>

											<div class="bb-field-wrap">
												<label for="bp-group-zoom-api-email-popup" class="group-setting-label"><?php esc_html_e( 'Zoom Account Email', 'buddyboss-pro' ); ?>*</label>
												<div class="bp-input-wrap">
													<input type="text" name="bp-group-zoom-api-email-popup" class="zoom-group-instructions-cloned-input" value="<?php echo esc_attr( $api_email ); ?>" />
												</div>
											</div>

										</div><!-- .bb-group-zoom-settings-container -->

									</div>
								</div>

								<div class="bp-step-block" id="step-4">
									<div id="zoom-instruction-container">
										<p><?php esc_html_e( 'Once you have entered the API Key, API Secret, and Zoom Account Email, continue to the "Feature" tab. Enable "Event Subscriptions" and then click "Add new event subscription". This step is necessary to allow meeting updates from Zoom to automatically sync back into your group. Note that within the group on this site, you can also click the "Sync" button at any time to force a manual sync.', 'buddyboss-pro' ); ?></p>
										<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-event_subscription.png' ) ); ?>" />
										<p>
										<?php
										printf(
											/* translators: %1$s - icon html */
											esc_html__( 'For "Subscription Name" you can again enter any name you want. Click the %1$s Copy Link button below to copy a special link, and then paste that link back into Zoom in the field titled "Event notification endpoint URL".', 'buddyboss-pro' ),
											'<span class="bb-icon-l bb-icon-duplicate"></span>'
										);
										?>
											</p>
										<p><a href="#" class="copy-webhook-link button small outline" data-text="<?php esc_attr_e( 'Copy Link', 'buddyboss-pro' ); ?>" data-copied="<?php esc_attr_e( 'Copied', 'buddyboss-pro' ); ?>" data-webhook-link="<?php echo esc_url( bp_get_groups_directory_permalink() . '?zoom_webhook=1&group_id=' . $group_id ); ?>">
												<span class="bb-icon-l bb-icon-duplicate"></span>&nbsp;<?php esc_html_e( 'Copy Link', 'buddyboss-pro' ); ?>
											</a></p>
										<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-event_notification.png' ) ); ?>" />
										<?php /* translators: %s is options and already having translation another string. */ ?>
										<p><?php printf( esc_html__( 'Next, click the "Add events" button. In the popup, make sure to check the following options: %s', 'buddyboss-pro' ), '<strong>' . esc_html__( 'Start Meeting, End Meeting, Meeting has been updated, Meeting has been deleted.', 'buddyboss-pro' ) . '</strong>' ); ?></p>
										<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-events_1.png' ) ); ?>" />
										<?php /* translators: %s is options and already having translation another string. */ ?>
										<p><?php printf( esc_html__( 'Make sure to check: %s', 'buddyboss-pro' ), '<strong>' . esc_html__( 'All Recordings have completed.', 'buddyboss-pro' ) . '</strong>' ); ?></p>
										<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-events_2.png' ) ); ?>" />
										<?php /* translators: %s is options and already having translation another string. */ ?>
										<p><?php printf( esc_html__( 'Make sure to check the following options: %s', 'buddyboss-pro' ), '<strong>' . esc_html__( 'Start Webinar, End Webinar, Webinar has been updated, Webinar has been deleted.', 'buddyboss-pro' ) . '</strong>' ); ?></p>
										<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-events_3.png' ) ); ?>" />
										<p><?php esc_html_e( 'Click "Done" to close the popup. In the "Event Subscriptions" box, click "Save".', 'buddyboss-pro' ); ?></p>
										<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-event_save.png' ) ); ?>" />
										<p><?php esc_html_e( 'You should now see a "Security Token" created at the top of the page. Click "Copy" and then paste the token into the Security Token field at the bottom of this form. You\'re almost done!', 'buddyboss-pro' ); ?></p>
										<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-token.png' ) ); ?>" />

										<div class="bb-group-zoom-settings-container">
											<div class="bb-field-wrap">
												<label for="bp-group-zoom-api-webhook-token-popup" class="group-setting-label"><?php esc_html_e( 'Security Token', 'buddyboss-pro' ); ?></label>
												<div class="bp-input-wrap">
													<input type="text" name="bp-group-zoom-api-webhook-token-popup" class="zoom-group-instructions-cloned-input" value="<?php echo esc_attr( $webhook_token ); ?>">
												</div>
											</div>
										</div>
									</div>
								</div>

								<div class="bp-step-block" id="step-5">
									<div id="zoom-instruction-container">
										<p><?php esc_html_e( 'Now you can click "Continue" back at Zoom. You should see a message that "Your app is activated on the account". At this point we are done with the Zoom website.', 'buddyboss-pro' ); ?></p>
										<img src="<?php echo esc_url( bp_zoom_integration_url( '/assets/images/wizard-activated.png' ) ); ?>" />
										<p><?php esc_html_e( 'Make sure to click the "Save" button on this tab to save the data you entered. Then click the "Check Connection" button on the page to confirm the API was successfully connected. If everything worked you should see a new "Zoom" tab in your group, where you can start scheduling meetings! ', 'buddyboss-pro' ); ?></p>
									</div>
								</div>

							</div> <!-- .bp-step-blocks -->

							<div class="bp-step-actions">
								<span class="bp-step-prev button small outline" style="display: none;"><i class="bb-icon-l bb-icon-angle-left"></i>&nbsp;<?php esc_html_e( 'Previous', 'buddyboss-pro' ); ?></span>
								<span class="bp-step-next button small outline"><?php esc_html_e( 'Next', 'buddyboss-pro' ); ?>&nbsp;<i class="bb-icon-l bb-icon-angle-right"></i></span>

								<span class="save-settings button small"><?php esc_html_e( 'Save', 'buddyboss-pro' ); ?></span>

							</div> <!-- .bp-step-actions -->

						</div> <!-- .bp-step-nav-main -->

					</div>

					<button type="submit" class="bb-save-settings"><?php esc_html_e( 'Save Settings', 'buddyboss-pro' ); ?></button>
				<?php else : ?>
					<p>
						<a class="button" href="
						<?php
						echo esc_url(
							bp_get_admin_url(
								add_query_arg(
									array(
										'page'    => 'bp-help',
										'article' => 88334,
									),
									'admin.php'
								)
							)
						);
						?>
						"><?php esc_html_e( 'View Tutorial', 'buddyboss-pro' ); ?></a>
					</p>
				<?php endif; ?>
			</div>

			<?php

			// Verify intent.
			if ( is_admin() ) {
				?>
				<input type="hidden" id="bp-zoom-group-id" value="<?php echo esc_attr( $group_id ); ?>" />
				<?php
				wp_nonce_field( 'groups_edit_save_zoom', 'zoom_group_admin_ui' );
			} else {
				wp_nonce_field( 'groups_edit_save_zoom' );
			}
			?>
		</div>
		<?php
	}

	/**
	 * Save the Group Zoom data on edit
	 *
	 * @param int $group_id (to handle Group Admin UI hook bp_group_admin_edit_after ).
	 *
	 * @since 1.0.0
	 */
	public function edit_screen_save( $group_id = 0 ) {

		// Bail if not a POST action.
		if ( ! bp_is_post_request() ) {
			return;
		}

		$nonce = bb_pro_filter_input_string( INPUT_POST, '_wpnonce' );

		// Admin Nonce check.
		if ( is_admin() ) {
			check_admin_referer( 'groups_edit_save_zoom', 'zoom_group_admin_ui' );

			// Theme-side Nonce check.
		} elseif ( empty( $nonce ) || ( ! empty( $nonce ) && ! wp_verify_nonce( $nonce, 'groups_edit_save_zoom' ) ) ) {
			return;
		}

		global $wpdb, $bp;

		$edit_zoom     = filter_input( INPUT_POST, 'bp-edit-group-zoom', FILTER_VALIDATE_INT );
		$manager       = bb_pro_filter_input_string( INPUT_POST, 'bp-group-zoom-manager' );
		$api_key       = bb_pro_filter_input_string( INPUT_POST, 'bp-group-zoom-api-key' );
		$api_secret    = bb_pro_filter_input_string( INPUT_POST, 'bp-group-zoom-api-secret' );
		$api_email     = filter_input( INPUT_POST, 'bp-group-zoom-api-email', FILTER_VALIDATE_EMAIL );
		$webhook_token = bb_pro_filter_input_string( INPUT_POST, 'bp-group-zoom-api-webhook-token' );

		$edit_zoom = ! empty( $edit_zoom ) ? true : false;
		$manager   = ! empty( $manager ) ? $manager : bp_zoom_group_get_manager( $group_id );
		$group_id  = ! empty( $group_id ) ? $group_id : bp_get_current_group_id();

		groups_update_groupmeta( $group_id, 'bp-group-zoom', $edit_zoom );
		groups_update_groupmeta( $group_id, 'bp-group-zoom-manager', $manager );

		$old_api_email = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-email', true );

		if ( $edit_zoom ) {
			if ( $api_email ) {
				bp_zoom_conference()->zoom_api_key    = $api_key;
				bp_zoom_conference()->zoom_api_secret = $api_secret;

				$user_info = bp_zoom_conference()->get_user_info( $api_email );

				if ( 200 === $user_info['code'] && ! empty( $user_info['response'] ) ) {
					groups_update_groupmeta( $group_id, 'bp-group-zoom-api-email', $api_email );
					groups_update_groupmeta( $group_id, 'bp-group-zoom-api-key', $api_key );
					groups_update_groupmeta( $group_id, 'bp-group-zoom-api-secret', $api_secret );
					groups_update_groupmeta( $group_id, 'bp-group-zoom-api-webhook-token', $webhook_token );
					groups_update_groupmeta( $group_id, 'bp-group-zoom-api-host', $user_info['response']->id );
					groups_update_groupmeta( $group_id, 'bp-group-zoom-api-host-type', $user_info['response']->type );
					groups_update_groupmeta( $group_id, 'bp-group-zoom-api-host-user', wp_json_encode( $user_info['response'] ) );

					if ( $old_api_email !== $api_email ) {
						// Hide old host meetings.
						$wpdb->query( $wpdb->prepare( "UPDATE {$bp->table_prefix}bp_zoom_meetings SET hide_sitewide = %d WHERE group_id = %d AND host_id = %s", '1', $group_id, $old_api_email ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching

						// Un-hide current host meetings.
						$wpdb->query( $wpdb->prepare( "UPDATE {$bp->table_prefix}bp_zoom_meetings SET hide_sitewide = %d WHERE group_id = %d AND host_id = %s", '0', $group_id, $api_email ) ); // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
					}

					// Get user settings of host user.
					$user_settings = bp_zoom_conference()->get_user_settings( $user_info['response']->id );

					// Save user settings into group meta.
					if ( 200 === $user_settings['code'] && ! empty( $user_settings['response'] ) ) {
						groups_update_groupmeta( $group_id, 'bp-group-zoom-api-host-user-settings', wp_json_encode( $user_settings['response'] ) );

						if ( isset( $user_settings['response']->feature->webinar ) && true === $user_settings['response']->feature->webinar ) {
							groups_update_groupmeta( $group_id, 'bp-group-zoom-enable-webinar', true );
						} else {
							groups_delete_groupmeta( $group_id, 'bp-group-zoom-enable-webinar' );
						}
					}

					$success_message = __( 'Group Zoom settings were successfully updated.', 'buddyboss-pro' );
					if ( ! is_admin() ) {
						bp_core_add_message( $success_message, 'success' );
					} else {
						set_transient(
							'bb_group_zoom_notice_' . $group_id,
							array(
								'message' => $success_message,
								'type'    => 'success',
							),
							30
						);
					}
				} else {

					groups_update_groupmeta( $group_id, 'bp-group-zoom-api-email', $api_email );
					groups_update_groupmeta( $group_id, 'bp-group-zoom-api-key', $api_key );
					groups_update_groupmeta( $group_id, 'bp-group-zoom-api-secret', $api_secret );
					groups_update_groupmeta( $group_id, 'bp-group-zoom-api-webhook-token', $webhook_token );
					groups_delete_groupmeta( $group_id, 'bp-group-zoom-api-host' );
					groups_delete_groupmeta( $group_id, 'bp-group-zoom-api-host-type' );
					groups_delete_groupmeta( $group_id, 'bp-group-zoom-api-host-user' );
					groups_delete_groupmeta( $group_id, 'bp-group-zoom-api-host-user-settings' );
					groups_delete_groupmeta( $group_id, 'bp-group-zoom-enable-webinar' );

					$error_message = __( 'Invalid Credentials. Please enter valid key, secret key or account email.', 'buddyboss-pro' );
					if ( ! is_admin() ) {
						bp_core_add_message( $error_message, 'error' );
					} else {
						set_transient(
							'bb_group_zoom_notice_' . $group_id,
							array(
								'message' => $error_message,
								'type'    => 'error',
							),
							30
						);
					}
				}
			} else {

				groups_update_groupmeta( $group_id, 'bp-group-zoom-api-email', $api_email );
				groups_update_groupmeta( $group_id, 'bp-group-zoom-api-key', $api_key );
				groups_update_groupmeta( $group_id, 'bp-group-zoom-api-secret', $api_secret );
				groups_update_groupmeta( $group_id, 'bp-group-zoom-api-webhook-token', $webhook_token );
				groups_delete_groupmeta( $group_id, 'bp-group-zoom-api-host' );
				groups_delete_groupmeta( $group_id, 'bp-group-zoom-api-host-type' );
				groups_delete_groupmeta( $group_id, 'bp-group-zoom-api-host-user' );
				groups_delete_groupmeta( $group_id, 'bp-group-zoom-api-host-user-settings' );
				groups_delete_groupmeta( $group_id, 'bp-group-zoom-enable-webinar' );

				$error_message = __( 'There was an error updating group Zoom API settings. Please try again.', 'buddyboss-pro' );
				if ( ! is_admin() ) {
					bp_core_add_message( $error_message, 'error' );
				} else {
					set_transient(
						'bb_group_zoom_notice_' . $group_id,
						array(
							'message' => $error_message,
							'type'    => 'error',
						),
						30
					);
				}
			}
		} else {
			$success_message = __( 'Group Zoom settings were successfully updated.', 'buddyboss-pro' );
			if ( ! is_admin() ) {
				bp_core_add_message( $success_message, 'success' );
			} else {
				set_transient(
					'bb_group_zoom_notice_' . $group_id,
					array(
						'message' => $success_message,
						'type'    => 'success',
					),
					30
				);
			}
		}

		/**
		 * Add action that fire before user redirect
		 *
		 * @Since 1.0.0
		 *
		 * @param int $group_id Current group id
		 */
		do_action( 'bp_group_admin_after_edit_screen_save', $group_id );

		// Redirect after save when not in admin.
		if ( ! is_admin() ) {
			bp_core_redirect( trailingslashit( bp_get_group_permalink( buddypress()->groups->current_group ) . '/admin/zoom' ) );
		}
	}

	/**
	 * Check webinar option.
	 *
	 * @since 1.0.9
	 */
	public function check_webinar_option() {
		$group_id = false;
		if ( bp_zoom_is_groups_zoom() ) {
			$group_id = bp_get_current_group_id();
		}

		if ( empty( $group_id ) ) {
			return;
		}

		$webinar_checked = groups_get_groupmeta( $group_id, 'bp-group-zoom-webinar-checked', true );

		if ( ! empty( $webinar_checked ) ) {
			return;
		}

		$api_key       = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-key', true );
		$api_secret    = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-secret', true );
		$api_host_user = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-host-user', true );

		bp_zoom_conference()->zoom_api_key    = $api_key;
		bp_zoom_conference()->zoom_api_secret = $api_secret;

		if ( ! empty( $api_host_user ) ) {
			$api_host_user = json_decode( $api_host_user );

			// Get user settings of host user.
			$user_settings = bp_zoom_conference()->get_user_settings( $api_host_user->id );

			// Save user settings into group meta.
			if ( 200 === $user_settings['code'] && ! empty( $user_settings['response'] ) ) {
				groups_update_groupmeta( $group_id, 'bp-group-zoom-api-host-user-settings', wp_json_encode( $user_settings['response'] ) );

				if ( isset( $user_settings['response']->feature->webinar ) && true === $user_settings['response']->feature->webinar ) {
					groups_update_groupmeta( $group_id, 'bp-group-zoom-enable-webinar', true );
				} else {
					groups_delete_groupmeta( $group_id, 'bp-group-zoom-enable-webinar' );
				}
			}
			groups_update_groupmeta( $group_id, 'bp-group-zoom-webinar-checked', true );
		}
	}

	/**
	 * Register our activity actions with BuddyBoss
	 *
	 * @since 1.0.0
	 * @uses bp_activity_set_action()
	 */
	public function register_activity_actions() {
		// Group activity stream items.
		bp_activity_set_action(
			buddypress()->groups->id,
			'zoom_meeting_create',
			esc_html__( 'New Zoom meeting', 'buddyboss-pro' ),
			array(
				$this,
				'meeting_activity_action_callback',
			)
		);

		// Group activity notify stream items.
		bp_activity_set_action(
			buddypress()->groups->id,
			'zoom_meeting_notify',
			esc_html__( 'New Zoom meeting', 'buddyboss-pro' ),
			array(
				$this,
				'meeting_activity_action_callback',
			)
		);

		// Group webinar activity stream items.
		bp_activity_set_action(
			buddypress()->groups->id,
			'zoom_webinar_create',
			esc_html__( 'New Zoom webinar', 'buddyboss-pro' ),
			array(
				$this,
				'webinar_activity_action_callback',
			)
		);

		// Group webinar activity notify stream items.
		bp_activity_set_action(
			buddypress()->groups->id,
			'zoom_webinar_notify',
			esc_html__( 'New Zoom webinar', 'buddyboss-pro' ),
			array(
				$this,
				'webinar_activity_action_callback',
			)
		);
	}

	/**
	 * Zoom meeting activity action.
	 *
	 * @param string $action Action activity.
	 * @param object $activity Activity object.
	 *
	 * @return string
	 * @since 1.0.0
	 */
	public function meeting_activity_action_callback( $action, $activity ) {
		if ( ( 'zoom_meeting_create' === $activity->type || 'zoom_meeting_notify' === $activity->type ) && buddypress()->groups->id === $activity->component && ! bp_zoom_is_group_setup( $activity->item_id ) ) {
			return $action;
		}

		$user_id    = $activity->user_id;
		$group_id   = $activity->item_id;
		$meeting_id = $activity->secondary_item_id;

		$meeting = new BP_Zoom_Meeting( $meeting_id );

		if ( empty( $meeting->id ) ) {
			return $action;
		}

		// User link.
		$user_link = bp_core_get_userlink( $user_id );

		// Meeting.
		$meeting_permalink = bp_get_zoom_meeting_url( $group_id, $meeting_id );
		$meeting_title     = $meeting->title;
		$meeting_link      = '<a href="' . $meeting_permalink . '">' . $meeting_title . '</a>';

		$group      = groups_get_group( $group_id );
		$group_link = bp_get_group_link( $group );

		$activity_action = sprintf(
			/* translators: %1$s - user link, %2$s - meeting link., %3$s - group link.*/
			esc_html__( '%1$s scheduled a Zoom meeting %2$s in the group %3$s', 'buddyboss-pro' ),
			$user_link,
			$meeting_link,
			$group_link
		);

		if ( 'zoom_meeting_notify' === $activity->type ) {
			$activity_action = sprintf(
				/* translators: %1$s - user link, %2$s - meeting link., %3$s - group link.*/
				esc_html__( '%1$s scheduled Zoom meeting %2$s starting soon in the group %3$s', 'buddyboss-pro' ),
				$user_link,
				$meeting_link,
				$group_link
			);
		}

		return $activity_action;
	}

	/**
	 * Zoom webinar activity action.
	 *
	 * @param string $action Action activity.
	 * @param object $activity Activity object.
	 *
	 * @return string
	 * @since 1.0.9
	 */
	public function webinar_activity_action_callback( $action, $activity ) {
		if ( ( 'zoom_webinar_create' === $activity->type || 'zoom_webinar_notify' === $activity->type ) && buddypress()->groups->id === $activity->component && ! bp_zoom_is_group_setup( $activity->item_id ) ) {
			return $action;
		}

		$user_id    = $activity->user_id;
		$group_id   = $activity->item_id;
		$webinar_id = $activity->secondary_item_id;

		$webinar = new BP_Zoom_Webinar( $webinar_id );

		if ( empty( $webinar->id ) ) {
			return $action;
		}

		// User link.
		$user_link = bp_core_get_userlink( $user_id );

		// Webinar.
		$webinar_permalink = bp_get_zoom_meeting_url( $group_id, $webinar_id );
		$webinar_title     = $webinar->title;
		$webinar_link      = '<a href="' . $webinar_permalink . '">' . $webinar_title . '</a>';

		$group      = groups_get_group( $group_id );
		$group_link = bp_get_group_link( $group );

		$activity_action = sprintf(
		/* translators: %1$s - user link, %2$s - group link. */
			esc_html__( '%1$s scheduled a Zoom webinar %2$s in the group %3$s', 'buddyboss-pro' ),
			$user_link,
			$webinar_link,
			$group_link
		);

		if ( 'zoom_webinar_notify' === $activity->type ) {
			$activity_action = sprintf(
			/* translators: %1$s - user link, %2$s - webinar link., %3$s - group link.*/
				esc_html__( '%1$s - Zoom webinar %2$s is starting soon in the group %3$s', 'buddyboss-pro' ),
				$user_link,
				$webinar_link,
				$group_link
			);
		}

		return $activity_action;
	}

	/**
	 * Create activity for meeting.
	 *
	 * @param object $meeting Meeting object.
	 * @param array  $args Arguments.
	 *
	 * @since 1.0.9
	 */
	public function create_meeting_activity( $meeting, $args ) {
		// Create activity for meeting and check if no occurrence activity is created in this code.
		if ( bp_is_active( 'activity' ) && 'meeting' === $meeting->zoom_type && ! empty( $meeting ) && ! empty( $meeting->group_id ) && empty( $meeting->parent ) && empty( $args['id'] ) ) {

			// Create activity.
			bp_zoom_groups_create_meeting_activity( $meeting );
		}
	}

	/**
	 * Create activity for webinar.
	 *
	 * @param object $webinar Webinar object.
	 * @param array  $args Arguments.
	 *
	 * @since 1.0.9
	 */
	public function create_webinar_activity( $webinar, $args ) {
		// Create activity for meeting and check if no occurrence activity is created in this code.
		if ( bp_is_active( 'activity' ) && 'webinar' === $webinar->zoom_type && ! empty( $webinar ) && ! empty( $webinar->group_id ) && empty( $webinar->parent ) && empty( $args['id'] ) ) {

			// Create activity.
			bp_zoom_groups_create_webinar_activity( $webinar );
		}
	}

	/**
	 * Return activity meeting embed HTML
	 *
	 * @return false|string|void
	 * @since 1.0.0
	 */
	public function embed_meeting() {
		if ( ( 'zoom_meeting_create' === bp_get_activity_type() || 'zoom_meeting_notify' === bp_get_activity_type() ) && buddypress()->groups->id === bp_get_activity_object_name() && ! bp_zoom_is_group_setup( bp_get_activity_item_id() ) ) {
			return;
		}

		$meeting_id = bp_activity_get_meta( bp_get_activity_id(), 'bp_meeting_id', true );

		if ( empty( $meeting_id ) ) {
			return;
		}

		$meeting = new BP_Zoom_Meeting( $meeting_id );

		if ( empty( $meeting->id ) ) {
			return;
		}

		$args = array(
			'include' => $meeting_id,
			'from'    => false,
			'since'   => false,
		);

		if ( true === (bool) $meeting->recurring && true === (bool) $meeting->hide_sitewide ) {
			$args['hide_sitewide'] = true;
		}

		if ( bp_has_zoom_meetings( $args ) ) {
			while ( bp_zoom_meeting() ) {
				bp_the_zoom_meeting();

				bp_get_template_part( 'zoom/activity-meeting-entry' );
			}
		}
	}

	/**
	 * Return activity webinar embed HTML
	 *
	 * @return false|string|void
	 * @since 1.0.9
	 */
	public function embed_webinar() {
		if ( ( 'zoom_webinar_create' === bp_get_activity_type() || 'zoom_webinar_notify' === bp_get_activity_type() ) && buddypress()->groups->id === bp_get_activity_object_name() && ! bp_zoom_is_group_setup( bp_get_activity_item_id() ) ) {
			return;
		}

		$webinar_id = bp_activity_get_meta( bp_get_activity_id(), 'bp_webinar_id', true );

		if ( empty( $webinar_id ) ) {
			return;
		}

		$webinar = new BP_Zoom_Webinar( $webinar_id );

		if ( empty( $webinar->id ) ) {
			return;
		}

		$args = array(
			'include' => $webinar_id,
			'from'    => false,
			'since'   => false,
		);

		if ( true === (bool) $webinar->recurring && true === (bool) $webinar->hide_sitewide ) {
			$args['hide_sitewide'] = true;
		}

		if ( bp_has_zoom_webinars( $args ) ) {
			while ( bp_zoom_webinar() ) {
				bp_the_zoom_webinar();

				bp_get_template_part( 'zoom/activity-webinar-entry' );
			}
		}
	}

	/**
	 * Notify all group members when a meeting is created.
	 *
	 * @param object $meeting Meeting object.
	 * @param array  $args Arguments.
	 *
	 * @since 1.0.0
	 */
	public function groups_notification_meeting_created( $meeting, $args ) {
		if ( ! bp_is_active( 'notifications' ) || empty( $meeting ) || empty( $meeting->group_id ) || ! empty( $args['id'] ) || ! empty( $meeting->parent ) ) {
			return;
		}

		// Send notifications.
		bp_zoom_groups_send_meeting_notifications( $meeting );
	}

	/**
	 * Notify all group members when a webinar is created.
	 *
	 * @param object $webinar Webinar object.
	 * @param array  $args Arguments.
	 *
	 * @since 1.0.9
	 */
	public function groups_notification_webinar_created( $webinar, $args ) {
		if ( ! bp_is_active( 'notifications' ) || empty( $webinar ) || empty( $webinar->group_id ) || ! empty( $args['id'] ) || ! empty( $webinar->parent ) ) {
			return;
		}

		// Send notifications.
		bp_zoom_groups_send_webinar_notifications( $webinar );
	}

	/**
	 * Create meeting notification for groups.
	 *
	 * @param string $action            Notification action.
	 * @param int    $item_id           Item for notification.
	 * @param int    $secondary_item_id Secondary item for notification.
	 * @param int    $total_items       Total items.
	 * @param string $format            Format html or string.
	 *
	 * @return mixed|void
	 * @since 1.0.0
	 */
	public function groups_format_create_meeting_notification( $action, $item_id, $secondary_item_id, $total_items, $format ) {
		$group_id = $item_id;

		$group      = groups_get_group( $group_id );
		$group_link = bp_get_group_permalink( $group );
		$meeting    = new BP_Zoom_Meeting( $secondary_item_id );
		$amount     = 'single';

		if ( (int) $total_items > 1 ) {
			$text = sprintf(
				/* translators: total number of groups. */
				__( 'You have %1$d new Zoom meetings in groups', 'buddyboss-pro' ),
				(int) $total_items
			);
			$amount            = 'multiple';
			$notification_link = trailingslashit( bp_loggedin_user_domain() . bp_get_groups_slug() ) . '?n=1';

			if ( 'string' === $format ) {
				/**
				 * Filters multiple promoted to group mod notification for string format.
				 * Complete filter - bp_groups_multiple_member_promoted_to_mod_notification.
				 *
				 * @param string $string HTML anchor tag for notification.
				 * @param int $total_items Total number of rejected requests.
				 * @param string $text Notification content.
				 * @param string $notification_link The permalink for notification.
				 *
				 * @since 1.0.0
				 */
				return apply_filters( 'bp_groups_' . $amount . '_' . $action . '_notification', '<a href="' . $notification_link . '">' . $text . '</a>', $total_items, $text, $notification_link );
			} else {
				/**
				 * Filters multiple promoted to group mod notification for non-string format.
				 * Complete filter - bp_groups_multiple_member_promoted_to_mod_notification.
				 *
				 * @param array $array Array holding permalink and content for notification.
				 * @param int $total_items Total number of rejected requests.
				 * @param string $text Notification content.
				 * @param string $notification_link The permalink for notification.
				 *
				 * @since 1.0.0
				 */
				return apply_filters(
					'bp_groups_' . $amount . '_' . $action . '_notification',
					array(
						'link' => $notification_link,
						'text' => $text,
					),
					$total_items,
					$text,
					$notification_link
				);
			}
		} else {
			if ( 'meeting_occurrence' === $meeting->zoom_type ) {
				$text = sprintf(
				/* translators: 1 Meeting title. 2 Group Title. */
					__( 'You have a meeting "%1$s" scheduled in the group "%2$s"', 'buddyboss-pro' ),
					$meeting->title,
					$group->name
				);
			} else {
				$text = sprintf(
				/* translators: 1 Meeting title. 2 Group Title. */
					__( 'Zoom meeting "%1$s" created in the group "%2$s"', 'buddyboss-pro' ),
					$meeting->title,
					$group->name
				);
			}

			$notification_link = wp_nonce_url(
				add_query_arg(
					array(
						'action'     => 'bp_mark_read',
						'group_id'   => $item_id,
						'meeting_id' => $secondary_item_id,
					),
					$group_link . 'zoom/meetings/' . $secondary_item_id
				),
				'bp_mark_meeting_' . $item_id
			);

			if ( 'string' === $format ) {
				/**
				 * Filters single promoted to group mod notification for string format.
				 * Complete filter - bp_groups_single_zoom_meeting_created_notification.
				 *
				 * @param string $string HTML anchor tag for notification.
				 * @param int $group_link The permalink for the group.
				 * @param string $group ->name       Name of the group.
				 * @param string $text Notification content.
				 * @param string $notification_link The permalink for notification.
				 *
				 * @since 1.0.0
				 */
				return apply_filters( 'bp_groups_' . $amount . '_' . $action . '_notification', '<a href="' . $notification_link . '">' . $text . '</a>', $group_link, $group->name, $text, $notification_link );
			} else {
				/**
				 * Filters single promoted to group admin notification for non-string format.
				 * Complete filter - bp_groups_single_member_promoted_to_mod_notification.
				 *
				 * @param array $array Array holding permalink and content for notification.
				 * @param int $group_link The permalink for the group.
				 * @param string $group ->name       Name of the group.
				 * @param string $text Notification content.
				 * @param string $notification_link The permalink for notification.
				 *
				 * @since 1.0.0
				 */
				return apply_filters(
					'bp_groups_' . $amount . '_' . $action . '_notification',
					array(
						'link' => $notification_link,
						'text' => $text,
					),
					$group_link,
					$group->name,
					$text,
					$notification_link
				);
			}
		}
	}

	/**
	 * Mark zoom meeting modern notifications.
	 *
	 * @since 1.2.1
	 *
	 * @param bool   $success    Any sucess ready performed or not.
	 * @param int    $user_id    Current user ID.
	 * @param int    $group_id   Group ID.
	 * @param int    $action     Action for notification.
	 * @param string $meeting_id Meeting ID.
	 *
	 * @return mixed|void
	 */
	public function bb_mark_modern_meeting_notifications( $success, $user_id, $group_id, $action, $meeting_id ) {
		if ( empty( $user_id ) ) {
			return;
		}

		if ( ! empty( $meeting_id ) ) {
			// Attempt to clear notifications for the current user from this meeting.
			bp_notifications_mark_notifications_by_item_id( $user_id, $group_id, buddypress()->groups->id, 'bb_groups_new_zoom', $meeting_id );
		} else {
			// Attempt to clear notifications for the current user from this meeting.
			bp_notifications_mark_notifications_by_item_id( $user_id, $group_id, buddypress()->groups->id, 'bb_groups_new_zoom' );
		}
	}

	/**
	 * Notified meeting notification for groups.
	 *
	 * @param string $action            Notification action.
	 * @param int    $item_id           Item for notification.
	 * @param int    $secondary_item_id Secondary item for notification.
	 * @param int    $total_items       Total items.
	 * @param string $format            Format html or string.
	 *
	 * @return mixed|void
	 * @since 1.0.0
	 */
	public function groups_format_notified_meeting_notification( $action, $item_id, $secondary_item_id, $total_items, $format ) {
		$group_id = $item_id;

		$group      = groups_get_group( $group_id );
		$group_link = bp_get_group_permalink( $group );
		$meeting    = new BP_Zoom_Meeting( $secondary_item_id );
		$amount     = 'single';

		if ( (int) $total_items > 1 ) {
			$text = sprintf(
			/* translators: total number of groups. */
				__( 'You have %1$d new Zoom meetings in groups', 'buddyboss-pro' ),
				(int) $total_items
			);
			$amount            = 'multiple';
			$notification_link = trailingslashit( bp_loggedin_user_domain() . bp_get_groups_slug() ) . '?n=1';

			if ( 'string' === $format ) {
				/**
				 * Filters multiple promoted to group mod notification for string format.
				 * Complete filter - bp_groups_multiple_member_promoted_to_mod_notification.
				 *
				 * @param string $string HTML anchor tag for notification.
				 * @param int $total_items Total number of rejected requests.
				 * @param string $text Notification content.
				 * @param string $notification_link The permalink for notification.
				 *
				 * @since 1.0.0
				 */
				return apply_filters( 'bp_groups_' . $amount . '_' . $action . '_notification', '<a href="' . $notification_link . '">' . $text . '</a>', $total_items, $text, $notification_link );
			} else {
				/**
				 * Filters multiple promoted to group mod notification for non-string format.
				 * Complete filter - bp_groups_multiple_member_promoted_to_mod_notification.
				 *
				 * @param array $array Array holding permalink and content for notification.
				 * @param int $total_items Total number of rejected requests.
				 * @param string $text Notification content.
				 * @param string $notification_link The permalink for notification.
				 *
				 * @since 1.0.0
				 */
				return apply_filters(
					'bp_groups_' . $amount . '_' . $action . '_notification',
					array(
						'link' => $notification_link,
						'text' => $text,
					),
					$total_items,
					$text,
					$notification_link
				);
			}
		} else {

			$text = sprintf(
				/* translators: 1 Meeting title. 2 Group Title. */
				__( 'You have a meeting "%1$s" scheduled in the group "%2$s"', 'buddyboss-pro' ),
				$meeting->title,
				$group->name
			);

			$notification_link = wp_nonce_url(
				add_query_arg(
					array(
						'action'     => 'bp_mark_read',
						'group_id'   => $item_id,
						'meeting_id' => $secondary_item_id,
					),
					$group_link . 'zoom/meetings/' . $secondary_item_id
				),
				'bp_mark_meeting_' . $item_id
			);

			if ( 'string' === $format ) {
				/**
				 * Filters single promoted to group mod notification for string format.
				 * Complete filter - bp_groups_single_zoom_meeting_created_notification.
				 *
				 * @param string $string HTML anchor tag for notification.
				 * @param int $group_link The permalink for the group.
				 * @param string $group ->name       Name of the group.
				 * @param string $text Notification content.
				 * @param string $notification_link The permalink for notification.
				 *
				 * @since 1.0.0
				 */
				return apply_filters( 'bp_groups_' . $amount . '_' . $action . '_notification', '<a href="' . $notification_link . '">' . $text . '</a>', $group_link, $group->name, $text, $notification_link );
			} else {
				/**
				 * Filters single promoted to group admin notification for non-string format.
				 * Complete filter - bp_groups_single_member_promoted_to_mod_notification.
				 *
				 * @param array $array Array holding permalink and content for notification.
				 * @param int $group_link The permalink for the group.
				 * @param string $group ->name       Name of the group.
				 * @param string $text Notification content.
				 * @param string $notification_link The permalink for notification.
				 *
				 * @since 1.0.0
				 */
				return apply_filters(
					'bp_groups_' . $amount . '_' . $action . '_notification',
					array(
						'link' => $notification_link,
						'text' => $text,
					),
					$group_link,
					$group->name,
					$text,
					$notification_link
				);
			}
		}
	}

	/**
	 * Mark zoom meeting notifications.
	 *
	 * @param string $action Action for notification.
	 *
	 * @since 1.0.0
	 */
	public function zoom_meeting_mark_notifications( $action = '' ) {
		$group_id = filter_input( INPUT_GET, 'group_id', FILTER_VALIDATE_INT );

		// Bail if no group ID is passed.
		if ( empty( $group_id ) ) {
			return;
		}

		// Bail if action is not for this function.
		if ( 'bp_mark_read' !== $action ) {
			return;
		}

		// Get required data.
		$user_id    = bp_loggedin_user_id();
		$meeting_id = filter_input( INPUT_GET, 'meeting_id', FILTER_VALIDATE_INT );

		// Check nonce.
		if ( ! bp_verify_nonce_request( 'bp_mark_meeting_' . $group_id ) ) {
			return;

			// Check current user's ability to edit the user.
		} elseif ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		if ( ! empty( $meeting_id ) ) {
			// Attempt to clear notifications for the current user from this meeting.
			$success = bp_notifications_mark_notifications_by_item_id( $user_id, $group_id, buddypress()->groups->id, 'zoom_meeting_created', $meeting_id );
		} else {
			// Attempt to clear notifications for the current user from this meeting.
			$success = bp_notifications_mark_notifications_by_item_id( $user_id, $group_id, buddypress()->groups->id, 'zoom_meeting_created' );
		}

		if ( ! empty( $meeting_id ) ) {
			// Attempt to clear notifications for the current user from this meeting.
			$success_notified = bp_notifications_mark_notifications_by_item_id( $user_id, $group_id, buddypress()->groups->id, 'zoom_meeting_notified', $meeting_id );
		} else {
			// Attempt to clear notifications for the current user from this meeting.
			$success_notified = bp_notifications_mark_notifications_by_item_id( $user_id, $group_id, buddypress()->groups->id, 'zoom_meeting_notified' );
		}

		if ( ! empty( $success_notified ) ) {
			$success = $success_notified;
		}

		// Do additional subscriptions actions.
		do_action( 'bp_zoom_meeting_mark_notifications_handler', $success, $user_id, $group_id, $action, $meeting_id );
	}

	/**
	 * Delete create meeting notifications.
	 *
	 * @param array $meeting_ids Meeting ids deleted.
	 *
	 * @since 1.0.0
	 */
	public function delete_meeting_notifications( $meeting_ids ) {
		if ( ! bp_is_active( 'notifications' ) ) {
			return;
		}

		if ( ! empty( $meeting_ids ) ) {
			foreach ( $meeting_ids as $meeting_id ) {
				$meeting = new BP_Zoom_Meeting( $meeting_id );

				if ( ! empty( $meeting->id ) && ! empty( $meeting->group_id ) && ! empty( $meeting->user ) ) {
					bp_notifications_delete_notifications_by_item_id( $meeting->user, $meeting->group_id, buddypress()->groups->id, 'zoom_meeting_created', $meeting_id );
				}
			}
		}
	}

	/**
	 * Create webinar notification for groups.
	 *
	 * @param string $action            Notification action.
	 * @param int    $item_id           Item for notification.
	 * @param int    $secondary_item_id Secondary item for notification.
	 * @param int    $total_items       Total items.
	 * @param string $format            Format html or string.
	 *
	 * @return mixed|void
	 * @since 1.0.9
	 */
	public function groups_format_create_webinar_notification( $action, $item_id, $secondary_item_id, $total_items, $format ) {
		$group_id = $item_id;

		$group      = groups_get_group( $group_id );
		$group_link = bp_get_group_permalink( $group );
		$webinar    = new BP_Zoom_Webinar( $secondary_item_id );
		$amount     = 'single';

		if ( (int) $total_items > 1 ) {
			$text = sprintf(
			/* translators: total number of groups. */
				__( 'You have %1$d new Zoom webinars in groups', 'buddyboss-pro' ),
				(int) $total_items
			);
			$amount            = 'multiple';
			$notification_link = trailingslashit( bp_loggedin_user_domain() . bp_get_groups_slug() ) . '?n=1';

			if ( 'string' === $format ) {
				/**
				 * Filters multiple promoted to group mod notification for string format.
				 * Complete filter - bp_groups_multiple_member_promoted_to_mod_notification.
				 *
				 * @param string $string HTML anchor tag for notification.
				 * @param int $total_items Total number of rejected requests.
				 * @param string $text Notification content.
				 * @param string $notification_link The permalink for notification.
				 *
				 * @since 1.0.0
				 */
				return apply_filters( 'bp_groups_' . $amount . '_' . $action . '_notification', '<a href="' . $notification_link . '">' . $text . '</a>', $total_items, $text, $notification_link );
			} else {
				/**
				 * Filters multiple promoted to group mod notification for non-string format.
				 * Complete filter - bp_groups_multiple_member_promoted_to_mod_notification.
				 *
				 * @param array $array Array holding permalink and content for notification.
				 * @param int $total_items Total number of rejected requests.
				 * @param string $text Notification content.
				 * @param string $notification_link The permalink for notification.
				 *
				 * @since 1.0.0
				 */
				return apply_filters(
					'bp_groups_' . $amount . '_' . $action . '_notification',
					array(
						'link' => $notification_link,
						'text' => $text,
					),
					$total_items,
					$text,
					$notification_link
				);
			}
		} else {
			if ( 'webinar_occurrence' === $webinar->zoom_type ) {
				$text = sprintf(
				/* translators: 1 Webinar title. 2 Group Title. */
					__( 'You have a webinar "%1$s" scheduled in the group "%2$s"', 'buddyboss-pro' ),
					$webinar->title,
					$group->name
				);
			} else {
				$text = sprintf(
				/* translators: 1 Webinar title. 2 Group Title. */
					__( 'Zoom webinar "%1$s" created in the group "%2$s"', 'buddyboss-pro' ),
					$webinar->title,
					$group->name
				);
			}

			$notification_link = wp_nonce_url(
				add_query_arg(
					array(
						'action'     => 'bp_mark_read',
						'group_id'   => $item_id,
						'webinar_id' => $secondary_item_id,
					),
					$group_link . 'zoom/webinars/' . $secondary_item_id
				),
				'bp_mark_webinar_' . $item_id
			);

			if ( 'string' === $format ) {
				/**
				 * Filters single promoted to group mod notification for string format.
				 * Complete filter - bp_groups_single_zoom_meeting_created_notification.
				 *
				 * @param string $string HTML anchor tag for notification.
				 * @param int $group_link The permalink for the group.
				 * @param string $group ->name       Name of the group.
				 * @param string $text Notification content.
				 * @param string $notification_link The permalink for notification.
				 *
				 * @since 1.0.0
				 */
				return apply_filters( 'bp_groups_' . $amount . '_' . $action . '_notification', '<a href="' . $notification_link . '">' . $text . '</a>', $group_link, $group->name, $text, $notification_link );
			} else {
				/**
				 * Filters single promoted to group admin notification for non-string format.
				 * Complete filter - bp_groups_single_member_promoted_to_mod_notification.
				 *
				 * @param array $array Array holding permalink and content for notification.
				 * @param int $group_link The permalink for the group.
				 * @param string $group ->name       Name of the group.
				 * @param string $text Notification content.
				 * @param string $notification_link The permalink for notification.
				 *
				 * @since 1.0.0
				 */
				return apply_filters(
					'bp_groups_' . $amount . '_' . $action . '_notification',
					array(
						'link' => $notification_link,
						'text' => $text,
					),
					$group_link,
					$group->name,
					$text,
					$notification_link
				);
			}
		}
	}

	/**
	 * Notified webinar notification for groups.
	 *
	 * @param string $action            Notification action.
	 * @param int    $item_id           Item for notification.
	 * @param int    $secondary_item_id Secondary item for notification.
	 * @param int    $total_items       Total items.
	 * @param string $format            Format html or string.
	 *
	 * @return mixed|void
	 * @since 1.0.9
	 */
	public function groups_format_notified_webinar_notification( $action, $item_id, $secondary_item_id, $total_items, $format ) {
		$group_id = $item_id;

		$group      = groups_get_group( $group_id );
		$group_link = bp_get_group_permalink( $group );
		$webinar    = new BP_Zoom_Webinar( $secondary_item_id );
		$amount     = 'single';

		if ( (int) $total_items > 1 ) {
			$text = sprintf(
			/* translators: total number of groups. */
				__( 'You have %1$d new Zoom webinars in groups', 'buddyboss-pro' ),
				(int) $total_items
			);
			$amount            = 'multiple';
			$notification_link = trailingslashit( bp_loggedin_user_domain() . bp_get_groups_slug() ) . '?n=1';

			if ( 'string' === $format ) {
				/**
				 * Filters multiple promoted to group mod notification for string format.
				 * Complete filter - bp_groups_multiple_member_promoted_to_mod_notification.
				 *
				 * @param string $string HTML anchor tag for notification.
				 * @param int $total_items Total number of rejected requests.
				 * @param string $text Notification content.
				 * @param string $notification_link The permalink for notification.
				 *
				 * @since 1.0.0
				 */
				return apply_filters( 'bp_groups_' . $amount . '_' . $action . '_notification', '<a href="' . $notification_link . '">' . $text . '</a>', $total_items, $text, $notification_link );
			} else {
				/**
				 * Filters multiple promoted to group mod notification for non-string format.
				 * Complete filter - bp_groups_multiple_member_promoted_to_mod_notification.
				 *
				 * @param array $array Array holding permalink and content for notification.
				 * @param int $total_items Total number of rejected requests.
				 * @param string $text Notification content.
				 * @param string $notification_link The permalink for notification.
				 *
				 * @since 1.0.0
				 */
				return apply_filters(
					'bp_groups_' . $amount . '_' . $action . '_notification',
					array(
						'link' => $notification_link,
						'text' => $text,
					),
					$total_items,
					$text,
					$notification_link
				);
			}
		} else {

			$text = sprintf(
			/* translators: 1 Webinar title. 2 Group Title. */
				__( 'You have a webinar "%1$s" scheduled in the group "%2$s"', 'buddyboss-pro' ),
				$webinar->title,
				$group->name
			);

			$notification_link = wp_nonce_url(
				add_query_arg(
					array(
						'action'     => 'bp_mark_read',
						'group_id'   => $item_id,
						'webinar_id' => $secondary_item_id,
					),
					$group_link . 'zoom/webinars/' . $secondary_item_id
				),
				'bp_mark_webinar_' . $item_id
			);

			if ( 'string' === $format ) {
				/**
				 * Filters single promoted to group mod notification for string format.
				 * Complete filter - bp_groups_single_zoom_meeting_created_notification.
				 *
				 * @param string $string HTML anchor tag for notification.
				 * @param int $group_link The permalink for the group.
				 * @param string $group ->name       Name of the group.
				 * @param string $text Notification content.
				 * @param string $notification_link The permalink for notification.
				 *
				 * @since 1.0.0
				 */
				return apply_filters( 'bp_groups_' . $amount . '_' . $action . '_notification', '<a href="' . $notification_link . '">' . $text . '</a>', $group_link, $group->name, $text, $notification_link );
			} else {
				/**
				 * Filters single promoted to group admin notification for non-string format.
				 * Complete filter - bp_groups_single_member_promoted_to_mod_notification.
				 *
				 * @param array $array Array holding permalink and content for notification.
				 * @param int $group_link The permalink for the group.
				 * @param string $group ->name       Name of the group.
				 * @param string $text Notification content.
				 * @param string $notification_link The permalink for notification.
				 *
				 * @since 1.0.0
				 */
				return apply_filters(
					'bp_groups_' . $amount . '_' . $action . '_notification',
					array(
						'link' => $notification_link,
						'text' => $text,
					),
					$group_link,
					$group->name,
					$text,
					$notification_link
				);
			}
		}
	}

	/**
	 * Mark zoom webinar notifications.
	 *
	 * @param string $action Action for notification.
	 *
	 * @since 1.0.9
	 */
	public function zoom_webinar_mark_notifications( $action = '' ) {
		$group_id = filter_input( INPUT_GET, 'group_id', FILTER_VALIDATE_INT );

		// Bail if no group ID is passed.
		if ( empty( $group_id ) ) {
			return;
		}

		// Bail if action is not for this function.
		if ( 'bp_mark_read' !== $action ) {
			return;
		}

		// Get required data.
		$user_id    = bp_loggedin_user_id();
		$webinar_id = filter_input( INPUT_GET, 'webinar_id', FILTER_VALIDATE_INT );

		// Check nonce.
		if ( ! bp_verify_nonce_request( 'bp_mark_webinar_' . $group_id ) ) {
			return;

			// Check current user's ability to edit the user.
		} elseif ( ! current_user_can( 'edit_user', $user_id ) ) {
			return;
		}

		if ( ! empty( $webinar_id ) ) {
			// Attempt to clear notifications for the current user from this webinar.
			$success = bp_notifications_mark_notifications_by_item_id( $user_id, $group_id, buddypress()->groups->id, 'zoom_webinar_created', $webinar_id );
		} else {
			// Attempt to clear notifications for the current user from this webinar.
			$success = bp_notifications_mark_notifications_by_item_id( $user_id, $group_id, buddypress()->groups->id, 'zoom_webinar_created' );
		}

		if ( ! empty( $webinar_id ) ) {
			// Attempt to clear notifications for the current user from this webinar.
			$success_notified = bp_notifications_mark_notifications_by_item_id( $user_id, $group_id, buddypress()->groups->id, 'zoom_webinar_notified', $webinar_id );
		} else {
			// Attempt to clear notifications for the current user from this webinar.
			$success_notified = bp_notifications_mark_notifications_by_item_id( $user_id, $group_id, buddypress()->groups->id, 'zoom_webinar_notified' );
		}

		if ( ! empty( $success_notified ) ) {
			$success = $success_notified;
		}

		// Do additional subscriptions actions.
		do_action( 'bp_zoom_webinar_mark_notifications_handler', $success, $user_id, $group_id, $action, $webinar_id );
	}

	/**
	 * Mark zoom meeting modern notifications.
	 *
	 * @since 1.2.1
	 *
	 * @param bool   $success    Any sucess ready performed or not.
	 * @param int    $user_id    Current user ID.
	 * @param int    $group_id   Group ID.
	 * @param int    $action     Action for notification.
	 * @param string $webinar_id Webinar ID.
	 *
	 * @return void
	 */
	public function bb_mark_modern_webinar_notifications( $success, $user_id, $group_id, $action, $webinar_id ) {
		if ( empty( $user_id ) ) {
			return;
		}

		if ( ! empty( $webinar_id ) ) {
			// Attempt to clear notifications for the current user from this meeting.
			bp_notifications_mark_notifications_by_item_id( $user_id, $group_id, buddypress()->groups->id, 'bb_groups_new_zoom', $webinar_id );
		} else {
			// Attempt to clear notifications for the current user from this meeting.
			bp_notifications_mark_notifications_by_item_id( $user_id, $group_id, buddypress()->groups->id, 'bb_groups_new_zoom' );
		}
	}

	/**
	 * Delete create webinar notifications.
	 *
	 * @param array $webinar_ids Webinar ids deleted.
	 *
	 * @since 1.0.9
	 */
	public function delete_webinar_notifications( $webinar_ids ) {
		if ( ! bp_is_active( 'notifications' ) ) {
			return;
		}

		if ( ! empty( $webinar_ids ) ) {
			foreach ( $webinar_ids as $webinar_id ) {
				$webinar = new BP_Zoom_Webinar( $webinar_id );

				if ( ! empty( $webinar->id ) && ! empty( $webinar->group_id ) && ! empty( $webinar->user ) ) {
					bp_notifications_delete_notifications_by_item_id( $webinar->user, $webinar->group_id, buddypress()->groups->id, 'zoom_webinar_created', $webinar_id );
				}
			}
		}
	}

	/**
	 * Customizer group nav items.
	 *
	 * @param array  $nav_items Nav items for customizer.
	 * @param object $group Group Object.
	 *
	 * @since 1.0.0
	 */
	public function customizer_group_nav_items( $nav_items, $group ) {
		$nav_items['zoom'] = array(
			'name'        => __( 'Zoom', 'buddyboss-pro' ),
			'slug'        => 'zoom',
			'parent_slug' => $group->slug,
			'position'    => 90,
		);

		return $nav_items;
	}

	/**
	 * Zoom webhook handler
	 *
	 * @since 1.0.0
	 */
	public function zoom_webhook() {
		$zoom_webhook = filter_input( INPUT_GET, 'zoom_webhook', FILTER_VALIDATE_INT );

		if ( ! empty( $zoom_webhook ) && 1 === $zoom_webhook ) {

			$group_id = filter_input( INPUT_GET, 'group_id', FILTER_VALIDATE_INT );
			if ( ! empty( $group_id ) && 0 < $group_id && bp_zoom_is_group_setup( $group_id ) && ! empty( groups_get_group( $group_id ) ) ) {
				$content = file_get_contents( 'php://input' );
				$json    = json_decode( $content, true );

				$group_token = groups_get_groupmeta( $group_id, 'bp-group-zoom-api-webhook-token', true );

				if ( ! empty( $json['event'] ) && 'endpoint.url_validation' === $json['event'] && ! empty( $json['payload']['plainToken'] ) ) {
					$this->zoom_url_validate( $json, $group_token );
				}

				if ( empty( trim( $group_token ) ) ) {
					$this->forbid( 'No token detected' );
					exit;
				}

				$event = '';
				if ( ! empty( $json['event'] ) ) {
					$event = $json['event'];
				}

				if ( ! empty( $json['payload']['object'] ) && in_array( $event, array( 'meeting.started', 'meeting.ended', 'meeting.updated', 'meeting.deleted', 'recording.completed' ), true ) ) {
					$object          = $json['payload']['object'];
					$zoom_meeting_id = ! empty( $object['id'] ) ? $object['id'] : false;
					$zoom_meeting    = BP_Zoom_Meeting::get_meeting_by_meeting_id( $zoom_meeting_id );
					$meeting         = false;

					if ( ! empty( $zoom_meeting ) ) {
						$meeting = new BP_Zoom_Meeting( $zoom_meeting->id );

						if ( empty( $meeting->id ) ) {
							$this->forbid( 'No meeting detected' );
							exit;
						}
					}

					if ( empty( $meeting ) ) {
						$this->forbid( 'No meeting detected' );
						exit;
					}

					if ( $meeting->group_id !== $group_id ) {
						$this->forbid( 'This meeting does not belong to group provided' );
						exit;
					}

					switch ( $event ) {
						case 'meeting.started':
							bp_zoom_meeting_update_meta( $meeting->id, 'meeting_status', 'started' );

							// Recurring meeting than check occurrences dates and update those as well and remove parent's status.
							if ( 8 === $meeting->type ) {
								$occurrences = bp_zoom_meeting_get(
									array(
										'parent' => $meeting->meeting_id,
										'fields' => 'ids',
									)
								);
								if ( ! empty( $occurrences['meetings'] ) ) {
									foreach ( $occurrences['meetings'] as $occurrence ) {
										$zoom_meeting_occurrence = new BP_Zoom_Meeting( $occurrence );
										$occurrence_date         = new DateTime( $zoom_meeting_occurrence->start_date_utc );
										$occurrence_date->setTimezone( wp_timezone() );
										if ( $occurrence_date->format( 'Y-m-d' ) === wp_date( 'Y-m-d', strtotime( 'now' ) ) ) {
											bp_zoom_meeting_update_meta( $occurrence, 'meeting_status', 'started' );
											bp_zoom_meeting_delete_meta( $meeting->id, 'meeting_status' );
											break;
										}
									}
								}
							}
							break;

						case 'meeting.ended':
							bp_zoom_meeting_update_meta( $meeting->id, 'meeting_status', 'ended' );

							// Recurring meeting than check occurrences and remove their status.
							if ( 8 === $meeting->type ) {
								$occurrences = bp_zoom_meeting_get(
									array(
										'parent' => $meeting->meeting_id,
										'fields' => 'ids',
									)
								);
								if ( ! empty( $occurrences['meetings'] ) ) {
									foreach ( $occurrences['meetings'] as $occurrence ) {
										bp_zoom_meeting_delete_meta( $occurrence, 'meeting_status' );
									}
								}
							}
							break;

						case 'meeting.deleted':
							if ( ! empty( $object['occurrences'] ) ) {
								foreach ( $object['occurrences'] as $occurrence ) {
									bp_zoom_meeting_delete( array( 'meeting_id' => $occurrence['occurrence_id'] ) );
								}
							} else {
								bp_zoom_meeting_delete( array( 'id' => $meeting->id ) );
							}
							break;

						case 'meeting.updated':
							$meeting->save();
							break;
						case 'recording.completed':
							if ( ! bp_zoom_is_zoom_recordings_enabled() ) {
								break;
							}
							$password        = ! empty( $object['password'] ) ? $object['password'] : '';
							$recording_files = ! empty( $object['recording_files'] ) ? $object['recording_files'] : array();
							$start_time      = ! empty( $object['start_time'] ) ? $object['start_time'] : '';
							if ( ! empty( $recording_files ) ) {
								foreach ( $recording_files as $recording_file ) {
									$recording_id = ( isset( $recording_file['id'] ) ? $recording_file['id'] : '' );
									if ( ! empty( $recording_id ) && empty( bp_zoom_recording_get( array(), array( 'recording_id' => $recording_id ) ) ) ) {
										bp_zoom_recording_add(
											array(
												'recording_id' => $recording_id,
												'meeting_id' => $zoom_meeting_id,
												'uuid'     => $object['uuid'],
												'details'  => $recording_file,
												'password' => $password,
												'file_type' => $recording_file['file_type'],
												'start_time' => $start_time,
											)
										);
									}
								}

								$count = bp_zoom_recording_get(
									array(),
									array(
										'meeting_id' => $zoom_meeting_id,
									)
								);

								bp_zoom_meeting_update_meta( $meeting->id, 'zoom_recording_count', (int) count( $count ) );
							}
							break;
					}
				}

				if ( ! empty( $json['payload']['object'] ) && in_array( $event, array( 'webinar.started', 'webinar.ended', 'webinar.updated', 'webinar.deleted', 'recording.completed' ), true ) ) {
					$object          = $json['payload']['object'];
					$zoom_webinar_id = ! empty( $object['id'] ) ? $object['id'] : false;
					$zoom_webinar    = BP_Zoom_Webinar::get_webinar_by_webinar_id( $zoom_webinar_id );
					$webinar         = false;

					if ( ! empty( $zoom_webinar ) ) {
						$webinar = new BP_Zoom_Webinar( $zoom_webinar->id );

						if ( empty( $webinar->id ) ) {
							$this->forbid( 'No webinar detected' );
							exit;
						}
					}

					if ( empty( $webinar ) ) {
						$this->forbid( 'No webinar detected' );
						exit;
					}

					if ( $webinar->group_id !== $group_id ) {
						$this->forbid( 'This webinar does not belong to group provided' );
						exit;
					}

					switch ( $event ) {
						case 'webinar.started':
							bp_zoom_webinar_update_meta( $webinar->id, 'webinar_status', 'started' );

							// Recurring webinar than check occurrences dates and update those as well and remove parent's status.
							if ( 9 === $webinar->type ) {
								$occurrences = bp_zoom_webinar_get(
									array(
										'parent' => $webinar->webinar_id,
										'fields' => 'ids',
									)
								);
								if ( ! empty( $occurrences['webinars'] ) ) {
									foreach ( $occurrences['webinars'] as $occurrence ) {
										$zoom_webinar_occurrence = new BP_Zoom_Webinar( $occurrence );
										$occurrence_date         = new DateTime( $zoom_webinar_occurrence->start_date_utc );
										$occurrence_date->setTimezone( wp_timezone() );
										if ( $occurrence_date->format( 'Y-m-d' ) === wp_date( 'Y-m-d', strtotime( 'now' ) ) ) {
											bp_zoom_webinar_update_meta( $occurrence, 'webinar_status', 'started' );
											bp_zoom_webinar_delete_meta( $webinar->id, 'webinar_status' );
											break;
										}
									}
								}
							}
							break;

						case 'webinar.ended':
							bp_zoom_webinar_update_meta( $webinar->id, 'webinar_status', 'ended' );

							// Recurring webinar than check occurrences and remove their status.
							if ( 8 === $webinar->type ) {
								$occurrences = bp_zoom_webinar_get(
									array(
										'parent' => $webinar->webinar_id,
										'fields' => 'ids',
									)
								);
								if ( ! empty( $occurrences['webinars'] ) ) {
									foreach ( $occurrences['webinars'] as $occurrence ) {
										bp_zoom_webinar_delete_meta( $occurrence, 'webinar_status' );
									}
								}
							}
							break;

						case 'webinar.deleted':
							if ( ! empty( $object['occurrences'] ) ) {
								foreach ( $object['occurrences'] as $occurrence ) {
									bp_zoom_webinar_delete( array( 'webinar_id' => $occurrence['occurrence_id'] ) );
								}
							} else {
								bp_zoom_webinar_delete( array( 'id' => $webinar->id ) );
							}
							break;

						case 'webinar.updated':
							$webinar->save();
							break;
						case 'recording.completed':
							if ( ! bp_zoom_is_zoom_recordings_enabled() ) {
								break;
							}
							$password        = ! empty( $object['password'] ) ? $object['password'] : '';
							$recording_files = ! empty( $object['recording_files'] ) ? $object['recording_files'] : array();
							$start_time      = ! empty( $object['start_time'] ) ? $object['start_time'] : '';
							if ( ! empty( $recording_files ) ) {
								foreach ( $recording_files as $recording_file ) {
									$recording_id = ( isset( $recording_file['id'] ) ? $recording_file['id'] : '' );
									if ( ! empty( $recording_id ) && empty( bp_zoom_webinar_recording_get( array(), array( 'recording_id' => $recording_id ) ) ) ) {
										bp_zoom_webinar_recording_add(
											array(
												'recording_id' => $recording_id,
												'webinar_id' => $zoom_webinar_id,
												'uuid'     => $object['uuid'],
												'details'  => $recording_file,
												'password' => $password,
												'file_type' => $recording_file['file_type'],
												'start_time' => $start_time,
											)
										);
									}
								}

								$count = bp_zoom_webinar_recording_get(
									array(),
									array(
										'webinar_id' => $zoom_webinar_id,
									)
								);

								bp_zoom_webinar_update_meta( $webinar->id, 'zoom_recording_count', (int) count( $count ) );
							}
							break;
					}
				}
			}
		}
	}

	/**
	 * Validate zoom webhook URL.
	 *
	 * @since 2.3.0
	 *
	 * @param array  $parameters Webhook validate API request params.
	 * @param string $group_token zoom api webhook token.
	 */
	public function zoom_url_validate( $parameters, $group_token ) {
		$plain_token     = $parameters['payload']['plainToken'];
		$encrypted_token = hash_hmac( 'sha256', $plain_token, $group_token );
		$retval = array(
			'plainToken'     => $plain_token,
			'encryptedToken' => $encrypted_token,
		);

		// setup status code.
		http_response_code( 200 );

		echo json_encode( $retval );

		// stop executing.
		exit;
	}

	/**
	 * Forbid zoom webhook.
	 *
	 * @since 1.0.0
	 *
	 * @param string $reason Reason to print on screen.
	 */
	public function forbid( $reason ) {
		// format the error.
		$error = '=== ERROR: ' . $reason . " ===\n*** ACCESS DENIED ***\n";

		// forbid.
		http_response_code( 403 );

		echo esc_html( $error );

		// stop executing.
		exit;
	}

	/**
	 * Setup page title for the zoom.
	 *
	 * @param string $title Page title.
	 *
	 * @return mixed
	 * @since 1.0.0
	 */
	public function bp_nouveau_group_zoom_set_page_title( $title ) {
		global $bp_zoom_current_meeting, $bp_zoom_current_webinar;
		$new_title = '';

		if ( bp_zoom_is_single_meeting() && ! empty( $bp_zoom_current_meeting->title ) ) {
			$new_title = $bp_zoom_current_meeting->title;
		}

		if ( empty( $new_title ) && bp_zoom_is_single_webinar() && ! empty( $bp_zoom_current_webinar->title ) ) {
			$new_title = $bp_zoom_current_webinar->title;
		}

		if ( empty( $new_title ) && bp_zoom_is_past_meetings() ) {
			$new_title = esc_html__( 'Past Meetings', 'buddyboss-pro' );
		}

		if ( empty( $new_title ) && bp_zoom_is_meetings() ) {
			$new_title = esc_html__( 'Upcoming Meetings', 'buddyboss-pro' );
		}

		if ( empty( $new_title ) && bp_zoom_is_create_meeting() ) {
			$new_title = esc_html__( 'Create Meeting', 'buddyboss-pro' );
		}

		if ( empty( $new_title ) && bp_zoom_is_past_webinars() ) {
			$new_title = esc_html__( 'Past Webinars', 'buddyboss-pro' );
		}

		if ( empty( $new_title ) && bp_zoom_is_webinars() ) {
			$new_title = esc_html__( 'Upcoming Webinars', 'buddyboss-pro' );
		}

		if ( empty( $new_title ) && bp_zoom_is_create_webinar() ) {
			$new_title = esc_html__( 'Create Webinar', 'buddyboss-pro' );
		}

		if ( strlen( $new_title ) > 0 ) {
			$title['title'] = $new_title;
		}

		return $title;
	}

	/**
	 * Setup title tag for the page.
	 *
	 * @param string $title Page title.
	 *
	 * @return mixed
	 * @since 1.0.0
	 */
	public function bp_nouveau_group_zoom_set_title_tag( $title ) {
		global $bp_zoom_current_meeting, $bp_zoom_current_webinar;
		$new_title = '';

		if ( bp_zoom_is_single_meeting() && ! empty( $bp_zoom_current_meeting->title ) ) {
			$new_title = $bp_zoom_current_meeting->title;
		}

		if ( empty( $new_title ) && bp_zoom_is_single_webinar() && ! empty( $bp_zoom_current_webinar->title ) ) {
			$new_title = $bp_zoom_current_webinar->title;
		}

		if ( empty( $new_title ) && bp_zoom_is_past_meetings() ) {
			$new_title = esc_html__( 'Past Meetings', 'buddyboss-pro' );
		}

		if ( empty( $new_title ) && bp_zoom_is_meetings() ) {
			$new_title = esc_html__( 'Upcoming Meetings', 'buddyboss-pro' );
		}

		if ( empty( $new_title ) && bp_zoom_is_create_meeting() ) {
			$new_title = esc_html__( 'Create Meeting', 'buddyboss-pro' );
		}

		if ( empty( $new_title ) && bp_zoom_is_past_webinars() ) {
			$new_title = esc_html__( 'Past Webinars', 'buddyboss-pro' );
		}

		if ( empty( $new_title ) && bp_zoom_is_webinars() ) {
			$new_title = esc_html__( 'Upcoming Webinars', 'buddyboss-pro' );
		}

		if ( empty( $new_title ) && bp_zoom_is_create_webinar() ) {
			$new_title = esc_html__( 'Create Webinar', 'buddyboss-pro' );
		}

		if ( in_array( bp_zoom_group_current_tab(), array( 'meetings', 'past-meetings', 'create-meeting' ), true ) || bp_zoom_is_single_meeting() ) {
			$sep                = apply_filters( 'document_title_separator', '-' );
			$current_group_name = bp_get_current_group_name();

			$new_title = $new_title . ' ' . $sep . ' ' . $current_group_name . ' ' . $sep . ' ' . bp_get_site_name();
		} elseif ( in_array( bp_zoom_group_current_tab(), array( 'webinars', 'past-webinars', 'create-webinar' ), true ) || bp_zoom_is_single_webinar() ) {
			$sep                = apply_filters( 'document_title_separator', '-' );
			$current_group_name = bp_get_current_group_name();

			$new_title = $new_title . ' ' . $sep . ' ' . $current_group_name . ' ' . $sep . ' ' . bp_get_site_name();
		}

		// Combine the new title with the old (separator and tagline).
		if ( strlen( $new_title ) > 0 ) {
			$title = $new_title . ' ' . $title;
		}

		return $title;
	}

	/**
	 * Remove all meetings belonging to a specific group.
	 *
	 * @since 1.0.0
	 *
	 * @param int $group_id ID of the group.
	 */
	public function delete_group_delete_all_meetings( $group_id ) {
		bp_zoom_meeting_delete( array( 'group_id' => $group_id ) );
	}

	/**
	 * Remove all webinars belonging to a specific group.
	 *
	 * @since 1.0.9
	 *
	 * @param int $group_id ID of the group.
	 */
	public function delete_group_delete_all_webinars( $group_id ) {
		bp_zoom_webinar_delete( array( 'group_id' => $group_id ) );
	}
}

