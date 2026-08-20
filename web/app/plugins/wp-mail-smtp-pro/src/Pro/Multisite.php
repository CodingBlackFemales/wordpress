<?php

namespace WPMailSMTP\Pro;

use WPMailSMTP\Admin\Area;
use WPMailSMTP\Admin\DebugEvents\DebugEvents;
use WPMailSMTP\EmailSendingDebug;
use WPMailSMTP\Options;
use WPMailSMTP\Pro\Tasks\LicenseCheckTask;
use WPMailSMTP\Tasks\NotificationsUpdateTask;
use WPMailSMTP\UsageTracking\SendUsageTask;
use WPMailSMTP\WP;
use WPMailSMTP\Pro\Emails\Logs\Email;

/**
 * Add support for WP Multisite functionality.
 *
 * @since 2.2.0
 */
class Multisite {

	/**
	 * Initialize the functionality.
	 *
	 * @since 2.2.0
	 */
	public function init() {

		// Abort if this is not a multisite WP.
		if ( ! is_multisite() ) {
			return;
		}

		// Remove the lite WPMS settings page (used only for the WPMS product education).
		remove_action( 'network_admin_menu', [ wp_mail_smtp()->get_admin(), 'add_wpms_network_wide_setting_product_education_page' ] );

		// Add the plugin admin pages for WPMS and remove unneeded menu items.
		add_action( 'network_admin_menu', [ wp_mail_smtp()->get_admin(), 'add_admin_options_page' ] );

		// Add the multisite plugin setting.
		add_filter( 'wp_mail_smtp_admin_settings_tab_display', [ $this, 'add_multisite_network_wide_setting' ] );

		// Filter plugin settings save process.
		add_filter( 'wp_mail_smtp_options_set', [ $this, 'multisite_network_wide_filter_options_set' ] );

		// Process the settings tab post submission data.
		add_filter( 'wp_mail_smtp_settings_tab_process_post', [ $this, 'multisite_network_wide_process_settings_tab_post' ] );

		// Filter the core plugin options population.
		add_filter( 'wp_mail_smtp_populate_options', [ $this, 'filter_populate_options' ] );

		// Filter the crypto key option.
		add_filter( 'wp_mail_smtp_helpers_crypto_get_secret_key', [ $this, 'filter_crypto_secret_key' ] );

		// Filter the WP::admin_url.
		add_filter( 'wp_mail_smtp_admin_url', [ $this, 'filter_wp_admin_url' ], 10, 3 );

		// Change Gmail auth redirect URL for network admin (WPMS).
		add_filter( 'wp_mail_smtp_gmail_get_plugin_auth_url', [ $this, 'change_gmail_auth_redirect_url' ] );
		add_filter( 'wp_mail_smtp_pro_providers_gmail_auth_get_plugin_auth_url', [ $this, 'change_gmail_auth_redirect_url' ] );

		// Change Outlook auth redirect URL for network admin (WPMS).
		add_filter( 'wp_mail_smtp_outlook_get_plugin_auth_url', [ $this, 'change_outlook_auth_redirect_url' ] );
		add_filter( 'wp_mail_smtp_pro_providers_outlook_one_click_auth_get_plugin_auth_url', [ $this, 'change_outlook_oneclick_auth_redirect_url' ] );

		// Remove other settings tabs if on network admin and the global settings options is disabled.
		add_filter( 'wp_mail_smtp_admin_get_pages', [ $this, 'maybe_remove_other_setting_tabs' ] );

		// Remove WP update nag on plugin pages.
		add_action( 'admin_init', [ $this, 'remove_wp_update_nag' ] );

		// Maybe change the admin bar menu item link for network-wide setting.
		add_filter( 'wp_mail_smtp_admin_adminbarmenu_main_menu_href', [ $this, 'maybe_change_main_menu_admin_bar_menu_href' ] );

		// Maybe disable notifications for sub-sites.
		add_filter( 'wp_mail_smtp_admin_notifications_has_access', [ $this, 'maybe_disable_notifications' ] );

		// Aggregated network-admin notice for subsites with email sending failures.
		add_action( 'network_admin_notices', [ $this, 'render_aggregated_subsite_errors' ] );

		// AJAX handler for the "Clear these errors" link in the aggregated notice.
		add_action( 'wp_ajax_wp_mail_smtp_pro_multisite_clear_error_notices', [ $this, 'clear_aggregated_error_notices_callback' ] );

		if ( is_network_admin() ) {

			// Enqueue assets.
			add_action( 'wp_mail_smtp_admin_area_enqueue_assets', [ $this, 'enqueue_assets' ] );

			// Add scripts data.
			add_filter( 'wp_mail_smtp_admin_area_enqueue_assets_scripts_data', [ $this, 'scripts_data' ], 10, 2 );
		}

		// Check if on network admin and subsite related request.
		if ( $this->is_network_admin_subsite_related_request() ) {

			// Display network admin email logs site selector.
			add_action(
				'wp_mail_smtp_pro_emails_logs_admin_archive_page_display_header',
				[ $this, 'display_network_admin_site_selector' ]
			);

			// Display network admin email logs export site selector.
			add_action(
				'wp_mail_smtp_admin_page_tools_export_display_header',
				[ $this, 'display_network_admin_site_selector' ]
			);

			// Display network admin email reports site selector.
			add_action(
				'wp_mail_smtp_admin_page_reports_reports_display_header',
				[ $this, 'display_network_admin_site_selector' ]
			);

			// Handle network admin site selector. This option must be saved early.
			$this->handle_network_admin_site_selector();

			// Change network admin edit email log link from network to related site.
			add_filter(
				'wp_mail_smtp_pro_emails_logs_admin_table_get_item_link',
				[ $this, 'network_admin_emails_logs_item_link' ],
				10,
				3
			);

			// Switch blog on email logs page.
			add_action( 'wp-mail-smtp_page_wp-mail-smtp-logs', [ $this, 'switch_blog_to_selection' ], 0 );
			add_action( 'wp-mail-smtp_page_wp-mail-smtp-logs', 'restore_current_blog', PHP_INT_MAX );

			// Switch blog on email logs export page.
			add_action( 'wp-mail-smtp_page_wp-mail-smtp-tools', [ $this, 'switch_blog_to_selection' ], 0 );
			add_action( 'wp-mail-smtp_page_wp-mail-smtp-tools', 'restore_current_blog', PHP_INT_MAX );

			// Switch blog on email reports page.
			add_action( 'wp-mail-smtp_page_wp-mail-smtp-reports', [ $this, 'switch_blog_to_selection' ], 0 );
			add_action( 'wp-mail-smtp_page_wp-mail-smtp-reports', 'restore_current_blog', PHP_INT_MAX );

			// Switch blog on admin actions.
			add_action( 'admin_init', [ $this, 'switch_blog_to_selection' ], 0 );
			add_action( 'admin_init', 'restore_current_blog', PHP_INT_MAX );

			// Switch blog on enqueue assets (mainly for js data objects preparation).
			add_action( 'wp_mail_smtp_admin_area_enqueue_assets', [ $this, 'switch_blog_to_selection' ], 0 );
			add_action( 'wp_mail_smtp_admin_area_enqueue_assets', 'restore_current_blog', PHP_INT_MAX );

			// Filters options.
			add_filter( 'wp_mail_smtp_populate_options', [ $this, 'network_admin_filter_options' ] );
		}

		// Handle network admin subsite related AJAX request.
		if (
			WP::is_doing_self_ajax() &&
			! empty( $_REQUEST['network_admin_subsite_related_request'] ) && // phpcs:ignore WordPress.Security.NonceVerification.Recommended
			current_user_can( 'manage_network' )
		) {
			add_action( 'admin_init', [ $this, 'switch_blog_to_selection' ], 0 );
		}

		// Get network sites ajax handler.
		add_action( 'wp_ajax_wp_mail_smtp_pro_get_sites_ajax', [ $this, 'get_sites_ajax' ] );

		// Maybe remove plugin admin pages for network child sites.
		$this->maybe_remove_plugin_admin_pages_from_child_sites();

		// Fire DB migrations once per day on "init" action instead of "admin_init" for correct DB tables creation on subsites.
		if (
			! WP::in_wp_admin() &&
			! WP::is_doing_ajax() &&
			! is_network_admin() &&
			! wp_doing_cron() &&
			! get_transient( 'wp_mail_smtp_ms_init_migrations_daily' )
		) {
			$migrations = wp_mail_smtp()->get_migrations();

			remove_action( 'admin_init', [ $migrations, 'init_migrations_on_request' ] );
			add_action( 'init', [ $migrations, 'init_migrations_on_request' ] );
			set_transient( 'wp_mail_smtp_ms_init_migrations_daily', true, DAY_IN_SECONDS );
		}

		add_filter( 'wp_mail_smtp_tasks_get_tasks', [ $this, 'unset_global_tasks_on_subsites' ] );
	}

