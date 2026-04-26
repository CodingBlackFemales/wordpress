<?php
/**
 * LearnDash Admin Header Question service class.
 *
 * @since 4.23.1
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\Admin\Header;

use LDLMS_Post_Types;

/**
 * Service class for Admin Header Question additions.
 *
 * @since 4.23.1
 */
class Question {
	/**
	 * Add header buttons to the question admin page.
	 *
	 * @since 4.23.1
	 * @since 4.23.2 Removed the logic as it is no longer need.
	 *
	 * @param array<string,mixed> $buttons Array of header buttons.
	 *
	 * @return array<int|string,mixed> Modified array of header buttons.
	 */
	public function add_header_buttons( $buttons = [] ) {
		return $buttons;
	}
}
