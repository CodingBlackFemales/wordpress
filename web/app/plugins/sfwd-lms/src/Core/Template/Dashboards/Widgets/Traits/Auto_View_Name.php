<?php
/**
 * A trait that adds a view name to a widget automatically.
 *
 * @since 4.9.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Template\Dashboards\Widgets\Traits;

use ReflectionClass;

/**
 * A trait that adds a view name to a widget automatically.
 *
 * @since 4.9.0
 */
trait Auto_View_Name {
	/**
	 * Returns a widget name.
	 *
	 * @since 4.9.0
	 *
	 * @return string
	 */
	protected function get_view_name(): string {
		$short_class_name = ( new ReflectionClass( __CLASS__ ) )->getShortName();

		return mb_strtolower(
			str_replace( '_', '-', $short_class_name )
		);
	}
}