	/**
	 * Enqueue required JS and CSS.
	 *
	 * @since 2.9.0
	 */
	public function enqueue_assets() {

		if ( $this->is_network_admin_subsite_related_request() ) {
			wp_enqueue_style(
				'wp-mail-smtp-admin-select2',
				wp_mail_smtp()->assets_url . '/pro/css/vendor/select2.min.css',
				[],
				'4.0.13'
			);
			wp_enqueue_script(
				'wp-mail-smtp-admin-select2',
				wp_mail_smtp()->assets_url . '/pro/js/vendor/select2.full.min.js',
				[],
				'4.0.13',
				false
			);
			wp_enqueue_script(
				'wp-mail-smtp-admin-select2-enhanced',
				wp_mail_smtp()->assets_url . '/pro/js/smtp-pro-select2.min.js',
				[ 'wp-mail-smtp-admin-select2' ],
				WPMS_PLUGIN_VER,
				false
			);
		}

		wp_enqueue_script(
			'wp-mail-smtp-network-admin',
			wp_mail_smtp()->assets_url . '/pro/js/smtp-pro-network-admin' . WP::asset_min() . '.js',
			[ 'wp-mail-smtp-admin' ],
			WPMS_PLUGIN_VER,
			false
		);
	}

	/**
	 * Plugin scripts data.
	 *
	 * @since 2.9.0
	 *
	 * @param array  $data Data.
	 * @param string $hook Current hook.
	 *
	 * @return array
	 */
	public function scripts_data( $data, $hook ) {

		if ( $this->is_network_admin_subsite_related_request() ) {
			$data['select2'] = [
				'i18n' => [
					'error_loading' => esc_html__( 'The results could not be loaded.', 'wp-mail-smtp-pro' ),
					'loading_more'  => esc_html__( 'Loading more results...', 'wp-mail-smtp-pro' ),
					'no_results'    => esc_html__( 'No results found.', 'wp-mail-smtp-pro' ),
					'searching'     => esc_html__( 'Searching...', 'wp-mail-smtp-pro' ),
				],
			];
		}

		$data['network_subsite_mode'] = $this->is_network_admin_subsite_related_request();

		return $data;
	}

