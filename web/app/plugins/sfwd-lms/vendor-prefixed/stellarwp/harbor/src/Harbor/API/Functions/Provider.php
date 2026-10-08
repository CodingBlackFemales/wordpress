<?php declare( strict_types=1 );

namespace StellarWP\Learndash\LiquidWeb\Harbor\API\Functions;

use StellarWP\Learndash\LiquidWeb\Harbor\Contracts\Abstract_Provider;
use StellarWP\Learndash\LiquidWeb\Harbor\Harbor;

/**
 * Registers global (non-namespaced) Harbor helper functions.
 *
 * @since 1.0.0
 */
final class Provider extends Abstract_Provider {

	/**
	 * @inheritDoc
	 */
	public function register(): void {
		require_once dirname( __DIR__, 2 ) . '/global-functions.php';
		Global_Function_Registry::register( Harbor::VERSION );
	}
}
