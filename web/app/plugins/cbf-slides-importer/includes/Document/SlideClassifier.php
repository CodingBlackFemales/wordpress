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
		$override = self::override_for( $slide, $overrides );
		if ( $override !== null ) {
			return $override;
		}

		if ( self::layout_is_heading( (string) $slide['layout_name'], $heading_regex ) ) {
			return 'heading';
		}

		return 'body';
	}


	/**
	 * The user's override for a slide, if they set a usable one.
	 *
	 * @param  array $slide     ParsedSlide array.
	 * @param  array $overrides Per-slide user overrides, keyed by 1-based number.
	 * @return string|null The override, or null when there is none to apply.
	 */
	private static function override_for( array $slide, array $overrides ): ?string {
		$slide_number = (int) $slide['slide_number'];

		if ( ! isset( $overrides[ $slide_number ] ) ) {
			return null;
		}

		$override = (string) $overrides[ $slide_number ];

		return in_array( $override, array( 'body', 'heading', 'hidden', 'section' ), true ) ? $override : null;
	}


	/**
	 * Whether a layout name matches the configured heading pattern.
	 *
	 * The pattern comes from user configuration, so a malformed one must not
	 * take the import down with it — hence the silenced match.
	 *
	 * @param  string $layout        Layout name from the deck.
	 * @param  string $heading_regex Heading layout regex.
	 * @return bool
	 */
	private static function layout_is_heading( string $layout, string $heading_regex ): bool {
		if ( $heading_regex === '' || $layout === '' ) {
			return false;
		}

		// Anchored for full-match semantics.
		return (bool) @preg_match( '/^(?:' . $heading_regex . ')$/i', $layout ); // phpcs:ignore WordPress.PHP.NoSilencedErrors.Discouraged
	}
}
