<?php
/**
 * Classifies each parsed slide into a type based on layout name and config overrides.
 *
 * Mirrors the python-pptx pipeline's use of slide layout names to identify
 * section headings vs body content slides. Works on the format-neutral ParsedDeck
 * (see Document\Ir), so a PDF page and a DOCX section classify the same way a
 * PPTX slide does — for those formats `layout_name` carries the page label or the
 * heading style name respectively.
 *
 * @class   Document\SlideClassifier
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Document;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * SlideClassifier class.
 *
 * Slide types mirror the python-pptx pipeline's three categories:
 * - 'cover'   : slide 1 (always excluded from content)
 * - 'heading' : layout name matches heading_layout_regex (starts a new topic)
 * - 'section' : layout name matches section_heading_layout_regex (section divider only)
 * - 'hidden'  : show="0" in OOXML (excluded from import)
 * - 'body'    : all other visible slides
 */
final class SlideClassifier {

	/**
	 * Classify all slides in a ParsedDeck.
	 *
	 * Applies: hidden flag → cover flag → user overrides → heading regex → default 'body'.
	 *
	 * @param  array  $parsed_deck       ParsedDeck array from Parser::parse().
	 * @param  string $heading_regex     Regex matched against layout_name to identify topic-start slides.
	 * @param  array  $slide_overrides   User overrides from DeckConfig: {slide_number => 'body'|'heading'|'hidden'}.
	 * @return array  ParsedDeck with 'slide_type' added to each slide entry.
	 */
	public static function classify( array $parsed_deck, string $heading_regex = '', array $slide_overrides = array() ): array {
		$slides = $parsed_deck['slides'] ?? array();

		foreach ( $slides as &$slide ) {
			$slide['slide_type'] = self::classify_slide( $slide, $heading_regex, $slide_overrides );
		}
		unset( $slide );

		$parsed_deck['slides'] = $slides;
		return $parsed_deck;
	}


	/**
	 * Classify a single parsed slide.
	 *
	 * @param  array  $slide           ParsedSlide array.
	 * @param  string $heading_regex   Heading layout regex.
	 * @param  array  $overrides       Per-slide user overrides.
	 * @return string Slide type: 'cover'|'hidden'|'heading'|'body'.
	 */
	private static function classify_slide( array $slide, string $heading_regex, array $overrides ): string {
		// Cover slide is always index 0.
		if ( $slide['is_cover'] ) {
			return 'cover';
		}

		// Hidden slides are excluded regardless of overrides.
		if ( $slide['is_hidden'] ) {
			return 'hidden';
		}

		// User overrides take precedence over auto-detection.
		$slide_number = (int) $slide['slide_number'];
		if ( isset( $overrides[ $slide_number ] ) ) {
			$override = (string) $overrides[ $slide_number ];
			if ( in_array( $override, array( 'body', 'heading', 'hidden', 'section' ), true ) ) {
				return $override;
			}
		}

		// Auto-detect from layout name using heading regex.
		if ( ! empty( $heading_regex ) ) {
			$layout = (string) $slide['layout_name'];
			// Use preg_match with full-match semantics (anchored).
			if ( $layout && @preg_match( '/^(?:' . $heading_regex . ')$/i', $layout ) ) { // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
				return 'heading';
			}
		}

		return 'body';
	}
}
