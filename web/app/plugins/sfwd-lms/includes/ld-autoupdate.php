<?php
/**
 * Plugin updater
 *
 * @since 2.1.0
 *
 * @package LearnDash\Updater
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'nss_plugin_updater_sfwd_lms' ) ) {

	/**
	 * Class to update LearnDash
	 */
	class nss_plugin_updater_sfwd_lms {

		/**
		 * The plugin current version
		 *
		 * @var string
		 */
		public $current_version;

		/**
		 * The plugin remote update path
		 *
		 * @var string
		 */
		public $update_path;

		/**
		 * The plugin remote base update path
		 *
		 * @since 4.5.0
		 *
		 * @var string
		 */
		private $update_path_base;

		/**
		 * Plugin Slug (plugin_directory/plugin_file.php)
		 *
		 * @var string
		 */
		public $plugin_slug;

		/**
		 * Plugin name (plugin_file)
		 *
		 * @var string
		 */
		public $slug;

		/**
		 * Initialized as $slug, this is used as a substring to create dynamic hooks and actions
		 *
		 * @var string
		 */
		public $code;

		/**
		 * Updater object
		 *
		 * @var object
		 */
		private $ld_updater;

		/**
		 * Upgrade notice
		 *
		 * @var array
		 */
		private $upgrade_notice = array();

		/**
		 * Minutes value of how frequent we validate the license.
		 *
		 * @since 3.6.0.3
		 *
		 * @var integer $plugin_license_cache_time_limit (minutes).
		 */
		private $plugin_license_cache_time_limit;

		/**
		 * Minutes value of how frequent we check for new plugin information.
		 *
		 * @since 3.6.0.3
		 *
		 * @var integer $plugin_info_cache_time_limit (minutes).
		 */
		private $plugin_info_cache_time_limit;

		/**
		 * Initialize a new instance of the WordPress Auto-Update class
		 *
		 * @since 2.1.0
		 *
		 * @param string $update_path Update path.
		 * @param string $plugin_slug Plugin slug.
		 */
		public function __construct( $update_path, $plugin_slug ) {

			// Set the class public variables
			// $this->update_path = $update_path;
			$this->plugin_slug      = $plugin_slug;
			$this->current_version  = LEARNDASH_VERSION;
			$this->update_path_base = $update_path;

			list ( $t1, $t2 ) = explode( '/', $plugin_slug );
			$this->slug       = str_replace( '.php', '', $t2 );
			$code             = esc_attr( $this->slug );
			$this->code       = $code;

			$this->plugin_license_cache_time_limit = 3600; // 60 minutes
			if ( ( defined( 'LEARNDASH_PLUGIN_LICENSE_INTERVAL' ) ) && ( LEARNDASH_PLUGIN_LICENSE_INTERVAL > 3600 ) ) {
				$this->plugin_license_cache_time_limit = LEARNDASH_PLUGIN_LICENSE_INTERVAL;
			}

			$this->plugin_info_cache_time_limit = 600; // 10 minutes
			if ( ( defined( 'LEARNDASH_PLUGIN_INFO_INTERVAL' ) ) && ( LEARNDASH_PLUGIN_INFO_INTERVAL > 600 ) ) {
				$this->plugin_info_cache_time_limit = LEARNDASH_PLUGIN_INFO_INTERVAL;
			}

			$license      = get_option( 'nss_plugin_license_' . $code );
			$licenseemail = get_option( 'nss_plugin_license_email_' . $code );
			if ( ( empty( $license ) ) || ( empty( $licenseemail ) ) ) {
				$this->reset();
			} else {
				if ( learndash_updates_enabled() ) {
					// Build the updater path ONLY if the license and email are not empty. This prevents unnecessary calls to the remote server.
					$this->generate_update_path();
				}
			}

			// Add Menu.
			add_action( 'admin_menu', array( $this, 'nss_plugin_license_menu' ), 1 );

			// define the alternative API for updating checking.
			add_filter( 'pre_set_site_transient_update_plugins', array( $this, 'check_update' ) );

			// Define the alternative response for information checking.
			add_filter( 'plugins_api', array( $this, 'check_info' ), 50, 3 );
			add_action( 'in_admin_header', array( $this, 'check_notice' ) );

			add_action( 'admin_notices', array( &$this, 'admin_notice_upgrade_notice' ) );
			add_action( 'in_plugin_update_message-' . $this->plugin_slug, array( $this, 'show_upgrade_notification' ), 10, 2 );

			// Handle License post update.
			add_action( 'admin_init', array( $this, 'nss_plugin_license_update' ), 1 );
		}

		/**
		 * Handle license form post updates.
		 *
		 * @since 3.0.0
		 */
		public function nss_plugin_license_update() {
			if ( ( isset( $_GET['force-check'] ) ) && ( '1' === $_GET['force-check'] ) ) {
				delete_option( 'nss_plugin_info_check_' . $this->slug );
				delete_option( 'nss_plugin_check_' . $this->slug );
			}

			// See if the user has posted us some information
			// If they did, this hidden field will be set to 'Y'.
			if ( ( isset( $_POST['ld_plugin_license_nonce'] ) ) && ( ! empty( $_POST['ld_plugin_license_nonce'] ) ) && ( wp_verify_nonce( $_POST['ld_plugin_license_nonce'], 'update_nss_plugin_license_' . $this->code ) ) ) {
				$license = '';
				if ( ( isset( $_POST[ 'nss_plugin_license_' . $this->code ] ) ) && ( ! empty( $_POST[ 'nss_plugin_license_' . $this->code ] ) ) ) {
					$license = trim( sanitize_text_field( wp_unslash( $_POST[ 'nss_plugin_license_' . $this->code ] ) ) );
				}

				$email = '';
				if ( ( isset( $_POST[ 'nss_plugin_license_email_' . $this->code ] ) ) && ( is_email( $_POST[ 'nss_plugin_license_email_' . $this->code ] ) ) ) {
					$email = trim( sanitize_text_field( wp_unslash( $_POST[ 'nss_plugin_license_email_' . $this->code ] ) ) );
				}

				// Save the posted value in the database.
				update_option( 'nss_plugin_license_' . $this->code, trim( $license ), LEARNDASH_PLUGIN_LICENSE_OPTIONS_AUTOLOAD );
				update_option( 'nss_plugin_license_email_' . $this->code, trim( $email ), LEARNDASH_PLUGIN_LICENSE_OPTIONS_AUTOLOAD );

				$this->reset();
				$this->generate_update_path();

				$this->getRemote_license();
				?>
				<script> window.location = window.location; </script>
				<?php
			}
		}

		/**
		 * Show upgrade notification
		 *
		 * @param array $current_plugin_metadata Current metadata.
		 * @param array $new_plugin_metadata     New metadata.
		 */
		public function show_upgrade_notification( $current_plugin_metadata, $new_plugin_metadata ) {
			$upgrade_notice = $this->get_plugin_upgrade_notice();
			if ( ! empty( $upgrade_notice ) ) {
				echo '</p><p class="ld-plugin-update-notice">' . str_replace( array( '<p>', '</p>' ), array( '', '<br />' ), $upgrade_notice ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
			}
		}

		/**
		 * Utility function to the status of the license.
		 */
		public function is_license_valid() {
			$license = get_option( 'nss_plugin_remote_license_' . $this->slug );
			if ( ( isset( $license['value'] ) ) && ( '1' === $license['value'] ) ) {
				return true;
			}
			return false;
		}

		/**
		 * Checks to see if a license administrative notice needs to be displayed, and if so, displays it.
		 *
		 * @since 2.1.0
		 */
		public function check_notice() {
			if ( ( isset( $_GET['force-check'] ) ) && ( '1' === $_GET['force-check'] ) ) {
				delete_option( 'nss_plugin_info_check_' . $this->slug );
				delete_option( 'nss_plugin_check_' . $this->slug );
			}

			if ( ( isset( $_REQUEST['page'] ) ) && ( 'nss_plugin_license-' . $this->code . '-settings' === $_REQUEST['page'] ) ||
				( isset( $_REQUEST['page'] ) ) && ( 'learndash-setup' === $_REQUEST['page'] ) ) {
				$this->check_update( array() );
			}

			if ( ! $this->is_license_valid() ) {
				add_action( 'admin_notices', array( $this, 'admin_notice' ) );
			}
		}

		/**
		 * Determines if the plugin should check for updates
		 *
		 * @since 2.1.0
		 *
		 * @return bool
		 */
		public function time_to_recheck() {
			return $this->time_to_recheck_license();
		}

		public function time_to_recheck_license() {
			if ( ( isset( $_REQUEST['pluginupdate'] ) ) && ( $_REQUEST['pluginupdate'] === $this->code ) ) {
				// error_log( $_SERVER['SERVER_NAME'] . ': ' . __FUNCTION__ . ': return true #2' );
				return true;
			}

			$nss_plugin_check = get_option( 'nss_plugin_check_' . $this->slug );
			$nss_plugin_check = absint( $nss_plugin_check );

			// error_log( $_SERVER['SERVER_NAME'] . ': ' . __FUNCTION__ . ': nss_plugin_check['. $nss_plugin_check . ']' );

			$time_less_interval = $nss_plugin_check + ( $this->plugin_license_cache_time_limit * MINUTE_IN_SECONDS ) - time();
			// error_log( $_SERVER['SERVER_NAME'] . ': ' . __FUNCTION__ . ': time_less_interval['. $time_less_interval . ']' );

			if ( $time_less_interval < 0 ) {
				// error_log( $_SERVER['SERVER_NAME'] . ': ' . __FUNCTION__ . ': return true #3' );
				return true;
			}

			// error_log( $_SERVER['SERVER_NAME'] . ': ' . __FUNCTION__ . ': return false' );
			return false;
		}

		/**
		 * Resets the time the plugin was checked last, and removes previous license, version, and plugin info data
		 *
		 * @since 2.1.0
		 */
		public function reset() {
			delete_option( 'nss_plugin_remote_version_' . $this->slug );
			delete_option( 'nss_plugin_remote_license_' . $this->slug );
			delete_option( 'nss_plugin_info_' . $this->slug );
			delete_option( 'nss_plugin_check_' . $this->slug );
			delete_option( 'nss_plugin_info_check_' . $this->slug );
		}

		/**
		 * Generates the update path for the plugin
		 *
		 * @since 4.5.0
		 *
		 * @return void
		 */
		public function generate_update_path() {
			$license      = get_option( 'nss_plugin_license_' . $this->code );
			$licenseemail = get_option( 'nss_plugin_license_email_' . $this->code );

			if ( empty( $license ) || empty( $licenseemail ) ) {
				return;
			}

			$this->update_path = add_query_arg(
				array(
					'pluginupdate'    => $this->code,
					'licensekey'      => rawurlencode( $license ),
					'licenseemail'    => rawurlencode( $licenseemail ),
					'nsspu_wpurl'     => rawurlencode( get_bloginfo( 'wpurl' ) ),
					'nsspu_admin'     => rawurlencode( get_bloginfo( 'admin_email' ) ),
					'nsspu_test'      => 'TEST',
					'current_version' => $this->current_version,
				),
				$this->update_path_base
			);
		}

		/**
		 * Echos the administrative notice if the plugin license is incorrect
		 *
		 * @since 2.1.0
		 */
		public function admin_notice() {
			static $notice_shown = false;

			if ( true !== $notice_shown ) {
				$current_screen = get_current_screen();
				if ( ! in_array( $current_screen->id, array( 'admin_page_nss_plugin_license-sfwd_lms-settings', 'dashboard', 'admin_page_learndash-setup' ), true ) ) {
					$notice_shown = true;

					if ( learndash_get_license_show_notice() ) {
						?>
						<div class="<?php echo esc_attr( learndash_get_license_class( 'notice notice-error is-dismissible learndash-license-is-dismissible' ) ); ?>" <?php echo learndash_get_license_data_attrs(); ?>> <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Hardcoded, escaped in function. ?>
							<p><?php echo wp_kses_post( learndash_get_license_message( 2 ) ); ?></p>
						</div>
						<?php
					}
				}
			}
		}

		/**
		 * Support for admin notice header for "Upgrade Notice Admin" header
		 * from readme.txt.
		 *
		 * @since 3.1.4
		 */
		public function admin_notice_upgrade_notice() {
			static $notice_shown_upgrade_notice = false;

			if ( true !== $notice_shown_upgrade_notice ) {

				/** This filter is documented in includes/class-ld-addons-updater.php */
				if ( apply_filters( 'learndash_upgrade_notice_admin_show', true ) ) {
					$upgrade_notice = $this->get_plugin_upgrade_notice( 'upgrade_notice_admin' );
					if ( ! empty( $upgrade_notice ) ) {
						$notice_shown_upgrade_notice = true;
						?>
						<div class="notice notice-error notice-alt is-dismissible ld-plugin-update-notice">
							<?php echo wp_kses_post( $upgrade_notice ); ?>
						</div>
						<?php
					}
				}
			}
		}

		/**
		 * Adds admin notices, and deactivates the plugin.
		 *
		 * @since 2.1.0
		 */
		public function invalid_current_license() {
			// There is NEVER a time when we want to deactive our plugin automatically.
			return;
		}

		/**
		 * Returns the metadata of the LearnDash plugin
		 *
		 * @since 2.1.0
		 *
		 * @return object Metadata of the LearnDash plugin
		 */
		public function get_plugin_data() {
			if ( ! function_exists( 'get_plugin_data' ) ) {
				include_once ABSPATH . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'plugin.php';
			}

			return (object) get_plugin_data( dirname( dirname( dirname( __FILE__ ) ) ) . DIRECTORY_SEPARATOR . $this->plugin_slug );
		}



		/**
		 * Add our self-hosted autoupdate plugin to the filter transient
		 *
		 * @since 2.1.0
		 *
		 * @param mixed $transient Value of transient.
		 *
		 * @return object $transient
		 */
		public function check_update( $transient ) {

			if ( ( isset( $_GET['force-check'] ) ) && ( $_GET['force-check'] === $this->code ) ) {
				error_log( $_SERVER['SERVER_NAME'] . ': ' . __FUNCTION__ . ': return true #2' );
				// return true;
			}

			if ( is_array( $transient ) ) {
				$transient = (object) $transient;
			}

			// If the update_pathis not set then abort.
			if ( empty( $this->update_path ) ) {
				return $transient;
			}

			$remote_version = '';
			$license        = '';

			// Get the remote version
			$info = $this->getRemote_information();
			if ( ( $info ) && ( property_exists( $info, 'new_version' ) ) ) {
				$remote_version = $info->new_version;
				update_option( 'nss_plugin_remote_version_' . $this->slug, $remote_version, LEARNDASH_PLUGIN_LICENSE_OPTIONS_AUTOLOAD );
			}

			// If a newer version is available, add the update.
			if ( ( ! empty( $remote_version ) ) && ( version_compare( $this->current_version, $remote_version, '<' ) ) ) {
				$obj              = new stdClass();
				$obj->slug        = $this->slug;
				$obj->new_version = $remote_version;
				$obj->plugin      = 'sfwd-lms/' . $this->slug;

				$obj->url     = $this->update_path;
				$obj->package = $this->update_path;

				$plugin_readme = $this->get_plugin_readme();
				if ( ! empty( $plugin_readme ) ) {
					// First we remove the properties we DON'T want from the support site.
					foreach ( array( 'sections', 'requires', 'tested', 'last_updated' ) as $property_key ) {
						if ( property_exists( $obj, $property_key ) ) {
							unset( $obj->$property_key );
						}
					}

					if ( isset( $plugin_readme['upgrade_notice'] ) ) {
						unset( $plugin_readme['upgrade_notice'] );
					}

					foreach ( $plugin_readme as $key => $val ) {
						if ( ! property_exists( $obj, $key ) ) {
							$obj->$key = $val;
						}
					}
				}

				if ( ! property_exists( $obj, 'icons' ) ) {
					// Add an image for the WP 4.9.x plugins update screen.
					$obj->icons = array(
						'default' => LEARNDASH_LMS_PLUGIN_URL . '/assets/images/ld-plugin-image.jpg',
					);
				}

				$transient->response[ $this->plugin_slug ] = $obj;
			}

			return $transient;
		}

		/**
		 * Get plugin readme
		 */
		public function get_plugin_readme() {
			$override_cache = false;
			if ( isset( $_GET['force-check'] ) ) {
				$override_cache = true;
			}

			if ( ! empty( $this->update_path ) ) {
				if ( class_exists( 'LearnDash_Addon_Updater' ) ) {
					if ( is_null( $this->ld_updater ) ) {
						$this->ld_updater = LearnDash_Addon_Updater::get_instance();
					}
					$this->ld_updater->get_addon_plugins( $override_cache );
					return $this->ld_updater->update_plugin_readme( 'learndash-core-readme', $override_cache );
				}
			}
		}

		/**
		 * Get plugin upgrade notice
		 *
		 * @param string $admin Which upgrade notice to process.
		 */
		public function get_plugin_upgrade_notice( $admin = 'upgrade_notice' ) {
			$upgrade_notice = '';

			$plugin_readme = $this->get_plugin_readme();
			if ( 'upgrade_notice' === $admin ) {
				if ( ( isset( $plugin_readme['upgrade_notice']['content'] ) ) && ( ! empty( $plugin_readme['upgrade_notice']['content'] ) ) ) {
					foreach ( $plugin_readme['upgrade_notice']['content'] as $upgrade_notice_version => $upgrade_notice_message ) {
						if ( version_compare( $upgrade_notice_version, $this->current_version, '>' ) ) {
							$upgrade_notice_message = str_replace( array( "\r\n", "\n", "\r" ), '', $upgrade_notice_message );
							$upgrade_notice_message = str_replace( '</p><p>', '<br /><br />', $upgrade_notice_message );
							$upgrade_notice_message = str_replace( '<p>', '', $upgrade_notice_message );
							$upgrade_notice_message = str_replace( '</p>', '', $upgrade_notice_message );

							$upgrade_notice .= '<p><span class="version">' . $upgrade_notice_version . '</span>: ' . $upgrade_notice_message . '</p>';
						}
					}
				}
			} elseif ( 'upgrade_notice_admin' === $admin ) {
				if ( ( isset( $plugin_readme['upgrade_notice_admin']['content'] ) ) && ( ! empty( $plugin_readme['upgrade_notice_admin']['content'] ) ) ) {
					foreach ( $plugin_readme['upgrade_notice_admin']['content'] as $upgrade_notice_version => $upgrade_notice_message ) {
						if ( version_compare( $upgrade_notice_version, $this->current_version, '>' ) ) {
							$upgrade_notice_message = str_replace( array( '<h4>', '</h4>' ), array( '<p class="header">', '</p>' ), $upgrade_notice_message );
							$upgrade_notice        .= $upgrade_notice_message;

						}
					}
				}
			}

			return $upgrade_notice;
		}

		/**
		 * Add our self-hosted description to the filter, or returns false
		 *
		 * @since 2.1.0
		 *
		 * @param bool   $false  $false.
		 * @param array  $action Action to perform.
		 * @param object $arg    Object of arguments.
		 *
		 * @return bool|object
		 */
		public function check_info( $false, $action, $arg ) {
			if ( empty( $arg ) || empty( $arg->slug ) || empty( $this->slug ) ) {
				return $false;
			}

			if ( $arg->slug === $this->slug ) {

				if ( ! $this->time_to_recheck_license() ) {
					$info = get_option( 'nss_plugin_info_' . $this->slug );
					if ( ! empty( $info ) ) {
						return $info;
					}
				}

				if ( 'plugin_information' == $action ) {
					$information = $this->getRemote_information();

					update_option( 'nss_plugin_info_' . $this->slug, $information, LEARNDASH_PLUGIN_LICENSE_OPTIONS_AUTOLOAD );
					$false = $information;
				}
			}

			return $false;
		}



		/**
		 * Return the remote version, or returns false
		 *
		 * @return bool|string $remote_version
		 */
		public function getRemote_version() {
			if ( ! empty( $this->update_path ) ) {
				// error_log( $_SERVER['SERVER_NAME'] . ': ' . __FUNCTION__ . ': update_path['. $this->update_path . ']' );

				if ( defined( 'LEARNDASH_UPDATE_HTTP_METHOD' ) ) {
					// error_log( $_SERVER['SERVER_NAME'] . ': ' . __FUNCTION__ . ': LEARNDASH_UPDATE_HTTP_METHOD['. LEARNDASH_UPDATE_HTTP_METHOD . ']' );

					if ( 'post' === LEARNDASH_UPDATE_HTTP_METHOD ) {
						$request = wp_remote_post(
							$this->update_path,
							array(
								'body'    => array( 'action' => 'version' ),
								'timeout' => LEARNDASH_HTTP_REMOTE_POST_TIMEOUT,
							)
						);
					} elseif ( 'get' === LEARNDASH_UPDATE_HTTP_METHOD ) {
						$request = wp_remote_get(
							$this->update_path,
							array(
								'body'    => array( 'action' => 'version' ),
								'timeout' => LEARNDASH_HTTP_REMOTE_POST_TIMEOUT,
							)
						);
					}
				}

				if ( ! is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) === 200 ) {
					$request_body = wp_remote_retrieve_body( $request );
					// error_log( $_SERVER['SERVER_NAME'] . ': ' . __FUNCTION__ . ': request_body['. $request_body . ']' );
					return $request_body;
				}
			}

			return false;
		}

		/**
		 * Get information about the remote version, or returns false
		 *
		 * @return bool|object
		 */
		public function getRemote_information() {
			$information = get_option( 'nss_plugin_info_' . $this->slug );

			if ( ( ! empty( $this->update_path ) ) && ( $this->time_to_recheck_information() ) ) {
				// error_log( $_SERVER['SERVER_NAME'] . ': ' . __FUNCTION__ . ': update_path['. $this->update_path . ']' );

				if ( defined( 'LEARNDASH_UPDATE_HTTP_METHOD' ) ) {
					// error_log( $_SERVER['SERVER_NAME'] . ': ' . __FUNCTION__ . ': LEARNDASH_UPDATE_HTTP_METHOD['. LEARNDASH_UPDATE_HTTP_METHOD . ']' );

					if ( 'post' === LEARNDASH_UPDATE_HTTP_METHOD ) {
						$request = wp_remote_post(
							$this->update_path,
							array(
								'body'    => array( 'action' => 'info' ),
								'timeout' => LEARNDASH_HTTP_REMOTE_POST_TIMEOUT,
							)
						);
					} elseif ( 'get' === LEARNDASH_UPDATE_HTTP_METHOD ) {
						$request = wp_remote_get(
							$this->update_path,
							array(
								'body'    => array( 'action' => 'info' ),
								'timeout' => LEARNDASH_HTTP_REMOTE_POST_TIMEOUT,
							)
						);
					}
				}

				if ( ! is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) === 200 ) {
					$request_body = wp_remote_retrieve_body( $request );
					// error_log( $_SERVER['SERVER_NAME'] . ': ' . __FUNCTION__ . ': request_body['. $request_body . ']' );

					$information = @unserialize( $request_body );
					if ( empty( $information ) ) {
						$information = new stdClass();
					}

					$plugin_readme = $this->get_plugin_readme();
					if ( ! empty( $plugin_readme ) ) {
						// First we remove the properties we DON'T want from the support site.
						foreach ( array( 'sections', 'requires', 'tested', 'last_updated' ) as $property_key ) {
							if ( property_exists( $information, $property_key ) ) {
								unset( $information->$property_key );
							}
						}

						foreach ( $plugin_readme as $key => $val ) {
							if ( ! property_exists( $information, $key ) ) {
								$information->$key = $val;
							}
						}
					}

					update_option( 'nss_plugin_info_' . $this->slug, $information, LEARNDASH_PLUGIN_LICENSE_OPTIONS_AUTOLOAD );
					update_option( 'nss_plugin_info_check_' . $this->slug, time(), LEARNDASH_PLUGIN_LICENSE_OPTIONS_AUTOLOAD );

					return $information;
				}
			}

			return $information;
		}

		/**
		 * Determines if the plugin should check for plugin information.
		 *
		 * @since 3.6.0.3
		 *
		 * @return bool
		 */
		public function time_to_recheck_information() {
			if ( ( isset( $_REQUEST['pluginupdate'] ) ) && ( $_REQUEST['pluginupdate'] === $this->code ) ) {
				// error_log( $_SERVER['SERVER_NAME'] . ': ' . __FUNCTION__ . ': return true #1' );
				return true;
			}

			// if ( ( isset( $_GET['force-check'] ) ) && ( $_GET['force-check'] === $this->code ) ) {
			// error_log( $_SERVER['SERVER_NAME'] . ': ' . __FUNCTION__ . ': return true #2' );
			// return true;
			// }

			$nss_plugin_check = get_option( 'nss_plugin_info_check_' . $this->slug );
			$nss_plugin_check = absint( $nss_plugin_check );
			// error_log( $_SERVER['SERVER_NAME'] . ': ' . __FUNCTION__ . ': nss_plugin_check['. $nss_plugin_check . ']' );

			$time_less_interval = $nss_plugin_check + ( $this->plugin_info_cache_time_limit * MINUTE_IN_SECONDS ) - time();
			// error_log( $_SERVER['SERVER_NAME'] . ': ' . __FUNCTION__ . ': time_less_interval['. $time_less_interval . ']' );

			if ( $time_less_interval < 0 ) {
				// error_log( $_SERVER['SERVER_NAME'] . ': ' . __FUNCTION__ . ': return true #3' );
				return true;
			}

			// error_log( $_SERVER['SERVER_NAME'] . ': ' . __FUNCTION__ . ': return false' );
			return false;
		}

		/**
		 * Return the status of the plugin licensing, or returns true
		 *
		 * @since 2.1.0
		 *
		 * @return bool|string $remote_license
		 */
		public function getRemote_license() {
			$license_status = get_option( 'nss_plugin_remote_license_' . $this->slug );
			if ( isset( $license_status['value'] ) ) {
				$license_status = $license_status['value'];
			} else {
				$license_status = false;
			}

			if ( ( ! empty( $this->update_path ) ) && ( $this->time_to_recheck_license() ) ) {
				// error_log( $_SERVER['SERVER_NAME'] . ': ' . __FUNCTION__ . ': update_path['. $this->update_path . ']' );

				if ( defined( 'LEARNDASH_UPDATE_HTTP_METHOD' ) ) {
					// error_log( $_SERVER['SERVER_NAME'] . ': ' . __FUNCTION__ . ': LEARNDASH_UPDATE_HTTP_METHOD['. LEARNDASH_UPDATE_HTTP_METHOD . ']' );

					if ( 'post' === LEARNDASH_UPDATE_HTTP_METHOD ) {
						$request = wp_remote_post(
							$this->update_path,
							array(
								'body'    => array( 'action' => 'license' ),
								'timeout' => LEARNDASH_HTTP_REMOTE_POST_TIMEOUT,
							)
						);
					} elseif ( 'get' === LEARNDASH_UPDATE_HTTP_METHOD ) {
						$request = wp_remote_get(
							$this->update_path,
							array(
								'body'    => array( 'action' => 'license' ),
								'timeout' => LEARNDASH_HTTP_REMOTE_POST_TIMEOUT,
							)
						);
					}
				}

				if ( ! is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) === 200 ) {
					$request_body = wp_remote_retrieve_body( $request );
					// error_log( $_SERVER['SERVER_NAME'] . ': ' . __FUNCTION__ . ': request_body['. $request_body . ']' );

					if ( '1' !== $request_body ) {
						$this->reset();
						add_action( 'admin_notices', array( &$this, 'admin_notice' ) );
						return $license_status;
					} else {
						$license_status = $request_body;
						update_option( 'nss_plugin_check_' . $this->slug, time(), LEARNDASH_PLUGIN_LICENSE_OPTIONS_AUTOLOAD );

						/**
						 * NOTE: The getRemote_license() does not update the option.
						 * So we need to do it. And it needs to be set as an array structure.
						 */
						update_option( 'nss_plugin_remote_license_' . $this->slug, array( 'value' => $license_status ), LEARNDASH_PLUGIN_LICENSE_OPTIONS_AUTOLOAD );
					}

					return $license_status;
				}
			}

			return $license_status;
		}

		/**
		 * Retrieves the current license from remote server, or returns true
		 *
		 * @since 2.1.0
		 *
		 * @return bool|string $current_license
		 */
		public function getRemote_current_license() {
			if ( ! empty( $this->update_path ) ) {
				// error_log( $_SERVER['SERVER_NAME'] . ': ' . __FUNCTION__ . ': update_path['. $this->update_path . ']' );

				if ( defined( 'LEARNDASH_UPDATE_HTTP_METHOD' ) ) {
					// error_log( $_SERVER['SERVER_NAME'] . ': ' . __FUNCTION__ . ': LEARNDASH_UPDATE_HTTP_METHOD['. LEARNDASH_UPDATE_HTTP_METHOD . ']' );

					if ( 'post' === LEARNDASH_UPDATE_HTTP_METHOD ) {
						$request = wp_remote_post(
							$this->update_path,
							array(
								'body'    => array( 'action' => 'current_license' ),
								'timeout' => LEARNDASH_HTTP_REMOTE_POST_TIMEOUT,
							)
						);
					} elseif ( 'get' === LEARNDASH_UPDATE_HTTP_METHOD ) {
						$request = wp_remote_get(
							$this->update_path,
							array(
								'body'    => array( 'action' => 'current_license' ),
								'timeout' => LEARNDASH_HTTP_REMOTE_POST_TIMEOUT,
							)
						);
					}
				}

				// error_log( 'request<pre>' . print_r( $request, true ) . '</pre>' );

				if ( ! is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) === 200 ) {
					$request_body = wp_remote_retrieve_body( $request );
					// error_log( $_SERVER['SERVER_NAME'] . ': ' . __FUNCTION__ . ': request_body['. $request_body . ']' );

					return $request_body;
				}
			}

			return true;
		}


		/**
		 * Adds the license submenu to the administrative settings page
		 *
		 * @since 2.1.0
		 */
		public function nss_plugin_license_menu() {
			add_submenu_page(
				'admin.php?page=learndash_lms_settings',
				$this->get_plugin_data()->Name . ' License',
				$this->get_plugin_data()->Name . ' License',
				LEARNDASH_ADMIN_CAPABILITY_CHECK,
				'nss_plugin_license-' . $this->code . '-settings',
				array( $this, 'nss_plugin_license_menupage' )
			);
		}

		/**
		 * Outputs the license settings page
		 *
		 * @since 2.1.0
		 */
		public function nss_plugin_license_menupage() {
			$code = $this->code;

			// must check that the user has the required capability.
			if ( ! learndash_is_admin_user() ) {
				wp_die( esc_html__( 'You do not have sufficient permissions to access this page.', 'learndash' ) );
			}

			// Read in existing option value from database.
			$license = get_option( 'nss_plugin_license_' . $code );
			$email   = get_option( 'nss_plugin_license_email_' . $code );

			$domain         = str_replace( array( 'http://', 'https://' ), '', get_bloginfo( 'url' ) );
			$license        = get_option( 'nss_plugin_license_' . $code );
			$email          = get_option( 'nss_plugin_license_email_' . $code );
			$license_status = false;

			if ( ! empty( $license ) && ! empty( $email ) ) {
				$license_status = $this->getRemote_license();
			}

			?>
			<style>
			.grayblock {
				border: solid 1px #ccc;
				background: #eee;
				padding: 1px 8px;
				width: 30%;
			}
			</style>
			<div class=wrap>
				<form method="post" action="<?php echo esc_attr( $_SERVER['REQUEST_URI'] ); ?>">
					<?php
					// Use nonce for verification.
					wp_nonce_field( 'update_nss_plugin_license_' . $code, 'ld_plugin_license_nonce' );
					?>
					<h1><?php esc_html_e( 'License Settings', 'learndash' ); ?></h1>
					<br />
					<?php
					if ( '1' === $license_status ) {
						?>
						<div class="notice notice-success">
							<p><?php esc_html_e( 'Your license is valid.', 'learndash' ); ?></p>
							</div>
							<?php
					} else {
						if ( learndash_get_license_show_notice() ) {
							?>
							<div class="<?php echo esc_attr( learndash_get_license_class( 'notice notice-error is-dismissible learndash-license-is-dismissible' ) ); ?>" <?php echo learndash_get_license_data_attrs(); ?>> <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Hardcoded, escaped in function ?>
								<p>
								<?php
								echo learndash_get_license_message(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Hardcoded, escaped in function.
								?>
								</p>
							</div>
							<?php
						}
					}
					?>
					<p><label for="nss_plugin_license_email_<?php echo esc_attr( $code ); ?>"><?php esc_html_e( 'Email:', 'learndash' ); ?></label><br />

					<input id="nss_plugin_license_email_<?php echo esc_attr( $code ); ?>" name="nss_plugin_license_email_<?php echo esc_attr( $code ); ?>" style="min-width:30%" value="<?php // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeOpen,Squiz.PHP.EmbeddedPhp.ContentAfterOpen
					/** This filter is documented in https://developer.wordpress.org/reference/hooks/format_to_edit/ */
					esc_html_e( apply_filters( 'format_to_edit', $email ), 'learndash' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WP Core Hook
					?>" /></p> <?php // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentAfterEnd ?>

					<p><label ><?php esc_html_e( 'License Key:', 'learndash' ); ?></label><br />
					<input id="nss_plugin_license_<?php echo esc_attr( $code ); ?>" name="nss_plugin_license_<?php echo esc_attr( $code ); ?>" style="min-width:30%" value="<?php // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentBeforeOpen,Squiz.PHP.EmbeddedPhp.ContentAfterOpen
					/** This filter is documented in https://developer.wordpress.org/reference/hooks/format_to_edit/ */
					esc_html_e( apply_filters( 'format_to_edit', $license ), 'learndash' ); // phpcs:ignore WordPress.WP.I18n.NonSingularStringLiteralText, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- WP Core Hook
					?>" /></p> <?php // phpcs:ignore Squiz.PHP.EmbeddedPhp.ContentAfterEnd ?>

					<div class="submit">
						<input type="submit" name="update_nss_plugin_license_<?php echo esc_attr( $code ); ?>" value="<?php esc_html_e( 'Update License', 'learndash' ); ?>" class="button button-primary"/>
					</div>
				</form>

				<br><br><br><br>
				<div id="nss_license_footer">

				<?php
					/**
					 * Fires after the NSS license footer HTML.
					 *
					 * The dynamic part of the hook `$code` refers to the slug of the plugin.
					 *
					 * @since 2.1.0
					 */
					do_action( esc_attr( $code ) . '-nss_license_footer' );

				?>
				</div>
			</div>
			<?php
		}
	}

	add_action(
		'learndash_init',
		function() {
			learndash_get_updater_instance();
		}
	);
}

