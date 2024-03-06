<?php

namespace WPForms\Pro\LicenseApi;

/**
 * License api validate key cache.
 *
 * @since 1.8.7
 */
class ValidateKeyCache extends LicenseApiCache {

	/**
	 * Constructor.
	 *
	 * @since 1.8.7
	 */
	public function __construct() {

		$this->plugin_slug = 'wpforms-pro';
		$this->type        = 'validate-key';
	}

	/**
	 * Get action slug.
	 *
	 * @since 1.8.7
	 *
	 * @return string
	 */
	protected function get_action_slug(): string {

		return $this->type;
	}
}