	/**
	 * Add the multisite network_wide plugin setting
	 * HTML output.
	 *
	 * @since 2.2.0
	 *
	 * @param string $settings_output Default HTML output for General settings tab.
	 *
	 * @return string
	 */
	public function add_multisite_network_wide_setting( $settings_output ) {

		// If not on multisite and not on main site, output normal plugin settings.
		if ( ! is_multisite() || ! is_network_admin() ) {
			return $settings_output;
		}

		// Fetch fresh plugin options with updated general->network_wide setting.
		$global_settings_enabled = (bool) Options::init()->get( 'general', 'network_wide' );

		ob_start();
		?>
		<!-- Multisite Section Title -->
		<div class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-content wp-mail-smtp-clear section-heading no-desc" id="wp-mail-smtp-setting-row-multisite-heading">
			<div class="wp-mail-smtp-setting-field">
				<h2><?php esc_html_e( 'Multisite', 'wp-mail-smtp-pro' ); ?></h2>
			</div>
		</div>

		<!-- Network wide setting -->
		<div id="wp-mail-smtp-setting-row-multisite" class="wp-mail-smtp-setting-row wp-mail-smtp-setting-row-multisite wp-mail-smtp-clear">
			<div class="wp-mail-smtp-setting-label">
				<label for="wp-mail-smtp-setting-multisite-settings-control"><?php esc_html_e( 'Settings control', 'wp-mail-smtp-pro' ); ?></label>
			</div>
			<div class="wp-mail-smtp-setting-field">
				<input name="wp-mail-smtp[general][network_wide]" type="checkbox"
					value="true" <?php checked( $global_settings_enabled ); ?>
					id="wp-mail-smtp-setting-network-wide">

				<label for="wp-mail-smtp-setting-network-wide">
					<?php esc_html_e( 'Make the plugin settings global network-wide', 'wp-mail-smtp-pro' ); ?>
				</label>

				<p class="desc">
					<?php esc_html_e( 'If disabled, each subsite of this multisite will have its own WP Mail SMTP settings page that has to be configured separately.', 'wp-mail-smtp-pro' ); ?>
					<br>
					<?php esc_html_e( 'If enabled, these global settings will manage email sending for all subsites of this multisite.', 'wp-mail-smtp-pro' ); ?>
				</p>
			</div>
		</div>

		<?php
		$settings = ob_get_clean();

		if ( $global_settings_enabled ) {
			$settings .= $settings_output;
		}

		return $settings;
	}

