<?php
/**
 * Plugin service provider class file.
 *
 * @since 2.3.2
 *
 * @package LearnDash\Zapier
 */

namespace LearnDash\Zapier;

/**
 * Plugin Dependency checking class.
 *
 * @since 2.3.2
 */
class Dependency_Checker {
	/**
	 * Instance of our class.
	 *
	 * @since 2.3.2
	 *
	 * @var Dependency_Checker
	 */
	protected static $instance;

	/**
	 * The displayed message shown to the user on admin pages. Defaults to an empty string.
	 *
	 * @since 2.3.2
	 *
	 * @var string
	 */
	private $admin_notice_message = '';

	/**
	 * The array of plugin) to check Should be key => label pair.
	 * The label can be anything to display.
	 * Default empty array.
	 *
	 * @since 2.3.2
	 *
	 * @var array<string, array{label: string, class?: string, min_version?: string, version_constant?: string}>
	 */
	private $plugins_to_check = [];

	/**
	 * Array to hold the inactive plugins.
	 * This is populated during the plugins_loaded action via the function call to check_inactive_plugin_dependency().
	 * Defaults to an empty array.
	 *
	 * @since 2.3.2
	 *
	 * @var array<string, array{label: string, class?: string, min_version?: string, version_constant?: string}>
	 */
	private $plugins_inactive = [];

	/**
	 * Plugin Dependency checking class.
	 *
	 * @since 2.3.2
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ], 11 );
	}

	/**
	 * Returns the instance of this class or new one.
	 *
	 * @since 2.3.2
	 *
	 * @return Dependency_Checker Instance
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Checks if required plugins are active.
	 *
	 * @since 2.3.2
	 *
	 * @return bool Passed/Failed check.
	 */
	public function check_dependency_results(): bool {
		return empty( $this->plugins_inactive );
	}

	/**
	 * Callback function for the plugins_loaded action.
	 *
	 * @since 2.3.2
	 *
	 * @return void
	 */
	public function plugins_loaded() {
		$this->check_inactive_plugin_dependency();
	}

	/**
	 * Function called during the plugins_loaded process to check if required plugins are present and active.
	 * Handles regular and Multisite checks.
	 *
	 * @since 2.3.2
	 *
	 * @param bool $set_admin_notice Whether to set the Admin Notice or not. Defaults to true.
	 *
	 * @return array<string, array{label: string, class?: string, min_version?: string, version_constant?: string}>
	 */
	public function check_inactive_plugin_dependency( bool $set_admin_notice = true ): array {
		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( empty( $this->plugins_to_check ) ) {
			return [];
		}

		if ( ! function_exists( 'is_plugin_active' ) ) {
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		foreach ( $this->plugins_to_check as $plugin_key => $plugin_data ) {
			// Check if plugin is inactive directly.
			if ( ! is_plugin_active( $plugin_key ) ) {
				if ( is_multisite() ) {
					if ( ! is_plugin_active_for_network( $plugin_key ) ) {
						$this->plugins_inactive[ $plugin_key ] = $plugin_data;
					}
				} else {
					$this->plugins_inactive[ $plugin_key ] = $plugin_data;
				}
			}

			// Check for the defined Plugin Class if the expected plugin path isn't active.
			if (
				! empty( $this->plugins_inactive[ $plugin_key ] )
				&& ! empty( $plugin_data['class'] )
				&& class_exists( $plugin_data['class'] )
			) {
				unset( $this->plugins_inactive[ $plugin_key ] );
			}

			// We've determined the plugin cannot be active, so skip further processing.
			if ( ! empty( $this->plugins_inactive[ $plugin_key ] ) ) {
				continue;
			}

			// Version Checks if plugin is active.
			if ( empty( $plugin_data['min_version'] ) ) {
				continue;
			}

			if (
				! empty( $plugin_data['version_constant'] )
				&& defined( $plugin_data['version_constant'] )
			) {
				// Check against a Version Constant.
				$version_constant = $plugin_data['version_constant'];

				if ( ! is_scalar( $version_constant ) ) {
					$version_constant = '';
				} else {
					$version_constant = strval( $version_constant );
				}

				$version_constant = constant( $version_constant );

				if ( ! is_scalar( $version_constant ) ) {
					$version_constant = '';
				} else {
					$version_constant = strval( $version_constant );
				}

				// Check against the Version Constant if we know it.
				if (
					version_compare(
						$version_constant,
						$plugin_data['min_version'],
						'<'
					)
				) {
					$this->plugins_inactive[ $plugin_key ] = $plugin_data;
				}
			} elseif (
				file_exists(
					trailingslashit(
						wp_normalize_path( WP_PLUGIN_DIR )
					) . $plugin_key
				)
			) {
				// Check against the Plugin Header.
				$plugin_header = get_plugin_data(
					trailingslashit(
						wp_normalize_path( WP_PLUGIN_DIR )
					) . $plugin_key
				);

				if (
					version_compare(
						$plugin_header['Version'],
						$plugin_data['min_version'],
						'<'
					)
				) {
					$this->plugins_inactive[ $plugin_key ] = $plugin_data;
				}
			}
		}

		if (
			! empty( $this->plugins_inactive )
			&& $set_admin_notice
		) {
			add_action( 'admin_notices', [ $this, 'notify_required' ] );
		}

		return $this->plugins_inactive;
	}

	/**
	 * Function to set custom admin notice message
	 *
	 * @since 2.3.2
	 *
	 * @param string $message Message.
	 *
	 * @return void
	 */
	public function set_message( string $message = '' ): void {
		if ( ! empty( $message ) ) {
			$this->admin_notice_message = $message;
		}
	}

	/**
	 * Sets plugin required dependencies.
	 *
	 * @since 2.3.2
	 *
	 * @param array<string, array{label: string, class?: string, min_version?: string, version_constant?: string}> $plugins Array of of plugins to check.
	 *
	 * @return void
	 */
	public function set_dependencies( array $plugins = [] ): void {
		if ( is_array( $plugins ) ) {
			$this->plugins_to_check = $plugins;
		}
	}

	/**
	 * Notify user that missing plugins are required.
	 *
	 * @since 2.3.2
	 *
	 * @return void
	 */
	public function notify_required(): void {
		if (
			empty( $this->admin_notice_message )
			|| empty( $this->plugins_inactive )
		) {
			return;
		}

		$required_plugins = [];
		foreach ( $this->plugins_inactive as $plugin ) {
			$required_plugin = $plugin['label'];

			if ( ( isset( $plugin['min_version'] ) ) && ( ! empty( $plugin['min_version'] ) ) ) {
				$required_plugin .= ' v' . $plugin['min_version'];
			}

			$required_plugins[] = $required_plugin;
		}

		$admin_notice_message = sprintf(
			$this->admin_notice_message . '<ul style="list-style-type: disc; margin-left: 1.5em;">%s</ul>',
			implode(
				"\n",
				array_map(
					function ( $required_plugin ) {
						return "<li>{$required_plugin}</li>";
					},
					$required_plugins
				)
			)
		);
		?>
		<div class="notice notice-error ld-notice-error is-dismissible">
			<p>
				<?php echo wp_kses_post( $admin_notice_message ); ?>
			</p>
		</div>
		<?php
	}
}