// Poor man's get singleton for now.
/**
 * Gets the `nss_plugin_updater_sfwd_lms` instance.
 *
 * If the instance already exists it returns the existing instance otherwise creates a new instance.
 *
 * @param bool $force_new Whether to force a new instance. @since 4.0.0
 * @return void|nss_plugin_updater_sfwd_lms The `nss_plugin_updater_sfwd_lms` instance.
 */
function learndash_get_updater_instance( $force_new = false ) {
	static $updater_sfwd_lms = null;

	if ( true === $force_new ) {
		if ( ! is_null( $updater_sfwd_lms ) ) {
			$updater_sfwd_lms = null;
		}
	}

	if ( ! is_a( $updater_sfwd_lms, 'nss_plugin_updater_sfwd_lms' ) ) {
		$nss_plugin_updater_plugin_remote_path = 'https://support.learndash.com/';
		// $nss_plugin_updater_plugin_remote_path = 'http://local-support.learndash.com/';
		$nss_plugin_updater_plugin_slug = basename( LEARNDASH_LMS_PLUGIN_DIR ) . '/sfwd_lms.php';
		$updater_sfwd_lms               = new nss_plugin_updater_sfwd_lms( $nss_plugin_updater_plugin_remote_path, $nss_plugin_updater_plugin_slug );
	}

	if ( ( $updater_sfwd_lms ) && ( is_a( $updater_sfwd_lms, 'nss_plugin_updater_sfwd_lms' ) ) ) {
		return $updater_sfwd_lms;
	}
}

