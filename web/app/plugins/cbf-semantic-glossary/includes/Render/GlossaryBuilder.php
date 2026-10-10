<?php
/**
 * Order a glossary's entries.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Render;

use CodingBlackFemales\SemanticGlossary\Entry\Entry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GlossaryBuilder.
 *
 * Sorts alphabetically by displayed term, which is the abbreviation when there
 * is one, so "VCS" sorts under V. The A–Z index letter comes from the same
 * text, so the index and the order always agree. Initials that are not letters
 * (`.gitignore`, `2FA`) group under "#", ahead of A.
 */
final class GlossaryBuilder {

	/**
	 * Build ordered glossary items.
	 *
	 * Unpublished entries are dropped: a glossary never lists something its
	 * references would not link to.
	 *
	 * @param Entry[] $entries    Entries to list, in any order.
	 * @param int[]   $inline_ids Entries the post marks inline.
	 * @return GlossaryItem[]
	 */
	public static function items( array $entries, array $inline_ids ): array {
		$inline = array_flip( array_map( 'intval', $inline_ids ) );
		$items  = array();

		foreach ( $entries as $entry ) {
			if ( $entry->is_published() ) {
				$items[ $entry->id ] = new GlossaryItem( $entry, self::letter( $entry->display() ), isset( $inline[ $entry->id ] ) );
			}
		}

		$items = array_values( $items );
		usort( $items, array( self::class, 'compare' ) );

		return $items;
	}


	/**
	 * The A–Z index group for a displayed term.
	 *
	 * @param string $display Displayed term.
	 */
	public static function letter( string $display ): string {
		$first = mb_substr( trim( $display ), 0, 1 );
		$ascii = strtoupper( remove_accents( $first ) );

		return preg_match( '/^[A-Z]$/', $ascii ) ? $ascii : GlossaryItem::OTHER;
	}


	/**
	 * Order items: "#" group first, then naturally and case-insensitively.
	 *
	 * @param GlossaryItem $a First.
	 * @param GlossaryItem $b Second.
	 */
	private static function compare( GlossaryItem $a, GlossaryItem $b ): int {
		$group = ( $a->letter !== GlossaryItem::OTHER ) <=> ( $b->letter !== GlossaryItem::OTHER );

		if ( $group !== 0 ) {
			return $group;
		}

		$order = strnatcasecmp( self::sort_key( $a ), self::sort_key( $b ) );

		return $order !== 0 ? $order : $a->entry->id <=> $b->entry->id;
	}


	/**
	 * Comparable form of an item's displayed term.
	 *
	 * @param GlossaryItem $item Item.
	 */
	private static function sort_key( GlossaryItem $item ): string {
		return mb_strtolower( remove_accents( $item->entry->display() ) );
	}
}
