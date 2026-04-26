<?php
/**
 * A widget with a simple value.
 *
 * @since 4.9.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Template\Dashboards\Widgets\Types;

use LearnDash\Core\Template\Dashboards\Widgets\Traits\Auto_View_Name;
use LearnDash\Core\Template\Dashboards\Widgets\Widget;

/**
 * Simple one value widget.
 *
 * @since 4.9.0
 *
 * @template TValue of string|int|float
 */
abstract class Value extends Widget {
	use Auto_View_Name;

	/**
	 * Label. Default is empty string.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	protected $label = '';

	/**
	 * Sub label. Default is empty string.
	 *
	 * @since 4.9.0
	 *
	 * @var string
	 */
	protected $sub_label = '';

	/**
	 * Value. Default is empty string.
	 *
	 * @since 4.9.0
	 *
	 * @var TValue
	 */
	protected $value = '';

	/**
	 * Returns a label.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public function get_label(): string {
		return $this->label;
	}

	/**
	 * Returns a sub label.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	public function get_sub_label(): string {
		return $this->sub_label;
	}

	/**
	 * Returns a value.
	 *
	 * @since 4.9.0
	 *
	 * @return TValue
	 */
	public function get_value() {
		return $this->value;
	}

	/**
	 * Sets a label.
	 *
	 * @since 4.9.0
	 *
	 * @param string $label Label.
	 *
	 * @return void
	 */
	public function set_label( string $label ): void {
		$this->label = $label;
	}

	/**
	 * Sets a sub label.
	 *
	 * @since 4.9.0
	 *
	 * @param string $label Sub label.
	 *
	 * @return void
	 */
	public function set_sub_label( string $label ): void {
		$this->sub_label = $label;
	}

	/**
	 * Sets a value.
	 *
	 * @since 4.9.0
	 *
	 * @param TValue $value Value.
	 *
	 * @return void
	 */
	public function set_value( $value ): void {
		$this->value = $value;
	}
}
