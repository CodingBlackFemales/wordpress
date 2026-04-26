<?php
/**
 * Abstract REST endpoint.
 *
 * @since 4.25.0
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Core\Modules\REST\V1\Contracts;

use LearnDash\Core\Modules\REST\V1\OpenAPI;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;
use LearnDash\Core\Utilities\Cast;
use StellarWP\Learndash\StellarWP\Arrays\Arr;
use stdClass;

/**
 * Abstract REST endpoint class.
 *
 * @since 4.25.0
 */
abstract class Endpoint implements Interface_Endpoint {
	/**
	 * The namespace for this endpoint.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	protected string $namespace = 'learndash/v1';

	/**
	 * The base route for this endpoint.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	protected string $base_route = '';

	/**
	 * The permission required to access this endpoint, if not set, the endpoint is public.
	 *
	 * @since 4.25.0
	 *
	 * @var string
	 */
	protected string $permission_required = 'manage_options';

	/**
	 * Whether the endpoint is experimental.
	 *
	 * @since 4.25.0
	 *
	 * @var bool
	 */
	protected bool $experimental = true;

	/**
	 * Returns the routes configuration for this endpoint.
	 *
	 * @since 4.25.0
	 *
	 * @return array<string,array<string,string|callable|mixed[]>>
	 */
	abstract protected function get_routes(): array;

	/**
	 * Returns the endpoint arguments.
	 *
	 * @since 4.25.0
	 *
	 * @return array<string,array<string,mixed>>
	 */
	abstract protected function get_endpoint_args(): array;

	/**
	 * Validates the experimental header.
	 *
	 * @since 4.25.0
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request The REST request object.
	 *
	 * @return bool|WP_Error
	 */
	protected function validate_experimental_header( $request ) {
		if ( ! $this->experimental ) {
			return true;
		}

		if ( 'allow' !== strtolower( Cast::to_string( $request->get_header( 'Learndash-Experimental-Rest-Api' ) ) ) ) {
			return new WP_Error(
				'rest_not_allowed',
				__( 'The Learndash-Experimental-Rest-Api header is required to access this endpoint.', 'learndash' ),
				[
					'status' => 403,
				]
			);
		}

		return true;
	}

	/**
	 * Validates a parameter type.
	 *
	 * @since 4.25.0
	 *
	 * @param mixed  $value The parameter value.
	 * @param string $type  The expected type.
	 *
	 * @return bool
	 */
	protected function validate_parameter_type( $value, $type ): bool {
		switch ( $type ) {
			case 'integer':
				return is_numeric( $value );
			case 'boolean':
				return is_bool( $value ) || in_array( $value, [ '1', '0', 'true', 'false' ], true );
			case 'array':
				return is_array( $value );
			case 'object':
				return is_object( $value ) || is_array( $value );
			case 'string':
			default:
				return is_string( $value ) || is_numeric( $value );
		}
	}

	/**
	 * Sanitizes a parameter value.
	 *
	 * Sanitizes values based on their type:
	 * - integer: Casts to integer.
	 * - number: Casts to float or integer.
	 * - boolean: Converts to true/false using rest_sanitize_boolean().
	 * - array: Recursively sanitizes array values.
	 * - object: Recursively sanitizes object properties.
	 * - string: Sanitizes using sanitize_text_field().
	 *
	 * @since 4.25.0
	 *
	 * @param mixed   $value      The parameter value to sanitize.
	 * @param string  $type       The parameter type (integer, boolean, array, object, string).
	 * @param ?string $param_name Optional. The parameter name for context-specific sanitization.
	 *
	 * @return mixed The sanitized value.
	 */
	protected function sanitize_parameter( $value, string $type, ?string $param_name = null ) {
		switch ( $type ) {
			case 'integer':
				return Cast::to_int( $value );
			case 'number':
				return is_float( $value ) ? Cast::to_float( $value ) : Cast::to_int( $value );
			case 'boolean':
				return rest_sanitize_boolean( Cast::to_string( $value ) );
			case 'array':
				return $this->sanitize_array_parameter( $value, Cast::to_string( $param_name ) );
			case 'object':
				return $this->sanitize_object_parameter( $value, Cast::to_string( $param_name ) );
			case 'string':
			default:
				return sanitize_text_field( Cast::to_string( $value ) );
		}
	}

