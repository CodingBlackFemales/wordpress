<?php

namespace WPForms\Pro\Forms\Fields;

/**
 * Fields Helpers class.
 *
 * @since 1.9.2
 */
class Helpers {

	/**
	 * Enqueue `wpforms-iframe` script.
	 * Has `WPFormsIframe` global variable inside.
	 *
	 * @since 1.9.2
	 */
	public static function enqueue_iframe_script() {

		if ( wp_script_is( 'wpforms-iframe' ) ) {
			return;
		}

		$min = wpforms_get_min_suffix();

		wp_enqueue_script(
			'wpforms-iframe',
			WPFORMS_PLUGIN_URL . "assets/pro/js/frontend/iframe{$min}.js",
			[],
			WPFORMS_VERSION,
			true
		);
	}
}
