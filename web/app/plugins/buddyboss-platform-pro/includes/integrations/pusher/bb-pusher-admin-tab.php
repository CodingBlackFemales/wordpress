<?php
/**
 * Pusher integration admin tab
 *
 * @since   2.1.6
 * @package BuddyBossPro\Integration\Pusher
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Setup Pusher integration admin tab class.
 *
 * @since 2.1.6
 */
class BB_Pusher_Admin_Integration_Tab extends BP_Admin_Integration_tab {

	/**
	 * Current section.
	 *
	 * @var $current_section
	 */
	protected $current_section;

	/**
	 * Setup public cluster list.
	 *
	 * @var array
	 */
	protected $public_cluster = array();

	/**
	 * Initialize
	 *
	 * @since 2.1.6
	 */
	public function initialize() {
		$this->tab_order       = 48;
		$this->current_section = 'bb_pusher-integration';
		$this->intro_template  = $this->root_path . '/templates/admin/integration-tab-intro.php';

		add_filter( 'bb_admin_icons', array( $this, 'admin_setting_icons' ), 10, 2 );

		$this->public_cluster = array(
			'mt1'    => __( 'mt1 (N. Virginia)', 'buddyboss-pro' ),
			'us2'    => __( 'us2 (Ohio)', 'buddyboss-pro' ),
			'us3'    => __( 'us3 (Oregon)', 'buddyboss-pro' ),
			'eu'     => __( 'eu (Ireland)', 'buddyboss-pro' ),
			'ap1'    => __( 'ap1 (Singapore)', 'buddyboss-pro' ),
			'ap2'    => __( 'ap2 (Mumbai)', 'buddyboss-pro' ),
			'ap3'    => __( 'ap3 (Tokyo)', 'buddyboss-pro' ),
			'ap4'    => __( 'ap4 (Sydney)', 'buddyboss-pro' ),
			'sa1'    => __( 'sa1 (São Paulo)', 'buddyboss-pro' ),
			'custom' => __( 'Custom', 'buddyboss-pro' ),
		);
	}

	/**
	 * Pusher Integration is active?
	 *
	 * @since 2.1.6
	 * @return bool
	 */
	public function is_active() {
		return (bool) apply_filters( 'bb_pusher_integration_is_active', true );
	}

	/**
	 * Load the settings html.
	 *
	 * @since 2.1.6
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
	 * @since 2.1.6
	 */
	public function settings_save() {
		$bb_pusher_app_cluster    = bb_pro_filter_input_string( INPUT_POST, 'bb-pusher-app-cluster' );
		$bb_pusher_custom_cluster = bb_pro_filter_input_string( INPUT_POST, 'bb-pusher-app-custom-cluster' );

		if ( 'custom' === $bb_pusher_app_cluster && ! empty( $bb_pusher_custom_cluster ) ) {
			bp_update_option( 'bb-pusher-app-custom-cluster', $bb_pusher_custom_cluster );
		} else {
			bp_delete_option( 'bb-pusher-app-custom-cluster' );
		}

		parent::settings_save();

		bb_pusher_credential_validate();
	}

	/**
	 * Pusher integration tab scripts.
	 *
	 * @since 2.1.6
	 */
	public function register_admin_script() {

		$active_tab = bp_core_get_admin_active_tab();

		if ( 'bb-pusher' === $active_tab ) {
			$min     = ( defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG ) ? '' : '.min';
			$rtl_css = is_rtl() ? '-rtl' : '';
			wp_enqueue_style( 'bb-pusher-admin', bb_pusher_integration_url( '/assets/css/bb-pusher-admin' . $rtl_css . $min . '.css' ), false, bb_platform_pro()->version );
			wp_enqueue_script( 'bb-pusher-admin', bb_pusher_integration_url( '/assets/js/bb-pusher-admin' . $min . '.js' ), array( 'jquery' ), bb_platform_pro()->version, true );
		}

		parent::register_admin_script();
	}

