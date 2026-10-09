<?php
/**
 * The Markdown subset accepted for definitions.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Entry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Markdown.
 *
 * Definitions arrive as Markdown from WP-CLI (`--definition`), CSV imports and
 * the editor's create popover. Definitions only need what Definition allows,
 * so this converts exactly that subset rather than pulling in a full Markdown
 * library:
 *
 * - paragraphs, separated by a blank line
 * - `code`, **bold**, *italic* and _italic_
 * - [links](https://example.com)
 *
 * Anything else is escaped and shown as typed.
 */
final class Markdown {

	/**
	 * A tag-like sequence; input containing one is treated as HTML already.
	 */
	const HTML_PATTERN = '#</?[a-z][a-z0-9]*(\s[^>]*)?/?>#i';


	/**
	 * Whether input is HTML rather than Markdown.
	 *
	 * @param string $text Definition input.
	 */
	public static function is_html( string $text ): bool {
		return (bool) preg_match( self::HTML_PATTERN, $text );
	}


	/**
	 * Definition markup from input that may be either Markdown or HTML.
	 *
	 * @param string $text Definition input.
	 */
	public static function to_definition( string $text ): string {
		return self::is_html( $text ) ? $text : self::to_html( $text );
	}


	/**
	 * Convert the Markdown subset to HTML.
	 *
	 * @param string $text Markdown.
	 */
	public static function to_html( string $text ): string {
		$blocks = preg_split( '/\R\s*\R/u', trim( $text ) );
		$blocks = array_filter( array_map( 'trim', (array) $blocks ), 'strlen' );

		$html = array_map(
			fn ( string $block ): string => '<p>' . self::inline( $block ) . '</p>',
			$blocks
		);

		return implode( "\n", $html );
	}


	/**
	 * Convert inline syntax.
	 *
	 * Code spans are cut out first, so emphasis markers inside them stay
	 * literal (`*args` is code, not the start of italics).
	 *
	 * @param string $text One paragraph of Markdown.
	 */
	private static function inline( string $text ): string {
		$codes = array();
		$text  = (string) preg_replace_callback(
			'/`([^`]+)`/u',
			function ( array $match ) use ( &$codes ): string {
				$codes[] = '<code>' . htmlspecialchars( $match[1], ENT_QUOTES | ENT_HTML5, 'UTF-8' ) . '</code>';
				return "\u{E000}" . ( count( $codes ) - 1 ) . "\u{E001}";
			},
			$text
		);

		$text = self::emphasis( htmlspecialchars( $text, ENT_QUOTES | ENT_HTML5, 'UTF-8', false ) );
		$text = (string) preg_replace( '/\s*\R\s*/u', ' ', $text );

		return (string) preg_replace_callback(
			"/\u{E000}(\d+)\u{E001}/u",
			fn ( array $match ): string => $codes[ (int) $match[1] ],
			$text
		);
	}


	/**
	 * Links, bold and italics, on already-escaped text.
	 *
	 * @param string $text Escaped text.
	 */
	private static function emphasis( string $text ): string {
		$text = (string) preg_replace_callback(
			'/\[([^\]]+)\]\(([^)\s]+)\)/u',
			fn ( array $m ): string => '<a href="' . self::url( $m[2] ) . '">' . $m[1] . '</a>',
			$text
		);

		$text = (string) preg_replace( '/\*\*(?=\S)(.+?)(?<=\S)\*\*/u', '<strong>$1</strong>', $text );
		$text = (string) preg_replace( '/(?<![*\w])\*(?=\S)(.+?)(?<=\S)\*(?![*\w])/u', '<em>$1</em>', $text );

		return (string) preg_replace( '/(?<![\w])_(?=\S)(.+?)(?<=\S)_(?![\w])/u', '<em>$1</em>', $text );
	}


	/**
	 * A link target safe for an href attribute.
	 *
	 * Only http(s), mailto, root-relative and fragment links survive; anything
	 * else (javascript:, data:) becomes an inert fragment.
	 *
	 * @param string $url Escaped URL text from the Markdown.
	 */
	private static function url( string $url ): string {
		$raw = html_entity_decode( $url, ENT_QUOTES | ENT_HTML5, 'UTF-8' );

		if ( ! preg_match( '#^(https?://|mailto:|/|\#)#i', $raw ) ) {
			return '#';
		}

		return htmlspecialchars( $raw, ENT_QUOTES | ENT_HTML5, 'UTF-8' );
	}
}
