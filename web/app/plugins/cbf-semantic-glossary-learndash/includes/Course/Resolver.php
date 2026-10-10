<?php
/**
 * Resolve a course glossary for a learner, an editor or the full set.
 *
 * @package CodingBlackFemales/SemanticGlossaryLearnDash
 */

namespace CodingBlackFemales\SemanticGlossaryLearnDash\Course;

use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Resolver.
 *
 * Steps decides which steps are open; core's public API then lists the
 * entries those steps reference, in course order, so each entry's first use is
 * the step that introduces it.
 */
final class Resolver {

	/**
	 * What a learner sees.
	 *
	 * @param int     $course_id Course ID.
	 * @param WP_User $learner   Learner; ID 0 for a visitor who is not logged in.
	 */
	public static function for_learner( int $course_id, WP_User $learner ): CourseTerms {
		return self::resolve( $course_id, Steps::for_learner( $course_id, $learner ) );
	}


	/**
	 * Everything, as an editor checking the course sees it.
	 *
	 * @param int $course_id Course ID.
	 */
	public static function full( int $course_id ): CourseTerms {
		return self::resolve( $course_id, Steps::all( $course_id ) );
	}


	/**
	 * The editor preview: dripped steps locked.
	 *
	 * @param int $course_id Course ID.
	 */
	public static function for_editor( int $course_id ): CourseTerms {
		return self::resolve( $course_id, Steps::for_editor( $course_id ) );
	}


	/**
	 * Every entry the course references, each with the step that first uses it.
	 *
	 * @param int $course_id Course ID.
	 * @return array<int, array<string, mixed>> As glossary_get_terms_for_posts() returns them.
	 */
	public static function first_use( int $course_id ): array {
		return Cache::remember( 'first-use', $course_id, fn () => glossary_get_terms_for_posts( Steps::ids( $course_id ) ) );
	}


	/**
	 * Resolve against a set of steps.
	 *
	 * @param int    $course_id Course ID.
	 * @param Step[] $steps     Steps, in course order.
	 */
	private static function resolve( int $course_id, array $steps ): CourseTerms {
		$course_terms = self::first_use( $course_id );
		$open_ids     = CourseTerms::open_ids( $steps );

		$open_terms = count( $open_ids ) === count( $steps ) ? $course_terms : glossary_get_terms_for_posts( $open_ids );

		return new CourseTerms( $steps, $open_terms, $course_terms );
	}
}
