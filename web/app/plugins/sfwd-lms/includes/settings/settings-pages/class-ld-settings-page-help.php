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

if ( class_exists( 'LearnDash_Settings_Page' ) && ! class_exists( 'LearnDash_Settings_Page_Help' ) ) {
	/**
	 * Class LearnDash Settings Page Overview.
	 *
	 * @since 4.4.0
	 */
	class LearnDash_Settings_Page_Help extends LearnDash_Settings_Page {

		/**
		 * Public constructor for class
		 *
		 * @since 4.4.0
		 */
		public function __construct() {
			$this->parent_menu_page_url  = 'admin.php?page=learndash-help';
			$this->menu_page_capability  = LEARNDASH_ADMIN_CAPABILITY_CHECK;
			$this->settings_page_id      = 'learndash-help';
			$this->settings_page_title   = esc_html__( 'LearnDash Help', 'learndash' );
			$this->settings_tab_title    = esc_html__( 'Help', 'learndash' );
			$this->settings_tab_priority = 100;

			if ( ! learndash_cloud_is_enabled() ) {
				add_filter( 'learndash_submenu', array( $this, 'submenu_item' ), 200 );

				add_filter( 'learndash_admin_tab_sets', array( $this, 'learndash_admin_tab_sets' ), 10, 3 );
				add_filter( 'learndash_header_data', array( $this, 'admin_header' ), 40, 3 );
				add_action( 'admin_head', array( $this, 'output_admin_inline_scripts' ) );

				parent::__construct();
			}
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
					$submenu,
					array(
						$this->settings_page_id => array(
							'name'  => $this->settings_tab_title,
							'cap'   => $this->menu_page_capability,
							'link'  => $this->parent_menu_page_url,
							'class' => 'submenu-ldlms-help',
						),
					)
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
			?>
            <?php // phpcs:ignore?>
            <?php if ( isset( $_GET['page'] ) && in_array( $_GET['page'], [ 'learndash-help' ], true ) ) : ?>
				<style>
					body .notice {
						display: none;
					}
				</style>
			<?php endif; ?>
			<?php
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
		 * Enqueue support assets
		 *
		 * @since 4.4.0
		 *
		 * @return void
		 */
		public static function enqueue_support_assets() : void {
			wp_enqueue_style(
				'learndash-help',
				LEARNDASH_LMS_PLUGIN_URL . '/assets/css/help.css',
				array(),
				LEARNDASH_VERSION,
				'all'
			);

			wp_enqueue_script(
				'learndash-help',
				LEARNDASH_LMS_PLUGIN_URL . '/assets/js/help.js',
				array( 'jquery' ),
				LEARNDASH_VERSION,
				true
			);
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

			self::enqueue_support_assets();

			$learndash_assets_loaded['styles']['learndash-admin-help-page-style'] = __FUNCTION__;

			$learndash_assets_loaded['scripts']['learndash-admin-help-page-script'] = __FUNCTION__;
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
				if ( 'admin_page_learndash-help' === $current_page_id ) {
					?>
					<style> h1.nav-tab-wrapper { display: none; }</style>
					<?php
				}
			}
			return $tab_set;
		}

		/**
		 * Output page display HTML.
		 *
		 * @since 4.4.0
		 *
		 * @return void
		 */
		public function show_settings_page() : void {
			$categories = self::get_categories();

			SFWD_LMS::get_view(
				'support/help',
				array(
					'categories' => $categories,
				),
				true
			);
		}

		/**
		 * Get categories
		 *
		 * @since 4.4.0
		 *
		 * @return array<string, array<string, string>>
		 */
		public static function get_categories() : array {
			$categories = array(
				'getting-started'     => array(
					'id'          => 'getting-started',
					'helpScoutId' => '',
					'label'       => __( 'Getting Started', 'learndash' ),
                    // phpcs:ignore Generic.Files.LineLength.TooLong
					'description' => __( 'Not sure what to do next? Read our top articles to get more information.', 'learndash' ),
					'icon'        => 'getting-started',
				),
				'learndash-core'      => array(
					'id'          => 'learndash-core',
					'helpScoutId' => '',
					'label'       => __( 'LearnDash Core', 'learndash' ),
                    // phpcs:ignore Generic.Files.LineLength.TooLong
					'description' => __( 'Everything about LearnDash LMS core plugin.', 'learndash' ),
					'icon'        => 'core',
				),
				'add-ons'             => array(
					'id'          => 'add-ons',
					'helpScoutId' => '',
					'label'       => __( 'Add-Ons', 'learndash' ),
                    // phpcs:ignore Generic.Files.LineLength.TooLong
					'description' => __( 'Course Grid, Stripe, WooCommerce, Zapier, and other official add-ons documentations.', 'learndash' ),
					'icon'        => 'addons',
				),
				'users-and-groups'    => array(
					'id'          => 'users-and-groups',
					'helpScoutId' => '',
					'label'       => __( 'Users & Groups', 'learndash' ),
                    // phpcs:ignore Generic.Files.LineLength.TooLong
					'description' => __( 'Have questions about users & groups? Our articles may help.', 'learndash' ),
					'icon'        => 'users-groups',
				),
				'reporting'           => array(
					'id'          => 'reporting',
					'helpScoutId' => '',
					'label'       => __( 'Reporting', 'learndash' ),
                    // phpcs:ignore Generic.Files.LineLength.TooLong
					'description' => __( 'LearnDash reporting guides.', 'learndash' ),
					'icon'        => 'reporting',
				),
				'user-guides'         => array(
					'id'          => 'user-guides',
					'helpScoutId' => '',
					'label'       => __( 'User Guides', 'learndash' ),
                    // phpcs:ignore Generic.Files.LineLength.TooLong
					'description' => __( 'Collection of guides that will help you accomplish certain tasks.', 'learndash' ),
					'icon'        => 'user-guides',
				),
				'troubleshooting'     => array(
					'id'          => 'troubleshooting',
					'helpScoutId' => '',
					'label'       => __( 'Troubleshooting', 'learndash' ),
                    // phpcs:ignore Generic.Files.LineLength.TooLong
					'description' => __( 'Have issues? Follow our troubleshooting guides to resolve them.', 'learndash' ),
					'icon'        => 'troubleshooting',
				),
				'faqs'                => array(
					'id'          => 'faqs',
					'helpScoutId' => '',
					'label'       => __( 'FAQs', 'learndash' ),
                    // phpcs:ignore Generic.Files.LineLength.TooLong
					'description' => __( 'Have a question? See if it\'s already been answered.', 'learndash' ),
					'icon'        => 'faqs',
				),
				'account-and-billing' => array(
					'id'          => 'account-and-billing',
					'helpScoutId' => '',
					'label'       => __( 'Accounts & Billing', 'learndash' ),
                    // phpcs:ignore Generic.Files.LineLength.TooLong
					'description' => __( 'Accounts & Billing related articles.', 'learndash' ),
					'icon'        => 'accounts-billing',
				),
			);

			return $categories;
		}

		/**
		 * Get article categories.
		 *
		 * @since 4.4.0
		 *
		 * @param array<string> $exclude_categories Category keys that will excluded in the result.
		 *
		 * @return array<string, string>
		 */
		public static function get_articles_categories( array $exclude_categories = array() ) : array {
			$categories = array(
				'additional_resources' => __( 'Additional Resources', 'learndash' ),
				'build_courses'        => __( 'Build Courses', 'learndash' ),
				'sell_courses'         => __( 'Sell Your Courses', 'learndash' ),
				'manage_students'      => __( 'Manage Students', 'learndash' ),
			);

			if ( ! empty( $exclude_categories ) ) {
				$categories = array_filter(
					$categories,
					function ( $category ) use ( $exclude_categories ) {
						return ! in_array( $category, $exclude_categories, true );
					},
					ARRAY_FILTER_USE_KEY
				);
			}

			return $categories;
		}

		/**
		 * Get selected articles.
		 *
		 * @since 4.4.0
		 *
		 * @param string        $category           Category key the returned articles are from.
		 * @param array<string> $exclude_categories Excluded category keys the returned articles are from.
		 *
		 * @return array<int, array<string, array<int, string>|string>>
		 */
		public static function get_articles( string $category = null, array $exclude_categories = array() ) : array {
			$articles = array(
				array(
					'type'       => 'youtube_video',
					'title'      => __( 'Welcome to LearnDash', 'learndash' ),
					'youtube_id' => 'hcSTaMhZi64',
					'category'   => 'overview_video',
				),
				array(
					'type'     => 'vimeo_video',
					'title'    => __( 'A Brief Overview of LearnDash', 'learndash' ),
					'vimeo_id' => '797750743',
					'category' => 'overview_article',
				),
				array(
					'type'     => 'url',
					'title'    => __( 'LearnDash 101', 'learndash' ),
					'url'      => 'https://academy.learndash.com/courses/learndash-101/',
					'category' => 'additional_resources',
				),
				array(
					'type'     => 'url',
					'title'    => __( 'WordPress 101', 'learndash' ),
					'url'      => 'https://academy.learndash.com/courses/wordpress-101/',
					'category' => 'additional_resources',
				),
				array(
					'type'     => 'helpscout_action',
					'title'    => __( 'LearnDash Documentation', 'learndash' ),
					'action'   => 'open_doc',
					'keyword'  => '',
					'category' => 'additional_resources',
				),
				array(
					'type'         => 'article',
					'title'        => __( 'Getting Started', 'learndash' ),
					'helpscout_id' => '62a0e4f0e1d2cf0eac00f2bb',
					'category'     => 'additional_resources',
				),
				array(
					'type'     => 'helpscout_action',
					'title'    => __( 'Contact Support', 'learndash' ),
					'action'   => 'open_chat',
					'keyword'  => '',
					'category' => 'additional_resources',
				),
				array(
					'type'     => 'vimeo_video',
					'title'    => __( 'Creating Courses with the Course Builder [Video]', 'learndash' ),
					'vimeo_id' => '798775119',
					'category' => 'build_courses',
				),
				array(
					'type'     => 'vimeo_video',
					'title'    => __( 'Adding Content with Lessons & Topics [Video]', 'learndash' ),
					'vimeo_id' => '798793610',
					'category' => 'build_courses',
				),
				array(
					'type'     => 'vimeo_video',
					'title'    => __( 'Creating Quizzes [Video]', 'learndash' ),
					'vimeo_id' => '799349718',
					'category' => 'build_courses',
				),
				array(
					'type'     => 'vimeo_video',
					'title'    => __( 'PayPal Settings [Video]', 'learndash' ),
					'vimeo_id' => '799333129',
					'category' => 'sell_courses',
				),
				array(
					'type'     => 'vimeo_video',
					'title'    => __( 'Stripe Integration [Video]', 'learndash' ),
					'vimeo_id' => '799333097',
					'category' => 'sell_courses',
				),
				array(
					'type'         => 'article',
					'title'        => __( 'WooCommerce Integration [Article]', 'learndash' ),
					'helpscout_id' => '6216b293aca5bb2b753c5c7f',
					'category'     => 'sell_courses',
				),
				array(
					'type'     => 'vimeo_video',
					'title'    => __( 'Course Access Settings [Video]', 'learndash' ),
					'vimeo_id' => '798788916',
					'category' => 'sell_courses',
				),
				array(
					'type'     => 'vimeo_video',
					'title'    => __( 'Setting Up User Registration [Video]', 'learndash' ),
					'vimeo_id' => '799330885',
					'category' => 'manage_students',
				),
				array(
					'type'         => 'article',
					'title'        => __( 'Adding a User Profile Page', 'learndash' ),
					'helpscout_id' => '6216c2961173d072c69fb37a',
					'category'     => 'manage_students',
				),
				array(
					'type'         => 'article',
					'title'        => __( 'LearnDash Login & Registration [Guide]', 'learndash' ),
					'helpscout_id' => '6217ffea1173d072c69fba4d',
					'category'     => 'manage_students',
				),
			);

			if ( ! empty( $category ) ) {
				$articles = array_values(
					array_filter(
						$articles,
						function ( $article ) use ( $category ) {
							return $article['category'] === $category;
						}
					)
				);
			}

			if ( ! empty( $exclude_categories ) ) {
				$articles = array_values(
					array_filter(
						$articles,
						function ( $article ) use ( $exclude_categories ) {
							return ! in_array( $article['category'], $exclude_categories, true );
						}
					)
				);
			}

			return $articles;
		}
	}
}

add_action(
	'learndash_settings_pages_init',
	function() {
		LearnDash_Settings_Page_Help::add_page_instance();
	}
);
