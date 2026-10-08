<?php
/**
 * LearnDash User Quiz Progress Class.
 *
 * @since 3.4.0
 * @package LearnDash\User\Progression
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LDLMS_Model_User_Quiz_Progress' ) ) && ( class_exists( 'LDLMS_Model_User' ) ) ) {

	/**
	 * Class for LearnDash LDLMS_Model_User_Quiz_Progress.
	 *
	 * @since 3.4.0
	 * @uses LDLMS_Model_User
	 */
	class LDLMS_Model_User_Quiz_Progress extends LDLMS_Model_User {

		/**
		 * User Progress Loaded flag.
		 *
		 * @var boolean $progress_loaded Set to false initially. Set to true once user
		 * progress has been loaded.
		 */
		private $progress_loaded = false;

		/**
		 * User Quiz Progress Meta Key.
		 *
		 * @var boolean $progress_meta_key Meta Key used to load progress.
		 */
		private $progress_meta_key = '_sfwd-quizzes';

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
		 * Public constructor for class.
		 *
		 * @since 3.2.0
		 *
		 * @param integer $user_id User ID.
		 *
		 * @throws LDLMS_Exception_NotFound When no user.
		 */
		public function __construct( $user_id = 0 ) {
			if ( ! $this->initialize( $user_id ) ) {
				throw new LDLMS_Exception_NotFound();
			} else {
				add_action( 'updated_user_meta', array( $this, 'updated_user_meta_progress' ), 30, 4 );
				return $this;
			}
		}

		/**
		 * Initialize the User class vars.
		 *
		 * @since 3.2.0
		 *
		 * @param integer $user_id User ID to use for class instance.
		 * @return boolean True if success.
		 */
		private function initialize( $user_id = 0 ) {
			$user_id = absint( $user_id );

			if ( ! empty( $user_id ) ) {
				$user = get_user_by( 'ID', $user_id );
				if ( ( is_a( $user, 'WP_User' ) ) && ( $user->ID === $user_id ) ) {
					$this->user_id = $user_id;
					$this->user    = $user;

					return true;
				}
			}
			return false;
		}

		/**
		 * Hook into the user meta update logic from WordPress so we know if external
		 * processes are updating the user meta value. If so we set the dirty flag to
		 * force a reload of the meta and rebuild the data structure.
		 *
		 * @since 3.2.0
		 *
		 * See the WordPress action 'updated_user_meta' for source of parameters.
		 * @param int    $meta_id     ID of updated metadata entry.
		 * @param int    $object_id   Object ID.
		 * @param string $meta_key    Meta key.
		 * @param mixed  $_meta_value Meta value.
		 */
		public function updated_user_meta_progress( $meta_id, $object_id, $meta_key, $_meta_value ) {
			if ( ( $object_id === $this->user_id ) && ( $meta_key === $this->progress_meta_key ) ) {
				$this->set_progress_unloaded();
			}

		}

		/**
		 * Load Legacy User Progress from User Meta.
		 *
		 * @since 3.2.0
		 */
		public function load_progress() {
			if ( ! $this->progress_loaded ) {
				$this->progress_loaded = true;

				$this->progress_legacy = get_user_meta( $this->user_id, $this->progress_meta_key, true );

				if ( ( ! empty( $this->progress_legacy ) ) && ( is_array( $this->progress_legacy ) ) ) {
					$this->build_quiz_progress( $this->progress_legacy );
				}
			}
		}

		/**
		 * Build Quiz Progress
		 *
		 * @param array $progress_legacy Legacy progress.
		 */
		protected function build_quiz_progress( $progress_legacy = array() ) {
			$this->progress = array();

			if ( ! empty( $progress_legacy ) ) {

				if ( ! isset( $this->progress['course_id'] ) ) {
					$this->progress['course_id'] = array();
				}

				if ( ! isset( $this->progress['quiz_id'] ) ) {
					$this->progress['quiz_id'] = array();
				}

				if ( ! isset( $this->progress['quiz_time'] ) ) {
					$this->progress['quiz_time'] = array();
				}

				foreach ( $progress_legacy as $quiz_item ) {
					$quiz_id   = 0;
					$quiz_time = 0;
					$course_id = 0;

					if ( ( isset( $quiz_item['quiz'] ) ) && ( ! empty( $quiz_item['quiz'] ) ) ) {
						$quiz_id = absint( $quiz_item['quiz'] );
					}

					if ( ( isset( $quiz_item['time'] ) ) && ( ! empty( $quiz_item['time'] ) ) ) {
						$quiz_time = absint( $quiz_item['time'] );
					}

					if ( ( isset( $quiz_item['course'] ) ) && ( ! empty( $quiz_item['course'] ) ) ) {
						$course_id = absint( $quiz_item['course'] );
					}

					if ( ( ! empty( $quiz_id ) ) && ( ! empty( $quiz_time ) ) ) {

						$this->progress['quiz_time'][ $quiz_time ] = $quiz_item;

						if ( ! isset( $this->progress['quiz_id'][ $quiz_id ] ) ) {
							$this->progress['quiz_id'][ $quiz_id ] = array();
						}
						$this->progress['quiz_id'][ $quiz_id ][ $quiz_time ] = $quiz_item;

						if ( ! empty( $course_id ) ) {
							if ( ! isset( $this->progress['course_id'][ $course_id ] ) ) {
								$this->progress['course_id'][ $course_id ] = array();
							}
							if ( ! isset( $this->progress['course_id'][ $course_id ][ $quiz_id ] ) ) {
								$this->progress['course_id'][ $course_id ][ $quiz_id ] = array();
							}
							$this->progress['course_id'][ $course_id ][ $quiz_id ][ $quiz_time ] = $quiz_item;
						}
					}
				}

				if ( ! empty( $this->progress['quiz_time'] ) ) {
					ksort( $this->progress['quiz_time'] );
				}

				if ( ! empty( $this->progress['course_id'] ) ) {
					foreach ( $this->progress['course_id'] as $course_id => $course_quizzes ) {
						if ( ! empty( $course_quizzes ) ) {
							foreach ( $course_quizzes as $quiz_id => $quiz_items ) {
								if ( ! empty( $quiz_items ) ) {
									ksort( $quiz_items );
									$this->progress['course_id'][ $course_id ][ $quiz_id ] = $quiz_items;
								}
							}
						}
					}
				}

				if ( ! empty( $this->progress['quiz_id'] ) ) {
					foreach ( $this->progress['quiz_id'] as $quiz_id => $quiz_items ) {
						if ( ! empty( $quiz_items ) ) {
							ksort( $quiz_items );
							$this->progress['quiz_id'][ $quiz_id ] = $quiz_items;
						}
					}
				}
			}
		}

		/**
		 * Build User Quiz Progress nodes.
		 *
		 * @since 3.2.0
		 *
		 * @param integer $quiz_id   Quiz Post ID to load progress for.
		 * @param integer $course_id Course Post ID .
		 */
		protected function load_quiz_progress( $quiz_id = 0, $course_id = 0 ) {
			if ( ! empty( $quiz_id ) ) {
				$this->load_progress();
			}
		}

		/**
		 * Sets the Progress loaded flag to false and clears all data
		 * structures. Thousand will force the progress to be reloaded
		 * from meta.
		 *
		 * @since 3.2.0
		 */
		public function set_progress_unloaded() {
			if ( ! empty( $this->user_id ) ) {
				$this->progress_loaded = false;
				$this->progress        = array();
				$this->progress_legacy = array();
			}
		}

		/**
		 * Get User Quiz Progress by progress type.
		 *
		 * @since 3.4.0
		 *
		 * @param integer $quiz_id       Quiz Post ID for progress set to return.
		 * @param integer $course_id     Course Post ID for progress set to return.
		 *
		 * @return array of User Quiz Progress.
		 */
		public function get_progress( $quiz_id = 0, $course_id = 0 ) {
			$quiz_id   = absint( $quiz_id );
			$course_id = absint( $course_id );

			$this->load_progress();

			if ( ( ! empty( $course_id ) ) && ( ! empty( $quiz_id ) ) && ( isset( $this->progress['course_id'][ $course_id ][ $quiz_id ] ) ) ) {
				return $this->progress['course_id'][ $course_id ][ $quiz_id ];
			} elseif ( ( ! empty( $quiz_id ) ) && ( isset( $this->progress['quiz_id'][ $quiz_id ] ) ) ) {
				return $this->progress['quiz_id'][ $quiz_id ];
			} elseif ( ( ! empty( $course_id ) ) && ( isset( $this->progress['course_id'][ $course_id ] ) ) ) {
				return $this->progress['course_id'][ $course_id ];
			}

			return array();
		}


		/**
		 * Set Progress steps.
		 *
		 * @since 3.2.0
		 * @param integer $course_id Course ID of progress to update.
		 * @param array   $progress Array of user progress.
		 */
		public function set_progress( $course_id = 0, $progress = array() ) {
			if ( ! empty( $this->user_id ) ) {
				if ( ! empty( $course_id ) ) {
					$this->load_progress();
					$this->progress[ $course_id ] = $progress;
					$this->progress_loaded        = false;
					return update_user_meta( $this->user_id, $this->progress_meta_key, $this->progress );
				}
			}
		}

		/**
		 * Check if user has completed/passed a specific Quiz.
		 *
		 * Can check for course quizzes or global quizzes.
		 *
		 * @since 3.4.0
		 *
		 * @param integer $quiz_id   Quiz Post ID.
		 * @param integer $course_id Course Post ID.
		 *
		 * @return boolean
		 */
		public function has_completed_step( $quiz_id = 0, $course_id = 0 ) {
			$quiz_id   = absint( $quiz_id );
			$course_id = absint( $course_id );

			$quiz_progress = $this->get_progress( $quiz_id, $course_id );
			if ( ! empty( $quiz_progress ) ) {
				foreach ( $quiz_progress as $quiz_idx => $quiz_item ) {
					if ( ! isset( $quiz_item['quiz'] ) ) {
						continue;
					}

					if ( isset( $quiz_item['pass'] ) ) {
						$pass = ( 1 == $quiz_item['pass'] ) ? 1 : 0;
					} else {
						$quiz_passingpercentage = (int) learndash_get_setting( $quiz_item['quiz'], 'passingpercentage' );

						$pass = ( ! empty( $quiz_item['count'] ) && $quiz_item['score'] * 100 / $quiz_item['count'] >= $quiz_passingpercentage ) ? 1 : 0;
					}

					if ( $pass ) {
						return true;
					}
				}
			}

			return false;
		}
	}
}

