<?php
/**
 * The reference index.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Reference;

use CodingBlackFemales\SemanticGlossary\Entry\PostType;
use CodingBlackFemales\SemanticGlossary\Entry\Repository;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Index.
 *
 * A table of which posts reference which entries, rebuilt from a post's
 * content every time it is saved. Rendering never reads it (it scans the
 * content being rendered); it exists for the questions that would otherwise
 * mean scanning every post: usage counts, "Used in N posts", "First used in",
 * `glossary_get_terms_for_posts()` and the WP-CLI reports.
 *
 * Rows for a deleted post are removed with it. Rows left pointing at a post
 * that no longer exists (deleted while the plugin was inactive, or straight
 * from the database) are what `wp glossary term list --orphaned` reports.
 */
final class Index {

	/**
	 * Post statuses that do not count as a use.
	 */
	const IGNORED_STATUSES = array( 'trash', 'auto-draft', 'inherit' );

	/**
	 * Post types never indexed.
	 */
	const SKIPPED_TYPES = array( PostType::NAME, 'revision', 'attachment', 'nav_menu_item', 'customize_changeset', 'oembed_cache', 'user_request', 'wp_global_styles', 'wp_font_family', 'wp_font_face' );


	/**
	 * Register hooks.
	 */
	public static function hooks(): void {
		add_action( 'wp_after_insert_post', array( __CLASS__, 'on_save' ), 10, 2 );
		add_action( 'deleted_post', array( __CLASS__, 'delete_post' ) );
	}