	/**
	 * Add network_wide filters for plugin options set.
	 *
	 * @since 2.2.0
	 *
	 * @param array $options Plugin options.
	 *
	 * @return array
	 */
	public function multisite_network_wide_filter_options_set( $options ) {

		if ( isset( $options['general']['network_wide'] ) ) {
			$options['general']['network_wide'] = (bool) $options['general']['network_wide'];
		}

		return $options;
	}

	/**
	 * Process settings tab post submission for the network_wide option.
	 *
	 * @since 2.2.0
	 * @since 2.6.0 Fix checkbox overwriting with default values.
	 *
	 * @param array $data The raw plugin options data.
	 *
	 * @return array
	 */
	public function multisite_network_wide_process_settings_tab_post( $data ) {

		// When checkbox is unchecked - it's not submitted at all, so we need to define its default false value.
		if ( ! isset( $data['general']['network_wide'] ) ) {
			$data['general']['network_wide'] = false;
		}

		/*
		 * The plugin settings state in the SettingsTab::process_post if the network_wide setting is toggled from
		 * disabled to enabled. Just the set network_wide setting and the default false values in
		 * 'mail' and 'smtp' groups are present at that time.
		 */
		$network_wide_enabled_first_time_settings = [
			'general' => [
				'network_wide' => 'true',
			],
			'mail'    => [
				'from_email_force' => false,
				'from_name_force'  => false,
				'return_path'      => true,
			],
			'smtp'    => [
				'autotls' => false,
				'auth'    => false,
			],
		];

		// Maybe revert the default checkbox values of plugin settings when network_wide is switched to enabled state.
		if ( $data === $network_wide_enabled_first_time_settings ) {
			$options = Options::init();
			$old_opt = $options->get_all();

			// Set the old plugin values for the checkboxes that were set to false because they didn't exist in the POST request.
			$data['mail']['from_email_force'] = isset( $old_opt['mail']['from_email_force'] ) ?
				$old_opt['mail']['from_email_force'] : $data['mail']['from_email_force'];
			$data['mail']['from_name_force']  = isset( $old_opt['mail']['from_name_force'] ) ?
				$old_opt['mail']['from_name_force'] : $data['mail']['from_name_force'];
			$data['mail']['return_path']      = isset( $old_opt['mail']['return_path'] ) ?
				$old_opt['mail']['return_path'] : $data['mail']['return_path'];
			$data['smtp']['autotls']          = isset( $old_opt['smtp']['autotls'] ) ?
				$old_opt['smtp']['autotls'] : $data['smtp']['autotls'];
			$data['smtp']['auth']             = isset( $old_opt['smtp']['auth'] ) ?
				$old_opt['smtp']['auth'] : $data['smtp']['auth'];
		}

		return $data;
	}

	/**
	 * Remove plugin settings page from subsites if this is a multisite
	 * and the network_wide option is enabled in the network admin.
	 *
	 * @since 2.2.0
	 */
	public function maybe_remove_plugin_admin_pages_from_child_sites() {

		if ( WP::use_global_plugin_settings() ) {
			add_action(
				'admin_menu',
				function () {
					remove_submenu_page( Area::SLUG, Area::SLUG );

					if ( ! wp_mail_smtp()->pro->get_logs()->is_enabled() ) {
						remove_submenu_page( Area::SLUG, Area::SLUG . '-logs' );
						remove_submenu_page( Area::SLUG, Area::SLUG . '-reports' );
					}

					/*
					 * We can't remove top level page with function remove_menu_page(), because we need to keep it for visible sub-pages.
					 * So, we just restrict access to top level page.
					 */
					add_action(
						'load-' . wp_mail_smtp()->get_admin()->hook,
						function () {
							/* Translators: %s - Network admin email address. */
							wp_die( sprintf( esc_html__( 'Sorry, you are not allowed to access the WP Mail SMTP settings for individual network subsite. The network-wide setting is enabled, so the plugin settings are only accessible in the network admin dashboard. Please contact your network admin: %s.', 'wp-mail-smtp-pro' ), esc_html( get_site_option( 'admin_email' ) ) ), 403 );
						}
					);
				},
				100
			);
		}
	}

