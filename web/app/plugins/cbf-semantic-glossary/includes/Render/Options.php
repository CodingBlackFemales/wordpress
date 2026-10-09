<?php
/**
 * How one glossary is presented.
 *
 * @package CodingBlackFemales/SemanticGlossary
 */

namespace CodingBlackFemales\SemanticGlossary\Render;

use CodingBlackFemales\SemanticGlossary\Settings;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Options.
 *
 * The block, the shortcode and the automatic glossary all reduce to this.
 */
final class Options {

	/**
	 * Heading levels on offer.
	 */
	const LEVELS = array( 2, 3, 4 );

	/**
	 * Constructor.
	 *
	 * @param string $heading           Heading text; may hold inline markup.
	 * @param int    $level             Heading level, one of LEVELS.
	 * @param bool   $show_alternatives Whether to list alternative forms.
	 * @param bool   $back_links        Whether to link definitions back to their first mention.
	 * @param bool   $show_index        Whether to show the A–Z index (subject to the minimum).
	 * @param string $heading_id        ID of the heading, for aria-labelledby.
	 * @param int    $index_min_entries Entries needed before the A–Z index is shown.
	 */
	public function __construct(
		public readonly string $heading = '',
		public readonly int $level = 2,
		public readonly bool $show_alternatives = true,
		public readonly bool $back_links = true,
		public readonly bool $show_index = true,
		public readonly string $heading_id = 'glossary-heading',
		public readonly int $index_min_entries = Settings::INDEX_MIN_ENTRIES
	) {}


	/**
	 * From Glossary block attributes.
	 *
	 * @param array<string, mixed> $attributes Block attributes.
	 * @param string               $heading_id Heading ID.
	 */
	public static function from_block( array $attributes, string $heading_id ): self {
		$attributes = array_merge(
			array(
				'heading'          => self::default_heading(),
				'level'            => 2,
				'showAlternatives' => true,
				'backLinks'        => true,
				'showIndex'        => true,
				'indexMinEntries'  => null,
			),
			$attributes
		);

		return new self(
			(string) $attributes['heading'],
			self::level( $attributes['level'] ),
			(bool) $attributes['showAlternatives'],
			(bool) $attributes['backLinks'],
			(bool) $attributes['showIndex'],
			$heading_id,
			self::index_min( $attributes['indexMinEntries'] )
		);
	}


	/**
	 * From `[glossary]` shortcode attributes.
	 *
	 * Mirrors the block: `heading`, `level`, `alternatives`, `backlinks`,
	 * `index` (the last three taking yes/no, true/false or 1/0) and
	 * `index_min`, the entries needed before the A–Z index appears.
	 *
	 * @param array<string, string>|string $atts       Shortcode attributes.
	 * @param string                       $heading_id Heading ID.
	 */
	public static function from_shortcode( $atts, string $heading_id ): self {
		$atts = array_merge(
			array(
				'heading'      => self::default_heading(),
				'level'        => 2,
				'alternatives' => true,
				'backlinks'    => true,
				'index'        => true,
				'index_min'    => null,
			),
			is_array( $atts ) ? $atts : array()
		);

		return new self(
			(string) $atts['heading'],
			self::level( $atts['level'] ),
			self::flag( $atts['alternatives'] ),
			self::flag( $atts['backlinks'] ),
			self::flag( $atts['index'] ),
			$heading_id,
			self::index_min( $atts['index_min'] )
		);
	}


	/**
	 * A copy with back-links switched off.
	 */
	public function without_back_links(): self {
		return new self( $this->heading, $this->level, $this->show_alternatives, false, $this->show_index, $this->heading_id, $this->index_min_entries );
	}


	/**
	 * A copy with a different heading ID.
	 *
	 * @param string $heading_id Heading ID.
	 */
	public function with_heading_id( string $heading_id ): self {
		return new self( $this->heading, $this->level, $this->show_alternatives, $this->back_links, $this->show_index, $heading_id, $this->index_min_entries );
	}


	/**
	 * The heading used when none is set.
	 */
	public static function default_heading(): string {
		return __( 'Glossary', 'cbf-semantic-glossary' );
	}


	/**
	 * A heading level on offer, defaulting to 2.
	 *
	 * @param mixed $level Requested level.
	 */
	private static function level( $level ): int {
		$level = (int) $level;
		return in_array( $level, self::LEVELS, true ) ? $level : 2;
	}


	/**
	 * An index minimum from a block or shortcode, else the site default.
	 *
	 * @param mixed $value Requested minimum; null or empty when unset.
	 */
	private static function index_min( $value ): int {
		return is_numeric( $value ) ? Settings::clamp_index_min( $value ) : Settings::index_min_entries();
	}


	/**
	 * Interpret a shortcode flag.
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
