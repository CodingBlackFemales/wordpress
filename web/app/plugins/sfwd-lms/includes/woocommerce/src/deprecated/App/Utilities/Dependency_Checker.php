<?php
/**
 * Deprecated plugin dependency checker class file.
 *
 * @deprecated 5.2.0
 *
 * @package LearnDash\WooCommerce\Deprecated
 */

namespace LearnDash\WooCommerce\Utilities;

_deprecated_file(
	__FILE__,
	'2.0.0',
	esc_html(
		LEARNDASH_WOOCOMMERCE_PLUGIN_PATH . 'src/App/Dependency_Checker.php'
	)
);

/**
 * Plugin dependency checker class.
 *
 * @since 5.2.0
 * @deprecated 5.2.0 Moved to LearnDash\WooCommerce namespace.
 *
 * @phpstan-type Plugin array{
 *   class?: string,
 *   label?: string,
 *   min_version?: string,
 *   version_constant?: string
 * }
 *
 * @phpstan-type Plugins array<string, Plugin>
 */
class Dependency_Checker extends \LearnDash\WooCommerce\Dependency_Checker {}
