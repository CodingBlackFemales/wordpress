<?php
/**
 * User Quiz Resume class and functions.
 *
 * @since 3.5.0
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LDLMS_User_Quiz_Resume' ) ) {
	/**
	 * Class to create the instance.
	 */
	class LDLMS_User_Quiz_Resume {
		/**
		 * Activity meta key.
		 *
		 * @var string $meta_key.
		 */
		private static $meta_key = 'quiz_resume_data';

		/**
		 * Get the User Quiz Resume Activity and Meta record.
		 *
		 * @since 3.5.0
		 *
		 * @param int  $user_id      User ID.
		 * @param int  $quiz_id      Quiz ID.
		 * @param int  $course_id    Course ID.
		 * @param int  $quiz_started Quiz started timestamp.
		 * @param bool $create       If true will create the activity record if does not exist.
		 */
		public static function get_user_quiz_resume_activity( $user_id = 0, $quiz_id = 0, $course_id = 0, $quiz_started = 0, $create = false ) {
			$user_id      = absint( $user_id );
			$quiz_id      = absint( $quiz_id );
			$course_id    = absint( $course_id );
			$quiz_started = absint( $quiz_started );

			$activity = null;

			if ( ( ! empty( $user_id ) ) && ( ! empty( $quiz_id ) ) ) {
				$args = array(
					'activity_id'        => 0,
					'course_id'          => $course_id,
					'user_id'            => $user_id,
					'post_id'            => $quiz_id,
					'activity_type'      => 'quiz',
					'activity_completed' => 0,
				);

				if ( ! empty( $quiz_started ) ) {
					$args['activity_started'] = $quiz_started;
				}

				$activity = learndash_get_user_activity( $args );
				if ( ( is_object( $activity ) ) && ( property_exists( $activity, 'activity_id' ) ) && ( ! empty( $activity->activity_id ) ) ) {
					$activity = new LDLMS_Model_Activity( $activity );

					// If we have an existing activity record and it's valid, we include the meta.

					$activity_meta_raw = (array) learndash_get_user_activity_meta( $activity->activity_id, self::$meta_key, true, true );

					// Validate the activity meta before using it.

					if ( self::validate_quiz_resume_data( $activity_meta_raw ) ) {
						$activity->activity_meta = $activity_meta_raw;
					} else {
						// Delete the activity meta record if it's invalid.
						learndash_delete_user_activity_meta( $activity->activity_id, self::$meta_key );

						$activity->activity_meta = [];
					}
				} elseif ( true === $create ) {
					$activity = learndash_activity_start_quiz( $user_id, $course_id, $quiz_id, $quiz_started );
					if ( ! is_null( $activity ) ) {
						$activity->activity_meta = [];
					}
				}
			}

			return $activity;
		}

		/**
		 * Update the User Quiz Resume Activity Meta record.
		 *
		 * @since 3.5.0
		 *
		 * @param int   $user_id      User ID.
		 * @param int   $quiz_id      Quiz ID.
		 * @param int   $course_id    Course ID.
		 * @param int   $quiz_started Quiz started timestamp.
		 * @param array $results      Quiz question results array.
		 */
		public static function update_user_quiz_resume_metadata( $user_id = 0, $quiz_id = 0, $course_id = 0, $quiz_started = 0, $results = array() ) {
			$user_id      = absint( $user_id );
			$quiz_id      = absint( $quiz_id );
			$course_id    = absint( $course_id );
			$quiz_started = absint( $quiz_started );

			if ( empty( $user_id ) || empty( $quiz_id ) || empty( $results ) ) {
				return false;
			}

			$changes_made = false;

			$activity = self::get_user_quiz_resume_activity( $user_id, $quiz_id, $course_id, $quiz_started, true );

			if (
				is_a( $activity, 'LDLMS_Model_Activity' ) &&
				property_exists( $activity, 'activity_id' ) &&
				! empty( $activity->activity_id )
			) {
				foreach ( $results as $result_key => $result_data ) {
					$activity->activity_meta[ $result_key ] = $result_data;
				}

				// If the meta data is invalid, skip the update. It prevents malformed data from being saved.
				if ( ! self::validate_quiz_resume_data( $activity->activity_meta ) ) {
					return false;
				}

				$changes_made = true;

				learndash_update_user_activity_meta( $activity->activity_id, self::$meta_key, $activity->activity_meta );
			}

			/**
			 * Fires when the quiz resume metadata is updated.
			 *
			 * @since 4.7.0.1
			 *
			 * @param bool                                         $changes_made  A flag indicating if changes were made.
			 * @param array{ quiz_started: int, results: mixed[] } $activity_data Activity data, including quiz started timestamp and results.
			 * @param int                                          $quiz_id       Quiz ID.
			 * @param int                                          $course_id     Course ID.
			 * @param int                                          $user_id       User ID.
			 */
			do_action(
				'learndash_quiz_resume_metadata_updated',
				$changes_made,
				[
					'quiz_started' => $quiz_started,
					'results'      => $results,
				],
				$quiz_id,
				$course_id,
				$user_id
			);

			return $changes_made;
		}

		/**
		 * Delete the User Quiz Resume Activity Meta record.
		 *
		 * @since 3.5.0
		 *
		 * @param int $user_id      User ID.
		 * @param int $quiz_id      Quiz ID.
		 * @param int $course_id    Course ID.
		 * @param int $quiz_started Quiz started timestamp.
		 */
		public static function delete_user_quiz_resume_metadata( $user_id = 0, $quiz_id = 0, $course_id = 0, $quiz_started = 0 ) {
			$user_id   = absint( $user_id );
			$quiz_id   = absint( $quiz_id );
			$course_id = absint( $course_id );

			if ( ( ! empty( $user_id ) ) && ( ! empty( $quiz_id ) ) ) {
				$activity = self::get_user_quiz_resume_activity( $user_id, $quiz_id, $course_id, $quiz_started );

				if ( ( is_a( $activity, 'LDLMS_Model_Activity' ) ) && ( property_exists( $activity, 'activity_id' ) ) && ( ! empty( $activity->activity_id ) ) ) {
					if ( ( property_exists( $activity, 'activity_meta' ) ) && ( ! empty( $activity->activity_meta ) ) ) {
						return learndash_delete_user_activity_meta( $activity->activity_id, self::$meta_key );
					}
				}
			}
		}

		/**
		 * Validates the quiz resume data meta to ensure integrity.
		 *
		 * Checks if reviewBox entries marked as solved have corresponding question data.
		 *
		 * @since 4.25.8
		 *
		 * @param array<mixed> $activity_meta The quiz resume activity meta data array to validate.
		 *
		 * @return bool True if data is valid, false otherwise.
		 */
		private static function validate_quiz_resume_data( array $activity_meta ): bool {
			// If empty, it's considered valid (fresh start).
			if ( empty( $activity_meta ) ) {
				return true;
			}

			// If reviewBox doesn't exist, it's valid (no action taken yet).
			if (
				! isset( $activity_meta['reviewBox'] )
				|| ! is_array( $activity_meta['reviewBox'] )
			) {
				return true;
			}

			// Validate each reviewBox entry that has a "solved" key.

			$review_box = $activity_meta['reviewBox'];

			foreach ( $review_box as $review_index => $review_data ) {
				// Invalid reviewBox entry.
				if ( ! is_array( $review_data ) ) {
					return false;
				}

				// Skip not solved entries.
				if ( ! isset( $review_data['solved'] ) ) {
					continue;
				}

				$question_id_found = false;

				// Find the numeric key that has an index matching this reviewBox position.
				foreach ( $activity_meta as $key => $value ) {
					// Skip non-numeric keys as they are not relevant to this validation.
					if ( ! is_numeric( $key ) ) {
						continue;
					}

					// Check if this question has the matching index.

					if (
						isset( $value['index'] )
						&& absint( $value['index'] ) === absint( $review_index )
					) {
						$question_id_found = true;

						break;
					}
				}

				// If no question ID was found for this solved review, data is invalid.

				if ( ! $question_id_found ) {
					return false;
				}
			}

			return true;
		}
	}
}
