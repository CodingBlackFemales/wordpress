<?php
/**
 * WP-CLI integration
 *
 * @package     CodingBlackFemales/Multisite/Customizations
 * @version     1.0.0
 */

namespace CodingBlackFemales\Multisite\Customizations;

use WP_CLI;
use WP_Post;

if ( ! defined( 'ABSPATH' ) || ! defined( 'WP_CLI' ) ) {
	exit;
}

/**
 * Manages the synced patterns that a theme ships as source files.
 *
 * Theme patterns in a theme's patterns/ folder register themselves, but they
 * are never synced: inserting one copies its blocks. A synced pattern is a
 * wp_block post, so it lives in the database. Themes keep the source of their
 * synced patterns in synced-patterns/*.html, and this command creates or
 * updates the posts from those files.
 *
 * Each file starts with a comment header:
 *
 *     <!--
 *     Title: Learning checkpoint
 *     Slug: cbf-academy/learning-checkpoint
 *     Replaces: Learning Checkpoint
 *     -->
 *
 * `Slug` identifies the pattern from then on. `Replaces` (optional) adopts an
 * existing synced pattern with that title the first time, so every lesson that
 * already references it picks up the new content.
 *
 * A theme pattern in patterns/*.php can take over an unsynced database
 * pattern in the same way, with a `Replaces: <title>` line in its header.
 * Unsynced patterns are copied into posts, so there is no shared content to
 * update: the posts built from the old pattern are pointed at the theme
 * pattern instead, and the old pattern is moved to the bin.
 *
 * ## EXAMPLES
 *
 *     # Preview the changes on the academy site
 *     $ wp cbf patterns sync --dry-run --url=https://academy.codingblackfemales.com
 *
 *     # Create or update the academy's synced patterns
 *     $ wp cbf patterns sync --url=https://academy.codingblackfemales.com
 */
class Patterns_Command extends \WP_CLI_Command {

	/**
	 * Post meta holding a synced pattern's slug.
	 */
	const SLUG_META = '_cbf_pattern_slug';

	/**
	 * Folder in the active theme holding synced pattern sources.
	 */
	const SOURCE_DIR = 'synced-patterns';

	/**
	 * Theme pattern headers read here. WordPress ignores `Replaces`.
	 */
	const THEME_HEADERS = array(
		'slug'     => 'Slug',
		'replaces' => 'Replaces',
	);

	/**
	 * Post meta left behind by Otter Blocks' custom CSS, which the new content
	 * no longer uses.
	 */
	const STALE_META = array( '_themeisle_gutenberg_block_styles', '_themeisle_gutenberg_block_stylesheet' );

	/**
	 * Creates or updates the synced patterns defined by the active theme.
	 *
	 * Patterns go into the pattern category named after the theme's prefix
	 * (cbf-academy/… goes into "cbf-academy"), so they appear beside the
	 * theme's own patterns in the inserter.
	 *
	 * Then replaces any unsynced database pattern that a theme pattern names
	 * in its `Replaces` header: posts built from it are pointed at the theme
	 * pattern, and it is moved to the bin. Their content is not changed.
	 *
	 * ## OPTIONS
	 *
	 * [--dry-run]
	 * : Report what would change without saving anything.
	 *
	 * ## EXAMPLES
	 *
	 *     $ wp cbf patterns sync --url=https://academy.codingblackfemales.com
	 *     Updated: Learning checkpoint (5916)
	 *     Created: Break (7426)
	 *     Replaced: OS tabs (6101) with cbf-academy/os-tabs in 10 posts
	 *     Success: 1 created, 1 updated, 0 unchanged, 1 replaced.
	 *
	 * @param string[]             $args       Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function sync( $args, $assoc_args ) {
		$dry_run = ! empty( $assoc_args['dry-run'] );
		$files   = glob( get_stylesheet_directory() . '/' . self::SOURCE_DIR . '/*.html' );

		if ( empty( $files ) ) {
			WP_CLI::error( sprintf( 'The active theme has no %s/*.html files.', self::SOURCE_DIR ) );
		}

		$counts = array(
			'created'   => 0,
			'updated'   => 0,
			'unchanged' => 0,
		);

		foreach ( $files as $file ) {
			$pattern = self::read( $file );
			$post    = self::find( $pattern );
			$result  = self::save( $pattern, $post, $dry_run );

			++$counts[ $result ];
			WP_CLI::log( sprintf( '%s%s: %s%s', $dry_run ? 'Would be ' : '', ucfirst( $result ), $pattern['title'], $post ? " ({$post->ID})" : '' ) );
		}

		$replaced = self::replace_database_patterns( $dry_run );

		WP_CLI::success( sprintf( '%s%d created, %d updated, %d unchanged, %d replaced.', $dry_run ? 'Dry run: ' : '', $counts['created'], $counts['updated'], $counts['unchanged'], $replaced ) );
	}

	/**
	 * Replaces the unsynced database patterns that theme patterns take over.
	 *
	 * @param bool $dry_run Whether to only report.
	 * @return int Number of patterns replaced.
	 */
	private static function replace_database_patterns( $dry_run ) {
		$replaced = 0;

		foreach ( glob( get_stylesheet_directory() . '/patterns/*.php' ) as $file ) {
			$header = get_file_data( $file, self::THEME_HEADERS );

			if ( $header['replaces'] !== '' && $header['slug'] !== '' ) {
				$replaced += self::replace_database_pattern( $header['replaces'], $header['slug'], $dry_run );
				$replaced += self::replace_deleted_pattern( $header['replaces'], $header['slug'], $dry_run );
			}
		}

		return $replaced;
	}

