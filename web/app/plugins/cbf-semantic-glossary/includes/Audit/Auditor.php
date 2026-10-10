<?php
/**
 * Check a post's references.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Audit;

use CodingBlackFemales\SemanticGlossary\Entry\EntrySource;
use CodingBlackFemales\SemanticGlossary\Reference\Reference;
use CodingBlackFemales\SemanticGlossary\Reference\Scanner;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Auditor.
 *
 * Four kinds of finding:
 *
 * - duplicate: an entry marked again after its first mention. Renders as plain
 *   text, but is reported rather than silently stripped;
 * - dead: a reference to an entry that is deleted or trashed;
 * - unpublished: a reference to an entry that exists but is not published
 *   yet, e.g. a draft in review. Renders as plain text until it is published;
 * - unmarked: a known entry that occurs in the post without being marked.
 *
 * Duplicates and dead references are fixable mechanically. Unpublished references
 * are left alone (they start working once the entry is approved), and unmarked
 * terms are an editorial decision, so fix() never touches either.
 */
final class Auditor {

	const DUPLICATE   = 'duplicate';
	const DEAD        = 'dead';
	const UNPUBLISHED = 'unpublished';
	const UNMARKED    = 'unmarked';

	/**
	 * Finding types fix() removes.
	 */
	const FIXABLE = array( self::DUPLICATE, self::DEAD );


	/**
	 * Audit some content.
	 *
	 * @param string      $html        Post content.
	 * @param EntrySource $source      Entry lookup.
	 * @param int[]       $ignored_ids Entries not to report as unmarked.
	 * @return array<int, array{type:string, entry_id:int, text:string, position:int|null}>
	 */
	public static function audit( string $html, EntrySource $source, array $ignored_ids = array() ): array {
		$references = Scanner::scan( $html );
		$entries    = $source->find_many( Scanner::entry_ids( $references ) );

		return array_merge(
			self::reference_findings( $references, $entries ),
			self::unmarked( $html, $source, Scanner::entry_ids( $references ), $ignored_ids )
		);
	}


	/**
	 * Remove duplicate and dead references, keeping their text.
	 *
	 * @param string      $html   Post content.
	 * @param EntrySource $source Entry lookup.
	 * @return array{html:string, fixed:int}
	 */
	public static function fix( string $html, EntrySource $source ): array {
		$references = Scanner::scan( $html );
		$findings   = self::reference_findings( $references, $source->find_many( Scanner::entry_ids( $references ) ) );
		$fixable    = array_filter( $findings, fn ( array $finding ): bool => in_array( $finding['type'], self::FIXABLE, true ) );
		$positions  = array_column( $fixable, 'position' );

		return array(
			'html'  => Scanner::replace( $html, array_fill_keys( $positions, null ), $references ),
			'fixed' => count( $positions ),
		);
	}


	/**
	 * Whether any finding can be fixed by fix().
	 *
	 * @param array<int, array{type:string}> $findings Findings from audit().
	 */
	public static function has_fixable( array $findings ): bool {
		return array_filter( $findings, fn ( array $finding ): bool => in_array( $finding['type'], self::FIXABLE, true ) ) !== array();
	}


	/**
	 * Duplicate, dead and unpublished references.
	 *
	 * A duplicate of a dead or unpublished reference is reported as dead or unpublished
	 * only once; later ones are duplicates.
	 *
	 * @param Reference[]                                                     $references References in document order.
	 * @param array<int, \CodingBlackFemales\SemanticGlossary\Entry\Entry>    $entries    Entries found, keyed by ID.
	 * @return array<int, array{type:string, entry_id:int, text:string, position:int}>
	 */
	private static function reference_findings( array $references, array $entries ): array {
		$findings = array();
		$seen     = array();

		foreach ( $references as $reference ) {
			$type = self::reference_type( $entries[ $reference->entry_id ] ?? null, isset( $seen[ $reference->entry_id ] ) );

			$seen[ $reference->entry_id ] = true;

			if ( $type !== null ) {
				$findings[] = array(
					'type'     => $type,
					'entry_id' => $reference->entry_id,
					'text'     => $reference->text,
					'position' => $reference->position,
				);
			}
		}

		return $findings;
	}


	/**
	 * What, if anything, is wrong with one reference.
	 *
	 * @param \CodingBlackFemales\SemanticGlossary\Entry\Entry|null $entry The referenced entry, if it exists.
	 * @param bool                                                    $seen  Whether the entry was referenced earlier.
	 * @return string|null Finding type, or null when the reference is fine.
	 */
	private static function reference_type( $entry, bool $seen ): ?string {
		if ( $seen ) {
			return self::DUPLICATE;
		}

		if ( $entry === null ) {
			return self::DEAD;
		}

		return $entry->is_published() ? null : self::UNPUBLISHED;
	}


	/**
	 * Known entries that occur but are not marked.
	 *
	 * @param string      $html        Post content.
	 * @param EntrySource $source      Entry lookup.
	 * @param int[]       $marked_ids  Entries already marked.
	 * @param int[]       $ignored_ids Entries not to report.
	 * @return array<int, array{type:string, entry_id:int, text:string, position:null}>
	 */
	private static function unmarked( string $html, EntrySource $source, array $marked_ids, array $ignored_ids ): array {
		$skip       = array_flip( array_merge( $marked_ids, array_map( 'intval', $ignored_ids ) ) );
		$candidates = array_diff_key( $source->all_published(), $skip );

		return array_map(
			fn ( array $match ): array => array(
				'type'     => self::UNMARKED,
				'entry_id' => $match['entry_id'],
				'text'     => $match['text'],
				'position' => null,
			),
			TermMatcher::find( $html, $candidates )
		);
	}
}