/**
 * Return Data from Instance.
 *
 * @since 3.4.0
 *
 * @param integer $user_id   User ID.
 * @param integer $quiz_id   Quiz Post ID.
 * @param integer $course_id Course Post ID.
 *
 * @return array of instances. Array or arrays.
 */
function learndash_user_get_quiz_progress( $user_id = 0, $quiz_id = 0, $course_id = 0 ) {
	$user_id   = absint( $user_id );
	$quiz_id   = absint( $quiz_id );
	$course_id = absint( $course_id );

	if ( ! empty( $user_id ) ) {
		$quiz_progress_object = LDLMS_Factory_User::quiz_progress( $user_id );
		if ( ( $quiz_progress_object ) && ( is_a( $quiz_progress_object, 'LDLMS_Model_User_Quiz_Progress' ) ) ) {
			return $quiz_progress_object->get_progress( $quiz_id, $course_id );
		}
	}

	return array();
}

/**
 * Check if user has completed/passed a specific Quiz.
 *
 * Can check for course quizzes or global quizzes.
 *
 * @since 3.4.0
 *
 * @param integer $user_id   User ID.
 * @param integer $quiz_id   Quiz Post ID.
 * @param integer $course_id Course Post ID.
 */
function learndash_user_quiz_has_completed( $user_id = 0, $quiz_id = 0, $course_id = 0 ) {
	$user_id   = absint( $user_id );
	$quiz_id   = absint( $quiz_id );
	$course_id = absint( $course_id );

	if ( ! empty( $user_id ) ) {
		$quiz_progress_object = LDLMS_Factory_User::quiz_progress( $user_id );
		if ( ( $quiz_progress_object ) && ( is_a( $quiz_progress_object, 'LDLMS_Model_User_Quiz_Progress' ) ) ) {
			return $quiz_progress_object->has_completed_step( $quiz_id, $course_id );
		}
	}
}
