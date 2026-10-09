<?php
/**
 * Work out a post's rows in the reference index.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Reference;

use CodingBlackFemales\SemanticGlossary\Entry\Entry;
use CodingBlackFemales\SemanticGlossary\Entry\EntrySource;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * IndexRows.
 */
final class IndexRows {

	/**
	 * Index rows for one post.
	 *
	 * Every inline reference gets a row, duplicates and dead ones included, so
	 * the audit and `wp glossary refs list` can see them. Entries added to the
	 * post's glossary without an inline reference get a row with is_inline = 0,
	 * unless they are also marked inline.
	 *
	 * @param Reference[] $references Inline references, in document order.
	 * @param int[]       $extra_ids  Entries listed without an inline reference.
	 * @param EntrySource $source     Entry lookup, to record which form was used.
	 * @return array<int, array{entry_id:int, position:int, ref_text:string, form_index:int|null, is_inline:int, is_first:int, render_abbr:int}>
	 */
	public static function build( array $references, array $extra_ids, EntrySource $source ): array {
		$entries = $source->find_many( array_merge( Scanner::entry_ids( $references ), $extra_ids ) );
		$rows    = array();
		$seen    = array();

		foreach ( $references as $reference ) {
			if ( $reference->entry_id !== 0 ) {
				$rows[]                       = self::inline_row( $reference, $entries[ $reference->entry_id ] ?? null, ! isset( $seen[ $reference->entry_id ] ) );
				$seen[ $reference->entry_id ] = true;
			}
		}

		foreach ( array_values( array_unique( array_map( 'intval', $extra_ids ) ) ) as $offset => $entry_id ) {
			if ( $entry_id > 0 && ! isset( $seen[ $entry_id ] ) ) {
				$rows[] = self::extra_row( $entry_id, count( $references ) + $offset );
			}
		}

		return $rows;
	}


	/**
	 * Row for an inline reference.
	 *
	 * @param Reference  $reference The reference.
	 * @param Entry|null $entry     Its entry, if it exists.
	 * @param bool       $is_first  Whether it is the entry's first reference in the post.
	 * @return array{entry_id:int, position:int, ref_text:string, form_index:int|null, is_inline:int, is_first:int, render_abbr:int}
	 */
	private static function inline_row( Reference $reference, ?Entry $entry, bool $is_first ): array {
		$match = $entry?->match( $reference->text );

		return array(
			'entry_id'    => $reference->entry_id,
			'position'    => $reference->position,
			'ref_text'    => mb_substr( $reference->text, 0, 255 ),
			'form_index'  => $match['form'] ?? null,
			'is_inline'   => 1,
			'is_first'    => (int) $is_first,
			'render_abbr' => (int) $reference->render_abbr,
		);
	}


	/**
	 * Row for an entry listed in the glossary without an inline reference.
	 *
	 * @param int $entry_id Entry ID.
	 * @param int $position Position after every inline reference.
	 * @return array{entry_id:int, position:int, ref_text:string, form_index:null, is_inline:int, is_first:int, render_abbr:int}
	 */
	private static function extra_row( int $entry_id, int $position ): array {
		return array(
			'entry_id'    => $entry_id,
			'position'    => $position,
			'ref_text'    => '',
			'form_index'  => null,
			'is_inline'   => 0,
			'is_first'    => 1,
			'render_abbr' => 0,
		);
	}
}