	/**
	 * Replaces an unsynced database pattern with a theme pattern.
	 *
	 * @param string $title   Database pattern title.
	 * @param string $slug    Theme pattern slug.
	 * @param bool   $dry_run Whether to only report.
	 * @return int 1 if the pattern was replaced, else 0.
	 */
	private static function replace_database_pattern( $title, $slug, $dry_run ) {
		$old = self::find_unsynced( $title );

		if ( ! $old ) {
			return 0;
		}

		$posts = self::posts_built_from( $old->ID );

		if ( ! $dry_run ) {
			foreach ( $posts as $post_id ) {
				self::replace_in_post( $post_id, self::pattern_marker( "core/block/{$old->ID}" ), self::pattern_marker( $slug ) );
			}
			wp_trash_post( $old->ID );
		}

		WP_CLI::log( sprintf( '%sReplaced: %s (%d) with %s in %d posts', $dry_run ? 'Would be ' : '', $old->post_title, $old->ID, $slug, count( $posts ) ) );

		return 1;
	}

	/**
	 * Points blocks inserted from a database pattern that no longer exists at
	 * a theme pattern.
	 *
	 * Such blocks can only be recognised by the pattern name in their
	 * metadata. A reference is left alone if its ID is still a pattern on
	 * this site.
	 *
	 * @param string $title   Deleted pattern's title.
	 * @param string $slug    Theme pattern slug.
	 * @param bool   $dry_run Whether to only report.
	 * @return int 1 if any blocks were found, else 0.
	 */
	private static function replace_deleted_pattern( $title, $slug, $dry_run ) {
		$references = self::deleted_pattern_references( $title );

		if ( ! $references ) {
			return 0;
		}

		if ( ! $dry_run ) {
			foreach ( $references as $post_id => $pattern_ids ) {
				foreach ( $pattern_ids as $pattern_id ) {
					self::replace_in_post( $post_id, self::pattern_marker( "core/block/{$pattern_id}", $title ), self::pattern_marker( $slug, $title ) );
				}
			}
		}

		$ids = array_unique( array_merge( ...array_values( $references ) ) );
		WP_CLI::log( sprintf( '%sReplaced: %s (deleted pattern %s) with %s in %d posts', $dry_run ? 'Would be ' : '', $title, implode( ', ', $ids ), $slug, count( $references ) ) );

		return 1;
	}

