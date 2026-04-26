<?php
/**
 * Controller for the REST API.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\V1;

use LearnDash\Core\Modules\REST\V1\Contracts\Endpoint;
use StellarWP\Learndash\lucatume\DI52\Container;
use StellarWP\Learndash\StellarWP\Arrays\Arr;

/**
 * Controller for the REST API.
 *
 * @since 4.25.0
 */
class Controller {
	/**
	 * The registered endpoints.
	 *
	 * @since 4.25.0
	 *
	 * @var Endpoint[]
	 */
	private array $endpoints = [];

	/**
	 * The container
	 *
	 * @since 5.0.0
	 *
	 * @var Container
	 */
	private Container $container;

	/**
	 * Constructor.
	 *
	 * @since 5.0.0
	 *
	 * @param Container $container The container.
	 */
	public function __construct( Container $container ) {
		$this->container = $container;
	}

	/**
	 * Loads and instantiates all endpoint classes.
	 *
	 * @since 4.25.0
	 *
	 * @return Endpoint[]
	 */
	protected function load_endpoints(): array {
		/**
		 * Filters the list of REST endpoint classes to load.
		 *
		 * @since 4.25.0
		 *
		 * @param class-string<Endpoint>[] $endpoint_classes Array of endpoint class names.
		 *
		 * @return class-string<Endpoint>[]
		 */
		$endpoint_classes = apply_filters(
			'learndash_rest_endpoints',
			[
				Endpoints\Manifest\Manifest::class,
				Endpoints\Documentation::class,
				Endpoints\Profile\Remove_Card::class,
				Endpoints\Progress_Status\Collection::class,
				Endpoints\Progress_Status\Singular::class,
				Endpoints\Progress_Status\Nested::class,
			]
		);

		$endpoints = array_map(
			function ( $class_name ) {
				$endpoint = class_exists( $class_name )
					? $this->container->get( $class_name )
					: null;

				return $endpoint instanceof Endpoint ? $endpoint : null;
			},
			$endpoint_classes
		);

		// Filter out null values (classes that don't exist).
		return array_filter( $endpoints );
	}

	/**
	 * Registers all REST API routes.
	 *
	 * @since 4.25.0
	 *
	 * @return void
	 */
	public function register_routes(): void {
		foreach ( $this->get_endpoints() as $endpoint ) {
			$endpoint->register_routes();
		}
	}

	/**
	 * Returns all registered endpoints.
	 *
	 * @since 4.25.0
	 *
	 * @return Endpoint[]
	 */
	public function get_endpoints(): array {
		if ( empty( $this->endpoints ) ) {
			$this->endpoints = $this->load_endpoints();
		}

		return $this->endpoints;
	}

	/**
	 * Returns OpenAPI documentation for all endpoints.
	 *
	 * @since 4.25.0
	 * @since 5.0.0 Added $trim parameter.
	 *
	 * @param bool $trim Whether to trim unnecessary documentation. Default is true.
	 *
	 * @return array<string,mixed>
	 */
	public function get_openapi_documentation( bool $trim = true ): array {
		$documentation = OpenAPI::get_base_spec();

		foreach ( $this->get_endpoints() as $endpoint ) {
			if ( method_exists( $endpoint, 'get_openapi_schema' ) ) {
				$schema = $endpoint->get_openapi_schema( $trim );

				$documentation['paths'] = array_merge(
					Arr::wrap( $documentation['paths'] ),
					$schema
				);
			}
		}

		/**
		 * Filters the OpenAPI documentation.
		 *
		 * @since 4.25.0
		 * @since 5.0.0 Added $trim parameter.
		 *
		 * @param array<string,mixed> $documentation The OpenAPI documentation.
		 * @param bool               $trim           Whether trimming was requested. Default is true.
		 *
		 * @return array<string,mixed>|string
		 */
		return apply_filters(
			'learndash_rest_openapi_documentation',
			$documentation,
			$trim
		);
	}
}
