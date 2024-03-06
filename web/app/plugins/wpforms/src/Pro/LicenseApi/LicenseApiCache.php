<?php

namespace WPForms\Pro\LicenseApi;

use WPForms\Helpers\CacheBase;
use WPForms\Helpers\Transient;

/**
 * License api cache.
 *
 * Store the information about a plugin info or a new releases in cached json file.
 * Register an Action Scheduler task to update the cache using WPForms caching logic.
 * Allow retrieving the information about a plugin info or a new releases from the cache.
 *
 * @since 1.8.7
 */
abstract class LicenseApiCache extends CacheBase {

	/**
	 * Cache time in hours.
	 *
	 * @since 1.8.7
	 *
	 * @var int
	 */
	const CACHE_TIME = 6;

	/**
	 * Type.
	 *
	 * @since 1.8.7
	 *
	 * @var string
	 */
	protected $type;

	/**
	 * Plugin slug.
	 *
	 * @since 1.8.7
	 *
	 * @var string
	 */
	protected $plugin_slug;

	/**
	 * Expirable URL key.
	 *
	 * @since 1.8.7
	 *
	 * @var string|bool
	 */
	protected $expirable_url_key = false;

	/**
	 * Initialize.
	 *
	 * Normalize the plugin slug and type.
	 *
	 * @since 1.8.7
	 */
	public function init() {

		$this->plugin_slug = strtolower( str_replace( '_', '-', $this->plugin_slug ) );
		$this->plugin_slug = $this->plugin_slug === 'wpforms' ? 'wpforms-pro' : $this->plugin_slug;

		parent::init();
	}

	/**
	 * Determine if the class is allowed to load.
	 *
	 * @since 1.8.7
	 *
	 * @return bool
	 */
	protected function allow_load(): bool {

		$allow = $this->plugin_slug && is_string( $this->plugin_slug );

		/**
		 * Whether to load this class.
		 *
		 * @since 1.8.7
		 *
		 * @param bool $allow True or false.
		 */
		return (bool) apply_filters( $this->plugin_slug . '_license_api_' . $this->type . '_cache_allow_load', $allow ); // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName
	}

	/**
	 * Provide settings.
	 *
	 * @since 1.8.7
	 *
	 * @return array Settings array.
	 */
	protected function setup(): array {

		/**
		 * Downloaded files could contain sensitive information, so we need to add hash to the filename.
		 * Otherwise, anyone knowing the site has WPForms installed could download the files.
		 */
		$file_name = $this->plugin_slug . '-' . $this->type;

		$file_name .= '-' . wp_hash( $file_name ) . '.json';

		return [
			'remote_source' => WPFORMS_UPDATER_API,
			'cache_file'    => $file_name,
			/**
			 * Time-to-live of the templates cache files in seconds.
			 *
			 * @since 1.8.7
			 *
			 * @param int $cache_ttl Time-to-live of the templates cache files in seconds.
			 *
			 * @return int
			 */
			'cache_ttl'     => (int) apply_filters( $this->plugin_slug . '_license_api_' . $this->type . '_cache_ttl', HOUR_IN_SECONDS * self::CACHE_TIME ), // phpcs:ignore WPForms.PHP.ValidateHooks.InvalidHookName
			'update_action' => $this->plugin_slug . '_license_api_' . $this->type . '_cache',
			'query_args'    => [
				'tgm-updater-action'      => $this->get_action_slug(),
				'tgm-updater-wp-version'  => get_bloginfo( 'version' ),
				'tgm-updater-php-version' => PHP_VERSION,
				'tgm-updater-referer'     => site_url(),
				'tgm-updater-plugin'      => $this->plugin_slug === 'wpforms-pro' ? 'wpforms' : $this->plugin_slug,
			],
		];
	}

	/**
	 * Get action slug.
	 *
	 * @since 1.8.7
	 *
	 * @return string
	 */
	protected function get_action_slug(): string {

		return 'get-' . $this->type;
	}

	/**
	 * Reschedule cache update if it is scheduled beyond URL expiration time.
	 *
	 * @since 1.8.7
	 *
	 * @param array $data Data received by the remote request.
	 *
	 * @return bool|array
	 */
	protected function maybe_update_transient( array $data ) {

		if (
			$this->expirable_url_key === false ||
			! isset( $data[ $this->expirable_url_key ], $this->settings['cache_ttl'], $this->settings['cache_file'] )
		) {
			return false;
		}

		// Get a params array from URL.
		$url_params = wp_parse_args( wp_parse_url( $data[ $this->expirable_url_key ], PHP_URL_QUERY ) );

		if ( ! isset( $url_params['Expires'] ) ) {
			return false;
		}

		// Get expiration time from URL as timestamp.
		$expiration = $url_params['Expires'];

		// Get transient duration time in seconds.
		$duration = $this->settings['cache_ttl'];

		// If expiration time is less than transient duration time, then update transient duration time.
		$time = time();

		if ( $expiration > ( $time + $duration ) ) {
			return false;
		}

		$duration = $expiration - $time - 1;

		Transient::set( $this->settings['cache_file'], $time, $duration );

		return $data;
	}
}
