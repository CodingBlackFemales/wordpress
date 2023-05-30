<?php
/**
 * Set up LearnDash Dependency Check
 *
 * @package LearnDash
 * @since 1.0.0
 */

if ( ! class_exists( 'LearnDash_Dependency_Check_LD_Zapier' ) ) {

	final class LearnDash_Dependency_Check_LD_Zapier {

		/**
		 * Instance of our class.
		 *
		 * @var object $instance
		 */
		private static $instance;

		/**
		 * The displayed message shown to the user on admin pages.
		 *
		 * @var string $admin_notice_message
		 */
		private $admin_notice_message = '';

		/**
		 * The array of plugin) to check Should be key => label paird. The label can be anything to display
		 *
		 * @var array $plugins_to_check
		 */
		private $plugins_to_check = array();

		/**
		 * Array to hold the inactive plugins. This is populated during the
		 * admin_init action via the function call to check_inactive_plugin_dependency()
		 *
		 * @var array $plugins_inactive
		 */
		private $plugins_inactive = array();

		/**
		 * LearnDash_ProPanel constructor.
		 */
		public function __construct() {
			add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 1 );
		}

		/**
		 * Returns the instance of this class or new one.
		 */
		public static function get_instance() {
			if ( static::$instance === null ) {
				static::$instance = new static();
			}

			return static::$instance;
		}

		/**
		 * Check if required plugins are not active.
		 */
		public function check_dependency_results() {
			if ( empty( $this->plugins_inactive ) ) {
				return true;
			}

			return false;
		}

		/**
		 * Callback function for the admin_init action.
		 */
		public function plugins_loaded() {
			$this->check_inactive_plugin_dependency();
		}

		/**
		 * Function called during the admin_init process to check if required plugins
		 * are present and active. Handles regular and Multisite checks.
		 */
		public function check_inactive_plugin_dependency( $set_admin_notice = true ) {
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
					} else {
						if ( ( isset( $plugin_data['class'] ) ) && ( ! empty( $plugin_data['class'] ) ) && ( ! class_exists( $plugin_data['class'] ) ) ) {
							$this->plugins_inactive[ $plugin_key ] = $plugin_data;
						}
					}

					if ( ( ! isset( $this->plugins_inactive[ $plugin_key ] ) ) && ( isset( $plugin_data['min_version'] ) ) && ( ! empty( $plugin_data['min_version'] ) ) ) {
						if ( ( $plugin_key === 'sfwd-lms/sfwd_lms.php' ) && ( defined( 'LEARNDASH_VERSION' ) ) ) {
							// Special logic for LearnDash since it can be installed in any directory.
							if ( version_compare( LEARNDASH_VERSION, $plugin_data['min_version'], '<' ) ) {
								$this->plugins_inactive[ $plugin_key ] = $plugin_data;
							}
						} else {
							if ( file_exists( trailingslashit( str_replace( '\\', '/', WP_PLUGIN_DIR ) ) . $plugin_key ) ) {
								$plugin_header = get_plugin_data( trailingslashit( str_replace( '\\', '/', WP_PLUGIN_DIR ) ) . $plugin_key );
								if ( version_compare( $plugin_header['Version'], $plugin_data['min_version'], '<' ) ) {
									$this->plugins_inactive[ $plugin_key ] = $plugin_data;
								}
							}
						}
					}
				}

				if ( ( ! empty( $this->plugins_inactive ) ) && ( $set_admin_notice ) ) {
					add_action( 'admin_notices', array( $this, 'notify_required' ) );
				}
			}

			return $this->plugins_inactive;
		}

		/**
		 * Function to set custom admin motice message
		 *
		 * @since 1.0.0
		 * @param string $message Message.
		 */
		public function set_message( $message = '' ) {
			if ( ! empty( $message ) ) {
				$this->admin_notice_message = $message;
			}
		}

		/**
		 * Set plugin required dependencies.
		 *
		 * @since 1.0.0
		 * @param array $plugins Array of of plugins to check.
		 */
		public function set_dependencies( $plugins = array() ) {
			if ( is_array( $plugins ) ) {
				$this->plugins_to_check = $plugins;
			}
		}

		/**
		 * Notify user that LearnDash is required.
		 */
		public function notify_required() {
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
