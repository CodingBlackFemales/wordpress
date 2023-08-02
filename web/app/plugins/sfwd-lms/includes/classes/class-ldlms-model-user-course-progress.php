<?php
/**
 * LearnDash User Progress Course Class.
 *
 * @since 3.4.0
 * @package LearnDash\User\Progression
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LDLMS_Model_User_Course_Progress' ) ) && ( class_exists( 'LDLMS_Model_User' ) ) ) {

	/**
	 * Class for LearnDash LearnDash User Progress Course Class.
	 *
	 * @since 3.4.0
	 * @uses LDLMS_Model_User
	 */
	class LDLMS_Model_User_Course_Progress extends LDLMS_Model_User {

		/**
		 * User Progress Loaded flag.
		 *
		 * @var boolean $progress_legacy_loaded Set to false initially. Set to true once user
		 * progress has been loaded.
		 */
		private $progress_legacy_loaded = false;

		/**
		 * User Course Progress Meta Key.
		 *
		 * @var boolean $progress_meta_key Meta Key used to load progress.
		 */
		private $progress_meta_key = '_sfwd-course_progress';

		/**
		 * Legacy User Course Progress array.
		 *
		 * This array structure is the current stored in user meta.
		 *
		 * @var array $progress_legacy Array of user course progress.
		 */
		private $progress_legacy = null;

		/**
		 * User Course Progress array.
		 *
		 * This array structure will contain different nodes of various 'views'
		 * of the data model. One nice 'co' will be the completion order of the
		 * course steps.
		 *
		 * @var array $progress Array of user course progress.
		 */
		protected $progress = array();

		/**
		 * Internal flag to know when we are updating the user meta.
		 *
		 * Used to prevent the hooks into the WP user meta add/update from being processed.
		 *
		 * @since 3.4.0
		 *
		 * @var bool $user_meta_updating Flag indicating user meta is updated.
		 */
		private $user_meta_updating = false;

		/**
		 * Public constructor for class.
		 *
		 * @since 3.4.0
		 *
		 * @param integer $user_id User ID.
		 *
		 * @throws LDLMS_Exception_NotFound When no user.
		 */
		public function __construct( $user_id = 0 ) {
			if ( ! $this->initialize( $user_id ) ) {
				throw new LDLMS_Exception_NotFound();
			} else {
				// Hook into Add/Update actions to the course progress usermeta.
				add_action( 'added_user_meta', array( $this, 'changed_user_meta_progress_legacy' ), 30, 4 );
				add_action( 'updated_user_meta', array( $this, 'changed_user_meta_progress_legacy' ), 30, 4 );

				return $this;
			}
		}

		/**
		 * Initialize the User class vars.
		 *
		 * @since 3.4.0
		 *
		 * @param int $user_id User ID to use for class instance.
		 *
		 * @return bool True if success.
		 */
		private function initialize( $user_id = 0 ) {
			$return = false;

			$user_id = absint( $user_id );

			if ( empty( $user_id ) ) {
				$return = false;
			}

			$user = get_user_by( 'ID', $user_id );
			if ( ( $user ) && ( is_a( $user, 'WP_User' ) ) && ( $user->ID === $user_id ) ) {
				$this->user_id = $user_id;
				$this->user    = $user;
				$return        = true;
			} else {
				$return = false;
			}

			return $return;
		}

		/**
		 * Hook into the user meta update logic from WordPress so we know if external
		 * processes add/update the user meta value. If so we set the dirty flag to
		 * force a reload of the meta and rebuild the data structure.
		 *
		 * @since 3.4.0
		 *
		 * See the WordPress action 'updated_user_meta' for source of parameters.
		 * @param int    $meta_id     ID of updated metadata entry.
		 * @param int    $object_id   Object ID.
		 * @param string $meta_key    Meta key.
		 * @param mixed  $_meta_value Meta value.
		 */
		public function changed_user_meta_progress_legacy( $meta_id, $object_id, $meta_key, $_meta_value ) {
			if ( ( $object_id === $this->user_id ) && ( $meta_key === $this->progress_meta_key ) ) {
				if ( ! $this->user_meta_updating ) {
					$this->set_progress_unloaded();
				}
			}
		}

		/**
		 * Load the Course Progress from the usermeta record.
		 *
		 * @since 3.4.0
		 *
		 * @param bool $force_reload True to force reload of usermeta item.
		 */
		public function load_user_meta_progress_legacy( $force_reload = false ) {
			if ( ( ! $this->progress_legacy_loaded ) || ( true === $force_reload ) ) {
				$this->progress_legacy_loaded = true;

				$this->progress_legacy = get_user_meta( $this->user_id, $this->progress_meta_key, true );
				if ( ! is_array( $this->progress_legacy ) ) {
					$this->progress_legacy = array();
				}
			}
		}

		/**
		 * Save the Course Progress to the usermeta record.
		 *
		 * @since 3.4.0
		 *
		 * @return boolean True on update success.
		 */
		protected function save_user_meta_progress_legacy() {
			if ( ! empty( $this->user_id ) ) {
				$return = update_user_meta( $this->user_id, $this->progress_meta_key, $this->progress_legacy );

				if ( $return ) {
					return true;
				}
			}
			return false;
		}

		/**
		 * Build User Course Progress nodes for Course.
		 *
		 * @since 3.4.0
		 *
		 * @param integer $course_id Course ID to load progress for.
		 */
		protected function load_course_progress( $course_id = 0 ) {
			if ( ! empty( $course_id ) ) {
				if ( ! isset( $this->progress[ $course_id ] ) ) {
					$this->progress[ $course_id ] = array();
				}

				if ( ! isset( $this->progress[ $course_id ]['legacy'] ) ) {
					$this->progress[ $course_id ]['legacy'] = $this->build_course_progress_legacy( $course_id );

					// Update the legacy data structure as other processes will reference it.
					$this->progress_legacy[ $course_id ] = $this->progress[ $course_id ]['legacy'];

				}

				if ( ! isset( $this->progress[ $course_id ]['co'] ) ) {
					$this->progress[ $course_id ]['co'] = $this->build_course_progress_completion_order( $course_id );
				}

				if ( ! isset( $this->progress[ $course_id ]['l'] ) ) {
					$this->progress[ $course_id ]['l'] = $this->build_course_progress_linear_order( $course_id );
				}

				if ( ! isset( $this->progress[ $course_id ]['summary'] ) ) {
					$this->progress[ $course_id ]['summary'] = $this->build_course_progress_summary( $course_id );

					// Update the legacy data structure as other processes will reference it.
					$this->progress[ $course_id ]['legacy'] = array_merge( $this->progress[ $course_id ]['legacy'], $this->progress[ $course_id ]['summary'] );
				}
			}
		}

		/**
		 * Get the legacy user meta course progression node for a specific course.
		 *
		 * @since 3.4.0
		 *
		 * @param integer $course_id Course ID to load progress for.
		 *
		 * @return array
		 */
		public function get_course_progress_legacy( $course_id = 0 ) {
			$course_id = absint( $course_id );

			if ( ! empty( $course_id ) ) {
				if ( isset( $this->progress[ $course_id ]['legacy'] ) ) {
					$progress_legacy = $this->progress[ $course_id ]['legacy'];
				} else {
					$this->load_user_meta_progress_legacy();
					if ( isset( $this->progress_legacy[ $course_id ] ) ) {
						$progress_legacy = $this->progress_legacy[ $course_id ];
					}
				}
			}

			if ( ! isset( $progress_legacy ) ) {
				$progress_legacy = array();
			}

			if ( ( ! isset( $progress_legacy['lessons'] ) ) || ( ! is_array( $progress_legacy['lessons'] ) ) ) {
				$progress_legacy['lessons'] = array();
			}

			if ( ( ! isset( $progress_legacy['topics'] ) ) || ( ! is_array( $progress_legacy['topics'] ) ) ) {
				$progress_legacy['topics'] = array();
			}

			if ( ! isset( $progress_legacy['completed'] ) ) {
				$progress_legacy['completed'] = 0;
			} else {
				$progress_legacy['completed'] = absint( $progress_legacy['completed'] );
			}

			if ( ! isset( $progress_legacy['total'] ) ) {
				$progress_legacy['total'] = 0;
			} else {
				$progress_legacy['total'] = absint( $progress_legacy['total'] );
			}

			if ( ! isset( $progress_legacy['last_id'] ) ) {
				$progress_legacy['last_id'] = 0;
			} else {
				$progress_legacy['last_id'] = absint( $progress_legacy['last_id'] );
			}

			return $progress_legacy;
		}

		/**
		 * Build Course Progress node for 'legacy'.
		 *
		 * This is the legacy tree structure used by the '_sfwd-course_progress'
		 * user_meta key.
		 *
		 * @since 3.4.0
		 *
		 * @param integer $course_id Course ID to load progress for.
		 */
		protected function build_course_progress_legacy( $course_id = 0 ) {
			$course_steps_legacy = array();

			$course_id = absint( $course_id );
			if ( ! empty( $course_id ) ) {

				$steps_legacy    = learndash_course_get_steps_by_type( $course_id, 'legacy' );
				$progress_legacy = $this->get_course_progress_legacy( $course_id );

				/**
				 * Merge the Lesson and Topic steps from the known steps_legacy array into
				 * the current progress_legacy array.
				 */
				if ( ( isset( $steps_legacy['lessons'] ) ) && ( ! empty( $steps_legacy['lessons'] ) ) ) {

					foreach ( $steps_legacy['lessons'] as $lesson_id => $lesson_id_status ) {
						if ( ( isset( $progress_legacy['lessons'][ $lesson_id ] ) ) && ( $progress_legacy['lessons'][ $lesson_id ] ) ) {
							$steps_legacy['lessons'][ $lesson_id ] = $progress_legacy['lessons'][ $lesson_id ];
						}
					}
					$progress_legacy['lessons'] = $steps_legacy['lessons'];
				}

				if ( ( isset( $steps_legacy['topics'] ) ) && ( ! empty( $steps_legacy['topics'] ) ) ) {
					foreach ( $steps_legacy['topics'] as $lesson_id => $lesson_set ) {
						if ( ( is_array( $lesson_set ) ) && ( ! empty( $lesson_set ) ) ) {
							foreach ( $lesson_set as $topic_id => $topic_id_status ) {
								if ( ( isset( $progress_legacy['topics'][ $lesson_id ][ $topic_id ] ) ) && ( $progress_legacy['topics'][ $lesson_id ][ $topic_id ] ) ) {
									$steps_legacy['topics'][ $lesson_id ][ $topic_id ] = $progress_legacy['topics'][ $lesson_id ][ $topic_id ];
								}
							}
						}
					}
					$progress_legacy['topics'] = $steps_legacy['topics'];
				}
			}

			return $progress_legacy;
		}

		/**
		 * Build the Course Progress node for 'co' Completion order.
		 *
		 * This is a linear step order rather than the 'legacy' tree
		 * and easier to determine if previous steps are not completed.
		 *
		 * @since 3.4.0
		 *
		 * @param integer $course_id Course ID to load progress for.
		 */
		protected function build_course_progress_completion_order( $course_id = 0 ) {
			$progress_co = array();

			$course_id = absint( $course_id );
			if ( ! empty( $course_id ) ) {
				$progress_legacy = $this->get_course_progress_legacy( $course_id );

				$course_steps_co = learndash_course_get_steps_by_type( $course_id, 'co' );
				if ( ! empty( $course_steps_co ) ) {
					$lesson_slug = learndash_get_post_type_slug( 'lesson' );
					$topic_slug  = learndash_get_post_type_slug( 'topic' );
					$quiz_slug   = learndash_get_post_type_slug( 'quiz' );

					foreach ( $course_steps_co as $course_step ) {
						list( $s_post_type, $s_post_id ) = explode( ':', $course_step );
						if ( in_array( $s_post_type, array( $lesson_slug, $topic_slug ), true ) ) {
							$progress_co[ $course_step ] = 0;
						} elseif ( in_array( $s_post_type, array( $quiz_slug ), true ) ) {
							$has_completed_quiz = learndash_user_quiz_has_completed( $this->user_id, $s_post_id, $course_id );

							$progress_co[ $course_step ] = (int) $has_completed_quiz;
						}
					}

					if ( ( isset( $progress_legacy['lessons'] ) ) && ( ! empty( $progress_legacy['lessons'] ) ) ) {
						foreach ( $progress_legacy['lessons'] as $lesson_id => $status ) {
							if ( isset( $progress_co[ $lesson_slug . ':' . $lesson_id ] ) ) {
								$progress_co[ $lesson_slug . ':' . $lesson_id ] = $status;
							}
						}
					}

					if ( ( isset( $progress_legacy['topics'] ) ) && ( ! empty( $progress_legacy['topics'] ) ) ) {
						foreach ( $progress_legacy['topics'] as $lesson_id => $topics ) {
							if ( ( is_array( $topics ) ) && ( ! empty( $topics ) ) ) {
								foreach ( $topics as $topic_id => $status ) {
									if ( isset( $progress_co[ $topic_slug . ':' . $topic_id ] ) ) {
										$progress_co[ $topic_slug . ':' . $topic_id ] = $status;
									}
								}
							}
						}
					}
				}
			}
			return $progress_co;
		}

		/**
		 * Build the Course Progress node for 'l' Linear order.
		 *
		 * This is a linear step order rather than the 'legacy' tree
		 * and easier to determine if previous steps are not completed.
		 *
		 * @since 4.3.0
		 *
		 * @param integer $course_id Course ID to load progress for.
		 */
		protected function build_course_progress_linear_order( $course_id = 0 ) {
			$progress_co = array();

			$course_id = absint( $course_id );
			if ( ! empty( $course_id ) ) {
				$progress_legacy = $this->get_course_progress_legacy( $course_id );

				$course_steps_co = learndash_course_get_steps_by_type( $course_id, 'l' );
				if ( ! empty( $course_steps_co ) ) {
					$lesson_slug = learndash_get_post_type_slug( 'lesson' );
					$topic_slug  = learndash_get_post_type_slug( 'topic' );
					$quiz_slug   = learndash_get_post_type_slug( 'quiz' );

					foreach ( $course_steps_co as $course_step ) {
						list( $s_post_type, $s_post_id ) = explode( ':', $course_step );
						if ( in_array( $s_post_type, array( $lesson_slug, $topic_slug ), true ) ) {
							$progress_co[ $course_step ] = 0;
						} elseif ( in_array( $s_post_type, array( $quiz_slug ), true ) ) {
							$has_completed_quiz = learndash_user_quiz_has_completed( $this->user_id, $s_post_id, $course_id );

							$progress_co[ $course_step ] = (int) $has_completed_quiz;
						}
					}

					if ( ( isset( $progress_legacy['lessons'] ) ) && ( ! empty( $progress_legacy['lessons'] ) ) ) {
						foreach ( $progress_legacy['lessons'] as $lesson_id => $status ) {
							if ( isset( $progress_co[ $lesson_slug . ':' . $lesson_id ] ) ) {
								$progress_co[ $lesson_slug . ':' . $lesson_id ] = $status;
							}
						}
					}

					if ( ( isset( $progress_legacy['topics'] ) ) && ( ! empty( $progress_legacy['topics'] ) ) ) {
						foreach ( $progress_legacy['topics'] as $lesson_id => $topics ) {
							if ( ( is_array( $topics ) ) && ( ! empty( $topics ) ) ) {
								foreach ( $topics as $topic_id => $status ) {
									if ( isset( $progress_co[ $topic_slug . ':' . $topic_id ] ) ) {
										$progress_co[ $topic_slug . ':' . $topic_id ] = $status;
									}
								}
							}
						}
					}
				}
			}
			return $progress_co;
		}


		/**
		 * Build the Course Progress Completes Steps Count.
		 *
		 * @since 3.4.0
		 *
		 * @param integer $course_id Course ID to load progress for.
		 *
		 * @return integer $course_completed_count.
		 */
		protected function build_course_progress_completed_count( $course_id = 0 ) {
			$course_completed_count = 0;

			$course_id = absint( $course_id );
			if ( ! empty( $course_id ) ) {
				if ( ( isset( $this->progress[ $course_id ]['co'] ) ) && ( ! empty( $this->progress[ $course_id ]['co'] ) ) ) {
					foreach ( $this->progress[ $course_id ]['co'] as $progress_step_key => $status ) {
						list( $progress_step_post_type, $progress_step_post_id ) = explode( ':', $progress_step_key );
						if ( in_array( $progress_step_post_type, learndash_get_post_type_slug( array( 'lesson', 'topic' ) ), true ) ) {
							$course_completed_count += (int) $status;
						}
					}
				}

				if ( ( learndash_has_global_quizzes( $course_id ) ) && ( learndash_is_all_global_quizzes_complete( $this->user_id, $course_id ) ) ) {
					++$course_completed_count;
				}
			}

			return $course_completed_count;
		}

		/**
		 * Build the Course Progress node for 'summary' Summary.
		 *
		 * @since 3.4.0
		 *
		 * @param integer $course_id Course ID to load progress for.
		 */
		protected function build_course_progress_summary( $course_id = 0 ) {
			$progress_summary = array(
				'completed' => 0,
				'total'     => 0,
				'status'    => 'not_started',
			);

			$course_id = absint( $course_id );
			if ( ! empty( $course_id ) ) {
				$progress_legacy = $this->get_course_progress_legacy( $course_id );

				$progress_summary['total']     = (int) learndash_course_get_steps_count( $course_id );
				$progress_summary['completed'] = (int) $this->build_course_progress_completed_count( $course_id );

				if ( $progress_summary['completed'] > $progress_summary['total'] ) {
					$progress_summary['completed'] = $progress_summary['total'];
				}

				$completed_on = get_user_meta( $this->user_id, 'course_completed_' . $course_id, true );
				if ( ! empty( $completed_on ) ) {
					$progress_summary['status'] = 'completed';

					/**
					 * If the status is 'completed' we make sure the user completed
					 * steps match the total course steps.
					 */
					$progress_summary['completed'] = $progress_summary['total'];
				} elseif ( $progress_summary['completed'] > 0 ) {
					$progress_summary['status'] = 'in_progress';
				}
			}

			return $progress_summary;
		}

		/**
		 * Build the Course Progress node for 'activity' Activity.
		 *
		 * The activity items are pulled from the User Activity tables.
		 *
		 * @since 3.4.0
		 *
		 * @param integer $course_id Course ID to load progress for.
		 */
		protected function build_course_progress_by_activity( $course_id = 0 ) {
			global $wpdb;

			$progress_activity = array();

			if ( ! empty( $course_id ) ) {

				$course_steps_co = learndash_course_get_steps_by_type( $course_id, 'co' );

				$course_activity_remove_items = array();

				// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching -- Only used for querying.
				$activity_items = $wpdb->get_results(
					$wpdb->prepare( 'SELECT * FROM ' . esc_sql( LDLMS_DB::get_table_name( 'user_activity' ) ) . ' WHERE course_id=%d AND user_id=%d', $course_id, $this->user_id ),
					ARRAY_A
				);

				if ( ! empty( $activity_items ) ) {
					foreach ( $activity_items as $activity_item ) {
						$activity_item['activity_id']        = ( isset( $activity_item['activity_id'] ) ) ? absint( $activity_item['activity_id'] ) : 0;
						$activity_item['user_id']            = ( isset( $activity_item['user_id'] ) ) ? absint( $activity_item['user_id'] ) : 0;
						$activity_item['post_id']            = ( isset( $activity_item['post_id'] ) ) ? absint( $activity_item['post_id'] ) : 0;
						$activity_item['course_id']          = ( isset( $activity_item['course_id'] ) ) ? absint( $activity_item['course_id'] ) : 0;
						$activity_item['activity_type']      = ( isset( $activity_item['activity_type'] ) ) ? esc_attr( $activity_item['activity_type'] ) : '';
						$activity_item['activity_status']    = ( isset( $activity_item['activity_status'] ) ) ? (bool) $activity_item['activity_status'] : 0;
						$activity_item['activity_started']   = ( isset( $activity_item['activity_started'] ) ) ? absint( $activity_item['activity_started'] ) : 0;
						$activity_item['activity_completed'] = ( isset( $activity_item['activity_completed'] ) ) ? absint( $activity_item['activity_completed'] ) : 0;
						$activity_item['activity_updated']   = ( isset( $activity_item['activity_updated'] ) ) ? absint( $activity_item['activity_updated'] ) : 0;

						if ( ( ! empty( $activity_item['post_id'] ) ) && ( ! empty( $activity_item['activity_type'] ) ) ) {
							$activity_item_post_type = learndash_get_post_type_slug( $activity_item['activity_type'] );
							if ( in_array( $activity_item_post_type, learndash_get_post_types( 'course' ), true ) ) {
								if ( ( 'course' === $activity_item['activity_type'] ) || ( in_array( $activity_item_post_type . ':' . $activity_item['post_id'], $course_steps_co, true ) ) ) {
									$progress_activity[ $activity_item_post_type . ':' . $activity_item['post_id'] ] = $activity_item;
								}
							}
						}
					}
				}
			}

			return $progress_activity;
		}

		/**
		 * Sets the Progress loaded flag to false and clears all data
		 * structures.
		 *
		 * This will force the progress to be reloaded from meta. This is
		 * called when te user meta is updated either via this class or from
		 * external.
		 *
		 * @since 3.4.0
		 */
		public function set_progress_unloaded() {
			if ( ! empty( $this->user_id ) ) {
				$this->progress_legacy_loaded = false;
				$this->progress               = array();
				$this->progress_legacy        = array();
			}
		}

		/**
		 * Get User Course Progress by progress type.
		 *
		 * @since 3.4.0
		 *
		 * @param integer $course_id     Course ID for progress set to return.
		 * @param string  $progress_type Progress node. Default 'legacy'.
		 *
		 * @return array of User Course Progress.
		 */
		public function get_progress( $course_id = 0, $progress_type = 'legacy' ) {
			if ( ! empty( $course_id ) ) {
				$this->load_course_progress( $course_id );

				if ( isset( $this->progress[ $course_id ] ) ) {
					if ( 'activity' === $progress_type ) {
						return $this->build_course_progress_by_activity( $course_id );
					} else {
						if ( ( ! empty( $progress_type ) ) && ( isset( $this->progress[ $course_id ][ $progress_type ] ) ) ) {
							return $this->progress[ $course_id ][ $progress_type ];
						} else {
							return $this->progress[ $course_id ];
						}
					}
				}
			}
			return array();
		}

		/**
		 * Set Progress steps.
		 *
		 * @since 3.4.0
		 *
		 * @param integer $course_id Course ID of progress to update.
		 * @param array   $progress Array of user progress. Array structure
		 *                should match the 'legacy' format.
		 */
		public function set_progress( $course_id = 0, $progress = array() ) {
			$course_id = absint( $course_id );
			if ( ( ! empty( $this->user_id ) ) && ( ! empty( $course_id ) ) ) {
				// For reload of the user meta in case there were other changes outside of our processes.
				$this->load_user_meta_progress_legacy( true );

				$this->progress_legacy[ $course_id ] = $progress;

				return $this->save_user_meta_progress_legacy();
			}
		}

		/**
		 * Get User Course Progress by 'legacy' type.
		 *
		 * @since 3.4.0
		 *
		 * @param integer $course_id     Course ID for progress set to return.
		 *
		 * @return array of User Course Progress.
		 */
		public function get_progress_legacy( $course_id = 0 ) {
			return $this->get_progress( $course_id, 'legacy' );
		}
	}
}