	/**
	 * Sanitizes an array parameter with proper chaining.
	 *
	 * @since 4.25.0
	 *
	 * @param mixed  $value      The array value to sanitize.
	 * @param string $param_name The parameter name for context.
	 *
	 * @return array<string,mixed>
	 */
	protected function sanitize_array_parameter( $value, $param_name ): array {
		if ( ! is_array( $value ) ) {
			return [];
		}

		// For non-meta arrays, we need schema to know how to sanitize.
		// Without schema, we cannot safely sanitize, so return empty array.
		return [];
	}

	/**
	 * Sanitizes an object parameter with proper chaining.
	 *
	 * @since 4.25.0
	 *
	 * @param mixed  $value      The object value to sanitize.
	 * @param string $param_name The parameter name for context.
	 *
	 * @return array<string,mixed>|object
	 */
	protected function sanitize_object_parameter( $value, $param_name ) {
		if (
			! is_array( $value )
			&& ! is_object( $value )
		) {
			return (object) [];
		}

		// For non-meta objects, we need schema to know how to sanitize.
		// Without schema, we cannot safely sanitize, so return empty object.
		return (object) [];
	}

	/**
	 * Returns the meta type for this endpoint.
	 *
	 * Override this method in child classes to specify the correct meta type.
	 *
	 * @since 4.25.0
	 *
	 * @return string
	 */
	protected function get_meta_type(): string {
		return 'post';
	}

	/**
	 * Returns the meta schema for this endpoint.
	 *
	 * Override this method in child classes to provide specific meta field schemas.
	 *
	 * @since 4.25.0
	 *
	 * @return array<string,mixed>
	 */
	protected function get_meta_schema(): array {
		return [];
	}

	/**
	 * Converts endpoint args to request schema format.
	 *
	 * This method transforms WordPress REST API endpoint args format
	 * to OpenAPI/JSON Schema format for request body validation.
	 *
	 * @since 4.25.0
	 *
	 * @param array<string,array<string,mixed>> $endpoint_args The endpoint arguments. Defaults to an empty array, which will be overridden by the `get_endpoint_args` method if it exists.
	 *
	 * @return array{
	 *     type: string,
	 *     properties: array<string,array<string,mixed>>|object,
	 *     required?: string[],
	 * }
	 */
	protected function convert_endpoint_args_to_schema( array $endpoint_args = [] ): array {
		if ( empty( $endpoint_args ) ) {
			if ( ! method_exists( $this, 'get_endpoint_args' ) ) {
				return [
					'type'       => 'object',
					'properties' => [],
				];
			}

			$endpoint_args = $this->get_endpoint_args();
		}

		$properties = [];
		$required   = [];

		foreach ( $endpoint_args as $param => $config ) {
			// Check if parameter is required.
			if ( isset( $config['required'] ) && $config['required'] ) {
				$required[] = $param;
			}

			$properties[ $param ] = $this->convert_property_config( $config );
		}

		$schema = [
			'type'       => 'object',
			'properties' => empty( $properties )
				? new stdClass() // For empty properties, we need to return an object.
				: $properties,
		];

		if ( ! empty( $required ) ) {
			$schema['required'] = $required;
		}

		return $schema;
	}

	/**
	 * Converts a single property configuration, handling nested properties.
	 *
	 * @since 4.25.2
	 *
	 * @param array<string,mixed> $config The property configuration.
	 *
	 * @return array<string,mixed>
	 */
	protected function convert_property_config( array $config ): array {
		$property = [];

		// Map type.
		if ( isset( $config['type'] ) ) {
			$property['type'] = $config['type'];
		}

		// Map description.
		if ( isset( $config['description'] ) ) {
			$property['description'] = $config['description'];
		}

		// Map default value.
		if ( isset( $config['default'] ) ) {
			$property['default'] = $config['default'];
		}

		// Map enum values.
		if ( isset( $config['enum'] ) ) {
			$property['enum'] = $config['enum'];
		}

		// Map minimum/maximum for numbers.
		if ( isset( $config['minimum'] ) ) {
			$property['minimum'] = $config['minimum'];
		}
		if ( isset( $config['maximum'] ) ) {
			$property['maximum'] = $config['maximum'];
		}

		// Map readonly to the proper JSON readOnly.
		if ( isset( $config['readonly'] ) ) {
			$property['readOnly'] = $config['readonly'];
		}

		// Map array items.
		if ( isset( $config['items'] ) ) {
			$property['items'] = $config['items'];
		}

		// Map object properties recursively.
		if (
			isset( $config['properties'] )
			&& is_array( $config['properties'] )
		) {
			$child_properties = [];

			foreach ( $config['properties'] as $key => $property_config ) {
				$child_properties[ $key ] = $this->convert_property_config( $property_config );
			}

			$property['properties'] = $child_properties;
		}

		// Map additionalProperties recursively.
		if (
			isset( $config['additionalProperties'] )
			&& is_array( $config['additionalProperties'] )
		) {
			$property['additionalProperties'] = $this->convert_property_config( $config['additionalProperties'] );
		}

		if ( isset( $config['example'] ) ) {
			$property['example'] = $config['example'];
		}

		// Add example if not present but default is available.
		if ( ! Arr::has( $property, 'example' ) && Arr::has( $property, 'default' ) ) {
			$property['example'] = Arr::get( $property, 'default' );
		}

		// Parameter location.
		if ( isset( $config['in'] ) ) {
			$property['in'] = $config['in'];
		}

		return $property;
	}

