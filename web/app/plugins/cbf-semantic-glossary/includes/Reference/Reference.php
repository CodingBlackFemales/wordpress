<?php
/**
 * One inline reference found in post content.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Reference;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Reference.
 *
 * Offsets are byte offsets into the scanned HTML, so a caller can replace the
 * reference (or just unwrap it) without re-parsing anything around it.
 */
final class Reference {

	/**
	 * Constructor.
	 *
	 * @param int    $entry_id    Referenced entry; 0 when the markup carries no usable ID.
	 * @param int    $start       Offset of the opening `<span`.
	 * @param int    $end         Offset just past the closing `</span>`.
	 * @param string $inner_html  Markup between the tags.
	 * @param string $text        Plain text of the reference.
	 * @param bool   $render_abbr Whether the editor forced the `<abbr>` shape.
	 * @param int    $position    Zero-based position in document order.
	 */
	public function __construct(
		public readonly int $entry_id,
		public readonly int $start,
		public readonly int $end,
		public readonly string $inner_html,
		public readonly string $text,
		public readonly bool $render_abbr,
		public readonly int $position
	) {}
}
