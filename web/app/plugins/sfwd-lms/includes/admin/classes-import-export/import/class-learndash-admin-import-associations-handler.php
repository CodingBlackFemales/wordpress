<?php
/**
 * LearnDash Admin Import Associations Handler.
 *
 * @since 4.3.0
 *
 * @package LearnDash
 */

use LearnDash\Core\Utilities\Cast;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Admin_Import_Associations_Handler' ) ) {
	/**
	 * Class LearnDash Admin Import Associations Handler.
	 *
	 * @since 4.3.0
	 */
	class Learndash_Admin_Import_Associations_Handler {
		const META_KEY_QUIZ_PROGRESS = '_sfwd-quizzes';

		/**
		 * Old statistic ref id => new statistic ref id hash.
		 *
		 * @since 4.3.0
		 *
		 * @var array
		 */
		private $old_new_statistic_ref_id_hash;

		/**
		 * Old user id => new user id hash.
		 *
		 * @since 4.3.0
		 *
		 * @var array
		 */
		private $old_user_id_new_user_id_hash;

		/**
		 * Flag for the course shared steps mode. Default is `true`.
		 *
		 * It helps to skip some unnecessary operations.
		 * It will be overridden in the `setup` method with the actual value from the `learndash_is_course_shared_steps_enabled` function.
		 *
		 * @since 4.18.0
		 *
		 * @var bool
		 */
		private bool $is_course_shared_steps_enabled = true;

		/**
		 * Updates associations that we could not match in importers.
		 *
		 * @since 4.3.0
		 *
		 * @return void
		 */
		public function handle(): void {
			$this->setup();

			$this->update_lessons();
			$this->update_topics();
			$this->update_quizzes();
			$this->update_exams();
			$this->update_groups();
			$this->update_courses();
			$this->update_essays();
			$this->update_assignments();
			$this->update_coupons();
			$this->update_transactions();
			$this->update_users();
			$this->update_post_authors();
		}

		/**
		 * Updates groups.
		 *
		 * @since 4.3.0
		 *
		 * @return void
		 */
		protected function update_groups(): void {
			foreach ( $this->get_imported_post_ids( LDLMS_Post_Types::GROUP ) as $post_id ) {
				$this->update_setting( $post_id, LDLMS_Post_Types::CERTIFICATE );

				learndash_set_groups_users(
					$post_id,
					learndash_get_groups_user_ids( $post_id, true )
				);
			}
		}

		/**
		 * Updates courses.
		 *
		 * @since 4.3.0
		 *
		 * @return void
		 */
		protected function update_courses(): void {
			foreach ( $this->get_imported_post_ids( LDLMS_Post_Types::COURSE ) as $post_id ) {
				$this->update_setting( $post_id, LDLMS_Post_Types::CERTIFICATE );
				$this->update_setting( $post_id, 'exam_challenge' );
				$this->update_course_steps( $post_id );
			}
		}

		/**
		 * Updates lessons.
		 *
		 * @since 4.3.0
		 *
		 * @return void
		 */
		protected function update_lessons(): void {
			// We must not update course associations for lessons in this case.
			if ( $this->is_course_shared_steps_enabled ) {
				return;
			}

			foreach ( $this->get_imported_post_ids( LDLMS_Post_Types::LESSON ) as $post_id ) {
				$this->update_setting( $post_id, LDLMS_Post_Types::COURSE );
			}
		}

		/**
		 * Updates topics.
		 *
		 * @since 4.3.0
		 *
		 * @return void
		 */
		protected function update_topics(): void {
			// We must not update course associations for topics in this case.
			if ( $this->is_course_shared_steps_enabled ) {
				return;
			}

			foreach ( $this->get_imported_post_ids( LDLMS_Post_Types::TOPIC ) as $post_id ) {
				$this->update_setting( $post_id, LDLMS_Post_Types::COURSE );
				$this->update_setting( $post_id, LDLMS_Post_Types::LESSON );
			}
		}

		/**
		 * Updates quizzes.
		 *
		 * @since 4.3.0
		 *
		 * @return void
		 */
		protected function update_quizzes(): void {
			foreach ( $this->get_imported_post_ids( LDLMS_Post_Types::QUIZ ) as $post_id ) {
				$this->update_setting( $post_id, LDLMS_Post_Types::CERTIFICATE );

				// We only must update course associations for quizzes in this case.
				if ( ! $this->is_course_shared_steps_enabled ) {
					$this->update_setting( $post_id, LDLMS_Post_Types::COURSE );
					$this->update_setting( $post_id, LDLMS_Post_Types::LESSON );
				}
			}
		}

		/**
		 * Updates exams.
		 *
		 * @since 4.3.0
		 *
		 * @return void
		 */
		protected function update_exams(): void {
			foreach ( $this->get_imported_post_ids( LDLMS_Post_Types::EXAM ) as $post_id ) {
				$this->update_setting( $post_id, 'exam_challenge_course_show' );
				$this->update_setting( $post_id, 'exam_challenge_course_passed' );
			}
		}

		/**
		 * Updates essays' authors. Deletes an essay if a new author ID was not found.
		 *
		 * @since 4.3.0
		 *
		 * @return void
		 */
		protected function update_essays(): void {
			// All logic has been moved to the `update_post_authors` method.
			// Keep this method for backward compatibility and in case we need some specific logic in the future.
		}

		/**
		 * Updates assignments' authors. Deletes an assignment if a new author ID was not found.
		 *
		 * @since 4.3.0
		 *
		 * @return void
		 */
		protected function update_assignments(): void {
			// All logic has been moved to the `update_post_authors` method.
			// Keep this method for backward compatibility and in case we need some specific logic in the future.
		}

		/**
		 * Updates coupons.
		 *
		 * @since 4.3.0
		 *
		 * @return void
		 */
		protected function update_coupons(): void {
			foreach ( $this->get_imported_post_ids( LDLMS_Post_Types::COUPON ) as $post_id ) {
				foreach ( LEARNDASH_COUPON_ASSOCIATED_FIELDS as $field ) {
					$old_ids = learndash_get_setting( $post_id, $field );

					if ( ! is_array( $old_ids ) || empty( $old_ids ) ) {
						continue;
					}

					$new_ids = array();
					foreach ( $old_ids as $old_id ) {
						$new_id = Learndash_Admin_Import::get_new_post_id_by_old_post_id( $old_id );

						if ( is_null( $new_id ) ) {
							continue;
						}

						$new_ids[] = $new_id;
					}

					learndash_sync_coupon_associated_metas( $post_id, $field, $new_ids );
					learndash_update_setting( $post_id, $field, $new_ids );
				}
			}
		}

		/**
		 * Updates transactions.
		 *
		 * @since 4.3.0
		 *
		 * @return void
		 */
		protected function update_transactions(): void {
			foreach ( $this->get_imported_post_ids( LDLMS_Post_Types::TRANSACTION ) as $post_id ) {
				$old_user_id = get_post_meta(
					$post_id,
					Learndash_Admin_Import::META_KEY_IMPORTED_FROM_USER_ID,
					true
				);
				$new_user_id = intval( $this->old_user_id_new_user_id_hash[ $old_user_id ] ?? 0 );

				// Attached course/group ID.
				$purchased_post_field  = 'post_id';
				$old_purchased_post_id = get_post_meta( $post_id, $purchased_post_field, true );

				// Legacy purchased id fields.
				if ( empty( $old_purchased_post_id ) ) {
					$purchased_post_field  = 'course_id';
					$old_purchased_post_id = get_post_meta( $post_id, $purchased_post_field, true );
				}
				if ( empty( $old_purchased_post_id ) ) {
					$purchased_post_field  = 'group_id';
					$old_purchased_post_id = get_post_meta( $post_id, $purchased_post_field, true );
				}

				$new_purchased_post_id = (int) Learndash_Admin_Import::get_new_post_id_by_old_post_id(
					(int) $old_purchased_post_id
				);

				// Update user ID and post parent ID.

				$post_object = get_post( $post_id );

				wp_update_post(
					array(
						'ID'          => $post_id,
						'post_author' => $new_user_id,
						'post_parent' => (int) Learndash_Admin_Import::get_new_post_id_by_old_post_id(
							$post_object ? $post_object->post_parent : 0
						),
					)
				);

				update_post_meta( $post_id, 'user_id', $new_user_id );

				// Update purchased post ID.
				update_post_meta( $post_id, 'post_id', $new_purchased_post_id );

				// Delete legacy meta keys.
				delete_post_meta( $post_id, 'course_id' );
				delete_post_meta( $post_id, 'group_id' );
			}
		}

		/**
		 * Updates users' progress.
		 *
		 * @since 4.3.0
		 *
		 * @return void
		 */
		protected function update_users(): void {
			$keys_with_id = array(
				LDLMS_Post_Types::QUIZ,
				LDLMS_Post_Types::COURSE,
				LDLMS_Post_Types::LESSON,
				LDLMS_Post_Types::TOPIC,
			);

			foreach ( $this->old_user_id_new_user_id_hash as $user_id ) {
				$quiz_attempts = get_user_meta( $user_id, self::META_KEY_QUIZ_PROGRESS, true );

				if ( ! is_array( $quiz_attempts ) || empty( $quiz_attempts ) ) {
					continue;
				}

				$mapped_quiz_attempts = array();

				foreach ( $quiz_attempts as $quiz_attempt ) {
					$id_key = 'statistic_ref_id';

					$quiz_attempt[ $id_key ] = $this->old_new_statistic_ref_id_hash[ $quiz_attempt[ $id_key ] ] ?? null;

					if ( is_null( $quiz_attempt['statistic_ref_id'] ) ) {
						continue; // skip this quiz attempt.
					}

					foreach ( $keys_with_id as $key_with_id ) {
						$new_post_id = Learndash_Admin_Import::get_new_post_id_by_old_post_id(
							$quiz_attempt[ $key_with_id ]
						);

						if ( is_null( $new_post_id ) ) {
							continue 2; // skip this quiz attempt.
						}

						$quiz_attempt[ $key_with_id ] = $new_post_id;
					}

					$quiz_attempt['pro_quizid'] = get_post_meta(
						$quiz_attempt['quiz'],
						'quiz_pro_id',
						true
					);
					$quiz_attempt['quiz_key']   = implode(
						'_',
						array(
							$quiz_attempt['completed'],
							absint( $quiz_attempt['pro_quizid'] ),
							absint( $quiz_attempt['quiz'] ),
							absint( $quiz_attempt['course'] ),
						)
					);

					$mapped_quiz_attempts[] = $quiz_attempt;
				}

				update_user_meta( $user_id, self::META_KEY_QUIZ_PROGRESS, $mapped_quiz_attempts );
			}
		}

		/**
		 * Returns imported post IDs by post type.
		 *
		 * @since 4.3.0
		 *
		 * @param string $post_type_name Post type name. Optional.
		 *
		 * @return int[]
		 */
		protected function get_imported_post_ids( string $post_type_name = '' ): array {
			$args = array(
				'fields'      => 'ids',
				'post_type'   => empty( $post_type_name )
					? array_merge(
						array_values( LDLMS_Post_Types::get_all_post_types_set() ),
						array( 'page' )
					)
					: LDLMS_Post_Types::get_post_type_slug( $post_type_name ),
				'post_status' => 'any',
				'numberposts' => -1,
				'meta_query'  => array(
					array(
						'key'     => Learndash_Admin_Import::META_KEY_IMPORTED_FROM_POST_ID,
						'compare' => 'EXISTS',
					),
				),
			);

			return get_posts( $args );
		}

		/**
		 * Updates the post setting.
		 *
		 * @since 4.3.0
		 *
		 * @param int    $post_id Post ID.
		 * @param string $setting Post setting name.
		 *
		 * @return void
		 */
		protected function update_setting( int $post_id, string $setting ): void {
			$old_id = (int) learndash_get_setting( $post_id, $setting );

			if ( 0 === $old_id ) {
				return;
			}

			learndash_update_setting(
				$post_id,
				$setting,
				Learndash_Admin_Import::get_new_post_id_by_old_post_id( $old_id )
			);
		}

		/**
		 * Updates course steps meta.
		 *
		 * @since 4.18.0
		 *
		 * @param int $post_id Course Post ID.
		 *
		 * @return void
		 */
		private function update_course_steps( int $post_id ): void {
			// Get course steps handler. If not found, return as we can't update the course steps.

			$course_steps_handler = LDLMS_Factory_Post::course_steps( $post_id );

			if ( ! $course_steps_handler instanceof LDLMS_Course_Steps ) {
				return;
			}

			/*
			 * The current course steps metadata structure can look like this.
			 * Please unserialize the example to see the actual structure, it would take too much space to write it here.
			 *
			 * Example:
			 * `a:7:{s:5:"steps";a:1:{s:1:"h";a:2:{s:12:"sfwd-lessons";a:1:{i:7031;a:2:{s:10:"sfwd-topic";a:1:{i:7033;a:1:{s:9:"sfwd-quiz";a:1:{i:7025;a:0:{}}}}s:9:"sfwd-quiz";a:1:{i:7027;a:0:{}}}}s:9:"sfwd-quiz";a:1:{i:7023;a:0:{}}}}s:9:"course_id";i:7029;s:7:"version";s:7:"4.8.0.1";s:5:"empty";b:0;s:22:"course_builder_enabled";b:1;s:27:"course_shared_steps_enabled";b:1;s:11:"steps_count";i:3;}`.
			 */
			$existing_course_steps_metadata = get_post_meta( $post_id, 'ld_course_steps', true );

			// If the course steps metadata is not found or is not in the expected format, we need to build it later.
			if (
				! is_array( $existing_course_steps_metadata )
				|| ! isset( $existing_course_steps_metadata['steps'] )
				|| ! isset( $existing_course_steps_metadata['steps']['h'] )
				|| ! is_array( $existing_course_steps_metadata['steps']['h'] )
			) {
				// Set a flag to update the course steps after the import is completed.
				update_post_meta( $post_id, 'course_steps_update_after_import_is_needed', true );

				return;
			}

			// Get existing course steps.
			$existing_course_steps = $existing_course_steps_metadata['steps']['h'];

			// Get post type slugs.

			$lesson_post_type = LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::LESSON );
			$quiz_post_type   = LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::QUIZ );
			$topic_post_type  = LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TOPIC );

			// Create new empty course steps.
			$new_course_steps = [
				$lesson_post_type => [],
				$quiz_post_type   => [],
			];

			// Map lessons.

			if ( isset( $existing_course_steps[ $lesson_post_type ] ) ) {
				$existing_lessons_data = $existing_course_steps[ $lesson_post_type ];

				foreach ( array_keys( $existing_lessons_data ) as $lesson_id ) {
					$new_lesson_id = Learndash_Admin_Import::get_new_post_id_by_old_post_id(
						Cast::to_int( $lesson_id )
					);

					if ( is_null( $new_lesson_id ) ) {
						continue;
					}

					$new_course_steps[ $lesson_post_type ][ $new_lesson_id ] = [];

					// Map lesson topics.
					if ( isset( $existing_lessons_data[ $lesson_id ][ $topic_post_type ] ) ) {
						foreach ( array_keys( $existing_lessons_data[ $lesson_id ][ $topic_post_type ] ) as $topic_id ) {
							$new_topic_id = Learndash_Admin_Import::get_new_post_id_by_old_post_id(
								Cast::to_int( $topic_id )
							);

							if ( is_null( $new_topic_id ) ) {
								continue;
							}

							$new_course_steps[ $lesson_post_type ][ $new_lesson_id ][ $topic_post_type ][ $new_topic_id ] = [];

							// Map topic quizzes.
							if ( isset( $existing_lessons_data[ $lesson_id ][ $topic_post_type ][ $topic_id ][ $quiz_post_type ] ) ) {
								foreach ( array_keys( $existing_lessons_data[ $lesson_id ][ $topic_post_type ][ $topic_id ][ $quiz_post_type ] ) as $quiz_id ) {
									$new_quiz_id = Learndash_Admin_Import::get_new_post_id_by_old_post_id(
										Cast::to_int( $quiz_id )
									);

									if ( ! is_null( $new_quiz_id ) ) {
										$new_course_steps[ $lesson_post_type ][ $new_lesson_id ][ $topic_post_type ][ $new_topic_id ][ $quiz_post_type ][ $new_quiz_id ] = [];
									}
								}
							}
						}
					}

					// Map lesson quizzes.
					if ( isset( $existing_lessons_data[ $lesson_id ][ $quiz_post_type ] ) ) {
						foreach ( array_keys( $existing_lessons_data[ $lesson_id ][ $quiz_post_type ] ) as $quiz_id ) {
							$new_quiz_id = Learndash_Admin_Import::get_new_post_id_by_old_post_id( Cast::to_int( $quiz_id ) );

							if ( ! is_null( $new_quiz_id ) ) {
								$new_course_steps[ $lesson_post_type ][ $new_lesson_id ][ $quiz_post_type ][ $new_quiz_id ] = [];
							}
						}
					}
				}
			}

			// Map final quizzes.

			if ( isset( $existing_course_steps[ $quiz_post_type ] ) ) {
				foreach ( array_keys( $existing_course_steps[ $quiz_post_type ] ) as $quiz_id ) {
					$new_quiz_id = Learndash_Admin_Import::get_new_post_id_by_old_post_id(
						Cast::to_int( $quiz_id )
					);

					if ( ! is_null( $new_quiz_id ) ) {
						$new_course_steps[ $quiz_post_type ][ $new_quiz_id ] = [];
					}
				}
			}

			// Update course steps.

			$course_steps_handler->set_steps_dirty();
			$course_steps_handler->set_steps_keeping_sections( $new_course_steps );
		}

		/**
		 * Assigns post authors where possible. Deletes essay and assignment posts if the author is not found.
		 *
		 * @since 4.5.1
		 *
		 * @return void
		 */
		private function update_post_authors(): void {
			foreach ( $this->get_imported_post_ids() as $post_id ) {
				$old_user_id = get_post_meta(
					$post_id,
					Learndash_Admin_Import::META_KEY_IMPORTED_FROM_USER_ID,
					true
				);
				$new_user_id = $this->old_user_id_new_user_id_hash[ $old_user_id ] ?? null;

				if ( ! is_null( $new_user_id ) ) {
					// Assign original post author with the new user ID.
					wp_update_post(
						array(
							'ID'          => $post_id,
							'post_author' => $new_user_id,
						)
					);
				} else {
					// Delete essay and assignment posts where the author is not found.
					if (
						in_array(
							LDLMS_Post_Types::get_post_type_key(
								strval( get_post_type( $post_id ) )
							),
							array( LDLMS_Post_Types::ESSAY, LDLMS_Post_Types::ASSIGNMENT ),
							true
						)
					) {
						wp_delete_post( $post_id, true );
					}

					// For other post types, the post has been assigned with the user running import, let's keep it.
				}
			}
		}

		/**
		 * Sets up the handler.
		 *
		 * @since 4.18.0
		 *
		 * @return void
		 */
		private function setup(): void {
			$old_new_statistic_ref_id_hash = get_transient( Learndash_Admin_Import::TRANSIENT_KEY_STATISTIC_REF_IDS );

			$this->old_new_statistic_ref_id_hash  = is_array( $old_new_statistic_ref_id_hash ) ? $old_new_statistic_ref_id_hash : array();
			$this->old_user_id_new_user_id_hash   = Learndash_Admin_Import::get_old_user_id_new_user_id_hash();
			$this->is_course_shared_steps_enabled = learndash_is_course_shared_steps_enabled();
		}
	}
}
