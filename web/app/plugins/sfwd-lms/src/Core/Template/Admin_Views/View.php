<?php
/**
 * A base class for all admin views.
 *
 * @since 4.9.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Template\Admin_Views;

use LearnDash\Core\Template\View as View_Base;

/**
 * A base class for all admin views.
 *
 * @since 4.9.0
 */
abstract class View extends View_Base {
	/**
	 * Constructor.
	 *
	 * @since 4.9.0
	 *
	 * @param string       $view_slug View slug.
	 * @param array<mixed> $context   Context.
	 */
	public function __construct( string $view_slug, array $context = [] ) {
		parent::__construct( $view_slug, $context, true );
	}
}