/**
 * Checks Whether the learndash license is valid or not.
 *
 * @return boolean
 */
function learndash_is_learndash_license_valid() {
	if ( learndash_is_learndash_hub_active() ) { // new license system.
		return learndash_is_license_hub_valid();
	}

	$updater_sfwd_lms = learndash_get_updater_instance();
	if ( ( $updater_sfwd_lms ) && ( is_a( $updater_sfwd_lms, 'nss_plugin_updater_sfwd_lms' ) ) ) {
		return $updater_sfwd_lms->is_license_valid();
	}

	return false;
}

/**
 * Get the last license check time
 *
 * @return int The last license check time.
 */
function learndash_get_last_license_check_time() {
	if ( learndash_is_learndash_hub_active() ) { // new license system.
		return learndash_get_last_license_hub_check_time();
	}

	return intval( get_option( 'nss_plugin_check_sfwd_lms', 0 ) );
}

/**
 * Utility function to check if we should check for updates.
 *
 * Updates includes by not limited to:
 * License checks, LD core and ProPanel Updates,
 * Add-on updates, Translations.
 *
 * @since 3.1.8
 */
function learndash_updates_enabled() {
	$updates_enabled = true;

	if ( ( defined( 'LEARNDASH_UPDATES_ENABLED' ) ) && ( true !== LEARNDASH_UPDATES_ENABLED ) ) {
		$updates_enabled = false;
	}

	/**
	 * Filter for controlling update processing cycle.
	 *
	 * @since 3.1.8
	 *
	 * @param boolean $updates_enabled true.
	 * @return boolean True to process updates call. Anything else to abort.
	 */
	return (bool) apply_filters( 'learndash_updates_enabled', $updates_enabled );
}

