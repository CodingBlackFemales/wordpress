<?php

namespace WPForms\Pro\Admin;

// phpcs:disable WPForms.PHP.UseStatement.UnusedUseStatement
use WP_Plugins_List_Table;
use stdClass;
use WPForms\Admin\Addons\AddonsCache;
// phpcs:enable WPForms.PHP.UseStatement.UnusedUseStatement

/**
 * Class PluginList.
 *
 * Notice displayed in the Plugins page for Pro users.
 *
 * @since 1.8.6
 */
class PluginList {

	/**
	 * License Statuses.
	 *
	 * @since 1.8.6
	 *
	 * @var string
	 */
	const LICENSE_STATUS_EMPTY    = 'empty';
	const LICENSE_STATUS_VALID    = 'valid';
	const LICENSE_STATUS_EXPIRED  = 'expired';
	const LICENSE_STATUS_DISABLED = 'disabled';
	const LICENSE_STATUS_INVALID  = 'invalid';

	/**
	 * Plugin slug.
	 *
	 * @since 1.8.6
	 *
	 * @var string
	 */
	private $plugin_slug;

	/**
	 * Plugin path (relative to the plugins' dir).
	 *
	 * @since 1.8.6
	 *
	 * @var string
	 */
	private $plugin_path;

	/**
	 * The license status.
	 *
	 * @since 1.8.6
	 *
	 * @var string|null
	 */
	private $license_status;

	/**
	 * The latest version fetched from a remote source.
	 *
	 * @since 1.8.6
	 *
	 * @var null|string
	 */
	private $remote_latest_version;

	/**
	 * Whether this site is using the latest version of WPForms Pro.
	 *
	 * @since 1.8.6
	 *
	 * @var bool
	 */
	private $is_using_latest_version;

	/**
	 * Internal set_site_transient_update_addons cache.
	 *
	 * @since 1.9.0
	 *
	 * @var object|null
	 */
	private $update_addons_cache;

	/**
	 * Instance of AddonsCache.
	 *
	 * @since 1.9.0
	 *
	 * @var AddonsCache|null
	 */
	private $addons_cache_obj;

	/**
	 * Init.
	 *
	 * @since 1.8.6
	 *
	 * @return void
	 */
	public function init() {

		$this->plugin_slug      = defined( 'WPFORMS_PLUGIN_DIR' ) ? plugin_basename( WPFORMS_PLUGIN_DIR ) : 'wpforms';
		$this->plugin_path      = $this->plugin_slug . '/wpforms.php';
		$this->addons_cache_obj = wpforms()->obj( 'addons_cache' );

		$this->hooks();
	}

	/**
	 * Register hooks.
	 *
	 * @since 1.8.6
	 *
	 * @return void
	 */
	private function hooks() {

		global $pagenow;

		if ( empty( $pagenow ) || $pagenow !== 'plugins.php' ) {
			return;
		}

		add_action( 'admin_init', [ $this, 'update_addons_cache' ] );
		add_filter( 'site_transient_update_plugins', [ $this, 'site_transient_update_addons' ] );
		add_filter( 'set_site_transient_update_plugins', [ $this, 'set_site_transient_update_addons' ] );
		add_action( 'admin_head', [ $this, 'add_style' ] );
		add_action( "after_plugin_row_{$this->plugin_path}", [ $this, 'show_plugin_notice' ], 0 );
	}

	/**
	 * Update addons cache.
	 *
	 * @since 1.9.0
	 *
	 * @return void
	 */
	public function update_addons_cache() {

		if ( $this->addons_cache_obj ) {
			$this->addons_cache_obj->update();
		}
	}

