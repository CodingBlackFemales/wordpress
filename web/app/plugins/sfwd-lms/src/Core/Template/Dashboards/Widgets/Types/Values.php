<?php
/**
 * Values widget.
 *
 * @since 4.9.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Template\Dashboards\Widgets\Types;

use LearnDash\Core\Template\Dashboards\Widgets\Traits\Auto_View_Name;
use LearnDash\Core\Template\Dashboards\Widgets\Types\DTO\Values_Item;
use LearnDash\Core\Template\Dashboards\Widgets\Widget;

/**
 * Values widget.
 *
 * @since 4.9.0
 */
abstract class Values extends Widget {
	use Auto_View_Name;

	/**
	 * Items.
	 *
	 * @since 4.9.0
	 *
	 * @var Values_Item[]
	 */
	protected $items = [];

	/**
	 * Returns a label.
	 *
	 * @since 4.9.0
	 *
	 * @return Values_Item[]
	 */
	public function get_items(): array {
		return $this->items;
	}

	/**
	 * Sets items.
	 *
	 * @since 4.9.0
	 *
	 * @param Values_Item[] $items Items.
	 *
	 * @return void
	 */
	public function set_items( array $items ): void {
		$this->items = $items;
	}
}
