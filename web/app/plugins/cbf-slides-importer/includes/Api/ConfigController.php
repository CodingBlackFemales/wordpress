<?php
/**
 * REST endpoints for deck import configurations.
 *
 * GET    /configs        — list
 * POST   /configs        — create
 * GET    /configs/{id}   — get
 * PUT    /configs/{id}   — update
 * DELETE /configs/{id}   — delete
 *
 * @class   Api\ConfigController
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Api;

use WP_REST_Request;
use WP_REST_Response;
use WP_Error;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ConfigController class.
 *
 * All queries filter on blog_id = get_current_blog_id() and
 * user_id = get_current_user_id() to prevent cross-user/cross-site access.
 */
final class ConfigController {

	/**
	 * Register routes.
	 *
	 * @param string $namespace REST namespace.
	 */
	public static function register_routes( string $namespace ): void {
		register_rest_route(
			$namespace,
			'/configs',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'index' ),
					'permission_callback' => array( AuthController::class, 'require_auth' ),
				),
				array(
					'methods'             => 'POST',
					'callback'            => array( __CLASS__, 'create' ),
					'permission_callback' => array( AuthController::class, 'require_auth' ),
					'args'                => self::config_args(),
				),
			)
		);

		register_rest_route(
			$namespace,
			'/configs/(?P<id>[\d]+)',
			array(
				array(
					'methods'             => 'GET',
					'callback'            => array( __CLASS__, 'show' ),
					'permission_callback' => array( AuthController::class, 'require_auth' ),
				),
				array(
					'methods'             => 'PUT',
					'callback'            => array( __CLASS__, 'update' ),
					'permission_callback' => array( AuthController::class, 'require_auth' ),
					'args'                => self::config_args( required: false ),
				),
				array(
					'methods'             => 'DELETE',
					'callback'            => array( __CLASS__, 'destroy' ),
					'permission_callback' => array( AuthController::class, 'require_auth' ),
				),
			)
		);
	}


	/** GET /configs */
	public static function index( WP_REST_Request $request ): WP_REST_Response {
		global $wpdb;
		$table = $wpdb->prefix . 'cbf_slide_import_configs';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE blog_id = %d AND user_id = %d ORDER BY updated_at DESC LIMIT 100", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				get_current_blog_id(),
				get_current_user_id()
			),
			ARRAY_A
		);

		return new WP_REST_Response( $rows ?: array(), 200 );
	}


	/** POST /configs */
	public static function create( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		global $wpdb;
		$table = $wpdb->prefix . 'cbf_slide_import_configs';

		$data = self::extract_data( $request );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$result = $wpdb->insert( $table, $data, self::data_formats() );
		if ( $result === false ) {
			return new WP_Error( 'cbf_si_db_error', $wpdb->last_error, array( 'status' => 500 ) );
		}

		return new WP_REST_Response( array_merge( $data, array( 'id' => $wpdb->insert_id ) ), 201 );
	}


	/** GET /configs/{id} */
	public static function show( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		$row = self::find_row( (int) $request->get_param( 'id' ) );
		return is_wp_error( $row ) ? $row : new WP_REST_Response( $row, 200 );
	}


	/** PUT /configs/{id} */
	public static function update( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		global $wpdb;
		$table = $wpdb->prefix . 'cbf_slide_import_configs';

		$existing = self::find_row( (int) $request->get_param( 'id' ) );
		if ( is_wp_error( $existing ) ) {
			return $existing;
		}

		$data = self::extract_data( $request, update: true );

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update(
			$table,
			$data,
			array(
				'id'      => (int) $request->get_param( 'id' ),
				'user_id' => get_current_user_id(),
				'blog_id' => get_current_blog_id(),
			),
			self::data_formats(),
			array( '%d', '%d', '%d' )
		);

		return new WP_REST_Response( array_merge( $existing, $data ), 200 );
	}


	/** DELETE /configs/{id} */
	public static function destroy( WP_REST_Request $request ): WP_REST_Response|WP_Error {
		global $wpdb;
		$table = $wpdb->prefix . 'cbf_slide_import_configs';

		$existing = self::find_row( (int) $request->get_param( 'id' ) );
		if ( is_wp_error( $existing ) ) {
			return $existing;
		}

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete(
			$table,
			array(
				'id'      => (int) $request->get_param( 'id' ),
				'user_id' => get_current_user_id(),
				'blog_id' => get_current_blog_id(),
			),
			array( '%d', '%d', '%d' )
		);

		return new WP_REST_Response( null, 204 );
	}


	// ── Helpers ───────────────────────────────────────────────────────────────

	/**
	 * Find a config row owned by the current user on the current site.
	 *
	 * @param int $id Config ID.
	 * @return array|WP_Error
	 */
	private static function find_row( int $id ): array|WP_Error {
		global $wpdb;
		$table = $wpdb->prefix . 'cbf_slide_import_configs';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d AND user_id = %d AND blog_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$id,
				get_current_user_id(),
				get_current_blog_id()
			),
			ARRAY_A
		);

		if ( ! $row ) {
			return new WP_Error( 'cbf_si_not_found', __( 'Config not found.', 'cbf-slides-importer' ), array( 'status' => 404 ) );
		}

		return $row;
	}


	/**
	 * Extract and sanitise config data from the request.
	 *
	 * @param WP_REST_Request $request REST request.
	 * @param bool            $update  When true, only present params are included.
	 * @return array<string,mixed>
	 */
	private static function extract_data( WP_REST_Request $request, bool $update = false ): array {
		$data = array(
			'blog_id' => get_current_blog_id(),
			'user_id' => get_current_user_id(),
		);

		$fields = array(
			'drive_file_id'       => 'sanitize_text_field',
			'deck_name'           => 'sanitize_text_field',
			'mode'                => 'sanitize_text_field',
			'heading_layout_regex' => 'sanitize_text_field',
			'course_id'           => 'absint',
			'lesson_id'           => 'absint',
		);

		foreach ( $fields as $key => $sanitizer ) {
			$val = $request->get_param( $key );
			if ( $val !== null || ! $update ) {
				$data[ $key ] = call_user_func( $sanitizer, (string) $val );
			}
		}

		// slide_overrides: must be valid JSON object.
		$overrides = $request->get_param( 'slide_overrides' );
		if ( $overrides !== null || ! $update ) {
			$decoded = is_array( $overrides ) ? $overrides : json_decode( (string) $overrides, true );
			$data['slide_overrides'] = wp_json_encode( is_array( $decoded ) ? $decoded : array() );
		}

		return $data;
	}


	/** @return array<string,string> REST args schema. */
	private static function config_args( bool $required = true ): array {
		return array(
			'drive_file_id'        => array(
				'type' => 'string',
				'required' => $required,
			),
			'deck_name'            => array(
				'type' => 'string',
				'required' => false,
			),
			'mode'                 => array(
				'type' => 'string',
				'required' => false,
				'default' => 'lesson-only',
				'enum' => array( 'lesson-only', 'topic' ),
			),
			'lesson_id'            => array(
				'type' => 'integer',
				'required' => false,
				'default' => 0,
			),
			'heading_layout_regex' => array(
				'type' => 'string',
				'required' => false,
				'default' => '',
			),
			'course_id'            => array(
				'type' => 'integer',
				'required' => false,
				'default' => 0,
			),
			'slide_overrides'      => array(
				'type' => 'object',
				'required' => false,
				'default' => array(),
			),
		);
	}


	/** @return array<string> $wpdb format strings matching extract_data() field order. */
	private static function data_formats(): array {
		return array( '%d', '%d', '%s', '%s', '%s', '%s', '%d', '%d', '%s' );
	}
}
