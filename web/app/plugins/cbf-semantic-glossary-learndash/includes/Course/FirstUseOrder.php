<?php
/**
 * Order "First used in" by course order.
 *
 * @package CodingBlackFemales/SemanticGlossaryLearnDash
 */

namespace CodingBlackFemales\SemanticGlossaryLearnDash\Course;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * FirstUseOrder.
 *
 * Core orders the posts that use an entry by date, so its entry screen says
 * "First used in" whichever lesson was written first. This re-sorts the lessons
 * and topics of each course into course order, so it names the lesson that
 * introduces the entry, as the course glossary's "Introduced in" does.
 */
final class FirstUseOrder {

	/**
	 * Register hooks.
	 */
	public static function hooks(): void {
		add_filter( 'glossary_first_used_order', array( __CLASS__, 'filter' ), 10, 1 );
	}


	/**
	 * Filter callback.
	 *
	 * @param int[] $post_ids Post IDs, in core's order.
	 * @return int[]
	 */
	public static function filter( $post_ids ): array {
		$positions = array();

		foreach ( (array) $post_ids as $post_id ) {
			$positions[ (int) $post_id ] = self::position( (int) $post_id );
		}

		return self::sort( array_map( 'intval', (array) $post_ids ), $positions );
	}


	/**
	 * Sort posts so each course's steps are in course order.
	 *
	 * Each course's steps stay together, placed where the course's earliest step
	 * was; anything outside a course keeps its place.
	 *
	 * @param int[]                                $post_ids  Post IDs, in default order.
	 * @param array<int, array{0:int, 1:int}|null> $positions Course ID and position in it, by post ID; null outside a course.
	 * @return int[]
	 */
	public static function sort( array $post_ids, array $positions ): array {
		$groups = array();

		foreach ( $post_ids as $post_id ) {
			$position = $positions[ $post_id ] ?? null;
			$group    = $position === null ? 'post:' . $post_id : 'course:' . $position[0];

			$groups[ $group ][ $post_id ] = $position[1] ?? 0;
		}

		$sorted = array();
		foreach ( $groups as $members ) {
			asort( $members, SORT_NUMERIC );
			array_push( $sorted, ...array_keys( $members ) );
		}

		return $sorted;
	}


	/**
	 * A step's course and its position in it, or null.
	 *
	 * Uses the step's primary course: on admin screens LearnDash would otherwise
	 * read a course from the request, which here is the entry being edited.
	 *
	 * @param int $post_id Post ID.
	 * @return array{0:int, 1:int}|null
	 */
	private static function position( int $post_id ): ?array {
		if ( ! in_array( get_post_type( $post_id ), Steps::TYPES, true ) ) {
			return null;
		}

		$course_id = (int) learndash_get_course_id( $post_id, true );
		$index     = $course_id > 0 ? array_search( $post_id, Steps::ids( $course_id ), true ) : false;

		return $index === false ? null : array( $course_id, (int) $index );
	}
}