/**
 * Utility function to get a user's progress for a single course.
 *
 * @since 3.4.0
 *
 * @param integer $user_id   User ID.
 * @param integer $course_id Course ID.
 * @param string  $type      Progress Type. Default 'legacy'.
 * possible values 'legacy', 'co', 'summary'.
 *
 * @return Array of single course user progress. Format of
 *         array should match 'legacy' structure.
 */
function learndash_user_get_course_progress( $user_id = 0, $course_id = 0, $type = 'legacy' ) {
	$user_id   = absint( $user_id );
	$course_id = absint( $course_id );

	if ( ! empty( $user_id ) ) {
		$course_progress_object = LDLMS_Factory_User::course_progress( $user_id );
		if ( $course_progress_object ) {
			return $course_progress_object->get_progress( $course_id, $type );
		}
	}

	return array();
}

/**
 * Utility function to update a user's course progress instance.
 *
 * @since 3.4.0
 *
 * @param integer $user_id   User ID.
 * @param integer $course_id Course ID.
 * @param array   $progress  Array of single course user progress. Format of
 *                array should match 'legacy' structure.
 */
function learndash_user_set_course_progress( $user_id = 0, $course_id = 0, $progress = array() ) {
	$user_id   = absint( $user_id );
	$course_id = absint( $course_id );

	if ( ! empty( $user_id ) ) {
		$course_progress_object = LDLMS_Factory_User::course_progress( $user_id );
		if ( $course_progress_object ) {
			return $course_progress_object->set_progress( $course_id, $progress );
		}
	}
}