/**
 * Check if we are showing the license notice.
 *
 * @since 3.1.8
 */
function learndash_get_license_show_notice() {
	if ( ( defined( 'LEARNDASH_LICENSE_PANEL_SHOW' ) ) && ( false === LEARNDASH_LICENSE_PANEL_SHOW ) ) {
		return false;
	}

	if ( ! learndash_updates_enabled() ) {
		$current_screen = get_current_screen();
		if ( ! in_array( $current_screen->id, array( 'admin_page_nss_plugin_license-sfwd_lms-settings', 'admin_page_learndash-setup' ), true ) ) {
			return false;
		}

		$user_id = get_current_user_id();
		if ( ! empty( $user_id ) ) {
			$notice_dismissed_timestamp = get_user_meta( $user_id, 'learndash_license_notice_dismissed', true );
			$notice_dismissed_timestamp = absint( $notice_dismissed_timestamp );
			if ( ( time() - $notice_dismissed_timestamp ) < ( DAY_IN_SECONDS ) ) {
				return false;
			}
		}
	}

	return true;
}

/**
 * Get the license notice message.
 *
 * @since 3.1.8
 *
 * @param integer $mode Which message.
 */
function learndash_get_license_message( $mode = 1 ) {
	if ( learndash_updates_enabled() ) {
		if ( 2 === $mode ) {
			$updater_sfwd_lms = learndash_get_updater_instance();
			return sprintf(
				// translators: placeholders: Plugin name. Plugin update link.
				esc_html_x( 'License of your plugin %1$s is invalid or incomplete. Please click %2$s and update your license.', 'placeholders: Plugin name. Plugin update link.', 'learndash' ),
				'<strong>' . esc_html( $updater_sfwd_lms->get_plugin_data()->Name ) . '</strong>',
				'<a href="' . get_admin_url( null, 'admin.php?page=nss_plugin_license-sfwd_lms-settings' ) . '">' . esc_html__( 'here', 'learndash' ) . '</a>'
			);
		} elseif ( 1 === $mode ) {
			return sprintf(
				// translators: placeholder: Link to purchase LearnDash.
				esc_html_x( 'Please enter your email and a valid license or %s a license now.', 'placeholder: link to purchase LearnDash', 'learndash' ),
				"<a href='http://www.learndash.com/' target='_blank' rel='noreferrer noopener'>" . esc_html__( 'buy', 'learndash' ) . '</a>'
			);
		}
	} else {
		return sprintf(
			// translators: placeholders: Plugin name. Plugin update link.
			esc_html_x( 'LearnDash update and license calls are temporarily disabled. Click %s for more information.', 'placeholders: FAQ update link.', 'learndash' ),
			'<a target="_blank" rel="noopener noreferrer" aria-label="' . esc_html__( 'opens in a new tab', 'learndash' ) . '" href="https://www.learndash.com/support/docs/faqs/why-are-the-license-updates-and-license-checks-disabled-on-my-site/">' . esc_html__( 'here', 'learndash' ) . '</a>'
		);
	}
}

