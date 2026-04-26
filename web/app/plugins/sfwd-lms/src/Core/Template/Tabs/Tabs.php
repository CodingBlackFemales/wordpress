<?php
/**
 * LearnDash Tabs collection class.
 *
 * @since 4.6.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Template\Tabs;

use LearnDash\Core\Collections\Collection;

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
		 * @since 4.24.0
		 *
		 * @param array<string, Tab> $tabs   The tabs.
		 * @param Tabs               $object The Tabs iterator object.
		 *
		 * @return array<string, Tab>
		 */
		$this->items = (array) apply_filters( 'learndash_template_tabs_sorted', $this->items, $this );

		foreach ( $this->items as $tab ) {
			if ( ! $tab instanceof Tab ) {
				continue;
			}

			$tab->set_is_first( false );
		}

		if (
			isset( $this->items[ $this->key() ] )
			&& $this->items[ $this->key() ] instanceof Tab
		) {
			$this->items[ $this->key() ]->set_is_first();
		}

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
