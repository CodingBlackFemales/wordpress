<?php
/**
 * Read a course's lessons and topics from LearnDash.
 *
 * @package CodingBlackFemales/SemanticGlossaryLearnDash
 */

namespace CodingBlackFemales\SemanticGlossaryLearnDash\Course;

use LearnDash\Core\Models\Lesson;
use LearnDash\Core\Models\Topic;
use WP_User;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Steps.
 *
 * The only class that asks LearnDash about course structure and access
 * (verified against LearnDash 5.2):
 *
 * - Order comes from the course's linear step list (`'l'`): each lesson, then
 *   its topics, then the next lesson. That is the order a learner meets them in,
 *   which none of the per-type step helpers give.
 * - Access comes from `Step::is_content_visible()`, the check LearnDash's own
 *   lesson and topic templates use. It covers enrolment, sample lessons, drip
 *   schedules (inherited by topics), linear progression and the admin and group
 *   leader bypass settings. It does not cover course prerequisites or points;
 *   LearnDash enforces those on the course page instead.
 *
 * Quizzes are not steps here: they hold no prose to reference terms from.
 */
final class Steps {

	/**
	 * Step post types whose references count.
	 */
	const TYPES = array( 'sfwd-lessons', 'sfwd-topic' );

	/**
	 * Course post type.
	 */
	const COURSE_TYPE = 'sfwd-courses';


	/**
	 * Published lessons and topics, in course order.
	 *
	 * A topic under an unpublished lesson is left out with its lesson.
	 *
	 * @param int $course_id Course ID.
	 * @return int[]
	 */
	public static function ids( int $course_id ): array {
		return Cache::remember( 'steps', $course_id, fn () => self::load_ids( $course_id ) );
	}


	/**
	 * Steps as a learner meets them: locked unless LearnDash shows them the content.
	 *
	 * @param int     $course_id Course ID.
	 * @param WP_User $learner   Learner; ID 0 for a visitor who is not logged in.
	 * @return Step[]
	 */
	public static function for_learner( int $course_id, WP_User $learner ): array {
		return array_map(
			fn ( int $id ) => self::step( $course_id, $id, self::can_open( $id, $learner ) ? Step::OPEN : Step::LOCKED ),
			self::ids( $course_id )
		);
	}


	/**
	 * Every step open: the full set, with nothing hidden.
	 *
	 * @param int $course_id Course ID.
	 * @return Step[]
	 */
	public static function all( int $course_id ): array {
		return array_map( fn ( int $id ) => self::step( $course_id, $id, Step::OPEN ), self::ids( $course_id ) );
	}


	/**
	 * Steps for the editor preview, with steps still held back by a drip schedule locked.
	 *
	 * The editor has no learner to ask, and linear progression locks a different
	 * set for each one; a drip schedule is the one lock that is part of the course.
	 *
	 * @param int $course_id Course ID.
	 * @return Step[]
	 */
	public static function for_editor( int $course_id ): array {
		return array_map(
			fn ( int $id ) => self::step( $course_id, $id, self::has_drip( $course_id, $id ) ? Step::DRIP : Step::OPEN ),
			self::ids( $course_id )
		);
	}


	/**
	 * Whether LearnDash would show a learner a step's content.
	 *
	 * @param int     $step_id Step ID.
	 * @param WP_User $learner Learner.
	 */
	public static function can_open( int $step_id, WP_User $learner ): bool {
		$model = get_post_type( $step_id ) === 'sfwd-topic' ? Topic::find( $step_id ) : Lesson::find( $step_id );

		return $model !== null && $model->is_content_visible( $learner );
	}


	/**
	 * The course a post belongs to: itself when it is a course, else its step's course.
	 *
	 * With shared steps, LearnDash takes the course from the URL a step is viewed
	 * under, so the same lesson can show each course's glossary.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function course_of( int $post_id ): int {
		if ( get_post_type( $post_id ) === self::COURSE_TYPE ) {
			return $post_id;
		}

		return (int) learndash_get_course_id( $post_id );
	}


	/**
	 * Whether a post is a course.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function is_course( int $post_id ): bool {
		return $post_id > 0 && get_post_type( $post_id ) === self::COURSE_TYPE;
	}


	/**
	 * Every course, by title.
	 *
	 * @return int[]
	 */
	public static function course_ids(): array {
		return get_posts(
			array(
				'post_type'   => self::COURSE_TYPE,
				'post_status' => array( 'publish', 'draft', 'pending', 'future', 'private' ),
				'nopaging'    => true,
				'fields'      => 'ids',
				'orderby'     => 'title',
				'order'       => 'ASC',
			)
		);
	}


	/**
	 * A post's title as plain text.
	 *
	 * @param int $post_id Post ID.
	 */
	public static function title( int $post_id ): string {
		return html_entity_decode( wp_strip_all_tags( get_the_title( $post_id ) ), ENT_QUOTES, 'UTF-8' );
	}


	/**
	 * Read the step order from the course's linear step list.
	 *
	 * @param int $course_id Course ID.
	 * @return int[]
	 */
	private static function load_ids( int $course_id ): array {
		$ids = array();

		foreach ( (array) learndash_course_get_steps_by_type( $course_id, 'l' ) as $node ) {
			[ $type, $id ] = array_pad( explode( ':', (string) $node ), 2, '0' );

			if ( in_array( $type, self::TYPES, true ) && self::is_published( $course_id, (int) $id ) ) {
				$ids[] = (int) $id;
			}
		}

		return $ids;
	}


	/**
	 * Whether a step and every step above it are published.
	 *
	 * @param int $course_id Course ID.
	 * @param int $step_id   Step ID.
	 */
	private static function is_published( int $course_id, int $step_id ): bool {
		$chain = array_merge( (array) learndash_course_get_all_parent_step_ids( $course_id, $step_id ), array( $step_id ) );

		foreach ( $chain as $id ) {
			if ( get_post_status( (int) $id ) !== 'publish' ) {
				return false;
			}
		}

		return true;
	}


	/**
	 * Whether a step, or a step above it, is held back by its drip schedule.
	 *
	 * A release date counts until it has passed. A delay in days after
	 * enrolment always counts, since it differs for every learner.
	 *
	 * @param int $course_id Course ID.
	 * @param int $step_id   Step ID.
	 */
	private static function has_drip( int $course_id, int $step_id ): bool {
		$chain = array_merge( (array) learndash_course_get_all_parent_step_ids( $course_id, $step_id ), array( $step_id ) );

		foreach ( $chain as $id ) {
			$date = learndash_get_setting( (int) $id, 'visible_after_specific_date' );
			$time = is_numeric( $date ) ? (int) $date : (int) strtotime( (string) $date );

			if ( (int) learndash_get_setting( (int) $id, 'visible_after' ) > 0 || $time > time() ) {
				return true;
			}
		}

		return false;
	}


	/**
	 * A Step from a post.
	 *
	 * The link is the step under this course, which differs from its plain
	 * permalink when steps are shared between courses.
	 *
	 * @param int    $course_id Course ID.
	 * @param int    $id        Step ID.
	 * @param string $lock      Why it is locked.
	 */
	private static function step( int $course_id, int $id, string $lock ): Step {
		return new Step( $id, self::title( $id ), (string) learndash_get_step_permalink( $id, $course_id ), $lock );
	}
}
