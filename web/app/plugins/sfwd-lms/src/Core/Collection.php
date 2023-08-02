<?php
/**
 * LearnDash Collections class.
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

namespace LearnDash\Core;

use ArrayAccess;
use Countable;
use Iterator;

/**
 * The class for LD collections.
 *
 * @since 4.6.0
 *
 * @template TValue of mixed
 * @template TKey of array-key
 *
 * @phpstan-consistent-constructor
 * @implements ArrayAccess<TKey, TValue>
 */
class Collection implements ArrayAccess, Countable, Iterator {
	/**
	 * Collection of items.
	 *
	 * @since 4.6.0
	 *
	 * @var array<TKey, TValue>
	 */
	protected $items = [];

	/**
	 * Constructor.
	 *
	 * @since 4.6.0
	 *
	 * @param array<TKey, TValue> $items Array of items.
	 *
	 * @return void
	 */
	public function __construct( array $items = [] ) {
		$this->items = $items;
	}

	/**
	 * Get all items in the collection.
	 *
	 * @since 4.6.0
	 *
	 * @return array<TKey, TValue>
	 */
	public function all(): array {
		return $this->items;
	}

	/**
	 * Get the count of the items in the collection.
	 *
	 * @since 4.6.0
	 *
	 * @return int
	 */
	public function count(): int {
		return count( $this->items );
	}

	/**
	 * Get the current object.
	 *
	 * @since 4.6.0
	 *
	 * @return TValue|false
	 */
	public function current() {
		return current( $this->items );
	}

	/**
	 * Get the key of the current item.
	 *
	 * @since 4.6.0
	 *
	 * @return int|string|null
	 */
	public function key() {
		return key( $this->items );
	}

	/**
	 * Advance to the next object in the collection.
	 *
	 * @since 4.6.0
	 *
	 * @return void
	 */
	public function next(): void {
		next( $this->items );
	}

	/**
	 * Returns whether the offset exists in the collection.
	 *
	 * @since 4.6.0
	 *
	 * @param TKey $offset Offset.
	 *
	 * @return bool
	 */
	public function offsetExists( $offset ): bool {
		return isset( $this->items[ $offset ] );
	}

	/**
	 * Get the item at the given offset.
	 *
	 * @since 4.6.0
	 *
	 * @param TKey $offset Offset.
	 *
	 * @return TValue
	 */
	public function offsetGet( $offset ) {
		return $this->items[ $offset ];
	}

	/**
	 * Sets the item at the given offset.
	 *
	 * @since 4.6.0
	 *
	 * @param TKey   $offset Offset.
	 * @param TValue $value  Value.
	 *
	 * @return void
	 */
	public function offsetSet( $offset, $value ): void {
		$this->items[ $offset ] = $value;
	}

	/**
	 * Unset the item at the given offset.
	 *
	 * @since 4.6.0
	 *
	 * @param TKey $offset Offset.
	 *
	 * @return void
	 */
	public function offsetUnset( $offset ): void {
		unset( $this->items[ $offset ] );
	}

	/**
	 * Reset the iterator pointer to the beginning of the collection.
	 *
	 * @since 4.6.0
	 *
	 * @return void
	 */
	public function rewind(): void {
		reset( $this->items );
	}

	/**
	 * Gets whether the current position is valid.
	 *
	 * @since 4.6.0
	 *
	 * @return bool
	 */
	public function valid(): bool {
		return isset( $this->items[ $this->key() ] );
	}

	/**
	 * Helper function for removing a resource from the collection.
	 *
	 * @since 4.6.0
	 *
	 * @param TKey $key Item key.
	 *
	 * @return void
	 */
	public function remove( $key ): void {
		$this->offsetUnset( $key );
	}

	/**
	 * Reset the iterator pointer to the end of the collection.
	 *
	 * @since 4.6.0
	 *
	 * @return void
	 */
	public function fast_forward(): void {
		end( $this->items );
	}

	/**
	 * Sets a resource in the collection.
	 *
	 * @since 4.6.0
	 *
	 * @param TKey   $key   Item key.
	 * @param TValue $value Value.
	 *
	 * @return TValue
	 */
	public function set( $key, $value ) {
		$this->offsetSet( $key, $value );

		return $this->offsetGet( $key );
	}

	/**
	 * Pushes one or more items onto the end of the collection.
	 *
	 * @param TValue ...$values Values.
	 *
	 * @return self
	 */
	public function push( ...$values ): self {
		foreach ( $values as $value ) {
			$this->items[] = $value;
		}

		return $this;
	}

	/**
	 * Merge the collection with the given items.
	 *
	 * @since 4.6.0
	 *
	 * @param Collection|array<TKey, TValue> $items Array of items.
	 *
	 * @return static
	 */
	public function merge( $items ): Collection {
		if ( $items instanceof self ) {
			$items = $items->all();
		}

		if ( ! is_array( $items ) ) {
			$items = (array) $items;
		}

		return new static(
			array_merge( $this->items, $items )
		);
	}

	/**
	 * Filters the collection using the given callback.
	 * If no callback is given, the collection will be filtered using `array_filter`.
	 *
	 * @param callable|null $callback Callback.
	 *
	 * @return static
	 */
	public function filter( callable $callback = null ): Collection {
		$items = $callback
			? array_filter( $this->items, $callback, ARRAY_FILTER_USE_BOTH )
			: array_filter( $this->items );

		return new static( $items );
	}
}
