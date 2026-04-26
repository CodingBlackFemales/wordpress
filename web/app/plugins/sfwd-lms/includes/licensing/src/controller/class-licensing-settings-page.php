<?php
/**
 * Licensing Settings Page.
 *
 * @since 4.18.0
 *
 * @package LearnDash\Core
 */

declare( strict_types=1 );

namespace LearnDash\Hub\Controller;

use LearnDash\Hub\Traits\License;
use LearnDash\Hub\Traits\Permission;
use LearnDash_Settings_Page;

defined( 'ABSPATH' ) || exit;

if ( ( class_exists( 'LearnDash_Settings_Page' ) ) ) {
	/**
	 * Class LearnDash Settings Page License.
	 *
	 * @since 4.18.0
	 */
	class Licensing_Settings extends LearnDash_Settings_Page {
		use Permission;
		use License;

		/**
		 * The absolute path to view folder.
		 *
		 * @var string
		 */
		protected $view_path = '';

		/**
		 * Public constructor for class
		 *
		 * @since 4.18.0
		 */
		public function __construct() {
			$this->parent_menu_page_url  = 'admin.php?page=learndash_lms_settings';
			$this->menu_page_capability  = LEARNDASH_ADMIN_CAPABILITY_CHECK;
			$this->settings_page_id      = 'learndash_hub_licensing';
			$this->settings_page_title   = esc_html__( 'LMS License', 'learndash' );
			$this->settings_tab_title    = esc_html__( 'LMS License', 'learndash' );
			$this->show_submit_meta      = false;
			$this->show_quick_links_meta = false;

			$this->view_path = dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR;

			parent::__construct();
		}

		/**
		 * Enqueue scripts.
		 *
		 * @since 4.18.0
		 * @deprecated 4.18.0
		 *
		 * @return void
		 */
		public function enqueue_scripts() {
			_deprecated_function( __METHOD__, '4.18.0', 'LearnDash\Core\Modules\Licensing\Assets::enqueue_assets' );

			$page = $_GET['page'] ?? '';

			if ( 'learndash_hub_licensing' !== $page ) {
				return;
			}

			if ( $this->is_signed_on() && $this->is_user_allowed() ) {
				wp_localize_script(
					'learndash-hub-licensing',
					'Hub',
					array(
						'nonces'      => array(
							'sign_out' => wp_create_nonce( 'ld_hub_sign_out' ),
						),
						'rootUrl'     => admin_url( '/admin.php?page=learndash_hub_licensing' ),
						'email'       => $this->get_hub_email(),
						'license_key' => $this->get_license_key(),
					)
				);

				wp_enqueue_script( 'learndash-hub-licensing' );
			}

			wp_enqueue_style( 'learndash-hub' );
		}

		/**
		 * Render the page.
		 *
		 * @return void
		 */
		public function show_settings_page() {
			if ( ! $this->is_user_allowed() ) {
				require_once $this->view_path . 'access_denied.php';
				return;
			}

			if ( $this->is_signed_on() ) {
				require_once $this->view_path . 'root.php';
				return;
			}

			require_once $this->view_path . 'signin.php';
		}
	}

	add_action(
		'learndash_settings_pages_init',
		function () {
			Licensing_Settings::add_page_instance();
		}
	);
}
