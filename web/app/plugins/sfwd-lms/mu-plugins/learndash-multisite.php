<?php
/**
 * Plugin Name: LearnDash LMS Multisite
 * Plugin URI: http://www.learndash.com
 * Description: LearnDash LMS Plugin - Turn your WordPress site into a learning management system.
 * Version: 1.0.0
 * Author: LearnDash
 * Author URI: http://www.learndash.com
 * Text Domain: ld-multisite
 * Domain Path: /languages/
 *
 * @since 3.1.8
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	die();
}

if ( ! class_exists( 'LD_Multisite' ) ) {

	/**
	 * Class to create the instance.
	 */
	class LD_Multisite {

		/**
		 * Static instance variable to ensure
		 * only one instance of class is used.
		 *
		 * @var object $instance.
		 */
		protected static $instance = null;

		/**
		 * Internal flag to track if we are in the signup process.
		 *
		 * @var bool $doing_signup.
		 */
		private $doing_signup = false;

		/**
		 * Internal flag to track if we are in the activate process.
		 *
		 * @var bool $doing_activate.
		 */
		private $doing_activate = false;

		/**
		 * Set when the user is activated.
		 *
		 * @var int
		 */
		private $activated_user_id = 0;


		/**
		 * Get or create instance object of class.
		 *
		 * @since 1.0.0
		 */
		public static function get_instance() {
			if ( ! isset( static::$instance ) ) {
				self::$instance = new self();
			}

			return self::$instance;
		}

		/**
		 * Public constructor for class
		 *
		 * @since 1.0
		 */
		protected function __construct() {
			add_filter( 'allowed_redirect_hosts', array( $this, 'allowed_redirect_hosts' ), 30, 2 );
			add_filter( 'add_signup_meta', array( $this, 'add_signup_meta' ), 30, 1 );
			add_action( 'wpmu_activate_user', array( $this, 'wpmu_activate_user' ), 30, 3 );
			add_filter( 'network_site_url', array( $this, 'network_site_url' ), 30, 3 );
			add_action( 'before_signup_header', array( $this, 'before_signup_header' ), 30 );
			add_action( 'activate_header', array( $this, 'activate_header' ), 30 );
		}

		/**
		 * Called when the Signup process starts.
		 *
		 * @since 1.0.0
		 */
		public function before_signup_header() {
			$this->doing_signup = true;
		}

		/**
		 * Called when the Activate process starts.
		 *
		 * @since 1.0.0
		 */
		public function activate_header() {
			$this->doing_activate = true;
		}

		/**
		 * Ensure the user can get redirected back to the origin site after a password redirect.
		 *
		 * @since 1.0.0
		 * @param array  $known_hosts Known hosts. (allowed).
		 * @param string $dest_host Destination host. (optional).
		 * @return array $known_hosts.
		 */
		public function allowed_redirect_hosts( $known_hosts = array(), $dest_host = '' ) {
			if ( ( is_multisite() ) && ( ! empty( $dest_host ) ) ) {
				$http_post = isset( $_SERVER['REQUEST_METHOD'] ) && ( 'POST' === $_SERVER['REQUEST_METHOD'] );
				// Check that we are handling the 'lostpassword' action.
				if ( ( isset( $_GET['action'] ) ) && ( 'lostpassword' === $_GET['action'] ) && ( true === $http_post ) ) {
					// Also check that we are handling the LD 'resetpw' logic. // cspell:disable-line.
					if ( ( isset( $_POST['redirect_to'] ) ) && ( strpos( $_POST['redirect_to'], 'ld-resetpw=true' ) !== false ) ) { // cspell:disable-line.
						// If here we query the site table for the $dest_host.
						$args = array(
							'domain__in' => array( $dest_host ),
							'number'     => 1,
						);

						$site_query = new WP_Site_Query( $args );
						if ( ( $site_query ) && ( is_a( $site_query, 'WP_Site_Query' ) ) ) {
							if ( ( property_exists( $site_query, 'sites' ) ) && ( ! empty( $site_query->sites ) ) ) {
								foreach ( $site_query->sites as $site ) {
									if ( $site->domain === $dest_host ) {
										$known_hosts[] = $dest_host;
										break;
									}
								}
							}
						}
					}
				}
			}

			// Always return the $known_hosts.
			return $known_hosts;
		}

		/**
		 * In Multisite during the initial signup processing we need
		 * to capture the form data to use later when the user is activated.
		 *
		 * @since 1.0.0
		 *
		 * @param array $user_signup_meta User signup meta.
		 *
		 * @return array $user_signup_meta.
		 */
		public function add_signup_meta( $user_signup_meta = array() ) {
			if ( true === $this->doing_signup ) {
				$user_signup_meta['learndash_meta'] = array();

				if ( ( isset( $_POST['learndash-registration-form'] ) ) && ( wp_verify_nonce( $_POST['learndash-registration-form'], 'learndash-registration-form' ) ) ) {
					if ( ( isset( $_POST['learndash-registration-form-post'] ) ) && ( ! empty( $_POST['learndash-registration-form-post'] ) ) ) {
						$course_id = absint( $_POST['learndash-registration-form-post'] );
						if ( ( isset( $_POST['learndash-registration-form-post-nonce'] ) ) && ( wp_verify_nonce( $_POST['learndash-registration-form-post-nonce'], 'learndash-registration-form-post-' . $course_id . '-nonce' ) ) ) {
							$user_signup_meta['learndash_meta']['_ld_registered_post'] = $course_id;
						}
					}
				}

				if ( isset( $_POST['blog_id'] ) ) {
					$user_signup_meta['learndash_meta']['blog_id'] = $_POST['blog_id'];
				}

				if ( isset( $_POST['redirect_to'] ) ) {
					$user_signup_meta['learndash_meta']['redirect_to'] = $_POST['redirect_to'];
				}
			}

			// always return $user_signup_meta.
			return $user_signup_meta;
		}

		/**
		 * In Multisite when the user activates the new account we transfer the
		 * meta data to the user_meta table.
		 *
		 * @since 1.0.0
		 * @param int    $user_id  New User ID.
		 * @param string $password New User password.
		 * @param array  $meta New User meta.
		 */
		public function wpmu_activate_user( $user_id = 0, $password = '', $meta = array() ) {
			if ( ( is_multisite() ) && ( ! empty( $user_id ) ) ) {
				if ( ( isset( $meta['learndash_meta'] ) ) && ( is_array( $meta['learndash_meta'] ) ) && ( ! empty( $meta['learndash_meta'] ) ) ) {
					$this->activated_user_id = absint( $user_id );
					foreach ( $meta['learndash_meta'] as $key => $val ) {
						update_user_meta( $user_id, $key, $val );
					}

					if ( ( isset( $meta['learndash_meta']['blog_id'] ) ) && ( ! empty( $meta['learndash_meta']['blog_id'] ) ) ) {
						$default_role = get_site_option( absint( $meta['learndash_meta']['blog_id'] ), 'default_role' );
						if ( ! empty( $default_role ) ) {
							if ( apply_filters( 'learndash_multisite_user_active_add_to_blog', true, $user_id, absint( $meta['learndash_meta']['blog_id'] ), $default_role ) ) {
								add_user_to_blog( absint( $meta['learndash_meta']['blog_id'] ), $user_id, $default_role );
							}
						}
					}
				}
			}
		}

		/**
		 * Filter the network site url.
		 *
		 * @since 1.0.0
		 * @param string $url    URL to be used.
		 * @param string $path   Path for URL.
		 * @param string $scheme Scheme for URL.
		 */
		public function network_site_url( $url = '', $path = '', $scheme = '' ) {
			if ( ( 'login' === $scheme ) && ( 'wp-login.php' === $path ) ) {
				if ( ( true === $this->doing_activate ) && ( ! empty( $this->activated_user_id ) ) ) {
					$redirect_to = get_user_meta( $this->activated_user_id, 'redirect_to', true );
					if ( ! empty( $redirect_to ) ) {
						$redirect_to = remove_query_arg( 'ld-registered', $redirect_to );
						$url         = esc_url( $redirect_to );
					}
				}
			}

			// Always return $url.
			return $url;
		}

		// End of functions.
	}

	add_action(
		'init',
		function() {
			LD_Multisite::get_instance();
		},
		10,
		1
	);
}