/**
 * Utility function to get the previous incomplete course step for user.
 *
 * @since 3.4.0
 * @since 4.0.2 Added $return_parent_id parameter.
 *
 * @param integer $user_id          User ID.
 * @param integer $course_id        Course ID.
 * @param integer $step_id          Course Step ID.
 * @param bool    $return_parent_id Return the parent step id. Default true. See function code for details.
 */
function learndash_user_progress_get_previous_incomplete_step( $user_id = 0, $course_id = 0, $step_id = 0, $return_parent_id = true ) {
	$user_id = absint( $user_id );
	if ( empty( $user_id ) ) {
		return false;
	}

	$course_id = absint( $course_id );
	if ( empty( $course_id ) ) {
		return false;
	}

	$step_id = absint( $step_id );
	if ( empty( $step_id ) ) {
		return false;
	}

	$course_progress_object = LDLMS_Factory_User::course_progress( $user_id );
	if ( ! $course_progress_object ) {
		return false;
	}

	$course_progress_steps = $course_progress_object->get_progress( $course_id, 'co' );
	if ( ! empty( $course_progress_steps ) ) {
		$step_key = get_post_type( $step_id ) . ':' . $step_id;
		foreach ( $course_progress_steps as $progress_step_key => $progress_status ) {
			if ( $step_key === $progress_step_key ) {
				return $step_id;
			} elseif ( ! (bool) $progress_status ) {
				list( $progress_step_post_type, $progress_step_post_id ) = explode( ':', $progress_step_key );

				$progress_step_post_id = absint( $progress_step_post_id );
				if ( true === $return_parent_id ) {
					/**
					 * The reason to use the '$return_parent_id' is true.
					 * When this function is called from the main lesson.php template it needs to check
					 * if previous lessons are completed. If we only returned the incomplete step id it
					 * was cause the alert banner to show on the lesson instead of the lesson steps table.
					 */
					$parent_steps = learndash_course_get_all_parent_step_ids( $course_id, $progress_step_post_id );
					$parent_steps = array_map( 'absint', $parent_steps );
					if ( ( empty( $parent_steps ) ) || ( ! in_array( $step_id, $parent_steps, true ) ) ) {
						return absint( $progress_step_post_id );
					}
				} elseif ( ! empty( $progress_step_post_id ) ) {
					return $progress_step_post_id;
				}
			}
		}
	}

	return false;
}

