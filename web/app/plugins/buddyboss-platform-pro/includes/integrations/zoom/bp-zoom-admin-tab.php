<?php // phpcs:ignore WordPress.Files.FileName.InvalidClassFileName
/**
 * Zoom integration admin tab
 *
 * @package BuddyBossPro/Integration/Zoom
 * @since 1.0.0
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup Zoom integration admin tab class.
 *
 * @since 1.0.0
 */
class BP_Zoom_Admin_Integration_Tab extends BP_Admin_Integration_tab {

	/**
	 * Current section.
	 *
	 * @var $current_section
	 */
	protected $current_section;

	/**
	 * Initialize
	 *
	 * @since 1.0.0
	 */
	public function initialize() {
		$this->tab_order       = 50;
		$this->current_section = 'bp_zoom-integration';
		$this->intro_template  = $this->root_path . '/templates/admin/integration-tab-intro.php';

		// Add zoom settings metabox icons.
		add_filter( 'bb_admin_icons', array( $this, 'bb_zoom_admin_settings_icons' ), 10, 2 );

		add_filter( 'bb_pro_admin_localize_options', array( $this, 'bb_zoom_admin_localize_options' ), 10, 1 );
	}

	/**
	 * Zoom Integration is active?
	 *
	 * @since 1.0.0
	 * @return bool
	 */
	public function is_active() {
		return (bool) apply_filters( 'bp_zoom_integration_is_active', true );
	}

	/**
	 * Load the settings html
	 *
	 * @since 1.0.0
	 */
	public function form_html() {
		// Check license is valid.
		if ( ! bbp_pro_is_license_valid() ) {
			if ( is_file( $this->intro_template ) ) {
				require $this->intro_template;
			}
		} else {
			parent::form_html();
		}
	}

	/**
	 * Zoom Integration tab scripts
	 *
	 * @since 1.0.0
	 */
	public function register_admin_script() {
		if ( 'bp-zoom' === $this->tab_name ) {
			$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
			wp_enqueue_script( 'bp-zoom-meeting-common', bp_zoom_integration_url( '/assets/js/bp-zoom-meeting-common' . $min . '.js' ), array( 'jquery' ), bb_platform_pro()->version, true );
			wp_localize_script(
				'bp-zoom-meeting-common',
				'bpZoomMeetingCommonVars',
				array(
					'ajax_url'            => admin_url( 'admin-ajax.php' ),
					'fetch_account_nonce' => wp_create_nonce( 'fetch-gutenberg-zoom-accounts' ),
				)
			);
		}
		parent::register_admin_script();
	}