	/**
	 * Creates a standardized success response.
	 *
	 * @since 4.25.0
	 *
	 * @param mixed  $data    The response data.
	 * @param string $message Optional success message.
	 * @param int    $status  HTTP status code.
	 *
	 * @return WP_REST_Response
	 */
	protected function success_response( $data = null, $message = '', $status = 200 ): WP_REST_Response {
		$response = [
			'success' => true,
			'data'    => $data,
		];

		if ( ! empty( $message ) ) {
			$response['message'] = $message;
		}

		return new WP_REST_Response( $response, $status );
	}

	/**
	 * Creates a standardized error response.
	 *
	 * @since 4.25.0
	 *
	 * @param string $message Error message.
	 * @param string $code    Error code.
	 * @param int    $status  HTTP status code.
	 * @param mixed  $data    Optional error data.
	 *
	 * @return WP_REST_Response
	 */
	protected function error_response( $message, $code = 'rest_error', $status = 400, $data = null ): WP_REST_Response {
		$response = [
			'success' => false,
			'code'    => $code,
			'message' => $message,
		];

		if ( null !== $data ) {
			$response['data'] = $data;
		}

		return new WP_REST_Response( $response, $status );
	}

	/**
	 * Builds the OpenAPI path schema for a route configuration.
	 *
	 * @since 4.25.0
	 *
	 * @param string                              $path   The path of the route.
	 * @param array<string,string|callable|mixed> $config Route configuration.
	 *
	 * @return array<string,array<string,mixed>>
	 */
	protected function build_openapi_path_schema( string $path, array $config ): array {
		$path_schema = [];

		if ( isset( $config['methods'] ) ) {
			$config = [ $config ];
		}

		foreach ( $config as $method_config ) {
			$methods = Arr::get( $method_config, 'methods', [ WP_REST_Server::READABLE ] );

			if ( is_string( $methods ) ) {
				$methods = [ $methods ];
			}

			if ( ! is_array( $methods ) ) {
				continue;
			}

			foreach ( $methods as $method ) {
				$method       = Cast::to_string( $method );
				$method_lower = strtolower( $method );

				if ( 'GET' === $method ) {
					$method_lower = 'get';
				} elseif ( 'POST' === $method ) {
					$method_lower = 'post';
				} elseif ( 'PUT' === $method ) {
					$method_lower = 'put';
				} elseif ( 'DELETE' === $method ) {
					$method_lower = 'delete';
				}

				$path_schema[ $method_lower ] = [
					'operationId' => $this->get_operation_id( $path, $method ),
					'summary'     => Arr::get( $method_config, 'summary', '' ),
					'description' => Arr::get( $method_config, 'description', '' ),
					'security'    => $this->get_security_schemes( $path, $method ),
					'parameters'  => $this->build_openapi_parameters( $path, $method ),
					'responses'   => $this->build_openapi_responses( $path, $method ),
					'tags'        => $this->get_tags(),
				];

				if ( in_array( $method, [ 'POST', 'PUT', 'PATCH' ], true ) ) {
					$path_schema[ $method_lower ]['requestBody'] = [
						'content' => [
							'application/json' => [
								'schema' => $this->build_openapi_request_body( $path, $method ),
							],
						],
					];
				}
			}
		}

		return $path_schema;
	}

	/**
	 * Returns the tags for this endpoint.
	 *
	 * @since 4.25.0
	 *
	 * @return string[]
	 */
	protected function get_tags(): array {
		return [ trim( $this->get_base_route(), '/' ) ];
	}

