<?php
/**
 * Converts PhpPresentation shapes into the format-neutral document IR.
 *
 * This is the only place in the PPTX path that touches PhpPresentation's object
 * model for text; everything downstream consumes Document\Ir arrays. Splitting
 * it out is what lets BlockLayout and BlockRenderer serve the PDF and DOCX
 * parsers unchanged.
 *
 * UNIT NOTE (P0.4): PhpPresentation returns shape offsets/dimensions in PIXELS
 * (96 DPI), not EMU, so its geometry feeds BlockLayout directly.
 *
 * @class   Pptx\ShapeReader
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Pptx;

use CodingBlackFemales\SlidesImporter\Document\BlockLayout;
use CodingBlackFemales\SlidesImporter\Document\Ir;
use PhpOffice\PhpPresentation\Shape\RichText;
use PhpOffice\PhpPresentation\Shape\RichText\Paragraph;
use PhpOffice\PhpPresentation\Style\Bullet;
use PhpOffice\PhpPresentation\Style\Font;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * ShapeReader class.
 */
final class ShapeReader {

	/**
	 * Placeholder types that are always footer elements (slide number, footer
	 * text, date/time) and excluded regardless of position.
	 */
	const FOOTER_PLACEHOLDER_TYPES = array( 'sldNum', 'ftr', 'dt' );

	/** Placeholder types that hold the slide title, extracted separately. */
	const TITLE_PLACEHOLDER_TYPES = array( 'title', 'ctrTitle' );

	/**
	 * Minimum font size (pt) at which a PPTX paragraph is treated as a heading.
	 *
	 * Mirrors the python-pptx pipeline's rich_text.py threshold.
	 */
	const HEADING_MIN_SIZE_PT = 22;


	/**
	 * Build the ordered content blocks for a slide.
	 *
	 * Title placeholder shapes and footer-zone shapes are excluded; what remains
	 * is converted to positioned text boxes and handed to BlockLayout for
	 * row/column analysis.
	 *
	 * @param  object $slide           PhpPresentation slide object.
	 * @param  int    $slide_width_px  Slide width in pixels.
	 * @param  int    $slide_height_px Slide height in pixels (used for the footer cutoff).
	 * @return array<array> ContentBlock arrays.
	 */
	public static function build_content_blocks( object $slide, int $slide_width_px, int $slide_height_px = 0 ): array {
		return BlockLayout::build_blocks(
			self::collect_text_boxes( $slide, $slide_height_px ),
			$slide_width_px
		);
	}


	/**
	 * Extract the slide title from the title placeholder shape.
	 *
	 * @param  object $slide PhpPresentation slide.
	 * @return string
	 */
	public static function extract_title( object $slide ): string {
		foreach ( $slide->getShapeCollection() as $shape ) {
			if ( ! ( $shape instanceof RichText ) ) {
				continue;
			}
			if ( in_array( self::placeholder_type( $shape ), self::TITLE_PLACEHOLDER_TYPES, true ) ) {
				return Ir::plain_text_all( self::shape_paragraphs( $shape ) );
			}
		}
		return '';
	}


	/**
	 * Convert a RichText shape's paragraphs into IR paragraphs.
	 *
	 * @param  RichText $shape PhpPresentation rich-text shape.
	 * @return array Paragraph arrays.
	 */
	public static function shape_paragraphs( RichText $shape ): array {
		$paragraphs = array();
		foreach ( $shape->getParagraphs() as $para ) {
			$converted = self::convert_paragraph( $para );
			if ( $converted !== null ) {
				$paragraphs[] = $converted;
			}
		}
		return $paragraphs;
	}


	// ── Private helpers ───────────────────────────────────────────────────────