	/**
	 * Register setting fields for pusher integration.
	 *
	 * @since 2.1.6
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
	 * Get setting sections for pusher integration.
	 *
	 * @since 2.1.6
	 *
	 * @return array $settings Settings sections for pusher integration.
	 */
	public function get_settings_sections() {

		$status      = 'not-connected';
		$status_text = __( 'Not Connected', 'buddyboss-pro' );
		$errors      = get_transient( 'bb_pusher_error' );
		$warning     = get_transient( 'bb_pusher_warning' );

		if (
			'bb-pusher' === bp_core_get_admin_active_tab() &&
			bb_pusher_app_id() &&
			bb_pusher_app_key() &&
			bb_pusher_app_secret() &&
			bb_pusher_cluster()
		) {

			if ( bb_pusher_is_enabled() ) {
				$status      = 'connected';
				$status_text = __( 'Connected', 'buddyboss-pro' );
				delete_transient( 'bb_pusher_error' );
				delete_transient( 'bb_pusher_warning' );
			} elseif ( ! empty( $warning ) ) {
				$status      = 'warn-connected';
				$status_text = __( 'Connected', 'buddyboss-pro' );
			} elseif ( ! empty( $errors ) ) {
				$status      = 'error-connected';
				$status_text = __( 'Not Connected', 'buddyboss-pro' );
			}
		}

		$html = '<div class="bb-pusher-status">' .
			'<span class="status-line ' . esc_attr( $status ) . '">' . esc_html( $status_text ) . '</span>' .
		'</div>';

		$settings = array(
			'bb_pusher_settings_section' => array(
				'page'              => 'Pusher',
				'title'             => __( 'Pusher', 'buddyboss-pro' ) . $html,
				'tutorial_callback' => array( $this, 'setting_callback_pusher_tutorial' ),
				'notice'            => __( 'In your app\'s settings, please enable "Client events" and "Authorized connections" for this integration to work correctly.', 'buddyboss-pro' ),
			),
		);

		return $settings;
	}

	/**
	 * Link to Pusher Settings tutorial.
	 *
	 * @since 2.1.6
	 */
	public function setting_callback_pusher_tutorial() {
		?>
		<p>
			<a class="button" href="
			<?php
			echo esc_url(
				bp_get_admin_url(
					add_query_arg(
						array(
							'page'    => 'bp-help',
							'article' => '125826'
						),
						'admin.php'
					)
				)
			);
			?>
			"><?php esc_html_e( 'View Tutorial', 'buddyboss-pro' ); ?></a>
		</p>
		<?php
	}

	/**
	 * Get setting fields for section in pusher integration.
	 *
	 * @since 2.1.6
	 *
	 * @param string $section_id Section ID.
	 *
	 * @return array|false $fields setting fields for section in pusher integration false otherwise.
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
	 * Register setting fields for pusher integration.
	 *
	 * @since 2.1.6
	 *
	 * @return array $fields setting fields for pusher integration.
	 */
	public function get_settings_fields() {

		$fields = array();

		$fields['bb_pusher_settings_section'] = array(
			'bb-pusher-error-handles' => array(
				'title'    => 'Error',
				'callback' => array( $this, 'settings_callback_error_handle' ),
				'args'     => array( 'class' => 'hidden-header' ),
			),
			'information'             => array(
				'title'             => esc_html__( 'Information', 'buddyboss-pro' ),
				'callback'          => array( $this, 'setting_callback_pusher_information' ),
				'sanitize_callback' => 'string',
				'args'              => array( 'class' => 'hidden-header' ),
			),
			'dashboard'               => array(
				'title'             => esc_html__( 'Dashboard', 'buddyboss-pro' ),
				'callback'          => array( $this, 'setting_callback_pusher_dashboard' ),
				'sanitize_callback' => 'string',
				'args'              => array( 'class' => 'hidden-header' ),
			),
			'bb-pusher-app-id'        => array(
				'title'             => __( 'Pusher App ID', 'buddyboss-pro' ),
				'callback'          => array( $this, 'settings_callback_app_id' ),
				'sanitize_callback' => 'string',
				'args'              => array(),
			),
			'bb-pusher-app-key'       => array(
				'title'             => __( 'Pusher App Key', 'buddyboss-pro' ),
				'callback'          => array( $this, 'settings_callback_app_key' ),
				'sanitize_callback' => 'string',
				'args'              => array(),
			),
			'bb-pusher-app-secret'    => array(
				'title'             => __( 'Pusher Secret Key', 'buddyboss-pro' ),
				'callback'          => array( $this, 'settings_callback_app_secret' ),
				'sanitize_callback' => 'string',
				'args'              => array(),
			),
			'bb-pusher-app-cluster'   => array(
				'title'             => __( 'Pusher Cluster', 'buddyboss-pro' ),
				'callback'          => array( $this, 'settings_callback_app_cluster' ),
				'sanitize_callback' => 'string',
				'args'              => array(),
			),
		);

		if ( bb_pusher_is_enabled() ) {
			$fields['bb_pusher_settings_section']['bb-pusher-enabled-features'] = array(
				'title'             => __( 'Enable Features', 'buddyboss-pro' ),
				'callback'          => array( $this, 'settings_callback_enable_features' ),
				'sanitize_callback' => 'array',
				'args'              => array(),
			);
		}

		return $fields;
	}

