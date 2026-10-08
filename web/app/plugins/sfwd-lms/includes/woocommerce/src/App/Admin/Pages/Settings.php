<?php
/**
 * Settings page class file.
 *
 * @since 5.2.0
 *
 * @package LearnDash\WooCommerce
 */

namespace LearnDash\WooCommerce\Admin\Pages;

use LearnDash_Settings_Page;

/**
 * Settings page class.
 *
 * @since 5.2.0
 */
class Settings extends LearnDash_Settings_Page {
	/**
	 * Constructor.
	 *
	 * @since 5.2.0
	 */
	public function __construct() {
		$this->parent_menu_page_url  = 'admin.php?page=learndash-woocommerce-settings';
		$this->menu_page_capability  = LEARNDASH_ADMIN_CAPABILITY_CHECK;
		$this->settings_page_id      = 'learndash-woocommerce-settings';
		$this->settings_page_title   = esc_html__( 'LearnDash LMS - WooCommerce Settings', 'learndash' );
		$this->settings_tab_title    = esc_html__( 'Settings', 'learndash' );
		$this->settings_tab_priority = 0;

		parent::__construct();
	}
}