/**
 * Utility function to get the next incomplete course step for user.
 *
 * @since 4.2.0
 *
 * @param int $user_id   User ID.
 * @param int $course_id Course ID.
 * @param int $step_id   Course Step ID.
 *
 * @return int The next incomplete step id or 0 if none found.
 */
function learndash_user_progress_get_next_incomplete_step( $user_id = 0, $course_id = 0, $step_id = 0 ) {
	$user_id = absint( $user_id );
	if ( empty( $user_id ) ) {
		return false;
	}

	$course_id = absint( $course_id );
	if ( empty( $course_id ) ) {
		return false;
	}

	$step_id = absint( $step_id );
	if ( empty( $step_id ) ) {
		return false;
	}

	$course_progress_object = LDLMS_Factory_User::course_progress( $user_id );
	if ( ! $course_progress_object ) {
		return false;
	}

	$course_progress_steps = $course_progress_object->get_progress( $course_id, 'l' );
	if ( empty( $course_progress_steps ) ) {
		return false;
	}
	$step_key = get_post_type( $step_id ) . ':' . $step_id;

	$found_key = false;
	foreach ( $course_progress_steps as $progress_step_key => $progress_status ) {
		if ( false === $found_key ) {
			if ( $step_key === $progress_step_key ) {
				$found_key = true;
			}
		} elseif ( ! (bool) $progress_status ) {
			list( $progress_step_post_type, $progress_step_post_id ) = explode( ':', $progress_step_key );
			return absint( $progress_step_post_id );
		}
	}

	return 0;
}