	/**
	 * Generates an operation ID for the given path and method.
	 *
	 * @since 5.0.0
	 *
	 * @param string $path   The path of the route.
	 * @param string $method The HTTP method.
	 *
	 * @return string The operation ID.
	 */
	protected function get_operation_id( string $path, string $method ): string {
		// Remove leading slash and convert to lowercase.
		$path = trim( $path, '/' );

		// Convert method to lowercase.
		$method_lower = strtolower( $method );

		// Handle special cases for better readability.
		$path_parts      = explode( '/', $path );
		$operation_parts = [];
		$filter_parts    = [];

		foreach ( $path_parts as $part ) {
			// Skip empty parts.
			if ( empty( $part ) ) {
				continue;
			}

			// Handle path parameters.
			if ( str_starts_with( $part, '{' ) && str_ends_with( $part, '}' ) ) {
				$param_name = trim( $part, '{}' );
				// Convert parameter names to descriptive action words.
				switch ( $param_name ) {
					case 'id':
						$filter_parts[] = 'id';
						break;
					case 'slug':
						$filter_parts[] = 'slug';
						break;
					case 'type':
						$filter_parts[] = 'type';
						break;
					default:
						$filter_parts[] = $param_name;
				}
			} else {
				// Clean up the part name and remove 'sfwd' prefix.
				$clean_part = str_replace( [ '-', '_' ], ' ', $part );
				$clean_part = ucwords( $clean_part );
				$clean_part = str_replace( ' ', '', $clean_part );

				// Remove 'sfwd' prefix if present.
				if ( str_starts_with( $clean_part, 'Sfwd' ) ) {
					$clean_part = substr( $clean_part, 4 );
				}

				// Convert camelCase to snake_case.
				$clean_part = Cast::to_string( preg_replace( '/([a-z])([A-Z])/', '$1_$2', $clean_part ) );
				$clean_part = strtolower( $clean_part );

				$operation_parts[] = $clean_part;
			}
		}

		// Handle multiple filters with 'and' instead of chaining 'by_'.

		if ( ! empty( $filter_parts ) ) {
			$operation_parts[] = count( $filter_parts ) === 1
				? "by_{$filter_parts[0]}"
				: 'by_' . implode( '_and_', $filter_parts );
		}

		// Create a meaningful operation ID.
		$operation_id = implode( '_', $operation_parts );

		// Add method-specific suffixes for better clarity.
		switch ( $method_lower ) {
			case 'get':
				$operation_id = str_contains( $operation_id, 'by_' )
					? "get_{$operation_id}"
					: "list_{$operation_id}";
				break;
			case 'post':
				$operation_id = "create_$operation_id";
				break;
			case 'put':
				$operation_id = "update_$operation_id";
				break;
			case 'patch':
				$operation_id = "patch_$operation_id";
				break;
			case 'delete':
				$operation_id = "delete_$operation_id";
				break;
			default:
				$operation_id = "{$method_lower}_$operation_id";
		}

		// Clean up the final operation ID.
		$operation_id = preg_replace( '/_+/', '_', $operation_id );
		$operation_id = trim( Cast::to_string( $operation_id ), '_' );

		// If the operation ID is empty or doesn't start with a letter, use a fallback.
		if ( empty( $operation_id ) || ! ctype_alpha( $operation_id[0] ) ) {
			$operation_id = "operation_$method_lower";
		}

		return $operation_id;
	}

	/**
	 * Returns the security schemes for this endpoint.
	 *
	 * @since 4.25.0
	 *
	 * @param string $path   The path of the route.
	 * @param string $method The HTTP method.
	 *
	 * @return array<int,array<string,string[]>>
	 */
	protected function get_security_schemes( string $path, string $method ): array {
		$security_schemes = [];

		if ( $this->experimental ) {
			$security_schemes[ OpenAPI::$security_scheme_experimental ] = [];
		}

		return array_filter( [ $security_schemes ] );
	}

