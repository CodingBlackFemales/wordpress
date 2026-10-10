<?php
/**
 * One entry as it appears in a glossary.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Render;

use CodingBlackFemales\SemanticGlossary\Entry\Entry;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * GlossaryItem.
 */
final class GlossaryItem {

	/**
	 * Index group for initials that are not letters.
	 */
	const OTHER = '#';

	/**
	 * Constructor.
	 *
	 * @param Entry  $entry       The entry.
	 * @param string $letter      A–Z index group: an upper-case letter, or OTHER.
	 * @param bool   $has_inline  Whether the post marks this entry inline, so a back-link has somewhere to go.
	 */
	public function __construct(
		public readonly Entry $entry,
		public readonly string $letter,
		public readonly bool $has_inline
	) {}


	/**
	 * Fragment identifier of the index group's target.
	 */
	public function letter_anchor(): string {
		return 'glossary-' . ( $this->letter === self::OTHER ? 'other' : strtolower( $this->letter ) );
	}
}