	/**
	 * Index table name for the current site.
	 */
	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'glossary_refs';
	}


	/**
	 * Reindex a post after it is saved.
	 *
	 * Runs on wp_after_insert_post, which in the block editor fires after
	 * REST fields (the post's glossary-only entries) have been saved too.
	 *
	 * @param int     $post_id Post ID.
	 * @param WP_Post $post    Post.
	 */
	public static function on_save( int $post_id, WP_Post $post ): void {
		if ( wp_is_post_revision( $post_id ) || wp_is_post_autosave( $post_id ) || ! self::is_indexed_type( $post->post_type ) ) {
			return;
		}

		self::reindex( $post );
	}


	/**
	 * Whether posts of a type are indexed.
	 *
	 * @param string $post_type Post type.
	 */
	public static function is_indexed_type( string $post_type ): bool {
		return ! in_array( $post_type, self::SKIPPED_TYPES, true );
	}


	/**
	 * Rebuild one post's rows.
	 *
	 * @param WP_Post $post Post.
	 * @return int Number of rows written.
	 */
	public static function reindex( WP_Post $post ): int {
		global $wpdb;

		$rows = IndexRows::build( Scanner::scan( $post->post_content ), PostFields::extra( $post->ID ), Repository::instance() );

		self::delete_post( $post->ID );

		foreach ( $rows as $row ) {
			// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
			$wpdb->insert( self::table(), array_merge( array( 'post_id' => $post->ID ), $row ) );
		}

		wp_cache_set_last_changed( 'cbf_glossary' );

		return count( $rows );
	}


	/**
	 * Remove a post's rows.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function delete_post( int $post_id ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( self::table(), array( 'post_id' => $post_id ), array( '%d' ) );
	}


	/**
	 * How many posts use each entry.
	 *
	 * @param int[] $entry_ids Entry IDs.
	 * @return array<int, int> Entry ID to number of posts; entries with none are absent.
	 */
	public static function usage_counts( array $entry_ids ): array {
		global $wpdb;

		$entry_ids = array_values( array_filter( array_map( 'intval', $entry_ids ) ) );
		if ( $entry_ids === array() ) {
			return array();
		}

		$placeholders = implode( ',', array_fill( 0, count( $entry_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery
		$results = $wpdb->get_results(
			$wpdb->prepare(
				'SELECT r.entry_id, COUNT(DISTINCT r.post_id) AS uses FROM ' . self::table() . ' r ' . self::join_live_posts() . " WHERE r.entry_id IN ($placeholders) GROUP BY r.entry_id",
				$entry_ids
			)
		);
		// phpcs:enable

		return array_map( 'intval', array_column( (array) $results, 'uses', 'entry_id' ) );
	}


	/**
	 * Each post using an entry, with the form it used there.
	 *
	 * One row per post: its first inline reference, or its glossary-only row.
	 *
	 * @param int $entry_id Entry ID.
	 * @return array<int, object{post_id:string, ref_text:string, form_index:string|null, is_inline:string}>
	 */
	public static function uses_of( int $entry_id ): array {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				'SELECT r.post_id, r.ref_text, r.form_index, r.is_inline FROM ' . self::table() . ' r ' . self::join_live_posts() . ' WHERE r.entry_id = %d AND r.is_first = 1 ORDER BY p.post_date ASC, p.menu_order ASC',
				$entry_id
			)
		);
		// phpcs:enable
	}


	/**
	 * Index rows, filtered.
	 *
	 * @param array{post_id?: int, entry_id?: int} $where Filters.
	 * @return array<int, object> Rows with post_id, entry_id, position, ref_text, form_index, is_inline, is_first, render_abbr.
	 */
	public static function rows( array $where = array() ): array {
		global $wpdb;

		$sql    = 'SELECT r.* FROM ' . self::table() . ' r ' . self::join_live_posts() . ' WHERE 1 = 1';
		$params = array();

		foreach ( array( 'post_id', 'entry_id' ) as $column ) {
			if ( ! empty( $where[ $column ] ) ) {
				$sql     .= " AND r.{$column} = %d";
				$params[] = (int) $where[ $column ];
			}
		}

		$sql .= ' ORDER BY r.post_id ASC, r.position ASC';

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		return (array) $wpdb->get_results( $params === array() ? $sql : $wpdb->prepare( $sql, $params ) );
	}


	/**
	 * Rows for several posts, ordered as the posts are given and then by position.
	 *
	 * @param int[] $post_ids Post IDs.
	 * @return array<int, object>
	 */
	public static function rows_for_posts( array $post_ids ): array {
		global $wpdb;

		$post_ids = array_values( array_filter( array_map( 'intval', $post_ids ) ) );
		if ( $post_ids === array() ) {
			return array();
		}

		$placeholders = implode( ',', array_fill( 0, count( $post_ids ), '%d' ) );

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQLPlaceholders.UnfinishedPrepare, WordPress.DB.DirectDatabaseQuery
		return (array) $wpdb->get_results(
			$wpdb->prepare(
				'SELECT * FROM ' . self::table() . " WHERE post_id IN ($placeholders) ORDER BY FIELD(post_id, $placeholders), position ASC",
				array_merge( $post_ids, $post_ids )
			)
		);
		// phpcs:enable
	}


	/**
	 * Entries with at least one row pointing at a post that no longer exists.
	 *
	 * @return int[]
	 */
	public static function orphaned_entry_ids(): array {
		global $wpdb;

		// phpcs:disable WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		$ids = $wpdb->get_col(
			'SELECT DISTINCT r.entry_id FROM ' . self::table() . " r LEFT JOIN {$wpdb->posts} p ON p.ID = r.post_id WHERE p.ID IS NULL"
		);
		// phpcs:enable

		return array_map( 'intval', (array) $ids );
	}


	/**
	 * Posts that reference at least one entry, optionally of one type.
	 *
	 * @param string $post_type Post type, or an empty string for any.
	 * @return int[]
	 */
	public static function post_ids( string $post_type = '' ): array {
		global $wpdb;

		$sql = 'SELECT DISTINCT r.post_id FROM ' . self::table() . ' r ' . self::join_live_posts();

		if ( $post_type !== '' ) {
			$sql = $wpdb->prepare( $sql . ' WHERE p.post_type = %s', $post_type ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
		}

		// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery
		return array_map( 'intval', (array) $wpdb->get_col( $sql . ' ORDER BY r.post_id ASC' ) );
	}


	/**
	 * Join limiting rows to posts that exist and count as a use.
	 */
	private static function join_live_posts(): string {
		global $wpdb;

		$statuses = "'" . implode( "','", array_map( 'esc_sql', self::IGNORED_STATUSES ) ) . "'";

		return "INNER JOIN {$wpdb->posts} p ON p.ID = r.post_id AND p.post_status NOT IN ({$statuses})";
	}
}
