<?php
/**
 * Resolver for the Legacy Licensing and Management plugin.
 *
 * @since 4.18.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Licensing\Legacy;

/**
 * Legacy Licensing and Management plugin resolver class.
 *
 * @since 4.18.0
 */
class Resolver {
	/**
	 * Legacy Licensing and Management plugin directory name default.
	 *
	 * @since 4.18.0
	 *
	 * @var string
	 */
	public static string $plugin_directory_default = 'learndash-hub';

	/**
	 * Legacy Licensing and Management plugin file name.
	 *
	 * @since 4.18.0
	 *
	 * @var string
	 */
	public static string $plugin_file = 'learndash-hub.php';

	/**
	 * This is the closest thing that the legacy Licensing and Management plugin has to a directory constant.
	 * This is all that we can reliably use to know that the plugin has been loaded already.
	 *
	 * @since 4.18.0
	 *
	 * @var string
	 */
	public static string $plugin_basename_constant = 'HUB_PLUGIN_BASENAME';

	/**
	 * Returns the Legacy Licensing and Management Plugin path, relative to /wp-content/plugins.
	 *
	 * As the legacy Licensing and Management Plugin does not have a directory constant and instead assumes it is always
	 * installed under `learndash-hub`, this is what we need to check for.
	 *
	 * @since 4.18.0
	 *
	 * @return string
	 */
	public static function get_plugin_path(): string {
		return trailingslashit( self::$plugin_directory_default ) . self::$plugin_file;
	}

	/**
	 * Determines whether the Legacy Licensing and Management Plugin is active.
	 *
	 * @since 4.18.0
	 *
	 * @return bool
	 */
	public static function is_plugin_active(): bool {
		$legacy_plugin_path = self::get_plugin_path();

		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		return is_plugin_active( $legacy_plugin_path )
			|| is_plugin_active_for_network( $legacy_plugin_path );
	}
}
