<?php
/**
 * LearnDash Collections class.
 *
 * @since 4.6.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Collections;

use ArrayAccess;
use Countable;
use Iterator;
use ReturnTypeWillChange;

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
	 * Determines if the collection is empty or not.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	public function is_empty(): bool {
		return empty( $this->items );
	}

	/**
	 * Determines if the collection is not empty.
	 *
	 * @since 4.9.0
	 *
	 * @return bool
	 */
	public function is_not_empty(): bool {
		return ! $this->is_empty();
	}

	/**
	 * Get the current object.
	 *
	 * @since 4.6.0
	 *
	 * @return TValue|false
	 */
	#[ReturnTypeWillChange]
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
	#[ReturnTypeWillChange]
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
	#[ReturnTypeWillChange]
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
	 * Pushes one or more items onto the end of the collection.
	 *
	 * @since 4.9.0
	 *
	 * @param TValue ...$values Values.
	 *
	 * @return static
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
	 * @since 4.9.0
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

	/**
	 * Creates a new collection instance with items.
	 *
	 * @since 4.9.0
	 *
	 * @param array<TKey, TValue> $items Array of items.
	 *
	 * @return static
	 */
	public static function make( array $items = [] ): self {
		return new static( $items );
	}

	/**
	 * Creates a new collection instance with no items.
	 *
	 * @since 4.9.0
	 *
	 * @return static
	 */
	public static function empty(): self {
		return new static( [] );
	}
}
