<?php
/**
 * One course glossary being rendered.
 *
 * @package CodingBlackFemales/SemanticGlossaryLearnDash
 */

namespace CodingBlackFemales\SemanticGlossaryLearnDash\Render;

use CodingBlackFemales\SemanticGlossaryLearnDash\Course\CourseTerms;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * View.
 *
 * Travels through core's render context, so the filters that decorate core's
 * markup know they are decorating a course glossary, and which one.
 */
final class View {

	/**
	 * Constructor.
	 *
	 * @param CourseTerms   $terms   What to list.
	 * @param CourseOptions $options Presentation.
	 * @param string        $id      Prefix for the IDs this glossary's controls use.
	 * @param bool          $editor  Whether this is the block editor's preview.
	 */
	public function __construct(
		public readonly CourseTerms $terms,
		public readonly CourseOptions $options,
		public readonly string $id,
		public readonly bool $editor = false
	) {}


	/**
	 * Whether the lesson filter is shown: asked for, with more than one lesson to pick.
	 */
	public function shows_filter(): bool {
		return $this->options->show_filter && count( $this->terms->filter_steps() ) > 1;
	}


	/**
	 * Whether locked lessons appear as placeholders.
	 */
	public function shows_placeholders(): bool {
		return $this->editor && $this->options->preview_locked;
	}
}
