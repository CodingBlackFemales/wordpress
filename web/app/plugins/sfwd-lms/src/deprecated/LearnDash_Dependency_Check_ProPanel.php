<?php
/**
 * Deprecated. This class is no longer needed.
 *
 * Set up ProPanel Dependency Check.
 *
 * @since 4.17.0
 * @deprecated 4.17.0
 *
 * @package LearnDash\Deprecated
 */

_deprecated_file( __FILE__, '4.17.0' );

if ( class_exists( 'LearnDash_Dependency_Check_ProPanel' ) ) {
	return;
}

/**
 * ProPanel Dependency Check.
 *
 * @since 4.17.0
 * @deprecated 4.17.0
 */
final class LearnDash_Dependency_Check_ProPanel {
	/**
	 * Current instance.
	 *
	 * @since 4.17.0
	 * @deprecated 4.17.0
	 *
	 * @var LearnDash_Dependency_Check_ProPanel
	 */
	private static $instance;

	/**
	 * The displayed message shown to the user on admin pages.
	 *
	 * @since 4.17.0
	 * @deprecated 4.17.0
	 *
	 * @var string
	 */
	private $admin_notice_message = '';

	/**
	 * The array of plugin) to check Should be key => label paird. The label can be anything to display
	 *
	 * @since 4.17.0
	 * @deprecated 4.17.0
	 *
	 * @var array<string, array{label: string, class?: string, min_version?: string, version_constant?: string}>
	 */
	private $plugins_to_check = array();

	/**
	 * Array to hold the inactive plugins. This is populated during the
	 * admin_init action via the function call to check_inactive_plugin_dependency().
	 *
	 * @since 4.17.0
	 * @deprecated 4.17.0
	 *
	 * @var array<string, array{label: string, class?: string, min_version?: string, version_constant?: string}>
	 */
	private $plugins_inactive = array();


	/**
	 * LearnDash_ProPanel constructor.
	 *
	 * @since 4.17.0
	 * @deprecated 4.17.0
	 */
	public function __construct() {
		_deprecated_constructor( __CLASS__, '4.17.0' );

		add_action( 'plugins_loaded', array( $this, 'plugins_loaded' ), 1 );
	}

	/**
	 * Gets the current instance.
	 *
	 * @since 4.17.0
	 * @deprecated 4.17.0
	 *
	 * @return LearnDash_Dependency_Check_ProPanel
	 */
	public static function get_instance() {
		_deprecated_function( __METHOD__, '4.17.0' );

		if ( null === self::$instance ) {
			self::$instance = new self();
		}

		return self::$instance;
	}

	/**
	 * Checks if required plugins are active.
	 *
	 * @since 4.17.0
	 * @deprecated 4.17.0
	 *
	 * @return bool Passed/Failed check.
	 */
	public function check_dependency_results() {
		_deprecated_function( __METHOD__, '4.17.0' );

		if ( empty( $this->plugins_inactive ) ) {
			return true;
		}

		return false;
	}

	/**
	 * Callback function for the plugins_loaded action.
	 *
	 * @since 4.17.0
	 * @deprecated 4.17.0
	 *
	 * @return void
	 */
	public function plugins_loaded() {
		_deprecated_function( __METHOD__, '4.17.0' );

		$this->check_inactive_plugin_dependency();
	}

	/**
	 * Function called during the plugins_loaded process to check if required plugins are present and active.
	 * Handles regular and Multisite checks.
	 *
	 * @since 4.17.0
	 * @deprecated 4.17.0
	 *
	 * @param bool $set_admin_notice Whether to set the Admin Notice or not. Defaults to true.
	 *
	 * @return array<string, array{label: string, class?: string, min_version?: string, version_constant?: string}>
	 */
	public function check_inactive_plugin_dependency( $set_admin_notice = true ) {
		_deprecated_function( __METHOD__, '4.17.0' );

		if ( ! function_exists( 'get_plugins' ) ) {
			require_once ABSPATH . 'wp-admin/includes/plugin.php';
		}
		// $all_plugins = get_plugins();
		// error_log('all_plugins<pre>'. print_r($all_plugins, true) .'</pre>');

		// $current_plugins = get_site_transient( 'update_plugins' );
		// error_log('current_plugins<pre>'. print_r($current_plugins, true) .'</pre>');

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
			}

			if ( ( ! empty( $this->plugins_inactive ) ) && ( $set_admin_notice ) ) {
				add_action( 'admin_notices', array( $this, 'notify_user_learndash_required' ) );
			}
		}

		return $this->plugins_inactive;
	}

	/**
	 * Function to set custom admin notice message
	 *
	 * @since 4.17.0
	 * @deprecated 4.17.0
	 *
	 * @param string $message Message.
	 *
	 * @return void
	 */
	public function set_message( $message = '' ) {
		_deprecated_function( __METHOD__, '4.17.0' );

		if ( ! empty( $message ) ) {
			$this->admin_notice_message = $message;
		}
	}

	/**
	 * Sets plugin required dependencies.
	 *
	 * @since 4.17.0
	 * @deprecated 4.17.0
	 *
	 * @param array<string, array{label: string, class?: string, min_version?: string, version_constant?: string}> $plugins Array of of plugins to check.
	 *
	 * @return void
	 */
	public function set_dependencies( $plugins = array() ) {
		_deprecated_function( __METHOD__, '4.17.0' );

		if ( is_array( $plugins ) ) {
			$this->plugins_to_check = $plugins;
		}
	}

	/**
	 * Notify user that missing plugins are required.
	 *
	 * @since 4.17.0
	 * @deprecated 4.17.0
	 *
	 * @return void
	 */
	public function notify_user_learndash_required() {
		_deprecated_function( __METHOD__, '4.17.0' );

		if ( ( ! empty( $this->admin_notice_message ) ) && ( ! empty( $this->plugins_inactive ) ) ) {
			$admin_notice_message = sprintf( $this->admin_notice_message . ' %s', implode( ', ', wp_list_pluck( $this->plugins_inactive, 'label' ) ) );
			if ( ! empty( $admin_notice_message ) ) {
				?>
				<div class="notice notice-error ld-notice-error is-dismissible">
					<p><?php echo $admin_notice_message; ?></p>
				</div>
				<?php
			}
		}
	}
}
