<?php
/**
 * OpenAPI helper class for generating OpenAPI documentation.
 *
 * Provides helper methods for generating OpenAPI documentation schemas.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\V1;

/**
 * OpenAPI helper class for generating OpenAPI documentation.
 *
 * @since 4.25.0
 */
class OpenAPI {
	/**
	 * The name of the cookie security scheme.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	public static string $security_scheme_cookie = 'cookieAuth';

	/**
	 * The name of the nonce security scheme.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	public static string $security_scheme_nonce = 'nonceAuth';

	/**
	 * The name of the experimental security scheme.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	public static string $security_scheme_experimental = 'experimentalAuth';

	/**
	 * Returns the base OpenAPI specification structure.
	 *
	 * @since 4.25.0
	 *
	 * @return array{
	 *      openapi: string,
	 *      info: array<string, mixed>,
	 *      servers: list<array<string, mixed>>,
	 *      paths: array{},
	 *      components: array{schemas: array<string,array<string,mixed>>, securitySchemes: array<string,array<string,string>>}
	 *  }
	 */
	public static function get_base_spec(): array {
		return [
			'openapi'    => '3.0.0',
			'info'       => [
				'title'       => __( 'LearnDash REST API', 'learndash' ),
				'description' => __( 'Provides programmatic access to LearnDash plugin features via a RESTful API. This specification is versioned according to the LearnDash plugin release.', 'learndash' ),
				'version'     => LEARNDASH_VERSION,
				'contact'     => [
					'name' => 'LearnDash',
					'url'  => 'http://www.learndash.com',
				],
				'license'     => [
					'name' => 'GPL v2 or later',
					'url'  => 'https://www.gnu.org/licenses/gpl-2.0.html',
				],
			],
			'servers'    => [
				[
					'url'         => rtrim( rest_url(), '/' ),
					'description' => __( 'LearnDash REST API Server', 'learndash' ),
				],
			],
			'paths'      => [],
			'components' => [
				'schemas'         => self::get_common_schemas(),
				'securitySchemes' => self::get_security_schemes(),
			],
		];
	}

	/**
	 * Returns common schemas used across endpoints.
	 *
	 * @since 4.25.0
	 *
	 * @return array<string,array<string,mixed>>
	 */
	public static function get_common_schemas(): array {
		/**
		 * Filters the common schemas used across endpoints.
		 *
		 * @since 4.25.0
		 *
		 * @param array<string,array<string,mixed>> $schemas The common schemas.
		 *
		 * @return array<string,array<string,mixed>>
		 */
		return apply_filters(
			'learndash_rest_v1_common_schemas',
			[
				'SuccessResponse' => [
					'type'       => 'object',
					'properties' => [
						'success' => [
							'type'        => 'boolean',
							'description' => __( 'Indicates if the request was successful.', 'learndash' ),
							'example'     => true,
						],
						'data'    => [
							'type'        => 'object',
							'description' => __( 'The response data.', 'learndash' ),
						],
						'message' => [
							'type'        => 'string',
							'description' => __( 'Optional success message.', 'learndash' ),
							'example'     => __( 'Operation completed successfully.', 'learndash' ),
						],
					],
					'required'   => [ 'success' ],
				],
				'ErrorResponse'   => [
					'oneOf' => [
						[
							'type'        => 'object',
							'description' => __( 'WordPress WP_Error format.', 'learndash' ),
							'properties'  => [
								'code'    => [
									'type'        => 'string',
									'description' => __( 'Error code.', 'learndash' ),
									'example'     => 'rest_error',
								],
								'message' => [
									'type'        => 'string',
									'description' => __( 'Error message.', 'learndash' ),
									'example'     => __( 'An error occurred.', 'learndash' ),
								],
								'data'    => [
									'type'        => 'object',
									'description' => __( 'Error data containing status and additional information.', 'learndash' ),
									'properties'  => [
										'status' => [
											'type'        => 'integer',
											'description' => __( 'HTTP status code.', 'learndash' ),
											'example'     => 400,
										],
									],
									'required'    => [ 'status' ],
								],
							],
							'required'    => [ 'code', 'message', 'data' ],
						],
						[
							'type'        => 'object',
							'description' => __( 'WP_REST_Response format.', 'learndash' ),
							'properties'  => [
								'success' => [
									'type'        => 'boolean',
									'description' => __( 'Indicates if the request was successful.', 'learndash' ),
									'example'     => false,
								],
								'code'    => [
									'type'        => 'string',
									'description' => __( 'Error code.', 'learndash' ),
									'example'     => 'rest_error',
								],
								'message' => [
									'type'        => 'string',
									'description' => __( 'Error message.', 'learndash' ),
									'example'     => __( 'An error occurred.', 'learndash' ),
								],
								'data'    => [
									'type'        => 'object',
									'description' => __( 'Optional error data.', 'learndash' ),
								],
							],
							'required'    => [ 'success', 'code', 'message' ],
						],
					],
				],
			]
		);
	}

	/**
	 * Returns security schemes for authentication.
	 *
	 * @since 4.25.0
	 *
	 * @return array<string,array<string,string>>
	 */
	public static function get_security_schemes(): array {
		return [
			self::$security_scheme_cookie       => [
				'type'        => 'apiKey',
				'in'          => 'cookie',
				'name'        => 'wordpress_logged_in',
				'description' => __( 'WordPress authentication cookie.', 'learndash' ),
			],
			self::$security_scheme_nonce        => [
				'type'        => 'apiKey',
				'in'          => 'header',
				'name'        => 'X-WP-Nonce',
				'description' => __( 'WordPress nonce for CSRF protection.', 'learndash' ),
			],
			self::$security_scheme_experimental => [
				'type'        => 'apiKey', // While this doesn't feel like an API Key, this is closest thing that OpenAPI provides.
				'in'          => 'header',
				'name'        => 'Learndash-Experimental-Rest-Api',
				'description' => __( 'LearnDash Experimental REST API header for access to experimental endpoints.', 'learndash' ),
			],
		];
	}

	/**
	 * Generates parameter schema for common parameters.
	 *
	 * @since 4.25.0
	 *
	 * @param string $type Parameter type (quantity, meta, etc.).
	 *
	 * @return array<string,mixed>
	 */
	public static function get_parameter_schema( string $type ): array {
		switch ( $type ) {
			// @TODO: Add more parameter types in the future.
			default:
				return [
					'type'        => 'string',
					'description' => __( 'Parameter value.', 'learndash' ),
				];
		}
	}

	/**
	 * Generates tags for endpoint categorization.
	 *
	 * @since 4.25.0
	 *
	 * @return array<string,array<string,string>>
	 */
	public static function get_tags(): array {
		return []; // @TODO: Add tags in the future.
	}
}
