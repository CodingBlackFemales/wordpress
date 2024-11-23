<?php

namespace WPForms\Pro\LicenseApi;

/**
 * License api plugin info cache.
 *
 * @see LicenseApiCache
 *
 * @since 1.8.7
 */
class PluginInfoCache extends LicenseApiCache {

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
	const SYNC_WITH = 'license_api_plugin_update_cache';

	/**
	 * Expirable URL key.
	 *
	 * @since 1.8.7
	 *
	 * @var string|bool
	 */
	protected $expirable_url_key = 'download_link';

	/**
	 * Constructor.
	 *
	 * @since 1.8.7
	 */
	public function __construct() {

		$this->plugin_slug = 'wpforms-pro';
		$this->type        = 'plugin-info';
	}
}
