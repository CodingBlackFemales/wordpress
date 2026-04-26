<?php
/**
 * Virtual Instructor settings page.
 *
 * @since 4.13.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\AI\Virtual_Instructor\Settings;

use LDLMS_Post_Types;
use LearnDash_Custom_Label;
use LearnDash_Settings_Page;

/**
 * Settings page.
 *
 * @since 4.13.0
 */
class Page extends LearnDash_Settings_Page {
	/**
	 * Page constructor.
	 *
	 * @since 4.13.0
	 */
	public function __construct() {
		$this->parent_menu_page_url  = 'edit.php?post_type=' . learndash_get_post_type_slug( LDLMS_Post_Types::VIRTUAL_INSTRUCTOR );
		$this->menu_page_capability  = LEARNDASH_ADMIN_CAPABILITY_CHECK;
		$this->settings_page_id      = 'virtual-instructors-settings';
		$this->settings_page_title   = sprintf(
			// translators: placeholder: Virtual Instructors.
			esc_html_x( '%s Settings', 'placeholder: virtual instructors label', 'learndash' ),
			LearnDash_Custom_Label::get_label( 'virtual_instructors' )
		);
		$this->settings_tab_title    = __( 'Settings', 'learndash' );
		$this->settings_tab_priority = 10;

		parent::__construct();
	}
}