/**
 * Utility function to get all incomplete course step for user.
 *
 * @since 3.4.0
 *
 * @param integer $user_id   User ID.
 * @param integer $course_id Course ID.
 *
 * @return array Array of incomplete course step IDs.
 */
function learndash_user_progress_get_all_incomplete_steps( $user_id = 0, $course_id = 0 ) {
	$user_id = absint( $user_id );
	if ( empty( $user_id ) ) {
		return false;
	}

	$course_id = absint( $course_id );
	if ( empty( $course_id ) ) {
		return false;
	}

	$course_progress_object = LDLMS_Factory_User::course_progress( $user_id );
	if ( ! $course_progress_object ) {
		return false;
	}

	$incomplete_steps = array();

	$course_progress_steps = $course_progress_object->get_progress( $course_id, 'co' );
	if ( ! empty( $course_progress_steps ) ) {
		foreach ( $course_progress_steps as $progress_step_key => $progress_status ) {
			if ( ! (bool) $progress_status ) {
				list( $progress_step_post_type, $progress_step_post_id ) = explode( ':', $progress_step_key );

				$incomplete_steps[] = absint( $progress_step_post_id );
			}
		}
	}

	return $incomplete_steps;
}

/**
 * Utility function to get first incomplete course step for user.
 *
 * @since 3.4.0
 *
 * @param int $user_id   User ID.
 * @param int $course_id Course ID.
 *
 * @return int Incomplete course step ID.
 */
