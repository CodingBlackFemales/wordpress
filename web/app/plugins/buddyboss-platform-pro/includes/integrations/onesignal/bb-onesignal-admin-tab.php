<?php
/**
 * OneSignal integration admin tab
 *
 * @since   2.0.3
 * @package BuddyBossPro/Integration/OneSignal
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup OneSignal integration admin tab class.
 *
 * @since 2.0.3
 */
class BB_OneSignal_Admin_Integration_Tab extends BP_Admin_Integration_tab {

	/**
	 * Current section.
	 *
	 * @var $current_section
	 */
	protected $current_section;

	/**
	 * Initialize
	 *
	 * @since 2.0.3
	 */
	public function initialize() {
		$this->tab_order       = 45;
		$this->current_section = 'bb_onesignal-integration';
		$this->intro_template  = $this->root_path . '/templates/admin/integration-tab-intro.php';

		add_filter( 'bb_admin_icons', array( $this, 'admin_setting_icons' ), 10, 2 );
		add_action( 'admin_notices', array( $this, 'bb_onesignal_site_notice' ) );
		add_filter( 'bb_pro_admin_localize_options', array( $this, 'bb_onesignal_admin_localize_options' ), 10, 1 );
	}

	/**
	 * OneSignal Integration is active?
	 *
	 * @since 2.0.3
	 * @return bool
	 */
	public function is_active() {
		return (bool) apply_filters( 'bb_onesignal_integration_is_active', true );
	}

	/**
	 * Load the settings html.
	 *
	 * @since 2.0.3
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
	 * Method to save the fields.
	 *
	 * @since 2.0.3
	 */
	public function settings_save() {
		parent::settings_save();

		$bb_onesignal_app_id       = bb_pro_filter_input_string( INPUT_POST, 'bb-onesignal-app-id' );
		$bb_onesignal_rest_api_key = bb_pro_filter_input_string( INPUT_POST, 'bb-onesignal-rest-api' );

		bb_onesignal_update_settings(
			array(
				'app_id'          => $bb_onesignal_app_id,
				'rest_api_key'    => $bb_onesignal_rest_api_key,
				'is_connected'    => false,
				'app_name'        => '',
				'app_details'     => array(),
				'warnings'        => array(),
				'errors'          => array(),
				'sidewide_errors' => array(),
			)
		);

		if ( ! empty( $bb_onesignal_app_id ) && ! empty( $bb_onesignal_rest_api_key ) ) {
			bb_onesignal_update_app_details();
		}
	}

	/**
	 * OneSignal integration tab scripts.
	 *
	 * @since 2.0.3
	 */
	public function register_admin_script() {

		$active_tab = bp_core_get_admin_active_tab();

		if ( 'bp-notifications' === $active_tab || 'bb-onesignal' === $active_tab ) {
			$min = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
			wp_enqueue_script( 'bb-onesignal-notification-settings', bb_onesignal_integration_url( '/assets/js/bb-onesignal-notification-settings' . $min . '.js' ), array( 'jquery' ), bb_platform_pro()->version, true );
			wp_localize_script(
				'bb-onesignal-notification-settings',
				'bpOneSignalCommonVars',
				array(
					'ajax_url'             => admin_url( 'admin-ajax.php' ),
					'are_you_sure'         => __( 'Are you sure?', 'buddyboss-pro' ),
					'soft_prompt_image'    => array(
						'select_file'       => esc_js( esc_html__( 'No file was uploaded.', 'buddyboss-pro' ) ),
						'file_upload_error' => esc_js( esc_html__( 'There was a problem uploading the soft prompt photo.', 'buddyboss-pro' ) ),
						'feedback_messages' => array(
							1 => esc_html__( 'soft prompt photo was uploaded successfully.', 'buddyboss-pro' ),
							2 => esc_html__( 'There was a problem deleting soft prompt photo. Please try again.', 'buddyboss-pro' ),
							3 => esc_html__( 'soft prompt photo was deleted successfully.', 'buddyboss-pro' ),
						),
						'upload'            => array(
							'nonce'           => wp_create_nonce( 'bp-uploader' ),
							'action'          => 'bp_cover_image_upload',
							'object'          => 'soft-prompt-image',
							'item_id'         => 0,
							'item_type'       => 'default',
							'has_cover_image' => false,
						),
						'remove'            => array(
							'nonce'  => wp_create_nonce( 'bp_delete_cover_image' ),
							'action' => 'bp_cover_image_delete',
							'json'   => true,
						),
					),
				)
			);
		}

		parent::register_admin_script();

	}

