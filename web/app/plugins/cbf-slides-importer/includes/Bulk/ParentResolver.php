<?php
/**
 * Resolves the `parent` titles in a bulk CSV to the post each row belongs under.
 *
 * A topic or quiz names its parent by title so that one file can create a
 * session and the content under it in a single run. The parent is therefore
 * either a session or topic the course already has, or a row earlier in the
 * same file that will have been imported by the time this one runs — rows run
 * one at a time, in file order.
 *
 * An earlier row wins over existing content with the same title: the importer
 * matches posts by title, so that row will reuse, update or create the very
 * post the title refers to, and its outcome is the authority on which.
 *
 * Pure: the caller supplies the course's existing parents, so the matching
 * rules can be tested without WordPress.
 *
 * @class   Bulk\ParentResolver
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Bulk;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ParentResolver class.
 *
 * Sets `parent` on each topic and quiz row that named its parent by title:
 * {
 *   id:    int,     // existing post ID, or 0 when the parent is a row in this file
 *   line:  int,     // the parent row's line, or 0 when the parent already exists
 *   type:  string,  // sfwd-lessons | sfwd-topic
 *   title: string,
 * }
 */
final class ParentResolver {

	/** The row types each row type may be placed under. */
	const ALLOWED = array(
		CsvParser::TYPE_TOPIC => array( CsvParser::TYPE_SESSION ),
		CsvParser::TYPE_QUIZ  => array( CsvParser::TYPE_SESSION, CsvParser::TYPE_TOPIC ),
	);

	/** Post type of the content each row type creates. */
	const POST_TYPES = array(
		CsvParser::TYPE_SESSION => BatchPlanner::POST_TYPE_SESSION,
		CsvParser::TYPE_TOPIC   => BatchPlanner::POST_TYPE_TOPIC,
	);


	/**
	 * Resolve every title-named parent in a planned file.
	 *
	 * @param  array $rows     PlannedRow arrays, in file order.
	 * @param  array $existing The course's sessions and topics, each
	 *                         { id: int, title: string, type: string }.
	 * @return array PlannedRow arrays, with `parent` set or an error added.
	 */
	public static function resolve( array $rows, array $existing ): array {
		$in_course = self::index_existing( $existing );
		$in_file   = self::index_rows( $rows );

		foreach ( $rows as $i => $row ) {
			if ( ( $row['parent_title'] ?? '' ) === '' || ! isset( self::ALLOWED[ $row['type'] ] ) ) {
				continue;
			}

			$result = self::resolve_row( $row, $rows, $in_file, $in_course );

			if ( is_string( $result ) ) {
				$rows[ $i ]['errors'][] = $result;
				$rows[ $i ]['status']   = 'error';
				continue;
			}

			$rows[ $i ]['parent'] = $result;
		}

		return $rows;
	}


