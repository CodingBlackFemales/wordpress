<?php
/**
 * Plugin updater
 *
 * @since 2.1.0
 * @deprecated 4.18.0
 *
 * @package LearnDash\Deprecated
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

_deprecated_file(
	__FILE__,
	'4.18.0',
	esc_html( LEARNDASH_LMS_PLUGIN_DIR . '/includes/ld-license.php' )
);

if ( ! class_exists( 'nss_plugin_updater_sfwd_lms' ) ) {
	/**
	 * Class to update LearnDash
	 */
	class nss_plugin_updater_sfwd_lms { // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound, PEAR.NamingConventions.ValidClassName.StartWithCapital, PEAR.NamingConventions.ValidClassName.Invalid, Generic.Classes.OpeningBraceSameLine.ContentAfterBrace -- We're not renaming the class.
		/**
		 * The plugin current version
		 *
		 * @since 3.0.0
		 * @deprecated 4.18.0
		 *
		 * @var string
		 */
		public $current_version;

		/**
		 * The plugin remote update path
		 *
		 * @since 3.0.0
		 * @deprecated 4.18.0
		 *
		 * @var string
		 */
		public $update_path;

		/**
		 * The plugin remote base update path
		 *
		 * @since 4.5.0
		 * @deprecated 4.18.0
		 *
		 * @var string
		 */
		private $update_path_base;

		/**
		 * Plugin Slug (plugin_directory/plugin_file.php)
		 *
		 * @since 3.0.0
		 * @deprecated 4.18.0
		 *
		 * @var string
		 */
		public $plugin_slug;

		/**
		 * Plugin name (plugin_file)
		 *
		 * @since 3.0.0
		 * @deprecated 4.18.0
		 *
		 * @var string
		 */
		public $slug;

		/**
		 * Initialized as $slug, this is used as a substring to create dynamic hooks and actions
		 *
		 * @since 3.0.0
		 * @deprecated 4.18.0
		 *
		 * @var string
		 */
		public $code;

		/**
		 * Updater object
		 *
		 * @since 3.0.0
		 * @deprecated 4.18.0
		 *
		 * @var object
		 */
		private $ld_updater;

		/**
		 * Upgrade notice
		 *
		 * @since 3.1.4
		 * @deprecated 4.18.0
		 *
		 * @var array
		 */
		private $upgrade_notice = array();

		/**
		 * Minutes value of how frequent we validate the license.
		 *
		 * @since 3.6.0.3
		 * @deprecated 4.18.0
		 *
		 * @var integer $plugin_license_cache_time_limit (minutes).
		 */
		private $plugin_license_cache_time_limit;

		/**
		 * Minutes value of how frequent we check for new plugin information.
		 *
		 * @since 3.6.0.3
		 * @deprecated 4.18.0
		 *
		 * @var integer $plugin_info_cache_time_limit (minutes).
		 */
		private $plugin_info_cache_time_limit;

		/**
		 * Initialize a new instance of the WordPress Auto-Update class
		 *
		 * @since 2.1.0
		 * @deprecated 4.18.0
		 *
		 * @param string $update_path Update path.
		 * @param string $plugin_slug Plugin slug.
		 */
		public function __construct( $update_path, $plugin_slug ) {
			_deprecated_constructor( __CLASS__, '4.18.0' );

			// Set the class public variables.
			$this->plugin_slug      = $plugin_slug;
			$this->current_version  = LEARNDASH_VERSION;
			$this->update_path_base = $update_path;

			list ( $t1, $t2 ) = explode( '/', $plugin_slug );
			$this->slug       = str_replace( '.php', '', $t2 );
			$code             = esc_attr( $this->slug );
			$this->code       = $code;

			$this->plugin_license_cache_time_limit = 3600; // 60 minutes.
			if ( ( defined( 'LEARNDASH_PLUGIN_LICENSE_INTERVAL' ) ) && ( LEARNDASH_PLUGIN_LICENSE_INTERVAL > 3600 ) ) {
				$this->plugin_license_cache_time_limit = LEARNDASH_PLUGIN_LICENSE_INTERVAL;
			}

			$this->plugin_info_cache_time_limit = 600; // 10 minutes.
			if ( ( defined( 'LEARNDASH_PLUGIN_INFO_INTERVAL' ) ) && ( LEARNDASH_PLUGIN_INFO_INTERVAL > 600 ) ) {
				$this->plugin_info_cache_time_limit = LEARNDASH_PLUGIN_INFO_INTERVAL;
			}

			$license      = get_option( 'nss_plugin_license_' . $code );
			$licenseemail = get_option( 'nss_plugin_license_email_' . $code );
			if ( ( empty( $license ) ) || ( empty( $licenseemail ) ) ) {
				$this->reset();
			} elseif ( learndash_updates_enabled() ) {
					// Build the updater path ONLY if the license and email are not empty. This prevents unnecessary calls to the remote server.
					$this->generate_update_path();
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
		 * @deprecated 4.18.0
		 *
		 * @return void
		 */
		public function nss_plugin_license_update() {
			_deprecated_function( __METHOD__, '4.18.0' );

			if ( ( isset( $_GET['force-check'] ) ) && ( '1' === $_GET['force-check'] ) ) {
				delete_option( 'nss_plugin_info_check_' . $this->slug );
				delete_option( 'nss_plugin_check_' . $this->slug );
			}

			// See if the user has posted us some information.
			// If they did, this hidden field will be set to 'Y'.
			if ( ( isset( $_POST['ld_plugin_license_nonce'] ) ) && ( ! empty( $_POST['ld_plugin_license_nonce'] ) ) && ( wp_verify_nonce( $_POST['ld_plugin_license_nonce'], 'update_nss_plugin_license_' . $this->code ) ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Deprecated/unused code.
				$license = '';
				if ( ( isset( $_POST[ 'nss_plugin_license_' . $this->code ] ) ) && ( ! empty( $_POST[ 'nss_plugin_license_' . $this->code ] ) ) ) {
					$license = trim( sanitize_text_field( wp_unslash( $_POST[ 'nss_plugin_license_' . $this->code ] ) ) );
				}

				$email = '';
				if ( ( isset( $_POST[ 'nss_plugin_license_email_' . $this->code ] ) ) && ( is_email( $_POST[ 'nss_plugin_license_email_' . $this->code ] ) ) ) { // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash -- Deprecated/unused code.
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
		 * @since 3.1.4
		 * @deprecated 4.18.0
		 *
		 * @param array $current_plugin_metadata Current metadata.
		 * @param array $new_plugin_metadata     New metadata.
		 *
		 * @return void
		 */
		public function show_upgrade_notification( $current_plugin_metadata, $new_plugin_metadata ) { // phpcs:ignore Generic.CodeAnalysis.UnusedFunctionParameter.FoundAfterLastUsed -- Deprecated/unused code.
			_deprecated_function( __METHOD__, '4.18.0' );

			$upgrade_notice = $this->get_plugin_upgrade_notice();
			if ( ! empty( $upgrade_notice ) ) {
				echo '</p><p class="ld-plugin-update-notice">' . str_replace( array( '<p>', '</p>' ), array( '', '<br />' ), $upgrade_notice ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML.
			}
		}

		/**
		 * Utility function to the status of the license.
		 *
		 * @since 3.0.0
		 * @deprecated 4.18.0
		 *
		 * @return bool
		 */
		public function is_license_valid() {
			_deprecated_function( __METHOD__, '4.18.0' );

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
		 * @deprecated 4.18.0
		 *
		 * @return void
		 */
		public function check_notice() {
			_deprecated_function( __METHOD__, '4.18.0' );

			if ( ( isset( $_GET['force-check'] ) ) && ( '1' === $_GET['force-check'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Deprecated/unused code.
				delete_option( 'nss_plugin_info_check_' . $this->slug );
				delete_option( 'nss_plugin_check_' . $this->slug );
			}

			if ( ( isset( $_REQUEST['page'] ) ) && ( 'nss_plugin_license-' . $this->code . '-settings' === $_REQUEST['page'] ) || // phpcs:ignore WordPress.Security.NonceVerification.Recommended, Generic.CodeAnalysis.RequireExplicitBooleanOperatorPrecedence.MissingParentheses -- Deprecated/unused code.
				( isset( $_REQUEST['page'] ) ) && ( 'learndash-setup' === $_REQUEST['page'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended, Generic.CodeAnalysis.RequireExplicitBooleanOperatorPrecedence.MissingParentheses -- Deprecated/unused code.
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
		 * @deprecated 4.18.0
		 *
		 * @return bool
		 */
		public function time_to_recheck() {
			_deprecated_function( __METHOD__, '4.18.0' );

			return $this->time_to_recheck_license();
		}

		/**
		 * Determines whether it is time to re-check the license.
		 *
		 * @since 3.6.0.3
		 * @deprecated 4.18.0
		 *
		 * @return bool
		 */
		public function time_to_recheck_license() {
			_deprecated_function( __METHOD__, '4.18.0' );

			if ( ( isset( $_REQUEST['pluginupdate'] ) ) && ( $_REQUEST['pluginupdate'] === $this->code ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Deprecated/unused code.
				return true;
			}

			$nss_plugin_check = get_option( 'nss_plugin_check_' . $this->slug );
			$nss_plugin_check = absint( $nss_plugin_check );

			$time_less_interval = $nss_plugin_check + ( $this->plugin_license_cache_time_limit * MINUTE_IN_SECONDS ) - time();

			if ( $time_less_interval < 0 ) {
				return true;
			}

			return false;
		}

		/**
		 * Resets the time the plugin was checked last, and removes previous license, version, and plugin info data
		 *
		 * @since 2.1.0
		 * @deprecated 4.18.0
		 *
		 * @return void
		 */
		public function reset() {
			_deprecated_function( __METHOD__, '4.18.0' );

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
		 * @deprecated 4.18.0
		 *
		 * @return void
		 */
		public function generate_update_path() {
			_deprecated_function( __METHOD__, '4.18.0' );

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
		 * Echos the administrative notice if the plugin license is incorrect.
		 *
		 * @since 2.1.0
		 * @deprecated 4.18.0
		 *
		 * @return void
		 */
		public function admin_notice() {
			_deprecated_function( __METHOD__, '4.18.0' );

			static $notice_shown = false;

			if ( true === $notice_shown ) {
				return;
			}

			$current_screen = get_current_screen();

			if (
				in_array(
					$current_screen->id,
					[
						'admin_page_nss_plugin_license-sfwd_lms-settings',
						'dashboard',
						'admin_page_learndash-setup',
						'admin_page_learndash_hub_licensing',
					],
					true
				)
			) {
				return;
			}

			$notice_shown = true;

			if ( learndash_is_license_hub_valid() ) {
				return;
			}

			printf(
				'<div class="%s" %s><p>%s</p></div>',
				esc_attr(
					learndash_get_license_class( 'notice notice-error is-dismissible learndash-license-is-dismissible' )
				),
				learndash_get_license_data_attrs(), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Hardcoded, escaped in function.
				wp_kses_post( learndash_get_license_message( 2 ) )
			);
		}

		/**
		 * Support for admin notice header for "Upgrade Notice Admin" header
		 * from readme.txt.
		 *
		 * @since 3.1.4
		 * @deprecated 4.18.0
		 *
		 * @return void
		 */
		public function admin_notice_upgrade_notice() {
			_deprecated_function( __METHOD__, '4.18.0' );

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
		 * @deprecated 4.18.0
		 *
		 * @return void
		 */
		public function invalid_current_license() {
			_deprecated_function( __METHOD__, '4.18.0' );
		}

		/**
		 * Returns the metadata of the LearnDash plugin
		 *
		 * @since 2.1.0
		 * @deprecated 4.18.0
		 *
		 * @return object Metadata of the LearnDash plugin
		 */
		public function get_plugin_data() {
			_deprecated_function( __METHOD__, '4.18.0' );

			if ( ! function_exists( 'get_plugin_data' ) ) {
				include_once ABSPATH . 'wp-admin' . DIRECTORY_SEPARATOR . 'includes' . DIRECTORY_SEPARATOR . 'plugin.php';
			}

			return (object) get_plugin_data( dirname( dirname( __DIR__ ) ) . DIRECTORY_SEPARATOR . $this->plugin_slug );
		}

		/**
		 * Add our self-hosted autoupdate plugin to the filter transient
		 *
		 * @since 2.1.0
		 * @deprecated 4.18.0
		 *
		 * @param mixed $transient Value of transient.
		 *
		 * @return object $transient
		 */
		public function check_update( $transient ) {
			_deprecated_function( __METHOD__, '4.18.0' );

			if ( ( isset( $_GET['force-check'] ) ) && ( $_GET['force-check'] === $this->code ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Deprecated/unused code.
				error_log( $_SERVER['SERVER_NAME'] . ': ' . __FUNCTION__ . ': return true #2' ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log, WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Deprecated/unused code.
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

			// Get the remote version.
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
		 *
		 * @since 3.1.4
		 * @deprecated 4.18.0
		 *
		 * @return string|void
		 */
		public function get_plugin_readme() {
			_deprecated_function( __METHOD__, '4.18.0' );

			$override_cache = false;
			if ( isset( $_GET['force-check'] ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Deprecated/unused code.
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
		 * @since 3.1.4
		 * @deprecated 4.18.0
		 *
		 * @param string $admin Which upgrade notice to process.
		 *
		 * @return string
		 */
		public function get_plugin_upgrade_notice( $admin = 'upgrade_notice' ) {
			_deprecated_function( __METHOD__, '4.18.0' );

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
		 * @deprecated 4.18.0
		 *
		 * @param bool   $return_false  False.
		 * @param array  $action        Action to perform.
		 * @param object $arg           Object of arguments.
		 *
		 * @return bool|object
		 */
		public function check_info( $return_false, $action, $arg ) {
			_deprecated_function( __METHOD__, '4.18.0' );

			if ( empty( $arg ) || empty( $arg->slug ) || empty( $this->slug ) ) {
				return $return_false;
			}

			if ( $arg->slug === $this->slug ) {
				if ( ! $this->time_to_recheck_license() ) {
					$info = get_option( 'nss_plugin_info_' . $this->slug );
					if ( ! empty( $info ) ) {
						return $info;
					}
				}

				if ( 'plugin_information' === $action ) {
					$information = $this->getRemote_information();

					update_option( 'nss_plugin_info_' . $this->slug, $information, LEARNDASH_PLUGIN_LICENSE_OPTIONS_AUTOLOAD );
					$return_false = $information;
				}
			}

			return $return_false;
		}

		/**
		 * Return the remote version, or returns false
		 *
		 * @since 3.0.0
		 * @deprecated 4.18.0
		 *
		 * @return bool|string $remote_version
		 */
		public function getRemote_version() {
			_deprecated_function( __METHOD__, '4.18.0' );

			if ( ! empty( $this->update_path ) ) {
				if ( defined( 'LEARNDASH_UPDATE_HTTP_METHOD' ) ) {
					// error_log( $_SERVER['SERVER_NAME'] . ': ' . __FUNCTION__ . ': LEARNDASH_UPDATE_HTTP_METHOD['. LEARNDASH_UPDATE_HTTP_METHOD . ']' );.

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
					// error_log( $_SERVER['SERVER_NAME'] . ': ' . __FUNCTION__ . ': request_body['. $request_body . ']' );.
					return $request_body;
				}
			}

			return false;
		}

		/**
		 * Get information about the remote version, or returns false
		 *
		 * @since 3.0.0
		 * @deprecated 4.18.0
		 *
		 * @return bool|object
		 */
		public function getRemote_information() {
			_deprecated_function( __METHOD__, '4.18.0' );

			$information = get_option( 'nss_plugin_info_' . $this->slug );

			if ( ( ! empty( $this->update_path ) ) && ( $this->time_to_recheck_information() ) ) {
				if ( defined( 'LEARNDASH_UPDATE_HTTP_METHOD' ) ) {
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

					$information = @unserialize( $request_body ); // phpcs:ignore WordPress.PHP.DiscouragedPHPFunctions.serialize_unserialize, WordPress.PHP.NoSilencedErrors.Discouraged -- Deprecated/unused code.
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
		 * @deprecated 4.18.0
		 *
		 * @return bool
		 */
		public function time_to_recheck_information() {
			_deprecated_function( __METHOD__, '4.18.0' );

			if ( ( isset( $_REQUEST['pluginupdate'] ) ) && ( $_REQUEST['pluginupdate'] === $this->code ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended -- Deprecated/unused code.
				return true;
			}

			$nss_plugin_check = get_option( 'nss_plugin_info_check_' . $this->slug );
			$nss_plugin_check = absint( $nss_plugin_check );

			$time_less_interval = $nss_plugin_check + ( $this->plugin_info_cache_time_limit * MINUTE_IN_SECONDS ) - time();

			if ( $time_less_interval < 0 ) {
				return true;
			}

			return false;
		}

		/**
		 * Return the status of the plugin licensing, or returns true
		 *
		 * @since 2.1.0
		 * @deprecated 4.18.0
		 *
		 * @return bool|string $remote_license
		 */
		public function getRemote_license() {
			_deprecated_function( __METHOD__, '4.18.0' );

			$license_status = get_option( 'nss_plugin_remote_license_' . $this->slug );
			if ( isset( $license_status['value'] ) ) {
				$license_status = $license_status['value'];
			} else {
				$license_status = false;
			}

			if ( ( ! empty( $this->update_path ) ) && ( $this->time_to_recheck_license() ) ) {
				if ( defined( 'LEARNDASH_UPDATE_HTTP_METHOD' ) ) {
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
		 * @deprecated 4.18.0
		 *
		 * @return bool|string $current_license
		 */
		public function getRemote_current_license() {
			_deprecated_function( __METHOD__, '4.18.0' );

			if ( ! empty( $this->update_path ) ) {
				if ( defined( 'LEARNDASH_UPDATE_HTTP_METHOD' ) ) {
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

				if ( ! is_wp_error( $request ) || wp_remote_retrieve_response_code( $request ) === 200 ) {
					$request_body = wp_remote_retrieve_body( $request );

					return $request_body;
				}
			}

			return true;
		}


		/**
		 * Adds the license submenu to the administrative settings page
		 *
		 * @since 2.1.0
		 * @deprecated 4.18.0
		 *
		 * @return void
		 */
		public function nss_plugin_license_menu() {
			_deprecated_function( __METHOD__, '4.18.0' );

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
		 * @deprecated 4.18.0
		 *
		 * @return void
		 */
		public function nss_plugin_license_menupage() {
			_deprecated_function( __METHOD__, '4.18.0' );

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
				<form method="post" action="<?php echo esc_attr( $_SERVER['REQUEST_URI'] ); ?>"><?php // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Deprecated/unused code. ?>
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
					} elseif ( learndash_get_license_show_notice() ) {
						?>
							<div class="<?php echo esc_attr( learndash_get_license_class( 'notice notice-error is-dismissible learndash-license-is-dismissible' ) ); ?>" <?php echo learndash_get_license_data_attrs(); ?>> <?php // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Hardcoded, escaped in function. ?>.
								<p>
								<?php
								echo learndash_get_license_message(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Hardcoded, escaped in function.
								?>
								</p>
							</div>
							<?php
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
				 * @deprecated 4.18.0
				 *
				 * @param string $code Plugin Slug.
				 */
				_deprecated_hook( esc_attr( $code ) . '-nss_license_footer', '4.18.0' ); // phpcs:ignore WordPress.NamingConventions.ValidHookName.UseUnderscores, WordPress.NamingConventions.PrefixAllGlobals.DynamicHooknameFound -- Deprecated/unused code.

				?>
				</div>
			</div>
			<?php
		}
	}
}