	/**
	 * Blocks inserted from a deleted database pattern with a title.
	 *
	 * @param string $title Pattern title.
	 * @return array<int, int[]> Pattern IDs referenced, by post ID.
	 */
	private static function deleted_pattern_references( $title ) {
		global $wpdb;

		$name  = self::json_string( $title );
		$like  = '%' . $wpdb->esc_like( '"name":"' . $name . '"' ) . '%';
		$regex = '/"patternName":"core\/block\/(\d+)","name":"' . preg_quote( $name, '/' ) . '"/';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- A one-off search of post content.
		$posts = $wpdb->get_results( $wpdb->prepare( "SELECT ID, post_content FROM {$wpdb->posts} WHERE post_type <> 'revision' AND post_content LIKE %s", $like ) );

		$references = array();
		foreach ( $posts as $post ) {
			preg_match_all( $regex, $post->post_content, $matches );
			$deleted = array_filter( array_unique( array_map( 'intval', $matches[1] ) ), fn( $id ) => get_post_type( $id ) !== 'wp_block' );

			if ( $deleted ) {
				$references[ (int) $post->ID ] = array_values( $deleted );
			}
		}

		return $references;
	}

	/**
	 * The unsynced database pattern with a title, unless it is in the bin.
	 *
	 * @param string $title Pattern title.
	 * @return WP_Post|null
	 */
	private static function find_unsynced( $title ) {
		$posts = get_posts(
			array(
				'post_type'   => 'wp_block',
				'post_status' => array( 'publish', 'draft', 'pending', 'private', 'future' ),
				'title'       => $title,
				'meta_key'    => 'wp_pattern_sync_status', // phpcs:ignore WordPress.DB.SlowDBQuery -- A handful of wp_block posts.
				'meta_value'  => 'unsynced', // phpcs:ignore WordPress.DB.SlowDBQuery
				'numberposts' => 1,
			)
		);

		return $posts ? $posts[0] : null;
	}

	/**
	 * Posts holding blocks inserted from a database pattern.
	 *
	 * @param int $pattern_id Pattern post ID.
	 * @return int[]
	 */
	private static function posts_built_from( $pattern_id ) {
		global $wpdb;

		$marker = '%' . $wpdb->esc_like( self::pattern_marker( "core/block/{$pattern_id}" ) ) . '%';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- A one-off search of post content.
		return array_map( 'intval', $wpdb->get_col( $wpdb->prepare( "SELECT ID FROM {$wpdb->posts} WHERE post_type <> 'revision' AND post_content LIKE %s", $marker ) ) );
	}

	/**
	 * Replaces a pattern reference in a post's block metadata.
	 *
	 * Only the patternName changes. It is written directly, so no content
	 * filter, revision or modified date is involved.
	 *
	 * @param int    $post_id Post ID.
	 * @param string $from    Marker to replace.
	 * @param string $to      Replacement marker.
	 */
	private static function replace_in_post( $post_id, $from, $to ) {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery -- See the method description.
		$wpdb->query( $wpdb->prepare( "UPDATE {$wpdb->posts} SET post_content = REPLACE( post_content, %s, %s ) WHERE ID = %d", $from, $to, $post_id ) );
		clean_post_cache( $post_id );
	}

	/**
	 * How block metadata records the pattern a block was inserted from, and
	 * optionally the block's name, which follows it.
	 *
	 * @param string $pattern Pattern name, such as core/block/6101.
	 * @param string $name    Block name, such as the pattern's title.
	 * @return string
	 */
	private static function pattern_marker( $pattern, $name = '' ) {
		return '"patternName":"' . $pattern . '"' . ( $name !== '' ? ',"name":"' . self::json_string( $name ) . '"' : '' );
	}

	/**
	 * A string as block attributes encode it, without the quotes.
	 *
	 * @param string $text Text.
	 * @return string
	 */
	private static function json_string( $text ) {
		return substr( serialize_block_attributes( array( $text ) ), 2, -2 );
	}

