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

		$bb_onesignal_auth_key = bb_pro_filter_input_string( INPUT_POST, 'bb-onesignal-auth-key' );
		$bb_onesignal_app_name = bb_pro_filter_input_string( INPUT_POST, 'bb-onesignal-connected-app-name' );
		$bb_onesignal_new_app  = filter_input( INPUT_POST, 'bb-onesignal-new-app', FILTER_VALIDATE_INT );

		bp_update_option( 'bb-onesignal-connected-app-name', $bb_onesignal_app_name );

		if ( ! empty( $bb_onesignal_auth_key ) ) {

			$args = array(
				'sslverify' => false,
				'headers'   => array( 'Authorization' => 'Basic ' . $bb_onesignal_auth_key ),
			);

			$request       = bbpro_remote_get( bb_onesignal_api_endpoint() . 'apps', $args );
			$response      = wp_remote_retrieve_body( $request );
			$response      = json_decode( $response, true );
			$response_code = wp_remote_retrieve_response_code( $request );

			if ( 200 === $response_code ) {
				bp_update_option( 'bb-onesignal-account-apps', $response );
				bp_update_option( 'bb-onesignal-authenticated', true );
			} elseif ( isset( $response['errors'] ) ) {
				$error = sprintf(
					/* translators: Error Message. */
					__( 'There was a problem connecting to your OneSignal account: %s', 'buddyboss-pro' ),
					'[' . ( is_array( $response['errors'] ) ? esc_html( implode( '<br/>', $response['errors'] ) ) : $response['errors'] ) . ']'
				);

				set_transient( 'bb_onesignal_error', $error, HOUR_IN_SECONDS );
				bp_delete_option( 'bb-onesignal-connected-app' );
				bp_delete_option( 'bb-onesignal-connected-app-name' );
				bp_delete_option( 'bb-onesignal-connected-app-details' );
				bp_update_option( 'bb-onesignal-account-apps', array() );
				bp_update_option( 'bb-onesignal-authenticated', false );
			} else {
				bp_delete_option( 'bb-onesignal-connected-app' );
				bp_delete_option( 'bb-onesignal-connected-app-name' );
				bp_delete_option( 'bb-onesignal-connected-app-details' );
				bp_update_option( 'bb-onesignal-account-apps', array() );
				bp_update_option( 'bb-onesignal-authenticated', false );
			}

			if ( ! empty( $bb_onesignal_new_app ) ) {

				$url      = wp_parse_url( site_url() );
				$site_url = ( isset( $url['scheme'] ) && $url['host'] ) ? $url['scheme'] . '://' . $url['host'] : site_url();

				$fields = array(
					'name'               => get_bloginfo( 'name' ),
					'site_name'          => str_replace( ' ', '_', get_bloginfo( 'name' ) ),
					'chrome_web_origin'  => $site_url,
					'safari_site_origin' => $site_url,
				);

				if ( bb_onesignal_soft_prompt_image() ) {
					$fields['chrome_web_default_notification_icon'] = bb_onesignal_soft_prompt_image();
				}

				$args['body']  = $fields;
				$request       = bbpro_remote_post( bb_onesignal_api_endpoint() . 'apps', $args );
				$response      = wp_remote_retrieve_body( $request );
				$response      = json_decode( $response, true );
				$response_code = wp_remote_retrieve_response_code( $request );

				if ( 200 === $response_code ) {

					$fetch_apps_request       = bbpro_remote_get( bb_onesignal_api_endpoint() . 'apps', $args );
					$fetch_apps_response      = wp_remote_retrieve_body( $fetch_apps_request );
					$fetch_apps_response      = json_decode( $fetch_apps_response, true );
					$fetch_apps_response_code = wp_remote_retrieve_response_code( $fetch_apps_request );

					if ( 200 === $fetch_apps_response_code && ! empty( $fetch_apps_response ) ) {
						bp_update_option( 'bb-onesignal-account-apps', $fetch_apps_response );
					} else {
						bp_update_option( 'bb-onesignal-account-apps', array( $response ) );
					}

					bp_update_option( 'bb-onesignal-connected-app', $response['id'] );
					bp_update_option( 'bb-onesignal-connected-app-details', $response );
					bp_update_option( 'bb-onesignal-connected-app-name', $response['name'] );
				} elseif ( isset( $response['errors'] ) ) {
					$error = sprintf(
					/* translators: Error Message. */
						__( 'There was a problem creating a OneSignal app: %s', 'buddyboss-pro' ),
						'[' . ( is_array( $response['errors'] ) ? esc_html( implode( '<br/>', $response['errors'] ) ) : $response['errors'] ) . ']'
					);
					set_transient( 'bb_onesignal_error', $error, HOUR_IN_SECONDS );
					bp_delete_option( 'bb-onesignal-connected-app' );
					bp_delete_option( 'bb-onesignal-connected-app-name' );
					bp_delete_option( 'bb-onesignal-connected-app-details' );
					bp_update_option( 'bb-onesignal-account-apps', array() );
				} else {
					bp_delete_option( 'bb-onesignal-connected-app' );
					bp_delete_option( 'bb-onesignal-connected-app-name' );
					bp_delete_option( 'bb-onesignal-connected-app-details' );
					bp_update_option( 'bb-onesignal-account-apps', array() );
				}
			}

			bb_onesignal_update_app_details();

		} else {
			bp_delete_option( 'bb-onesignal-connected-app' );
			bp_delete_option( 'bb-onesignal-connected-app-name' );
			bp_delete_option( 'bb-onesignal-account-apps' );
			bp_delete_option( 'bb-onesignal-connected-app-details' );
			bp_update_option( 'bb-onesignal-authenticated', false );
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
					'ajax_url'          => admin_url( 'admin-ajax.php' ),
					'are_you_sure'      => __( 'Are you sure?', 'buddyboss-pro' ),
					'soft_prompt_image' => array(
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

		if ( bbp_pro_is_license_valid() ) {
			$status      = 'not-connected';
			$status_text = __( 'Not Connected', 'buddyboss-pro' );

			if (
				'bb-onesignal' === bp_core_get_admin_active_tab() &&
				bb_onesignal_auth_key() &&
				bb_onesignal_account_apps() &&
				bb_onesignal_connected_app() &&
				bb_onesignal_connected_app_name()
			) {
				bb_onesignal_update_app_details();
			}

			if ( ! empty( bb_onesignal_auth_key() ) ) {
				$status      = 'error-connected';
				$status_text = __( 'Not Connected', 'buddyboss-pro' );
			}

			if (
				bb_onesignal_auth_key() &&
				bb_onesignal_account_apps() &&
				bb_onesignal_connected_app() &&
				bb_onesignal_connected_app_name() &&
				! empty( bb_onesignal_connected_app_details() )
			) {
				$status      = 'connected';
				$status_text = __( 'Connected', 'buddyboss-pro' );
				delete_transient( 'bb_onesignal_error' );
				delete_transient( 'bb_onesignal_warning' );
			} elseif (
				bb_onesignal_auth_key() &&
				true === (bool) bp_get_option( 'bb-onesignal-authenticated', false ) &&
				(
					! bb_onesignal_connected_app() ||
					! bb_onesignal_connected_app_name() ||
					empty( bb_onesignal_connected_app_details() )
				)
			) {
				$status      = 'warn-connected';
				$status_text = __( 'Connected', 'buddyboss-pro' );
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
			'bb-onesignal-auth-key'      => array(
				'title'             => __( 'OneSignal Auth Key', 'buddyboss-pro' ),
				'callback'          => array( $this, 'settings_callback_auth_key_field' ),
				'sanitize_callback' => 'string',
				'args'              => array(),
			),
		);

		if (
			! empty( bb_onesignal_auth_key() ) &&
			true === (bool) bp_get_option( 'bb-onesignal-authenticated', false )
		) {
			$fields['bb_onesignal_settings_section']['bb-onesignal-connected-app'] = array(
				'title'             => __( 'Select App', 'buddyboss-pro' ),
				'callback'          => array( $this, 'settings_callback_connected_app' ),
				'sanitize_callback' => 'string',
				'args'              => array(),
			);
		}

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

		$errors  = get_transient( 'bb_onesignal_error' );
		$warning = get_transient( 'bb_onesignal_warning' );
		if ( ! empty( $errors ) ) {
			echo '<div class="bbpro-onesignal-errors bb-error-section">' .
				( is_array( $errors ) ? esc_html( implode( '<br/>', $errors ) ) : $errors ) .
				'</div>';

			delete_transient( 'bb_onesignal_error' );
		}
		if ( ! empty( $warning ) ) {
			echo '<div class="bbpro-onesignal-warning bb-warning-section">' .
				( is_array( $warning ) ? esc_html( implode( '<br/>', $warning ) ) : $warning ) .
				'</div>';

			delete_transient( 'bb_onesignal_warning' );
		}
	}

	/**
	 * Callback function for auth key in OneSignal integration.
	 *
	 * @since 2.0.3
	 */
	public function settings_callback_auth_key_field() {
		?>
		<input name="bb-onesignal-auth-key" id="bb-onesignal-auth-key" type="text" value="<?php echo esc_attr( bb_onesignal_auth_key() ); ?>" aria-label="<?php esc_html_e( 'OneSignal Auth Key', 'buddyboss-pro' ); ?>"/>
		<?php /* translators: %s is the BuddyBoss marketplace link. */ ?>
		<p class="description"><?php printf( esc_html__( 'To use OneSignal for web push notifications, enter the %1$s from your %2$s. After saving, an app will be automatically configured in your OneSignal account.', 'buddyboss-pro' ), '<b>' . esc_html__( 'Auth Key', 'buddyboss-pro' ) . '</b>', '<a href="https://app.onesignal.com/" target="_blank">' . esc_html__( 'OneSignal account', 'buddyboss-pro' ) . '</a>' ); ?></p>
		<?php
	}

	/**
	 * Callback function for APIs in OneSignal integration.
	 *
	 * @since 2.0.3
	 */
	public function settings_callback_connected_app() {
		$apps      = bb_onesignal_account_apps();
		$connected = bb_onesignal_connected_app();
		$app_name  = bb_onesignal_connected_app_name();

		?>
		<select name="bb-onesignal-connected-app" id="bb-onesignal-connected-app" <?php disabled( empty( $apps ) ); ?>>
			<?php
			if ( ! empty( $apps ) ) {
				echo '<option data-name="" value="">' . esc_html__( '-- Select App --', 'buddyboss-pro' ) . '</option>';
				foreach ( $apps as $app ) {
					echo '<option data-name="' . esc_attr( $app['name'] ) . '" value="' . esc_attr( $app['id'] ) . '" ' . selected( $connected, $app['id'], false ) . '>' . esc_html( $app['name'] ) . '</option>';
				}
			} else {
				echo '<option data-name="" value="">' . esc_html__( 'No Apps Found', 'buddyboss-pro' ) . '</option>';
			}
			?>
		</select>
		<input type="hidden" name="bb-onesignal-connected-app-name" value="<?php echo esc_attr( ( empty( $app_name ) && ! empty( $apps ) ) ? current( $apps )['name'] : $app_name ); ?>"/>
		<?php

		if ( ! empty( $connected ) && ! empty( $app_name ) ) {
			echo '<a target="_blank" class="bbpro-onesignal-app-link button button-secondary" href="' . esc_url( 'https://app.onesignal.com/apps/' . $connected ) . '">' . esc_html__( 'View App', 'buddyboss-pro' ) . '</a>';
		}
		?>
		<p class="description">
			<?php printf( esc_html__( 'Select the app from your OneSignal app to use for sending web push notifications. To create a new app with the required settings pre-defined, click %s.', 'buddyboss-pro' ), '<strong>' . esc_html__( 'Create New App', 'buddyboss-pro' ) . '</strong>' ); ?>
		</p>

		<p class="bb-onesignal-new-app-wrap">
			<input type="hidden" name="bb-onesignal-new-app" value="0" id="bb-onesignal-new-app"/>
			<button type="submit" id="bb-onesignal-create-new-app" name="submit" class="button button-secondary"><?php esc_html_e( 'Create New App', 'buddyboss-pro' ); ?></button>
		</p>
		<?php
	}
}