	/**
	 * Render errors coming from pusher.
	 *
	 * @since 2.1.6
	 *
	 * @return void
	 */
	public function settings_callback_error_handle() {
		$errors  = get_transient( 'bb_pusher_error' );
		$warning = get_transient( 'bb_pusher_warning' );
		if ( ! empty( $errors ) ) {
			echo '<div class="bbpro-pusher-errors show-full-width bb-error-section">' .
				( is_array( $errors ) ? esc_html( implode( '<br/>', $errors ) ) : $errors ) .
			'</div>';

			delete_transient( 'bb_pusher_error' );
		}
		if ( ! empty( $warning ) ) {
			echo '<div class="bbpro-pusher-warning show-full-width bb-warning-section">' .
				( is_array( $warning ) ? esc_html( implode( '<br/>', $warning ) ) : $warning ) .
			'</div>';

			delete_transient( 'bb_pusher_warning' );
		}
	}

	/**
	 * Callback fields for pusher information.
	 *
	 * @since 2.1.6
	 *
	 * @return void
	 */
	public function setting_callback_pusher_information() {
		echo '<div class="show-full-width">' .
			sprintf(
				/* translators: pusher channels link */
				esc_html__( 'The BuddyBoss Platform has an integration with %s, a WebSocket service which can power realtime features on your BuddyBoss community such as live messaging.', 'buddyboss-pro' ),
				'<a href="https://pusher.com/channels" target="_blank">' . esc_html__( 'Pusher Channels', 'buddyboss-pro' ) . '</a>'
			) .
		'</div>';
	}

	/**
	 * Callback fields for pusher dashboard information.
	 *
	 * @since 2.1.6
	 *
	 * @return void
	 */
	public function setting_callback_pusher_dashboard() {
		echo '<div class="show-full-width">' .
			sprintf(
				/* translators: 1. pusher channels dashboard link 2. App Keys text */
				esc_html__( 'After creating your app in your Pusher Channels %1$s, enter the %2$s below to connect it to this site.', 'buddyboss-pro' ),
				'<a href="https://dashboard.pusher.com/channels" target="_blank">' . esc_html__( 'account', 'buddyboss-pro' ) . '</a>',
				'<strong>' . esc_html__( 'App Keys', 'buddyboss-pro' ) . '</strong>'
			) .
		'</div>';
	}

	/**
	 * Callback function for app id in Pusher integration.
	 *
	 * @since 2.1.6
	 */
	public function settings_callback_app_id() {
		?>
		<input name="bb-pusher-app-id" id="bb-pusher-app-id" type="text" value="<?php echo esc_attr( bb_pusher_app_id() ); ?>" aria-label="<?php esc_html_e( 'Pusher App ID', 'buddyboss-pro' ); ?>" required />
		<?php
	}

	/**
	 * Callback function for app key in Pusher integration.
	 *
	 * @since 2.1.6
	 */
	public function settings_callback_app_key() {
		?>
		<div class="password-toggle">
			<input name="bb-pusher-app-key" id="bb-pusher-app-key" type="password" value="<?php echo esc_attr( bb_pusher_app_key() ); ?>" aria-label="<?php esc_html_e( 'Pusher App Key', 'buddyboss-pro' ); ?>" required />
			<button type="button" class="button button-secondary bb-hide-pw hide-if-no-js" data-toggle="0">
				<span class="bb-icon bb-icon-eye-small" aria-hidden="true"></span>
			</button>
		</div>
		<?php
	}

