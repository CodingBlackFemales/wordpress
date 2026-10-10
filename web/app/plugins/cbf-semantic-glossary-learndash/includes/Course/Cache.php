<?php
/**
 * Intermediate caches: step order and first-use maps.
 *
 * @package CodingBlackFemales/SemanticGlossaryLearnDash
 */

namespace CodingBlackFemales\SemanticGlossaryLearnDash\Course;

use CodingBlackFemales\SemanticGlossary\Entry\PostType;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Cache.
 *
 * Per-course values live in the object cache, so they last for the request
 * without a persistent cache and across requests with one. Learner-specific
 * results (what is unlocked) are never cached.
 *
 * Saving or deleting a course, step or glossary entry, or changing a course's
 * structure, invalidates everything; `wp glossary course cache-flush` does
 * the same by hand after bulk imports.
 */
final class Cache {

	/**
	 * Object cache group.
	 */
	const GROUP = 'cbf-glossary-learndash';

	/**
	 * Core's cache group, marked as changed whenever it re-indexes a post.
	 */
	const CORE_GROUP = 'cbf_glossary';

	/**
	 * Kinds of value kept per course.
	 */
	const KINDS = array( 'steps', 'first-use' );

	/**
	 * Post meta holding a course's builder structure.
	 */
	const STEPS_META = 'ld_course_steps';


	/**
	 * Register hooks.
	 */
	public static function hooks(): void {
		add_action( 'wp_after_insert_post', array( __CLASS__, 'on_post_change' ), 20, 2 );
		add_action( 'deleted_post', array( __CLASS__, 'on_post_change' ), 10, 2 );
		add_action( 'trashed_post', array( __CLASS__, 'on_post_change' ) );
		add_action( 'added_post_meta', array( __CLASS__, 'on_meta_change' ), 10, 3 );
		add_action( 'updated_post_meta', array( __CLASS__, 'on_meta_change' ), 10, 3 );
		add_action( 'deleted_post_meta', array( __CLASS__, 'on_meta_change' ), 10, 3 );
	}


	/**
	 * A cached value, computing it when missing.
	 *
	 * @param string   $kind      One of KINDS.
	 * @param int      $course_id Course ID.
	 * @param callable $compute   Computes the value.
	 * @return mixed
	 */
	public static function remember( string $kind, int $course_id, callable $compute ) {
		$key   = self::key( $kind, $course_id );
		$found = false;
		$value = wp_cache_get( $key, self::GROUP, false, $found );

		if ( $found ) {
			return $value;
		}

		$value = $compute();
		wp_cache_set( $key, $value, self::GROUP );

		return $value;
	}


	/**
	 * Clear one course's values, or every course's.
	 *
	 * @param int $course_id Course ID, or 0 for all courses.
	 */
	public static function flush( int $course_id = 0 ): void {
		if ( $course_id === 0 ) {
			wp_cache_set_last_changed( self::GROUP );
			return;
		}

		foreach ( self::KINDS as $kind ) {
			wp_cache_delete( self::key( $kind, $course_id ), self::GROUP );
		}
	}


	/**
	 * Invalidate when a course, step or entry changes.
	 *
	 * Reference changes need nothing here: core marks its own cache group as
	 * changed whenever it re-indexes a post, and the keys include that too.
	 *
	 * @param int           $post_id Post ID.
	 * @param \WP_Post|null $post    Post, when the hook passes it.
	 */
	public static function on_post_change( int $post_id, $post = null ): void {
		$type = $post instanceof \WP_Post ? $post->post_type : get_post_type( $post_id );

		if ( in_array( $type, self::watched_types(), true ) ) {
			self::flush();
		}
	}


	/**
	 * Invalidate when a course's structure changes.
	 *
	 * @param int|int[] $meta_ids Meta ID(s).
	 * @param int       $post_id  Post ID.
	 * @param string    $key      Meta key.
	 */
	public static function on_meta_change( $meta_ids, int $post_id, string $key ): void {
		if ( $key === self::STEPS_META ) {
			self::flush();
		}
	}


	/**
	 * Post types whose changes affect a course glossary.
	 *
	 * @return string[]
	 */
	private static function watched_types(): array {
		return array( 'sfwd-courses', 'sfwd-lessons', 'sfwd-topic', PostType::NAME );
	}


	/**
	 * Cache key, salted with this group's last change and core's reference index's.
	 *
	 * @param string $kind      One of KINDS.
	 * @param int    $course_id Course ID.
	 */
	private static function key( string $kind, int $course_id ): string {
		return $kind . ':' . $course_id . ':' . wp_cache_get_last_changed( self::GROUP ) . ':' . wp_cache_get_last_changed( self::CORE_GROUP );
	}
}
