<?php
/**
 * Definition markup: what is allowed in, and how it is laid out.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Entry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Definition.
 *
 * Definitions are rich text: inline code, emphasis and links are part of the
 * content (a definition of HEAD that cannot set `.git` in code is worse). They
 * are kept to the inline vocabulary plus paragraphs and lists, because they are
 * rendered inside a `<dd>` and inside editor popovers.
 */
final class Definition {

	/**
	 * Tags and attributes a definition may contain, in wp_kses() format.
	 */
	const ALLOWED_HTML = array(
		'a'      => array(
			'href'   => true,
			'title'  => true,
			'rel'    => true,
			'target' => true,
			'lang'   => true,
		),
		'abbr'   => array( 'title' => true ),
		'b'      => array(),
		'br'     => array(),
		'code'   => array(),
		'em'     => array(),
		'i'      => array(),
		'kbd'    => array(),
		'li'     => array(),
		'ol'     => array(),
		'p'      => array(),
		'span'   => array( 'lang' => true ),
		'strong' => array(),
		'sub'    => array(),
		'sup'    => array(),
		'ul'     => array(),
	);

	/**
	 * Block-level tags; a definition containing one is laid out as blocks.
	 */
	const BLOCK_TAGS = '/<(p|ul|ol)[\s>]/i';


	/**
	 * Strip anything outside ALLOWED_HTML.
	 *
	 * @param string $html Untrusted definition markup.
	 */
	public static function sanitise( string $html ): string {
		return trim( wp_kses( $html, self::ALLOWED_HTML ) );
	}


	/**
	 * Lay a definition out for a `<dd>`.
	 *
	 * A single paragraph is unwrapped, so the common one-sentence definition is
	 * plain inline content.
	 *
	 * @param string $html Sanitised definition markup.
	 */
	public static function layout( string $html ): string {
		return self::paragraphs( trim( $html ) );
	}


	/**
	 * Wrap paragraphs, then unwrap a lone one.
	 *
	 * The classic editor saves definitions without `<p>` (wpautop() adds them
	 * on display); WP-CLI and imports may save them with.
	 *
	 * @param string $html Definition markup.
	 */
	private static function paragraphs( string $html ): string {
		if ( $html === '' ) {
			return '';
		}

		if ( ! preg_match( self::BLOCK_TAGS, $html ) ) {
			$html = trim( wpautop( $html ) );
		}

		if ( substr_count( $html, '<p>' ) === 1 && preg_match( '#^<p>(.*)</p>$#s', $html, $match ) ) {
			return trim( $match[1] );
		}

		return $html;
	}
}