function learndash_user_progress_get_first_incomplete_step( $user_id = 0, $course_id = 0 ) {
	$step_id = 0;

	$user_id = absint( $user_id );
	if ( empty( $user_id ) ) {
		return $step_id;
	}

	$course_id = absint( $course_id );
	if ( empty( $course_id ) ) {
		return $step_id;
	}

	$incomplete_steps = learndash_user_progress_get_all_incomplete_steps( $user_id, $course_id );
	if ( ! empty( $incomplete_steps ) ) {
		$step_id = absint( $incomplete_steps[0] );
	}

	return $step_id;
}

/**
 * Utility function to return incomplete course step within a parent step.
 *
 * @since 4.2.0
 *
 * @param integer $user_id   User ID.
 * @param integer $course_id Course ID.
 * @param integer $step_id   Course Step ID.
 *
 * @return array Array of incomplete step IDs.
 */
function learndash_user_progression_get_incomplete_child_steps( $user_id = 0, $course_id = 0, $step_id = 0 ) {

	$incomplete_child_steps = array();

	$user_id   = absint( $user_id );
	$course_id = absint( $course_id );
	$step_id   = absint( $step_id );

	$child_steps = learndash_course_get_children_of_step( $course_id, $step_id, '', 'ids', true );
	if ( ! empty( $child_steps ) ) {
		foreach ( $child_steps as $child_step_id ) {
			if ( true !== learndash_user_progress_is_step_complete( $user_id, $course_id, $child_step_id ) ) {
				$incomplete_child_steps[] = absint( $child_step_id );
			}
		}
	}

	return $incomplete_child_steps;

}