	/**
	 * Method to save the fields.
	 *
	 * @since 1.0.0
	 */
	public function settings_save() {

		$bb_zoom = isset( $_POST['bb-zoom'] ) ? map_deep( wp_unslash( $_POST['bb-zoom'] ), 'sanitize_text_field' ) : array(); // phpcs:ignore WordPress.Security.NonceVerification.Missing

		if ( ! empty( $bb_zoom ) ) {

			// Set blank account email while submit the settings details.
			bp_update_option( 'bb-zoom-account-emails', array() );

			$settings = bb_get_zoom_block_settings();

			// Defined null default.
			$bb_zoom['zoom_errors']                = array();
			$bb_zoom['zoom_warnings']              = array();
			$bb_zoom['sidewide_errors']            = array();
			$bb_zoom['account_host']               = '';
			$bb_zoom['account_host_user']          = array();
			$bb_zoom['account_host_user_settings'] = array();
			$bb_zoom['zoom_is_connected']          = false;

			// Validate and save S2S settings.
			if (
				! empty( $bb_zoom['s2s-account-id'] ) &&
				! empty( $bb_zoom['s2s-client-id'] ) &&
				! empty( $bb_zoom['s2s-client-secret'] )
			) {
				$account_email = ! empty( $bb_zoom['account-email'] ) ? $bb_zoom['account-email'] : '';

				$fetch_data = bb_zoom_fetch_account_emails(
					array(
						'account_id'    => $bb_zoom['s2s-account-id'],
						'client_id'     => $bb_zoom['s2s-client-id'],
						'client_secret' => $bb_zoom['s2s-client-secret'],
						'account_email' => $account_email,
						'force_api'     => true,
					)
				);

				if ( is_wp_error( $fetch_data ) ) {
					$bb_zoom['zoom_errors'][] = $fetch_data;
					$bb_zoom['account-email'] = '';
				} elseif ( ! empty( $fetch_data ) && ! is_wp_error( $fetch_data ) ) {
					$bb_zoom['zoom_is_connected'] = true;

					$email = $bb_zoom['account-email'];
					if ( ! array_key_exists( $email, $fetch_data ) ) {
						$bb_zoom['zoom_warnings'][] = new WP_Error( 'email_not_found', __( 'Email not found in Zoom account.', 'buddyboss-pro' ) );
						$bb_zoom['account-email']   = '';
					}

					$bb_zoom['account_host_user']          = get_transient( 'bp_zoom_account_host_user' );
					$bb_zoom['account_host_user_settings'] = get_transient( 'bp_zoom_account_host_user_settings' );
					$is_webinar_enabled                    = get_transient( 'bp_zoom_is_webinar_enabled' );

					// Check webinar is enabled or not.
					if ( true === $is_webinar_enabled ) {
						bp_update_option( 'bp-zoom-enable-webinar', true );
					} else {
						bp_delete_option( 'bp-zoom-enable-webinar' );
					}

					// Delete transient.
					delete_transient( 'bp_zoom_account_host_user' );
					delete_transient( 'bp_zoom_account_host_user_settings' );
					delete_transient( 'bp_zoom_is_webinar_enabled' );

					// Hide/Un-hide group meetings/webinars.
					if (
						isset( $settings['account-email'] ) &&
						! empty( $bb_zoom['account-email'] ) &&
						$settings['account-email'] !== $bb_zoom['account-email']
					) {
						bb_zoom_group_update_site_connection_group_meetings( $bb_zoom['account-email'], $settings['account-email'] );
					}
				}
			} else {

				$all_s2s_blank = false;
				if (
					empty( $bb_zoom['s2s-account-id'] ) &&
					empty( $bb_zoom['s2s-client-id'] ) &&
					empty( $bb_zoom['s2s-client-secret'] )
				) {
					$all_s2s_blank = true;
				}

				if ( ! $all_s2s_blank ) {
					if ( empty( $bb_zoom['s2s-account-id'] ) ) {
						$bb_zoom['zoom_errors'][] = new WP_Error( 'no_zoom_account_id', __( 'The Account ID is required.', 'buddyboss-pro' ) );
					} elseif ( empty( $bb_zoom['s2s-client-id'] ) ) {
						$bb_zoom['zoom_errors'][] = new WP_Error( 'no_zoom_client_id', __( 'The Client ID is required.', 'buddyboss-pro' ) );
					} elseif ( empty( $bb_zoom['s2s-client-secret'] ) ) {
						$bb_zoom['zoom_errors'][] = new WP_Error( 'no_zoom_client_secret', __( 'The Client Secret is required.', 'buddyboss-pro' ) );
					}
				}
			}

			$bb_zoom['zoom_sdk_is_connected'] = false;
			$bb_zoom['zoom_sdk_errors']       = array();
			$bb_zoom['zoom_sdk_warning']      = array();

			// Validate and save Meeting SDK settings.
			if (
				! empty( $bb_zoom['meeting-sdk-client-id'] ) &&
				! empty( $bb_zoom['meeting-sdk-client-secret'] )
			) {
				$validate = bp_zoom_conference()->bb_zoom_validate_meeting_sdk( $bb_zoom['meeting-sdk-client-id'], $bb_zoom['meeting-sdk-client-secret'] );

				if ( true === $validate ) {
					$bb_zoom['zoom_sdk_is_connected'] = true;
				} else {
					$bb_zoom['zoom_sdk_is_connected'] = false;
					if ( is_wp_error( $validate ) ) {
						$bb_zoom['zoom_sdk_errors'][] = $validate;
					}
				}
				delete_transient( 'bb-zoom-meeting-sdk-validate' );
			}

			$settings = bp_parse_args( $bb_zoom, $settings );
			bp_update_option( 'bb-zoom', $settings );
		}

		parent::settings_save();
	}

