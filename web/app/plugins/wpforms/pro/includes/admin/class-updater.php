<?php

// phpcs:disable Generic.Commenting.DocComment.MissingShort
/** @noinspection PhpIllegalPsrClassPathInspection */
/** @noinspection AutoloadingIssuesInspection */
// phpcs:enable Generic.Commenting.DocComment.MissingShort

use WPForms\Pro\Admin\PluginList;

/**
 * Updater class.
 *
 * @since 1.5.4.2
 */
class WPForms_Updater {

	/**
	 * Plugin name.
	 *
	 * @since 1.5.4.2
	 *
	 * @var bool|string
	 */
	public $plugin_name = false;

	/**
	 * Plugin slug.
	 *
	 * @since 1.5.4.2
	 *
	 * @var bool|string
	 */
	public $plugin_slug = false;

	/**
	 * Plugin path.
	 *
	 * @since 1.5.4.2
	 *
	 * @var bool|string
	 */
	public $plugin_path = false;

	/**
	 * URL of the plugin.
	 *
	 * @since 1.5.4.2
	 *
	 * @var bool|string
	 */
	public $plugin_url = false;

	/**
	 * Remote URL for getting plugin updates.
	 *
	 * @since 1.5.4.2
	 *
	 * @var bool|string
	 */
	public $remote_url = false;

	/**
	 * Version number of the plugin.
	 *
	 * @since 1.5.4.2
	 *
	 * @var string
	 */
	public $version = false;

	/**
	 * License key for the plugin.
	 *
	 * @since 2.0.0
	 *
	 * @var bool|string
	 */
	public $key = false;

	/**
	 * Store the update data returned from the API.
	 *
	 * @since 2.1.3
	 *
	 * @var bool|object
	 */
	public $update = false;

	/**
	 * Store the plugin info details for the update.
	 *
	 * @since 2.1.3
	 *
	 * @var bool|object
	 */
	public $info = false;

	/**
	 * Core plugin info cache object.
	 *
	 * @since 1.9.0
	 *
	 * @var object|null
	 */
	private $core_cache;

	/**
	 * Addons info cache object.
	 *
	 * @since 1.9.0
	 *
	 * @var object|null
	 */
	private $addons_cache;

	/**
	 * Whether the class is allowed.
	 *
	 * @since 1.9.1.1
	 *
	 * @var bool|null
	 */
	private $is_allowed;

	/**
	 * Primary class constructor.
	 *
	 * @since 1.5.4.2
	 *
	 * @param array $config Array of updater config args.
	 */
	public function __construct( array $config ) {

		// Set class properties.
		$accepted_args = [
			'plugin_name',
			'plugin_slug',
			'plugin_path',
			'plugin_url',
			'remote_url',
			'version',
			'key',
		];

		foreach ( $accepted_args as $arg ) {
			$this->$arg = $config[ $arg ] ?? false;
		}

		// Init class.
		$this->init();
	}

	/**
	 * Init class.
	 *
	 * @since 1.9.1
	 *
	 * @return void
	 */
	private function init() {

		// Proceed on selected admin pages, cron or cli only.
		if ( ! $this->allow_load() ) {
			return;
		}

		$this->core_cache   = wpforms()->obj( 'core_info_cache' );
		$this->addons_cache = wpforms()->obj( 'addons' );

		if ( $this->plugin_path && $this->version ) {
			$this->hooks();

			return;
		}

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . '/wp-admin/includes/plugin.php';
		}

		// Plugin path and versions are not set for addons, so we need to extract them from the WP Core.
		$plugins = get_plugins();

		foreach ( $plugins as $plugin_path => $plugin ) {
			$slug = explode( '/', $plugin_path )[0];

			if ( $slug !== $this->plugin_slug ) {
				continue;
			}

			$this->plugin_path = $plugin_path;
			$this->version     = $plugin['Version'];
		}

