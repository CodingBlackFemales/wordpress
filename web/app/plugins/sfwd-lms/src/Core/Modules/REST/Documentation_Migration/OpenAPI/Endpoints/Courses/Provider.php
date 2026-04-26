<?php
/**
 * Courses OpenAPI Documentation Provider.
 *
 * @since 4.25.2
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Endpoints\Courses;

use LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Endpoints;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Provider for initializing OpenAPI documentation for courses endpoints.
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
		$this->hooks();
	}

	/**
	 * Adds the endpoints to the Open API documentation.
	 *
	 * @since 4.25.2
	 *
	 * @param string[] $endpoints Class names of endpoints.
	 *
	 * @return string[]
	 */
	public function add_endpoints( array $endpoints ): array {
		return array_merge(
			$endpoints,
			[
				Endpoints\Courses\Course::class,
				Endpoints\Courses\Steps::class,
				Endpoints\Courses\Groups::class,
				Endpoints\Courses\Users::class,
				Endpoints\Courses\Prerequisites::class,
			]
		);
	}

	/**
	 * Hooks wrapper.
	 *
	 * @since 4.25.2
	 *
	 * @return void
	 */
	protected function hooks(): void {
		add_filter(
			'learndash_rest_endpoints',
			$this->container->callback(
				self::class,
				'add_endpoints'
			)
		);
	}
}
