<?php
/**
 * LearnDash plugin dependency checker class.
 *
 * @since 5.2.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Utilities;

/**
 * Checks whether required plugins are loaded and meet a minimum version.
 *
 * @since 5.2.0
 *
 * @phpstan-type Plugin_Requirement array{
 *     label?: string,
 *     class?: string,
 *     min_version?: string,
 *     version_constant?: string
 * }
 */
class Dependency_Checker {
	/**
	 * Plugins to check, keyed by plugin basename.
	 *
	 * @since 5.2.0
	 *
	 * @var array<string, Plugin_Requirement>
	 */
	private array $plugins_to_check = [];

	/**
	 * Plugins that failed the last check, keyed by plugin basename.
	 *
	 * @since 5.2.0
	 *
	 * @var array<string, Plugin_Requirement>
	 */
	private array $plugins_inactive = [];

	/**
	 * Sets plugin required dependencies.
	 *
	 * @since 5.2.0
	 *
	 * @param array<string, Plugin_Requirement> $plugins Array of plugins to check.
	 *
	 * @return void
	 */
	public function set_dependencies( array $plugins = [] ): void {
		$this->plugins_to_check = $plugins;
		$this->plugins_inactive = [];
	}

	/**
	 * Checks if required plugins are active and new enough.
	 *
	 * @since 5.2.0
	 *
	 * @return bool Passed/Failed check.
	 */
	public function check_dependency_results(): bool {
		$this->check_inactive_plugin_dependency();

		return empty( $this->plugins_inactive );
	}

	/**
	 * Checks if required plugins are present and active.
	 *
	 * @since 5.2.0
	 *
	 * @return array<string, Plugin_Requirement>
	 */
	public function check_inactive_plugin_dependency(): array {
		$this->plugins_inactive = [];

		if ( empty( $this->plugins_to_check ) ) {
			return [];
		}

		$this->ensure_plugin_functions_loaded();

		foreach ( $this->plugins_to_check as $plugin_key => $plugin_data ) {
			if ( ! $this->plugin_meets_requirement( $plugin_key, $plugin_data ) ) {
				$this->plugins_inactive[ $plugin_key ] = $plugin_data;
			}
		}

		return $this->plugins_inactive;
	}

	/**
	 * Whether one required plugin is loaded and new enough.
	 *
	 * @since 5.2.0
	 *
	 * @param string $plugin_basename Plugin basename.
	 * @param array  $plugin_data     Requirement for the plugin.
	 *
	 * @phpstan-param Plugin_Requirement $plugin_data
	 *
	 * @return bool
	 */
	private function plugin_meets_requirement( string $plugin_basename, array $plugin_data ): bool {
		$class_name       = Cast::to_string( $plugin_data['class'] ?? '' );
		$min_version      = Cast::to_string( $plugin_data['min_version'] ?? '' );
		$version_constant = Cast::to_string( $plugin_data['version_constant'] ?? '' );

		if ( ! $this->is_plugin_loaded( $plugin_basename, $class_name ) ) {
			return false;
		}

		return $this->meets_minimum_version( $plugin_basename, $min_version, $version_constant );
	}

	/**
	 * Whether the plugin is active, or its class is already defined.
	 *
	 * The class fallback covers a non-standard install path whose basename
	 * `is_plugin_active()` would miss.
	 *
	 * @since 5.2.0
	 *
	 * @param string $plugin_basename Plugin basename.
	 * @param string $class_name      Optional class that means the plugin is loaded.
	 *
	 * @return bool
	 */
	private function is_plugin_loaded( string $plugin_basename, string $class_name ): bool {
		if ( is_plugin_active( $plugin_basename ) ) {
			return true;
		}

		if ( $class_name === '' ) {
			return false;
		}

		return class_exists( $class_name );
	}

	/**
	 * Whether the installed copy meets `$min_version`.
	 *
	 * @since 5.2.0
	 *
	 * @param string $plugin_basename  Plugin basename.
	 * @param string $min_version      Minimum version. Empty skips the version gate.
	 * @param string $version_constant Optional constant that holds the installed version.
	 *
	 * @return bool
	 */
	private function meets_minimum_version( string $plugin_basename, string $min_version, string $version_constant ): bool {
		if ( $min_version === '' ) {
			return true;
		}

		$installed_version = $this->get_installed_version( $plugin_basename, $version_constant );

		if ( $installed_version === '' ) {
			return true;
		}

		return version_compare( $installed_version, $min_version, '>=' );
	}

	/**
	 * Installed version from a constant, or from the plugin header.
	 *
	 * @since 5.2.0
	 *
	 * @param string $plugin_basename  Plugin basename.
	 * @param string $version_constant Optional constant that holds the installed version.
	 *
	 * @return string
	 */
	private function get_installed_version( string $plugin_basename, string $version_constant ): string {
		if (
			$version_constant !== ''
			&& defined( $version_constant )
		) {
			return Cast::to_string( constant( $version_constant ) );
		}

		$plugin_file = $this->get_plugin_file_path( $plugin_basename );

		if ( ! file_exists( $plugin_file ) ) {
			return '';
		}

		$plugin_header = get_plugin_data( $plugin_file, true, false );

		return Cast::to_string( $plugin_header['Version'] );
	}

	/**
	 * Absolute path to the plugin's main file.
	 *
	 * @since 5.2.0
	 *
	 * @param string $plugin_basename Plugin basename.
	 *
	 * @return string
	 */
	private function get_plugin_file_path( string $plugin_basename ): string {
		return trailingslashit( wp_normalize_path( WP_PLUGIN_DIR ) ) . $plugin_basename;
	}

	/**
	 * Loads WordPress plugin.php when `is_plugin_active()` or `get_plugin_data()` is missing.
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	private function ensure_plugin_functions_loaded(): void {
		if (
			function_exists( 'is_plugin_active' )
			&& function_exists( 'get_plugin_data' )
		) {
			return;
		}

		require_once ABSPATH . 'wp-admin/includes/plugin.php';
	}
}
