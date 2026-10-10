<?php
/**
 * Find known terms that occur in text without being marked.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Audit;

use CodingBlackFemales\SemanticGlossary\Entry\Entry;
use CodingBlackFemales\SemanticGlossary\Reference\Scanner;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * TermMatcher.
 *
 * Matching rules, kept deliberately conservative because every match is a
 * suggestion an editor has to deal with:
 *
 * - whole words only;
 * - terms match case-insensitively, with an optional plural "s" or "es";
 * - abbreviations match case-sensitively ("IT" is not "it"), unless the
 *   abbreviation is itself lower case ("repo");
 * - forms shorter than two characters never match;
 * - text inside `<pre>`, HTML comments and existing references is skipped.
 *
 * The block editor sidebar asks the REST API for these results rather than
 * re-implementing the rules in JavaScript.
 */
final class TermMatcher {

	/**
	 * Shortest form worth matching.
	 */
	const MIN_LENGTH = 2;

	/**
	 * Not preceded by a word character.
	 */
	const BEFORE = '(?<![\p{L}\p{N}_])';

	/**
	 * Not followed by a word character.
	 */
	const AFTER = '(?![\p{L}\p{N}_])';


	/**
	 * First unmarked occurrence of each candidate entry.
	 *
	 * @param string  $html       Post content.
	 * @param Entry[] $candidates Entries to look for.
	 * @return array<int, array{entry_id:int, text:string, offset:int}> In order of occurrence.
	 */
	public static function find( string $html, array $candidates ): array {
		$text  = self::searchable_text( $html );
		$found = array();

		foreach ( $candidates as $entry ) {
			$match = self::first_match( $text, $entry );
			if ( $match !== null ) {
				$found[] = array( 'entry_id' => $entry->id ) + $match;
			}
		}

		usort( $found, fn ( array $a, array $b ): int => $a['offset'] <=> $b['offset'] );

		return $found;
	}


	/**
	 * The text a reader sees, minus the parts never worth suggesting from.
	 *
	 * Tags become spaces so that adjacent blocks do not run words together.
	 *
	 * @param string $html Post content.
	 */
	public static function searchable_text( string $html ): string {
		$references = Scanner::scan( $html );
		$html       = Scanner::replace( $html, array_fill_keys( array_keys( $references ), ' ' ), $references );
		$html       = (string) preg_replace( array( '/<!--.*?-->/s', '#<pre\b.*?</pre>#is' ), ' ', $html );
		$text       = (string) preg_replace( '/<[^>]*>/', ' ', $html );

		return html_entity_decode( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}


	/**
	 * Earliest occurrence of any of an entry's forms.
	 *
	 * @param string $text  Searchable text.
	 * @param Entry  $entry Entry.
	 * @return array{text:string, offset:int}|null
	 */
	private static function first_match( string $text, Entry $entry ): ?array {
		$best = null;

		foreach ( self::patterns( $entry ) as $pattern ) {
			if ( preg_match( $pattern, $text, $match, PREG_OFFSET_CAPTURE ) && ( $best === null || $match[0][1] < $best['offset'] ) ) {
				$best = array(
					'text'   => $match[0][0],
					'offset' => $match[0][1],
				);
			}
		}

		return $best;
	}


	/**
	 * One pattern per matchable name of an entry.
	 *
	 * @param Entry $entry Entry.
	 * @return string[]
	 */
	private static function patterns( Entry $entry ): array {
		$patterns = array();

		foreach ( $entry->forms as $form ) {
			if ( mb_strlen( $form['term'] ) >= self::MIN_LENGTH ) {
				$patterns[] = '/' . self::BEFORE . self::quote( $form['term'] ) . '(?:e?s)?' . self::AFTER . '/iu';
			}

			if ( mb_strlen( $form['abbr'] ) >= self::MIN_LENGTH ) {
				$flags      = mb_strtolower( $form['abbr'] ) === $form['abbr'] ? 'iu' : 'u';
				$patterns[] = '/' . self::BEFORE . self::quote( $form['abbr'] ) . 's?' . self::AFTER . '/' . $flags;
			}
		}

		return $patterns;
	}


	/**
	 * Quote a name for a pattern, letting any run of whitespace match a space.
	 *
	 * @param string $name Term or abbreviation.
	 */
	private static function quote( string $name ): string {
		return (string) preg_replace( '/\s+/', '\s+', preg_quote( $name, '/' ) );
	}
}
