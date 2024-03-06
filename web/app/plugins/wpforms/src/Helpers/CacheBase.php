<?php

namespace WPForms\Helpers;

use WPForms\Tasks\Tasks;

/**
 * Remote data cache handler.
 *
 * Usage example in `WPForms\Admin\Addons\AddonsCache` and `WPForms\Admin\Builder\TemplatesCache`.
 *
 * @since 1.6.8
 */
abstract class CacheBase {

	/**
	 * Encrypt cached file.
	 *
	 * @since 1.8.7
	 */
	const ENCRYPT = false;

	/**
	 * Request lock time, min.
	 *
	 * @since 1.8.7
	 */
	const REQUEST_LOCK_TIME = 15;

	/**
	 * Indicates whether the cache was updated during the current run.
	 *
	 * @since 1.6.8
	 *
	 * @var bool
	 */
	protected $updated = false;

	/**
	 * Settings.
	 *
	 * @since 1.6.8
	 *
	 * @var array
	 */
	protected $settings;

	/**
	 * Cache key.
	 *
	 * @since 1.8.2
	 *
	 * @var string
	 */
	private $cache_key;

	/**
	 * Cache dir.
	 *
	 * @since 1.8.2
	 *
	 * @var string
	 */
	private $cache_dir;

	/**
	 * Cache file.
	 *
	 * @since 1.8.2
	 *
	 * @var string
	 */
	private $cache_file;

	/**
	 * Determine if the class is allowed to load.
	 *
	 * @since 1.6.8
	 *
	 * @return bool
	 */
	abstract protected function allow_load();

	/**
	 * Initialize.
	 *
	 * @since 1.6.8
	 */
	public function init() {

		// Init settings before allow_load() as settings are used in get().
		$this->update_settings();

		$this->cache_key  = $this->settings['cache_file'];
		$this->cache_dir  = $this->get_cache_dir(); // See comment in the method.
		$this->cache_file = $this->cache_dir . $this->settings['cache_file'];

		if ( ! $this->allow_load() ) {
			return;
		}

		// Quit if settings weren't provided.
		if (
			empty( $this->settings['remote_source'] ) ||
			empty( $this->settings['cache_file'] )
		) {
			return;
		}

		$this->hooks();
	}

	/**
	 * Base hooks.
	 *
	 * @since 1.6.8
	 */
	private function hooks() {

		add_action( 'shutdown', [ $this, 'cache_dir_complete' ] );

		if ( empty( $this->settings['update_action'] ) ) {
			return;
		}

		// Schedule recurring updates.
		add_action( 'admin_init', [ $this, 'schedule_update_cache' ] );
		add_action( $this->settings['update_action'], [ $this, 'update' ] );
	}

	/**
	 * Set up settings.
	 *
	 * @since 1.6.8
	 */
	private function update_settings() {

		$default_settings = [

			// Remote source URL.
			// For instance: 'https://wpforms.com/wp-content/addons.json'.
			'remote_source' => '',

			// Cache file.
			// Just file name. For instance: 'addons.json'.
			'cache_file'    => '',

			// Cache time to live in seconds.
			'cache_ttl'     => WEEK_IN_SECONDS,

			// Scheduled update action.
			// For instance: 'wpforms_admin_addons_cache_update'.
			'update_action' => '',
			// Additional query args for the remote source URL.
			'query_args'    => [],
		];

		$this->settings = wp_parse_args( $this->setup(), $default_settings );
	}

	/**
	 * Provide settings.
	 *
	 * @since 1.6.8
	 *
	 * @return array Settings array.
	 */
	abstract protected function setup();

	/**
	 * Get a cache directory path.
	 *
	 * @since 1.6.8
	 *
	 * @return string
	 */
	protected function get_cache_dir() {

		return File::get_cache_dir();
	}

	/**
	 * Get data from cache or from API call.
	 *
	 * @since 1.8.2
	 *
	 * @return array
	 */
	public function get() {

		$cache = $this->get_from_cache();

		if ( ! empty( $cache ) && ! $this->is_expired_cache() ) {
			return $cache;
		}

		$this->update();

		return $this->get_from_cache();
	}

	/**
	 * Determine if the cache is expired.
	 *
	 * @since 1.8.2
	 *
	 * @return bool
	 */
	private function is_expired_cache(): bool {

		return $this->cache_time() + $this->settings['cache_ttl'] < time();
	}

	/**
	 * Get cache creation time.
	 *
	 * @since 1.8.2
	 *
	 * @return int
	 */
	private function cache_time() {

		return (int) Transient::get( $this->cache_key );
	}

	/**
	 * Determine if the cache file exists.
	 *
	 * @since 1.8.2
	 *
	 * @return bool
	 */
	private function exists() {

		return is_file( $this->cache_file ) && is_readable( $this->cache_file );
	}

