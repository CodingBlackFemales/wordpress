<?php
/**
 * Where entries are used, for integrations and the entry screen.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Reference;

use CodingBlackFemales\SemanticGlossary\Entry\Entry;
use CodingBlackFemales\SemanticGlossary\Entry\Repository;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Usage.
 */
final class Usage {

	/**
	 * Entries referenced across some posts, in order of first appearance.
	 *
	 * Backs the public `glossary_get_terms_for_posts()`. Posts are walked in the
	 * order given, so a caller that passes a course's lessons in course order
	 * gets each entry's first appearance in that order. Within a post, inline
	 * references come in document order, then glossary-only entries.
	 *
	 * @param int[] $post_ids Post IDs, in the order that defines "first".
	 * @return array<int, array<string, mixed>> Entry arrays (see Entry::to_array())
	 *                                          plus post_id, text, form and inline.
	 */
	public static function terms_for_posts( array $post_ids ): array {
		$first = array();

		foreach ( Index::rows_for_posts( $post_ids ) as $row ) {
			$first[ (int) $row->entry_id ] ??= $row;
		}

		$entries = Repository::instance()->find_many( array_keys( $first ) );
		$terms   = array();

		foreach ( $first as $entry_id => $row ) {
			$entry = $entries[ $entry_id ] ?? null;
			if ( $entry !== null && $entry->is_published() ) {
				$terms[] = self::describe( $entry, $row );
			}
		}

		return $terms;
	}


	/**
	 * Posts that use an entry, in "first used" order.
	 *
	 * @param int $entry_id Entry ID.
	 * @return array<int, object{post_id:string, ref_text:string, form_index:string|null, is_inline:string}>
	 */
	public static function uses_of( int $entry_id ): array {
		$uses = Index::uses_of( $entry_id );
		$ids  = array_map( 'intval', array_column( $uses, 'post_id' ) );

		/**
		 * Filters the order of the posts that use an entry.
		 *
		 * The first post is shown as "First used in" on the entry screen. Core
		 * orders by post date, then menu order; an integration that knows a
		 * better order (a course's lesson sequence, say) can re-sort the IDs.
		 *
		 * @param int[] $post_ids Post IDs, in default order.
		 * @param int   $entry_id Entry ID.
		 */
		$ordered = array_map( 'intval', (array) apply_filters( 'glossary_first_used_order', $ids, $entry_id ) );

		$by_id = array_combine( $ids, $uses );

		return array_values( array_filter( array_map( fn ( int $id ) => $by_id[ $id ] ?? null, $ordered ) ) );
	}


	/**
	 * One entry's public description.
	 *
	 * @param Entry  $entry Entry.
	 * @param object $row   Its first index row.
	 * @return array<string, mixed>
	 */
	private static function describe( Entry $entry, object $row ): array {
		$inline = (bool) $row->is_inline;
		$match  = $inline ? $entry->match( (string) $row->ref_text ) : null;

		return array_merge(
			$entry->to_array(),
			array(
				'post_id' => (int) $row->post_id,
				'text'    => $inline ? (string) $row->ref_text : null,
				'form'    => $match !== null ? $entry->forms[ $match['form'] ] : null,
				'inline'  => $inline,
			)
		);
	}
}
