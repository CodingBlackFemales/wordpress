<?php
/**
 * `wp glossary term`.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Cli;

use CodingBlackFemales\SemanticGlossary\Entry\Entry;
use CodingBlackFemales\SemanticGlossary\Entry\Forms;
use CodingBlackFemales\SemanticGlossary\Entry\Markdown;
use CodingBlackFemales\SemanticGlossary\Entry\Repository;
use CodingBlackFemales\SemanticGlossary\Entry\Search;
use CodingBlackFemales\SemanticGlossary\Entry\Transfer;
use CodingBlackFemales\SemanticGlossary\Reference\Index;
use CodingBlackFemales\SemanticGlossary\Reference\Usage;
use InvalidArgumentException;
use WP_CLI;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Manage glossary entries.
 *
 * ## EXAMPLES
 *
 *     wp glossary term create --term="Version control system" --abbr=VCS --form="Source code management:SCM" --form="Revision control"
 *     wp glossary term list --unused --format=ids
 *     wp glossary term export --file=glossary.json
 */
final class TermCommand {

	/**
	 * Default columns for `list`.
	 */
	const LIST_FIELDS = array( 'id', 'slug', 'term', 'abbr', 'alternatives', 'usage' );

	/**
	 * Default fields for `get`.
	 */
	const GET_FIELDS = array( 'id', 'slug', 'anchor', 'status', 'term', 'abbr', 'alternatives', 'definition', 'usage', 'used_in' );