	/**
	 * Collect content shapes from a slide as IR text boxes.
	 *
	 * Excluded:
	 *  - Title/centre-title placeholder shapes (handled by extract_title).
	 *  - Footer placeholder types: sldNum, ftr, dt.
	 *  - Any shape whose top-edge sits in the footer zone, covering copyright
	 *    text-boxes and the CBF icon.
	 *  - Non-text shapes (images are extracted separately by the Parser).
	 *
	 * @param  object $slide           PhpPresentation slide.
	 * @param  int    $slide_height_px Slide height in px (0 = skip position filter).
	 * @return array TextBox arrays.
	 */
	private static function collect_text_boxes( object $slide, int $slide_height_px ): array {
		$footer_cutoff = BlockLayout::footer_cutoff( $slide_height_px );
		$excluded      = array_merge( self::TITLE_PLACEHOLDER_TYPES, self::FOOTER_PLACEHOLDER_TYPES );

		$boxes = array();
		foreach ( $slide->getShapeCollection() as $shape ) {
			if ( ! ( $shape instanceof RichText ) || ! self::is_content_shape( $shape, $excluded, $footer_cutoff ) ) {
				continue;
			}

			$paragraphs = Ir::drop_empty( self::shape_paragraphs( $shape ) );
			if ( empty( $paragraphs ) ) {
				continue;
			}

			$boxes[] = Ir::box(
				(float) $shape->getOffsetX(),
				(float) $shape->getOffsetY(),
				(float) $shape->getWidth(),
				(float) $shape->getHeight(),
				$paragraphs
			);
		}

		return $boxes;
	}


	/**
	 * Return true when a shape holds importable body content.
	 *
	 * @param  RichText $shape         PhpPresentation shape.
	 * @param  array    $excluded      Placeholder types to skip.
	 * @param  int      $footer_cutoff Top-edge pixel offset of the footer zone.
	 * @return bool
	 */
	private static function is_content_shape( RichText $shape, array $excluded, int $footer_cutoff ): bool {
		if ( in_array( self::placeholder_type( $shape ), $excluded, true ) ) {
			return false;
		}

		$left = $shape->getOffsetX();
		if ( $left === null || $shape->getWidth() <= 0 || $shape->getHeight() <= 0 ) {
			return false;
		}

		// Skip shapes in the footer zone (copyright text boxes, icons, etc.).
		return (int) $shape->getOffsetY() < $footer_cutoff;
	}


	/**
	 * Read a shape's placeholder type, tolerating shapes that have none.
	 *
	 * @param  RichText $shape PhpPresentation shape.
	 * @return string Placeholder type, or '' when the shape is not a placeholder.
	 */
	private static function placeholder_type( RichText $shape ): string {
		try {
			$ph = $shape->getPlaceholder();
			return $ph ? (string) $ph->getType() : '';
		} catch ( \Throwable $e ) {
			return '';
		}
	}


	/**
	 * Convert one PhpPresentation paragraph to an IR paragraph.
	 *
	 * Mirrors rich_text.py paragraph classification: monospaced runs become
	 * code, bulleted paragraphs become list items, large text becomes a
	 * heading, everything else is a paragraph.
	 *
	 * @param  Paragraph $para PhpPresentation paragraph.
	 * @return array|null IR paragraph, or null when the paragraph is empty.
	 */
	private static function convert_paragraph( Paragraph $para ): ?array {
		$collected = self::collect_runs( $para );

		if ( empty( $collected['runs'] ) ) {
			return null;
		}

		$bullet_type = self::bullet_type( $para );

		// Only a wholly monospaced paragraph becomes a code block; a code font
		// used for a few words inside prose stays an inline <code> run.
		$is_mono = $collected['all_chars'] > 0 && $collected['mono_chars'] === $collected['all_chars'];

		return Ir::para(
			self::paragraph_kind( $bullet_type, $is_mono, $collected['size'] ),
			$collected['runs'],
			array(
				'size'    => $collected['size'],
				'level'   => max( 0, (int) $para->getAlignment()->getLevel() ),
				'ordered' => $bullet_type === Bullet::TYPE_NUMERIC,
			)
		);
	}


