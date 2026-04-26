<?php
/**
 * Value comparison widget.
 *
 * @since 4.9.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Template\Dashboards\Widgets\Types;

use LearnDash\Core\Template\Dashboards\Widgets\Traits\Auto_View_Name;
use LearnDash\Core\Utilities\Cast;

/**
 * Value comparison widget.
 *
 * @since 4.9.0
 */
abstract class Value_Comparison extends Value {
	use Auto_View_Name;

	/**
	 * Previous value. Default is 0.
	 *
	 * @since 4.9.0
	 *
	 * @var int
	 */
	protected $previous_value = 0;

	/**
	 * Returns a percentage difference between the current and the previous number.
	 *
	 * @since 4.9.0
	 *
	 * @return float
	 */
	public function get_percentage_difference(): float {
		if ( 0 === $this->get_previous_value() ) {
			return 0.;
		}

		$value = Cast::to_int( $this->get_value() );

		return round(
			( $value - $this->get_previous_value() ) / $this->get_previous_value() * 100,
			2
		);
	}

	/**
	 * Returns the previous value.
	 *
	 * @since 4.9.0
	 *
	 * @return int
	 */
	public function get_previous_value(): int {
		return $this->previous_value;
	}

	/**
	 * Sets the previous value.
	 *
	 * @since 4.9.0
	 *
	 * @param int $value The previous value.
	 *
	 * @return void
	 */
	public function set_previous_value( int $value ): void {
		$this->previous_value = $value;
	}
}
