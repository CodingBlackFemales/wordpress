<?php
/**
 * Render inline references.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Render;

use CodingBlackFemales\SemanticGlossary\Entry\Entry;
use CodingBlackFemales\SemanticGlossary\Entry\EntrySource;
use CodingBlackFemales\SemanticGlossary\Reference\Reference;
use CodingBlackFemales\SemanticGlossary\Reference\Scanner;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * InlineRenderer.
 *
 * Turns the editor's stored spans into links to the glossary:
 *
 * - text matching one of the entry's abbreviations, or forced to render as
 *   one, becomes `<a href="#dfn-{slug}"><abbr title="{term}">t</abbr></a>`;
 * - anything else becomes `<a href="#dfn-{slug}">t</a>`;
 * - only the first reference to an entry is marked; later ones are plain text;
 * - a reference to a deleted or unpublished entry is plain text, never a dead link.
 */
final class InlineRenderer {

	/**
	 * Render every reference in some HTML.
	 *
	 * @param string      $html   Post content, already through do_blocks().
	 * @param EntrySource $source Where to look entries up.
	 */
	public static function render( string $html, EntrySource $source ): string {
		$references = Scanner::scan( $html );

		if ( $references === array() ) {
			return $html;
		}

		$entries      = $source->find_many( Scanner::entry_ids( $references ) );
		$replacements = array();
		$seen         = array();

		foreach ( $references as $reference ) {
			$entry = $entries[ $reference->entry_id ] ?? null;

			if ( $entry === null || ! $entry->is_published() || isset( $seen[ $entry->id ] ) ) {
				$replacements[ $reference->position ] = null;
				continue;
			}

			$seen[ $entry->id ]                   = true;
			$replacements[ $reference->position ] = self::markup( $entry, $reference );
		}

		return Scanner::replace( $html, $replacements, $references );
	}


	/**
	 * Markup for the first reference to an entry.
	 *
	 * The reference carries `id="ref-{slug}"` so the glossary can link back to
	 * it.
	 *
	 * @param Entry     $entry     Referenced entry.
	 * @param Reference $reference The reference.
	 */
	public static function markup( Entry $entry, Reference $reference ): string {
		$inner = $reference->inner_html;

		if ( $reference->render_abbr || $entry->is_abbreviation( $reference->text ) ) {
			$inner = '<abbr title="' . esc_attr( $entry->expansion_for( $reference->text ) ) . '">' . $inner . '</abbr>';
		}

		/**
		 * Filters where a reference links to.
		 *
		 * Defaults to the definition on the same page. An integration that shows
		 * the glossary elsewhere (a course-wide glossary, say) can point here.
		 *
		 * @param string    $href      Link target.
		 * @param Entry     $entry     Referenced entry.
		 * @param Reference $reference The reference.
		 */
		$href = apply_filters( 'glossary_reference_href', '#' . $entry->anchor(), $entry, $reference );

		$html = sprintf(
			'<a href="%1$s" id="%2$s" class="glossary-ref">%3$s</a>',
			str_starts_with( (string) $href, '#' ) ? esc_attr( $href ) : esc_url( $href ),
			esc_attr( $entry->ref_anchor() ),
			$inner
		);

		/**
		 * Filters the markup of a rendered inline reference.
		 *
		 * @param string    $html      Reference markup.
		 * @param Entry     $entry     Referenced entry.
		 * @param Reference $reference The reference.
		 */
		return apply_filters( 'glossary_reference_html', $html, $entry, $reference );
	}
}