	/**
	 * Filter the core plugin options population.
	 * Use the main site options if the network_wide is enabled.
	 *
	 * @since 2.2.0
	 *
	 * @param array $options Default plugin options.
	 *
	 * @return array
	 */
	public function filter_populate_options( $options ) {

		if ( ! WP::use_global_plugin_settings() ) {
			return $options;
		}

		return get_blog_option( get_main_site_id(), Options::META_KEY, [] );
	}

	/**
	 * Filter plugin's wrapper for `admin_url`.
	 * Call `network_admin_url` if on network admin pages.
	 *
	 * @since 2.2.0
	 *
	 * @param string $url    The Admin URL link with optional path appended.
	 * @param string $path   Optional path relative to the admin URL.
	 * @param string $scheme The scheme to use. Default is 'admin', which obeys force_ssl_admin() and is_ssl().
	 *                       'http' or 'https' can be passed to force those schemes.
	 *
	 * @return string
	 */
	public function filter_wp_admin_url( $url, $path, $scheme ) {

		if ( ! is_multisite() || ! is_network_admin() ) {
			return $url;
		}

		return network_admin_url( $path, $scheme );
	}

	/**
	 * Change the Gmail auth redirect URL if this is a WP multisite and
	 * the global network setting is enabled.
	 *
	 * @since 2.2.0
	 *
	 * @param string $url The default Gmail auth redirect URL.
	 *
	 * @return string
	 */
	public function change_gmail_auth_redirect_url( $url ) {

		if ( ! WP::use_global_plugin_settings() ) {
			return $url;
		}

		return add_query_arg(
			[
				'page' => Area::SLUG,
				'tab'  => 'auth',
			],
			WP::admin_url()
		);
	}

	/**
	 * Change the Outlook auth redirect URL if this is a WP multisite and
	 * the global network setting is enabled.
	 *
	 * @since 2.5.2
	 *
	 * @param string $url The default Outlook auth redirect URL.
	 *
	 * @return string
	 */
	public function change_outlook_auth_redirect_url( $url ) {

		if ( ! WP::use_global_plugin_settings() ) {
			return $url;
		}

		return network_admin_url();
	}

	/**
	 * Change the Outlook one-click auth redirect URL if this is a WP multisite and
	 * the global network setting is enabled.
	 *
	 * @since 4.7.0
	 *
	 * @param string $url The default Outlook auth redirect URL.
	 *
	 * @return string
	 */
	public function change_outlook_oneclick_auth_redirect_url( $url ) {

		if ( ! WP::use_global_plugin_settings() ) {
			return $url;
		}

		return add_query_arg(
			[
				'page' => Area::SLUG,
				'tab'  => 'auth',
			],
			WP::admin_url()
		);
	}

	/**
	 * Remove setting tabs (except "General" tab), if on network admin and
	 * the global plugin settings option is disabled.
	 *
	 * @since 2.2.0
	 *
	 * @param array $tabs The default setting tabs.
	 *
	 * @return array
	 */
	public function maybe_remove_other_setting_tabs( $tabs ) {

		if (
			is_network_admin() &&
			! WP::use_global_plugin_settings() &&
			! empty( $tabs['settings'] )
		) {
			return [ 'settings' => $tabs['settings'] ];
		}

		return $tabs;
	}

	/**
	 * Remove unneeded admin menu items for the WP Multisite.
	 *
	 * @deprecated 2.9.0
	 *
	 * @since 2.2.0
	 */
	public function remove_admin_menu_items() {

		_deprecated_function( __METHOD__, '2.9.0' );

		remove_submenu_page( Area::SLUG, Area::SLUG . '-logs' );
	}

	/**
	 * Remove the WP update nag on plugin pages.
	 *
	 * @since 2.2.0
	 */
	public function remove_wp_update_nag() {

		if ( wp_mail_smtp()->get_admin()->is_admin_page() ) {
			remove_action( 'network_admin_notices', 'update_nag', 3 );
		}
	}

	/**
	 * Change the main menu item href in the admin bar menu if the network-wide setting is enabled.
	 *
	 * @since 2.3.0
	 *
	 * @param string $href The default href of the main menu item in the admin bar menu.
	 *
	 * @return string
	 */
	public function maybe_change_main_menu_admin_bar_menu_href( $href ) {

		if ( WP::use_global_plugin_settings() ) {
			return add_query_arg( 'page', Area::SLUG, network_admin_url( 'admin.php' ) );
		}

		return $href;
	}

