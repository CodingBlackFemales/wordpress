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

					// If we have an existing activity record we include the meta.
					$activity->activity_meta = (array) learndash_get_user_activity_meta( $activity->activity_id, self::$meta_key, true, true );
				} elseif ( true === $create ) {
					$activity = learndash_activity_start_quiz( $user_id, $course_id, $quiz_id, $quiz_started );
					if ( ! is_null( $activity ) ) {
						$activity->activity_meta = array();
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
				$changes_made = true;

				foreach ( $results as $result_key => $result_data ) {
					$activity->activity_meta[ $result_key ] = $result_data;
				}

				learndash_update_user_activity_meta( $activity->activity_id, self::$meta_key, $activity->activity_meta );
			}

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

	}
}
