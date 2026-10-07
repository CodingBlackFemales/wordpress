<?php
/**
 * WPML compatibility layer for LearnDash Notifications.
 *
 * WPML assigns separate post IDs to translated versions of groups, courses,
 * lessons, topics, and quizzes. This causes two silent failures:
 *
 * 1. When `ld_added_group_access` fires with the original-language group ID,
 *    a notification configured against the translated group ID fails the
 *    `are_triggering_objects_valid()` check and is silently skipped.
 *
 * 2. When the delayed-email cron re-checks course/group access via
 *    `sfwd_lms_has_access()`, it may receive a translated post ID while
 *    LearnDash stores enrollment only on the original post ID, causing the
 *    check to return false and the queued row to be deleted without sending.
 *
 * Both filters are no-ops when WPML is not active.
 *
 * @package LearnDash\Notifications
 * @since 5.2.0
 */

namespace LearnDash\Notifications\Compat;

use LDLMS_Post_Types;
use LearnDash\Core\Utilities\Cast;

/**
 * WPML compatibility handler.
 *
 * @since 5.2.0
 */
class WPML {
	/**
	 * Register the WPML compatibility filters.
	 *
	 * Filters are always added; each callback short-circuits at runtime when
	 * WPML is not active. The guard cannot run here because WPML may not have
	 * registered `wpml_object_id` yet at plugin-include time.
	 *
	 * @since 5.2.0
	 *
	 * @return void
	 */
	public function listen(): void {
		add_filter( 'sfwd_lms_has_access', [ $this, 'filter_has_access' ], 10, 3 );
		add_filter( 'learndash_notifications_are_triggering_objects_valid', [ $this, 'filter_trigger_objects' ], 10, 4 );
	}

	/**
	 * Retry a failed course-access check using the original-language post ID.
	 *
	 * If the initial access check already returned true, or WPML is inactive,
	 * this filter is a no-op.
	 *
	 * @since 5.2.0
	 *
	 * @param bool $has_access Whether the user has access.
	 * @param int  $post_id    The post ID being checked.
	 * @param int  $user_id    The user ID being checked.
	 *
	 * @return bool
	 */
	public function filter_has_access( $has_access, $post_id, $user_id ) {
		$has_access = Cast::to_bool( $has_access );
		$post_id    = Cast::to_int( $post_id );
		$user_id    = Cast::to_int( $user_id );

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Intentionally calling WPML's filter.
		if (
			$has_access
			|| ! has_filter( 'wpml_object_id' )
		) {
			return $has_access;
		}

		if ( $post_id <= 0 ) {
			return $has_access;
		}

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Intentionally calling WPML's filter.
		$default_lang = Cast::to_string( apply_filters( 'wpml_default_language', '' ) );
		$post_type    = Cast::to_string( get_post_type( $post_id ) );
		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Intentionally calling WPML's filter.
		$original_id = Cast::to_int( apply_filters( 'wpml_object_id', $post_id, $post_type, true, $default_lang ) );

		if (
			! $original_id
			|| $original_id === $post_id
		) {
			return $has_access;
		}

		return sfwd_lms_has_access_fn( $original_id, $user_id );
	}

	/**
	 * Resolve translated object IDs to their originals before comparing.
	 *
	 * When `are_triggering_objects_valid()` fails (returns false), this filter
	 * re-runs the comparison after mapping both the incoming args and the
	 * notification's stored IDs to their default-language equivalents via WPML.
	 *
	 * @since 5.2.0
	 *
	 * @param bool                 $valid        Whether the triggering objects are valid.
	 * @param string               $trigger      Trigger slug.
	 * @param object               $notification Notification model.
	 * @param array<string, mixed> $args         Triggering context arguments.
	 *
	 * @return bool
	 */
	public function filter_trigger_objects( $valid, $trigger, $notification, $args ) {
		$valid = Cast::to_bool( $valid );

		if ( ! is_object( $notification ) ) {
			return $valid;
		}

		if ( ! is_array( $args ) ) {
			return $valid;
		}

		$trigger = Cast::to_string( $trigger );

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Intentionally calling WPML's filter.
		if (
			$valid
			|| ! has_filter( 'wpml_object_id' )
		) {
			return $valid;
		}

		// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Intentionally calling WPML's filter.
		$default_lang = Cast::to_string( apply_filters( 'wpml_default_language', '' ) );

		$checks = [
			'group_id'  => [
				'property'  => 'group_id',
				'post_type' => learndash_get_post_type_slug( LDLMS_Post_Types::GROUP ),
			],
			'course_id' => [
				'property'  => 'course_id',
				'post_type' => learndash_get_post_type_slug( LDLMS_Post_Types::COURSE ),
			],
			'lesson_id' => [
				'property'  => 'lesson_id',
				'post_type' => learndash_get_post_type_slug( LDLMS_Post_Types::LESSON ),
			],
			'topic_id'  => [
				'property'  => 'topic_id',
				'post_type' => learndash_get_post_type_slug( LDLMS_Post_Types::TOPIC ),
			],
			'quiz_id'   => [
				'property'  => 'quiz_id',
				'post_type' => learndash_get_post_type_slug( LDLMS_Post_Types::QUIZ ),
			],
		];

		$results = [];

		foreach ( $checks as $arg_key => $config ) {
			$property = $config['property'];

			if (
				empty( $args[ $arg_key ] )
				|| empty( $notification->$property )
			) {
				continue;
			}

			$source = $notification->$property;
			if ( ! is_array( $source ) ) {
				continue;
			}

			// "All" — always valid regardless of translation.
			if (
				in_array( 'all', $source, true )
				|| in_array( '0', $source, true )
			) {
				$results[] = true;
				continue;
			}

			// Resolve the incoming arg ID to its original-language equivalent.
			$raw_id = $args[ $arg_key ];
			if (
				! is_int( $raw_id )
				&& ! is_string( $raw_id )
			) {
				continue;
			}
			// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Intentionally calling WPML's filter.
			$arg_original = Cast::to_int( apply_filters( 'wpml_object_id', Cast::to_int( $raw_id ), $config['post_type'], true, $default_lang ) );

			$found = false;
			foreach ( $source as $source_id ) {
				// phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound -- Intentionally calling WPML's filter.
				$source_original = Cast::to_int( apply_filters( 'wpml_object_id', Cast::to_int( $source_id ), $config['post_type'], true, $default_lang ) );
				if (
					$arg_original > 0
					&& $arg_original === $source_original
				) {
					$found = true;
					break;
				}
			}

			$results[] = $found;
		}

		if ( empty( $results ) ) {
			return $valid;
		}

		return ! in_array( false, $results, true );
	}
}
