<?php
/**
 * Set up LearnDash Dependency Check
 *
 * @since 1.0.0
 * @deprecated 2.3.2
 *
 * @package LearnDash\Zapier\Deprecated
 */

_deprecated_file(
	__FILE__,
	'2.3.2',
	esc_html( LEARNDASH_ZAPIER_PLUGIN_PATH . '/src/App/Dependency_Checker.php' )
);

if ( ! class_exists( 'LearnDash_Dependency_Check_LD_Zapier' ) ) {
	/**
	 * Deprecated LearnDash Dependency Check class.
	 *
	 * @deprecated 2.3.2
	 */
	final class LearnDash_Dependency_Check_LD_Zapier {
		/**
		 * Instance of our class.
		 *
		 * @var object $instance
		 * @deprecated 2.3.2
		 */
		private static $instance;

		/**
		 * The displayed message shown to the user on admin pages.
		 *
		 * @deprecated 2.3.2
		 *
		 * @var string $admin_notice_message
		 */
		private $admin_notice_message = '';

		/**
		 * The array of plugin) to check Should be key => label paird. The label can be anything to display
		 *
		 * @deprecated 2.3.2
		 *
		 * @var array $plugins_to_check
		 */
		private $plugins_to_check = [];

		/**
		 * Array to hold the inactive plugins. This is populated during the
		 * admin_init action via the function call to check_inactive_plugin_dependency()
		 *
		 * @deprecated 2.3.2
		 *
		 * @var array $plugins_inactive
		 */
		private $plugins_inactive = [];

		/**
		 * LearnDash_ProPanel constructor.
		 *
		 * @deprecated 2.3.2
		 */
		public function __construct() {
			_deprecated_function( __METHOD__, '2.3.2' );

			add_action( 'plugins_loaded', [ $this, 'plugins_loaded' ], 1 );
		}

		/**
		 * Returns the instance of this class or new one.
		 *
		 * @deprecated 2.3.2
		 */
		public static function get_instance() {
			_deprecated_function( __METHOD__, '2.3.2' );

			if ( null === self::$instance ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Check if required plugins are not active.
		 *
		 * @deprecated 2.3.2
		 */
		public function check_dependency_results() {
			_deprecated_function( __METHOD__, '2.3.2' );

			if ( empty( $this->plugins_inactive ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Callback function for the admin_init action.
		 *
		 * @deprecated 2.3.2
		 */
		public function plugins_loaded() {
			_deprecated_function( __METHOD__, '2.3.2' );

			$this->check_inactive_plugin_dependency();
		}

		/**
		 * Function called during the admin_init process to check if required plugins
		 * are present and active. Handles regular and Multisite checks.
		 *
		 * @param bool $set_admin_notice Whether to set the Admin Notice or not. Defaults to true.
		 *
		 * @deprecated 2.3.2
		 */
		public function check_inactive_plugin_dependency( $set_admin_notice = true ) {
			_deprecated_function( __METHOD__, '2.3.2' );

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
		 * Function to set custom admin motice message
		 *
		 * @since 1.0.0
		 * @deprecated 2.3.2
		 *
		 * @param string $message Message.
		 */
		public function set_message( $message = '' ) {
			_deprecated_function( __METHOD__, '2.3.2' );

			if ( ! empty( $message ) ) {
				$this->admin_notice_message = $message;
			}
		}

		/**
		 * Set plugin required dependencies.
		 *
		 * @since 1.0.0
		 * @deprecated 2.3.2
		 *
		 * @param array $plugins Array of of plugins to check.
		 */
		public function set_dependencies( $plugins = [] ) {
			_deprecated_function( __METHOD__, '2.3.2' );

			if ( is_array( $plugins ) ) {
				$this->plugins_to_check = $plugins;
			}
		}

		/**
		 * Notify user that LearnDash is required.
		 *
		 * @deprecated 2.3.2
		 */
		public function notify_required() {
			_deprecated_function( __METHOD__, '2.3.2' );

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
}
