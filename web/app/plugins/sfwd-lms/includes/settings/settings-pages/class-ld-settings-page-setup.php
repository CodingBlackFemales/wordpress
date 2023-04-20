<?php
/**
 * LearnDash Settings Page Overview.
 *
 * @since 4.4.0
 * @package LearnDash\Settings\Pages
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use LearnDash_Settings_Page_Help as Help_Page;
use LearnDash_Settings_Section_Stripe_Connect as Stripe_Connect;

if ( class_exists( 'LearnDash_Settings_Page' ) && ! class_exists( 'LearnDash_Settings_Page_Setup' ) ) {
	/**
	 * Class LearnDash Settings Page Overview.
	 *
	 * @since 4.4.0
	 */
	class LearnDash_Settings_Page_Setup extends LearnDash_Settings_Page {
		const SETUP_SLUG_CLOUD    = 'learndash-cloud-setup';
		const SETUP_SLUG          = 'learndash-setup';
		const OVERVIEW_PAGE_SLUG  = 'learndash_lms_overview';
		const ACTIVATION_URL_SLUG = 'learndash-activate-stripe-connect';

		/**
		 * Public constructor for class
		 *
		 * @since 4.4.0
		 */
		public function __construct() {
			$this->parent_menu_page_url  = 'admin.php?page=learndash-setup';
			$this->menu_page_capability  = LEARNDASH_ADMIN_CAPABILITY_CHECK;
			$this->settings_page_id      = 'learndash-setup';
			$this->settings_page_title   = esc_html__( 'LearnDash Setup', 'learndash' );
			$this->settings_tab_title    = esc_html__( 'Setup', 'learndash' );
			$this->settings_tab_priority = 0;

			if ( ! learndash_cloud_is_enabled() ) {
				add_filter( 'learndash_submenu', array( $this, 'submenu_item' ), 200 );

				add_filter( 'learndash_admin_tab_sets', array( $this, 'learndash_admin_tab_sets' ), 10, 3 );
				add_filter( 'learndash_header_data', array( $this, 'admin_header' ), 40, 3 );
				add_action( 'admin_head', array( $this, 'output_admin_inline_scripts' ) );

				parent::__construct();
			}

			add_action( 'wp_loaded', array( $this, 'redirect_overview_to_setup' ), 1 );
			add_action( 'wp_loaded', array( $this, 'activate_connect_stripe' ) );
		}

		/**
		 * Control visibility of submenu items based on license status
		 *
		 * @since 4.4.0
		 *
		 * @param array $submenu Submenu item to check.
		 *
		 * @return array
		 */
		public function submenu_item( array $submenu ) : array {
			if ( ! isset( $submenu[ $this->settings_page_id ] ) ) {
				$submenu = array_merge(
					array(
						$this->settings_page_id => array(
							'name'  => $this->settings_tab_title,
							'cap'   => $this->menu_page_capability,
							'link'  => $this->parent_menu_page_url,
							'class' => 'submenu-ldlms-setup',
						),
					),
					$submenu
				);
			}

			return $submenu;
		}

		/**
		 * Filter the admin header data. We don't want to show the header panel on the Overview page.
		 *
		 * @since 4.4.0
		 *
		 * @param array  $header_data Array of header data used by the Header Panel React app.
		 * @param string $menu_key The menu key being displayed.
		 * @param array  $menu_items Array of menu/tab items.
		 *
		 * @return array
		 */
		public function admin_header( array $header_data = array(), string $menu_key = '', array $menu_items = array() ) : array {
			// Clear out $header_data if we are showing our page.
			return $menu_key === $this->parent_menu_page_url ? array() : $header_data;
		}

		/**
		 * Output inline scripts or styles in HTML head tag.
		 *
		 * @since 4.4.0
		 *
		 * @return void
		 */
		public function output_admin_inline_scripts() : void {
			// Setup page.
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['page'] ) && 'learndash-setup' === $_GET['page'] ) {
				?>
				<style>
					body .notice {
						display: none;
					}
				</style>
				<?php
			}
		}

		/**
		 * Redirect the old LearnDash overview page to setup page.
		 *
		 * @since 4.4.0
		 *
		 * @return void
		 */
		public function redirect_overview_to_setup() : void {
			global $pagenow;

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( $pagenow === 'admin.php' && isset( $_GET['page'] ) && $_GET['page'] === self::OVERVIEW_PAGE_SLUG ) {
				$setup_slug = learndash_cloud_is_enabled() ? self::SETUP_SLUG_CLOUD : self::SETUP_SLUG;

				wp_safe_redirect( admin_url( 'admin.php?page=' . $setup_slug ) );
				exit();
			}
		}

		/**
		 * Activate Stripe connect.
		 *
		 * @since 4.4.0.1
		 *
		 * @return void
		 */
		public function activate_connect_stripe(): void {
			global $pagenow;
			if ( $pagenow !== 'admin.php'
				|| ! isset( $_GET['page'] ) || $_GET['page'] !== self::SETUP_SLUG
				|| ! isset( $_GET['action'] ) || $_GET['action'] !== self::ACTIVATION_URL_SLUG
				|| ! isset( $_GET['nonce'] )
			) {
				return;
			}

			if ( ! current_user_can( LEARNDASH_ADMIN_CAPABILITY_CHECK ) ) {
				return;
			}

			// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
			if ( ! wp_verify_nonce( $_GET['nonce'], self::ACTIVATION_URL_SLUG ) ) {
				return;
			}

			LearnDash_Settings_Section::set_section_setting( 'LearnDash_Settings_Section_Stripe_Connect', 'enabled', 'yes' );

			$redirect_url = admin_url( 'admin.php?page=' . self::SETUP_SLUG );

			learndash_safe_redirect( $redirect_url );
		}

		/**
		 * Filter for page title wrapper.
		 *
		 * @since 4.4.0
		 *
		 * @return string
		 */
		public function get_admin_page_title() : string {

			/** This filter is documented in includes/settings/class-ld-settings-pages.php */
			return apply_filters( 'learndash_admin_page_title', '<h1>' . $this->settings_page_title . '</h1>' );
		}

		/**
		 * Action function called when Add-ons page is loaded.
		 *
		 * @since 4.4.0
		 *
		 * @return void
		 */
		public function load_settings_page() : void {

			global $learndash_assets_loaded;

			$object = array(
				'ajaxurl'                          => admin_url( 'admin-ajax.php' ),
				'plugin_url'                       => LEARNDASH_LMS_PLUGIN_URL,
				'admin_dashboard_url'              => admin_url( '/' ),
				'learndash_cloud_setup_url'        => add_query_arg(
					array( 'page' => 'learndash-setup' ),
					admin_url( 'admin.php' )
				),
				'learndash_cloud_setup_wizard_url' => add_query_arg(
					array( 'page' => 'learndash-setup-wizard' ),
					admin_url( 'admin.php' )
				),
				'learndash_setup_wizard_url'       => add_query_arg(
					array( 'page' => 'learndash-setup-wizard' ),
					admin_url( 'admin.php' )
				),
			);

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['page'] ) && 'learndash-setup' === $_GET['page'] ) {
				Help_Page::enqueue_support_assets();

				wp_enqueue_style(
					'learndash-setup',
					LEARNDASH_LMS_PLUGIN_URL . '/assets/css/setup.css',
					array(),
					LEARNDASH_VERSION,
					'all'
				);
				$learndash_assets_loaded['styles']['learndash-admin-setup-page-style'] = __FUNCTION__;

				wp_enqueue_script(
					'learndash-setup',
					LEARNDASH_LMS_PLUGIN_URL . '/assets/js/setup.js',
					array( 'jquery', 'wp-element' ),
					LEARNDASH_VERSION,
					true
				);
				$learndash_assets_loaded['scripts']['learndash-admin-setup-page-script'] = __FUNCTION__;

				wp_localize_script(
					'learndash-setup',
					'LearnDashSetup',
					$object
				);
			}
		}

		/**
		 * Hide the tab menu items if on add-on page.
		 *
		 * @since 4.4.0
		 *
		 * @param array  $tab_set Tab Set.
		 * @param string $tab_key Tab Key.
		 * @param string $current_page_id ID of shown page.
		 *
		 * @return array
		 */
		public function learndash_admin_tab_sets( array $tab_set = array(), string $tab_key = '', string $current_page_id = '' ) : array {
			if ( ( ! empty( $tab_set ) ) && ( ! empty( $tab_key ) ) && ( ! empty( $current_page_id ) ) ) {
				if ( 'admin_page_learndash-setup' === $current_page_id ) {
					?>
					<style> h1.nav-tab-wrapper { display: none; }</style>
					<?php
				}
			}
			return $tab_set;
		}

		/**
		 * Output the page HTML.
		 *
		 * @since 4.4.0
		 *
		 * @return void
		 */
		public function show_settings_page() : void {
			$stripe_connect_connected        = Stripe_Connect::is_stripe_connected();
			$stripe_connect_activated        = LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_Stripe_Connect', 'enabled' ) === 'yes';
			$stripe_connect_needs_activation = $stripe_connect_connected && ! $stripe_connect_activated;
			$stripe_connect_completed        = $stripe_connect_connected && $stripe_connect_activated;
			$stripe_activation_url           = add_query_arg(
				array(
					'page'   => self::SETUP_SLUG,
					'action' => self::ACTIVATION_URL_SLUG,
					'nonce'  => wp_create_nonce( self::ACTIVATION_URL_SLUG ),
				),
				admin_url( 'admin.php' )
			);

			/**
			 * Filters steps shown on setup page.
			 *
			 * @since 4.4.0
			 *
			 * @param array $steps List of steps with arguments.
			 */
			$steps = apply_filters(
				'learndash_setup_steps',
				array(
					'site_setup'    => array(
						'class'              => 'setup',
						'completed'          => 'completed' === get_option( 'learndash_setup_wizard_status' ),
						'time_in_minutes'    => 5,
						'url'                => admin_url( 'admin.php?page=learndash-setup-wizard' ),
						'title'              => __( 'Set up your site', 'learndash' ),
						'description'        => __( 'This is where the fun begins.', 'learndash' ),
						'action_label'       => __( 'Site & Course Details', 'learndash' ),
						'action_description' => __( 'Tell us a little bit about your site.', 'learndash' ),
						'icon_url'           => LEARNDASH_LMS_PLUGIN_URL . '/assets/images/setup.png',
						'button_type'        => 'arrow',
						'button_class'       => '',
						'button_text'        => '',
					),
					'design_setup'  => array(
						'class'              => 'design',
						'completed'          => 'completed' === get_option( 'learndash_design_wizard_status' ),
						'time_in_minutes'    => 5,
						'url'                => admin_url( 'admin.php?page=learndash-design-wizard' ),
						'title'              => __( 'Design your site', 'learndash' ),
						'description'        => __( 'It\'s all about appearances.', 'learndash' ),
						'action_label'       => __( 'Select A Starter Template', 'learndash' ),
						'action_description' => __( 'Choose a design to start with and customize. This will overwrite your current theme, may add additional content and change settings on your site.', 'learndash' ),
						'icon_url'           => LEARNDASH_LMS_PLUGIN_URL . '/assets/images/design.png',
						'button_type'        => 'arrow',
						'button_class'       => '',
						'button_text'        => '',
					),
					'payment_setup' => array(
						'class'              => 'payment',
						'completed'          => $stripe_connect_completed,
						'time_in_minutes'    => $stripe_connect_needs_activation ? null : 5,
						'url'                => $stripe_connect_needs_activation ? $stripe_activation_url : Stripe_Connect::generate_connect_url(),
						'title'              => __( 'Configure payment', 'learndash' ),
						'description'        => __( 'Don\'t leave money on the table.', 'learndash' ),
						'action_label'       => __( 'Set Up Stripe', 'learndash' ),
						'action_description' => __( 'Charge credit cards and pay low merchant fees.', 'learndash' ),
						'icon_url'           => LEARNDASH_LMS_PLUGIN_URL . '/assets/images/payment.png',
						'button_type'        => 'button',
						'button_class'       => 'button-stripe',
						'button_text'        => $stripe_connect_needs_activation ? __( 'Activate', 'learndash' ) : __( 'Connect Stripe', 'learndash' ),
					),
					'documentation' => array(
						'class'              => 'courses',
						'completed'          => null,
						'time_in_minutes'    => null,
						'url'                => null,
						'title'              => __( 'Manage your courses', 'learndash' ),
						'description'        => __( 'Get your coursework set up for success.', 'learndash' ),
						'action_label'       => null,
						'action_description' => null,
						'icon_url'           => null,
						'button_type'        => null,
						'button_class'       => null,
						'button_text'        => null,
						'content_path'       => 'setup/components/content-documentation',
					),
				)
			);

			SFWD_LMS::get_view(
				'setup/setup',
				array(
					'steps'            => $steps,
					'setup_wizard'     => $this,
					'overview_video'   => Help_Page::get_articles( 'overview_video' )[0],
					'overview_article' => Help_Page::get_articles( 'overview_article' )[0],
				),
				true
			);
		}
	}
}

add_action(
	'learndash_settings_pages_init',
	function() {
		LearnDash_Settings_Page_Setup::add_page_instance();
	}
);
