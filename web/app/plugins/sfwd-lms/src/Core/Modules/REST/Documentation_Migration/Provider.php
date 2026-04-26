<?php
/**
 * Provider for initializing documentation for endpoints that are not part of the learndash/v1 REST API.
 *
 * @since 4.25.2
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration;

use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Provider for initializing documentation for endpoints that are not part of the learndash/v1 REST API.
 *
 * @since 4.25.2
 */
class Provider extends ServiceProvider {
	/**
	 * Registers the service provider bindings.
	 *
	 * @since 4.25.2
	 *
	 * @return void
	 */
	public function register(): void {
		$this->container->register( OpenAPI\Provider::class );
	}
}
