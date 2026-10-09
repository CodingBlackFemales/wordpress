<?php
/**
 * Normalise and convert an entry's name pairs.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Entry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Forms helper.
 *
 * A form is one `{ term, abbr }` pair. The first is canonical. Every way a
 * form enters the plugin (the edit screen, REST, WP-CLI, imports) goes through
 * normalise(), so the rest of the code can rely on its shape.
 */
final class Forms {

	/**
	 * Separator between term and abbreviation in the `term:abbr` shorthand.
	 */
	const PAIR_SEPARATOR = ':';

	/**
	 * Separator between forms when several are packed into one CSV cell.
	 */
	const LIST_SEPARATOR = '|';


	/**
	 * Clean a list of forms.
	 *
	 * Accepts pairs as arrays or as `term:abbr` strings. Forms with no term
	 * are dropped (an abbreviation alone names nothing), and exact repeats are
	 * collapsed, keeping the first.
	 *
	 * @param array<int, mixed> $raw Forms in any accepted shape.
	 * @return array<int, array{term:string,abbr:string}>
	 */
	public static function normalise( array $raw ): array {
		$forms = array();
		$seen  = array();

		foreach ( $raw as $item ) {
			$form = is_string( $item ) ? self::parse_pair( $item ) : self::from_array( $item );
			$key  = mb_strtolower( $form['term'] . "\0" . $form['abbr'] );

			if ( $form['term'] === '' || isset( $seen[ $key ] ) ) {
				continue;
			}

			$seen[ $key ] = true;
			$forms[]      = $form;
		}

		return $forms;
	}


	/**
	 * Parse the `term:abbr` shorthand used by WP-CLI and CSV.
	 *
	 * Splits on the last separator, so a term may itself contain one.
	 *
	 * @param string $pair Shorthand such as "Source code management:SCM".
	 * @return array{term:string,abbr:string}
	 */
	public static function parse_pair( string $pair ): array {
		$at = strrpos( $pair, self::PAIR_SEPARATOR );

		if ( $at === false ) {
			return array(
				'term' => self::clean( $pair ),
				'abbr' => '',
			);
		}

		return array(
			'term' => self::clean( substr( $pair, 0, $at ) ),
			'abbr' => self::clean( substr( $pair, $at + 1 ) ),
		);
	}


	/**
	 * The `term:abbr` shorthand for a form; the inverse of parse_pair().
	 *
	 * @param array{term:string,abbr:string} $form Form.
	 */
	public static function to_pair( array $form ): string {
		return $form['abbr'] === '' ? $form['term'] : $form['term'] . self::PAIR_SEPARATOR . $form['abbr'];
	}


	/**
	 * Parse several forms packed into one string with LIST_SEPARATOR.
	 *
	 * @param string $packed E.g. "Source code management:SCM|Revision control".
	 * @return array<int, array{term:string,abbr:string}>
	 */
	public static function unpack( string $packed ): array {
		if ( trim( $packed ) === '' ) {
			return array();
		}

		return self::normalise( explode( self::LIST_SEPARATOR, $packed ) );
	}


	/**
	 * Pack forms into one string; the inverse of unpack().
	 *
	 * @param array<int, array{term:string,abbr:string}> $forms Forms.
	 */
	public static function pack( array $forms ): string {
		return implode( self::LIST_SEPARATOR, array_map( array( self::class, 'to_pair' ), $forms ) );
	}


	/**
	 * Remove forms whose term matches, case-insensitively.
	 *
	 * The canonical form is never removed: an entry must keep a name.
	 *
	 * @param array<int, array{term:string,abbr:string}> $forms Forms.
	 * @param string                                     $term  Term to remove.
	 * @return array<int, array{term:string,abbr:string}>
	 */
	public static function remove( array $forms, string $term ): array {
		$needle = mb_strtolower( self::clean( $term ) );
		$kept   = array();

		foreach ( $forms as $index => $form ) {
			if ( $index === 0 || mb_strtolower( $form['term'] ) !== $needle ) {
				$kept[] = $form;
			}
		}

		return $kept;
	}


	/**
	 * Build a form from an array that may be missing keys or carry junk.
	 *
	 * @param mixed $item Candidate form.
	 * @return array{term:string,abbr:string}
	 */
	private static function from_array( $item ): array {
		$item = is_array( $item ) ? $item : array();

		return array(
			'term' => self::clean( (string) ( $item['term'] ?? '' ) ),
			'abbr' => self::clean( (string) ( $item['abbr'] ?? '' ) ),
		);
	}


	/**
	 * Plain, single-line text.
	 *
	 * @param string $text Raw text.
	 */
	private static function clean( string $text ): string {
		$text = html_entity_decode( strip_tags( $text ), ENT_QUOTES | ENT_HTML5, 'UTF-8' );
		return trim( (string) preg_replace( '/\s+/u', ' ', $text ) );
	}
}
