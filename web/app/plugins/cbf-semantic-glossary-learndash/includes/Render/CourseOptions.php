<?php
/**
 * How one course glossary is presented.
 *
 * @package CodingBlackFemales/SemanticGlossaryLearnDash
 */

namespace CodingBlackFemales\SemanticGlossaryLearnDash\Render;

use CodingBlackFemales\SemanticGlossary\Render\Options;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * CourseOptions.
 *
 * Core's Options (heading, level, alternatives, back-links, index), plus what
 * only a course glossary has. The block and the shortcode both reduce to this.
 */
final class CourseOptions {

	/**
	 * Constructor.
	 *
	 * @param Options $glossary       Core presentation options.
	 * @param int     $course_id      Course to list; 0 for the course the page belongs to.
	 * @param bool    $show_filter    Whether to show the lesson filter.
	 * @param bool    $show_source    Whether to show each entry's "Introduced in" line.
	 * @param bool    $preview_locked Whether the editor preview shows locked lessons as placeholders.
	 */
	public function __construct(
		public readonly Options $glossary,
		public readonly int $course_id = 0,
		public readonly bool $show_filter = true,
		public readonly bool $show_source = true,
		public readonly bool $preview_locked = true
	) {}


	/**
	 * From Course Glossary block attributes.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param string               $heading_id Heading ID.
	 */
	public static function from_block( array $attributes, string $heading_id ): self {
		$attributes = array_merge(
			array(
				'course'        => 0,
				'showFilter'    => true,
				'showSource'    => true,
				'previewLocked' => true,
			),
			$attributes
		);

		return new self(
			Options::from_block( $attributes, $heading_id ),
			max( 0, (int) $attributes['course'] ),
			(bool) $attributes['showFilter'],
			(bool) $attributes['showSource'],
			(bool) $attributes['previewLocked']
		);
	}


	/**
	 * From `[course_glossary]` shortcode attributes.
	 *
	 * Takes core's `[glossary]` attributes (heading, level, alternatives,
	 * backlinks, index, index_min), plus `course` ("current" or a course ID),
	 * `filter` and `source` (yes/no, true/false or 1/0).
	 *
	 * @param array<string, string>|string $atts       Shortcode attributes.
	 * @param string                       $heading_id Heading ID.
	 */
	public static function from_shortcode( $atts, string $heading_id ): self {
		$atts = array_merge(
			array(
				'course' => 'current',
				'filter' => true,
				'source' => true,
			),
			is_array( $atts ) ? $atts : array()
		);

		return new self(
			Options::from_shortcode( $atts, $heading_id ),
			is_numeric( $atts['course'] ) ? max( 0, (int) $atts['course'] ) : 0,
			self::flag( $atts['filter'] ),
			self::flag( $atts['source'] )
		);
	}


	/**
	 * A copy with a different heading ID.
	 *
	 * @param string $heading_id Heading ID.
	 */
	public function with_heading_id( string $heading_id ): self {
		return new self( $this->glossary->with_heading_id( $heading_id ), $this->course_id, $this->show_filter, $this->show_source, $this->preview_locked );
	}


	/**
	 * Interpret a shortcode flag, as core does.
	 *
	 * @param mixed $value Attribute value.
	 */
	private static function flag( $value ): bool {
		if ( is_bool( $value ) ) {
			return $value;
		}
		return ! in_array( strtolower( trim( (string) $value ) ), array( '0', 'no', 'false', 'off', '' ), true );
	}
}
