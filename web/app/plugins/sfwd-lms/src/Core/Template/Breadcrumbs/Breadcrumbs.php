<?php
/**
 * LearnDash Breadcrumbs collection class.
 *
 * @since 4.6.0
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

namespace LearnDash\Core\Template\Breadcrumbs;

use LearnDash\Core\Collection;

/**
 * The Breadcrumbs collection.
 *
 * @since 4.6.0
 *
 * @extends Collection<Breadcrumb, string>
 */
class Breadcrumbs extends Collection {
	/**
	 * Constructor.
	 *
	 * @since 4.6.0
	 *
	 * @param array<string, string>[]|Breadcrumb[] $breadcrumbs Array of breadcrumbs.
	 */
	public function __construct( array $breadcrumbs = [] ) {
		parent::__construct();

		$this->parse_breadcrumbs( $breadcrumbs );
	}

	/**
	 * Adds a breadcrumb to the collection.
	 *
	 * @since 4.6.0
	 *
	 * @param Breadcrumb $breadcrumb Breadcrumb to add.
	 *
	 * @return Breadcrumb
	 */
	public function add( Breadcrumb $breadcrumb ): Breadcrumb {
		return parent::set( $breadcrumb->get_id(), $breadcrumb );
	}

	/**
	 * Updates the is_last property of the breadcrumbs.
	 *
	 * @since 4.6.0
	 *
	 * @return self
	 */
	public function update_is_last(): self {
		foreach ( $this->items as $breadcrumb ) {
			$breadcrumb->set_is_last( false );
		}

		$this->fast_forward();

		$this->items[ $this->key() ]->set_is_last();

		$this->rewind();

		return $this;
	}

	/**
	 * Parses an array into an array of Breadcrumb objects and sets.
	 *
	 * @since 4.6.0
	 *
	 * @param array<string, string>[]|Breadcrumb[] $breadcrumbs The breadcrumbs to parse.
	 *
	 * @return void
	 */
	protected function parse_breadcrumbs( array $breadcrumbs ): void {
		if ( empty( $breadcrumbs ) ) {
			return;
		}

		foreach ( $breadcrumbs as $breadcrumb ) {
			$this->add( Breadcrumb::parse( $breadcrumb ) );
		}
	}
}
