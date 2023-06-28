<?php
/**
 * LearnDash Tabs collection class.
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

namespace LearnDash\Core\Template\Tabs;

use LearnDash\Core\Collection;

/**
 * The Tabs collection.
 *
 * @since 4.6.0
 *
 * @extends Collection<Tab, string>
 */
class Tabs extends Collection {
	/**
	 * Constructor.
	 *
	 * @since 4.6.0
	 *
	 * @param array<string, Tab>|array<int, array<string, mixed>> $tabs Array of tabs.
	 */
	public function __construct( array $tabs = [] ) {
		parent::__construct();

		$this->parse_tabs( $tabs );
	}

	/**
	 * Adds a tab to the collection.
	 *
	 * @since 4.6.0
	 *
	 * @param Tab $tab Tab to add.
	 *
	 * @return Tab
	 */
	public function add( Tab $tab ): Tab {
		return parent::set( $tab->get_id(), $tab );
	}

	/**
	 * Gets an ordered array of Tabs.
	 *
	 * Ordered by priority first, then alphabetically within that priority.
	 *
	 * @since 4.6.0
	 *
	 * @return self
	 */
	public function sort(): self {
		$ordered = [];
		foreach ( $this->items as $item ) {
			if ( ! isset( $ordered[ $item->get_order() ] ) ) {
				$ordered[ $item->get_order() ] = [];
			}

			$ordered[ $item->get_order() ][ $item->get_label() ] = $item;
		}

		// Sort by order.
		ksort( $ordered );

		// Sort alphabetically within the order.
		foreach ( $ordered as $key => $items ) {
			ksort( $items );

			$ordered[ $key ] = $items;
		}

		$this->items = [];

		foreach ( $ordered as $items ) {
			foreach ( $items as $item ) {
				$this->items[ $item->get_id() ] = $item;
			}
		}

		/**
		 * Filters the ordered tabs.
		 *
		 * @since 4.6.0
		 *
		 * @param array<string, Tab> $tabs   The tabs.
		 * @param Tabs               $object The Tabs iterator object.
		 *
		 * @ignore
		 */
		$this->items = (array) apply_filters( 'learndash_template_tabs_sorted', $this->items, $this );

		foreach ( $this->items as $tab ) {
			$tab->set_is_first( false );
		}

		$this->items[ $this->key() ]->set_is_first();

		return $this;
	}

	/**
	 * Gets a filtered array of Tabs.
	 *
	 * @since 4.6.0
	 *
	 * @return self
	 */
	public function filter_empty_content(): self {
		return $this->filter(
			function( Tab $tab ) {
				return ! empty( $tab->get_content() );
			}
		);
	}

	/**
	 * Parses an array into an array of Tab objects and sets.
	 *
	 * @since 4.6.0
	 *
	 * @param array<string, Tab>|array<int, array<string, mixed>> $tabs The tabs to parse.
	 *
	 * @return void
	 */
	protected function parse_tabs( array $tabs ): void {
		if ( empty( $tabs ) ) {
			return;
		}

		foreach ( $tabs as $tab ) {
			$this->add( Tab::parse( $tab ) );
		}
	}
}