	/**
	 * Builds the OpenAPI parameters schema.
	 *
	 * @since 4.25.0
	 *
	 * @param string $path   The path of the route.
	 * @param string $method The HTTP method.
	 *
	 * @return array<int,array<string,mixed>>
	 */
	protected function build_openapi_parameters( string $path, string $method ): array {
		$schema     = $this->get_request_schema( $path, $method );
		$properties = $schema['properties'] ?? [];
		$parameters = [];

		foreach ( $properties as $name => $config ) {
			$parameter = [
				'name'        => $name,
				'in'          => Arr::get( $config, 'in', 'query' ),
				'description' => Arr::get( $config, 'description', '' ),
				'required'    => Arr::get( $config, 'required', false ),
				'schema'      => [
					'type' => Arr::get( $config, 'type', 'string' ),
				],
			];

			// Add enum values if present.
			if ( Arr::has( $config, 'enum' ) ) {
				$parameter['schema']['enum'] = $config['enum'];
			}

			// Add minimum/maximum for numbers.
			if ( Arr::has( $config, 'minimum' ) ) {
				$parameter['schema']['minimum'] = $config['minimum'];
			}
			if ( Arr::has( $config, 'maximum' ) ) {
				$parameter['schema']['maximum'] = $config['maximum'];
			}

			// Add items for array types.
			if ( Arr::has( $config, 'items' ) ) {
				$parameter['schema']['items'] = $config['items'];
			}

			// Add properties for object types.
			if ( Arr::has( $config, 'properties' ) ) {
				$parameter['schema']['properties'] = $config['properties'];
			}

			// Add example if present.
			if ( Arr::has( $config, 'example' ) ) {
				$parameter['schema']['example'] = $config['example'];
			}

			if ( Arr::has( $config, 'default' ) ) {
				$default_value = $config['default'];

				// If the parameter type is array and the default value is not an array, wrap it in an array.

				if (
					$parameter['schema']['type'] === 'array'
					&& ! is_array( $default_value )
				) {
					$default_value = [ $default_value ];
				}

				$parameter['schema']['default'] = $default_value;
			}

			$parameters[] = $parameter;
		}

		return $parameters;
	}

	/**
	 * Builds the OpenAPI responses schema.
	 *
	 * @since 4.25.0
	 *
	 * @param string $path   The path of the route.
	 * @param string $method The HTTP method.
	 *
	 * @return array<int, array<string, array<string, array<string, array<string, array<string, mixed>|string>|string>>|string>>
	 */
	protected function build_openapi_responses( string $path, string $method ): array {
		return [
			'200' => [
				'description' => 'Success',
				'content'     => [
					'application/json' => [
						'schema' => $this->get_response_schema( $path, $method ),
					],
				],
			],
			'400' => [
				'description' => 'Bad Request',
				'content'     => [
					'application/json' => [
						'schema' => [
							'type'       => 'object',
							'properties' => [
								'success' => [
									'type'    => 'boolean',
									'example' => false,
								],
								'code'    => [
									'type'    => 'string',
									'example' => 'rest_error',
								],
								'message' => [
									'type'    => 'string',
									'example' => 'Error message',
								],
							],
						],
					],
				],
			],
		];
	}

	/**
	 * Builds the OpenAPI request body schema.
	 *
	 * @since 4.25.2
	 *
	 * @param string $path   The path of the route.
	 * @param string $method The HTTP method.
	 *
	 * @return array{type:string, properties:array<string, array<string, mixed>>|object, required?:string[]}
	 */
	protected function build_openapi_request_body( string $path, string $method ): array {
		return $this->convert_endpoint_args_to_schema();
	}

	/**
	 * Returns the schema for request parameters.
	 *
	 * @since 4.25.0
	 *
	 * @param string $path   The path of the route.
	 * @param string $method The HTTP method.
	 *
	 * @return array<string,array<string,array<string,mixed>>>
	 */
	abstract public function get_request_schema( string $path, string $method ): array;

	/**
	 * Returns the schema for response data.
	 *
	 * @since 4.25.0
	 *
	 * @param string $path   The path of the route.
	 * @param string $method The HTTP method.
	 *
	 * @return array<string,array<string,mixed>|string>
	 */
	abstract public function get_response_schema( string $path, string $method ): array;

	/**
	 * Returns the namespace for this endpoint.
	 *
	 * @since 4.25.0
	 *
	 * @return string
	 */
	public function get_namespace(): string {
		return $this->namespace;
	}

	/**
	 * Returns the base route for this endpoint.
	 *
	 * @since 4.25.0
	 *
	 * @return string
	 */
	public function get_base_route(): string {
		return $this->base_route;
	}

	/**
	 * Returns the permission required to access this endpoint.
	 *
	 * @since 4.25.0
	 *
	 * @return string
	 */
	public function get_permission_required(): string {
		return $this->permission_required;
	}

