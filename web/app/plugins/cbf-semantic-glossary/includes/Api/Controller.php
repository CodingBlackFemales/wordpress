<?php
/**
 * REST endpoints for the block editor.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Api;

use CodingBlackFemales\SemanticGlossary\Audit\Auditor;
use CodingBlackFemales\SemanticGlossary\Entry\Entry;
use CodingBlackFemales\SemanticGlossary\Entry\Markdown;
use CodingBlackFemales\SemanticGlossary\Entry\PostType;
use CodingBlackFemales\SemanticGlossary\Entry\Repository;
use CodingBlackFemales\SemanticGlossary\Entry\Search;
use CodingBlackFemales\SemanticGlossary\Reference\Index;
use WP_Error;
use WP_REST_Request;
use WP_REST_Response;
use WP_REST_Server;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Controller.
 *
 * Routes under /wp-json/cbf-glossary/v1/:
 *
 * - GET  terms          search entries, or fetch some by ID (`include`), with usage counts
 * - POST terms          create an entry (Editors and above)
 * - POST terms/{id}     update an entry (Editors and above)
 * - POST audit          audit unsaved content: dead references and unmarked known terms
 *
 * Core's /wp/v2/glossary-terms endpoints exist too; these add what the editor
 * needs on top (searching every form, usage counts, auditing).
 */
final class Controller {

	/**
	 * REST namespace.
	 */
	const NAMESPACE = 'cbf-glossary/v1';

	/**
	 * Most results one search returns.
	 */
	const MAX_PER_PAGE = 100;


	/**
	 * Register hooks.
	 */
	public static function hooks(): void {
		add_action( 'rest_api_init', array( __CLASS__, 'register_routes' ) );
	}


