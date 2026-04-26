<?php
/**
 * Backups view class.
 *
 * @since 4.14.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Template\Admin_Views\Settings\Advanced;

use LearnDash\Core\Template\Admin_Views\View;

/**
 * Backups view class.
 *
 * @since 4.14.0
 */
class Backups extends View {
	/**
	 * Constructor.
	 *
	 * @since 4.14.0
	 */
	public function __construct() {
		$enabled    = is_plugin_active( 'backupbuddy/backupbuddy.php' );
		$button_url = $enabled
			? admin_url( 'admin.php?page=pb_backupbuddy_settings' )
			: 'https://solidwp.com/learndash-backups?utm_source=learndash&utm_medium=in-product&utm_campaign=learndash-in-product-cross-sell';
		$view_slug  = $enabled
			? 'settings/advanced/backups/enabled'
			: 'settings/advanced/backups/disabled';

		parent::__construct(
			$view_slug,
			[
				'images_dir' => LEARNDASH_LMS_PLUGIN_URL . 'src/assets/dist/images/settings/advanced/backups/',
				'button_url' => $button_url,
			]
		);
	}
}
