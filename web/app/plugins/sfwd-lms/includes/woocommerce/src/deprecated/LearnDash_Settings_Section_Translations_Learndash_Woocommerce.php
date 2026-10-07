<?php
/**
 * Deprecated LearnDash WooCommerce translations class.
 *
 * @since 5.2.0
 * @deprecated 5.2.0
 *
 * @package LearnDash\WooCommerce\Deprecated
 */

use LearnDash\WooCommerce\Admin\Translation;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

_deprecated_file(
	__FILE__,
	'2.0.0',
	esc_html(
		LEARNDASH_WOOCOMMERCE_PLUGIN_PATH . 'src/App/Admin/Translation.php'
	)
);

/**
 * Deprecated class.
 *
 * @since 5.2.0
 * @deprecated 5.2.0
 */
class LearnDash_Settings_Section_Translations_Learndash_Woocommerce extends Translation {}