	/**
	 * Register setting fields for zoom integration.
	 *
	 * @since 1.0.0
	 */
	public function register_fields() {

		$sections = $this->get_settings_sections();

		foreach ( (array) $sections as $section_id => $section ) {

			// Only add section and fields if section has fields.
			$fields = $this->get_settings_fields_for_section( $section_id );

			if ( empty( $fields ) ) {
				continue;
			}

			$section_title     = ! empty( $section['title'] ) ? $section['title'] : '';
			$section_callback  = ! empty( $section['callback'] ) ? $section['callback'] : false;
			$tutorial_callback = ! empty( $section['tutorial_callback'] ) ? $section['tutorial_callback'] : false;
			$notice            = ! empty( $section['notice'] ) ? $section['notice'] : false;

			// Add the section.
			$this->add_section( $section_id, $section_title, $section_callback, $tutorial_callback, $notice );

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
	 * Get setting sections for zoom integration.
	 *
	 * @since 1.0.0
	 *
	 * @return array $settings Settings sections for zoom integration.
	 */
	public function get_settings_sections() {
		$zoom_api_html = '';
		$zoom_sdk_html = '';

		if ( 'bp-zoom' === bp_core_get_admin_active_tab() ) {
			$zoom_api_html = $this->get_connection_label( 's2s' );
			$zoom_sdk_html = $this->get_connection_label( 'sdk' );
		}

		$settings = array(
			'bp_zoom_gutenberg_section' => array(
				'page'              => 'zoom',
				'title'             => __( 'Zoom Gutenberg Blocks', 'buddyboss-pro' ) . $zoom_api_html,
				'tutorial_callback' => 'bp_zoom_browser_settings_tutorial',
			),
			'bp_zoom_browser_section'   => array(
				'page'              => 'zoom',
				'title'             => __( 'Zoom In-Browser Meetings', 'buddyboss-pro' ) . $zoom_sdk_html,
				'tutorial_callback' => 'bp_zoom_browser_settings_tutorial',
				'notice'            => sprintf(
					'<div style="text-align:center">%1$s<br/>%2$s</div>',
					esc_html__( 'For webinars, hosts will be required to join the webinar using the Zoom app and only authenticated users will be able to join.', 'buddyboss-pro' ),
					esc_html__( 'Members will not be able to register for webinars on your site or participate in polls while accessing webinars through their browser.', 'buddyboss-pro' ),
				),
			),
			'bp_zoom_settings_section'  => array(
				'page'              => 'zoom',
				'title'             => __( 'Zoom Settings', 'buddyboss-pro' ),
				'tutorial_callback' => 'bp_zoom_settings_tutorial',
			),
		);

		return $settings;
	}

	/**
	 * Get setting fields for section in zoom integration.
	 *
	 * @param string $section_id Section ID.
	 * @since 1.0.0
	 *
	 * @return array|false $fields setting fields for section in zoom integration false otherwise.
	 */
	public function get_settings_fields_for_section( $section_id = '' ) {

		// Bail if section is empty.
		if ( empty( $section_id ) ) {
			return false;
		}

		$fields = $this->get_settings_fields();
		$fields = isset( $fields[ $section_id ] ) ? $fields[ $section_id ] : false;

		return $fields;
	}

	/**
	 * Register setting fields for zoom integration.
	 *
	 * @since 1.0.0
	 *
	 * @return array $fields setting fields for zoom integration.
	 */
	public function get_settings_fields() {

		$fields = array();

		$fields['bp_zoom_settings_section']['bp-zoom-enable-groups'] = array(
			'title'             => __( 'Social Groups', 'buddyboss-pro' ),
			'callback'          => 'bp_zoom_settings_callback_groups_enable_field',
			'sanitize_callback' => 'absint',
			'args'              => array(),
		);

		$fields['bp_zoom_settings_section']['bp-zoom-enable-recordings'] = array(
			'title'             => __( 'Recordings', 'buddyboss-pro' ),
			'callback'          => 'bp_zoom_settings_callback_recordings_enable_field',
			'sanitize_callback' => 'absint',
			'args'              => array(),
		);

		$fields['bp_zoom_settings_section']['bp-zoom-enable-recordings-links'] = array(
			'title'             => __( 'Recording Links', 'buddyboss-pro' ),
			'callback'          => '__return_true',
			'sanitize_callback' => 'absint',
			'args'              => array(
				'class' => 'hidden',
			),
		);

		// Zoom gutenberg block settings.
		$fields['bp_zoom_gutenberg_section']['bp-zoom-gutenberg-settings'] = array(
			'title'             => esc_html__( 'Zoom Gutenberg Settings', 'buddyboss-pro' ),
			'callback'          => 'bb_zoom_gutenberg_settings_callback',
			'sanitize_callback' => 'string',
			'args'              => array( 'class' => 'hidden-header child-no-padding' ),
		);

		// Zoom browser settings.
		$fields['bp_zoom_browser_section']['bp-zoom-browser-settings'] = array(
			'title'             => esc_html__( 'Zoom Browser Settings', 'buddyboss-pro' ),
			'callback'          => 'bb_zoom_browser_settings_callback',
			'sanitize_callback' => 'string',
			'args'              => array( 'class' => 'hidden-header child-no-padding' ),
		);

		return $fields;
	}

	/**
	 * Settings saved.
	 */
	public function settings_saved() {
		$this->db_install_zoom_meetings();
		parent::settings_saved();
	}

	/**
	 * Install database tables for the Groups zoom meetings.
	 *
	 * @since 1.0.0
	 */
	public function db_install_zoom_meetings() {

		// check zoom enabled.
		if ( ! bp_zoom_is_zoom_setup() ) {
			return;
		}

		bp_zoom_pro_core_install_zoom_integration();
	}

	/**
	 * Function will return icon based on section.
	 *
	 * @since 2.3.91
	 *
	 * @param string $meta_icon Icon of a section.
	 * @param string $id        ID of the section.
	 *
	 * @return string Return icon name.
	 */
	public function bb_zoom_admin_settings_icons( $meta_icon, $id ) {
		$bb_icon_bf = 'bb-icon-bf';

		if ( 'bp_zoom_settings_section' === $id ) {
			$meta_icon = $bb_icon_bf . ' bb-icon-brand-buddyboss';
		} elseif (
			'bp_zoom_gutenberg_section' === $id ||
			'bp_zoom_browser_section' === $id
		) {
			$meta_icon = $bb_icon_bf . ' bb-icon-brand-zoom';
		}

		return $meta_icon;
	}

	/**
	 * Function to return connection label based on settings.
	 *
	 * @since 2.3.91
	 *
	 * @param string $type Type of connection.
	 *
	 * @return string
	 */
	public function get_connection_label( $type = 's2s' ) {
		$settings = bb_get_zoom_block_settings();

		$status      = 'not-connected';
		$status_text = __( 'Not Connected', 'buddyboss-pro' );

		switch ( $type ) {
			case 's2s':
				if ( ! empty( $settings['zoom_errors'] ) ) {
					$status = 'error-connected';
				} elseif (
					! empty( $settings['s2s-account-id'] ) &&
					! empty( $settings['s2s-client-id'] ) &&
					! empty( $settings['s2s-client-secret'] ) &&
					! empty( $settings['zoom_is_connected'] )
				) {
					$status_text = __( 'Connected', 'buddyboss-pro' );
					if (
						! empty( $settings['zoom_warnings'] ) ||
						empty( $settings['account-email'] )
					) {
						$status = 'warn-connected';
					} else {
						$status = 'connected';
					}
				}
				break;

			case 'sdk':
				if ( ! empty( $settings['zoom_sdk_errors'] ) ) {
					$status = 'error-connected';
				} elseif (
					! empty( $settings['meeting-sdk-client-id'] ) &&
					! empty( $settings['meeting-sdk-client-secret'] ) &&
					! empty( $settings['zoom_sdk_is_connected'] )
				) {
					$status_text = __( 'Connected', 'buddyboss-pro' );
					$status      = 'connected';
				}
				break;
		}

		$html  = '<div class="bbpro-zoom-status">';
		$html .= '<span class="status-line ' . esc_attr( $status ) . '">' . esc_html( $status_text ) . '</span>';
		$html .= '</div>';

		return $html;
	}

	/**
	 * Function to add localize variable for Zoom.
	 *
	 * @since 2.3.91
	 *
	 * @param array $args Array of localize options.
	 *
	 * @return array
	 */
	public function bb_zoom_admin_localize_options( $args ) {
		$args['zoom_dismiss_notice_nonce'] = wp_create_nonce( 'bb-pro-zoom-dismiss-notice' );

		return $args;
	}
}
