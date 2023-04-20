<?php
/**
 * LearnDash class for displaying the design wizard.
 *
 * @package    LearnDash
 * @since 4.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LearnDash_Design_Wizard' ) ) {
	/**
	 * Design wizard class.
	 */
	class LearnDash_Design_Wizard {
		/**
		 * The opened status, can be completed, ongoing or closed.
		 */
		const STATUS_KEY = 'learndash_design_wizard_status';

		const STATUS_COMPLETED = 'completed';
		const STATUS_ONGOING   = 'ongoing';
		const STATUS_CLOSED    = 'closed';

		const DATA_KEY = 'learndash_design_wizard';

		const HANDLE = 'learndash-design-wizard';

		const FINAL_ADMIN_REDIRECT_PAGE = 'admin.php?page=learndash-setup';

		/**
		 * Available templates.
		 *
		 * @since 4.4.0
		 *
		 * @var array
		 */
		protected $templates = array();

		/**
		 * Current template details in the template building process.
		 *
		 * @since 4.4.0
		 *
		 * @var array
		 */
		private $ajax_template = array();

		/**
		 * Class constructor.
		 *
		 * @since 4.4.0
		 */
		public function __construct() {
			if ( ! is_admin() ) {
				return;
			}

			add_action( 'admin_menu', array( $this, 'register_pages' ) );
			add_action( 'admin_enqueue_scripts', array( $this, 'enqueue_admin_scripts' ) );

			$this->register_ajax_handlers();
			$this->register_templates();
			add_action( 'admin_head', array( $this, 'load_fonts' ), 9 );
		}

		/**
		 * Register AJAX handlers.
		 *
		 * @since 4.4.0
		 *
		 * @return void
		 */
		public function register_ajax_handlers() {
			$ajax_actions = array(
				'ld_dw_build_template',
			);

			foreach ( $ajax_actions as $action ) {
				add_action( 'wp_ajax_' . $action, array( $this, 'ajax_' . $action ) );
			}
		}

		/**
		 * Register templates.
		 *
		 * @since 4.4.0
		 *
		 * @return array<string, array<string, array<string, string>|string>>
		 */
		public function register_templates() : array {
			$this->templates = array(
				'kadence_seo_skills'              => array(
					'id'                => 'kadence_seo_skills',
					'label'             => 'SEO Skills',
					'theme'             => 'kadence',
					'theme_label'       => 'Kadence',
					'theme_template_id' => 'g21',
					'plugins'           => array(
						'kadence-starter-templates' => __( 'Starter Templates by Kadence WP', 'learndash' ),
					),
					'preview_url'       => 'https://startertemplatecloud.com/g21/?cache=bust',
				),
				'kadence_digital_course'          => array(
					'id'                => 'kadence_digital_course',
					'label'             => 'Digital Course',
					'theme'             => 'kadence',
					'theme_label'       => 'Kadence',
					'theme_template_id' => 'g22',
					'plugins'           => array(
						'kadence-starter-templates' => __( 'Starter Templates by Kadence WP', 'learndash' ),
					),
					'preview_url'       => 'https://startertemplatecloud.com/g22/?cache=bust',
				),
				'kadence_business_course'         => array(
					'id'                => 'kadence_business_course',
					'label'             => 'Business Course',
					'theme'             => 'kadence',
					'theme_label'       => 'Kadence',
					'theme_template_id' => 'g20',
					'plugins'           => array(
						'kadence-starter-templates' => __( 'Starter Templates by Kadence WP', 'learndash' ),
					),
					'preview_url'       => 'https://startertemplatecloud.com/g20/?cache=bust',
				),
				'kadence_course'                  => array(
					'id'                => 'kadence_course',
					'label'             => 'Course',
					'theme'             => 'kadence',
					'theme_label'       => 'Kadence',
					'theme_template_id' => 'g03',
					'plugins'           => array(
						'kadence-starter-templates' => __( 'Starter Templates by Kadence WP', 'learndash' ),
					),
					'preview_url'       => 'https://startertemplatecloud.com/g03/?cache=bust',
				),
				'kadence_get_income'              => array(
					'id'                => 'kadence_get_income',
					'label'             => 'Get Income',
					'theme'             => 'kadence',
					'theme_label'       => 'Kadence',
					'theme_template_id' => 'member_g01',
					'plugins'           => array(
						'kadence-starter-templates' => __( 'Starter Templates by Kadence WP', 'learndash' ),
					),
					'preview_url'       => 'https://startertemplatecloud.com/member-g01/?cache=bust',
				),
				'kadence_online_course'           => array(
					'id'                => 'kadence_online_course',
					'label'             => 'Online Course',
					'theme'             => 'kadence',
					'theme_label'       => 'Kadence',
					'theme_template_id' => 'g04',
					'plugins'           => array(
						'kadence-starter-templates' => __( 'Starter Templates by Kadence WP', 'learndash' ),
					),
					'preview_url'       => 'https://startertemplatecloud.com/g04/?cache=bust',
				),
				'kadence_painting_course'         => array(
					'id'                => 'kadence_painting_course',
					'label'             => 'Painting Course',
					'theme'             => 'kadence',
					'theme_label'       => 'Kadence',
					'theme_template_id' => 'g35',
					'plugins'           => array(
						'kadence-starter-templates' => __( 'Starter Templates by Kadence WP', 'learndash' ),
					),
					'preview_url'       => 'https://startertemplatecloud.com/g35/?cache=bust',
				),
				'astra_meditation_courses'        => array(
					'id'                => 'astra_meditation_courses',
					'label'             => 'Meditation Courses',
					'theme'             => 'astra',
					'theme_label'       => 'Astra',
					'theme_template_id' => '56593',
					'color_scheme'      => 'light',
					'plugins'           => array(
						'astra-sites' => __( 'Starter Templates by Astra', 'learndash' ),
					),
					'preview_url'       => 'https://websitedemos.net/learn-meditation-08/',
				),
				'astra_learndash_academy'         => array(
					'id'                => 'astra_learndash_academy',
					'label'             => 'LearnDash Academy',
					'theme'             => 'astra',
					'theme_label'       => 'Astra',
					'theme_template_id' => '47984',
					'color_scheme'      => 'light',
					'plugins'           => array(
						'astra-sites' => __( 'Starter Templates by Astra', 'learndash' ),
					),
					'preview_url'       => 'https://websitedemos.net/learndash-academy-08/',
				),
				'astra_online_health_coach'       => array(
					'id'                => 'astra_online_health_coach',
					'label'             => 'Online Health Coach',
					'theme'             => 'astra',
					'theme_label'       => 'Astra',
					'theme_template_id' => '47932',
					'color_scheme'      => 'light',
					'plugins'           => array(
						'astra-sites' => __( 'Starter Templates by Astra', 'learndash' ),
					),
					'preview_url'       => 'https://websitedemos.net/online-health-coach-08/',
				),
				'astra_learn_digital_marketing'   => array(
					'id'                => 'astra_learn_digital_marketing',
					'label'             => 'Learn Digital Marketing',
					'theme'             => 'astra',
					'theme_label'       => 'Astra',
					'theme_template_id' => '56525',
					'color_scheme'      => 'light',
					'plugins'           => array(
						'astra-sites' => __( 'Starter Templates by Astra', 'learndash' ),
					),
					'preview_url'       => 'https://websitedemos.net/learn-digital-marketing-08/',
				),
				'astra_online_course'             => array(
					'id'                => 'astra_online_course',
					'label'             => 'Online Course',
					'theme'             => 'astra',
					'theme_label'       => 'Astra',
					'theme_template_id' => '48026',
					'color_scheme'      => 'light',
					'plugins'           => array(
						'astra-sites' => __( 'Starter Templates by Astra', 'learndash' ),
					),
					'preview_url'       => 'https://websitedemos.net/online-courses-08/',
				),
				'astra_online_programming_course' => array(
					'id'                => 'astra_online_programming_course',
					'label'             => 'Online Programming Course',
					'theme'             => 'astra',
					'theme_label'       => 'Astra',
					'theme_template_id' => '47896',
					'color_scheme'      => 'light',
					'plugins'           => array(
						'astra-sites' => __( 'Starter Templates by Astra', 'learndash' ),
					),
					'preview_url'       => 'https://websitedemos.net/online-coding-course-08/',
				),
				'astra_online_cooking_course'     => array(
					'id'                => 'astra_online_cooking_course',
					'label'             => 'Online Cooking Course',
					'theme'             => 'astra',
					'theme_label'       => 'Astra',
					'theme_template_id' => '48061',
					'color_scheme'      => 'light',
					'plugins'           => array(
						'astra-sites' => __( 'Starter Templates by Astra', 'learndash' ),
					),
					'preview_url'       => 'https://websitedemos.net/online-cooking-course-08/',
				),
				'astra_yoga_instructor'           => array(
					'id'                => 'astra_yoga_instructor',
					'label'             => 'Yoga Instructor',
					'theme'             => 'astra',
					'theme_label'       => 'Astra',
					'theme_template_id' => '48631',
					'color_scheme'      => 'light',
					'plugins'           => array(
						'astra-sites' => __( 'Starter Templates by Astra', 'learndash' ),
					),
					'preview_url'       => 'https://websitedemos.net/yoga-instructor-08/',
				),
			);

			/**
			 * Filters available design wizard templates.
			 *
			 * @since 4.4.0
			 *
			 * @param array $templates
			 */
			return apply_filters( 'learndash_design_wizard_templates', $this->templates );
		}

		/**
		 * Load fonts on admin pages.
		 *
		 * @since 4.4.0
		 *
		 * @return void
		 */
		public function load_fonts() : void {
			if (
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				isset( $_GET['page'] ) && 'learndash-design-wizard' === $_GET['page']
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				&& isset( $_GET['step'] ) && intval( $_GET['step'] ) === 2
			) {
				?>
				<link rel="preconnect" href="https://fonts.googleapis.com">
				<link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
				<?php
			}
		}

		/**
		 * Maybe install a plugin.
		 *
		 * @since 4.4.0
		 *
		 * @param string $slug The plugin slug.
		 *
		 * @return bool True if the plugin is installed successfully, false otherwise.
		 */
		protected function maybe_install_a_plugin( string $slug ) : bool {
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
		 * Install a plugin.
		 *
		 * @since 4.4.0
		 *
		 * @param string $slug Plugin slug.
		 *
		 * @return bool True if plugin is installed, false otherwise.
		 */
		public function install( string $slug ) : bool {
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
		 * Register the scripts and styles.
		 *
		 * @since 4.4.0
		 *
		 * @return void
		 */
		public function enqueue_admin_scripts() : void {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['page'] ) && 'learndash-design-wizard' === $_GET['page'] ) {
				remove_all_actions( 'admin_notices' );

				wp_register_script(
					'js-cookie',
					'https://cdn.jsdelivr.net/npm/js-cookie@3.0.1/dist/js.cookie.min.js',
					array(),
					'3.0.1',
					true
				);

				wp_enqueue_style(
					'learndash-design-wizard',
					LEARNDASH_LMS_PLUGIN_URL . '/assets/css/design-wizard.css',
					array(),
					LEARNDASH_VERSION
				);

				wp_enqueue_script(
					'learndash-design-wizard',
					LEARNDASH_LMS_PLUGIN_URL . '/assets/js/design-wizard.js',
					array( 'jquery', 'js-cookie', 'updates' ),
					LEARNDASH_VERSION,
					true
				);

				ob_start();
				include_once LEARNDASH_LMS_PLUGIN_DIR . '/includes/views/design-wizard/actions-success.php';
				$actions_success = ob_get_clean();

				ob_start();
				include_once LEARNDASH_LMS_PLUGIN_DIR . '/includes/views/design-wizard/actions-error.php';
				$actions_error = ob_get_clean();

				$templates = compact( 'actions_success', 'actions_error' );

				wp_localize_script(
					'learndash-design-wizard',
					'LearnDashDesignWizard',
					array(
						'ajaxurl'                     => admin_url( 'admin-ajax.php' ),
						'site_url'                    => site_url(),
						'admin_dashboard_url'         => admin_url(),
						'learndash_setup_url'         => add_query_arg(
							array( 'page' => 'learndash-setup' ),
							admin_url( 'admin.php' )
						),
						'ajax_init_nonce'             => wp_create_nonce( 'ld_dw_build_template' ),
						'ajax_nonce'                  => wp_create_nonce( 'astra-sites' ),
						'ajax_set_data_nonce'         => wp_create_nonce( 'astra-sites-set-ai-site-data' ),
						'ajax_kadence_security_nonce' => wp_create_nonce( 'kadence-ajax-verification' ),
						'fonts'                       => $this->get_theme_fonts(),
						'palettes'                    => $this->get_theme_palettes(),
						'messages'                    => array(
							'dw_error_prefix'  => '<strong>' . __( 'Error', 'learndash' ) . '</strong>',
							// phpcs:ignore Generic.Files.LineLength.TooLong
							'dw_error_default' => __( 'There\'s unknown error with the design wizard. Please try again later or contact our support if the issue persists.', 'learndash' ),
						),
						'templates'                   => $templates,
					)
				);
			}

			if (
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				isset( $_GET['page'] ) && 'learndash-design-wizard' === $_GET['page']
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				&& isset( $_GET['step'] ) && 2 === intval( $_GET['step'] )
			) {
				wp_enqueue_style(
					'learndash-design-wizard-gfonts', // cspell:disable-line.
					// phpcs:ignore Generic.Files.LineLength.TooLong
					'https://fonts.googleapis.com/css2?family=Antic+Didone&family=Gilda+Display&family=Inter&family=Josefin+Sans:wght@700&family=Karla&family=Lato&family=Libre+Baskerville&family=Libre+Franklin:wght@700&family=Lora:wght@700&family=Merriweather:wght@400;700&family=Montserrat:wght@700&family=Nunito:wght@700&family=Open+Sans:wght@400;700&family=Oswald:wght@700&family=Playfair+Display:wght@700&family=Poppins:wght@700&family=Proza+Libre:wght@700&family=Raleway&family=Roboto&family=Roboto+Condensed:wght@700&family=Rubik:wght@700&family=Source+Sans+Pro&family=Vollkorn:wght@700&family=Work+Sans:wght@400;700&display=swap',
					array(),
					LEARNDASH_VERSION,
					'all'
				);
			}
		}

		/**
		 * Output the page HTML.
		 *
		 * @since 4.4.0
		 *
		 * @return void
		 */
		public function render() : void {
			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_GET['step'] ) && is_numeric( $_GET['step'] ) ) {
				// phpcs:ignore WordPress.Security.NonceVerification.Recommended
				$step = intval( $_GET['step'] );
			} else {
				$step = 1;
			}

			$this->render_step( $step );
		}

		/**
		 * Render HTML output for each step.
		 *
		 * @since 4.4.0
		 *
		 * @param int $step Step number of the design wizard.
		 *
		 * @return void
		 */
		public function render_step( int $step ) : void {
			$template_path = 'design-wizard/wizard-' . $step;
			$template_name = isset( $_GET['template'] ) ? sanitize_key( wp_unslash( $_GET['template'] ) ) : null;

			$args = array(
				'design_wizard'    => $this,
				'template_details' => ! empty( $template_name ) ? $this->get_template( $template_name ) : null,
			);

			switch ( $step ) {
				case 1:
					$args['templates'] = $this->get_templates();
					shuffle( $args['templates'] );
					break;

				case 2:
					$args['fonts'] = ! empty( $template_name ) ? $this->get_template_fonts( $template_name, true ) : null;
					break;

				case 3:
					$args['palettes'] = ! empty( $template_name ) ? $this->get_template_palettes( $template_name, true ) : null;
					break;

				case 4:
					// Nothing to do here.
					break;

				case 5:
					check_admin_referer( 'ld_dw_build_template', 'nonce' );
					break;
			}

			SFWD_LMS::get_view( $template_path, $args, true );
		}

		/**
		 * Register the design wizard admin page.
		 *
		 * @since 4.4.0
		 *
		 * @return void
		 */
		public function register_pages() : void {
			add_menu_page(
				__( 'LearnDash Design Wizard', 'learndash' ),
				__( 'LearnDash Design Wizard', 'learndash' ),
				LEARNDASH_ADMIN_CAPABILITY_CHECK,
				self::HANDLE,
				array( $this, 'render' )
			);

			// Hide the admin menu item, the page stays available.
			remove_menu_page( self::HANDLE );
		}

		/**
		 * Get design templates.
		 *
		 * @since 4.4.0
		 *
		 * @return array
		 */
		public function get_templates() : array {
			return $this->templates;
		}

		/**
		 * Get a specific template details.
		 *
		 * @since 4.4.0
		 *
		 * @param string $template Template key.
		 *
		 * @return array
		 */
		public function get_template( string $template ) : array {
			if ( isset( $this->templates[ $template ] ) ) {
				return $this->templates[ $template ];
			}

			return array();
		}

		/**
		 * Get a specific theme fonts.
		 *
		 * @since 4.4.0
		 *
		 * @param string $theme Theme key.
		 *
		 * @return array
		 */
		public function get_theme_fonts( string $theme = '' ) : array {
			$fonts = array();

			$kadence_fonts = array(
				'monserrat' => array(
					'label'    => 'Monserrat & Source Sans Pro',
					'families' => array(
						'heading' => 'Monserrat',
						'body'    => 'Source Sans Pro',
					),
				),
				'libre'     => array(
					'label'    => 'Libre Franklin & Libre Baskerville',
					'families' => array(
						'heading' => 'Libre Franklin',
						'body'    => 'Libre Baskerville',
					),
				),
				'proza'     => array(
					'label'    => 'Proza Libre & Open Sans',
					'families' => array(
						'heading' => 'Proza Libre',
						'body'    => 'Open Sans',
					),
				),
				'worksans'  => array( // cspell:disable-line.
					'label'    => 'Work Sans',
					'families' => array(
						'heading' => 'Work Sans',
						'body'    => 'Work Sans',
					),
				),
				'josefin'   => array(
					'label'    => 'Josefin Sans & Lato',
					'families' => array(
						'heading' => 'Josefin Sans',
						'body'    => 'Lato',
					),
				),
				'oswald'    => array(
					'label'    => 'Oswald & Open Sans',
					'families' => array(
						'heading' => 'Oswald',
						'body'    => 'Open Sans',
					),
				),
				'nunito'    => array(
					'label'    => 'Nunito & Roboto',
					'families' => array(
						'heading' => 'Nunito',
						'body'    => 'Roboto',
					),
				),
				'rubik'     => array(
					'label'    => 'Rubik & Karla',
					'families' => array(
						'heading' => 'Rubik',
						'body'    => 'Karla',
					),
				),
				'lora'      => array(
					'label'    => 'Lora & Merriweather',
					'families' => array(
						'heading' => 'Lora',
						'body'    => 'Merriweather',
					),
				),
				'playfair'  => array(
					'label'    => 'Playfair Display & Raleway',
					'families' => array(
						'heading' => 'Playfair Display',
						'body'    => 'Raleway',
					),
				),

				'antic'     => array(
					'label'    => 'Antic Didone & Raleway',
					'families' => array(
						'heading' => 'Antic Didone',
						'body'    => 'Raleway',
					),
				),
				'gilda'     => array(
					'label'    => 'Gilda Display & Raleway',
					'families' => array(
						'heading' => 'Gilda Display',
						'body'    => 'Raleway',
					),
				),
			);

			$astra_fonts = array(
				'default' => array(
					'label'    => 'Default',
					'families' => array(),
					'details'  => array(
						'body-font-family'      => '',
						'body-font-variant'     => '',
						'body-font-weight'      => 400,
						'font-size-body'        => array(
							'desktop'      => 16,
							'tablet'       => '',
							'mobile'       => '',
							'desktop-unit' => 'px',
							'tablet-unit'  => 'px',
							'mobile-unit'  => 'px',
						),
						'body-line-height'      => '',
						'headings-font-family'  => '',
						'headings-font-weight'  => 700,
						'headings-line-height'  => '',
						'headings-font-variant' => '',
					),
				),
				'1'       => array(
					'label'    => 'Playfair Display & Source Sans Pro',
					'families' => array(
						'heading' => 'Playfair Display',
						'body'    => 'Source Sans Pro',
					),
					'details'  => array(
						'body-font-family'      => "'Source Sans Pro', sans-serif",
						'body-font-variant'     => '',
						'body-font-weight'      => 400,
						'font-size-body'        => array(
							'desktop'      => 16,
							'tablet'       => '',
							'mobile'       => '',
							'desktop-unit' => 'px',
							'tablet-unit'  => 'px',
							'mobile-unit'  => 'px',
						),
						'body-line-height'      => '',
						'headings-font-family'  => "'Playfair Display', serif",
						'headings-font-weight'  => 700,
						'headings-line-height'  => '',
						'headings-font-variant' => '',
					),
				),
				'2'       => array(
					'label'    => 'Poppins & Lato',
					'families' => array(
						'heading' => 'Poppins',
						'body'    => 'Lato',
					),
					'details'  => array(
						'body-font-family'      => "'Lato', sans-serif",
						'body-font-variant'     => '',
						'body-font-weight'      => 400,
						'font-size-body'        => array(
							'desktop'      => 16,
							'tablet'       => '',
							'mobile'       => '',
							'desktop-unit' => 'px',
							'tablet-unit'  => 'px',
							'mobile-unit'  => 'px',
						),
						'body-line-height'      => '',
						'headings-font-family'  => "'Poppins', sans-serif",
						'headings-font-weight'  => 700,
						'headings-line-height'  => '',
						'headings-font-variant' => '',
					),
				),
				'3'       => array(
					'label'    => 'Monserrat & Lato',
					'families' => array(
						'heading' => 'Monserrat',
						'body'    => 'Lato',
					),
					'details'  => array(
						'body-font-family'      => "'Lato', sans-serif",
						'body-font-variant'     => '',
						'body-font-weight'      => 400,
						'font-size-body'        => array(
							'desktop'      => 17,
							'tablet'       => '',
							'mobile'       => '',
							'desktop-unit' => 'px',
							'tablet-unit'  => 'px',
							'mobile-unit'  => 'px',
						),
						'body-line-height'      => '',
						'headings-font-family'  => "'Montserrat', sans-serif",
						'headings-font-weight'  => 700,
						'headings-line-height'  => '',
						'headings-font-variant' => '',
					),
				),
				'4'       => array(
					'label'    => 'Rubik & Karla',
					'families' => array(
						'heading' => 'Rubik',
						'body'    => 'Karla',
					),
					'details'  => array(
						'body-font-family'      => "'Karla', sans-serif",
						'body-font-variant'     => '',
						'body-font-weight'      => 400,
						'font-size-body'        => array(
							'desktop'      => 17,
							'tablet'       => '',
							'mobile'       => '',
							'desktop-unit' => 'px',
							'tablet-unit'  => 'px',
							'mobile-unit'  => 'px',
						),
						'body-line-height'      => '',
						'headings-font-family'  => "'Rubik', sans-serif",
						'headings-font-weight'  => 700,
						'headings-line-height'  => '',
						'headings-font-variant' => '',
					),
				),
				'5'       => array(
					'label'    => 'Roboto Condensed & Roboto',
					'families' => array(
						'heading' => 'Roboto Condensed',
						'body'    => 'Roboto',
					),
					'details'  => array(
						'body-font-family'      => "'Roboto', sans-serif",
						'body-font-variant'     => '',
						'body-font-weight'      => 400,
						'font-size-body'        => array(
							'desktop'      => 16,
							'tablet'       => '',
							'mobile'       => '',
							'desktop-unit' => 'px',
							'tablet-unit'  => 'px',
							'mobile-unit'  => 'px',
						),
						'body-line-height'      => '',
						'headings-font-family'  => "'Roboto Condensed', sans-serif",
						'headings-font-weight'  => 700,
						'headings-line-height'  => '',
						'headings-font-variant' => '',
					),
				),
				'6'       => array(
					'label'    => 'Merriweather & Inter',
					'families' => array(
						'heading' => 'Merriweather',
						'body'    => 'Inter',
					),
					'details'  => array(
						'body-font-family'      => "'Inter', sans-serif",
						'body-font-variant'     => '',
						'body-font-weight'      => 400,
						'font-size-body'        => array(
							'desktop'      => 17,
							'tablet'       => '',
							'mobile'       => '',
							'desktop-unit' => 'px',
							'tablet-unit'  => 'px',
							'mobile-unit'  => 'px',
						),
						'body-line-height'      => '',
						'headings-font-family'  => "'Merriweather', serif",
						'headings-font-weight'  => 700,
						'headings-line-height'  => '',
						'headings-font-variant' => '',
					),
				),
				'7'       => array(
					'label'    => 'Volkorn & Open Sans', // cspell:disable-line.
					'families' => array(
						'heading' => 'Volkorn', // cspell:disable-line.
						'body'    => 'Open Sans',
					),
					'details'  => array(
						'body-font-family'      => "'Open Sans', sans-serif",
						'body-font-variant'     => '',
						'body-font-weight'      => 400,
						'font-size-body'        => array(
							'desktop'      => 16,
							'tablet'       => '',
							'mobile'       => '',
							'desktop-unit' => 'px',
							'tablet-unit'  => 'px',
							'mobile-unit'  => 'px',
						),
						'body-line-height'      => '',
						'headings-font-family'  => "'Vollkorn', serif",
						'headings-font-weight'  => 700,
						'headings-line-height'  => '',
						'headings-font-variant' => '',
					),
				),
				'8'       => array(
					'label'    => 'Open Sans & Work Sans',
					'families' => array(
						'heading' => 'Open Sans',
						'body'    => 'Work Sans',
					),
					'details'  => array(
						'body-font-family'      => "'Work Sans', sans-serif",
						'body-font-variant'     => '',
						'body-font-weight'      => 400,
						'font-size-body'        => array(
							'desktop'      => 16,
							'tablet'       => '',
							'mobile'       => '',
							'desktop-unit' => 'px',
							'tablet-unit'  => 'px',
							'mobile-unit'  => 'px',
						),
						'body-line-height'      => '',
						'headings-font-family'  => "'Open Sans', sans-serif",
						'headings-font-weight'  => 700,
						'headings-line-height'  => '',
						'headings-font-variant' => '',
					),
				),
			);

			if ( ! empty( $theme ) ) {
				switch ( $theme ) {
					case 'kadence':
						$fonts = $kadence_fonts;
						break;

					case 'astra':
						$fonts = $astra_fonts;
						break;
				}
			} else {
				$fonts = array(
					'kadence' => $kadence_fonts,
					'astra'   => $astra_fonts,
				);
			}

			return $fonts;
		}

		/**
		 * Get a specific template available fonts.
		 *
		 * @since 4.4.0
		 *
		 * @param string  $template     Template key.
		 * @param boolean $omit_default Whether to omit default fonts.
		 *
		 * @return array
		 */
		public function get_template_fonts( string $template = '', bool $omit_default = false ) : array {
			// @var array{ theme: string } Template details.
			$template = $this->get_template( $template );
			$fonts    = array();

			if ( ! empty( $template['theme'] ) ) {
				switch ( $template['theme'] ) {
					case 'kadence':
						$fonts = $this->get_theme_fonts( 'kadence' );
						break;

					case 'astra':
						$fonts = $this->get_theme_fonts( 'astra' );
						break;
				}
			}

			if ( $omit_default && isset( $fonts['default'] ) ) {
				unset( $fonts['default'] );
			}

			return $fonts;
		}

		/**
		 * Get a specific theme available palettes.
		 *
		 * @since 4.4.0
		 *
		 * @param string $theme Theme key.
		 *
		 * @return array
		 */
		public function get_theme_palettes( string $theme = '' ) : array {
			$palettes = array();

			$kadence_palettes = array(
				'base'        => array(
					'colors' => array(
						'#2B6CB0',
						'#3B3B3B',
						'#E1E1E1',
						'#F7F7F7',
					),
				),
				'orange'      => array(
					'colors' => array(
						'#e47b02',
						'#3E4C59',
						'#F3F4F7',
						'#F9F9FB',
					),
				),
				'pinkish'     => array(
					'colors' => array(
						'#E21E51',
						'#032075',
						'#DEDDEB',
						'#EFEFF5',
					),
				),
				'mint'        => array(
					'colors' => array(
						'#2cb1bc',
						'#133453',
						'#e0fcff',
						'#f5f7fa',
					),
				),
				'green'       => array(
					'colors' => array(
						'#049f82',
						'#353535',
						'#EEEEEE',
						'#F7F7F7',
					),
				),
				'rich'        => array(
					'colors' => array(
						'#295CFF',
						'#1C0D5A',
						'#E1EBEE',
						'#EFF7FB',
					),
				),
				'fem'         => array(
					'colors' => array(
						'#D86C97',
						'#282828',
						'#f7dede',
						'#F6F2EF',
					),
				),
				'teal'        => array(
					'colors' => array(
						'#7ACFC4',
						'#000000',
						'#F6E7BC',
						'#F9F7F7',
					),
				),
				'bold'        => array(
					'colors' => array(
						'#000000',
						'#000000',
						'#F6E7BC',
						'#F9F7F7',
					),
				),
				'hot'         => array(
					'colors' => array(
						'#FF5698',
						'#000000',
						'#FDEDEC',
						'#FDF6EE',
					),
				),
				'darkmode'    => array(
					'colors' => array(
						'#3296ff',
						'#F7FAFC',
						'#2D3748',
						'#252C39',
					),
				),
				'pinkishdark' => array( // cspell:disable-line.
					'colors' => array(
						'#E21E51',
						'#EFEFF5',
						'#514D7C',
						'#221E5B',
					),
				),
			);

			$astra_palettes = array(
				'dark'  => array(
					'default'  => array(
						'slug'   => 'default',
						'title'  => __( 'Default', 'learndash' ),
						'colors' => array(),
					),
					'style-1'  => array(
						'slug'   => 'style-1',
						'title'  => __( 'Style 1', 'learndash' ),
						'colors' => array(
							'#8E43F0',
							'#7215EA',
							'#FFFFFF',
							'#EEEBF4',
							'#150E1F',
							'#494153',
							'#000000',
							'#4B4F58',
							'#F6F7F8',
						),
					),
					'style-2'  => array(
						'slug'   => 'style-2',
						'title'  => __( 'Style 2', 'learndash' ),
						'colors' => array(
							'#EF4D48',
							'#D90700',
							'#FFFFFF',
							'#EEEAEC',
							'#2B161B',
							'#3C2F32',
							'#000000',
							'#4B4F58',
							'#F6F7F8',
						),
					),
					'style-3'  => array(
						'slug'   => 'style-3',
						'title'  => __( 'Style 3', 'learndash' ),
						'colors' => array(
							'#FF42B3',
							'#FF0099',
							'#FFFFFF',
							'#EEEAEC',
							'#2B161B',
							'#3C2F32',
							'#000000',
							'#4B4F58',
							'#F6F7F8',
						),
					),
					'style-4'  => array(
						'slug'   => 'style-4',
						'title'  => __( 'Style 4', 'learndash' ),
						'colors' => array(
							'#FF6A97',
							'#FA036B',
							'#FFFFFF',
							'#EEEAEC',
							'#2B161B',
							'#3C2F32',
							'#000000',
							'#4B4F58',
							'#F6F7F8',
						),
					),
					'style-5'  => array(
						'slug'   => 'style-5',
						'title'  => __( 'Style 5', 'learndash' ),
						'colors' => array(
							'#FF7A3D',
							'#FF5100',
							'#FFFFFF',
							'#F1EDEB',
							'#1E1810',
							'#443D3A',
							'#000000',
							'#4B4F58',
							'#F6F7F8',
						),
					),
					'style-6'  => array(
						'slug'   => 'style-6',
						'title'  => __( 'Style 6', 'learndash' ),
						'colors' => array(
							'#F9C349',
							'#FFB100',
							'#FFFFFF',
							'#F0EFEC',
							'#1E1810',
							'#4D4A46',
							'#000000',
							'#4B4F58',
							'#F6F7F8',
						),
					),
					'style-7'  => array(
						'slug'   => 'style-7',
						'title'  => __( 'Style 7', 'learndash' ),
						'colors' => array(
							'#30C7B5',
							'#00AC97',
							'#FFFFFF',
							'#F0EFEC',
							'#1E1810',
							'#4D4A46',
							'#000000',
							'#4B4F58',
							'#F6F7F8',
						),
					),
					'style-8'  => array(
						'slug'   => 'style-8',
						'title'  => __( 'Style 8', 'learndash' ),
						'colors' => array(
							'#1BAE70',
							'#06752E',
							'#FFFFFF',
							'#EBECEB',
							'#14261C',
							'#3D4641',
							'#000000',
							'#4B4F58',
							'#F6F7F8',
						),
					),
					'style-9'  => array(
						'slug'   => 'style-9',
						'title'  => __( 'Style 9', 'learndash' ),
						'colors' => array(
							'#2FE6FF',
							'#00D0EC',
							'#FFFFFF',
							'#E8EBEC',
							'#101218',
							'#3B4244',
							'#000000',
							'#4B4F58',
							'#F6F7F8',
						),
					),
					'style-10' => array(
						'slug'   => 'style-10',
						'title'  => __( 'Style 10', 'learndash' ),
						'colors' => array(
							'#4175FC',
							'#084AF3',
							'#FFFFFF',
							'#E8EBEC',
							'#101218',
							'#3B4244',
							'#000000',
							'#4B4F58',
							'#F6F7F8',
						),
					),
				),
				'light' => array(
					'default'  => array(
						'slug'   => 'default',
						'title'  => __( 'Default', 'learndash' ),
						'colors' => array(),
					),
					'style-1'  => array(
						'slug'   => 'style-1',
						'title'  => __( 'Style 1', 'learndash' ),
						'colors' => array(
							'#8E43F0',
							'#6300E2',
							'#150E1F',
							'#584D66',
							'#F3F1F6',
							'#FFFFFF',
							'#000000',
							'#4B4F58',
							'#F6F7F8',
						),
					),
					'style-2'  => array(
						'slug'   => 'style-2',
						'title'  => __( 'Style 2', 'learndash' ),
						'colors' => array(
							'#EF4D48',
							'#D90700',
							'#2B161B',
							'#453E3E',
							'#F7F3F5',
							'#FFFFFF',
							'#000000',
							'#4B4F58',
							'#F6F7F8',
						),
					),
					'style-3'  => array(
						'slug'   => 'style-3',
						'title'  => __( 'Style 3', 'learndash' ),
						'colors' => array(
							'#FF42B3',
							'#FF0099',
							'#2B161B',
							'#554B4E',
							'#F6F3F5',
							'#FFFFFF',
							'#000000',
							'#4B4F58',
							'#F6F7F8',
						),
					),
					'style-4'  => array(
						'slug'   => 'style-4',
						'title'  => __( 'Style 4', 'learndash' ),
						'colors' => array(
							'#FF6A97',
							'#FA036B',
							'#2B161B',
							'#645659',
							'#F8F3F5',
							'#FFFFFF',
							'#000000',
							'#4B4F58',
							'#F6F7F8',
						),
					),
					'style-5'  => array(
						'slug'   => 'style-5',
						'title'  => __( 'Style 5', 'learndash' ),
						'colors' => array(
							'#FF7A3D',
							'#FF5100',
							'#1E1810',
							'#575250',
							'#F8F5F4',
							'#FFFFFF',
							'#000000',
							'#4B4F58',
							'#F6F7F8',
						),
					),
					'style-6'  => array(
						'slug'   => 'style-6',
						'title'  => __( 'Style 6', 'learndash' ),
						'colors' => array(
							'#F9C349',
							'#FFB100',
							'#1E1810',
							'#62615C',
							'#F8F7F3',
							'#FFFFFF',
							'#000000',
							'#4B4F58',
							'#F6F7F8',
						),
					),
					'style-7'  => array(
						'slug'   => 'style-7',
						'title'  => __( 'Style 7', 'learndash' ),
						'colors' => array(
							'#30C7B5',
							'#00AC97',
							'#14261C',
							'#4F5655',
							'#F3F6F3',
							'#FFFFFF',
							'#000000',
							'#4B4F58',
							'#F6F7F8',
						),
					),
					'style-8'  => array(
						'slug'   => 'style-8',
						'title'  => __( 'Style 8', 'learndash' ),
						'colors' => array(
							'#1BAE70',
							'#06752E',
							'#14261C',
							'#4E5652',
							'#F4F6F4',
							'#FFFFFF',
							'#000000',
							'#4B4F58',
							'#F6F7F8',
						),
					),
					'style-9'  => array(
						'slug'   => 'style-9',
						'title'  => __( 'Style 9', 'learndash' ),
						'colors' => array(
							'#2FC1FF',
							'#08ACF2',
							'#101218',
							'#4C5253',
							'#F3F6F6',
							'#FFFFFF',
							'#000000',
							'#4B4F58',
							'#F6F7F8',
						),
					),
					'style-10' => array(
						'slug'   => 'style-10',
						'title'  => __( 'Style 10', 'learndash' ),
						'colors' => array(
							'#4175FC',
							'#084AF3',
							'#101218',
							'#494B51',
							'#F3F5F5',
							'#FFFFFF',
							'#000000',
							'#4B4F58',
							'#F6F7F8',
						),
					),
				),
			);

			if ( ! empty( $theme ) ) {
				switch ( $theme ) {
					case 'kadence':
						$palettes = $kadence_palettes;
						break;

					case 'astra':
						$palettes = $astra_palettes;
						break;
				}
			} else {
				$palettes = array(
					'kadence' => $kadence_palettes,
					'astra'   => $astra_palettes,
				);
			}

			return $palettes;
		}

		/**
		 * Get a specific template available palettes.
		 *
		 * @since 4.4.0
		 *
		 * @param string  $template     Template key.
		 * @param boolean $omit_default Whether to omit default template.
		 *
		 * @return array
		 */
		public function get_template_palettes( string $template, bool $omit_default = false ) : array {
			$template = $this->get_template( $template );
			$palettes = array();

			if ( ! empty( $template['theme'] ) ) {
				switch ( $template['theme'] ) {
					case 'kadence':
						$palettes = $this->get_theme_palettes( 'kadence' );
						break;

					case 'astra':
						$palettes     = $this->get_theme_palettes( 'astra' );
						$color_scheme = 'light'; // Default color scheme.
						$color_scheme = ! empty( $template['color_scheme'] ) ? $template['color_scheme'] : $color_scheme;

						$palettes = $palettes[ $color_scheme ];

						$palettes = array_map(
							function ( $palette ) {
								$palette['colors'] = array_slice( $palette['colors'], 0, 5 );

								return $palette;
							},
							$palettes
						);
						break;
				}
			}

			if ( $omit_default && isset( $palettes['default'] ) ) {
				unset( $palettes['default'] );
			}

			return $palettes;
		}

		/**
		 * Get template preview image URL.
		 *
		 * @since 4.4.0
		 *
		 * @param string $template Template key.
		 *
		 * @return string
		 */
		public function get_template_preview_image_url( string $template ) : string {
			$template = $this->get_template( $template );

			$image_dir_url  = LEARNDASH_LMS_PLUGIN_URL . '/assets/images/design-wizard/previews/';
			$image_dir_path = LEARNDASH_LMS_PLUGIN_DIR . '/assets/images/design-wizard/previews/';

			$image_url  = '';
			$extensions = array( 'jpg', 'jpeg', 'png' );
			foreach ( $extensions as $extension ) {
				if ( file_exists( $image_dir_path . $template['id'] . '.' . $extension ) ) {
					$image_url = $image_dir_url . $template['id'] . '.' . $extension;
					break;
				}
			}

			/**
			 * Filters preview image url of a template.
			 *
			 * @since 4.4.0
			 *
			 * @param string $image_url Original template image URL.
			 * @param array  $template  Template details.
			 */
			return apply_filters( 'learndash_design_wizard_template_preview_image_url', $image_url, $template );
		}

		/**
		 * Theme/Plugin installer methods.
		 */

		/**
		 * Install theme.
		 *
		 * @since 4.4.0
		 *
		 * @param string $theme Theme key.
		 *
		 * @return array Installation status details.
		 */
		public function install_theme( string $theme ) : array {
			if ( empty( $theme ) ) {
				wp_send_json_error(
					array(
						'slug'         => '',
						'errorCode'    => 'no_theme_specified',
						'errorMessage' => __( 'No theme specified.', 'learndash' ),
					)
				);
			}

			$slug = sanitize_key( wp_unslash( $theme ) );

			$status = array(
				'install' => 'theme',
				'slug'    => $slug,
			);

			if ( ! current_user_can( 'install_themes' ) ) {
				$status['errorMessage'] =
					__( 'Sorry, you are not allowed to install themes on this site.', 'learndash' );
				wp_send_json_error( $status );
			}

			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			include_once ABSPATH . 'wp-admin/includes/theme.php';

			$api = themes_api(
				'theme_information',
				array(
					'slug'   => $slug,
					'fields' => array( 'sections' => false ),
				)
			);

			if ( is_wp_error( $api ) ) {
				$status['errorMessage'] = $api->get_error_message();
				wp_send_json_error( $status );
			}

			$skin     = new WP_Ajax_Upgrader_Skin();
			$upgrader = new Theme_Upgrader( $skin );

			$result = null;
			if ( ! empty( $api->download_link ) ) {
				$result = $upgrader->install( $api->download_link );
			}

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$status['debug'] = $skin->get_upgrade_messages();
			}

			if ( is_wp_error( $result ) ) {
				$status['errorCode']    = $result->get_error_code();
				$status['errorMessage'] = $result->get_error_message();
				wp_send_json_error( $status );
			} elseif ( is_wp_error( $skin->result ) ) {
				$status['errorCode']    = $skin->result->get_error_code();
				$status['errorMessage'] = $skin->result->get_error_message();
				wp_send_json_error( $status );
			} elseif ( $skin->get_errors()->has_errors() ) {
				$status['errorMessage'] = $skin->get_error_messages();
				wp_send_json_error( $status );
			} elseif ( null === $result ) {
				global $wp_filesystem;

				$status['errorCode']    = 'unable_to_connect_to_filesystem';
				$status['errorMessage'] =
					__( 'Unable to connect to the filesystem. Please confirm your credentials.', 'learndash' );

				// Pass through the error from WP_Filesystem if one was raised.
				if ( $wp_filesystem instanceof WP_Filesystem_Base && $wp_filesystem->errors->has_errors() ) {
					$status['errorMessage'] = esc_html( $wp_filesystem->errors->get_error_message() );
				}

				wp_send_json_error( $status );
			}

			$status['themeName'] = wp_get_theme( $slug )->get( 'Name' );
			$status['success']   = true;

			return $status;
		}

		/**
		 * Install plugin.
		 *
		 * @since 4.4.0
		 *
		 * @param string $plugin Plugin key.
		 *
		 * @return array Installation status details.
		 */
		public function install_plugin( string $plugin ) : array {
			if ( empty( $plugin ) ) {
				wp_send_json_error(
					array(
						'slug'         => '',
						'errorCode'    => 'no_plugin_specified',
						'errorMessage' => __( 'No plugin specified.', 'learndash' ),
					)
				);
			}

			$plugin_key = sanitize_key( wp_unslash( $plugin ) );

			$status = array(
				'install' => 'plugin',
				'slug'    => $plugin_key,
			);

			if ( ! current_user_can( 'install_plugins' ) ) {
				$status['errorMessage'] =
					__( 'Sorry, you are not allowed to install plugins on this site.', 'learndash' );
				wp_send_json_error( $status );
			}

			require_once ABSPATH . 'wp-admin/includes/class-wp-upgrader.php';
			include_once ABSPATH . 'wp-admin/includes/plugin-install.php';

			$api = plugins_api(
				'plugin_information',
				array(
					'slug'   => $plugin_key,
					'fields' => array(
						'sections' => false,
					),
				)
			);

			if ( is_wp_error( $api ) ) {
				$status['errorMessage'] = $api->get_error_message();
			}

			if ( ! empty( $api->name ) ) {
				$status['pluginName'] = $api->name;
			}

			$skin     = new WP_Ajax_Upgrader_Skin();
			$upgrader = new Plugin_Upgrader( $skin );

			$result = null;
			if ( ! empty( $api->download_link ) ) {
				$result = $upgrader->install( $api->download_link );
			}

			if ( defined( 'WP_DEBUG' ) && WP_DEBUG ) {
				$status['debug'] = $skin->get_upgrade_messages();
			}

			if ( is_wp_error( $result ) ) {
				$status['errorCode']    = $result->get_error_code();
				$status['errorMessage'] = $result->get_error_message();
			} elseif ( is_wp_error( $skin->result ) ) {
				$status['errorCode']    = $skin->result->get_error_code();
				$status['errorMessage'] = $skin->result->get_error_message();
			} elseif ( $skin->get_errors()->has_errors() ) {
				$status['errorMessage'] = $skin->get_error_messages();
			} elseif ( null === $result ) {
				global $wp_filesystem;

				$status['errorCode']    = 'unable_to_connect_to_filesystem';
				$status['errorMessage'] =
					__( 'Unable to connect to the filesystem. Please confirm your credentials.', 'learndash' );

				// Pass through the error from WP_Filesystem if one was raised.
				if ( $wp_filesystem instanceof WP_Filesystem_Base && $wp_filesystem->errors->has_errors() ) {
					$status['errorMessage'] = esc_html( $wp_filesystem->errors->get_error_message() );
				}
			}

			// Try plugin installation fallback if there's error.
			if ( ! empty( $status['errorCode'] ) || ! empty( $status['errorMessage'] ) ) {
				$this->install_plugin_fallback( $plugin_key, $status );
			}

			// Send error response if there's still error.
			if ( ! empty( $status['errorCode'] ) || ! empty( $status['errorMessage'] ) ) {
				wp_send_json_error( $status );
			}

			$status['success'] = true;

			return $status;
		}

		/**
		 * Fallback method to install plugin from ZIP file.
		 *
		 * @since 4.4.1.1
		 *
		 * @param string $plugin_key Plugin key.
		 * @param array  $status    Passed by reference to update status.
		 *
		 * @return void
		 */
		private function install_plugin_fallback( $plugin_key = '', &$status = array() ) : void {
			$filename = $plugin_key . '.zip';
			$file     = LEARNDASH_LMS_PLUGIN_DIR . 'plugins/' . $filename;

			if ( file_exists( $file ) ) {
				if ( defined( 'WP_PLUGIN_DIR' ) ) {
					$plugins_dir = WP_PLUGIN_DIR;
				} else {
					$plugins_dir = ABSPATH . 'wp-content/plugins';
				}

				$plugin_name = '';
				switch ( $plugin_key ) {
					case 'kadence-starter-templates':
						$plugin_name = 'Kadence Starter Templates';
						break;
				}

				$zip = new ZipArchive();
				if ( $zip->open( $file ) === true ) {
					$zip->extractTo( $plugins_dir );
					$zip->close();

					$status['pluginName'] = $plugin_name;
					unset( $status['errorCode'] );
					unset( $status['errorMessage'] );
				} else {
					$status['errorCode']    = 'failed_extract';
					$status['errorMessage'] = __( 'We can\'t extract plugin file. Please check your file and directory permission.', 'learndash' );
				}
			}
		}

		/**
		 * Activate theme.
		 *
		 * @since 4.4.0
		 *
		 * @param string $theme Theme key.
		 *
		 * @return void
		 */
		public function activate_theme( string $theme ) : void {
			$theme = wp_get_theme( $theme );

			if ( $theme->exists() && $theme->is_allowed() ) {
				switch_theme( $theme->get_stylesheet() );
			}
		}

		/**
		 * Activate plugin.
		 *
		 * @since 4.4.0
		 *
		 * @param string $plugin Plugin key.
		 *
		 * @return void
		 */
		public function activate_plugin( string $plugin ) : void {
			$plugin_file = $plugin . '/' . $plugin . '.php';

			activate_plugin( $plugin_file );
		}

		/**
		 * AJAX handlers.
		 */

		/**
		 * AJAX handler for building template.
		 *
		 * @since 4.4.0
		 *
		 * @return void
		 */
		public function ajax_ld_dw_build_template() : void {
			check_ajax_referer( 'ld_dw_build_template', 'nonce' );

			if ( ! current_user_can( 'switch_themes' ) ) {
				wp_send_json_error(
					array(
						'message' => __( 'User doesn\'t have enough capability', 'learndash' ),
						'time'    => gmdate( 'Y-m-d H:i:s' ),
					)
				);
			}

			$this->ajax_template = isset( $_REQUEST['template'] ) ? $this->get_template( sanitize_key( $_REQUEST['template'] ) ) : null;

			$current_step = $this->ajax_get_current_step();

			switch ( $current_step ) {
				case 'install_theme':
					$response = $this->ajax_install_theme();
					break;

				case 'install_plugin':
					$response = $this->ajax_install_plugin();
					break;

				case 'activate_theme':
					$response = $this->ajax_activate_theme();
					break;

				case 'activate_plugin':
					$response = $this->ajax_activate_plugin();
					break;

				case 'build_template':
					$response = $this->ajax_build_template();
					break;

				case 'end_build_process':
					$response = $this->ajax_end_build_process();
					break;
			}

			if ( isset( $response['completed'] ) && $response['completed'] ) {
				$message = __( 'The template has been built and is ready to use.', 'learndash' );

				update_option( 'learndash_design_wizard_status', 'completed' );
				delete_option( 'ld_dw_build_last_step' );
			} else {
				$message = ! empty( $response['next_step_message'] ) ? $response['next_step_message'] : '';

				update_option( 'ld_dw_build_last_step', $current_step );
			}

			$complete = isset( $response['completed'] ) && $response['completed'];

			wp_send_json_success(
				array(
					'step'     => $current_step,
					'theme'    => $this->ajax_template['theme'],
					'template' => $this->ajax_template['id'],
					'complete' => $complete,
					'message'  => $message,
					'time'     => gmdate( 'Y-m-d H:i:s' ),
				)
			);
		}

		/**
		 * AJAX helper methods.
		 */

		/**
		 * Get current step in the template building process.
		 *
		 * @since 4.4.0
		 *
		 * @return string Current step in the template building process cycle.
		 */
		public function ajax_get_current_step() {
			$steps = array(
				'install_theme',
				'install_plugin',
				'activate_theme',
				'activate_plugin',
				'build_template',
				'end_build_process',
			);

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( isset( $_REQUEST['init'] ) && 'true' === $_REQUEST['init'] ) {
				$last_step = false;

				update_option( 'show_on_front', 'post' );
				delete_option( 'page_on_front' );
			} else {
				$last_step = get_option( 'ld_dw_build_last_step' );
			}

			if ( ! $last_step ) {
				$current_step = $steps[0];
			} else {
				$key          = array_search( $last_step, $steps, true ) + 1;
				$current_step = isset( $steps[ $key ] ) ? $steps[ $key ] : '';
			}

			return $current_step;
		}

		/**
		 * Install theme helper method.
		 *
		 * @since 4.4.0
		 *
		 * @return array
		 */
		public function ajax_install_theme() {
			$response = array();
			$themes   = wp_get_themes();

			if ( ! isset( $themes[ $this->ajax_template['theme'] ] ) ) {
				$install = $this->install_theme( $this->ajax_template['theme'] );
			}

			if ( isset( $install['success'] ) ) {
				$response['next_step_message'] = __( 'Install plugin(s)', 'learndash' );
			}

			return $response;
		}

		/**
		 * Install plugin helper method.
		 *
		 * @since 4.4.0
		 *
		 * @return array
		 */
		public function ajax_install_plugin() {
			$response    = array();
			$plugin_keys = array_keys( get_plugins() );
			$plugin_keys = array_map(
				function ( $key ) {
					return preg_replace( '/\/.*/', '', $key );
				},
				$plugin_keys
			);

			foreach ( $this->ajax_template['plugins'] as $key => $label ) {
				if ( ! in_array( $key, $plugin_keys, true ) ) {
					$install = $this->install_plugin( $key );
				}
			}

			if ( isset( $install['success'] ) ) {
				$response['next_step_message'] = __( 'Activate theme', 'learndash' );
			}

			return $response;
		}

		/**
		 * Theme activation helper method.
		 *
		 * @since 4.4.0
		 *
		 * @return array
		 */
		public function ajax_activate_theme() {
			$this->activate_theme( $this->ajax_template['theme'] );

			return array(
				'next_step_message' => __( 'Activate plugin(s)', 'learndash' ),
			);
		}

		/**
		 * Plugin activation helper method.
		 *
		 * @since 4.4.0
		 *
		 * @return array
		 */
		public function ajax_activate_plugin() {
			foreach ( $this->ajax_template['plugins'] as $key => $label ) {
				$this->activate_plugin( $key );
			}

			return array(
				'next_step_message' => __( 'Build template', 'learndash' ),
			);
		}

		/**
		 * Build template helper method.
		 *
		 * @since 4.4.0
		 *
		 * @return array
		 */
		public function ajax_build_template() {
			$message = '';
			if ( isset( $this->ajax_template['theme'] ) && $this->ajax_template['theme'] === 'kadence' ) {
				$message = __( 'Run Kadence template building process', 'learndash' );
			} elseif ( isset( $this->ajax_template['theme'] ) && $this->ajax_template['theme'] === 'astra' ) {
				$message = __( 'Run Astra template building process', 'learndash' );
			}

			return array( 'next_step_message' => $message );
		}

		/**
		 * Helper method for build process ending.
		 *
		 * @since 4.4.0
		 *
		 * @return array
		 */
		public function ajax_end_build_process() {
			return array(
				'completed' => true,
			);
		}
	}
}
