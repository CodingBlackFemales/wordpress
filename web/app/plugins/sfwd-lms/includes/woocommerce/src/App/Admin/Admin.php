<?php
/**
 * Admin controller class file.
 *
 * @since 5.2.0
 *
 * @package LearnDash\WooCommerce
 */

namespace LearnDash\WooCommerce\Admin;

use LearnDash\Core\Modules\AJAX\Notices\Dismisser;
use LearnDash\Core\Template\Template;

/**
 * Admin controller class.
 *
 * @since 5.2.0
 */
class Admin {
	/**
	 * Register admin template paths used in the plugin.
	 *
	 * @since 5.2.0
	 *
	 * @param array{theme:string[],templates:string[]} $paths     Template paths.
	 * @param string                                   $file_name Template file name.
	 *
	 * @return array{theme:string[],templates:string[]} Returned paths.
	 */
	public function register_admin_template_paths( $paths, string $file_name ) {
		$paths['templates'][] = LEARNDASH_WOOCOMMERCE_ADMIN_VIEWS_PATH . $file_name;

		return $paths;
	}

	/**
	 * Register submenu.
	 *
	 * @since 5.2.0
	 *
	 * @param array<string, array<string, string>> $submenu Submenu array.
	 *
	 * @return array<string, array<string, string>> Submenu array.
	 */
	public function register_submenu( $submenu ) {
		$submenu['woocommerce'] = [
			'name'  => 'WooCommerce',
			'cap'   => LEARNDASH_ADMIN_CAPABILITY_CHECK,
			'link'  => 'admin.php?page=learndash-woocommerce-settings',
			'class' => 'submenu-learndash-woocommerce-settings',
		];

		return $submenu;
	}

	/**
	 * Shows admin notice if WooCommerce guest checkout is enabled.
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	public function show_guest_checkout_setting_enabled_notice(): void {
		$notice_id = 'learndash-woocommerce-guest-checkout-enabled';
		$enabled   = get_option( 'woocommerce_enable_guest_checkout', 'no' );

		if (
			$enabled === 'no'
			|| Dismisser::is_dismissed( $notice_id )
		) {
			return;
		}

		$nonce               = learndash_create_nonce( Dismisser::$action );
		$dismisser_classname = Dismisser::$classname;

		$setting_page_url = add_query_arg(
			[
				'page' => 'wc-settings',
				'tab'  => 'account',
			],
			admin_url( 'admin.php' )
		);

		Template::show_admin_template(
			'notices/warnings/guest-checkout-enabled',
			[
				'notice_id'           => $notice_id,
				'nonce'               => $nonce,
				'dismisser_classname' => $dismisser_classname,
				'setting_page_url'    => $setting_page_url,
				'course_label'        => learndash_get_custom_label_lower( 'course' ),
				'group_label'         => learndash_get_custom_label_lower( 'group' ),
			]
		);
	}
}
