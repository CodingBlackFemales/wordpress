<?php
/**
 * LearnDash class for displaying the setup wizard.
 *
 * @package LearnDash
 * @since 4.0.0
 */

use LearnDash\Core\Modules\Payments\Gateways\Stripe\Connection_Handler;
use LearnDash\Core\Modules\Payments\Gateways\Paypal\Payment_Gateway as Paypal_Payment_Gateway;
use LearnDash\Core\Utilities\Cast;
use LearnDash\Core\Utilities\Location;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LearnDash_Setup_Wizard' ) ) {
	/**
	 * Setup wizard class.
	 */
	class LearnDash_Setup_Wizard {
		/**
		 * The opened status, can be completed, ongoing or closed.
		 */
		const STATUS_KEY = 'learndash_setup_wizard_status';

		const STATUS_COMPLETED = 'completed';
		const STATUS_ONGOING   = 'ongoing';
		const STATUS_CLOSED    = 'closed';

		const DATA_KEY = 'learndash_setup_wizard';

		const CERTIFICATE_BUILDER_SLUG   = 'learndash-certificate-builder/learndash-certificate-builder.php';
		const WOOCOMMERCE_SLUG           = 'woocommerce/woocommerce.php';
		const LEARNDASH_WOOCOMMERCE_SLUG = 'learndash-woocommerce/learndash_woocommerce.php';

		const HANDLE = 'learndash-setup-wizard';

		const LICENSE_KEY       = 'nss_plugin_license_sfwd_lms';
		const LICENSE_EMAIL_KEY = 'nss_plugin_license_email_sfwd_lms';

		const ADMIN_REDIRECT_PAGE       = 'admin.php?page=learndash-setup';
		const FINAL_ADMIN_REDIRECT_PAGE = 'admin.php?page=learndash-setup';

		/**
		 * The option key for the StellarSites integration.
		 *
		 * @since 4.21.5
		 *
		 * @var string
		 */
		private const LEARNDASH_SETUP_WIZARD_STELLARSITES = 'learndash_setup_wizard_stellarsites_triggered';

		/**
		 * The single instance of the class.
		 */
		public function __construct() {
			if ( ! is_admin() ) {
				return;
			}

			add_action( 'learndash_activated', array( $this, 'set_redirect_flag' ) );
			add_action( 'admin_init', array( $this, 'redirect_after_activation' ), 1 );
			add_action( 'admin_menu', array( $this, 'register_menu' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_scripts' ) );
			add_action( 'admin_init', array( $this, 'dismiss' ) );
			add_action( 'wp_ajax_learndash_setup_wizard_verify_license', array( $this, 'verify_license' ) );
			add_action( 'wp_ajax_learndash_setup_wizard_save_data', array( $this, 'save_data' ) );
			add_action( 'wp_ajax_learndash_finalize', array( $this, 'finalize_setup' ) );
			add_action( 'admin_post_stripe_connect_wizard_process', array( $this, 'enable_stripe_connect_and_redirect' ) );
			add_action( 'current_screen', [ $this, 'redirect_after_stellar_sites_plugin_activation' ] );
		}

		/**
		 * Check if this is a LearnDash submenu item click and handle wizard redirect if StellarSites MU plugin is active.
		 *
		 * @since 4.21.5
		 *
		 * @return void
		 */
		public function redirect_after_stellar_sites_plugin_activation( WP_Screen $screen ): void {
			if ( ! Location::is_learndash_admin_page() ) {
				return;
			}

			// Check if the StellarSites MU plugin is active.
			if ( ! class_exists( '\StellarWP\StellarSites\Plugin' ) ) {
				return;
			}

			// Check if the wizard has already been triggered.
			$wizard_triggered = get_option( self::LEARNDASH_SETUP_WIZARD_STELLARSITES, false );

			if ( $wizard_triggered ) {
				return;
			}

			// Get wizard status.
			$wizard_status = get_option( self::STATUS_KEY );

			// Only redirect if the wizard hasn't been completed or dismissed.
			if (
				$wizard_status !== self::STATUS_COMPLETED
				&& $wizard_status !== self::STATUS_CLOSED
			) {
				// Mark the wizard as triggered to prevent future redirects.
				update_option( self::LEARNDASH_SETUP_WIZARD_STELLARSITES, true );

				// Redirect to wizard.
				learndash_safe_redirect( admin_url( 'admin.php?page=' . self::HANDLE ) );
			}
		}

		/**
		 * Retrieves the wizard status.
		 *
		 * @since 4.21.5
		 *
		 * @return string The value of the wizard status from the options.
		 */
		public static function get_status(): string {
			/**
			 * Filters the status of the setup wizard.
			 *
			 * @since 4.21.5
			 *
			 * @param string $status The value of the wizard status from the options.
			 */
			return apply_filters(
				'learndash_setup_wizard_status',
				Cast::to_string(
					get_option( self::STATUS_KEY )
				)
			);
		}

		/**
		 * Enables the Stripe connect and then redirect to the wizard again.
		 *
		 * @since 4.0.0
		 *
		 * @return void
		 */
		public function enable_stripe_connect_and_redirect(): void {
			// Enable stripe connect.
			if ( LearnDash_Settings_Section_Stripe_Connect::is_stripe_connected() ) {
				LearnDash_Settings_Section::set_section_setting( 'LearnDash_Settings_Section_Stripe_Connect', 'enabled', 'yes' );
			}

			// Force update wizard data.
			$this->update_data( 'charge', 'yes' );
			$this->update_data( 'charge_method', 'stripe' );

			// Create a transient to flag that we need to create webhooks.
			set_transient( 'learndash_stripe_connect_create_webhooks', true, HOUR_IN_SECONDS );

			learndash_safe_redirect( admin_url( 'admin.php?page=' . self::HANDLE ) );
		}

		/**
		 * Check when we need to show the wizard and set an option for that.
		 *
		 * @since 4.5.0
		 *
		 * @return void
		 */
		public function set_redirect_flag() {
			if ( ! $this->should_display() ) {
				return;
			}

			update_option( 'learndash_setup_wizard_redirect', true );
		}

		/**
		 * Redirect to the setup wizard after an activation.
		 */
		public function redirect_after_activation() {
			$should_redirect = get_option( 'learndash_setup_wizard_redirect' );
			if ( ! $should_redirect ) {
				return;
			}

			delete_option( 'learndash_setup_wizard_redirect' );
			learndash_safe_redirect( admin_url( 'admin.php?page=' . self::HANDLE ) );
		}

		/**
		 * Dismiss
		 */
		public function dismiss() {
			if ( ! isset( $_GET['page'] ) || ! isset( $_GET['dismiss'] ) || ! isset( $_GET['nonce'] ) ) {
				return;
			}

			if ( self::HANDLE !== sanitize_text_field( wp_unslash( $_GET['page'] ) ) ) {
				return;
			}

			$nonce = sanitize_text_field( wp_unslash( $_GET['nonce'] ) );

			if ( ! wp_verify_nonce( $nonce, 'ld_setup_wizard_dismiss' ) ) {
				return;
			}

			update_option( self::STATUS_KEY, self::STATUS_CLOSED );

			learndash_safe_redirect( admin_url( self::ADMIN_REDIRECT_PAGE ) );
		}

		/**
		 * Returns the redirect URL after the wizard is completed.
		 *
		 * @since 4.2.0
		 *
		 * @return string The redirect URL.
		 */
		private function get_completed_redirect_url(): string {
			/**
				 * Filter the URL to redirect to after the setup wizard is completed.
				 *
				 * @since 4.1.2
				 *
				 * @param string $url The URL to redirect to.
				 */
				return apply_filters( 'learndash_setup_wizard_completed_redirect_url', admin_url( self::FINAL_ADMIN_REDIRECT_PAGE ) );
		}

		/**
		 * Ajax endpoint for finalize the setup.
		 */
		public function finalize_setup() {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';
			if ( empty( $nonce ) ) {
				wp_send_json_error();
			}

			if ( ! wp_verify_nonce( $nonce, 'ld_setup_wizard_finalize' ) ) {
				wp_send_json_error();
			}

			/**
			 * React data.
			 *
			 * @var array $data
			 */
			$data = isset( $_POST['data'] ) ? wp_unslash( $_POST['data'] ) : array(); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			$done = false;

			switch ( $data['step'] ) {
				case 'save_default_currency':
					LearnDash_Settings_Section::set_section_settings_all(
						'LearnDash_Settings_Section_Payments_Defaults',
						array(
							'country'  => $data['currency_country'],
							'currency' => $data['currency'],
						)
					);
					break;
				case 'create_registration_pages':
					// enable anyone can register option and create registration pages.
					update_option( 'users_can_register', true );
					$this->create_registration_pages();
					$this->create_profile_page();
					break;
				case 'process_course_listing':
					// Import demo course.
					// phpcs:ignore Generic.CodeAnalysis.EmptyStatement -- TODO: Remove this comment later.
					if ( 'yes' === $data['course_demo'] ) {
						Learndash_Admin_Import_Export::import_demo_content();
					}

					// Create course listing page.
					if ( 'multiple' === $data['courses_amount'] ) {
						$this->create_courses_listing_page();
					}
					break;
				case 'process_certificate_builder':
					// install certificate builder plugin.
					if ( 'true' === $data['certificate_builder'] ) {
						$ret = $this->maybe_install_a_plugin( self::CERTIFICATE_BUILDER_SLUG );
						if ( true === $ret ) {
							activate_plugin( self::CERTIFICATE_BUILDER_SLUG );
						}
					}
					break;
				case 'process_woo':
					// install woocommerce plugin and learndash add-on.
					if ( 'true' === $data['woocommerce'] ) {
						$ret = $this->maybe_install_a_plugin( self::WOOCOMMERCE_SLUG );
						if ( true === $ret ) {
							activate_plugin( self::WOOCOMMERCE_SLUG );
						}
						$ret = $this->maybe_install_a_plugin( self::LEARNDASH_WOOCOMMERCE_SLUG );
						if ( true === $ret ) {
							activate_plugin( self::LEARNDASH_WOOCOMMERCE_SLUG );
						}
					}
					break;
				case 'update_settings':
					// user from email address.
					$from_email = 'yes' === $data['use_registered_email'] ? $data['email'] : filter_var( $data['notification_email'], FILTER_VALIDATE_EMAIL );
					LearnDash_Settings_Section::set_section_setting( 'LearnDash_Settings_Section_Emails_Sender_Settings', 'from_email', $from_email );

					// group access and management.
					if ( is_array( $data['course_type'] ) && in_array( 'group_courses', $data['course_type'], true ) ) {
						// optional group settings.
						LearnDash_Settings_Section::set_section_setting( 'LearnDash_Settings_Groups_CPT', 'public', isset( $data['public_group'] ) && $data['public_group'] ? 'yes' : '' );
						LearnDash_Settings_Section::set_section_setting( 'LearnDash_Settings_Groups_CPT', 'has_archive', isset( $data['group_archive_page'] ) && $data['group_archive_page'] ? 'yes' : '' );
						LearnDash_Settings_Section::set_section_setting( 'LearnDash_Settings_Section_Groups_Group_Leader_User', 'manage_courses_enabled', isset( $data['manage_user'] ) && $data['manage_user'] ? 'yes' : '' );
						LearnDash_Settings_Section::set_section_setting( 'LearnDash_Settings_Section_Groups_Group_Leader_User', 'groups_autoenroll_managed', isset( $data['group_auto_enroll'] ) && $data['group_auto_enroll'] ? 'yes' : '' );
						LearnDash_Settings_Section::set_section_setting( 'LearnDash_Settings_Section_Groups_Group_Leader_User', 'courses_autoenroll', isset( $data['course_auto_enroll'] ) && $data['course_auto_enroll'] ? 'yes' : '' );
						LearnDash_Settings_Section::set_section_setting( 'LearnDash_Settings_Section_Groups_Group_Leader_User', 'bypass_course_limits', isset( $data['bypass_course_limit'] ) && $data['bypass_course_limit'] ? 'yes' : '' );
					}

					update_option( self::STATUS_KEY, self::STATUS_COMPLETED );
					$done = true;
					break;
			}

			// preparing return data.
			$result_data = array(
				'completed' => $done,
			);

			if ( $done ) {
				$result_data['redirect'] = $this->get_completed_redirect_url();

				/**
				 * Action to be run after the setup wizard is completed.
				 *
				 * @since 4.1.2
				 */
				do_action( 'learndash_setup_wizard_completed' );
			}

			wp_send_json_success( $result_data );
		}

		/**
		 * Install a plugin
		 *
		 * @param string $slug The plugin slug.
		 *
		 * @return bool
		 */
		protected function maybe_install_a_plugin( string $slug ) {
			$plugins = get_plugins();

			if ( isset( $plugins[ $slug ] ) && is_plugin_inactive( $slug ) ) {
				return true; // plugin is installed but not activated.
			}

			if ( ! function_exists( 'plugins_api' ) ) {
				require_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			}

			$slug = dirname( $slug );
			$api  = plugins_api(
				'plugin_information',
				array(
					'slug' => $slug,
				)
			);

			if ( is_wp_error( $api ) ) {
				WP_DEBUG && error_log( $api->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				return false;
			}

			$status = install_plugin_install_status( $api );

			if ( 'install' === $status['status'] ) {
				return $this->install( $slug );
			}

			return false;
		}

		/**
		 * Install a plugin
		 *
		 * @param string $slug Plugin slug.
		 *
		 * @return bool
		 */
		public function install( string $slug ) {
			// prepare for install.
			include_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			include_once ABSPATH . 'wp-admin/includes/plugin-install.php';
			include_once ABSPATH . 'wp-admin/includes/plugin.php';
			include_once ABSPATH . 'wp-admin/includes/file.php';

			$skin = new WP_Ajax_Upgrader_Skin();

			/**
			 * Response object.
			 *
			 * @var object api
			 */
			$api = plugins_api(
				'plugin_information',
				array(
					'slug'   => sanitize_key( $slug ),
					'fields' => array( 'sections' => false ),
				)
			);

			if ( is_wp_error( $api ) ) {
				WP_DEBUG && error_log( $api->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				return false;
			}

			$upgrade_er = new Plugin_Upgrader( $skin );
			$result     = $upgrade_er->install( isset( $api->download_link ) ? $api->download_link : $api->download_url );

			if ( is_wp_error( $result ) ) {
				WP_DEBUG && error_log( $result->get_error_message() ); // phpcs:ignore WordPress.PHP.DevelopmentFunctions.error_log_error_log
				return false;
			}

			return $result;
		}

		/**
		 * Create courses listing page
		 *
		 * @return void
		 */
		protected function create_courses_listing_page() {
			wp_insert_post(
				array(
					'post_title'   => __( 'Courses', 'learndash' ),
					'post_content' => '<!-- wp:learndash/ld-course-list /-->',
					'post_status'  => 'publish',
					'post_type'    => 'page',
				)
			);
		}

		/**
		 * Create profile page during Setup Wizard completion.
		 *
		 * @since 4.4.1
		 *
		 * @return void
		 */
		protected function create_profile_page(): void {
			wp_insert_post(
				array(
					'post_title'   => __( 'Profile', 'learndash' ),
					'post_content' => '<!-- wp:learndash/ld-profile /-->',
					'post_status'  => 'publish',
					'post_type'    => 'page',
				)
			);
		}

		/**
		 * Create registration, registration success, reset password and profile pages.
		 */
		protected function create_registration_pages() {
			// Data for each of the pages being created.
			$page_data = array(
				array(
					'identifier' => 'registration',
					'title'      => 'Registration',
					'content'    => '<!-- wp:learndash/ld-registration /-->',
				),
				array(
					'identifier' => 'registration_success',
					'title'      => 'Registration Success',
					'content'    => '<!-- wp:paragraph --><p>' . __( 'Welcome', 'learndash' ) . '</p><!-- /wp:paragraph -->',
				),
				array(
					'identifier' => 'reset_password',
					'title'      => 'Reset Password',
					'content'    => '<!-- wp:learndash/ld-reset-password {"width":""} /-->',
				),
			);

			foreach ( $page_data as $page_values ) {
				$page_id = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Registration_Pages', $page_values['identifier'] );

				if ( ! empty( $page_id ) ) {
					continue;
				}

				LearnDash_Settings_Section::set_section_setting(
					'LearnDash_Settings_Section_Registration_Pages',
					$page_values['identifier'],
					wp_insert_post(
						array(
							'post_title'   => $page_values['title'],
							'post_content' => $page_values['content'],
							'post_status'  => 'publish',
							'post_type'    => 'page',
						)
					)
				);
			}
		}

		/**
		 * An ajax endpoint for saving wizard data. When the
		 * user move to next step, we will store the current state in the db.
		 */
		public function save_data() {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

			if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'ld_setup_wizard_save_data' ) ) {
				wp_send_json_error();
			}

			$data = isset( $_POST['data'] )
				? $this->sanitize_text_fields( (array) wp_unslash( $_POST['data'] ) ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				: array();
			foreach ( $data as $key => $val ) {
				$this->update_data( $key, $val );
			}

			update_option( self::STATUS_KEY, self::STATUS_ONGOING );
		}

		/**
		 * Ajax endpoint, for trigger a request to validate the license key.
		 */
		public function verify_license() {
			$nonce = isset( $_POST['nonce'] ) ? sanitize_text_field( wp_unslash( $_POST['nonce'] ) ) : '';

			if ( empty( $nonce ) || ! wp_verify_nonce( $nonce, 'ld_setup_wizard_verify_license' ) ) {
				wp_send_json_error();
			}

			$email       = isset( $_POST['email'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['email'] ) ) ) : '';
			$license_key = isset( $_POST['license_key'] ) ? trim( sanitize_text_field( wp_unslash( $_POST['license_key'] ) ) ) : '';

			if ( ! filter_var( $email, FILTER_VALIDATE_EMAIL ) || empty( $license_key ) ) {
				wp_send_json_error();
			}

			update_option( self::LICENSE_KEY, $license_key );
			update_option( self::LICENSE_EMAIL_KEY, $email );

			// Licensing validation.

			$license_status = learndash_validate_hub_license( $email, $license_key, true );

			if ( ! $license_status ) {
				wp_send_json_error();
			}

			// Store the data.
			update_option( self::STATUS_KEY, self::STATUS_ONGOING );

			wp_send_json_success();
		}

		/**
		 *
		 * Update the wizard data.
		 *
		 * @param string $key Data key.
		 * @param mixed  $value Data value.
		 * @param string $option_name Option name. Default: 'learndash_setup_wizard'.
		 */
		private function update_data( string $key, $value, string $option_name = self::DATA_KEY ): void {
			$data = get_option( $option_name, array() );

			if ( ! is_array( $data ) ) {
				$data = array();
			}

			$data[ $key ] = $value;

			update_option( $option_name, $data );
		}

		/**
		 * Get the list of scenes.
		 *
		 * @return array The list of scenes. [scene_key => scene_data].
		 */
		private function get_scenes(): array {
			$available_scenes = array(
				'step-0' => esc_html__( 'Welcome', 'learndash' ),
				'step-1' => esc_html__( 'Your Info', 'learndash' ),
				'step-2' => esc_html__( 'Your Courses', 'learndash' ),
				'step-3' => esc_html__( 'Payment', 'learndash' ),
				'step-4' => esc_html__( 'Summary', 'learndash' ),
			);

			/**
			 * Filters the available scenes for the setup wizard.
			 *
			 * @since 4.2.0
			 *
			 * @param array $scenes The list of scenes. [scene_key => scene_description].
			 */
			$available_scenes = apply_filters( 'learndash_setup_wizard_available_scenes', $available_scenes );

			// make sure that we have at least one scene.
			if ( empty( $available_scenes ) ) {
				$available_scenes = array( 'step-0' => esc_html__( 'Welcome', 'learndash' ) );
			}

			$scenes = array();
			$keys   = array_keys( $available_scenes );
			$pos    = 0;
			foreach ( $available_scenes as $scene_key => $scene_description ) {
				$scenes[ $scene_key ] = array(
					'description' => $scene_description,
					'next'        => isset( $keys[ $pos + 1 ] ) ? $keys[ $pos + 1 ] : '',
					'prev'        => isset( $keys[ $pos - 1 ] ) ? $keys[ $pos - 1 ] : '',
				);
				++$pos;
			}

			return $scenes;
		}

		/**
		 * Register the script
		 */
		public function enqueue_scripts() {
			$screen = get_current_screen();
			if ( is_object( $screen ) && 'toplevel_page_learndash-setup-wizard' === $screen->id ) {
				wp_register_style(
					self::HANDLE,
					LEARNDASH_LMS_PLUGIN_URL . 'assets/js/setup-wizard/dist/css/style.css',
					array(),
					constant( 'SCRIPT_DEBUG' ) === true ? time() : LEARNDASH_VERSION
				);
				wp_enqueue_style( self::HANDLE );

				wp_register_script(
					self::HANDLE,
					LEARNDASH_LMS_PLUGIN_URL . 'assets/js/setup-wizard/dist/js/index.js',
					array( 'react', 'react-dom', 'wp-i18n' ),
					constant( 'SCRIPT_DEBUG' ) === true ? time() : LEARNDASH_VERSION,
					true
				);

				$data = get_option( self::DATA_KEY );
				if ( ! is_array( $data ) ) {
					$data = [];
				}

				$currency_code    = learndash_get_currency_code();
				$currency_country = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Payments_Defaults', 'country' ) ?? '';

				// scenes processing.
				$scenes        = $this->get_scenes();
				$current_scene = isset( $data['scene'] ) && isset( $scenes[ $data['scene'] ] ) ? $data['scene'] : array_keys( $scenes )[0];

				// Define if we need to create webhooks.
				$stripe_create_webhooks = get_transient( 'learndash_stripe_connect_create_webhooks' );
				delete_transient( 'learndash_stripe_connect_create_webhooks' );

				wp_localize_script(
					self::HANDLE,
					'ldSetupWizard',
					array(
						'urls'           => array(
							'assets'         => LEARNDASH_LMS_PLUGIN_URL . 'assets/js/setup-wizard/dist/',
							'dismiss'        => admin_url(
								wp_sprintf(
									'admin.php?page=%s&dismiss=true&nonce=%s',
									self::HANDLE,
									wp_create_nonce( 'ld_setup_wizard_dismiss' )
								)
							),
							'support'        => 'https://support.learndash.com',
							'stripe_connect' => LearnDash_Settings_Section_Stripe_Connect::generate_connect_url( admin_url( 'admin-post.php?action=stripe_connect_wizard_process' ) ),
							'iso_4217'       => 'https://en.wikipedia.org/wiki/ISO_4217#Active_codes',
							'no_step_url'    => $this->get_completed_redirect_url(),
							'paypal_connect' => esc_url_raw(
								add_query_arg(
									[
										'page'            => 'learndash_lms_payments',
										'section-payment' => 'settings_paypal_checkout',
										'setup-wizard'    => '1',
									],
									admin_url( 'admin.php' )
								)
							),
						),
						'nonces'         => array(
							'verify'                   => wp_create_nonce( 'ld_setup_wizard_verify_license' ),
							'save'                     => wp_create_nonce( 'ld_setup_wizard_save_data' ),
							'finalize'                 => wp_create_nonce( 'ld_setup_wizard_finalize' ),
							'stripe_ajax_post_connect' => wp_create_nonce( Connection_Handler::$ajax_action_post_connect ),
						),
						'data'           => [
							'scenes'                   => $scenes,
							'scene'                    => $current_scene,
							'email'                    => $data['email'] ?? get_option( self::LICENSE_EMAIL_KEY, '' ),
							'license_key'              => $data['license_key'] ?? get_option( self::LICENSE_KEY, '' ),
							'use_registered_email'     => $data['use_registered_email'] ?? 'yes',
							'notification_email'       => $data['notification_email'] ?? '',
							'license_validated'        => $data['license_validated'] ?? 'no',
							'course_demo'              => $data['course_demo'] ?? 'no',
							'courses_amount'           => $data['courses_amount'] ?? 'single',
							'course_type'              => $data['course_type'] ?? array(),
							'group_access'             => $data['group_access'] ?? 'no',
							'group_leader'             => $data['group_leader'] ?? 'no',
							'charge'                   => $data['charge'] ?? 'no',
							'charge_method'            => $data['charge_method'] ?? '',
							'currency'                 => ! empty( $currency_code ) ? $currency_code : '',
							'currency_country'         => ! empty( $currency_country ) ? $currency_country : '',
							'currency_select2_default' => ! empty( $currency_country ) ? ucwords( mb_strtolower( $currency_country ) ) . ' (' . learndash_get_currency_symbol( $currency_code ) . ') ' : '',
							'stripe_connected'         => LearnDash_Settings_Section_Stripe_Connect::is_stripe_connected(),
							'stripe_webhook_notice'    => wp_kses_post( LearnDash_Settings_Section_Stripe_Connect::get_stripe_webhook_notice() ),
							'stripe_create_webhooks'   => $stripe_create_webhooks ? 'yes' : 'no',
							'paypal_connected'         => Paypal_Payment_Gateway::account_is_connected(),
						],
						'plugins'        => array(
							'certificate_builder' => is_plugin_active( self::CERTIFICATE_BUILDER_SLUG ),
							'woocommerce'         => is_plugin_active( self::WOOCOMMERCE_SLUG ),
						),
						'currency_codes' => array(
							'list' => learndash_currency_codes_list(),
						),
					)
				);

				wp_enqueue_script( self::HANDLE );
			}
		}

		/**
		 * Output the html root for react app.
		 */
		public function render() {
			?>
			<div id="learndash-setup-wizard"></div>
			<?php
		}

		/**
		 * Register the admin page to the tree.
		 */
		public function register_menu() {
			add_menu_page(
				__( 'Setup Wizard', 'learndash' ),
				__( 'Setup Wizard', 'learndash' ),
				LEARNDASH_ADMIN_CAPABILITY_CHECK,
				self::HANDLE,
				array( $this, 'render' )
			);

			// Hide the admin menu item, the page stays available.
			remove_menu_page( self::HANDLE );

			if (
				isset( $_GET['page'] )
				&& self::HANDLE === sanitize_text_field( wp_unslash( $_GET['page'] ) )
			) {
				// remove_menu_page() call affects the global title and makes it null in the end,
				// which causes a deprecation error in WP core cause it requires it to be a string.
				global $title;
				$title = ''; // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- It's intentional.
			}
		}

		/**
		 * If there is no license key stored in the database, then we should show up the wizard
		 *
		 * @return bool
		 */
		protected function should_display(): bool {
			$should_display = false;

			$wizard_status = self::get_status();
			// The wizard is in progress, but closed by an accident or something like that.
			if ( self::STATUS_ONGOING === $wizard_status ) {
				$should_display = true;
			}

			// No license key/email.
			if (
				empty( get_option( self::LICENSE_KEY ) )
				|| empty( get_option( self::LICENSE_EMAIL_KEY ) )
			) {
				$should_display = true;
			}

			// Add StellarSites MU plugin integration check.
			if (
				class_exists( '\StellarWP\StellarSites\Plugin' )
				&& ! get_option( self::LEARNDASH_SETUP_WIZARD_STELLARSITES, false )
				&& $wizard_status !== self::STATUS_COMPLETED
				&& $wizard_status !== self::STATUS_CLOSED
			) {
				$should_display = true;
			}

			/**
			 * Filters whether the setup wizard should be displayed or not.
			 *
			 * @since 4.2.0
			 *
			 * @param bool $should_display Whether the setup wizard should be displayed or not.
			 */
			return apply_filters( 'learndash_setup_wizard_should_display', $should_display );
		}

		/**
		 * Sanitize an array recursively.
		 *
		 * @param array $array Array.
		 *
		 * @return array
		 */
		private function sanitize_text_fields( array $array ): array {
			foreach ( $array as &$value ) {
				$value = is_array( $value )
				? $this->sanitize_text_fields( $value )
				: sanitize_text_field( $value );
			}

			return $array;
		}
	}
}