/**
 * Utility function to return completed course step within a parent step.
 *
 * @since 4.4.0
 *
 * @param integer $user_id   User ID.
 * @param integer $course_id Course ID.
 * @param integer $step_id   Course Step ID.
 *
 * @return array Array of completed step IDs.
 */
function learndash_user_progression_get_complete_child_steps( $user_id = 0, $course_id = 0, $step_id = 0 ) {

	$complete_child_steps = array();

	$user_id   = absint( $user_id );
	$course_id = absint( $course_id );
	$step_id   = absint( $step_id );

	$child_steps = learndash_course_get_children_of_step( $course_id, $step_id, '', 'ids', true );
	if ( ! empty( $child_steps ) ) {
		foreach ( $child_steps as $child_step_id ) {
			if ( true === learndash_user_progress_is_step_complete( $user_id, $course_id, $child_step_id ) ) {
				$complete_child_steps[] = absint( $child_step_id );
			}
		}
	}

	return $complete_child_steps;
}

/**
 * Utility function to get check if the course step is complete.
 *
 * @since 3.4.0
 *
 * @param integer $user_id   User ID.
 * @param integer $course_id Course ID.
 * @param integer $step_id   Course Step ID.
 *
 * @return bool true if $step_id is complete.
 */
function learndash_user_progress_is_step_complete( $user_id = 0, $course_id = 0, $step_id = 0 ) {
	$user_id = absint( $user_id );
	if ( empty( $user_id ) ) {
		return false;
	}

	$course_id = absint( $course_id );
	if ( empty( $course_id ) ) {
		return false;
	}

	$step_id = absint( $step_id );
	if ( empty( $step_id ) ) {
		return false;
	}

	$course_progress_object = LDLMS_Factory_User::course_progress( $user_id );
	if ( ! $course_progress_object ) {
		return false;
	}

	// If the course is 'completed' for the user. Then ALL steps are completed.
	$course_progress_summary = $course_progress_object->get_progress( $course_id, 'summary' );
	if ( ( isset( $course_progress_summary['status'] ) ) && ( 'completed' === $course_progress_summary['status'] ) ) {
		return true;
	}

	$course_progress_steps = $course_progress_object->get_progress( $course_id, 'co' );
	if ( ! empty( $course_progress_steps ) ) {
		$step_key = get_post_type( $step_id ) . ':' . $step_id;
		if ( ( isset( $course_progress_steps[ $step_key ] ) ) && ( $course_progress_steps[ $step_key ] ) ) {
			return true;
		}
	}

	return false;
}

