<?php
/**
 * Handles plugin menu.
 *
 * @since 4.22.1
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Settings;

/**
 * Plugin menu class.
 *
 * @since 4.22.1
 */
class Menu {
	/**
	 * Updates the main menu label to the translated label.
	 * This allows us to use the translated label in the menu so it looks correct, but not affect the screen IDs.
	 *
	 * @since 4.22.1
	 *
	 * @return void
	 */
	public function update_main_menu_label(): void {
		global $menu;

		if ( ! is_array( $menu ) ) {
			return;
		}

		$learndash_menu_item_index = false;

		foreach ( $menu as $index => $item ) {
			if (
				isset( $item[2] ) &&
				$item[2] === 'learndash-lms'
			) {
				$learndash_menu_item_index = $index;
				break;
			}
		}

		if ( false === $learndash_menu_item_index ) {
			return;
		}

		// Index 0 is the menu item name.
		$menu[ $learndash_menu_item_index ][0] = __( 'LearnDash LMS', 'learndash' ); // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- This is WordPress core global.
	}
}