	/**
	 * List entries.
	 *
	 * ## OPTIONS
	 *
	 * [--search=<text>]
	 * : Only entries with a term or abbreviation containing this text.
	 *
	 * [--unused]
	 * : Only entries no post references.
	 *
	 * [--orphaned]
	 * : Only entries the index still links to posts that no longer exist.
	 *
	 * [--fields=<fields>]
	 * : Comma-separated fields. Also available: anchor, status, definition.
	 *
	 * [--format=<format>]
	 * : Output format.
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
		$entries = Search::rank( Repository::instance()->all_published(), (string) ( $assoc_args['search'] ?? '' ) );
		$usage   = Index::usage_counts( array_map( fn ( Entry $entry ): int => $entry->id, $entries ) );

		if ( ! empty( $assoc_args['unused'] ) ) {
			$entries = array_filter( $entries, fn ( Entry $entry ): bool => empty( $usage[ $entry->id ] ) );
		}

		if ( ! empty( $assoc_args['orphaned'] ) ) {
			$orphaned = array_flip( Index::orphaned_entry_ids() );
			$entries  = array_filter( $entries, fn ( Entry $entry ): bool => isset( $orphaned[ $entry->id ] ) );
		}

		$items = array_map( fn ( Entry $entry ): array => self::row( $entry, $usage[ $entry->id ] ?? 0 ), array_values( $entries ) );

		Output::items( $assoc_args, $items, self::LIST_FIELDS );
	}


	/**
	 * Show one entry: forms, definition, anchor and usage.
	 *
	 * ## OPTIONS
	 *
	 * <entry>
	 * : Entry ID or slug.
	 *
	 * [--fields=<fields>]
	 * : Comma-separated fields.
	 *
	 * [--format=<format>]
	 * : Output format.
	 * ---
	 * default: table
	 * options:
	 *   - table
	 *   - json
	 *   - csv
	 *   - yaml
	 * ---
	 *
	 * @param string[]             $args       Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function get( array $args, array $assoc_args ): void {
		$entry = self::resolve( $args[0] );
		$uses  = Usage::uses_of( $entry->id );
		$item  = array_merge(
			self::row( $entry, count( $uses ) ),
			array( 'used_in' => implode( ',', array_column( $uses, 'post_id' ) ) )
		);

		Output::item( $assoc_args, $item, self::GET_FIELDS );
	}


	/**
	 * Create an entry.
	 *
	 * ## OPTIONS
	 *
	 * --term=<text>
	 * : Canonical term.
	 *
	 * [--abbr=<text>]
	 * : Canonical abbreviation.
	 *
	 * [--definition=<definition>]
	 * : Definition, as Markdown or HTML. Markdown supports `code`, **bold**, *italic* and [links](url).
	 *
	 * [--form=<form>...]
	 * : Alternative form as `term` or `term:abbr`. Repeat for several.
	 *
	 * [--slug=<slug>]
	 * : Slug, which fixes the anchor. Defaults to one made from the term.
	 *
	 * [--porcelain]
	 * : Output just the new entry's ID.
	 *
	 * [--dry-run]
	 * : Validate and report without saving.
	 *
	 * @param string[]             $args       Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function create( array $args, array $assoc_args ): void {
		$forms = array_merge(
			array(
				array(
					'term' => (string) $assoc_args['term'],
					'abbr' => (string) ( $assoc_args['abbr'] ?? '' ),
				),
			),
			Output::values( $assoc_args, 'form' )
		);

		$data = self::data( Forms::normalise( $forms ), $assoc_args );

		if ( ! empty( $assoc_args['dry-run'] ) ) {
			/* translators: %s: term */
			WP_CLI::success( sprintf( __( 'Would create "%s".', 'cbf-semantic-glossary' ), $data['forms'][0]['term'] ) );
			return;
		}

		$id = Output::or_error( Repository::instance()->save( $data ) );

		if ( ! empty( $assoc_args['porcelain'] ) ) {
			WP_CLI::line( (string) $id );
			return;
		}

		/* translators: %d: entry ID */
		WP_CLI::success( sprintf( __( 'Created entry %d.', 'cbf-semantic-glossary' ), $id ) );
	}


	/**
	 * Update an entry.
	 *
	 * ## OPTIONS
	 *
	 * <entry>
	 * : Entry ID or slug.
	 *
	 * [--term=<text>]
	 * : New canonical term. The anchor does not change.
	 *
	 * [--abbr=<text>]
	 * : New canonical abbreviation; empty to remove it.
	 *
	 * [--definition=<definition>]
	 * : New definition, as Markdown or HTML.
	 *
	 * [--form=<form>...]
	 * : Replace every alternative form with these (`term` or `term:abbr`). Repeat for several.
	 *
	 * [--add-form=<form>...]
	 * : Add an alternative form (`term` or `term:abbr`). Repeat for several.
	 *
	 * [--remove-form=<term>...]
	 * : Remove the alternative form with this term. Repeat for several.
	 *
	 * [--slug=<slug>]
	 * : New slug. Changes the anchor, breaking existing links to it.
	 *
	 * [--dry-run]
	 * : Validate and report without saving.
	 *
	 * @param string[]             $args       Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function update( array $args, array $assoc_args ): void {
		$entry = self::resolve( $args[0] );
		$forms = FormEdits::apply( $entry->forms, $assoc_args );
		$data  = self::data( $forms, $assoc_args );

		if ( ! empty( $assoc_args['dry-run'] ) ) {
			/* translators: 1: entry ID, 2: forms */
			WP_CLI::success( sprintf( __( 'Would update entry %1$d: %2$s', 'cbf-semantic-glossary' ), $entry->id, Forms::pack( $forms ) ) );
			return;
		}

		Output::or_error( Repository::instance()->save( $data, $entry->id ) );

		/* translators: %d: entry ID */
		WP_CLI::success( sprintf( __( 'Updated entry %d.', 'cbf-semantic-glossary' ), $entry->id ) );
	}


	/**
	 * Delete entries permanently.
	 *
	 * Refuses entries that posts still reference, unless --force. Forced, those
	 * references render as plain text, as they do for any deleted entry.
	 *
	 * ## OPTIONS
	 *
	 * <entry>...
	 * : Entry IDs or slugs.
	 *
	 * [--force]
	 * : Delete even if posts reference the entry.
	 *
	 * [--dry-run]
	 * : Report what would be deleted.
	 *
	 * @param string[]             $args       Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function delete( array $args, array $assoc_args ): void {
		$entries = array_map( array( self::class, 'resolve' ), $args );
		$usage   = Index::usage_counts( array_map( fn ( Entry $entry ): int => $entry->id, $entries ) );
		$blocked = array_filter( $entries, fn ( Entry $entry ): bool => ! empty( $usage[ $entry->id ] ) );

		if ( $blocked !== array() && empty( $assoc_args['force'] ) ) {
			foreach ( $blocked as $entry ) {
				/* translators: 1: entry slug, 2: number of posts */
				WP_CLI::warning( sprintf( __( '"%1$s" is referenced by %2$d post(s).', 'cbf-semantic-glossary' ), $entry->slug, $usage[ $entry->id ] ) );
			}
			WP_CLI::error( __( 'Nothing deleted. Use --force to delete referenced entries.', 'cbf-semantic-glossary' ) );
		}

		foreach ( $entries as $entry ) {
			self::delete_one( $entry, ! empty( $assoc_args['dry-run'] ) );
		}
	}


	/**
	 * Import entries from JSON or CSV.
	 *
	 * Entries are matched on slug. Existing entries are skipped unless --update.
	 *
	 * ## OPTIONS
	 *
	 * <file>
	 * : JSON or CSV file, as written by `export`.
	 *
	 * [--format=<format>]
	 * : File format. Defaults to the file's extension.
	 * ---
	 * options:
	 *   - json
	 *   - csv
	 * ---
	 *
	 * [--update]
	 * : Overwrite entries that already exist.
	 *
	 * [--dry-run]
	 * : Report what would change without saving.
	 *
	 * @param string[]             $args       Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function import( array $args, array $assoc_args ): void {
		if ( ! is_readable( $args[0] ) ) {
			/* translators: %s: file path */
			WP_CLI::error( sprintf( __( 'Cannot read %s.', 'cbf-semantic-glossary' ), $args[0] ) );
		}

		try {
			$records = Transfer::import( (string) file_get_contents( $args[0] ), (string) ( $assoc_args['format'] ?? Transfer::format_for( $args[0] ) ) ); // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents
		} catch ( InvalidArgumentException $e ) {
			WP_CLI::error( $e->getMessage() );
		}

		$importer = new Importer( ! empty( $assoc_args['update'] ), ! empty( $assoc_args['dry-run'] ) );
		$results  = array_map( array( $importer, 'apply' ), $records );

		Output::items( array( 'format' => 'table' ), $results, array( 'slug', 'action', 'id' ) );
		WP_CLI::success( $importer->summary() );
	}


	/**
	 * Export every entry as JSON or CSV.
	 *
	 * ## OPTIONS
	 *
	 * [--file=<path>]
	 * : Write here instead of to standard output.
	 *
	 * [--format=<format>]
	 * : Export format. Defaults to the file's extension, else JSON.
	 * ---
	 * options:
	 *   - json
	 *   - csv
	 * ---
	 *
	 * @param string[]             $args       Positional arguments.
	 * @param array<string, mixed> $assoc_args Associative arguments.
	 */
	public function export( array $args, array $assoc_args ): void {
		$file   = (string) ( $assoc_args['file'] ?? '' );
		$format = (string) ( $assoc_args['format'] ?? ( $file !== '' ? Transfer::format_for( $file ) : Transfer::JSON ) );
		$output = Transfer::export( Transfer::records( Repository::instance()->all() ), $format );

		if ( $file === '' ) {
			WP_CLI::line( rtrim( $output, "\n" ) );
			return;
		}

		if ( file_put_contents( $file, $output ) === false ) { // phpcs:ignore WordPress.WP.AlternativeFunctions.file_system_operations_file_put_contents
			/* translators: %s: file path */
			WP_CLI::error( sprintf( __( 'Cannot write %s.', 'cbf-semantic-glossary' ), $file ) );
		}

		/* translators: %s: file path */
		WP_CLI::success( sprintf( __( 'Exported to %s.', 'cbf-semantic-glossary' ), $file ) );
	}


	/**
	 * Find an entry or exit with an error.
	 *
	 * @param string $id_or_slug ID or slug.
	 */
	public static function resolve( string $id_or_slug ): Entry {
		$entry = Repository::instance()->find( $id_or_slug );

		if ( $entry === null ) {
			/* translators: %s: entry ID or slug */
			WP_CLI::error( sprintf( __( 'No glossary entry "%s".', 'cbf-semantic-glossary' ), $id_or_slug ) );
		}

		return $entry;
	}


	/**
	 * Delete one entry, or report that it would be.
	 *
	 * @param Entry $entry   Entry.
	 * @param bool  $dry_run Whether to only report.
	 */
	private static function delete_one( Entry $entry, bool $dry_run ): void {
		if ( $dry_run ) {
			/* translators: %s: entry slug */
			WP_CLI::log( sprintf( __( 'Would delete "%s".', 'cbf-semantic-glossary' ), $entry->slug ) );
			return;
		}

		if ( ! Repository::instance()->delete( $entry->id ) ) {
			/* translators: %s: entry slug */
			WP_CLI::error( sprintf( __( 'Could not delete "%s".', 'cbf-semantic-glossary' ), $entry->slug ) );
		}

		/* translators: %s: entry slug */
		WP_CLI::success( sprintf( __( 'Deleted "%s".', 'cbf-semantic-glossary' ), $entry->slug ) );
	}


	/**
	 * Save data from validated forms and the shared options.
	 *
	 * @param array<int, array{term:string,abbr:string}> $forms      Forms.
	 * @param array<string, mixed>                       $assoc_args Associative arguments.
	 * @return array<string, mixed>
	 */
	private static function data( array $forms, array $assoc_args ): array {
		if ( $forms === array() ) {
			WP_CLI::error( __( 'A glossary entry needs a term.', 'cbf-semantic-glossary' ) );
		}

		$data = array( 'forms' => $forms );

		if ( isset( $assoc_args['definition'] ) ) {
			$data['definition'] = Markdown::to_definition( (string) $assoc_args['definition'] );
		}

		if ( isset( $assoc_args['slug'] ) ) {
			$data['slug'] = (string) $assoc_args['slug'];
		}

		return $data;
	}


	/**
	 * One entry as an output row.
	 *
	 * @param Entry $entry Entry.
	 * @param int   $usage Number of posts using it.
	 * @return array<string, mixed>
	 */
	private static function row( Entry $entry, int $usage ): array {
		return array(
			'id'           => $entry->id,
			'slug'         => $entry->slug,
			'anchor'       => '#' . $entry->anchor(),
			'status'       => $entry->status,
			'term'         => $entry->term(),
			'abbr'         => $entry->abbr(),
			'alternatives' => Forms::pack( $entry->alternatives() ),
			'definition'   => $entry->definition,
			'usage'        => $usage,
		);
	}
}
