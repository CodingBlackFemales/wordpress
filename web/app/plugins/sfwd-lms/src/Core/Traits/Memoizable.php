<?php
/**
 * This trait provides an easy way to memoize object method results.
 *
 * To enable memoization for an object, you need to use this trait and call the `enable_memoization` method.
 * You can call the `clear_memoization_cache` method to clear the cache.
 *
 * How to use:
 *
 * Example without args:
 *
 * public function test() {
 *     return $this->memoize(
 *         function() {
 *             return rand( 1, 1000 );
 *          }
 *     );
 * }
 *
 * Result:
 *
 * $obj->test(); // 123
 * $obj->test(); // 123
 *
 * With args: (the args are be used as a cache key too)
 *
 * public function test( $arg ) {
 *     return $this->memoize(
 *         function() use ( $arg ) {
 *             return $arg . rand( 1, 1000 );
 *          }
 *     );
 * }
 *
 * Result:
 *
 * $obj->test( 'a' ); // a234
 * $obj->test( 'a' ); // a234
 * $obj->test( 'b' ); // b456
 * $obj->test( 'b' ); // b456
 * $obj->test( 'c' ); // c789
 *
 * The passed function will be called only once and the result will be cached.
 *
 * Limitations:
 *
 * It was tested with simple public methods only.
 * It does not support static methods.
 * It does not support closures.
 *
 * The reason of it is that for such functionality we need to use some kind of global cache (not per object).
 * Once we need to support static methods or closures, we can improve this trait.
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

namespace LearnDash\Core\Traits;

/**
 * A trait to memoize object method results.
 *
 * @since 4.6.0
 */
trait Memoizable {
	/**
	 * The memoization cache.
	 *
	 * @since 4.6.0
	 *
	 * @var array<string, mixed>
	 */
	private $memoized = array();

	/**
	 * An indicator of whether memoization is enabled. Default false.
	 *
	 * @since 4.6.0
	 *
	 * @var bool
	 */
	private $memoization_enabled = false;

	/**
	 * Enables the memoization cache.
	 *
	 * @since 4.6.0
	 *
	 * @return void
	 */
	public function enable_memoization(): void {
		$this->memoization_enabled = true;
	}

	/**
	 * Disables the memoization cache.
	 *
	 * @since 4.6.0
	 *
	 * @return void
	 */
	public function disable_memoization(): void {
		$this->memoization_enabled = false;
	}

	/**
	 * Returns whether the memoization cache is enabled.
	 *
	 * @since 4.6.0
	 *
	 * @return bool
	 */
	public function memoization_is_enabled(): bool {
		return $this->memoization_enabled;
	}

	/**
	 * Clears the memoization cache.
	 *
	 * @since 4.6.0
	 *
	 * @return void
	 */
	public function clear_memoization_cache(): void {
		$this->memoized = array();
	}

	/**
	 * Memoizes the result of a callback.
	 *
	 * @since 4.6.0
	 *
	 * @template CallbackReturnType The callback output value type.
	 *
	 * @param callable(): CallbackReturnType $callback The callback to memoize.
	 *
	 * @return CallbackReturnType The result of the callback or the memoized result.
	 */
	protected function memoize( callable $callback ) {
		$trace      = debug_backtrace( DEBUG_BACKTRACE_PROVIDE_OBJECT, 2 );
		$zero_stack = $trace[0];
		$trace      = $trace[1]; // 0 is the memoize method, 1 is the calling method.

		$args = $trace['args'] ?? array();

		if (
			! $this->memoization_enabled
			|| '{closure}' === $trace['function'] // Is closure.
			|| empty( $trace['class'] ) // Is not a method call.
			|| empty( $trace['type'] ) // Is not a method call.
			|| '::' === $trace['type'] // Is static call.
		) {
			return call_user_func( $callback, $args );
		}

		$normalized_arguments = array_map(
			function ( $argument ) {
				return is_object( $argument ) ? spl_object_hash( $argument ) : $argument;
			},
			$args
		);

		$callback_hash = md5(
			$trace['class'] . $trace['function'] . serialize( $normalized_arguments ) . ( $zero_stack['line'] ?? 0 )
		);

		if ( ! array_key_exists( $callback_hash, $this->memoized ) ) {
			$this->memoized[ $callback_hash ] = call_user_func( $callback, $args );
		}

		return $this->memoized[ $callback_hash ];
	}
}
