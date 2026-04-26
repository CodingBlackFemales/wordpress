<?php
/**
 * Money widget.
 *
 * @since 4.9.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Template\Dashboards\Widgets\Types;

/**
 * Money widget.
 *
 * @since 4.9.0
 *
 * @template TValue of string|int|float
 */
abstract class Money extends Value {
	/**
	 * Returns a value.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public function get_value(): string {
		return learndash_get_price_formatted( $this->value );
	}
}