	/**
	 * Convert a paragraph's text elements into IR runs.
	 *
	 * Also tallies the monospaced share of the paragraph, which is what decides
	 * whether it renders as a code block or as prose with inline code.
	 *
	 * @param  Paragraph $para PhpPresentation paragraph.
	 * @return array{runs: array, size: float|null, mono_chars: int, all_chars: int}
	 */
	private static function collect_runs( Paragraph $para ): array {
		$runs       = array();
		$plain_text = '';
		$font_size  = null;
		$mono_chars = 0;
		$all_chars  = 0;

		foreach ( $para->getRichTextElements() as $element ) {
			$piece = self::element_run( $element );
			if ( $piece === null ) {
				continue;
			}

			$plain_text .= $piece['text'];
			$font_size   = $font_size ?? $piece['size'];
			$all_chars  += $piece['chars'];
			$mono_chars += $piece['mono_chars'];
			$runs[]      = $piece['run'];
		}

		return array(
			'runs'       => trim( $plain_text ) === '' ? array() : $runs,
			'size'       => $font_size,
			'mono_chars' => $mono_chars,
			'all_chars'  => $all_chars,
		);
	}


	/**
	 * Convert one text element into a run plus the tallies collect_runs needs.
	 *
	 * @param  object $element PhpPresentation rich-text element.
	 * @return array|null { run, text, size, chars, mono_chars }, or null when
	 *                    the element carries no text.
	 */
	private static function element_run( object $element ): ?array {
		$text = method_exists( $element, 'getText' ) ? (string) $element->getText() : '';
		if ( $text === '' ) {
			return null;
		}

		$font  = method_exists( $element, 'getFont' ) ? $element->getFont() : null;
		$flags = self::font_flags( $font );
		$chars = mb_strlen( trim( $text ) );

		return array(
			'run'        => Ir::run( $text, $flags ),
			'text'       => $text,
			'size'       => self::font_size_of( $font ),
			'chars'      => $chars,
			'mono_chars' => empty( $flags['mono'] ) ? 0 : $chars,
		);
	}


	/**
	 * Determine an IR paragraph kind for a PPTX paragraph.
	 *
	 * @param  string     $bullet_type Bullet style type (Bullet::TYPE_* constant).
	 * @param  bool       $is_mono     Whether any run uses a monospaced font.
	 * @param  float|null $font_size   First run's font size in points.
	 * @return string One of the Ir::KIND_* constants.
	 */
	private static function paragraph_kind( string $bullet_type, bool $is_mono, ?float $font_size ): string {
		if ( $is_mono ) {
			return Ir::KIND_CODE;
		}
		if ( $bullet_type !== Bullet::TYPE_NONE ) {
			return Ir::KIND_BULLET;
		}
		if ( $font_size !== null && $font_size >= self::HEADING_MIN_SIZE_PT ) {
			return Ir::KIND_HEADING;
		}
		return Ir::KIND_PARAGRAPH;
	}


	/**
	 * Read a paragraph's bullet style type.
	 *
	 * @param  Paragraph $para PhpPresentation paragraph.
	 * @return string Bullet::TYPE_* constant; TYPE_NONE when unbulleted.
	 */
	private static function bullet_type( Paragraph $para ): string {
		$bullet = $para->getBulletStyle();
		if ( ! $bullet || ! method_exists( $bullet, 'getBulletType' ) ) {
			return Bullet::TYPE_NONE;
		}
		return (string) $bullet->getBulletType();
	}


	/**
	 * Read a font's point size, if it declares one.
	 *
	 * @param  Font|null $font Run font.
	 * @return float|null Size in points, or null when unset.
	 */
	private static function font_size_of( ?Font $font ): ?float {
		if ( ! $font || ! $font->getSize() ) {
			return null;
		}
		return (float) $font->getSize();
	}


	/**
	 * Map a PhpPresentation font to IR run flags.
	 *
	 * @param  Font|null $font Run font, or null when unstyled.
	 * @return array Flags accepted by Ir::run().
	 */
	private static function font_flags( ?Font $font ): array {
		if ( ! $font ) {
			return array();
		}

		return array(
			'bold'      => (bool) $font->isBold(),
			'italic'    => (bool) $font->isItalic(),
			'underline' => $font->getUnderline() !== Font::UNDERLINE_NONE,
			'strike'    => (bool) $font->isStrikethrough(),
			'mono'      => Ir::is_mono_font( (string) $font->getName() ),
		);
	}
}
