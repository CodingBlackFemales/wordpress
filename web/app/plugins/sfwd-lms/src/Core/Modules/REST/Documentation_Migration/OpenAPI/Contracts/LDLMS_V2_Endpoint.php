<?php
/**
 * Abstract OpenAPI Documentation ldlms/v2 Endpoint.
 *
 * Provides base functionality for generating OpenAPI documentation
 * for existing ldlms/v2 endpoints.
 *
 * @since 4.25.2
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\Documentation_Migration\OpenAPI\Contracts;

use LearnDash\Core\Modules\REST\V1\OpenAPI;
use WP_REST_Server;

/**
 * Abstract OpenAPI Documentation ldlms/v2 Endpoint.
 *
 * @since 4.25.2
 */
abstract class LDLMS_V2_Endpoint extends Endpoint {
	/**
	 * Returns the namespace for this endpoint.
	 *
	 * @since 4.25.2
	 *
	 * @return string
	 */
	public function get_namespace(): string {
		return LEARNDASH_REST_API_NAMESPACE . '/v2';
	}

	/**
	 * Returns the security schemes for this endpoint.
	 *
	 * @since 4.25.2
	 *
	 * @param string $path   The path of the route.
	 * @param string $method The HTTP method.
	 *
	 * @return array<int,array<string,string[]>>
	 */
	public function get_security_schemes( string $path, string $method ): array {
		return [
			[
				OpenAPI::$security_scheme_cookie => [],
				OpenAPI::$security_scheme_nonce  => [],
			],
		];
	}

	/**
	 * Returns the supported methods for this endpoint.
	 * This provides a default set of supported methods that matches most LDLMS v2 endpoints.
	 *
	 * @since 5.0.0
	 *
	 * @return array{
	 *     collection?: string[],
	 *     singular?: string[],
	 *     nested?: string[],
	 * }
	 */
	protected function get_supported_methods(): array {
		return [
			'collection' => [
				WP_REST_Server::READABLE,
				WP_REST_Server::CREATABLE,
			],
			'singular'   => [
				WP_REST_Server::READABLE,
				'PATCH',
				WP_REST_Server::DELETABLE,
			],
			'nested'     => [
				WP_REST_Server::READABLE,
				'PATCH',
				WP_REST_Server::DELETABLE,
			],
		];
	}
}