	/**
	 * Register setting fields for onesignal integration.
	 *
	 * @since 2.0.3
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
	 * Get setting sections for onesignal integration.
	 *
	 * @since 2.0.3
	 *
	 * @return array $settings Settings sections for onesignal integration.
	 */
	public function get_settings_sections() {

		$html = '';

		if ( bbp_pro_is_license_valid() && 'bb-onesignal' === bp_core_get_admin_active_tab() ) {
			$status      = 'not-connected';
			$status_text = __( 'Not Connected', 'buddyboss-pro' );

			$settings = bb_onesignal_get_settings();

			if (
				bb_onesignal_app_id() &&
				bb_onesignal_rest_api_key() &&
				! empty( $settings['warnings'] )
			) {
				bb_onesignal_update_app_details();
			}

			if ( ! empty( $settings['errors'] ) ) {
				$status      = 'error-connected';
				$status_text = __( 'Not Connected', 'buddyboss-pro' );
			} elseif ( bb_onesignal_app_is_connected() ) {

				$status_text = __( 'Connected', 'buddyboss-pro' );

				if ( ! empty( $settings['warnings'] ) ) {
					$status = 'warn-connected';
				} else {
					$status = 'connected';
				}
			}

			$html .= '<div class="bbpro-onesignal-status">';
			$html .= '<span class="status-line ' . esc_attr( $status ) . '">' . esc_html( $status_text ) . '</span>';
			$html .= '</div>';
		}

		$settings = array(
			'bb_onesignal_settings_section' => array(
				'page'              => 'OneSignal',
				'title'             => __( 'OneSignal', 'buddyboss-pro' ) . $html,
				'tutorial_callback' => 'bb_onesignal_settings_tutorial',
			),
		);

		return $settings;
	}

	/**
	 * Get setting fields for section in onesignal integration.
	 *
	 * @since 2.0.3
	 *
	 * @param string $section_id Section ID.
	 *
	 * @return array|false $fields setting fields for section in onesignal integration false otherwise.
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
	 * Register setting fields for onesignal integration.
	 *
	 * @since 2.0.3
	 *
	 * @return array $fields setting fields for onesignal integration.
	 */
	public function get_settings_fields() {

		$fields = array();

		$fields['bb_onesignal_settings_section'] = array(
			'bb-onesignal-error-handles' => array(
				'title'    => 'Error',
				'callback' => array( $this, 'settings_callback_error_handle' ),
				'args'     => array( 'class' => 'hidden-header' ),
			),
			'bb-onesignal-app-id'        => array(
				'title'             => __( 'OneSignal App ID', 'buddyboss-pro' ),
				'callback'          => array( $this, 'settings_callback_app_id_field' ),
				'sanitize_callback' => 'string',
				'args'              => array(),
			),
			'bb-onesignal-rest-api'      => array(
				'title'             => __( 'Rest API Key', 'buddyboss-pro' ),
				'callback'          => array( $this, 'settings_callback_reset_api_field' ),
				'sanitize_callback' => 'string',
				'args'              => array(),
			),
		);

		return $fields;
	}

	/**
	 * Added icon for the onesignal admin settings.
	 *
	 * @since 2.0.3
	 *
	 * @param string $meta_icon Icon class.
	 * @param string $id        Section ID.
	 *
	 * @return mixed|string
	 */
	public function admin_setting_icons( $meta_icon, $id = '' ) {
		if ( 'bb_onesignal_settings_section' === $id ) {
			$meta_icon = 'bb-icon-bf  bb-icon-brand-onesignal';
		}

		return $meta_icon;
	}