	/**
	 * Reads a synced pattern source file.
	 *
	 * @param string $file Path to the file.
	 * @return array{title: string, slug: string, replaces: string, content: string}
	 */
	private static function read( $file ) {
		$raw = (string) file_get_contents( $file ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents -- A local theme file.

		if ( ! preg_match( '/^\s*<!--(.*?)-->/s', $raw, $header ) ) {
			WP_CLI::error( sprintf( '%s has no header comment.', basename( $file ) ) );
		}

		$fields = array();
		foreach ( preg_split( '/\R/', trim( $header[1] ) ) as $line ) {
			if ( preg_match( '/^\s*([A-Za-z ]+):\s*(.+?)\s*$/', $line, $match ) ) {
				$fields[ strtolower( $match[1] ) ] = $match[2];
			}
		}

		if ( empty( $fields['title'] ) || empty( $fields['slug'] ) ) {
			WP_CLI::error( sprintf( '%s needs a Title and a Slug in its header.', basename( $file ) ) );
		}

		return array(
			'title'    => $fields['title'],
			'slug'     => $fields['slug'],
			'replaces' => $fields['replaces'] ?? '',
			'content'  => trim( substr( $raw, strlen( $header[0] ) ) ),
		);
	}

	/**
	 * The existing post for a pattern: by slug, else the pattern it replaces.
	 *
	 * @param array{title: string, slug: string, replaces: string, content: string} $pattern Pattern.
	 */
	private static function find( $pattern ) {
		$query = array(
			'post_type'   => 'wp_block',
			'post_status' => 'any',
			'numberposts' => 1,
		);

		$by_slug = array(
			'meta_key'   => self::SLUG_META, // phpcs:ignore WordPress.DB.SlowDBQuery -- A handful of wp_block posts.
			'meta_value' => $pattern['slug'], // phpcs:ignore WordPress.DB.SlowDBQuery
		);

		$posts = get_posts( array_merge( $query, $by_slug ) );

		if ( ! $posts && $pattern['replaces'] !== '' ) {
			$posts = get_posts( array_merge( $query, array( 'title' => $pattern['replaces'] ) ) );
		}

		return $posts ? $posts[0] : null;
	}

	/**
	 * Creates or updates a pattern's post.
	 *
	 * @param array{title: string, slug: string, replaces: string, content: string} $pattern Pattern.
	 * @param WP_Post|null                                                           $post    Existing post, if any.
	 * @param bool                                                                   $dry_run Whether to only report.
	 * @return string created, updated or unchanged.
	 */
	private static function save( $pattern, $post, $dry_run ) {
		$result = self::status( $pattern, $post );

		if ( $dry_run || $result === 'unchanged' ) {
			return $result;
		}

		$id = wp_insert_post(
			wp_slash(
				array(
					'ID'           => $post ? $post->ID : 0,
					'post_type'    => 'wp_block',
					'post_status'  => 'publish',
					'post_title'   => $pattern['title'],
					'post_content' => $pattern['content'],
				)
			),
			true
		);

		if ( is_wp_error( $id ) ) {
			WP_CLI::error( $id );
		}

		update_post_meta( $id, self::SLUG_META, $pattern['slug'] );
		delete_post_meta( $id, 'wp_pattern_sync_status' );

		foreach ( self::STALE_META as $key ) {
			delete_post_meta( $id, $key );
		}

		self::categorise( $id, $pattern['slug'] );

		return $result;
	}

	/**
	 * What saving a pattern would do.
	 *
	 * @param array{title: string, slug: string, replaces: string, content: string} $pattern Pattern.
	 * @param WP_Post|null                                                           $post    Existing post, if any.
	 * @return string created, updated or unchanged.
	 */
	private static function status( $pattern, $post ) {
		if ( ! $post ) {
			return 'created';
		}

		$same = $post->post_title === $pattern['title']
			&& trim( $post->post_content ) === $pattern['content']
			&& get_post_meta( $post->ID, self::SLUG_META, true ) === $pattern['slug'];

		return $same ? 'unchanged' : 'updated';
	}

	/**
	 * Files a pattern under the category named by its slug's prefix.
	 *
	 * @param int    $id   Post ID.
	 * @param string $slug Pattern slug, such as cbf-academy/learning-checkpoint.
	 */
	private static function categorise( $id, $slug ) {
		$category = strtok( $slug, '/' );
		$term     = get_term_by( 'slug', $category, 'wp_pattern_category' );

		if ( ! $term ) {
			$registered = \WP_Block_Pattern_Categories_Registry::get_instance()->get_registered( $category );
			$created    = wp_insert_term( $registered['label'] ?? $category, 'wp_pattern_category', array( 'slug' => $category ) );

			if ( is_wp_error( $created ) ) {
				WP_CLI::error( $created );
			}

			$term_id = $created['term_id'];
		} else {
			$term_id = $term->term_id;
		}

		wp_set_object_terms( $id, array( (int) $term_id ), 'wp_pattern_category' );
	}

	/**
	 * Registers the command.
	 */
	public static function hooks() {
		WP_CLI::add_command( 'cbf patterns', self::class );
	}
}
