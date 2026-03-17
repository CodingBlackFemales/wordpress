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
		if ( bb_pro_should_lock_features() || ! bp_is_active( 'groups' ) || ! bp_zoom_is_zoom_groups_enabled() ) {
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
		add_action( 'bp_group_admin_edit_after', array( $this, 'admin_zoom_settings_screen_save' ) );

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

			$webinar_enabled = bp_zoom_groups_is_webinars_enabled( $current_group->id );

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
			$this->zoom_settings_screen_save( $current_group->id );

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
		add_action( 'groups_custom_edit_steps', array( $this, 'zoom_settings_edit_screen' ) );
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
			__( 'Zoom', 'buddyboss-pro' ),
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
		$this->admin_zoom_settings_screen( $item );
	}

	/**
	 * Show zoom option form when editing a group
	 *
	 * @param object|bool $group (the group to edit if in Group Admin UI).
	 *
	 * @since 1.0.0
	 * @uses is_admin() To check if we're in the Group Admin UI
	 */
	public function zoom_settings_edit_screen( $group = false ) {
		$group_id = empty( $group->id ) ? bp_get_new_group_id() : $group->id;

		if ( empty( $group_id ) ) {
			$group_id = bp_get_group_id();
		}

		$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
		wp_enqueue_script( 'bp-zoom-meeting-common', bp_zoom_integration_url( '/assets/js/bp-zoom-meeting-common' . $min . '.js' ), array( 'jquery' ), bb_platform_pro()->version, true );
		wp_localize_script(
			'bp-zoom-meeting-common',
			'bpZoomMeetingCommonVars',
			array(
				'ajax_url'                  => admin_url( 'admin-ajax.php' ),
				'fetch_account_nonce'       => wp_create_nonce( 'fetch-group-zoom-accounts' ),
				'update_secret_token_nonce' => wp_create_nonce( 'update-group-zoom-secret-token' ),
				'submit_zoom_wizard_nonce'  => wp_create_nonce( 'submit-group-zoom-wizard' ),
			)
		);

		// Should box be checked already?
		$checked = bp_zoom_group_is_zoom_enabled( $group_id );

		// Get S2S settings.
		$connection_type = groups_get_groupmeta( $group_id, 'bp-group-zoom-connection-type' );
		$account_id      = groups_get_groupmeta( $group_id, 'bb-group-zoom-s2s-account-id' );
		$client_id       = groups_get_groupmeta( $group_id, 'bb-group-zoom-s2s-client-id' );
		$client_secret   = groups_get_groupmeta( $group_id, 'bb-group-zoom-s2s-client-secret' );
		$s2s_api_email   = groups_get_groupmeta( $group_id, 'bb-group-zoom-s2s-api-email' );
		$secret_token    = groups_get_groupmeta( $group_id, 'bb-group-zoom-s2s-secret-token' );
		$account_emails  = groups_get_groupmeta( $group_id, 'bb-zoom-account-emails' );
		$bb_group_zoom   = groups_get_groupmeta( $group_id, 'bb-group-zoom' );

		if ( empty( $account_emails ) ) {
			$account_emails = array();
		}

		// Get notice.
		$notice_exists = get_transient( 'bb_group_zoom_notice_' . $group_id );

		// phpcs:ignore
		$current_tab = isset( $_GET['type'] ) ? $_GET['type'] : 's2s';

		// Prepare template arguments.
		$template_args = array(
			'group_id'        => $group_id,
			'checked'         => $checked,
			'connection_type' => $connection_type,
			'account_id'      => $account_id,
			'client_id'       => $client_id,
			'client_secret'   => $client_secret,
			's2s_api_email'   => $s2s_api_email,
			'secret_token'    => $secret_token,
			'account_emails'  => $account_emails,
			'bb_group_zoom'   => $bb_group_zoom,
			'notice_exists'   => $notice_exists,
			'current_tab'     => $current_tab,
		);

		bp_get_template_part( 'groups/single/zoom-settings-edit', null, $template_args );
	}

	/**
	 * Save the Group Zoom data on edit
	 *
	 * @param int $group_id (to handle Group Admin UI hook bp_group_admin_edit_after ).
	 *
	 * @since 1.0.0
	 */
	public function zoom_settings_screen_save( $group_id = 0 ) {

		// Bail if not a POST action.
		if ( ! bp_is_post_request() ) {
			return;
		}

		$nonce = bb_pro_filter_input_string( INPUT_POST, '_wpnonce' );

		// Theme-side Nonce check.
		if ( empty( $nonce ) || ( ! wp_verify_nonce( $nonce, 'groups_edit_save_zoom' ) ) ) {
			return;
		}

		$edit_zoom = filter_input( INPUT_POST, 'bp-edit-group-zoom', FILTER_VALIDATE_INT );
		$manager   = bb_pro_filter_input_string( INPUT_POST, 'bp-group-zoom-manager' );

		$edit_zoom = ! empty( $edit_zoom );
		$manager   = ! empty( $manager ) ? $manager : bp_zoom_group_get_manager( $group_id );
		$group_id  = ! empty( $group_id ) ? $group_id : bp_get_current_group_id();

		groups_update_groupmeta( $group_id, 'bp-group-zoom', $edit_zoom );
		groups_update_groupmeta( $group_id, 'bp-group-zoom-manager', $manager );

		bp_core_add_message( __( 'Group Zoom settings were successfully updated.', 'buddyboss-pro' ), 'success' );

		// Save S2S credentials.
		if ( $edit_zoom ) {
			$s2s_account_id    = bb_pro_filter_input_string( INPUT_POST, 'bb-group-zoom-s2s-account-id' );
			$s2s_client_id     = bb_pro_filter_input_string( INPUT_POST, 'bb-group-zoom-s2s-client-id' );
			$s2s_client_secret = bb_pro_filter_input_string( INPUT_POST, 'bb-group-zoom-s2s-client-secret' );
			$s2s_api_email     = bb_pro_filter_input_string( INPUT_POST, 'bb-group-zoom-s2s-api-email' );
			$s2s_secret_token  = bb_pro_filter_input_string( INPUT_POST, 'bb-group-zoom-s2s-secret-token' );

			bb_zoom_group_save_s2s_credentials(
				array(
					'account_id'    => $s2s_account_id,
					'client_id'     => $s2s_client_id,
					'client_secret' => $s2s_client_secret,
					'account_email' => $s2s_api_email,
					'secret_token'  => $s2s_secret_token,
					'group_id'      => $group_id,
				)
			);
		}

		/**
		 * Add action that fire before user redirect
		 *
		 * @Since 1.0.0
		 *
		 * @param int $group_id Current group id
		 */
		do_action( 'bp_group_admin_after_edit_screen_save', $group_id );

		$bb_active_tab = bb_pro_filter_input_string( INPUT_POST, 'bb-zoom-tab' );
		$bb_active_tab = ! empty( $bb_active_tab ) ? $bb_active_tab : 's2s';

		// Redirect after save.
		bp_core_redirect( trailingslashit( bp_get_group_permalink( buddypress()->groups->current_group ) . '/admin/zoom' ) . '?type=' . $bb_active_tab );
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

		$api_host_user = bb_zoom_group_get_api_host_user( $group_id );

		// Connect to Zoom.
		bb_zoom_group_connect_api( $group_id );

		if ( ! empty( $api_host_user ) ) {

			// Get user settings of host user.
			$user_settings = bp_zoom_conference()->get_user_settings( $api_host_user->id );

			// Save user settings into group meta.
			if ( 200 === $user_settings['code'] && ! empty( $user_settings['response'] ) ) {
				$connection_type = bb_zoom_group_get_connection_type( $group_id );
				if ( 'site' === $connection_type ) {
					$bb_group_zoom = bp_get_option( 'bb-zoom' );
					if ( empty( $bb_group_zoom ) ) {
						$bb_group_zoom = array();
					}
					$bb_group_zoom['account_host_user_settings'] = $user_settings['response'];
					bp_update_option( 'bb-zoom', $bb_group_zoom );

					// Checked webinar.
					if ( isset( $user_settings['response']->feature->webinar ) && true === $user_settings['response']->feature->webinar ) {
						bp_update_option( 'bp-zoom-enable-webinar', true );
					} else {
						bp_delete_option( 'bp-zoom-enable-webinar' );
					}
				} elseif ( 'group' === $connection_type ) {
					$bb_group_zoom = groups_get_groupmeta( $group_id, 'bb-group-zoom' );
					if ( empty( $bb_group_zoom ) ) {
						$bb_group_zoom = array();
					}
					$bb_group_zoom['account_host_user_settings'] = $user_settings['response'];
					groups_update_groupmeta( $group_id, 'bb-group-zoom', $bb_group_zoom );

					// Checked webinar.
					if ( isset( $user_settings['response']->feature->webinar ) && true === $user_settings['response']->feature->webinar ) {
						groups_update_groupmeta( $group_id, 'bp-group-zoom-enable-webinar', true );
					} else {
						groups_delete_groupmeta( $group_id, 'bp-group-zoom-enable-webinar' );
					}
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

		return apply_filters( 'bb_meeting_activity_action', $activity_action, $activity );
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
	 * Zoom webhook handler for groups.
	 *
	 * @since 1.0.0
	 */
	public function zoom_webhook() {
		$zoom_webhook = filter_input( INPUT_GET, 'zoom_webhook', FILTER_VALIDATE_INT );

		if ( bp_is_active( 'groups' ) && ! empty( $zoom_webhook ) && 1 === $zoom_webhook ) {

			$content = file_get_contents( 'php://input' );
			$json    = json_decode( $content, true );

			$group_id = filter_input( INPUT_GET, 'group_id', FILTER_VALIDATE_INT );
			if ( empty( $group_id ) ) {
				$event  = ! empty( $json['event'] ) ? $json['event'] : '';
				$object = isset( $json['payload']['object'] ) ? $json['payload']['object'] : array();

				if (
					! empty( $event ) &&
					! empty( $object ) &&
					'endpoint.url_validation' !== $event
				) {
					$zoom_meeting_id = ! empty( $object['id'] ) ? $object['id'] : false;
					$zoom_meeting    = BP_Zoom_Meeting::get_meeting_by_meeting_id( $zoom_meeting_id );

					if ( ! empty( $zoom_meeting ) ) {
						$group_id = $zoom_meeting->group_id;
					}
				}
			}

			if (
				! empty( $group_id ) &&
				0 < $group_id &&
				! empty( groups_get_group( $group_id ) )
			) {
				// Validate zoom webhook for groups.
				BP_Zoom_Conference_Api::zoom_webhook_callback( $json, $group_id );
			}
		}
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

	/**
	 * Show a zoom option form when editing a group from admin.
	 *
	 * @since 2.3.91
	 *
	 * @param object|bool $group (the group to edit if in Group Admin UI).
	 */
	public function admin_zoom_settings_screen( $group = false ) {
		$group_id = empty( $group->id ) ? bp_get_new_group_id() : $group->id;

		if ( empty( $group_id ) ) {
			$group_id = bp_get_group_id();
		}

		// Should box be checked already?
		$checked       = bp_zoom_group_is_zoom_enabled( $group_id );
		$notice_exists = get_transient( 'bb_group_zoom_notice_' . $group_id );

		$site_connected_class = 'is-disabled';
		$site_account_email   = esc_html__( 'not connected', 'buddyboss-pro' );
		if ( bb_zoom_is_s2s_connected() ) {
			$site_connected_class = '';
			$site_account_email   = bb_zoom_account_email();
		}
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

			<fieldset>
				<p class="bb-section-info"><?php esc_html_e( 'Create and sync Zoom meetings and webinars directly within this group.', 'buddyboss-pro' ); ?></p>
				<div class="field-group">
					<p class="checkbox bp-checkbox-wrap bp-group-option-enable">
						<input type="checkbox" name="bp-edit-group-zoom" id="bp-edit-group-zoom" class="bs-styled-checkbox" value="1" <?php checked( $checked ); ?> />
						<label for="bp-edit-group-zoom"><span><?php esc_html_e( 'Yes, I want to connect this group to Zoom', 'buddyboss-pro' ); ?></span></label>
					</p>
				</div>
			</fieldset>

			<div id="bp-group-zoom-settings-connection-type" class="group-settings-selections <?php echo ! $checked ? 'bp-hide' : ''; ?>">

				<hr class="bb-sep-line"/>
				<h4 class="bb-section-title"><?php esc_html_e( 'How should this group be connected to Zoom?', 'buddyboss-pro' ); ?></h4>

				<fieldset class="radio group-media">
					<legend class="screen-reader-text"><?php esc_html_e( 'How should this group be connected to Zoom?', 'buddyboss-pro' ); ?></legend>
					<p class="group-setting-label" tabindex="0">
						<?php
						echo sprintf(
						/* translators: %s: Zoom integration tab. */
							esc_html__( 'You can let the group organizers create and connect their own Zoom app to this group, or connect using the app defined in your site’s %s.', 'buddyboss-pro' ),
							sprintf(
								/* translators: 1: Zoom setting url, 2: Zoom setting title  */
								'<a href="%1$s">%2$s</a>',
								esc_url( bp_core_admin_integrations_url( 'bp-zoom' ) ),
								esc_html__( 'Zoom settings', 'buddyboss-pro' )
							)
						);
						?>
					</p>

					<div class="bp-radio-wrap">
						<input type="radio" name="bp-group-zoom-connection-type" id="group-zoom-connection-group" class="bs-styled-radio" value="group"<?php bb_zoom_group_show_connection_setting( 'group', $group_id ); ?> />
						<label for="group-zoom-connection-group"><?php esc_html_e( 'Let the group organizer(s) connect their own Zoom app', 'buddyboss-pro' ); ?></label>
					</div>

					<div class="bp-radio-wrap <?php echo esc_attr( $site_connected_class ); ?>">
						<input type="radio" name="bp-group-zoom-connection-type" id="group-zoom-connection-site" class="bs-styled-radio" value="site"<?php bb_zoom_group_show_connection_setting( 'site', $group_id ); ?> />
						<label for="group-zoom-connection-site">
							<?php
							echo sprintf(
							/* translators: %s: Account Email. */
								esc_html__( 'Use this site’s Zoom app (%s)', 'buddyboss-pro' ),
								esc_html( $site_account_email )
							);
							?>
						</label>
					</div>
				</fieldset>
				<hr class="bb-sep-line"/>
			</div>

			<div id="bp-group-zoom-settings-additional" class="group-settings-selections <?php echo ! $checked ? 'bp-hide' : ''; ?>">
				<h4 class="bb-section-title"><?php esc_html_e( 'Which group members can create, edit and delete Zoom meetings?', 'buddyboss-pro' ); ?></h4>

				<fieldset class="radio group-media">
					<legend class="screen-reader-text"><?php esc_html_e( 'Which group members can create, edit and delete Zoom meetings?', 'buddyboss-pro' ); ?></legend>
					<p class="group-setting-label" tabindex="0"><?php esc_html_e( 'The Zoom account connected to this group will be assigned as the default host for every meeting and webinar, regardless of which member they are created by.', 'buddyboss-pro' ); ?></p>

					<div class="bp-radio-wrap">
						<input type="radio" name="bp-group-zoom-manager" id="group-zoom-manager-admins" class="bs-styled-radio" value="admins"<?php bp_zoom_group_show_manager_setting( 'admins', $group ); ?> />
						<label for="group-zoom-manager-admins"><?php esc_html_e( 'Organizers only', 'buddyboss-pro' ); ?></label>
					</div>

					<div class="bp-radio-wrap">
						<input type="radio" name="bp-group-zoom-manager" id="group-zoom-manager-mods" class="bs-styled-radio" value="mods"<?php bp_zoom_group_show_manager_setting( 'mods', $group ); ?> />
						<label for="group-zoom-manager-mods"><?php esc_html_e( 'Organizers and Moderators only', 'buddyboss-pro' ); ?></label>
					</div>

					<div class="bp-radio-wrap">
						<input type="radio" name="bp-group-zoom-manager" id="group-zoom-manager-members" class="bs-styled-radio" value="members"<?php bp_zoom_group_show_manager_setting( 'members', $group ); ?> />
						<label for="group-zoom-manager-members"><?php esc_html_e( 'All group members', 'buddyboss-pro' ); ?></label>
					</div>
				</fieldset>
			</div>

			<input type="hidden" id="bp-zoom-group-id" value="<?php echo esc_attr( $group_id ); ?>"/>
			<?php wp_nonce_field( 'groups_edit_save_zoom', 'zoom_group_admin_ui' ); ?>
		</div>
		<?php
	}

	/**
	 * Save the admin Group Zoom settings on edit group.
	 *
	 * @since 2.3.91
	 *
	 * @param int $group_id Group ID.
	 */
	public function admin_zoom_settings_screen_save( $group_id = 0 ) {

		// Bail if not a POST action.
		if ( ! bp_is_post_request() ) {
			return;
		}

		// Admin Nonce check.
		check_admin_referer( 'groups_edit_save_zoom', 'zoom_group_admin_ui' );

		$edit_zoom = filter_input( INPUT_POST, 'bp-edit-group-zoom', FILTER_VALIDATE_INT );
		$edit_zoom = ! empty( $edit_zoom ) ? true : false;
		$group_id  = ! empty( $group_id ) ? $group_id : bp_get_current_group_id();

		// Retrieve old settings.
		$old_edit_zoom = (bool) groups_get_groupmeta( $group_id, 'bp-group-zoom' );

		groups_update_groupmeta( $group_id, 'bp-group-zoom', $edit_zoom );

		$is_setting_updated = false;
		if ( $edit_zoom !== $old_edit_zoom ) {
			$is_setting_updated = true;
		}

		if ( $edit_zoom ) {
			$manager         = bb_pro_filter_input_string( INPUT_POST, 'bp-group-zoom-manager' );
			$connection_type = bb_pro_filter_input_string( INPUT_POST, 'bp-group-zoom-connection-type' );

			$manager         = ! empty( $manager ) ? $manager : bp_zoom_group_get_manager( $group_id );
			$connection_type = ! empty( $connection_type ) ? $connection_type : bb_zoom_group_get_connection_type( $group_id );

			// Validate if a default set group does not connect block s2s connection then.
			if (
				! bb_zoom_is_s2s_connected() &&
				'site' === $connection_type
			) {
				$connection_type = 'group';
			}

			// Retrieve old settings.
			$old_manager         = groups_get_groupmeta( $group_id, 'bp-group-zoom-manager' );
			$old_connection_type = groups_get_groupmeta( $group_id, 'bp-group-zoom-connection-type' );

			groups_update_groupmeta( $group_id, 'bp-group-zoom-connection-type', $connection_type );
			groups_update_groupmeta( $group_id, 'bp-group-zoom-manager', $manager );

			if (
				$manager !== $old_manager ||
				$connection_type !== $old_connection_type
			) {
				$is_setting_updated = true;
			}

			// Update the meeting while update the connection type.
			if ( $connection_type !== $old_connection_type ) {

				// Find old account email.
				$old_account_email = '';
				if ( 'site' === $old_connection_type ) {
					$old_account_email = bb_zoom_account_email();
				} elseif ( 'group' === $old_connection_type ) {
					$old_account_email = groups_get_groupmeta( $group_id, 'bb-group-zoom-s2s-api-email' );
				}

				// Find new account email.
				$new_account_email = '';
				if ( ! empty( $connection_type ) ) {
					if ( 'site' === $connection_type ) {
						$new_account_email = bb_zoom_account_email();
					} elseif ( 'group' === $connection_type ) {
						$new_account_email = groups_get_groupmeta( $group_id, 'bb-group-zoom-s2s-api-email' );
					}
				}

				// Hide/Un-hide meetings.
				if (
					! empty( $new_account_email ) &&
					$new_account_email !== $old_account_email
				) {
					bb_zoom_group_hide_unhide_meetings( $group_id, $new_account_email, $old_account_email );
				}
			}
		}

		if ( $is_setting_updated ) {
			set_transient(
				'bb_group_zoom_notice_' . $group_id,
				array(
					'message' => __( 'Group Zoom settings were successfully updated.', 'buddyboss-pro' ),
					'type'    => 'success',
				),
				30
			);
		}

		/**
		 * Add action that fire before user redirect
		 *
		 * @Since 1.0.0
		 *
		 * @param int $group_id Current group id
		 */
		do_action( 'bp_group_admin_after_edit_screen_save', $group_id );
	}
}
