<?php

namespace WPForms\Pro\LicenseApi;

/**
 * License api plugin update cache.
 *
 * @see LicenseApiCache
 *
 * @since 1.8.7
 */
class PluginUpdateCache extends LicenseApiCache {

	/**
	 * Encrypt cached file.
	 *
	 * @since 1.8.7
	 */
	const ENCRYPT = true;

	/**
	 * A class id or array of cache class ids to sync updates with.
	 *
	 * @since 1.8.9
	 */
	const SYNC_WITH = 'license_api_plugin_info_cache';

	/**
	 * Expirable URL key.
	 *
	 * @since 1.8.7
	 *
	 * @var string|bool
	 */
	protected $expirable_url_key = 'package';

	/**
	 * Constructor.
	 *
	 * @since 1.8.7
	 */
	public function __construct() {

		$this->plugin_slug = 'wpforms-pro';
		$this->type        = 'plugin-update';
	}

	/**
	 * Initialize.
	 *
	 * @since 1.8.7
	 */
	public function init() {

		parent::init();

		// if this is GET force-check=1 set and $this->type is plugin-update, then Invalidate cache.
		// phpcs:ignore WordPress.Security.NonceVerification.Recommended
		if ( isset( $_GET['force-check'] ) && $_GET['force-check'] === '1' ) {
			$this->invalidate_cache();
		}
	}
}