	/**
	 * Callback function for app secret in Pusher integration.
	 *
	 * @since 2.1.6
	 */
	public function settings_callback_app_secret() {
		?>
		<div class="password-toggle">
			<input name="bb-pusher-app-secret" id="bb-pusher-app-secret" type="password" value="<?php echo esc_attr( bb_pusher_app_secret() ); ?>" aria-label="<?php esc_html_e( 'Pusher App Secret', 'buddyboss-pro' ); ?>" required />
			<button type="button" class="button button-secondary bb-hide-pw hide-if-no-js" data-toggle="0">
				<span class="bb-icon bb-icon-eye-small" aria-hidden="true"></span>
			</button>
		</div>
		<?php
	}

	/**
	 * Callback function for app cluster in Pusher integration.
	 *
	 * @since 2.1.6
	 */
	public function settings_callback_app_cluster() {
		$cluster           = bb_pusher_app_cluster();
		$is_custom_cluster = 'custom' === $cluster;
		if ( ! empty( $this->public_cluster ) ) {
			?>
			<select name="bb-pusher-app-cluster" id="bb-pusher-app-cluster">
				<?php
				foreach ( $this->public_cluster as $k => $val ) {
					echo '<option value="' . esc_attr( $k ) . '" ' . selected( $cluster, $k, false ) . '>' . wp_kses_post( $val ) . '</option>';
				}
				?>
			</select>
			<?php
		}
		?>
		<div class="custom-cluster <?php echo false === $is_custom_cluster ? 'bp-hide' : ''; ?>">
			<label for="bb-pusher-app-custom-cluster" class="description label_header"><?php esc_html_e( 'Cluster Name', 'buddyboss-pro' ); ?></label>
			<input name="bb-pusher-app-custom-cluster" id="bb-pusher-app-custom-cluster" type="text" value="<?php echo esc_attr( bb_pusher_app_custom_cluster() ); ?>" aria-label="<?php esc_html_e( 'Cluster Name', 'buddyboss-pro' ); ?>" <?php echo $is_custom_cluster ? 'required="required"' : ''; ?> />
		</div>
		<?php
	}

	/**
	 * Features list setting for the pusher.
	 *
	 * @since 2.1.6
	 *
	 * @return void
	 */
	public function settings_callback_enable_features() {
		$pusher_features = bb_get_pusher_features();

		if ( ! empty( $pusher_features ) ) {
			foreach ( $pusher_features as $key => $feature ) {
				$feature = bp_parse_args(
					$feature,
					array(
						'value'       => 1,
						'component'   => '',
						'disabled'    => false,
						'label'       => '',
						'description' => '',
					)
				);

				$checked = bb_pusher_is_feature_enabled( $key );
				?>
				<div class="checkbox-wrap">
					<input id="bb-feature-<?php echo esc_attr( $key ); ?>" name="bb-pusher-enabled-features[<?php echo esc_attr( $key ); ?>]" type="checkbox" value="<?php echo esc_attr( $feature['value'] ); ?>"
						<?php
							disabled( (bool) $feature['disabled'] );
							checked( $checked );
						?>
						/>
					<label for="bb-feature-<?php echo esc_attr( $key ); ?>"><?php echo wp_kses_post( $feature['label'] ); ?></label>
					<p class="description"><?php echo wp_kses_post( $feature['description'] ); ?></p>
				</div>
				<?php
			}
		}
	}

	/**
	 * Added icon for the pusher admin settings.
	 *
	 * @since 2.1.6
	 *
	 * @param string $meta_icon Icon class.
	 * @param string $id        Section ID.
	 *
	 * @return mixed|string
	 */
	public function admin_setting_icons( $meta_icon, $id = '' ) {
		if ( 'bb_pusher_settings_section' === $id ) {
			$meta_icon = 'bb-icon-bf  bb-icon-brand-pusher';
		}

		return $meta_icon;
	}
}
