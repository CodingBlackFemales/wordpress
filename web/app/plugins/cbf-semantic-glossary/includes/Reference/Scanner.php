<?php
/**
 * Find inline references in HTML.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Reference;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Scanner.
 *
 * The editor stores a reference as
 * `<span class="glossary-ref" data-glossary-id="42">text</span>`, holding only
 * the entry ID so that every other detail resolves at render time.
 *
 * This walks `<span>` tags with a depth count rather than parsing a DOM, so
 * that everything outside a reference is left byte-for-byte as it was. Content
 * filters run on whole posts, and re-serialising a DOM would rewrite markup
 * (entities, void tags, attribute quoting) that has nothing to do with the
 * glossary.
 */
final class Scanner {

	/**
	 * Class marking a reference.
	 */
	const CLASS_NAME = 'glossary-ref';

	/**
	 * Attribute holding the entry ID.
	 */
	const ID_ATTRIBUTE = 'data-glossary-id';

	/**
	 * Attribute set when the editor forced the abbreviation shape.
	 */
	const ABBR_ATTRIBUTE = 'data-glossary-abbr';

	/**
	 * Opening or closing `<span>` tag.
	 */
	const SPAN_PATTERN = '#<(/?)span\b((?:"[^"]*"|\'[^\']*\'|[^>"\'])*)>#i';

	/**
	 * One attribute, with or without a value.
	 */
	const ATTRIBUTE_PATTERN = '/([^\s=\/>"\']+)(?:\s*=\s*(?:"([^"]*)"|\'([^\']*)\'|([^\s>]+)))?/';


	/**
	 * Every reference in the HTML, in document order.
	 *
	 * @param string $html Markup to scan.
	 * @return Reference[]
	 */
	public static function scan( string $html ): array {
		if ( stripos( $html, self::CLASS_NAME ) === false ) {
			return array();
		}

		preg_match_all( self::SPAN_PATTERN, $html, $tags, PREG_SET_ORDER | PREG_OFFSET_CAPTURE );

		$found = self::pair( $tags );
		usort( $found, fn ( array $a, array $b ): int => $a['start'] <=> $b['start'] );

		$references = array();
		foreach ( $found as $position => $item ) {
			$references[] = self::build( $html, $item, $position );
		}

		return $references;
	}


	/**
	 * Entry IDs referenced, in order of first appearance.
	 *
	 * @param Reference[] $references References.
	 * @return int[]
	 */
	public static function entry_ids( array $references ): array {
		$ids = array();
		foreach ( $references as $reference ) {
			if ( $reference->entry_id > 0 ) {
				$ids[ $reference->entry_id ] = $reference->entry_id;
			}
		}
		return array_values( $ids );
	}


	/**
	 * Replace or unwrap references, leaving the rest of the HTML untouched.
	 *
	 * @param string                  $html         Markup the references were scanned from.
	 * @param array<int, string|null> $replacements Replacement markup keyed by reference position;
	 *                                              null unwraps the reference to its inner HTML.
	 * @param Reference[]             $references   References from scan( $html ).
	 */
	public static function replace( string $html, array $replacements, array $references ): string {
		foreach ( array_reverse( $references ) as $reference ) {
			if ( ! array_key_exists( $reference->position, $replacements ) ) {
				continue;
			}

			$markup = $replacements[ $reference->position ] ?? $reference->inner_html;
			$html   = substr_replace( $html, $markup, $reference->start, $reference->end - $reference->start );
		}

		return $html;
	}


	/**
	 * Plain text of some markup, as a reader would see it.
	 *
	 * @param string $html Markup.
	 */
	public static function text( string $html ): string {
		$text = html_entity_decode( strip_tags( $html ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		return trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
	}


	/**
	 * Match opening and closing tags, keeping the reference spans.
	 *
	 * @param array<int, array<int, array{0:string,1:int}>> $tags Tag matches with offsets.
	 * @return array<int, array{start:int, end:int, inner_start:int, inner_end:int, attributes:array<string,string>}>
	 */
	private static function pair( array $tags ): array {
		$stack = array();
		$found = array();

		foreach ( $tags as $tag ) {
			if ( $tag[1][0] === '' ) {
				$stack[] = array(
					'start'      => $tag[0][1],
					'inner'      => $tag[0][1] + strlen( $tag[0][0] ),
					'attributes' => self::attributes( $tag[2][0] ),
				);
				continue;
			}

			$open = array_pop( $stack );
			if ( $open !== null && self::is_reference( $open['attributes'] ) ) {
				$found[] = array(
					'start'       => $open['start'],
					'end'         => $tag[0][1] + strlen( $tag[0][0] ),
					'inner_start' => $open['inner'],
					'inner_end'   => $tag[0][1],
					'attributes'  => $open['attributes'],
				);
			}
		}

		return $found;
	}


	/**
	 * Build a Reference from a matched span.
	 *
	 * @param string               $html     Markup.
	 * @param array<string, mixed> $item     Matched span.
	 * @param int                  $position Document position.
	 */
	private static function build( string $html, array $item, int $position ): Reference {
		$inner = substr( $html, $item['inner_start'], $item['inner_end'] - $item['inner_start'] );
		$id    = $item['attributes'][ self::ID_ATTRIBUTE ] ?? '';

		return new Reference(
			ctype_digit( $id ) ? (int) $id : 0,
			$item['start'],
			$item['end'],
			$inner,
			self::text( $inner ),
			isset( $item['attributes'][ self::ABBR_ATTRIBUTE ] ) && $item['attributes'][ self::ABBR_ATTRIBUTE ] !== 'false',
			$position
		);
	}


	/**
	 * Whether a span's attributes mark it as a reference.
	 *
	 * @param array<string, string> $attributes Lower-cased attribute names to values.
	 */
	private static function is_reference( array $attributes ): bool {
		$classes = preg_split( '/\s+/', $attributes['class'] ?? '', -1, PREG_SPLIT_NO_EMPTY );
		return in_array( self::CLASS_NAME, (array) $classes, true );
	}


	/**
	 * Parse a tag's attribute string.
	 *
	 * @param string $source Everything between the tag name and `>`.
	 * @return array<string, string> Lower-cased names to decoded values.
	 */
	private static function attributes( string $source ): array {
		preg_match_all( self::ATTRIBUTE_PATTERN, $source, $matches, PREG_SET_ORDER );

		$attributes = array();
		foreach ( $matches as $match ) {
			$value = ( $match[2] ?? '' ) . ( $match[3] ?? '' ) . ( $match[4] ?? '' );

			$attributes[ strtolower( $match[1] ) ] = html_entity_decode( $value, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		}

		return $attributes;
	}
}