/**
 * Get the parent incomplete step.
 *
 * This utility function will check parent steps status to ensure
 * video progress or other requirements are met before allowing
 * access to the step.
 *
 * @since 4.2.1
 *
 * @param int $user_id   User ID.
 * @param int $course_id Course ID.
 * @param int $step_id   Course Step ID.
 *
 * @return int Adjusted Course Step ID.
 */
function learndash_user_progress_get_parent_incomplete_step( $user_id = 0, $course_id = 0, $step_id = 0 ) {
	$user_id = absint( $user_id );
	if ( empty( $user_id ) ) {
		return false;
	}

	$course_id = absint( $course_id );
	if ( empty( $course_id ) ) {
		return false;
	}

	$step_id = absint( $step_id );
	if ( empty( $step_id ) ) {
		return false;
	}

	$return_step_id = $step_id;

	/**
	 * Returns an array of parent steps in top-down order: Lesson, Topic, etc.
	 */
	$step_parent_ids = learndash_course_get_all_parent_step_ids( $course_id, $step_id );
	if ( ( is_array( $step_parent_ids ) ) && ( ! empty( $step_parent_ids ) ) ) {
		foreach ( $step_parent_ids as $step_parent_id ) {
			if ( in_array( get_post_type( $step_parent_id ), learndash_get_post_type_slug( array( 'lesson', 'topic' ) ), true ) ) {
				if ( ! learndash_user_progress_is_step_complete( $user_id, $course_id, $step_parent_id ) ) {
					if ( 'on' === learndash_get_setting( $step_parent_id, 'lesson_video_enabled' ) ) {
						if ( ! empty( learndash_get_setting( $step_parent_id, 'lesson_video_url' ) ) ) {
							if ( 'BEFORE' === learndash_get_setting( $step_parent_id, 'lesson_video_shown' ) ) {
								if ( ! learndash_video_complete_for_step( $step_parent_id, $course_id, $user_id ) ) {
									$return_step_id = $step_parent_id;
									break;
								}
							}
						}
					}
				}
			}
		}
	}

	return $return_step_id;
}
