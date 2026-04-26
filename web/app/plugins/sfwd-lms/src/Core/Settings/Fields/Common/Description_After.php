<?php
/**
 * Adds a Description to the bottom of each Field.
 *
 * @since 4.15.2
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Settings\Fields\Common;

use LearnDash\Core\Utilities\Cast;

/**
 * Class to add a Description to the bottom of each Field.
 *
 * @since 4.15.2
 */
class Description_After {
	/**
	 * Injects a Description at the bottom of the Field.
	 *
	 * @since 4.15.2
	 *
	 * @param string       $html       Field HTML.
	 * @param array<mixed> $field_args Field Args.
	 *
	 * @return string
	 */
	public function add( $html, array $field_args ) {
		if (
			empty( $field_args['description_after'] )
			|| empty( $field_args['type'] )
		) {
			return $html;
		}

		$html .= sprintf(
			'<p class="ld-%s-description">%s</p>',
			esc_attr( Cast::to_string( $field_args['type'] ) ),
			wp_kses_post( Cast::to_string( $field_args['description_after'] ) )
		);

		return $html;
	}
}