	/**
	 * Maybe disable notifications for multisite sub-sites.
	 *
	 * @since 2.3.0
	 *
	 * @param bool $access The default notifications access state.
	 *
	 * @return bool
	 */
	public function maybe_disable_notifications( $access ) {

		if (
			WP::use_global_plugin_settings() &&
			! is_network_admin()
		) {
			$access = false;
		}

		return $access;
	}

	/**
	 * Use the global plugin settings crypto secret key if network-wide setting is enabled.
	 *
	 * @since 2.5.3
	 *
	 * @param string $key Default crypto secret key.
	 *
	 * @return string
	 */
	public function filter_crypto_secret_key( $key ) {
		if ( WP::use_global_plugin_settings() ) {
			return get_blog_option( get_main_site_id(), 'wp_mail_smtp_mail_key' );
		}

		return $key;
	}

	/**
	 * Whether network admin subsite related request or not.
	 *
	 * @since 2.9.0
	 *
	 * @return bool
	 */
	public function is_network_admin_subsite_related_request() {

		$current_tab = isset( $_GET['tab'] ) ? sanitize_key( $_GET['tab'] ) : ''; // phpcs:ignore WordPress.Security.NonceVerification.Recommended

		if (
			is_network_admin() &&
			(
				wp_mail_smtp()->get_admin()->is_admin_page( 'logs' ) ||
				( wp_mail_smtp()->get_admin()->is_admin_page( 'tools' ) && $current_tab === 'export' ) ||
				( wp_mail_smtp()->get_admin()->is_admin_page( 'reports' ) && ( $current_tab === '' || $current_tab === 'reports' ) )
			)
		) {
			return true;
		}

		return false;
	}

	/**
	 * Display network admin site selector.
	 *
	 * @since 2.9.0
	 */
	public function display_network_admin_site_selector() {

		$form_action    = wp_mail_smtp()->get_admin()->get_admin_page_url();
		$current_action = current_action();

		if ( $current_action === 'wp_mail_smtp_pro_emails_logs_admin_archive_page_display_header' ) {
			$form_action = wp_mail_smtp()->get_pro()->get_logs()->get_admin_page_url();
		} elseif ( $current_action === 'wp_mail_smtp_admin_page_tools_export_display_header' ) {
			$form_action = wp_mail_smtp()->get_admin()->get_parent_pages()['tools']->get_link( 'export' );
		} elseif ( $current_action === 'wp_mail_smtp_admin_page_reports_reports_display_header' ) {
			$form_action = wp_mail_smtp()->get_admin()->get_parent_pages()['reports']->get_link( 'reports' );
		}

		global $blog_id;
		?>
		<form method="post" action="<?php echo esc_url( $form_action ); ?>" class="wp-mail-smtp-network-admin-site-selector-form">
			<label for="wp-mail-smtp-network-admin-site-selector">
				<?php echo esc_html__( 'Site', 'wp-mail-smtp-pro' ); ?>
			</label>
			<select name="wp_mail_smtp_network_admin_site" class="wp-mail-smtp-network-admin-site-selector" id="wp-mail-smtp-network-admin-site-selector">
				<option value="<?php echo esc_attr( $blog_id ); ?>">
					<?php echo esc_html( get_blog_details( $blog_id )->blogname ); ?>
				</option>
			</select>
		</form>
		<?php
	}

