<?php
/**
 * Deprecated LearnDash WooCommerce Dependency Check class file.
 *
 * @since 5.2.0
 * @deprecated 5.2.0
 *
 * @package LearnDash\WooCommerce\Deprecated
 */

_deprecated_file(
	__FILE__,
	'2.0.0',
	esc_html(
		LEARNDASH_WOOCOMMERCE_PLUGIN_PATH . 'src/App/Dependency_Checker.php'
	)
);

/**
 * Deprecated LearnDash_Dependency_Check_LD_WooCommerce class.
 *
 * Kept for backward compatibility if in any case the class is referred directly.
 *
 * @since 5.2.0
 * @deprecated 5.2.0 Use LearnDash\WooCommerce\Dependency_Checker instead.
 */
class LearnDash_Dependency_Check_LD_WooCommerce {
	/**
	 * Instance of our class.
	 *
	 * @since 5.2.0
	 * @deprecated 5.2.0
	 *
	 * @var object $instance
	 */
	private static $instance;

	/**
	 * The displayed message shown to the user on admin pages.
	 *
	 * @since 5.2.0
	 * @deprecated 5.2.0
	 *
	 * @var string $admin_notice_message
	 */
	private $admin_notice_message = '';

	/**
	 * The array of plugins to check Should be key => label pair. The label can be anything to display.
	 *
	 * @since 5.2.0
	 * @deprecated 5.2.0
	 *
	 * @var array $plugins_to_check
	 */
	private $plugins_to_check = [];

	/**
	 * Array to hold the inactive plugins. This is populated during the
	 * admin_init action via the function call to check_inactive_plugin_dependency().
	 *
	 * @since 5.2.0
	 * @deprecated 5.2.0
	 *
	 * @var array $plugins_inactive
	 */
	private $plugins_inactive = [];

