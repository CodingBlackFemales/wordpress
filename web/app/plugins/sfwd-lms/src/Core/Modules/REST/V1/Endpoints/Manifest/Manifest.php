<?php
/**
 * The OpenAPI manifest REST endpoints for use with Large Language Models. The endpoints
 * are broken up in order to be token efficient as we can't provide the entire
 * spec in one go.
 *
 * @since 5.0.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\V1\Endpoints\Manifest;

use InvalidArgumentException;
use LearnDash\Core\Modules\REST\V1\Contracts\Endpoint;
use LearnDash\Core\Modules\REST\V1\Manifest\Manifest_Generator;
use LearnDash\Core\Utilities\Base64_Url;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\Arrays\Arr;
use WP_Http;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

/**
 * The manifest REST endpoints.
 *
 * @since 5.0.0
 */
class Manifest extends Endpoint {
	/**
	 * The base route for this endpoint.
	 *
	 * @since TDB
	 *
	 * @var string
	 */
	protected string $base_route = '/manifest';

	/**
	 * The permission required to access this endpoint.
	 *
	 * @since TDB
	 *
	 * @var string
	 */
	protected string $permission_required = '';

	/**
	 * Whether the endpoint is experimental.
	 *
	 * @since 5.0.0
	 *
	 * @var bool
	 */
	protected bool $experimental = false;

	/**
	 * The manifest generator.
	 *
	 * @since 5.0.0
	 *
	 * @var Manifest_Generator
	 */
	private Manifest_Generator $manifest;

	/**
	 * The Base64 URL friendly utility.
	 *
	 * @var Base64_Url
	 */
	private Base64_Url $base64_url;

	/**
	 * The path parameter name.
	 *
	 * @since 5.0.0
	 *
	 * @var string
	 */
	private const PATH = 'path';

	/**
	 * The method parameter name.
	 *
	 * @since 5.0.0
	 *
	 * @var string
	 */
	private const METHOD = 'method';

	/**
	 * The component parameter name.
	 *
	 * @since 5.0.0
	 *
	 * @var string
	 */
	private const COMPONENT = 'component';

	/**
	 * Constructor.
	 *
	 * @param Manifest_Generator $manifest The manifest generator.
	 * @param Base64_Url         $base64_url The base64 URL utility.
	 */
	public function __construct(
		Manifest_Generator $manifest,
		Base64_Url $base64_url
	) {
		$this->manifest   = $manifest;
		$this->base64_url = $base64_url;
	}

	/**
	 * Returns the routes configuration for this endpoint.
	 *
	 * @since 5.0.0
	 *
	 * @return array<string,array{
	 *     methods: string,
	 *     callback: callable,
	 *     permission_callback: callable,
	 *     summary: string,
	 *     description: string,
	 * }>
	 */
	protected function get_routes(): array {
		return [
			'/'                                => [
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_manifest' ],
				'permission_callback' => [ $this, 'check_permission' ],
				'summary'             => __( 'Get the REST API manifest.', 'learndash' ),
				'description'         => __( 'Returns a list of available endpoints, their descriptions and a ref URL to get detailed schema.', 'learndash' ),
			],
			'/detail/(?P<method>get|post|put|patch|delete|options|head)/(?P<path>.+)' => [
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_detail' ],
				'permission_callback' => [ $this, 'check_permission' ],
				'summary'             => __( 'Get the schema details for an endpoint.', 'learndash' ),
				'description'         => __( 'Returns a partial OpenAPI specification in JSON for a specific endpoint.', 'learndash' ),
				'args'                => $this->get_detail_args(),
			],
			'/component/(?P<component>[\w-]+)' => [
				'methods'             => WP_REST_Server::READABLE,
				'callback'            => [ $this, 'get_component_schema' ],
				'permission_callback' => [ $this, 'check_permission' ],
				'summary'             => __( 'Get OpenAPI schema for a component.', 'learndash' ),
				'description'         => __( 'Returns the OpenAPI specification in JSON for a specific OpenAPI component.', 'learndash' ),
				'args'                => $this->get_component_args(),
			],
		];
	}

	/**
	 * Get the modified OpenAPI spec in a reduced manifest form.
	 *
	 * @return WP_REST_Response
	 */
	public function get_manifest(): WP_REST_Response {
		$manifest = $this->manifest->get()['manifest'];

		$paths = $manifest['paths'];

		foreach ( $paths as $path => $methods ) {
			// Skip unneeded paths.
			if ( str_contains( $path, 'openapi' ) ) {
				unset( $manifest['paths'][ $path ] );
				continue;
			}

			foreach ( $methods as $method => $data ) {
				$encoded_path = $this->base64_url->encode( $path );

				// Build a relative $ref URI where you can request the details for this path+method combo.
				$detail = sprintf(
					'/%s/manifest/detail/%s/%s',
					$this->get_namespace(),
					$method,
					$encoded_path
				);

				$manifest['paths'][ $path ][ $method ] = [
					'summary' => $data['summary'],
					'desc'    => $data['description'],
					'href'    => $detail,
				];
			}
		}

		return new WP_REST_Response( $manifest );
	}

