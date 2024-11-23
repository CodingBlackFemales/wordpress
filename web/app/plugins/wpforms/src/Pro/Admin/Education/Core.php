<?php

namespace WPForms\Pro\Admin\Education;

/**
 * Education core for Pro.
 *
 * @since 1.6.6
 */
class Core extends \WPForms\Admin\Education\Core {

	use StringsTrait;

	/**
	 * Load enqueues.
	 *
	 * @since 1.6.6
	 */
	public function enqueues() {

		parent::enqueues();

		$min = wpforms_get_min_suffix();

		wp_enqueue_script(
			'wpforms-pro-admin-education-core',
			WPFORMS_PLUGIN_URL . "assets/pro/js/admin/education/core{$min}.js",
			[ 'wpforms-admin-education-core' ],
			WPFORMS_VERSION,
			true
		);
	}
}