	/**
	 * Handle network admin email logs site selector.
	 *
	 * @since 2.9.0
	 */
	public function handle_network_admin_site_selector() {

		if (
			current_user_can( 'manage_network' ) &&
			isset( $_POST['wp_mail_smtp_network_admin_site'] ) && // phpcs:ignore WordPress.Security.NonceVerification.Missing
			is_numeric( $_POST['wp_mail_smtp_network_admin_site'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
		) {
			update_user_meta(
				get_current_user_id(),
				'wp_mail_smtp_network_admin_site',
				intval( $_POST['wp_mail_smtp_network_admin_site'] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
			);
		}
	}

	/**
	 * Switch to selected subsite.
	 *
	 * @since 2.9.0
	 */
	public function switch_blog_to_selection() {

		$selected_site = get_user_meta( get_current_user_id(), 'wp_mail_smtp_network_admin_site', true );

		if ( ! empty( $selected_site ) && get_blog_details( $selected_site ) !== false ) {
			switch_to_blog( $selected_site );
		}
	}

	/**
	 * Filters network admin plugin options.
	 *
	 * @since 2.9.0
	 *
	 * @param array $options Plugin options.
	 *
	 * @return array
	 */
	public function network_admin_filter_options( $options ) {

		// Skip subsite options change, if network wide setting is enabled.
		if ( WP::use_global_plugin_settings() ) {
			return $options;
		}

		$this->switch_blog_to_selection();
		$options = get_option( Options::META_KEY, [] );
		restore_current_blog();

		return $options;
	}

	/**
	 * Filters network admin ajax url.
	 *
	 * @deprecated 3.1.0
	 *
	 * @since 2.9.0
	 *
	 * @param array  $script_data Data.
	 * @param string $hook        Current hook.
	 *
	 * @return array
	 */
	public function network_admin_filter_ajax_url( $script_data, $hook ) {

		_deprecated_function( __METHOD__, '3.1.0' );

		$this->switch_blog_to_selection();
		$script_data['ajax_url'] = admin_url( 'admin-ajax.php' );
		restore_current_blog();

		return $script_data;
	}

	/**
	 * Change network admin edit email log link from network to related site.
	 *
	 * @since 2.9.0
	 *
	 * @param string $url  Item link.
	 * @param Email  $item Email instance.
	 * @param string $link Link type.
	 *
	 * @return string
	 */
	public function network_admin_emails_logs_item_link( $url, $item, $link ) {

		if ( $link === 'edit' ) {
			$url = add_query_arg(
				[
					'page'     => Area::SLUG . '-logs',
					'email_id' => $item->get_id(),
					'mode'     => 'view',
				],
				admin_url( 'admin.php' )
			);
		}

		return $url;
	}

	/**
	 * Get network sites ajax handler.
	 *
	 * @since 2.9.0
	 */
	public function get_sites_ajax() {

		// Run a security check.
		check_ajax_referer( 'wp-mail-smtp-admin', 'nonce' );

		if ( ! current_user_can( 'manage_network' ) ) {
			wp_send_json_error( esc_html__( 'You don\'t have the capability to perform this action.', 'wp-mail-smtp-pro' ) );
		}

		$page = isset( $_GET['page'] ) ? intval( $_GET['page'] ) : 1;
		$term = isset( $_GET['term'] ) ? sanitize_text_field( wp_unslash( $_GET['term'] ) ) : '';

		$sites_per_page = 10;

		$query_args = [
			'fields'        => 'ids',
			'number'        => $sites_per_page,
			'search'        => $term,
			'offset'        => $sites_per_page * ( $page - 1 ),
			'no_found_rows' => false,
		];

		$query = new \WP_Site_Query();

		$results = array_map(
			function ( $site_id ) {

				return [
					'id'   => intval( $site_id ),
					'text' => esc_html( get_blog_details( $site_id )->blogname ),
				];
			},
			$query->query( $query_args )
		);

		wp_send_json(
			[
				'results'    => $results,
				'pagination' => [
					'more' => $page < $query->max_num_pages,
				],
			]
		);
	}

	/**
	 * Unset global tasks that are not needed for subsites
	 * if the network-wide option is enabled.
	 *
	 * @since 4.4.0
	 *
	 * @param array $tasks Tasks.
	 *
	 * @return array
	 */
	public function unset_global_tasks_on_subsites( $tasks ) {

		if ( WP::use_global_plugin_settings() && ! is_main_site() ) {
			$tasks_to_remove = [
				NotificationsUpdateTask::class,
				LicenseCheckTask::class,
				SendUsageTask::class,
			];

			$tasks = array_filter(
				$tasks,
				function ( $task ) use ( $tasks_to_remove ) {
					return ! in_array( $task, $tasks_to_remove, true );
				}
			);
		}

		return $tasks;
	}

	/**
	 * Render the aggregated network-admin notice listing every subsite whose
	 * primary connection has a failed email sending record.
	 *
	 * Scope is primary-connection-only — additional connection errors are not
	 * surfaced in the network admin view.
	 *
	 * @since 4.9.0
	 */
	public function render_aggregated_subsite_errors() {

		if ( ! WP::use_global_plugin_settings() || ! is_network_admin() ) {
			return;
		}

		if ( ! current_user_can( wp_mail_smtp()->get_capability_manage_global_options() ) ) {
			return;
		}

		$errors = $this->collect_subsite_primary_errors();

		if ( empty( $errors ) ) {
			return;
		}

		$rows = [];

		foreach ( $errors as $error ) {
			$rows[] = sprintf(
				'<strong>%1$s</strong> - <a href="%2$s" target="_blank">%2$s</a><br>%3$s: %4$s',
				esc_html( $error['name'] ),
				esc_url( $error['url'] ),
				esc_html( $error['mailer_title'] ),
				esc_html( $error['error_message'] )
			);
		}

		?>
		<div class="notice <?php echo esc_attr( WP::ADMIN_NOTICE_ERROR ); ?>">
			<p>
				<?php
				echo wp_kses(
					__( '<strong>Heads up!</strong> The last email your site attempted to send was unsuccessful.', 'wp-mail-smtp-pro' ),
					[ 'strong' => [] ]
				);
				?>
			</p>
			<blockquote>
				<pre><?php echo wp_kses_post( implode( PHP_EOL . PHP_EOL, $rows ) ); ?></pre>
			</blockquote>
			<p>
				<a href="#" class="js-wp-mail-smtp-clear-network-wide-error-notices">
					<?php esc_html_e( 'Clear these errors', 'wp-mail-smtp-pro' ); ?>
				</a>
			</p>
		</div>
		<?php
	}

	/**
	 * Collect failed primary-connection records across every subsite.
	 *
	 * Reads each subsite's EmailSendingDebug option directly via
	 * `get_blog_option()` and filters down to records with
	 * `status: failed` and `context: regular`. The mailer title is resolved
	 * inside a `switch_to_blog`/`restore_current_blog` pair because the
	 * provider title can be subsite-specific.
	 *
	 * @since 4.9.0
	 *
	 * @return array List of `[ 'blog_id', 'name', 'url', 'mailer_title', 'error_message' ]` rows.
	 */
	private function collect_subsite_primary_errors() { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded -- Linear guard chain over the per-site failure record; collapsing the guards would obscure the schema checks.

		$rows = [];

		foreach ( get_sites() as $site ) {
			$option = get_blog_option( $site->blog_id, EmailSendingDebug::OPTION_KEY, [] );
			$record = is_array( $option ) && isset( $option['primary'] ) ? $option['primary'] : null;

			if ( ! is_array( $record ) ) {
				continue;
			}

			if ( ! isset( $record['status'], $record['context'] ) ) {
				continue;
			}

			if ( $record['status'] !== 'failed' || $record['context'] !== 'regular' ) {
				continue;
			}

			$mailer_slug = isset( $record['mailer'] ) ? (string) $record['mailer'] : '';

			switch_to_blog( $site->blog_id );

			$site_details = get_blog_details( $site->blog_id );
			$site_name    = ( $site_details && ! empty( $site_details->blogname ) ) ? $site_details->blogname : $site->domain;
			$site_url     = get_admin_url( $site->blog_id );

			$mailer_title = '';

			if ( $mailer_slug !== '' ) {
				$provider = wp_mail_smtp()->get_providers()->get_options( $mailer_slug );

				if ( $provider !== null ) {
					$mailer_title = $provider->get_title();
				}
			}

			if ( $mailer_title === '' ) {
				$mailer_title = $mailer_slug;
			}

			restore_current_blog();

			$rows[] = [
				'blog_id'       => (int) $site->blog_id,
				'name'          => $site_name,
				'url'           => $site_url,
				'mailer_title'  => $mailer_title,
				'error_message' => isset( $record['error_message'] ) ? (string) $record['error_message'] : '',
			];
		}

		return $rows;
	}

	/**
	 * AJAX handler: clear the primary-connection failure record on every subsite.
	 *
	 * Only the `primary` key is unset on each subsite's EmailSendingDebug
	 * option — additional-connection records are preserved so their per-site
	 * banners remain intact.
	 *
	 * @since 4.9.0
	 */
	public function clear_aggregated_error_notices_callback() {

		check_ajax_referer( 'wp-mail-smtp-pro-admin' );

		if ( ! current_user_can( wp_mail_smtp()->get_capability_manage_global_options() ) ) {
			wp_send_json_error();
		}

		if ( ! WP::use_global_plugin_settings() || ! is_multisite() ) {
			wp_send_json_error();
		}

		foreach ( get_sites() as $site ) {
			$option = get_blog_option( $site->blog_id, EmailSendingDebug::OPTION_KEY, [] );

			if ( ! is_array( $option ) || ! isset( $option['primary'] ) ) {
				continue;
			}

			unset( $option['primary'] );

			update_blog_option( $site->blog_id, EmailSendingDebug::OPTION_KEY, $option );
		}

		wp_send_json_success();
	}
}
