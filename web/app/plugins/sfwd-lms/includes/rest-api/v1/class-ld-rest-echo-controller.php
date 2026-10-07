<?php
/**
 * LearnDash REST API V1 Echo Controller.
 *
 * @since 3.0.7
 * @package LearnDash\REST\V1
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LD_REST_Echo_Controller_V1' ) ) && ( class_exists( 'WP_REST_Controller' ) ) ) {

	/**
	 * Class LearnDash REST API V1 Echo Controller.
	 *
	 * @since 3.0.7
	 */
	class LD_REST_Echo_Controller_V1 extends WP_REST_Controller /* phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedClassFound */ {
		/**
		 * Version
		 *
		 * @var string
		 */
		protected $version = 'v1';

		/**
		 * Maximum request body size accepted by this debug endpoint, in bytes.
		 *
		 * @since 5.0.5.1
		 *
		 * @var int
		 */
		private const MAX_BODY_BYTES = KB_IN_BYTES * 16;

		/**
		 * Constructor.
		 *
		 * @since 3.0.7
		 */
		public function __construct() {
			$this->namespace = LEARNDASH_REST_API_NAMESPACE . '/' . $this->version;
			$this->rest_base = 'echo';
		}

		/**
		 * Registers the routes for the objects of the controller.
		 *
		 * @since 3.0.7
		 *
		 * @see register_rest_route()
		 */
		public function register_routes() {
			register_rest_route(
				$this->namespace,
				'/' . $this->rest_base,
				array(
					array(
						'methods'             => WP_REST_Server::READABLE,
						'callback'            => array( $this, 'get_response' ),
						'permission_callback' => array( $this, 'get_response_permissions_check' ),
					),
					array(
						'methods'             => WP_REST_Server::EDITABLE,
						'callback'            => array( $this, 'get_response' ),
						'permission_callback' => array( $this, 'get_response_permissions_check' ),
					),
					array(
						'methods'             => WP_REST_Server::DELETABLE,
						'callback'            => array( $this, 'get_response' ),
						'permission_callback' => array( $this, 'get_response_permissions_check' ),
					),
					'schema' => array( $this, 'get_item_schema' ),
				)
			);
		}

		/**
		 * Checks if a given request has access to read the theme.
		 *
		 * @since 3.0.7
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return true|WP_Error True if the request has read access for the item, otherwise WP_Error object.
		 */
		public function get_response_permissions_check( $request ) {
			if (
				! defined( 'LEARNDASH_DEBUG' ) // @phpstan-ignore-line booleanOr.alwaysTrue -- Constant may or may not be defined by user.
				|| ! LEARNDASH_DEBUG // @phpstan-ignore-line booleanNot.alwaysTrue -- Constant may or may not be defined by user.
			) {
				return new WP_Error(
					'learndash_rest_forbidden',
					__( 'Sorry, you are not allowed to access this endpoint.', 'learndash' ),
					array( 'status' => rest_authorization_required_code() )
				);
			}

			return true; // @phpstan-ignore-line deadCode.unreachable -- Constant may or may not be defined by user.
		}

		/**
		 * Retrieves a response.
		 *
		 * @since 3.0.7
		 *
		 * @param WP_REST_Request $request Full details about the request.
		 *
		 * @return WP_REST_Response|WP_Error Response object on success, or WP_Error object on failure.
		 */
		public function get_response( $request ) {
			$request_body = $request->get_body();

			if ( strlen( $request_body ) > self::MAX_BODY_BYTES ) {
				return new WP_Error(
					'learndash_rest_payload_too_large',
					__( 'Request body exceeds the maximum allowed size.', 'learndash' ),
					array( 'status' => 413 )
				);
			}

			$response_array                  = array();
			$response_array['method']        = $request->get_method();
			$response_array['route']         = $request->get_route();
			$response_array['authenticated'] = is_user_logged_in() ? 1 : 0;
			$response_array['query_params']  = $request->get_query_params();

			if ( ! empty( $request_body ) ) {
				$response_array['content-type'] = $request->get_header( 'content-type' );
				$response_array['body']         = json_decode( $request_body, true );
			} else {
				$response_array['body'] = '';
			}

			$response = rest_ensure_response( $response_array );

			$response->header( 'X-WP-Total', count( $response_array ) );
			$response->header( 'X-WP-TotalPages', count( $response_array ) );

			return $response;
		}

		/**
		 * Retrieves the schema, conforming to JSON Schema.
		 *
		 * @since 3.0.7
		 *
		 * @return array Item schema data.
		 */
		public function get_item_schema() {
			$schema = array();

			return $this->add_additional_fields_schema( $schema );
		}
	}
}