	/**
	 * Checks if the current user has permission to access this endpoint.
	 *
	 * @since 4.25.0
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request The REST request object.
	 *
	 * @return bool|WP_Error
	 */
	public function check_permission( $request ) {
		$header_validation = $this->validate_experimental_header( $request );

		if ( is_wp_error( $header_validation ) ) {
			return $header_validation;
		}

		$permission = $this->get_permission_required();

		if ( '' === $permission ) {
			return true;
		}

		if ( ! is_user_logged_in() ) {
			return new WP_Error(
				'rest_not_logged_in',
				__( 'You must be logged in to access this endpoint.', 'learndash' ),
				[
					'status' => 401,
				]
			);
		}

		return current_user_can( $permission );
	}

	/**
	 * Validates the request parameters.
	 *
	 * @since 4.25.0
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request The REST request object.
	 *
	 * @return bool|WP_Error
	 */
	public function validate_request( $request ) {
		$schema = $this->get_request_schema( $request->get_route(), $request->get_method() );
		$errors = [];

		if ( empty( $schema['properties'] ) ) {
			return true;
		}

		$required_params = (array) Arr::get( $schema, 'required', [] );

		foreach ( $schema['properties'] as $param => $config ) {
			if ( ! $request->has_param( $param ) ) {
				// Check if parameter is required.
				if ( in_array( $param, $required_params, true ) ) {
					$errors[] = sprintf(
						/* translators: %s: parameter name */
						__( 'Missing required parameter: %s', 'learndash' ),
						$param
					);
				}
				continue;
			}

			$value = $request->get_param( $param );
			$type  = Cast::to_string( Arr::get( $config, 'type', 'string' ) );

			// Validate parameter type.
			if ( ! $this->validate_parameter_type( $value, $type ) ) {
				$errors[] = sprintf(
					/* translators: 1: parameter name, 2: expected type */
					__( 'Parameter %1$s must be of type %2$s.', 'learndash' ),
					$param,
					$type
				);
			}
		}

		if ( ! empty( $errors ) ) {
			return new WP_Error(
				'rest_invalid_param',
				implode( ', ', $errors ),
				[
					'status' => 422,
				]
			);
		}

		return true;
	}

	/**
	 * Sanitizes the request parameters.
	 *
	 * @since 4.25.0
	 *
	 * @param WP_REST_Request<array<string,mixed>> $request The REST request object.
	 *
	 * @return array<string,mixed>
	 */
	public function sanitize_request( $request ): array {
		$schema    = $this->get_request_schema( $request->get_route(), $request->get_method() );
		$sanitized = [];

		if ( empty( $schema['properties'] ) ) {
			return $request->get_params();
		}

		foreach ( $schema['properties'] as $param => $config ) {
			if ( ! $request->has_param( $param ) ) {
				continue;
			}

			$value = $request->get_param( $param );
			$type  = Cast::to_string( Arr::get( $config, 'type', 'string' ) );

			$sanitized[ $param ] = $this->sanitize_parameter( $value, $type, $param );
		}

		return $sanitized;
	}

	/**
	 * Returns the OpenAPI schema for this endpoint.
	 *
	 * @since 4.25.0
	 * @since 5.0.0 Added $trim parameter.
	 *
	 * @param bool $trim Whether to trim documentation from the OpenAPI schema. Only used if an Endpoint class overrides this method. Default is true.
	 *
	 * @return array<string, array<string, array<string, mixed>>>
	 */
	public function get_openapi_schema( bool $trim = true ): array {
		$routes = $this->get_routes();
		$schema = [];

		foreach ( $routes as $route => $config ) {
			// Add the namespace and base route to the path, so that the whole thing is properly listed in the OpenAPI schema.
			$path = '/' . trailingslashit( trim( $this->get_namespace(), '/' ) ) . trailingslashit( trim( $this->get_base_route(), '/' ) ) . ltrim( $route, '/' );

			$path = str_replace( '//', '/', $path );

			// Don't include manifest endpoints in the OpenAPI schema, these are just for the MCP server.
			if ( str_contains( $path, '/manifest' ) ) {
				continue;
			}

			// Convert patterns like (?P<id>[\d]+) to {id}.
			$path = Cast::to_string( preg_replace( '/\(\?P<([^>]+)>[^)]+\)/', '{$1}', $path ) );

			$schema[ $path ] = $this->build_openapi_path_schema( $path, $config );
		}

		return $schema;
	}

	/**
	 * Registers routes for this endpoint.
	 *
	 * @since 4.25.0
	 *
	 * @return void
	 */
	public function register_routes(): void {
		$routes = $this->get_routes();

		foreach ( $routes as $route => $config ) {
			register_rest_route(
				$this->get_namespace(),
				$this->get_base_route() . $route,
				$config
			);
		}
	}
}
