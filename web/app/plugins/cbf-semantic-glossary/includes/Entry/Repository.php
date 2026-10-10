<?php
/**
 * Read and write glossary entries.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Entry;

use WP_Error;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Repository.
 *
 * The only place entries are turned into posts and back. Everything else works
 * with Entry objects.
 */
final class Repository implements EntrySource {

	/**
	 * Per-request cache of published entries.
	 *
	 * @var array<int, Entry>|null
	 */
	private static $published = null;


	/**
	 * A shared instance, for callers that do not need to substitute one.
	 */
	public static function instance(): self {
		static $instance = null;
		$instance      ??= new self();
		return $instance;
	}


	/**
	 * Find an entry by ID or slug.
	 *
	 * @param int|string $id_or_slug Post ID, or slug.
	 */
	public function find( $id_or_slug ): ?Entry {
		$post = is_numeric( $id_or_slug )
			? get_post( (int) $id_or_slug )
			: get_page_by_path( (string) $id_or_slug, OBJECT, PostType::NAME );

		if ( ! $post instanceof WP_Post || $post->post_type !== PostType::NAME ) {
			return null;
		}

		return self::from_post( $post );
	}


	/**
	 * {@inheritDoc}
	 *
	 * @param int[] $ids Entry IDs.
	 * @return array<int, Entry>
	 */
	public function find_many( array $ids ): array {
		$ids = array_values( array_filter( array_unique( array_map( 'intval', $ids ) ) ) );

		if ( $ids === array() ) {
			return array();
		}

		$posts = get_posts(
			array(
				'post_type'        => PostType::NAME,
				'post__in'         => $ids,
				'post_status'      => 'any',
				'posts_per_page'   => -1,
				'orderby'          => 'post__in',
				'suppress_filters' => false,
			)
		);

		return self::key_by_id( array_map( array( self::class, 'from_post' ), $posts ) );
	}


	/**
	 * {@inheritDoc}
	 *
	 * @return array<int, Entry>
	 */
	public function all_published(): array {
		if ( self::$published !== null ) {
			return self::$published;
		}

		$posts = get_posts(
			array(
				'post_type'      => PostType::NAME,
				'post_status'    => 'publish',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		self::$published = self::key_by_id( array_map( array( self::class, 'from_post' ), $posts ) );

		return self::$published;
	}


	/**
	 * Every entry that is not trashed, for export.
	 *
	 * @return array<int, Entry> Keyed by ID.
	 */
	public function all(): array {
		$posts = get_posts(
			array(
				'post_type'      => PostType::NAME,
				'post_status'    => array( 'publish', 'future', 'draft', 'pending', 'private' ),
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
			)
		);

		return self::key_by_id( array_map( array( self::class, 'from_post' ), $posts ) );
	}


	/**
	 * Create or update an entry.
	 *
	 * @param array{forms?: array<int, mixed>, definition?: string, slug?: string, status?: string} $data Entry fields.
	 * @param int                                                                                   $id   Entry to update; 0 to create.
	 * @return int|WP_Error Entry ID.
	 */
	public function save( array $data, int $id = 0 ) {
		$forms = Forms::normalise( $data['forms'] ?? array() );

		if ( $forms === array() ) {
			return new WP_Error( 'glossary_missing_term', __( 'A glossary entry needs a term.', 'cbf-semantic-glossary' ), array( 'status' => 400 ) );
		}

		$postarr = self::postarr( $forms, $data, $id );
		$result  = $id > 0 ? wp_update_post( $postarr, true ) : wp_insert_post( $postarr, true );

		if ( is_wp_error( $result ) ) {
			return $result;
		}

		update_post_meta( (int) $result, PostType::FORMS_META, $forms );
		self::flush();

		return (int) $result;
	}


	/**
	 * Delete an entry permanently.
	 *
	 * @param int $id Entry ID.
	 */
	public function delete( int $id ): bool {
		$deleted = (bool) wp_delete_post( $id, true );
		self::flush();
		return $deleted;
	}


	/**
	 * Forget cached entries after a write.
	 */
	public static function flush(): void {
		self::$published = null;
	}


	/**
	 * Build an Entry from its post.
	 *
	 * An entry saved without forms meta (created some other way) falls back to
	 * its title as the only form.
	 *
	 * @param WP_Post $post Entry post.
	 */
	public static function from_post( WP_Post $post ): Entry {
		$forms = Forms::normalise( (array) get_post_meta( $post->ID, PostType::FORMS_META, true ) );

		if ( $forms === array() ) {
			$forms = Forms::normalise( array( array( 'term' => $post->post_title ) ) );
		}

		return new Entry(
			$post->ID,
			$post->post_name !== '' ? $post->post_name : sanitize_title( $post->post_title ),
			$forms,
			$post->post_content,
			$post->post_status
		);
	}


	/**
	 * Post fields for wp_insert_post() / wp_update_post().
	 *
	 * The slug is only set when given or when creating: renaming the canonical
	 * term must never move the anchor.
	 *
	 * @param array<int, array{term:string,abbr:string}> $forms Normalised forms.
	 * @param array<string, mixed>                       $data  Entry fields.
	 * @param int                                        $id    Entry ID, or 0.
	 * @return array<string, mixed>
	 */
	private static function postarr( array $forms, array $data, int $id ): array {
		$postarr = array(
			'post_type'  => PostType::NAME,
			'post_title' => $forms[0]['term'],
		);

		if ( $id > 0 ) {
			$postarr['ID'] = $id;
		} else {
			$postarr['post_status'] = 'publish';
			$postarr['post_name']   = sanitize_title( $forms[0]['term'] );
		}

		if ( isset( $data['definition'] ) ) {
			$postarr['post_content'] = Definition::sanitise( (string) $data['definition'] );
		}

		if ( ! empty( $data['slug'] ) ) {
			$postarr['post_name'] = sanitize_title( (string) $data['slug'] );
		}

		if ( ! empty( $data['status'] ) ) {
			$postarr['post_status'] = (string) $data['status'];
		}

		return $postarr;
	}


	/**
	 * Key entries by ID.
	 *
	 * @param Entry[] $entries Entries.
	 * @return array<int, Entry>
	 */
	private static function key_by_id( array $entries ): array {
		$keyed = array();
		foreach ( $entries as $entry ) {
			$keyed[ $entry->id ] = $entry;
		}
		return $keyed;
	}
}