	/**
	 * Find one row's parent.
	 *
	 * @param  array $row       The row naming a parent.
	 * @param  array $rows      Every planned row, in file order.
	 * @param  array $in_file   Session and topic row indexes, keyed by title key.
	 * @param  array $in_course Existing parents, keyed by title key.
	 * @return array|string The parent, or the reason it could not be found.
	 */
	private static function resolve_row( array $row, array $rows, array $in_file, array $in_course ): array|string {
		$key     = self::key( $row['parent_title'] );
		$allowed = self::ALLOWED[ $row['type'] ];
		$earlier = array();
		$later   = array();

		foreach ( $in_file[ $key ] ?? array() as $index ) {
			if ( ! in_array( $rows[ $index ]['type'], $allowed, true ) ) {
				continue;
			}

			if ( $rows[ $index ]['line'] < $row['line'] ) {
				$earlier[] = $rows[ $index ];
			} else {
				$later[] = $rows[ $index ];
			}
		}

		if ( count( $earlier ) > 1 ) {
			return sprintf(
				/* translators: 1: parent title, 2: comma-separated line numbers */
				__( 'More than one earlier row is titled "%1$s" (lines %2$s), so the parent is ambiguous. Give them distinct titles.', 'cbf-slides-importer' ),
				$row['parent_title'],
				implode( ', ', array_column( $earlier, 'line' ) )
			);
		}

		if ( $earlier !== array() ) {
			return self::from_row( $earlier[0] );
		}

		$matches = array_values(
			array_filter(
				$in_course[ $key ] ?? array(),
				static fn( array $post ): bool => in_array( $post['type'], array_map( static fn( string $t ): string => self::POST_TYPES[ $t ], $allowed ), true )
			)
		);

		if ( count( $matches ) === 1 ) {
			return array(
				'id'    => (int) $matches[0]['id'],
				'line'  => 0,
				'type'  => $matches[0]['type'],
				'title' => $matches[0]['title'],
			);
		}

		if ( count( $matches ) > 1 ) {
			return sprintf(
				/* translators: 1: parent title, 2: comma-separated post IDs */
				__( 'More than one item in this course is titled "%1$s" (IDs %2$s). Use the post ID in the parent column instead.', 'cbf-slides-importer' ),
				$row['parent_title'],
				implode( ', ', array_column( $matches, 'id' ) )
			);
		}

		if ( $later !== array() ) {
			return sprintf(
				/* translators: 1: parent title, 2: line number */
				__( 'The parent "%1$s" is on line %2$d, after this row. Rows import in order, so move this row below it.', 'cbf-slides-importer' ),
				$row['parent_title'],
				$later[0]['line']
			);
		}

		return $row['type'] === CsvParser::TYPE_QUIZ
			? sprintf(
				/* translators: %s: parent title */
				__( 'No session or topic titled "%s" exists in this course or earlier in this file.', 'cbf-slides-importer' ),
				$row['parent_title']
			)
			: sprintf(
				/* translators: %s: parent title */
				__( 'No session titled "%s" exists in this course or earlier in this file.', 'cbf-slides-importer' ),
				$row['parent_title']
			);
	}


	/**
	 * Describe a parent that is a row in this file.
	 *
	 * Only a parent already rejected at pre-flight is refused here, since
	 * nothing will ever exist to attach to. Whether a ready parent actually
	 * imports is only known once it has run; the batch runner checks that
	 * before queuing the dependent row.
	 *
	 * @param  array $parent The parent row.
	 * @return array|string
	 */
	private static function from_row( array $parent ): array|string {
		if ( ( $parent['status'] ?? '' ) !== 'ready' ) {
			return sprintf(
				/* translators: 1: parent title, 2: line number */
				__( 'The parent "%1$s" on line %2$d cannot be imported, so this row cannot be either.', 'cbf-slides-importer' ),
				$parent['title'],
				$parent['line']
			);
		}

		return array(
			'id'    => 0,
			'line'  => (int) $parent['line'],
			'type'  => self::POST_TYPES[ $parent['type'] ],
			'title' => $parent['title'],
		);
	}


	/**
	 * Index the course's existing parents by title key.
	 *
	 * @param  array $existing { id, title, type } entries.
	 * @return array<string, array>
	 */
	private static function index_existing( array $existing ): array {
		$index = array();

		foreach ( $existing as $post ) {
			$index[ self::key( (string) $post['title'] ) ][] = $post;
		}

		return $index;
	}


	/**
	 * Index the file's session and topic rows by title key.
	 *
	 * @param  array $rows PlannedRow arrays.
	 * @return array<string, int[]> Row indexes.
	 */
	private static function index_rows( array $rows ): array {
		$index = array();

		foreach ( $rows as $i => $row ) {
			if ( isset( self::POST_TYPES[ $row['type'] ] ) && $row['title'] !== '' ) {
				$index[ self::key( $row['title'] ) ][] = $i;
			}
		}

		return $index;
	}


	/**
	 * Normalise a title for matching.
	 *
	 * Case-insensitive, as the importer's own title match is under MySQL's
	 * default collation, and entity-decoded, since stored titles may hold
	 * `&amp;` where the spreadsheet has `&`.
	 *
	 * @param  string $title Title.
	 * @return string
	 */
	public static function key( string $title ): string {
		$title = html_entity_decode( $title, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		return mb_strtolower( trim( (string) preg_replace( '/\s+/u', ' ', $title ) ) );
	}
}
