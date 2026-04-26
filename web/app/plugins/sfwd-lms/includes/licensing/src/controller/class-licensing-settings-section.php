<?php
/**
 * Licensing Settings Section.
 *
 * @since 4.18.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Hub\Controller;

use LearnDash\Hub\Traits\License;
use LearnDash\Hub\Traits\Permission;
use LearnDash_Settings_Section;
use LearnDash_Settings_Page;
use LearnDash_Settings_Page_Advanced;
use ReflectionObject;

defined( 'ABSPATH' ) || exit;

if ( ( class_exists( 'LearnDash_Settings_Section' ) ) ) {
	/**
	 * Add the visibility functionality.
	 */
	class Licensing_Settings_Section extends LearnDash_Settings_Section {
		use Permission;
		use License;

		/**
		 * The absolute path to view folder.
		 *
		 * @var string
		 */
		protected $view_path = '';

		/**
		 * Protected constructor for class
		 *
		 * @since 4.18.0
		 */
		public function __construct() {
			$this->settings_page_id = 'learndash_lms_advanced';

			// Used within the Settings API to uniquely identify this section.
			$this->settings_section_key = 'setting_lms_licensing';

			// Section label/header.
			$this->settings_section_label   = esc_html__( 'License Visibility', 'learndash' );
			$this->settings_fields_callback = array( $this, 'display' );

			$this->view_path = dirname( __DIR__ ) . DIRECTORY_SEPARATOR . 'view' . DIRECTORY_SEPARATOR;

			parent::__construct();
		}

		/**
		 * Render the root element for React JS.
		 *
		 * @return void
		 */
		public function display(): void {
			if ( $this->is_signed_on() && $this->is_user_allowed() ) {
				?>
			<div id="app" class="learndash-hub">
			</div>
				<?php
			} else {
				require_once $this->view_path . 'access_denied.php';
			}
		}

		/**
		 * Enqueues scripts.
		 *
		 * @since 4.18.0
		 * @deprecated 4.18.0
		 *
		 * @return void
		 */
		public function enqueue_scripts() {
			_deprecated_function( __METHOD__, '4.18.0', 'LearnDash\Core\Modules\Licensing\Assets::enqueue_assets' );

			$screen = get_current_screen();
			if ( is_object( $screen ) && 'admin_page_learndash_lms_advanced' === $screen->id
				&& isset( $_GET['section-advanced'] ) && 'setting_lms_licensing' === $_GET['section-advanced']
			) {
				wp_enqueue_style( 'learndash-hub' );

				if ( $this->is_signed_on() && $this->is_user_allowed() ) {
					wp_localize_script(
						'learndash-hub-settings',
						'Hub',
						$this->make_data()
					);
					wp_enqueue_script( 'learndash-hub-settings' );

					wp_enqueue_style(
						'leanrdash-hub-select2', // cspell:disable-line .
						'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css',
						array(),
						HUB_VERSION
					);
					wp_enqueue_script(
						'learndash-hub-select2',
						'https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js',
						array(
							'jquery',
						),
						HUB_VERSION
					);
				}
			}
		}

		/**
		 * @return array
		 */
		public function make_data(): array {
			return array(
				'nonces' => array(
					'search_admins'      => wp_create_nonce( 'ld_hub_search_admins' ),
					'update_permissions' => wp_create_nonce( 'ld_hub_update_permissions' ),
					'reset_permissions'  => wp_create_nonce( 'ld_hub_reset_permissions' ),
				),
				'list'   => $this->get_users_list(),
			);
		}

		/**
		 * Get the users permissions list, for frontend display
		 *
		 * @return array
		 */
		private function get_users_list() {
			$lists = $this->get_allowed_users();

			$data = array();
			// need to fetch the users' data.
			foreach ( $lists as $user_id => $permissions ) {
				$user = get_user_by( 'id', $user_id );
				if ( is_object( $user ) ) {
					$data[] = array(
						'id'      => $user_id,
						'name'    => $user->display_name,
						'email'   => $user->user_email,
						'avatar'  => get_avatar_url( $user->ID ),
						'is_self' => get_current_user_id() === $user_id,
					);
				}
			}

			return $data;
		}
	}


	add_action(
		'learndash_settings_sections_init',
		function () {
			Licensing_Settings_Section::add_section_instance();
		}
	);

	add_action(
		'learndash_settings_page_init',
		function ( string $settings_page_id ) {
			if (
				'learndash_lms_advanced' === $settings_page_id &&
				isset( $_GET['section-advanced'] ) &&
				'setting_lms_licensing' === $_GET['section-advanced'] ) {
				// by this time, the learndash_lms_advanced should be initialized.
				$instance = LearnDash_Settings_Page::get_page_instance( LearnDash_Settings_Page_Advanced::class );
				if ( $instance instanceof LearnDash_Settings_Page_Advanced ) {
					$ref_object                = new ReflectionObject( $instance );
					$show_submit_meta_property = $ref_object->getProperty( 'show_submit_meta' );
					$show_submit_meta_property->setAccessible( true );
					$show_submit_meta_property->setValue( $instance, false );

					$setting_column_property = $ref_object->getProperty( 'settings_columns' );
					$setting_column_property->setAccessible( true );
					$setting_column_property->setValue( $instance, 1 );
				}
			}
		},
		20
	);
}
