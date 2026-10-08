<?php
/**
 * LearnDash_Dependency_Check_LD_WooCommerce class deprecated file.
 *
 * Kept for backward compatibility if in case the file is included directly.
 *
 * @since 5.2.0
 * @deprecated 5.2.0
 *
 * @package LearnDash\WooCommerce
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

_deprecated_file(
	__FILE__,
	'2.0.0',
	esc_html( LEARNDASH_WOOCOMMERCE_PLUGIN_PATH . 'src/WooCommerce/Dependency_Checker.php' )
);

require_once LEARNDASH_WOOCOMMERCE_PLUGIN_PATH . 'src/deprecated/LearnDash_Dependency_Check_LD_WooCommerce.php';