	/**
	 * Register routes.
	 */
	public static function register_routes(): void {
		register_rest_route(
			self::NAMESPACE,
			'/terms',
			array(
				array(
					'methods'             => WP_REST_Server::READABLE,
					'callback'            => array( __CLASS__, 'list_terms' ),
					'permission_callback' => array( __CLASS__, 'can_reference' ),
					'args'                => self::list_args(),
				),
				array(
					'methods'             => WP_REST_Server::CREATABLE,
					'callback'            => array( __CLASS__, 'create_term' ),
					'permission_callback' => array( __CLASS__, 'can_create' ),
					'args'                => self::entry_args(),
				),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/terms/(?P<id>\d+)',
			array(
				'methods'             => WP_REST_Server::EDITABLE,
				'callback'            => array( __CLASS__, 'update_term' ),
				'permission_callback' => fn ( WP_REST_Request $request ) => current_user_can( 'edit_post', (int) $request['id'] ),
				'args'                => self::entry_args(),
			)
		);

		register_rest_route(
			self::NAMESPACE,
			'/audit',
			array(
				'methods'             => WP_REST_Server::CREATABLE,
				'callback'            => array( __CLASS__, 'audit' ),
				'permission_callback' => array( __CLASS__, 'can_reference' ),
				'args'                => array(
					'content' => array(
						'type'     => 'string',
						'required' => true,
					),
					'ignored' => array(
						'type'    => 'array',
						'items'   => array( 'type' => 'integer' ),
						'default' => array(),
					),
				),
			)
		);
	}


	/**
	 * Anyone who can edit posts can look entries up and reference them.
	 */
	public static function can_reference(): bool {
		return current_user_can( 'edit_posts' );
	}


	/**
	 * Creating entries is for Editors and above.
	 */
	public static function can_create(): bool {
		$type = get_post_type_object( PostType::NAME );
		return $type !== null && current_user_can( $type->cap->create_posts );
	}


	/**
	 * GET /terms.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public static function list_terms( WP_REST_Request $request ): WP_REST_Response {
		$include = array_map( 'intval', (array) $request['include'] );
		$entries = $include !== array()
			? array_values( Repository::instance()->find_many( $include ) )
			: Search::rank( Repository::instance()->all_published(), (string) $request['search'], (int) $request['per_page'] );

		return new WP_REST_Response( self::prepare( $entries ) );
	}


	/**
	 * POST /terms.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function create_term( WP_REST_Request $request ) {
		return self::save( $request, 0 );
	}


	/**
	 * POST /terms/{id}.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return WP_REST_Response|WP_Error
	 */
	public static function update_term( WP_REST_Request $request ) {
		$entry = Repository::instance()->find( (int) $request['id'] );

		if ( $entry === null ) {
			return new WP_Error( 'glossary_not_found', __( 'No such glossary entry.', 'cbf-semantic-glossary' ), array( 'status' => 404 ) );
		}

		return self::save( $request, $entry->id );
	}


	/**
	 * POST /audit.
	 *
	 * The sidebar sends the editor's current, unsaved content.
	 *
	 * @param WP_REST_Request $request Request.
	 */
	public static function audit( WP_REST_Request $request ): WP_REST_Response {
		$findings = Auditor::audit( (string) $request['content'], Repository::instance(), (array) $request['ignored'] );
		$findings = array_values( array_filter( $findings, fn ( array $f ): bool => $f['type'] !== Auditor::DUPLICATE ) );
		$entries  = Repository::instance()->find_many( array_column( $findings, 'entry_id' ) );

		return new WP_REST_Response(
			array(
				'findings' => $findings,
				'entries'  => self::prepare( array_values( $entries ) ),
			)
		);
	}


	/**
	 * Create or update from a request.
	 *
	 * @param WP_REST_Request $request Request.
	 * @param int             $id      Entry ID, or 0 to create.
	 * @return WP_REST_Response|WP_Error
	 */
	private static function save( WP_REST_Request $request, int $id ) {
		$result = Repository::instance()->save( self::entry_data( $request ), $id );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		$entry = Repository::instance()->find( $result );

		return new WP_REST_Response( self::prepare( array_filter( array( $entry ) ) )[0] ?? null, $id > 0 ? 200 : 201 );
	}


	/**
	 * Entry fields from a create or update request.
	 *
	 * @param WP_REST_Request $request Request.
	 * @return array<string, mixed>
	 */
	private static function entry_data( WP_REST_Request $request ): array {
		$data = array( 'forms' => (array) $request['forms'] );

		if ( $request->has_param( 'definition' ) ) {
			$data['definition'] = Markdown::to_definition( (string) $request['definition'] );
		}

		if ( $request->has_param( 'slug' ) ) {
			$data['slug'] = (string) $request['slug'];
		}

		return $data;
	}


	/**
	 * Entries as response data, with usage counts and edit links.
	 *
	 * @param Entry[] $entries Entries.
	 * @return array<int, array<string, mixed>>
	 */
	private static function prepare( array $entries ): array {
		$usage = Index::usage_counts( array_map( fn ( Entry $entry ): int => $entry->id, $entries ) );

		return array_map(
			function ( Entry $entry ) use ( $usage ): array {
				$can_edit = current_user_can( 'edit_post', $entry->id );
				$data     = array_merge(
					$entry->to_array(),
					array(
						'usage'     => $usage[ $entry->id ] ?? 0,
						'edit_link' => $can_edit ? get_edit_post_link( $entry->id, 'raw' ) : null,
					)
				);

				// Authors see that an unpublished entry exists (its references render
				// as plain text), but not what an unpublished draft says.
				if ( ! $entry->is_published() && ! $can_edit ) {
					$data['definition'] = '';
				}

				return $data;
			},
			$entries
		);
	}


	/**
	 * Arguments for GET /terms.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private static function list_args(): array {
		return array(
			'search'   => array(
				'type'    => 'string',
				'default' => '',
			),
			'include'  => array(
				'type'    => 'array',
				'items'   => array( 'type' => 'integer' ),
				'default' => array(),
			),
			'per_page' => array(
				'type'    => 'integer',
				'default' => 10,
				'minimum' => 1,
				'maximum' => self::MAX_PER_PAGE,
			),
		);
	}


	/**
	 * Arguments for creating and updating.
	 *
	 * The definition may be Markdown (the editor popover) or HTML.
	 *
	 * @return array<string, array<string, mixed>>
	 */
	private static function entry_args(): array {
		return array(
			'forms'      => array(
				'type'     => 'array',
				'required' => true,
				'items'    => array(
					'type'       => 'object',
					'properties' => array(
						'term' => array( 'type' => 'string' ),
						'abbr' => array( 'type' => 'string' ),
					),
				),
			),
			'definition' => array( 'type' => 'string' ),
			'slug'       => array( 'type' => 'string' ),
		);
	}
}