/**
 * Get license notice class.
 *
 * @since 3.1.8
 *
 * @param string $class Current class.
 */
function learndash_get_license_class( $class = '' ) {
	if ( ! learndash_updates_enabled() ) {
		$class = 'notice notice-info is-dismissible learndash-updates-disabled-dismissible';
	}

	return $class;
}

/**
 * Get license notice attributes.
 *
 * @since 3.1.8
 */
function learndash_get_license_data_attrs() {
	if ( ! learndash_updates_enabled() ) {
		echo ' data-notice-dismiss-nonce="' . esc_attr( wp_create_nonce( 'notice-dismiss-nonce-' . get_current_user_id() ) ) . '" ';
	}
}

/**
 * AJAX function to handle license notice dismiss action from browser.
 *
 * @since 3.1.8
 */
function learndash_license_notice_dismissed_ajax() {
	$user_id = get_current_user_id();
	if ( ! empty( $user_id ) ) {
		if ( ( isset( $_POST['action'] ) ) && ( 'learndash_license_notice_dismissed' === $_POST['action'] ) ) {
			if ( ( isset( $_POST['learndash_license_notice_dismissed_nonce'] ) ) && ( ! empty( $_POST['learndash_license_notice_dismissed_nonce'] ) ) && ( wp_verify_nonce( $_POST['learndash_license_notice_dismissed_nonce'], 'notice-dismiss-nonce-' . $user_id ) ) ) {
				update_user_meta( $user_id, 'learndash_license_notice_dismissed', time() );
			}
		}
	}

	die();
}
add_action( 'wp_ajax_learndash_license_notice_dismissed', 'learndash_license_notice_dismissed_ajax' );

