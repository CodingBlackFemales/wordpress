<?php
/**
 * Deprecated functions from LD 4.17.0.
 * The functions will be removed in a later version.
 *
 * @since 4.17.0
 *
 * @package LearnDash\Deprecated
 */

use LearnDash\Core\Utilities\Cast;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! function_exists( 'activate_learndash_propanel' ) ) {
	/**
	 * Activation logic.
	 *
	 * @since 4.17.0
	 * @deprecated 4.17.0 - This logic has been moved.
	 *
	 * @return void
	 */
	function activate_learndash_propanel() {
		_deprecated_function( __FUNCTION__, '4.17.0', 'LearnDash\Core\Modules\Reports\Capabilities::add()' );
	}
}

if ( ! function_exists( 'deactivate_learndash_propanel' ) ) {
	/**
	 * Deactivation logic.
	 *
	 * @since 4.17.0
	 * @deprecated 4.17.0 - This function never did anything.
	 *
	 * @return void
	 */
	function deactivate_learndash_propanel() {
		_deprecated_function( __FUNCTION__, '4.17.0' );
	}
}

if ( ! function_exists( 'learndash_propanel_admin_tabs' ) ) {
	/**
	 * Shows the old ProPanel license menu in the LD admin area.
	 *
	 * @since 4.17.0
	 * @deprecated 4.17.0. This function was used to show the old ProPanel license menu and it is no longer used.
	 *
	 * @param mixed $admin_tabs The admin tabs.
	 *
	 * @return void
	 */
	function learndash_propanel_admin_tabs( $admin_tabs ) {
		_deprecated_function( __FUNCTION__, '4.17.0' );
	}
}

if ( ! function_exists( 'learndash_propanel_learndash_admin_tabs_on_page' ) ) {
	/**
	 * Shows the old ProPanel license menu in the LD admin area.
	 *
	 * @since 4.17.0
	 * @deprecated 4.17.0. This function was used to show the old ProPanel license menu and it is no longer used.
	 *
	 * @param mixed $admin_tabs_on_page The admin tabs on page.
	 * @param mixed $admin_tabs         The admin tabs.
	 * @param mixed $current_page_id    The current page ID.
	 *
	 * @return void
	 */
	function learndash_propanel_learndash_admin_tabs_on_page( $admin_tabs_on_page, $admin_tabs, $current_page_id ) {
		_deprecated_function( __FUNCTION__, '4.17.0' );
	}
}

if ( ! function_exists( 'learndash_propanel_block_categories' ) ) {
	/**
	 * Registers a custom block category.
	 *
	 * @since 4.17.0
	 * @deprecated 4.17.0. This function was used in the ProPanel add-on for the deprecated `block_categories` hook. It is no longer needed, as we moved to the new hook `block_categories_all`.
	 *
	 * @param array         $block_categories Optional. An array of current block categories. Default empty array.
	 * @param WP_Post|false $post             Optional. The `WP_Post` instance of post being edited. Default false.
	 *
	 * @return array An array of block categories.
	 */
	function learndash_propanel_block_categories( $block_categories = array(), $post = false ) {
		_deprecated_function( __FUNCTION__, '4.17.0' );

		$ld_block_cat_found = false;

		foreach ( $block_categories as $block_cat ) {
			if ( ( isset( $block_cat['slug'] ) ) && ( 'ld-propanel-blocks' === $block_cat['slug'] ) ) {
				$ld_block_cat_found = true;
			}
		}

		if ( false === $ld_block_cat_found ) {
			$block_categories[] = array(
				'slug'  => 'ld-propanel-blocks',
				'title' => esc_html__( 'LearnDash LMS Reporting Blocks', 'learndash' ),
				'icon'  => false,
			);
		}

		// Always return $default_block_categories.
		return $block_categories;
	}
}
