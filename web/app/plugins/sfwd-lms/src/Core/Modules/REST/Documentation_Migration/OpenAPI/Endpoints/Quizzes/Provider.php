<?php
/**
 * Quizzes OpenAPI Documentation Provider.
 *
 * @since 5.0.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Endpoints\Quizzes;

use LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Endpoints;
use StellarWP\Learndash\lucatume\DI52\ServiceProvider;

/**
 * Provider for initializing OpenAPI documentation for quizzes endpoints.
 *
 * @since 5.0.0
 */
class Provider extends ServiceProvider {
	/**
	 * Registers the service provider bindings.
	 *
	 * @since 5.0.0
	 *
	 * @return void
	 */
	public function register(): void {
		$this->hooks();
	}

	/**
	 * Adds the endpoints to the Open API documentation.
	 *
	 * @since 5.0.0
	 *
	 * @param string[] $endpoints Class names of endpoints.
	 *
	 * @return string[]
	 */
	public function add_endpoints( array $endpoints ): array {
		return array_merge(
			$endpoints,
			[
				Endpoints\Quizzes\Quiz_Statistics_Questions::class,
				Endpoints\Quizzes\Quiz_Statistics::class,
				Endpoints\Quizzes\Quiz::class,
			]
		);
	}

	/**
	 * Hooks wrapper.
	 *
	 * @since 5.0.0
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
