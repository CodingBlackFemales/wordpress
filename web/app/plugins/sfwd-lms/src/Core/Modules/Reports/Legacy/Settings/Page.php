<?php
/**
 * Reports Legacy Settings Page.
 *
 * @since 4.17.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Reports\Legacy\Settings;

use LearnDash_Settings_Page;
use Learndash_Admin_Settings_Data_Reports;
use LearnDash\Core\Utilities\Cast;
use LearnDash\Core\App;
use LearnDash\Core\Template\Template;

/**
 * Reports Legacy Settings Page.
 *
 * @since 4.17.0
 */
class Page extends LearnDash_Settings_Page {
	/**
	 * Public constructor for class.
	 *
	 * @since 4.17.0
	 */
	public function __construct() {
		$this->parent_menu_page_url = 'admin.php?page=learndash-lms-reports';

		$this->menu_page_capability        = LEARNDASH_ADMIN_CAPABILITY_CHECK;
		$this->settings_page_id            = Cast::to_string(
			App::getVar( 'learndash_settings_reports_page_id' )
		);
		$this->settings_page_title         = __( 'Reports', 'learndash' );
		$this->settings_tab_title          = __( 'Reports', 'learndash' );
		$this->settings_tab_priority       = 0;
		$this->show_quick_links_meta       = false;
		$this->settings_columns            = 1;
		$this->show_submit_meta            = false;
		$this->settings_menu_item_priority = 11;

		$this->init_header_buttons();

		parent::__construct();
	}

	/**
	 * Loads necessary CSS/JS when loading this Settings Page.
	 *
	 * @since 4.17.0
	 *
	 * @return void
	 */
	public function load_settings_page() {
		global $learndash_assets_loaded;

		// This script controls the Export User Course Data and Export User Quiz Data buttons.
		wp_enqueue_script(
			'learndash-admin-settings-data-reports-script',
			LEARNDASH_LMS_PLUGIN_URL . 'assets/js/learndash-admin-settings-data-reports' . learndash_min_asset() . '.js',
			array( 'jquery' ),
			LEARNDASH_SCRIPT_VERSION_TOKEN,
			true
		);
		$learndash_assets_loaded['scripts']['learndash-admin-settings-data-reports-script'] = __FUNCTION__;

		parent::load_settings_page();
	}

	/**
	 * Sets the buttons shown within the LearnDash Global Header.
	 *
	 * @since 4.17.0
	 *
	 * @return void
	 */
	public function init_header_buttons(): void {
		$legacy_reports = new Learndash_Admin_Settings_Data_Reports();

		if ( empty( $legacy_reports->get_report_actions() ) ) {
			$legacy_reports->init_report_actions();
		}

		$actions = $legacy_reports->get_report_actions();

		foreach ( $actions as $action ) {
			if (
				! is_array( $action )
				|| empty( $action['slug'] )
			) {
				continue;
			}

			$text = ! empty( $action['text'] ) ? $action['text'] : $action['slug'];

			$this->buttons[] = [
				'text'      => wp_strip_all_tags( $text ),
				'class'     => 'learndash-data-reports-button',
				'icon'      => false,
				'collapsed' => false,
				'data'      => [
					'nonce' => wp_create_nonce( 'learndash-data-reports-' . esc_attr( $action['slug'] ) . '-' . get_current_user_id() ),
					'slug'  => esc_attr( $action['slug'] ),
				],
			];
		}
	}

	/**
	 * Function to handle showing of Settings page. This is the main function for all visible output. Extending classes can implement its own function.
	 *
	 * @since 4.17.0
	 *
	 * @return void
	 */
	public function show_settings_page() {
		/**
		 * Fires before settings page content.
		 *
		 * @since 3.0.0
		 */
		do_action( 'learndash_settings_page_before_content' );

		Template::show_template(
			'components/icons/refresh',
			[
				'classes' => [
					'ld-svgicon__refresh--reports',
					'hidden',
				],
				'label'   => __( 'Loading...', 'learndash' ),
			]
		);

		Template::show_template(
			'components/icons/check',
			[
				'classes'        => [
					'ld-svgicon__check--reports',
					'hidden',
				],
				'is_aria_hidden' => true,
			]
		);

		parent::show_settings_page();
	}

	/**
	 * Sets the LearnDash header data for this page.
	 *
	 * @since 4.23.1
	 *
	 * @param array<string, mixed> $header_data Header data.
	 *
	 * @return array<string, mixed> Header data.
	 */
	public function set_header_data( array $header_data ): array {
		$current_screen = get_current_screen();

		if (
			! $current_screen
			|| $current_screen->id !== $this->settings_screen_id
		) {
			return $header_data;
		}

		if (
			isset( $header_data['post_data'] )
			&& is_array( $header_data['post_data'] )
		) {
			$header_data['post_data']['builder_post_title'] = esc_html( $this->settings_page_title );
		}
		$this->set_header_buttons( $header_data );

		return $header_data;
	}
}