		// Load the updater hooks and filters if the plugin path and version are set.
		if ( $this->version ) {
			$this->hooks();
		}
	}

	/**
	 * Hooks.
	 *
	 * @since 1.8.6
	 */
	private function hooks() {

		add_filter( 'plugins_api', [ $this, 'plugins_api' ], 10, 3 );
		add_filter( 'pre_set_site_transient_update_plugins', [ $this, 'update_plugins_filter' ] );
		add_action( 'upgrader_process_complete', [ $this, 'upgrader_process_complete' ], 10, 2 );
	}

	/**
	 * Infuse plugin update details when WordPress runs its update checker.
	 *
	 * @since 1.5.4.2
	 *
	 * @param object $update_obj The WordPress update object.
	 *
	 * @return object $value Amended WordPress update object on success, default if an object is empty.
	 */
	public function update_plugins_filter( $update_obj ) {

		// If no update object exists or given value is not an object type, return early.
		if ( empty( $update_obj ) || ! is_object( $update_obj ) ) {
			return $update_obj;
		}

		if ( $this->is_core_plugin() ) {
			$this->update = $this->get_core_update();
		} else {
			$this->update = $this->get_addon_update();
		}

		// No update is available.
		if ( ! $this->update || ! empty( $this->update->error ) ) {
			$this->update = false;

			$update_obj->no_update[ $this->plugin_path ] = $this->get_no_update();

			return $update_obj;
		}

		// Infuse the update object with our data if the version from the remote API is newer.
		if ( $this->version && isset( $this->update->new_version ) && version_compare( $this->version, $this->update->new_version, '<' ) ) {

			// The $this->update object contains new_version, package, slug and last_update keys.
			$this->update->version                      = $this->version;
			$this->update->old_version                  = $this->version;
			$this->update->plugin                       = $this->plugin_path;
			$update_obj->response[ $this->plugin_path ] = $this->update;

			return $update_obj;
		}

		$update_obj->no_update[ $this->plugin_path ] = $this->get_no_update();

		// Return the update object.
		return $update_obj;
	}

	/**
	 * Actions to run after the upgrader process is complete.
	 *
	 * @since 1.9.0.2
	 *
	 * @param WP_Upgrader $upgrader   WP_Upgrader instance.
	 * @param array       $hook_extra Array of bulk item update data.
	 *
	 * @return void
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function upgrader_process_complete( WP_Upgrader $upgrader, array $hook_extra ) {

		$upgraded_plugins = $hook_extra['plugins'] ?? [];

		if ( in_array( $this->plugin_path, $upgraded_plugins, true ) ) {
			$all_plugins      = get_plugins();
			$upgraded_version = $all_plugins[ $this->plugin_path ]['Version'] ?? null;

			$this->version = $upgraded_version ? (string) $upgraded_version : $this->version;
		}
	}

	/**
	 * Get the core plugin update object.
	 *
	 * @since 1.9.0
	 *
	 * @return object|bool
	 */
	private function get_core_update() {

		if ( $this->update ) {
			return $this->update;
		}

		// Run update check by pinging the external API.
		if ( $this->is_valid_license() ) {
			return $this->perform_remote_request( 'get-plugin-update', [ 'tgm-updater-plugin' => $this->plugin_slug ] );
		}

		// For core plugin, if the license key is empty, we should get the update from the cached core.json file.
		return $this->get_update_from_cached_json_file();
	}

	/**
	 * Get the addon update object.
	 *
	 * @since 1.9.0
	 *
	 * @return object|bool
	 */
	private function get_addon_update() { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.TooHigh

		if ( $this->update ) {
			return $this->update;
		}

		$cached_data   = $this->get_update_from_cached_json_file();
		$is_compatible = $this->is_plugin_compatible( $cached_data );

		if ( $is_compatible && $this->is_valid_license() ) {
			// We should get the update from the external API to obtain the package URL.
			$update_data = $this->perform_remote_request( 'get-plugin-update', [ 'tgm-updater-plugin' => $this->plugin_slug ] );
		} else {
			// For inactive licenses, we should get the update from the cached addons.json file.
			$update_data = $cached_data;
		}

		// No update is available.
		if ( ! $update_data || ! empty( $update_data->error ) ) {
			return $update_data;
		}

		// Update from the API doesn't contain the icon URL, so we need to add it from the addons.json data.
		$icon_url = WPFORMS_PLUGIN_URL . 'assets/images/' . ( $cached_data->icon ?? 'sullie.png' );

		// The icons are used on the Dashboard > Updates page.
		$update_data->icons = [
			'1x'      => $icon_url,
			'2x'      => $icon_url,
			'default' => $icon_url,
		];

		// Before providing the download link, check if the plugin is compatible with the current environment.
		$update_data->download_url = $is_compatible && isset( $update_data->download_url )
			? $update_data->download_url
			: '';

		$update_data->download_link = $update_data->download_url;
		$update_data->package       = $update_data->download_url;

		return $update_data;
	}

	/**
	 * Get the update object with details from the cached `core.json` file.
	 *
	 * @since 1.8.6
	 *
	 * @return object
	 */
	private function get_update_from_cached_json_file() { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded

		if ( $this->is_core_plugin() ) {
			// Get the core info.
			$plugin_info = $this->core_cache ? $this->core_cache->get() : [];
		} else {
			// Get the addon info.
			$plugin_info = $this->addons_cache ? $this->addons_cache->get_addon( $this->plugin_slug ) : [];
		}

		// Mock the update object of the WPForms Pro plugin or addon.
		return (object) [
			'id'               => $this->plugin_path,
			'slug'             => $this->plugin_slug,
			'plugin'           => $this->plugin_path,
			'name'             => $this->plugin_name,
			'new_version'      => $plugin_info['version'] ?? $this->version,
			'tested'           => '',
			'requires'         => $plugin_info['required_versions']['wp'] ?? '5.5',
			'requires_php'     => $plugin_info['required_versions']['php'] ?? '7.0',
			'requires_wpforms' => $plugin_info['required_versions']['wpforms'] ?? WPFORMS_VERSION,
			'active_installs'  => 5 * 1000 * 1000,
			'package'          => '',
			'download_url'     => '',
			'changelog'        => implode( '', $plugin_info['changelog'] ?? [] ),
			'icon'             => $plugin_info['icon'] ?? '',
			'icons'            => $plugin_info['icons'] ?? [],
			'banners'          => (object) [
				'low'  => 'https://plugins.svn.wordpress.org/wpforms-lite/assets/banner-772x250.png',
				'high' => 'https://plugins.svn.wordpress.org/wpforms-lite/assets/banner-1544x500.png',
			],
			'banners_rtl'      => [],
		];
	}

	/**
	 * Disable SSL verification to prevent download package failures.
	 *
	 * @since 1.5.4.2
	 * @deprecated 1.8.6
	 *
	 * @param array  $args Array of request args.
	 * @param string $url  The URL to be pinged.
	 *
	 * @return array $args Amended array of request args.
	 * @noinspection PhpMissingParamTypeInspection
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function http_request_args( $args, $url ): array { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter

		_deprecated_function( __METHOD__, '1.8.6 of the WPForms plugin' );

		return $args;
	}

	/**
	 * Filter the plugins_api function to get our custom plugin information from a private repo.
	 *
	 * @since 1.5.4.2
	 *
	 * @param object|mixed $api    The original plugins_api object.
	 * @param string|mixed $action The action sent by plugins_api.
	 * @param object       $args   Additional args to send to plugins_api.
	 *
	 * @return object New stdClass with plugin or addon information on success, default response on failure.
	 */
	public function plugins_api( $api, $action = '', $args = null ) {

		$wpforms_plugin =
			(string) $action === 'plugin_information' &&
			isset( $args->slug ) &&
			$this->plugin_slug === $args->slug;

		// If plugin slug matches the request, set our own plugin data, else return the default response.
		if ( $wpforms_plugin ) {
			return $this->set_plugins_api( $api );
		}

		return $api;
	}

	/**
	 * Ping a remote API to retrieve plugin information for WordPress to display.
	 *
	 * @since 1.5.4.2
	 *
	 * @param object|mixed $default_api The default API object.
	 *
	 * @return object|mixed Return custom plugin or addon information to plugins_api.
	 */
	public function set_plugins_api( $default_api ) { // phpcs:ignore Generic.Metrics.CyclomaticComplexity.MaxExceeded

		$cached_data = $this->get_update_from_cached_json_file();

		// Perform the remote request to retrieve our plugin information. If it fails, return the default object.
		if ( ! $this->info && $this->is_valid_license() ) {
			$this->info = $this->perform_remote_request( 'get-plugin-info', [ 'tgm-updater-plugin' => $this->plugin_slug ] );
		}

		if ( ! $this->info ) {
			$this->info = $cached_data;
		}

		if ( ! $this->info || ! empty( $this->info->error ) ) {
			$this->info = false;

			return $default_api;
		}

		// Create a new stdClass object and populate it with our plugin information.
		$api                        = new stdClass();
		$api->name                  = $this->info->name ?? '';
		$api->slug                  = $this->info->slug ?? '';
		$api->version               = $this->info->version ?? '';
		$api->author                = $this->info->author ?? '';
		$api->author_profile        = $this->info->author_profile ?? '';
		$api->requires              = $this->info->requires ?? '';
		$api->tested                = $this->info->tested ?? '';
		$api->last_updated          = $this->info->last_updated ?? '';
		$api->homepage              = $this->info->homepage ?? '';
		$api->sections['changelog'] = $this->info->changelog ?? '';
		$api->active_installs       = $this->info->active_installs ?? '';
		$api->banners               = isset( $this->info->banners ) ? (array) $this->info->banners : '';

		// Before providing the download link, check if the plugin is compatible with the current environment.
		$api->download_link = isset( $this->info->download_link ) && $this->is_plugin_compatible( $cached_data )
			? $this->info->download_link
			: '';

		// Return the new API object with our custom data.
		return $api;
	}

	/**
	 * Query the remote URL via wp_remote_get() and returns a JSON decoded response.
	 *
	 * @since 1.5.4.2
	 * @since 1.7.2 Switch from POST to GET request.
	 * @since 1.8.7 Added caching.
	 *
	 * @param string $action        The name of the request action var.
	 * @param array  $body          The GET query attributes.
	 * @param array  $headers       The headers to send to the remote URL.
	 * @param string $return_format The format for returning content from the remote URL.
	 *
	 * @return object|false         Json-decoded response on success, false on failure.
	 * @noinspection PhpUnusedParameterInspection
	 */
	public function perform_remote_request( string $action, array $body = [], array $headers = [], string $return_format = 'json' ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed

		/**
		 * Filter an empty response object before the request execution.
		 *
		 * Allows loading cached response if it exists.
		 *
		 * @see WPForms_Pro::get_updater_response_from_cache as an example.
		 *
		 * @since 1.8.7
		 *
		 * @param object $response_body The response body, empty by default.
		 * @param string $action        The name of the request action var.
		 * @param array  $body          The GET query attributes.
		 * @param array  $headers       The headers to send to the remote URL.
		 *
		 * @return object
		 */
		$response_body = (object) apply_filters(
			'wpforms_updater_perform_remote_request_before_response',
			new stdClass(),
			$action,
			$body,
			$headers
		);

		if ( empty( $response_body->package ) ) {
			$response_body = $this->get_real_remote_response( $action, $body, $headers );
		}

		// Return the JSON decoded content.
		return $this->repack_response( $response_body );
	}

	/**
	 * Repack the response body to match the expected format.
	 *
	 * @since 1.8.7
	 *
	 * @param object|false $response_body The response body.
	 *
	 * @return object|false
	 * @noinspection PhpMissingParamTypeInspection
	 */
	private function repack_response( $response_body ) {

		// If the package is empty, return the response body early.
		if ( empty( $response_body->package ) ) {
			return $response_body;
		}

		// Convert icons from an object to an array if they exist.
		if ( ! empty( $response_body->icons ) ) {
			$response_body->icons = (array) $response_body->icons;
		}

		// Convert banners from an object to an array if they exist.
		if ( ! empty( $response_body->banners ) ) {
			$response_body->banners = (array) $response_body->banners;
		}

		return $response_body;
	}

	/**
	 * Perform the remote request to the API.
	 *
	 * @since 1.8.7
	 *
	 * @param string $action  The name of the request action var.
	 * @param array  $body    The GET query attributes.
	 * @param array  $headers The headers to send to the remote URL.
	 *
	 * @return object|false   Json-decoded response on success, false on failure.
	 */
	private function get_real_remote_response( string $action, array $body = [], array $headers = [] ) {

		// Request query parameters.
		$query_params = wp_parse_args(
			$body,
			[
				'tgm-updater-action'      => $action,
				'tgm-updater-key'         => $this->key,
				'tgm-updater-wp-version'  => get_bloginfo( 'version' ),
				'tgm-updater-php-version' => PHP_VERSION,
				'tgm-updater-referer'     => site_url(),
			]
		);

		// Setup variable for wp_remote_post.
		$args = [
			'headers' => $headers,
		];

		// Perform the query and retrieve the response.
		$response      = wp_remote_get( add_query_arg( $query_params, $this->remote_url ), $args );
		$response_code = wp_remote_retrieve_response_code( $response );
		$response_body = wp_remote_retrieve_body( $response );

		// Bail out early if there are any errors.
		if ( $response_code !== 200 || is_wp_error( $response_body ) ) {
			return false;
		}

		return json_decode( $response_body, false ) ?? false;
	}

	/**
	 * Prepare the "mock" item to the `no_update` property.
	 * Is required for the enable/disable auto-updates links to correctly appear in the UI.
	 *
	 * @since 1.6.4
	 *
	 * @return object
	 */
	public function get_no_update() {

		return (object) [
			'id'            => $this->plugin_path,
			'slug'          => $this->plugin_slug,
			'plugin'        => $this->plugin_path,
			'new_version'   => $this->version,
			'url'           => '',
			'package'       => '',
			'icons'         => [],
			'banners'       => [],
			'banners_rtl'   => [],
			'tested'        => '',
			'requires_php'  => '',
			'compatibility' => new stdClass(),
		];
	}

	/**
	 * Determine if the class is allowed to load.
	 *
	 * @since 1.9.0
	 *
	 * @return bool
	 */
	private function allow_load(): bool {

		if ( $this->is_allowed !== null ) {
			return $this->is_allowed;
		}

		if ( wp_doing_cron() || wpforms_doing_wp_cli() ) {
			$this->is_allowed = true;

			return true;
		}

		// If a user cannot update plugins, stop processing here.
		if ( ! current_user_can( 'update_plugins' ) ) {
			$this->is_allowed = false;

			return false;
		}

		$this->is_allowed = $this->is_update();

		return $this->is_allowed;
	}

	/**
	 * Whether is an update page or action.
	 *
	 * @since 1.9.1
	 *
	 * @return bool
	 */
	private function is_update(): bool {

		global $pagenow;

		// phpcs:disable WordPress.Security.NonceVerification.Missing
		$action = isset( $_POST['action'] ) ? sanitize_text_field( wp_unslash( $_POST['action'] ) ) : '';
		$slug   = isset( $_POST['slug'] ) ? sanitize_text_field( wp_unslash( $_POST['slug'] ) ) : '';
		// phpcs:enable WordPress.Security.NonceVerification.Missing

		$is_update_action = $pagenow === 'admin-ajax.php' && $action === 'update-plugin' && $slug === $this->plugin_slug;
		$is_update_page   = in_array(
			$pagenow ?? '',
			[
				'update-core.php',
				'plugins.php',
				'plugin-install.php',
				'update.php',
			],
			true
		);

		// We should only run the update check on the update-core.php or plugins.php page,
		// or when the user is updating the plugin.
		return $is_update_page || $is_update_action;
	}

	/**
	 * Detect core plugin.
	 *
	 * @since 1.9.0
	 *
	 * @return bool
	 */
	private function is_core_plugin(): bool {

		return $this->plugin_slug === 'wpforms';
	}

	/**
	 * Check if the plugin is compatible with the current environment.
	 *
	 * @since 1.9.0
	 *
	 * @param object $data The plugin data.
	 *
	 * @return bool
	 */
	private function is_plugin_compatible( $data ): bool {

		return is_php_version_compatible( $data->requires_php ?? '' ) &&
			is_wp_version_compatible( $data->requires ?? '' ) &&
			( $this->is_core_plugin() || PluginList::is_wpforms_version_compatible( $data->requires_wpforms ?? '' ) );
	}

	/**
	 * Check if the license is valid.
	 *
	 * @since 1.9.0
	 *
	 * @return bool
	 */
	private function is_valid_license(): bool {

		return ( new PluginList() )->is_valid_license();
	}
}
