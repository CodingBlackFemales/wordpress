<?php
/**
 * Course fixtures for the unit suite.
 *
 * @package CodingBlackFemales/SemanticGlossaryLearnDash
 */

declare( strict_types=1 );

namespace CodingBlackFemales\SemanticGlossaryLearnDash\Tests\Support;

use CodingBlackFemales\SemanticGlossaryLearnDash\Course\Step;

/**
 * Course.
 *
 * Steps and term arrays shaped as Steps and glossary_get_terms_for_posts()
 * produce them.
 */
final class Course {

	/**
	 * A lesson.
	 *
	 * @param int    $id   Step ID.
	 * @param string $lock Why it is locked.
	 */
	public static function step( int $id, string $lock = Step::OPEN ): Step {
		return new Step( $id, 'Lesson ' . $id, 'https://academy.test/lessons/lesson-' . $id . '/', $lock );
	}


	/**
	 * A term as glossary_get_terms_for_posts() returns it.
	 *
	 * @param int    $id      Entry ID.
	 * @param string $term    Term.
	 * @param int    $post_id Step where it is first used.
	 * @param bool   $inline  Whether it is marked inline there.
	 * @return array<string, mixed>
	 */
	public static function term( int $id, string $term, int $post_id, bool $inline = true ): array {
		return array(
			'id'         => $id,
			'slug'       => strtolower( str_replace( ' ', '-', $term ) ),
			'forms'      => array(
				array(
					'term' => $term,
					'abbr' => '',
				),
			),
			'definition' => 'Definition of ' . $term . '.',
			'status'     => 'publish',
			'post_id'    => $post_id,
			'text'       => $inline ? $term : null,
			'form'       => null,
			'inline'     => $inline,
		);
	}
}