	/**
	 * Get cache from cache file.
	 *
	 * @since 1.8.2
	 *
	 * @return array
	 */
	private function get_from_cache(): array {

		if ( ! $this->exists() ) {
			return [];
		}

		$content = File::get_contents( $this->cache_file );

		// Do not decrypt non-encrypted legacy files, they will be encrypted on the scheduled update.
		if ( static::ENCRYPT && ! wpforms_is_json( $content ) ) {
			$content = Crypto::decrypt( $content );
		}

		return (array) json_decode( $content, true );
	}

	/**
	 * Update cache.
	 *
	 * @since 1.8.2
	 *
	 * @param bool $force Force update.
	 *
	 * @return bool
	 */
	public function update( bool $force = false ): bool {

		if (
			! $force &&
			time() < $this->cache_time() + self::REQUEST_LOCK_TIME * MINUTE_IN_SECONDS
		) {
			return false;
		}

		Transient::set( $this->cache_key, time(), $this->settings['cache_ttl'] );

		if ( ! wp_mkdir_p( $this->cache_dir ) ) {
			return false;
		}

		$data    = $this->perform_remote_request();
		$content = wp_json_encode( $data );

		$this->maybe_update_transient( $data );

		if ( static::ENCRYPT ) {
			$content = Crypto::encrypt( $content );
		}

		if ( ! File::put_contents( $this->cache_file, $content ) ) {
			return false;
		}

		$this->updated = true;

		return true;
	}

	/**
	 * Get cached data.
	 *
	 * @since 1.6.8
	 * @deprecated 1.8.2
	 *
	 * @return array Cached data.
	 * @noinspection PhpUnused
	 */
	public function get_cached() {

		_deprecated_function( __METHOD__, '1.8.2 of the WPForms plugin', __CLASS__ . '::get()' );

		return $this->get();
	}

	/**
	 * Update cached data with actual data retrieved from the remote source.
	 *
	 * @since 1.6.8
	 * @deprecated 1.8.2
	 *
	 * @return array
	 * @noinspection PhpUnused
	 */
	public function update_cache() {

		_deprecated_function( __METHOD__, '1.8.2 of the WPForms plugin' );

		$this->update();

		return $this->get();
	}

	/**
	 * Get data from API.
	 *
	 * @since 1.8.2
	 *
	 * @return array
	 */
	private function perform_remote_request(): array {

		$wpforms_key = wpforms()->is_pro() ? wpforms_get_license_key() : 'lite';

		$query_args = array_merge(
			[ 'tgm-updater-key' => $wpforms_key ],
			$this->settings['query_args'] ?? []
		);

		$request = wp_remote_get(
			add_query_arg( $query_args, $this->settings['remote_source'] ),
			[
				'timeout'    => 10,
				'user-agent' => wpforms_get_default_user_agent(),
			]
		);

		if ( is_wp_error( $request ) ) {
			return [];
		}

		$json = wp_remote_retrieve_body( $request );

		if ( empty( $json ) ) {
			return [];
		}

		return $this->prepare_cache_data( json_decode( $json, true ) );
	}

	/**
	 * Schedule updates.
	 *
	 * @since 1.6.8
	 */
	public function schedule_update_cache() {

		// Just skip if not need to register scheduled action.
		if ( empty( $this->settings['update_action'] ) ) {
			return;
		}

		$tasks = wpforms()->get( 'tasks' );

		if (
			! $tasks instanceof Tasks ||
			$tasks->is_scheduled( $this->settings['update_action'] ) !== false
		) {
			return;
		}

		$tasks->create( $this->settings['update_action'] )
			->recurring( time() + $this->settings['cache_ttl'], $this->settings['cache_ttl'] )
			->params()
			->register();
	}

	/**
	 * Complete the cache directory.
	 *
	 * @since 1.6.8
	 */
	public function cache_dir_complete() {

		if ( ! $this->updated ) {
			return;
		}

		wpforms_create_upload_dir_htaccess_file();
		wpforms_create_cache_dir_htaccess_file();
		wpforms_create_index_html_file( $this->cache_dir );
		wpforms_create_index_php_file( $this->cache_dir );
	}

	/**
	 * Invalidate cache.
	 *
	 * @since 1.8.7
	 */
	public function invalidate_cache() {

		Transient::delete( $this->cache_key );
	}

	/**
	 * Prepare data to store in a local cache.
	 *
	 * @since 1.6.8
	 *
	 * @param array|mixed $data Raw data received by the remote request.
	 *
	 * @return array Prepared data for caching.
	 */
	protected function prepare_cache_data( $data ): array {

		if ( empty( $data ) || ! is_array( $data ) ) {
			return [];
		}

		return $data;
	}

	/**
	 * Maybe update transient duration time.
	 *
	 * Allows updating transient duration time if it's less than expiration time.
	 * To do this, overwrite this method in child classes.
	 *
	 * @since 1.8.7
	 *
	 * @param array $data Data received by the remote request.
	 *
	 * @return bool|array
	 */
	protected function maybe_update_transient( array $data ) {

		return $data;
	}
}