	/**
	 * Render errors coming from onesignal.
	 *
	 * @since 2.0.3
	 *
	 * @return void
	 */
	public function settings_callback_error_handle() {

		/* translators: %s is the BuddyBoss marketplace link. */ ?>
		<div class="full-with-content"><?php printf( esc_html__( 'To use %1$s for web push notifications, create an app in your account and enter the API credentials from the settings below.', 'buddyboss-pro' ), '<a href="https://app.onesignal.com/" target="_blank">' . esc_html__( 'OneSignal', 'buddyboss-pro' ) . '</a>' ); ?></div>

		<?php
		$settings = bb_onesignal_get_settings();

		if ( ! empty( $settings['errors'] ) ) {
			echo '<div class="bbpro-onesignal-errors bb-error-section">' .
				( is_array( $settings['errors'] ) ? wp_kses_post( implode( '<br/>', $settings['errors'] ) ) : $settings['errors'] ) . // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				'</div>';
		}
		if ( ! empty( $settings['warnings'] ) ) {
			echo '<div class="bbpro-onesignal-warning bb-warning-section">' .
				( is_array( $settings['warnings'] ) ? wp_kses_post( implode( '<br/>', $settings['warnings'] ) ) : $settings['warnings'] ) . // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
				'</div>';
		}
	}

	/**
	 * Callback function for app ID in OneSignal integration.
	 *
	 * @since 2.3.40
	 */
	public function settings_callback_app_id_field() {
		?>
		<div class="password-toggle">
			<input name="bb-onesignal-app-id" id="bb-onesignal-app-id" type="password" value="<?php echo esc_attr( bb_onesignal_app_id() ); ?>" aria-label="<?php esc_html_e( 'OneSignal Auth Key', 'buddyboss-pro' ); ?>" required />
			<button type="button" class="button button-secondary bb-hide-pw hide-if-no-js" aria-label="<?php esc_attr_e( 'Toggle', 'buddyboss-pro' ); ?>">
				<span class="bb-icon bb-icon-eye-small" aria-hidden="true"></span>
			</button>
		</div>
		<?php
	}

	/**
	 * Callback function for Rest API key in OneSignal integration.
	 *
	 * @since 2.3.40
	 */
	public function settings_callback_reset_api_field() {
		?>
		<div class="password-toggle">
			<input name="bb-onesignal-rest-api" id="bb-onesignal-rest-api" type="password" value="<?php echo esc_attr( bb_onesignal_rest_api_key() ); ?>" aria-label="<?php esc_html_e( 'Rest API Key', 'buddyboss-pro' ); ?>" required />
			<button type="button" class="button button-secondary bb-hide-pw hide-if-no-js" data-toggle="0">
				<span class="bb-icon bb-icon-eye-small" aria-hidden="true"></span>
			</button>
		</div>
		<?php
	}

	/**
	 * Function to display site wise notice for onesignal.
	 *
	 * @since 2.3.40
	 */
	public function bb_onesignal_site_notice() {
		$settings = bb_onesignal_get_settings();

		if (
			! empty( $settings['sidewide_errors'] ) &&
			in_array( 'upgrade_to_rest_api_key', $settings['sidewide_errors'], true ) &&
			empty( $settings['hide_sidewide_errors'] )
		) {
			printf(
				'<div class="notice notice-info is-dismissible bb-onsignal-dismiss-site-notice"><p>%s</p></div>',
				sprintf(
				/* translators: Onesignal setting page link */
					esc_html__( 'Due to a change by OneSignal, you\'ll need to enter new %s in order to resume sending web push notifications', 'buddyboss-pro' ),
					sprintf(
						'<a href="%1$s">%2$s</a>',
						esc_url( admin_url( 'admin.php?page=bp-integrations&tab=bb-onesignal' ) ),
						esc_html__( 'API keys', 'buddyboss-pro' )
					)
				)
			);
		}
	}

	/**
	 * Function to add localize variable for OneSignal.
	 *
	 * @since 2.3.41
	 *
	 * @param array $args Array of localize options.
	 *
	 * @return array
	 */
	public function bb_onesignal_admin_localize_options( $args ) {
		$args['dismiss_notice_nonce'] = wp_create_nonce( 'bb-pro-onesignal-dismiss-notice' );

		return $args;
	}
}
