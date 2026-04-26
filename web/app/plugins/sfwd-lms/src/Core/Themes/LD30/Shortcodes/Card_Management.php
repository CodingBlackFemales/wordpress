<?php
/**
 * Card management actions for shortcodes.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Themes\LD30\Shortcodes;

use LearnDash\Core\Template\Template;
use StellarWP\Learndash\StellarWP\SuperGlobals\SuperGlobals;
use LearnDash\Core\Utilities\Cast;

/**
 * Card management actions for shortcodes.
 *
 * @since 4.25.0
 */
class Card_Management {
	/**
	 * Handles the ajax request to load the card manager form.
	 *
	 * @since 4.25.0
	 *
	 * @return void
	 */
	public function handle_load_card_manager_form(): void {
		if (
			! wp_verify_nonce( Cast::to_string( SuperGlobals::get_var( 'nonce' ) ), 'learndash_ld30_shortcodes' )
			|| ! is_user_logged_in()
		) {
			wp_die( esc_html__( 'You are not authorized to add a new card.', 'learndash' ) );
		}

		Template::show_template( 'themes/ld30/shortcodes/add-card-form' );

		exit;
	}
}
