<?php
/**
 * `wp glossary refs`.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Cli;

use CodingBlackFemales\SemanticGlossary\Entry\Repository;
use CodingBlackFemales\SemanticGlossary\Reference\Index;
use CodingBlackFemales\SemanticGlossary\Reference\Scanner;
use WP_CLI;
use WP_Post;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * List and rebuild references.
 *
 * ## EXAMPLES
 *
 *     wp glossary refs list --post=123
 *     wp glossary refs list --term=version-control-system --format=csv
 *     wp glossary refs reindex --post-type=sfwd-lessons
 */
final class RefsCommand {

	/**
	 * Default columns for `list`.
	 */
	const FIELDS = array( 'post_id', 'post_title', 'entry_id', 'entry', 'text', 'form', 'first', 'inline' );


	/**
	 * List references with the post, the form used, and whether each is the first mention.
	 *
	 * Reads the reference index. Run `wp glossary refs reindex` if content was
	 * changed while the plugin was inactive.
	 *
	 * ## OPTIONS
	 *
	 * [--post=<id>]
	 * : Only references in this post.
	 *
	 * [--term=<id|slug>]
	 * : Only references to this entry.
	 *
	 * [--fields=<fields>]
	 * : Comma-separated fields. Also available: position, render_abbr.
	 *
	 * [--format=<format>]
	 * : Output format. `ids` lists post IDs.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 *   - ids
	 *   - count
	 * ---
	 *
	 * @subcommand list
	 *
	 * @param string[]             $args       Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function list_( array $args, array $assoc_args ): void {
		$where = array( 'post_id' => (int) ( $assoc_args['post'] ?? 0 ) );

		if ( isset( $assoc_args['term'] ) ) {
			$where['entry_id'] = TermCommand::resolve( (string) $assoc_args['term'] )->id;
		}

		$rows    = Index::rows( $where );
		$entries = Repository::instance()->find_many( array_map( 'intval', array_column( $rows, 'entry_id' ) ) );
		$items   = array_map( fn ( object $row ): array => self::item( $row, $entries ), $rows );

		Output::items( $assoc_args, $items, self::FIELDS );
	}


	/**
	 * Rebuild the reference index from post content.
	 *
	 * ## OPTIONS
	 *
	 * [--post=<id>]
	 * : Only this post.
	 *
	 * [--post-type=<type>]
	 * : Only posts of this type. Defaults to every indexed type.
	 *
	 * [--dry-run]
	 * : Report how many references each post has without writing.
	 *
	 * @param string[]             $args       Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function reindex( array $args, array $assoc_args ): void {
		$posts   = Posts::select( $assoc_args );
		$dry_run = ! empty( $assoc_args['dry-run'] );
		$total   = 0;

		foreach ( $posts as $post ) {
			$total += $dry_run ? count( Scanner::scan( $post->post_content ) ) : Index::reindex( $post );
		}

		WP_CLI::success(
			sprintf(
				/* translators: 1: prefix ("Dry run: " or nothing), 2: number of posts, 3: number of references */
				__( '%1$s%2$d posts, %3$d references.', 'cbf-semantic-glossary' ),
				$dry_run ? __( 'Dry run: ', 'cbf-semantic-glossary' ) : '',
				count( $posts ),
				$total
			)
		);
	}


	/**
	 * One index row as an output item.
	 *
	 * @param object                                                         $row     Index row.
	 * @param array<int, \CodingBlackFemales\SemanticGlossary\Entry\Entry>  $entries Entries by ID.
	 * @return array<string, mixed>
	 */
	private static function item( object $row, array $entries ): array {
		$entry = $entries[ (int) $row->entry_id ] ?? null;
		$match = $entry !== null && (int) $row->is_inline === 1 ? $entry->match( (string) $row->ref_text ) : null;
		$post  = get_post( (int) $row->post_id );

		return array(
			'post_id'     => (int) $row->post_id,
			'post_title'  => $post instanceof WP_Post ? $post->post_title : '',
			'entry_id'    => (int) $row->entry_id,
			'entry'       => $entry !== null ? $entry->slug : __( '(deleted)', 'cbf-semantic-glossary' ),
			'text'        => (string) $row->ref_text,
			'form'        => $match !== null ? $match['type'] . ':' . $match['form'] : '',
			'first'       => Output::yes_no( (int) $row->is_first ),
			'inline'      => Output::yes_no( (int) $row->is_inline ),
			'position'    => (int) $row->position,
			'render_abbr' => Output::yes_no( (int) $row->render_abbr ),
		);
	}
}