	/**
	 * Constructor.
	 *
	 * @since 5.2.0
	 * @deprecated 5.2.0
	 */
	public function __construct() {
		_deprecated_class(
			__CLASS__,
			'2.0.0',
			'LearnDash\WooCommerce\Dependency_Checker'
		);

		_deprecated_constructor(
			__CLASS__,
			'2.0.0'
		);

		add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ], 1 );
	}

	/**
	 * Returns the instance of this class or new one.
	 *
	 * @since 5.2.0
	 * @deprecated 5.2.0
	 */
	public static function get_instance() {
		_deprecated_function(
			__METHOD__,
			'2.0.0',
			'LearnDash\WooCommerce\Dependency_Checker::get_instance'
		);

		if ( null === static::$instance ) {
			static::$instance = new static();
		}

		return static::$instance;
	}

	/**
	 * Check if required plugins are not active.
	 *
	 * @since 5.2.0
	 * @deprecated 5.2.0
	 */
	public function check_dependency_results() {
		_deprecated_function(
			__METHOD__,
			'2.0.0',
			'LearnDash\WooCommerce\Dependency_Checker::check_dependency_results'
		);

		if ( empty( $this->plugins_inactive ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Callback function for the admin_init action.
	 *
	 * @since 5.2.0
	 * @deprecated 5.2.0
	 */
	public function plugins_loaded() {
		_deprecated_function(
			__METHOD__,
			'2.0.0',
			'LearnDash\WooCommerce\Dependency_Checker::plugins_loaded'
		);

		$this->check_inactive_plugin_dependency();
	}

	/**
	 * Function called during the admin_init process to check if required plugins
	 * are present and active. Handles regular and Multisite checks.
	 *
	 * @since 5.2.0
	 * @deprecated 5.2.0
	 */
	public function check_inactive_plugin_dependency( $set_admin_notice = true ) {
		_deprecated_function(
			__METHOD__,
			'2.0.0',
			'LearnDash\WooCommerce\Dependency_Checker::check_inactive_plugin_dependency'
		);

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}

		if ( ! empty( $this->plugins_to_check ) ) {
			if ( ! function_exists( 'is_plugin_active' ) ) {
				include_once ABSPATH . 'wp-admin/includes/plugin.php';
			}

			foreach ( $this->plugins_to_check as $plugin_key => $plugin_data ) {
				if ( ! is_plugin_active( $plugin_key ) ) {
					if ( is_multisite() ) {
						if ( ! is_plugin_active_for_network( $plugin_key ) ) {
							$this->plugins_inactive[ $plugin_key ] = $plugin_data;
						}
					} else {
						$this->plugins_inactive[ $plugin_key ] = $plugin_data;
					}
				} elseif ( ( isset( $plugin_data['class'] ) ) && ( ! empty( $plugin_data['class'] ) ) && ( ! class_exists( $plugin_data['class'] ) ) ) {
						$this->plugins_inactive[ $plugin_key ] = $plugin_data;
				}

				if ( ( ! isset( $this->plugins_inactive[ $plugin_key ] ) ) && ( isset( $plugin_data['min_version'] ) ) && ( ! empty( $plugin_data['min_version'] ) ) ) {
					if ( ( 'sfwd-lms/sfwd_lms.php' === $plugin_key ) && ( defined( 'LEARNDASH_VERSION' ) ) ) {
						// Special logic for LearnDash since it can be installed in any directory.
						if ( version_compare( LEARNDASH_VERSION, $plugin_data['min_version'], '<' ) ) {
							$this->plugins_inactive[ $plugin_key ] = $plugin_data;
						}
					} elseif ( file_exists( trailingslashit( str_replace( '\\', '/', WP_PLUGIN_DIR ) ) . $plugin_key ) ) {
							$plugin_header = get_plugin_data( trailingslashit( str_replace( '\\', '/', WP_PLUGIN_DIR ) ) . $plugin_key );
						if ( version_compare( $plugin_header['Version'], $plugin_data['min_version'], '<' ) ) {
							$this->plugins_inactive[ $plugin_key ] = $plugin_data;
						}
					}
				}
			}

			if ( ( ! empty( $this->plugins_inactive ) ) && ( $set_admin_notice ) ) {
				add_action( 'admin_notices', [ $this, 'notify_required' ] );
			}
		}

		return $this->plugins_inactive;
	}

	/**
	 * Function to set custom admin notice message.
	 *
	 * @since 5.2.0
	 * @deprecated 5.2.0
	 *
	 * @param string $message Message.
	 */
	public function set_message( $message = '' ) {
		_deprecated_function(
			__METHOD__,
			'2.0.0',
			'LearnDash\WooCommerce\Dependency_Checker::set_message'
		);

		if ( ! empty( $message ) ) {
			$this->admin_notice_message = $message;
		}
	}

	/**
	 * Set plugin required dependencies.
	 *
	 * @since 5.2.0
	 * @deprecated 5.2.0
	 *
	 * @param array $plugins Array of plugins to check.
	 */
	public function set_dependencies( $plugins = [] ) {
		_deprecated_function(
			__METHOD__,
			'2.0.0',
			'LearnDash\WooCommerce\Dependency_Checker::set_dependencies'
		);

		if ( is_array( $plugins ) ) {
			$this->plugins_to_check = $plugins;
		}
	}

	/**
	 * Notify user that LearnDash is required.
	 *
	 * @since 5.2.0
	 * @deprecated 5.2.0
	 */
	public function notify_required() {
		_deprecated_function(
			__METHOD__,
			'2.0.0',
			'LearnDash\WooCommerce\Dependency_Checker::notify_required'
		);

		if ( ( ! empty( $this->admin_notice_message ) ) && ( ! empty( $this->plugins_inactive ) ) ) {
			$plugins_list_str = '';
			foreach ( $this->plugins_inactive as $plugin ) {
				if ( ! empty( $plugins_list_str ) ) {
					$plugins_list_str .= ', ';
				}
				$plugins_list_str .= $plugin['label'];

				if ( ( isset( $plugin['min_version'] ) ) && ( ! empty( $plugin['min_version'] ) ) ) {
					$plugins_list_str .= ' v' . $plugin['min_version'];
				}
			}
			if ( ! empty( $plugins_list_str ) ) {
				$admin_notice_message = sprintf( $this->admin_notice_message . '<br />%s', $plugins_list_str );
				if ( ! empty( $admin_notice_message ) ) {
					?>
					<div class="notice notice-error ld-notice-error is-dismissible">
						<p><?php echo wp_kses_post( $admin_notice_message ); ?></p>
					</div>
					<?php
				}
			}
		}
	}
}