	/**
	 * Add CSS styles.
	 *
	 * @since 1.8.6
	 *
	 * @return void
	 */
	public function add_style() {

		printf(
			'<style>
				#wpforms-update td.plugin-update {
					box-shadow: 0 1px 0 0 rgba(0,0,0,.1);
					transform: translateY(-1px);
				}

				.plugins tr.update[data-slug="%1$s"] .second,
				.plugins tr.update[data-slug="%1$s"] .row-actions {
					padding-bottom: 0;
				}

				.wpforms-wrong-license {
					color: #dc3232;
					font-weight: bold;
				}

				@media screen and (max-width: 782px) {
					.plugins tr[data-slug="%1$s"].plugin-update-tr.active:before {
						background-color: #f0f6fc;
						border-left: 4px solid #72aee6;
					}

					.plugins .plugin-update-tr .wpforms-update-message {
						margin-left: 0;
					}
				}
			</style>',
			esc_attr( $this->plugin_slug )
		);
	}

	/**
	 * Filters `update_plugins` transient in Admin Plugins page.
	 *
	 * This method removes the "update" of the Pro plugin.
	 * Doing this fixes the edge case where both the default WP plugin update notice
	 * and our custom notice is displayed at the same time.
	 *
	 * @since 1.8.6
	 * @deprecated 1.9.0
	 *
	 * @param mixed $value Value of site transient.
	 *
	 * @return object $value Amended WordPress update object.
	 */
	public function site_transient_update_plugins( $value ) {

		// We don't need current method anymore.
		_deprecated_function( __METHOD__, '1.9.0 of the WPForms plugin' );

		return $value;
	}

	/**
	 * Filters `update_plugins` transient in Admin Plugins page.
	 *
	 * This method checks available addon updates.
	 *
	 * @since 1.9.0
	 *
	 * @param mixed $value Value of site transient.
	 *
	 * @return object $value Amended WordPress update object.
	 */
	public function site_transient_update_addons( $value ) { // phpcs:ignore WPForms.PHP.HooksMethod.InvalidPlaceForAddingHooks, Generic.Metrics.CyclomaticComplexity.MaxExceeded

		global $current_screen;

		// We only want this filter in the Dashboard -> Plugins page.
		if ( is_null( $current_screen ) || $current_screen->id !== 'plugins' ) {
			return $value;
		}

		if ( $this->update_addons_cache !== null ) {
			return $this->update_addons_cache;
		}

		if ( ! is_object( $value ) ) {
			$value = new stdClass();
		}

		$addons = $this->addons_cache_obj ? $this->addons_cache_obj->get() : [];

		foreach ( get_plugins() as $name => $plugin ) {
			$slug = explode( '/', $name )[0];

			if ( ! array_key_exists( $slug, $addons ) ) {
				continue;
			}

			$addon = $addons[ $slug ];

			if ( version_compare( $plugin['Version'], $addon['version'], '<' ) ) {
				// Do not set 'slug' here, only 'url', to form $details_url in after_plugin_row() properly.
				$url = add_query_arg(
					[
						'utm_source'   => 'WordPress',
						'utm_campaign' => 'plugin',
						'utm_medium'   => 'addons',
						'utm_content'  => $addon['title'],
					],
					$addon['url']
				);

				$icon_url = WPFORMS_PLUGIN_URL . 'assets/images/' . ( $addon['icon'] ?? 'sullie.png' );
				$icons    = [
					'1x'      => $icon_url,
					'2x'      => $icon_url,
					'default' => $icon_url,
				];

				$value->response[ $name ] = (object) [
					'id'               => $addon['id'],
					'plugin'           => $name,
					'version'          => $plugin['Version'],
					'new_version'      => $addon['version'] ?? $plugin['Version'],
					'url'              => $url,
					'package'          => $value->response[ $name ]->package ?? '',
					'icons'            => $icons,
					'banners'          => [],
					'banners_rtl'      => [],
					'requires'         => $addon['required_versions']['wp'] ?? '5.5',
					'requires_php'     => $addon['required_versions']['php'] ?? '7.0',
					'requires_wpforms' => $addon['required_versions']['wpforms'] ?? WPFORMS_VERSION,
					'requires_plugin'  => 'wpforms/wpforms.php',
				];
			}

			add_action( "after_plugin_row_{$name}", [ $this, 'after_plugin_row' ], 0, 2 );
		}

		$this->update_addons_cache = $value;

		return $value;
	}

	/**
	 * Set site transient update plugins action.
	 *
	 * Method site_transient_update_addons() is called multiple times,
	 * so we cache it's result internally in this class.
	 *
	 * On set_transient_update_plugins action, we flush internal caches.
	 *
	 * @since 1.9.0
	 *
	 * @return void
	 */
	public function set_site_transient_update_addons() {

		$this->update_addons_cache = null;
	}

	/**
	 * Displays update information for a plugin.
	 * Form of the wp_plugin_update_row().
	 *
	 * @since 1.9.0
	 *
	 * @param string $file        Plugin basename.
	 * @param array  $plugin_data Plugin information.
	 *
	 * @return void|false
	 * @noinspection HtmlUnknownAttribute
	 */
	public function after_plugin_row( string $file, array $plugin_data ) { // phpcs:ignore WPForms.PHP.HooksMethod.InvalidPlaceForAddingHooks, Generic.Metrics.CyclomaticComplexity.MaxExceeded

		// Remove WP Core action, as we override its functionality for the plugin.
		remove_action( "after_plugin_row_{$file}", 'wp_plugin_update_row' );

		$current = get_site_transient( 'update_plugins' );

		if ( ! isset( $current->response[ $file ] ) ) {
			return false;
		}

		$response = $current->response[ $file ];

		$plugins_allowed_tags = [
			'a'       => [
				'href'  => [],
				'title' => [],
			],
			'abbr'    => [ 'title' => [] ],
			'acronym' => [ 'title' => [] ],
			'code'    => [],
			'em'      => [],
			'strong'  => [],
		];

		$plugin_name   = wp_kses( $plugin_data['Name'], $plugins_allowed_tags );
		$plugin_chunks = explode( '/', $file );
		$plugin_slug   = $plugin_chunks[0] ?? $response->id;
		$details_url   = add_query_arg(
			[
				'tab'       => 'plugin-information',
				'plugin'    => $plugin_slug,
				'section'   => 'changelog',
				'TB_iframe' => 'true',
				'width'     => 772,
				'height'    => 771,
			],
			self_admin_url( 'plugin-install.php' )
		);
		/**
		 * WP List Table.
		 *
		 * @var WP_Plugins_List_Table $wp_list_table
		 */
		$wp_list_table = _get_list_table(
			'WP_Plugins_List_Table',
			[
				'screen' => get_current_screen(),
			]
		);

		if ( ! is_network_admin() && is_multisite() ) {
			return;
		}

		if ( is_network_admin() ) {
			$active_class = is_plugin_active_for_network( $file ) ? ' active' : '';
		} else {
			$active_class = is_plugin_active( $file ) ? ' active' : '';
		}

		$requires_php     = $response->requires_php ?? '';
		$requires_wp      = $response->requires ?? '';
		$requires_wpforms = $response->requires_wpforms ?? '';

		$compatible['php']     = is_php_version_compatible( $requires_php ) ? true : 'PHP ' . $requires_php;
		$compatible['wp']      = is_wp_version_compatible( $requires_wp ) ? true : 'WordPress ' . $requires_wp;
		$compatible['wpforms'] = self::is_wpforms_version_compatible( $requires_wpforms ) ? true : 'WPForms ' . $requires_wpforms;

		$notice_type = 'notice-warning';

		foreach ( $compatible as $value ) {
			if ( $value !== true ) {
				$notice_type = 'notice-error';

				break;
			}
		}

		printf(
			'<tr class="plugin-update-tr%s" id="%s" data-slug="%s" data-plugin="%s">
				<td colspan="%s" class="plugin-update colspanchange">
				<div class="wpforms-update-message update-message notice inline %s notice-alt"><p>',
			esc_attr( $active_class ),
			esc_attr( $plugin_slug . '-update' ),
			esc_attr( $plugin_slug ),
			esc_attr( $file ),
			esc_attr( $wp_list_table->get_column_count() ),
			esc_attr( $notice_type )
		);

		$this->print_addon_update_message( $plugin_name, $details_url, $response, $compatible, $file );

		/**
		 * Fires at the end of the update message container in each
		 * row of the plugins' list table.
		 *
		 * The dynamic portion of the hook name, `$file`, refers to the path
		 * of the plugin's primary file relative to the plugins' directory.
		 *
		 * @since 2.8.0
		 *
		 * @param array   $plugin_data      An array of plugin metadata. See get_plugin_data()
		 *                                  and the {@see 'plugin_row_meta'} filter for the list
		 *                                  of possible values.
		 * @param object  $response         {
		 *                                  An object of metadata about the available plugin update.
		 *
		 * @type string   $id               Plugin ID, e.g. `w.org/plugins/[plugin-name]`.
		 * @type string   $slug             Plugin slug.
		 * @type string   $plugin           Plugin basename.
		 * @type string   $new_version      New plugin version.
		 * @type string   $url              Plugin URL.
		 * @type string   $package          Plugin update package URL.
		 * @type string[] $icons            An array of plugin icon URLs.
		 * @type string[] $banners          An array of plugin banner URLs.
		 * @type string[] $banners_rtl      An array of plugin RTL banner URLs.
		 * @type string   $requires         The version of WordPress which the plugin requires.
		 * @type string   $tested           The version of WordPress the plugin is tested against.
		 * @type string   $requires_php     The version of PHP which the plugin requires.
		 * @type string   $requires_wp      The version of WP which the plugin requires.
		 * @type string   $requires_wpforms The version of WPForms which the plugin requires.
		 *                                  }
		 */
		do_action( "in_plugin_update_message-{$file}", $plugin_data, $response ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName, WordPress.NamingConventions.ValidHookName.UseUnderscores

		echo '</p></div></td></tr>';
	}

	/**
	 * Get the license status.
	 *
	 * @since 1.8.6
	 *
	 * @return string
	 */
	private function get_license_status(): string {

		if ( ! is_null( $this->license_status ) ) {
			return $this->license_status;
		}

		$license_type = wpforms_get_license_type();
		$license_key  = wpforms_get_license_key();

		$this->license_status = self::LICENSE_STATUS_VALID;

		if ( empty( $license_key ) || empty( $license_type ) ) {
			$this->license_status = self::LICENSE_STATUS_EMPTY;

			return $this->license_status;
		}

		$this->license_status = wpforms_setting( 'is_expired', false, 'wpforms_license' ) ?
			self::LICENSE_STATUS_EXPIRED :
			$this->license_status;
		$this->license_status = wpforms_setting( 'is_disabled', false, 'wpforms_license' ) ?
			self::LICENSE_STATUS_DISABLED :
			$this->license_status;
		$this->license_status = wpforms_setting( 'is_invalid', false, 'wpforms_license' ) ?
			self::LICENSE_STATUS_INVALID :
			$this->license_status;

		return $this->license_status;
	}

	/**
	 * Check if the license is valid.
	 *
	 * @since 1.9.0
	 *
	 * @return bool
	 */
	public function is_valid_license(): bool {

		return $this->get_license_status() === self::LICENSE_STATUS_VALID;
	}

	/**
	 * Adds custom plugin notice for Pro users without a valid license.
	 *
	 * @since 1.8.6
	 *
	 * @param string $plugin_file Path to the plugin file relative to the `plugins` directory.
	 *
	 * @return void
	 */
	public function show_plugin_notice( string $plugin_file ) { // phpcs:ignore WPForms.PHP.HooksMethod.InvalidPlaceForAddingHooks

		if ( $plugin_file !== $this->plugin_path ) {
			return;
		}

		// Remove WP Core action, as we override its functionality for the plugin.
		remove_action( "after_plugin_row_{$plugin_file}", 'wp_plugin_update_row' );

		$update_notice = $this->get_update_notice();

		if ( empty( $update_notice ) ) {
			return;
		}

		global $wp_list_table;

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo wpforms_render(
			'admin/plugins-list/update-notice',
			[
				'plugin_slug'   => $this->plugin_slug,
				'plugin_path'   => $this->plugin_path,
				'columns_count' => empty( $wp_list_table ) ? 4 : $wp_list_table->get_column_count(),
				'update_notice' => $update_notice,
			],
			true
		);
	}

	/**
	 * Get the update notice.
	 *
	 * @since 1.8.6
	 *
	 * @return string
	 */
	private function get_update_notice(): string {

		$status = $this->get_license_status();

		if ( $status === self::LICENSE_STATUS_VALID ) {
			return $this->is_using_latest_version() ? '' : $this->get_new_version_available_notice();
		}

		return $status === self::LICENSE_STATUS_EMPTY ?
			$this->get_no_license_notice() :
			$this->get_wrong_license_notice( $status );
	}

	/**
	 * Get the notice for users without a license key.
	 *
	 * @since 1.8.6
	 *
	 * @return string
	 * @noinspection HtmlUnknownTarget
	 */
	private function get_no_license_notice(): string {

		$activate_url = add_query_arg( [ 'page' => 'wpforms-settings' ], admin_url( 'admin.php' ) );
		$purchase_url = wpforms_utm_link( 'https://wpforms.com/pricing/', 'no license', 'purchase one now' );

		if ( $this->is_using_latest_version() ) {
			return sprintf( /* translators: %1$s - WPForms Pro URL; %2$s - WPForms Pro purchase link. */
				__(
					'<a href="%1$s">Activate WPForms Pro</a> to receive features, updates, and support. Don\'t have a license? <a target="_blank" href="%2$s" rel="noopener noreferrer">Purchase one now</a>.',
					'wpforms'
				),
				esc_url( $activate_url ),
				esc_url( $purchase_url )
			);
		}

		return $this->get_new_version_available_notice()
			. '<br />'
			. sprintf( /* translators: %1$s - WPForms Pro URL; %2$s - WPForms Pro purchase link. */
				__(
					'<a href="%1$s">Activate</a> your license to access this update, new features, and support. Don\'t have a license? <a target="_blank" href="%2$s" rel="noopener noreferrer">Purchase one now</a>.',
					'wpforms'
				),
				esc_url( $activate_url ),
				esc_url( $purchase_url )
			);
	}

	/**
	 * Get the notice for users with expired license key.
	 *
	 * @since 1.8.6
	 *
	 * @param string $status License status.
	 *
	 * @return string
	 * @noinspection HtmlUnknownTarget
	 */
	private function get_wrong_license_notice( string $status ): string {

		$message = $this->is_using_latest_version() ? '' : $this->get_new_version_available_notice() . '<br>';

		$renew_msg = sprintf( /* translators: %s - WPForms Pro renew link. */
			__(
				'<a target="_blank" href="%1$s" rel="noopener noreferrer">Renew now</a> to receive new features, updates, and support.',
				'wpforms'
			),
			esc_url( wpforms_utm_link( 'https://wpforms.com/account/licenses/', 'plugins list expired license', 'WPForms' ) )
		);

		$status_messages = [
			self::LICENSE_STATUS_EXPIRED  => esc_html__( 'Your WPForms Pro license is expired.', 'wpforms' ),
			self::LICENSE_STATUS_DISABLED => esc_html__( 'Your WPForms Pro license is disabled.', 'wpforms' ),
			self::LICENSE_STATUS_INVALID  => esc_html__( 'Your WPForms Pro license is invalid.', 'wpforms' ),
		];

		$status_message = $status_messages[ $status ] ?? '';

		return "$message<span class='wpforms-wrong-license'>$status_message</span> $renew_msg";
	}

	/**
	 * Get the notice to show when a new version of WPForms Pro is available.
	 *
	 * @since 1.8.6
	 *
	 * @return string
	 * @noinspection HtmlUnknownTarget
	 */
	private function get_new_version_available_notice(): string {

		$details_url  = wpforms_utm_link(
			'https://wpforms.com/docs/how-to-view-recent-changes-to-the-wpforms-plugin-changelog/',
			'WPForms',
			'view version details'
		);
		$details_url .= '#changelog';

		$upgrade_url = wp_nonce_url(
			self_admin_url( 'update.php?action=upgrade-plugin&plugin=' . $this->plugin_path ),
			'upgrade-plugin_' . $this->plugin_path
		);
		$new_version = $this->get_latest_version();

		// Message without "update now" link.
		$new_version_message = sprintf( /* translators: %1$s - WPForms Pro Changelog link; %2$s - WPForms Pro latest version. */
			__(
				'There is a new version of WPForms available. <a target="_blank" href="%1$s" rel="noopener noreferrer">View version %2$s details</a>',
				'wpforms'
			),
			esc_url( $details_url ),
			esc_html( $new_version )
		);

		if ( $this->get_license_status() !== self::LICENSE_STATUS_VALID ) {
			return $new_version_message . '.';
		}

		// Message with "update now" link.
		return $new_version_message . ' ' .
			sprintf( /* translators: %1$s - WPForms Pro upgrade URL; %2$s - Update now link aria-label attribute. */
				__(
					'or <a href="%1$s" class="update-link" aria-label="%2$s">update now</a>.',
					'wpforms'
				),
				esc_url( $upgrade_url ),
				esc_attr__( 'Update Now', 'wpforms' )
			);
	}

	/**
	 * Whether this site is using the latest version of WPForms Pro.
	 *
	 * @since 1.8.6
	 *
	 * @return bool
	 */
	private function is_using_latest_version(): bool {

		if ( ! is_null( $this->is_using_latest_version ) ) {
			return $this->is_using_latest_version;
		}

		$this->is_using_latest_version = version_compare( WPFORMS_VERSION, $this->get_latest_version(), '>=' );

		return $this->is_using_latest_version;
	}

	/**
	 * Get the latest version.
	 *
	 * @since 1.8.6
	 *
	 * @return string
	 */
	private function get_latest_version() {

		if ( ! is_null( $this->remote_latest_version ) ) {
			return $this->remote_latest_version;
		}

		// WP core updates this transient.
		// We use it to get the latest version of WPForms Pro.
		// We have a hook on `pre_set_site_transient_update_plugins` in the `WPForms_Updater` class
		// that checks the remote API and adds the update for WPForms Pro to this transient.
		$option = get_site_option( '_site_transient_update_plugins' );

		$this->remote_latest_version = $option->response[ $this->plugin_path ]->new_version ?? WPFORMS_VERSION;

		return $this->remote_latest_version;
	}

	/**
	 * Print addon update message.
	 *
	 * @since 1.9.0
	 *
	 * @param string $plugin_name Plugin name.
	 * @param string $details_url Details URL.
	 * @param object $response    Response.
	 * @param array  $compatible  Compatibility array.
	 * @param string $file        Filename.
	 *
	 * @return void
	 * @noinspection HtmlUnknownTarget
	 * @noinspection HtmlUnknownAttribute
	 */
	public function print_addon_update_message( string $plugin_name, string $details_url, $response, array $compatible, string $file ) {

		$link_attr = sprintf(
			'class="thickbox open-plugin-details-modal" aria-label="%s"',
			esc_attr(
				sprintf(
					/* translators: 1: Plugin name, 2: Version number. */
					__( 'View %1$s version %2$s details', 'wpforms' ),
					$plugin_name,
					$response->new_version
				)
			)
		);

		// Prepare incompatible components.
		$incompatible = [];

		foreach ( $compatible as $key => $value ) {
			if ( $value !== true ) {
				$incompatible[ $key ] = $value;
			}
		}

		// Compatible updates.
		if ( empty( $incompatible ) ) {
			$this->print_addon_compatible_update_message( $plugin_name, $details_url, $response, $link_attr, $file );

			return;
		}

		// Incompatible updates available.

		// Details URL with disabled update button.
		$details_url = add_query_arg(
			[
				'update'    => 'disabled',
				'TB_iframe' => 'true',
				'width'     => 772,
				'height'    => 771,
			],
			// Remove query args that is added after `update=disabled`.
			// This is necessary because WP removes everything from URL after `TB_iframe=true&width=772&height=771`.
			remove_query_arg( [ 'TB_iframe', 'width', 'height' ], $details_url )
		);

		$available = wp_kses(
			sprintf(
				/* translators: 1: Plugin name, 2: Details URL, 3: Link attributes, 4: Version number, 5: Components. */
				__(
					'Sorry, the %1$s addon can\'t be updated to <a href="%2$s" %3$s>version %4$s</a> because it requires %5$s.',
					'wpforms'
				),
				esc_html( $plugin_name ),
				esc_url( $details_url ),
				$link_attr,
				esc_html( $response->new_version ),
				wpforms_list_array( $incompatible )
			),
			[
				'a' => [
					'href'       => [],
					'class'      => [],
					'aria-label' => [],
				],
			]
		);

		$check_updates = wp_kses(
			sprintf( /* translators: 1: Details URL, 2: Additional link attributes, 3: Version number. */
				__( 'If no update is available, try <a href="%1$s">checking for new plugin updates</a>.', 'wpforms' ),
				esc_url( admin_url( 'update-core.php' ) )
			),
			[
				'a' => [
					'href' => [],
				],
			]
		);

		$php_update = '';

		if ( ! empty( $incompatible['php'] ) ) {
			$php_update = sprintf(
				wp_kses(
					/* translators: 1:URL to Update PHP page. */
					__( 'Learn more about <a href="%1$s" target="_blank" rel="noopener noreferrer">updating PHP</a>.', 'wpforms' ),
					[
						'a' => [
							'href'   => [],
							'target' => [],
							'rel'    => [],
						],
					]
				),
				esc_url( wp_get_update_php_url() )
			);
		}

		// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
		echo $available . ' ' . $check_updates . ' ' . $php_update;

		wp_update_php_annotation( '<br><em>', '</em>' );
	}

	/**
	 * Print addon compatible update message.
	 *
	 * @since 1.9.0
	 *
	 * @param string $plugin_name Plugin name.
	 * @param string $details_url Details URL.
	 * @param object $response    Response.
	 * @param string $link_attr   Additional link attributes.
	 * @param string $file        Filename.
	 *
	 * @return void
	 * @noinspection HtmlUnknownTarget
	 * @noinspection HtmlUnknownAttribute
	 */
	public function print_addon_compatible_update_message( string $plugin_name, string $details_url, $response, string $link_attr, string $file ) {

		$update_now = '';

		if ( $this->is_valid_license() && current_user_can( 'update_plugins' ) ) {
			$update_now = sprintf(
				' or <a href="%1$s" %2$s>update now</a>',
				esc_url( wp_nonce_url( self_admin_url( 'update.php?action=upgrade-plugin&plugin=' ) . $file, 'upgrade-plugin_' . $file ) ),
				sprintf(
					'class="update-link" aria-label="%s"',
					/* translators: %s: Plugin name. */
					esc_attr( sprintf( _x( 'Update %s now', 'wpforms' ), $plugin_name ) )
				)
			);
		}

		echo wp_kses(
			sprintf(
				/* translators: 1: Plugin name, 2: Details URL, 3: Additional link attributes, 4: Version number, 5: Update URL, 6: Additional link attributes. */
				__(
					'There is a new version of %1$s available. <a href="%2$s" %3$s>View version %4$s details</a>%5$s.',
					'wpforms'
				),
				$plugin_name,
				esc_url( $details_url ),
				$link_attr,
				esc_attr( $response->new_version ),
				$update_now
			),
			[
				'a' => [
					'href'       => [],
					'class'      => [],
					'aria-label' => [],
				],
			]
		);
	}

	/**
	 * Checks compatibility with the current WPForms version.
	 *
	 * @since 1.9.0
	 *
	 * @param string $required Minimum required WPForms version.
	 *
	 * @return bool True if a required version is compatible or empty, false if not.
	 */
	public static function is_wpforms_version_compatible( string $required ): bool {

		return empty( $required ) || version_compare( WPFORMS_VERSION, $required, '>=' );
	}
}
