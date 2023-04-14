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
					'ajax_url' => admin_url( 'admin-ajax.php' ),
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
		$bp_zoom_api_key    = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-api-key' );
		$bp_zoom_api_secret = bb_pro_filter_input_string( INPUT_POST, 'bp-zoom-api-secret' );
		$bp_zoom_api_email  = filter_input( INPUT_POST, 'bp-zoom-api-email', FILTER_VALIDATE_EMAIL );

		if ( ! empty( $bp_zoom_api_secret ) && ! empty( $bp_zoom_api_key ) && ! empty( $bp_zoom_api_email ) ) {
			bp_zoom_conference()->zoom_api_key    = $bp_zoom_api_key;
			bp_zoom_conference()->zoom_api_secret = $bp_zoom_api_secret;

			$user_info = bp_zoom_conference()->get_user_info( $bp_zoom_api_email );

			if ( 200 !== $user_info['code'] ) {
				unset( $_POST['bp-zoom-api-email'] ); // phpcs:ignore WordPress.Security.NonceVerification.Missing
				bp_delete_option( 'bp-zoom-api-email' );
				bp_delete_option( 'bp-zoom-api-host' );
				bp_delete_option( 'bp-zoom-api-host-user' );
				bp_delete_option( 'bp-zoom-api-host-user-settings' );
			} else {
				bp_update_option( 'bp-zoom-api-host', $user_info['response']->id );
				bp_update_option( 'bp-zoom-api-host-user', wp_json_encode( $user_info['response'] ) );

				// Get user settings of host user.
				$user_settings = bp_zoom_conference()->get_user_settings( $user_info['response']->id );

				// Save user settings into group meta.
				if ( 200 === $user_settings['code'] && ! empty( $user_settings['response'] ) ) {
					bp_update_option( 'bp-zoom-api-host-user-settings', wp_json_encode( $user_settings['response'] ) );

					if ( isset( $user_settings['response']->feature->webinar ) && true === $user_settings['response']->feature->webinar ) {
						bp_update_option( 'bp-zoom-enable-webinar', true );
					} else {
						bp_delete_option( 'bp-zoom-enable-webinar' );
					}
				} else {
					bp_delete_option( 'bp-zoom-api-host-user-settings' );
					bp_delete_option( 'bp-zoom-enable-webinar' );
				}
			}
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
	 * Get setting sections for zoom integration.
	 *
	 * @since 1.0.0
	 *
	 * @return array $settings Settings sections for zoom integration.
	 */
	public function get_settings_sections() {

		if ( version_compare( BP_PLATFORM_VERSION, '1.5.7.3', '<=' ) ) {
			$settings = array(
				'bp_zoom_settings_section'  => array(
					'page'  => 'zoom',
					'title' => __( 'Zoom Settings', 'buddyboss-pro' ),
				),
				'bp_zoom_gutenberg_section' => array(
					'page'  => 'zoom',
					'title' => __( 'Zoom Gutenberg Blocks', 'buddyboss-pro' ),
				),
			);
		} else {
			$settings = array(
				'bp_zoom_settings_section'  => array(
					'page'              => 'zoom',
					'title'             => __( 'Zoom Settings', 'buddyboss-pro' ),
					'tutorial_callback' => 'bp_zoom_settings_tutorial',
				),
				'bp_zoom_gutenberg_section' => array(
					'page'              => 'zoom',
					'title'             => __( 'Zoom Gutenberg Blocks', 'buddyboss-pro' ),
					'tutorial_callback' => 'bp_zoom_settings_tutorial',
				),
			);
		}

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

		$fields['bp_zoom_settings_section'] = array(
			'bp-zoom-enable' => array(
				'title'             => __( 'Enable Zoom', 'buddyboss-pro' ),
				'callback'          => 'bp_zoom_settings_callback_enable_field',
				'sanitize_callback' => 'string',
				'args'              => array(),
			),
		);

		if ( bp_zoom_is_zoom_enabled() ) {
			$fields['bp_zoom_gutenberg_section']['bp-zoom-api-key'] = array(
				'title'             => __( 'Zoom API Key', 'buddyboss-pro' ),
				'callback'          => 'bp_zoom_settings_callback_api_key_field',
				'sanitize_callback' => 'string',
				'args'              => array(),
			);

			$fields['bp_zoom_gutenberg_section']['bp-zoom-api-secret'] = array(
				'title'             => __( 'Zoom API Secret', 'buddyboss-pro' ),
				'callback'          => 'bp_zoom_settings_callback_api_secret_field',
				'sanitize_callback' => 'string',
				'args'              => array(),
			);

			$fields['bp_zoom_gutenberg_section']['bp-zoom-api-email'] = array(
				'title'             => __( 'Zoom Account Email', 'buddyboss-pro' ),
				'callback'          => 'bp_zoom_settings_callback_api_email_field',
				'sanitize_callback' => 'string',
				'args'              => array(),
			);

			if ( ! empty( bp_zoom_api_key() ) && ! empty( bp_zoom_api_secret() ) && ! empty( bp_zoom_api_email() ) ) {
				$fields['bp_zoom_gutenberg_section']['bp_zoom_api_check_connection'] = array(
					'title'    => __( '&#160;', 'buddyboss-pro' ),
					'callback' => 'bp_zoom_api_check_connection_button',
				);
			}

			if ( bp_is_active( 'groups' ) ) {
				$fields['bp_zoom_settings_section']['bp-zoom-enable-groups'] = array(
					'title'             => __( 'Social Groups', 'buddyboss-pro' ),
					'callback'          => 'bp_zoom_settings_callback_groups_enable_field',
					'sanitize_callback' => 'absint',
					'args'              => array(),
				);
			}

			$fields['bp_zoom_settings_section']['bp-zoom-hide-zoom-urls'] = array(
				'title'             => __( 'Private Meeting URLs', 'buddyboss-pro' ),
				'callback'          => 'bp_zoom_settings_callback_hide_zoom_urls_field',
				'sanitize_callback' => 'absint',
				'args'              => array(),
			);

			if ( bp_zoom_is_zoom_webinar_enabled() ) {
				$fields['bp_zoom_settings_section']['bp-zoom-hide-zoom-webinar-urls'] = array(
					'title'             => __( 'Private Webinar URLs', 'buddyboss-pro' ),
					'callback'          => 'bp_zoom_settings_callback_hide_zoom_webinar_urls_field',
					'sanitize_callback' => 'absint',
					'args'              => array(),
				);
			}

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

			if ( version_compare( BP_PLATFORM_VERSION, '1.5.7.3', '<=' ) ) {
				$fields['bp_zoom_settings_section']['bp_zoom_settings_tutorial'] = array(
					'title'    => __( '&#160;', 'buddyboss-pro' ),
					'callback' => 'bp_zoom_settings_tutorial',
				);
			}
		}

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
		if ( ! bp_zoom_is_zoom_enabled() ) {
			return;
		}

		bp_zoom_pro_core_install_zoom_integration();
	}
}