	/**
	 * Get the schema for a specific HTTP method and path.
	 *
	 * @param WP_REST_Request $request The REST request.
	 *
	 * @phpstan-param WP_REST_Request<array<string,mixed>> $request
	 *
	 * @return WP_REST_Response
	 */
	public function get_detail( WP_REST_Request $request ): WP_REST_Response {
		try {
			$path = $this->base64_url->decode( Cast::to_string( $request->get_param( self::PATH ) ) );
		} catch ( InvalidArgumentException $e ) {
			return $this->error_response(
				__( 'Invalid path.', 'learndash' ),
				'manifest_detail_invalid_path',
				WP_Http::BAD_REQUEST
			);
		}

		$method = Cast::to_string( $request->get_param( self::METHOD ) );
		$paths  = $this->manifest->get()['manifest']['paths'];
		$found  = $paths[ $path ][ $method ] ?? false;

		if ( ! is_array( $found ) ) {
			return $this->error_response(
				__( 'The manifest detail was not found.', 'learndash' ),
				'manifest_detail_not_found',
				WP_Http::NOT_FOUND,
				[
					'method' => esc_html( $method ),
					'path'   => esc_html( $path ),
				]
			);
		}

		// Remove properties we don't need.
		$found = Arr::except( $found, [ 'summary', 'description', 'servers', 'tags', 'security' ] );

		return new WP_REST_Response( $found );
	}

	/**
	 * Get the schema of a specific component.
	 *
	 * @param WP_REST_Request $request The request.
	 *
	 * @phpstan-param WP_REST_Request<array<string, mixed>> $request
	 *
	 * @return WP_REST_Response
	 */
	public function get_component_schema( WP_REST_Request $request ): WP_REST_Response {
		$component = rawurldecode( Cast::to_string( $request->get_param( self::COMPONENT ) ) );

		$manifest   = $this->manifest->get();
		$components = $manifest['components'];

		$found = $components['schemas'][ $component ] ?? false;

		if ( ! is_array( $found ) ) {
			return $this->error_response(
				__( 'The component schema was not found.', 'learndash' ),
				'component_schema_not_found',
				WP_Http::NOT_FOUND,
				[
					self::COMPONENT => esc_html( $component ),
				]
			);
		}

		return new WP_REST_Response( $found );
	}

	/**
	 * Returns the endpoint arguments.
	 *
	 * @since 5.0.0
	 *
	 * @return array{}
	 */
	protected function get_endpoint_args(): array {
		return [];
	}

	/**
	 * Returns the schema for request parameters.
	 *
	 * @since 5.0.0
	 *
	 * @param string $path   The path of the route.
	 * @param string $method The HTTP method.
	 *
	 * @return array{}
	 */
	public function get_request_schema( string $path, string $method ): array {
		return [];
	}

	/**
	 * Returns the schema for response data.
	 *
	 * @since 5.0.0
	 *
	 * @param string $path   The path of the route.
	 * @param string $method The HTTP method.
	 *
	 * @return array{}
	 */
	public function get_response_schema( string $path, string $method ): array {
		return [];
	}

	/**
	 * Get the detail endpoint request arguments.
	 *
	 * @return array<string, array<string, array<int, string>|string|true>>
	 */
	private function get_detail_args(): array {
		return [
			self::METHOD => [
				'type'        => 'string',
				'in'          => 'path',
				'description' => esc_html__( 'The type of HTTP method.', 'learndash' ),
				'enum'        => [
					'get',
					'post',
					'put',
					'patch',
					'delete',
					'options',
					'head',
				],
				'required'    => true,
			],
			self::PATH   => [
				'type'        => 'string',
				'in'          => 'path',
				'description' => esc_html__( 'The URL safe base64 encoded path relative to /wp-json to get schema details for.', 'learndash' ),
				'required'    => true,
			],
		];
	}

	/**
	 * Get the component endpoint request arguments.
	 *
	 * @return array<string, array<string, string|true>>
	 */
	private function get_component_args(): array {
		return [
			self::COMPONENT => [
				'type'        => 'string',
				'in'          => 'path',
				'description' => esc_html__( 'The component to fetch the schema for.', 'learndash' ),
				'required'    => true,
			],
		];
	}
}
