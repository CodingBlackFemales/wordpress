<?php
/**
 * LearnDash widgets collection class.
 *
 * @since 4.9.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Template\Dashboards\Widgets;

use InvalidArgumentException;
use LearnDash\Core\Collections\Collection;

/**
 * The collection of widgets.
 *
 * @since 4.9.0
 *
 * @extends Collection<Widget, int>
 */
class Widgets extends Collection {
	/**
	 * Constructor.
	 *
	 * @since 4.9.0
	 *
	 * @param Widget[] $widgets Array of widgets.
	 *
	 * @throws InvalidArgumentException If any of the widgets is not an instance of Widget.
	 */
	public function __construct( array $widgets = [] ) {
		parent::__construct();

		foreach ( $widgets as $widget ) {
			if ( ! $widget instanceof Widget ) {
				throw new InvalidArgumentException(
					sprintf( 'A widget must be a %1$s instance.', Widget::class )
				);
			}

			$this->push( $widget );
		}
	}
}