/**
 * AJAX function to handle hub upgrade notice dismiss action from browser.
 *
 * @since 4.3.1
 */
function learndash_hub_upgrade_dismissed_ajax() {
	$user_id = get_current_user_id();
	if ( ! empty( $user_id ) ) {
		if ( ( isset( $_POST['action'] ) ) && ( 'learndash_hub_upgrade_dismissed' === $_POST['action'] ) ) {
			if ( ( isset( $_POST['learndash_hub_upgrade_dismissed_nonce'] ) ) && ( ! empty( $_POST['learndash_hub_upgrade_dismissed_nonce'] ) ) && ( wp_verify_nonce( $_POST['learndash_hub_upgrade_dismissed_nonce'], 'notice-dismiss-nonce-' . $user_id ) ) ) {
				delete_option( 'learndash_show_hub_upgrade_admin_notice' );
			}
		}
	}

	die();
}
add_action( 'wp_ajax_learndash_hub_upgrade_dismissed', 'learndash_hub_upgrade_dismissed_ajax' );


/**
 * Hide the ProPanel license notice when we have disabled the LD updates.
 *
 * @since 3.1.8
 */
function learndash_license_hide_propanel_notice() {
	if ( ! learndash_updates_enabled() ) {
		?>
		<style>
		p#nss_plugin_updater_admin_notice { display:none !important; }
		</style>
		<?php
	}
}
add_filter( 'admin_footer', 'learndash_license_hide_propanel_notice', 99 );
