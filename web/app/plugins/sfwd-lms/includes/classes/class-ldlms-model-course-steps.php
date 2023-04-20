<?php
/**
 * LearnDash Course Steps Class.
 *
 * @since 2.5.0
 * @package LearnDash\Course\Steps
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LDLMS_Course_Steps' ) ) && ( class_exists( 'LDLMS_Model' ) ) ) {

	/**
	 * Class for LearnDash Course Steps.
	 *
	 * @since 2.5.0
	 * @uses LDLMS_Model
	 */
	class LDLMS_Course_Steps extends LDLMS_Model {

		/**
		 * Course ID for use in this instance.
		 *
		 * @var integer $course_id
		 */
		private $course_id = 0;

		/**
		 * Course Steps Loaded flag.
		 *
		 * @var boolean $steps_loaded Set to false initially. Set to true once course
		 * steps have been loaded.
		 */
		private $steps_loaded = false;

		/**
		 * Course Steps are dirty.
		 *
		 * @var boolean $steps_dirty Set to false initially but can be set to true if the
		 * dirty meta is read in and it true.
		 */
		private $steps_dirty = false;

		/**
		 * Course Steps being saved.
		 *
		 * @since 3.4.2
		 *
		 * @var boolean $saving_steps Set to (bool) true when the steps are being saved.
		 */
		private $saving_steps = false;

		/**
		 * Steps meta array.
		 *
		 * @since 3.4.0
		 *
		 * @var array $meta Array of post meta details including steps.
		 */
		protected $meta = array();

		/**
		 * Course Steps array.
		 *
		 * @var array $steps Array of course steps.
		 */
		protected $steps = array();

		/**
		 * Course Steps Objects Loaded flag.
		 *
		 * @var boolean $objects_loaded Set to false initially. Set to true once course
		 * steps objects have been loaded.
		 */
		protected $objects_loaded = false;

		/**
		 * Course Objects array.
		 *
		 * @var array $objects Array of course steps.
		 */
		protected $objects = array();

		/**
		 * Course post types
		 *
		 * @var array $steps_post_types Course post types.
		 */
		protected $steps_post_types = array();

		/**
		 * Public constructor for class.
		 *
		 * @since 2.6.0
		 *
		 * @param integer $course_id Course post ID.
		 */
		public function __construct( $course_id = 0 ) {
			if ( ! empty( $course_id ) ) {
				$this->course_id = absint( $course_id );

				$this->steps_post_types = learndash_get_post_types( 'course_steps' );
			}
		}

		/**
		 * Load Course Steps.
		 *
		 * @since 2.6.0
		 */
		public function load_steps() {
			if ( ! $this->steps_loaded ) {
				$this->steps_loaded = true;

				$this->load_steps_meta();

				if ( true === $this->meta['empty'] ) {
					// Note here since we are loading the steps via legacy methods we don't need to validate.
					$this->steps['h'] = $this->load_steps_legacy();
				}

				$this->build_steps();

				if ( $this->is_steps_dirty() ) {
					if ( true === $this->meta['course_shared_steps_enabled'] ) {
						$steps_h = $this->validate_steps( $this->steps['h'] );
					} elseif ( true === $this->meta['course_builder_enabled'] ) {
						$steps_h = $this->load_steps_legacy();
					}

					if ( ( ! isset( $steps_h ) ) || ( ! is_array( $steps_h ) ) ) {
						$steps_h = array();
					}

					if ( wp_json_encode( $this->steps['h'] ) !== wp_json_encode( $steps_h ) ) {
						$this->steps      = array();
						$this->steps['h'] = $steps_h;

						$this->build_steps();
						$this->save_steps_meta();
					}

					if ( $this->is_steps_dirty() ) {
						$this->clear_steps_dirty();
					}
				} elseif ( true === $this->meta['empty'] ) {
					// Finally clear our empty flag.
					$this->meta['empty'] = false;

					$this->save_steps_meta();
				}
			}
		}

		/**
		 * Load the Steps Post meta data set.
		 *
		 * @since 3.4.0
		 */
		protected function load_steps_meta() {
			$save_after_load = false;

			$this->objects_loaded = false;
			$this->objects        = array();

			if ( learndash_is_course_builder_enabled() ) {
				$this->meta = get_post_meta( $this->course_id, 'ld_course_steps', true );
			}

			if ( ( isset( $this->meta['h'] ) ) && ( ! isset( $this->meta['steps']['h'] ) ) ) {
				$steps_h = $this->meta['h'];

				$this->meta          = array();
				$this->meta['steps'] = array(
					'h' => $steps_h,
				);

				$save_after_load = true;
			}

			if ( ! is_array( $this->meta ) ) {
				$this->meta = array();
			}

			if ( ! isset( $this->meta['steps'] ) ) {
				$this->meta['steps'] = array();
			}

			/**
			 * We check the 'course_id' to verify the step metadata. This helps
			 * with clone plugins that will copy the post_meta. In theory.
			 */
			if ( isset( $this->meta['course_id'] ) ) {
				$this->meta['course_id'] = absint( $this->meta['course_id'] );
				if ( $this->course_id !== $this->meta['course_id'] ) {
					$this->meta['steps'] = array();
					$save_after_load     = true;
				}
			}

			if ( ( isset( $this->meta['course_builder_enabled'] ) ) && ( true === (bool) $this->meta['course_builder_enabled'] ) ) {
				if ( ! (bool) learndash_is_course_builder_enabled() ) {
					$this->meta['steps'] = array();
					$save_after_load     = true;
				}
			}

			if ( ( isset( $this->meta['course_shared_steps_enabled'] ) ) && ( true === (bool) $this->meta['course_shared_steps_enabled'] ) ) {
				if ( ! (bool) learndash_is_course_shared_steps_enabled() ) {
					$this->meta['steps'] = array();
					$save_after_load     = true;
				}
			}

			$this->meta['course_id'] = $this->course_id;
			$this->meta['version']   = LEARNDASH_VERSION;

			if ( ( ! isset( $this->meta['steps']['h'] ) ) || ( ! is_array( $this->meta['steps']['h'] ) ) ) {
				$this->meta['steps']['h'] = array();
				$this->meta['empty']      = true;
			} else {
				$this->meta['empty'] = false;
			}

			$this->meta['course_builder_enabled']      = (bool) learndash_is_course_builder_enabled();
			$this->meta['course_shared_steps_enabled'] = (bool) learndash_is_course_shared_steps_enabled();

			if ( isset( $this->meta['steps'] ) ) {
				$this->steps = $this->meta['steps'];
			}

			if ( true === $save_after_load ) {
				$this->save_steps_meta();
			}
		}

		/**
		 * Set the Steps Post meta data set.
		 *
		 * @since 3.4.0
		 */
		protected function save_steps_meta() {
			if ( learndash_is_course_builder_enabled() ) {
				if ( isset( $this->steps['h'] ) ) {
					$this->meta['steps_count'] = $this->calculate_steps_count();
					$this->meta['steps']       = array(
						'h' => $this->steps['h'],
					);
				}
				update_post_meta( $this->course_id, 'ld_course_steps', $this->meta );
			} else {
				delete_post_meta( $this->course_id, 'ld_course_steps' );
			}

			$this->set_steps_count_meta();
		}


		/**
		 * Sets the Course steps dirty flag and will force the steps to be
		 * reloaded from queries.
		 *
		 * @since 2.5.0
		 */
		public function set_steps_dirty() {
			if ( ( ! empty( $this->course_id ) ) && ( true !== $this->saving_steps ) ) {
				$this->steps_dirty = true;
				return update_post_meta( $this->course_id, 'ld_course_steps_dirty', $this->course_id );
			}
		}

		/**
		 * Check if the steps dirty flag is set.
		 *
		 * @since 2.5.0
		 */
		public function is_steps_dirty() {
			// If the steps_dirty boolean has been previously set to try it save a call to postmeta.
			if ( false === $this->steps_dirty ) {
				if ( ! empty( $this->course_id ) ) {
					$is_dirty = get_post_meta( $this->course_id, 'ld_course_steps_dirty', true );
					if ( absint( $is_dirty ) === absint( $this->course_id ) ) {
						$this->steps_dirty = true;
					}
				}
			}

			return $this->steps_dirty;
		}

		/**
		 * Clear the steps dirty flag.
		 *
		 * @since 2.5.0
		 */
		public function clear_steps_dirty() {
			if ( ! empty( $this->course_id ) ) {
				$this->steps_dirty = false;
				delete_post_meta( $this->course_id, 'ld_course_steps_dirty' );
			}
		}

		/**
		 * Get the total steps for the course.
		 *
		 * @since 2.6.0
		 */
		public function get_steps_count() {
			$this->load_steps();
			return $this->calculate_steps_count();
		}

		/**
		 * Calculate the total steps count for the course.
		 *
		 * @since 3.4.0
		 */
		protected function calculate_steps_count() {
			$steps_count = 0;

			if ( ( isset( $this->steps['h'] ) ) && ( ! empty( $this->steps['h'] ) ) ) {

				if ( ! isset( $this->steps['t'] ) ) {
					$this->steps['t'] = $this->steps_grouped_by_type( $this->steps['h'] );
				}

				// All Lessons.
				if ( isset( $this->steps['t']['sfwd-lessons'] ) ) {
					$steps_count += count( $this->steps['t']['sfwd-lessons'] );
				}

				// All topics.
				if ( isset( $this->steps['t']['sfwd-topic'] ) ) {
					$steps_count += count( $this->steps['t']['sfwd-topic'] );
				}

				// Any Global Quizzes only count as 1.
				if ( ( isset( $this->steps['h']['sfwd-quiz'] ) ) && ( ! empty( $this->steps['h']['sfwd-quiz'] ) ) ) {
					$steps_count++;
				}
			}

			return $steps_count;
		}

		/**
		 * Set the total steps postmeta for the course.
		 *
		 * @since 3.4.0
		 */
		public function set_steps_count_meta() {
			$steps_count = $this->calculate_steps_count();

			update_post_meta( $this->course_id, '_ld_course_steps_count', $steps_count );
		}

		/**
		 * Build Course Steps nodes.
		 *
		 * @since 2.5.0
		 */
		protected function build_steps() {
			if ( ! isset( $this->steps['h'] ) ) {
				$this->steps['h'] = array();
			}
			if ( ! isset( $this->steps['t'] ) ) {
				$this->steps['t'] = array();
			}
			if ( ! isset( $this->steps['r'] ) ) {
				$this->steps['r'] = array();
			}
			if ( ! isset( $this->steps['l'] ) ) {
				$this->steps['l'] = array();
			}
			if ( ! isset( $this->steps['co'] ) ) {
				$this->steps['co'] = array();
			}
			if ( ! isset( $this->steps['legacy'] ) ) {
				$this->steps['legacy'] = array();
			}
			if ( ! isset( $this->steps['sections'] ) ) {
				$this->steps['sections'] = array();
			}
			if ( isset( $this->steps['h']['section-heading'] ) ) {
				unset( $this->steps['h']['section-heading'] );
			}

			if ( ! empty( $this->steps['h'] ) ) {

				if ( empty( $this->steps['t'] ) ) {
					$this->steps['t'] = $this->steps_grouped_by_type( $this->steps['h'] );
				}

				if ( empty( $this->steps['l'] ) ) {
					$this->steps['l'] = $this->steps_grouped_linear( $this->steps['h'] );
				}

				if ( empty( $this->steps['co'] ) ) {
					$this->steps['co'] = $this->steps_grouped_completion_order( $this->steps['h'] );
				}

				if ( empty( $this->steps['r'] ) ) {
					$this->steps['r'] = $this->steps_grouped_reverse_keys( $this->steps['h'] );
				}

				if ( empty( $this->steps['sections'] ) ) {
					$this->steps['sections'] = $this->steps_grouped_sections( $this->steps['h'] );
				}

				if ( empty( $this->steps['legacy'] ) ) {
					$this->steps['legacy'] = $this->steps_grouped_legacy( $this->steps['h'] );
					if ( ! isset( $this->steps['legacy']['lessons'] ) ) {
						$this->steps['legacy']['lessons'] = array();
					}
					if ( ! isset( $this->steps['legacy']['topics'] ) ) {
						$this->steps['legacy']['topics'] = array();
					}
				}

				$this->load_steps_objects();
			}
		}

		/**
		 * Validate Course Steps nodes and items.
		 *
		 * @since 2.5.0
		 *
		 * @param array $steps Current steps nodes and items.
		 *
		 * @return array $steps Valid steps nodes and items.
		 */
		protected function validate_steps( $steps = array() ) {
			if ( ! empty( $steps ) ) {
				foreach ( $steps as $steps_type => $steps_type_set ) {
					if ( ( is_array( $steps_type_set ) ) && ( ! empty( $steps_type_set ) ) ) {
						foreach ( $steps_type_set as $steps_type_id => $steps_type_items ) {
							if ( ! isset( $this->objects[ $steps_type_id ] ) ) {
								unset( $steps[ $steps_type ][ $steps_type_id ] );
							} else {
								$steps[ $steps_type ][ $steps_type_id ] = $this->validate_steps( $steps_type_items );
							}
						}
					}
				}
			}

			return $steps;
		}

		/**
		 * This converts the normal hierarchy steps into an array groups be the post type. This is easier for search.
		 *
		 * @since 2.5.0
		 *
		 * @param array $steps Array of Course steps nodes and items.
		 *
		 * @return array Array of steps by type.
		 */
		protected function steps_grouped_by_type( $steps = array() ) {
			$steps_by_type = array();

			if ( ! empty( $steps ) ) {
				foreach ( $steps as $steps_type => $steps_type_set ) {
					if ( ! isset( $steps_by_type[ $steps_type ] ) ) {
						$steps_by_type[ $steps_type ] = array();
					}

					if ( ( is_array( $steps_type_set ) ) && ( ! empty( $steps_type_set ) ) ) {
						foreach ( $steps_type_set as $step_id => $step_id_set ) {
							$steps_by_type[ $steps_type ][] = $step_id;
							if ( ( is_array( $step_id_set ) ) && ( ! empty( $step_id_set ) ) ) {
								$sub_steps = $this->steps_grouped_by_type( $step_id_set );
								if ( ! empty( $sub_steps ) ) {
									foreach ( $sub_steps as $sub_step_type => $sub_step_ids ) {
										if ( ! isset( $steps_by_type[ $sub_step_type ] ) ) {
											$steps_by_type[ $sub_step_type ] = array();
										}

										if ( ! empty( $sub_step_ids ) ) {
											$steps_by_type[ $sub_step_type ] = array_merge( $steps_by_type[ $sub_step_type ], $sub_step_ids );
										}
									}
								}
							}
						}
					}
				}
			}

			return $steps_by_type;
		}

		/**
		 * Steps grouped linear.
		 *
		 * @since 2.5.0
		 *
		 * @param array $steps Array of Course step nodes and items.
		 *
		 * @return array Array of steps by linear.
		 */
		protected function steps_grouped_linear( $steps = array() ) {
			$steps_linear = array();

			if ( ! empty( $steps ) ) {
				foreach ( $steps as $steps_type => $steps_type_set ) {
					if ( ( is_array( $steps_type_set ) ) && ( ! empty( $steps_type_set ) ) ) {
						foreach ( $steps_type_set as $step_id => $step_id_set ) {
							$steps_linear[] = $steps_type . ':' . $step_id;
							if ( ( is_array( $step_id_set ) ) && ( ! empty( $step_id_set ) ) ) {
								$sub_steps = $this->steps_grouped_linear( $step_id_set );
								if ( ! empty( $sub_steps ) ) {
									$steps_linear = array_merge( $steps_linear, $sub_steps );
								}
							}
						}
					}
				}
			}

			return $steps_linear;
		}

		/**
		 * Steps grouped Legacy.
		 *
		 * @since 3.4.0
		 *
		 * @param array $steps Array of Course step nodes and items.
		 *
		 * @return array Array of steps by legacy.
		 */
		protected function steps_grouped_legacy( $steps = array() ) {
			$steps_legacy = array();

			if ( ! empty( $steps ) ) {
				foreach ( $steps as $steps_type => $steps_type_set ) {
					if ( ( is_array( $steps_type_set ) ) && ( ! empty( $steps_type_set ) ) ) {
						foreach ( $steps_type_set as $step_id => $step_id_set ) {
							if ( learndash_get_post_type_slug( 'lesson' ) === $steps_type ) {
								if ( ! isset( $steps_legacy['lessons'] ) ) {
									$steps_legacy['lessons'] = array();
								}
								$steps_legacy['lessons'][ $step_id ] = 0;

								if ( ! isset( $steps_legacy['topics'] ) ) {
									$steps_legacy['topics'] = array();
								}
								$steps_legacy['topics'][ $step_id ] = $this->steps_grouped_legacy( $step_id_set );

								if ( ! isset( $steps_legacy['total'] ) ) {
									$steps_legacy['total'] = $this->calculate_steps_count();
								}
							} elseif ( learndash_get_post_type_slug( 'topic' ) === $steps_type ) {
								$steps_legacy[ $step_id ] = 0;
							}
						}
					}
				}
			}

			return $steps_legacy;
		}

		/**
		 * Steps grouped by Completion Order.
		 *
		 * @since 3.4.0
		 *
		 * @param array $steps Array of Course step nodes and items.
		 *
		 * @return array Array of steps by complete order.
		 */
		protected function steps_grouped_completion_order( $steps = array() ) {
			$steps_co = array();

			if ( ! empty( $steps ) ) {
				foreach ( $steps as $steps_type => $steps_type_set ) {
					if ( ( is_array( $steps_type_set ) ) && ( ! empty( $steps_type_set ) ) ) {
						foreach ( $steps_type_set as $step_id => $step_id_set ) {

							if ( ( is_array( $step_id_set ) ) && ( ! empty( $step_id_set ) ) ) {
								$sub_steps = $this->steps_grouped_completion_order( $step_id_set );
								if ( ! empty( $sub_steps ) ) {
									$steps_co = array_merge( $steps_co, $sub_steps );
								}
							}
							$steps_co[] = $steps_type . ':' . $step_id;
						}
					}
				}
			}

			return $steps_co;
		}

		/**
		 * Group Steps by Section.
		 *
		 * @since 3.4.0
		 *
		 * @param array $steps Array of Course sections nodes and related lessons.
		 *
		 * @return array Array of sections.
		 */
		protected function steps_grouped_sections( $steps = array() ) {
			$sections = array();

			$sections_raw = get_post_meta( $this->course_id, 'course_sections', true );
			if ( empty( $sections_raw ) ) {
				return $sections;
			}

			$sections_array = json_decode( $sections_raw );
			if ( empty( $sections_array ) || ( ! is_array( $sections_array ) ) ) {
				return $sections;
			}

			if ( ( ! isset( $steps['sfwd-lessons'] ) ) || ( empty( $steps['sfwd-lessons'] ) ) ) {
				return $sections;
			}
			$lessons = array_keys( $steps['sfwd-lessons'] );

			foreach ( $sections_array as $section ) {
				if ( ! is_object( $section ) ) {
					continue;
				}

				if ( ( ! property_exists( $section, 'ID' ) ) || ( empty( $section->ID ) ) ) {
					continue;
				}

				if ( ! property_exists( $section, 'order' ) ) {
					continue;
				}

				if ( ( ! property_exists( $section, 'post_title' ) ) || ( empty( $section->post_title ) ) ) {
					continue;
				}

				if ( ( ! property_exists( $section, 'type' ) ) || ( empty( $section->type ) ) ) {
					continue;
				}

				array_splice( $lessons, (int) $section->order, 0, array( $section ) );
			}

			foreach ( $lessons as $lesson ) {
				if ( is_object( $lesson ) ) {

					unset( $lesson->url );
					unset( $lesson->edit_link );
					unset( $lesson->tree );
					unset( $lesson->expanded );
					$lesson->steps = array();

					$sections[] = $lesson;
				} else {
					if ( count( $sections ) ) {
						$sections[ count( $sections ) - 1 ]->steps[] = $lesson;
					}
				}
			}

			return $sections;
		}

		/**
		 * Load the Steps Objects.
		 *
		 * This function will take the steps by type and load all needed
		 * WP_Post objects.
		 *
		 * @since 3.4.0
		 */
		protected function load_steps_objects() {
			$all_objects_ids = array();

			if ( $this->objects_loaded ) {
				return;
			}

			if ( true === $this->saving_steps ) {
				return;
			}

			$all_steps_ids = $this->get_all_steps_ids();

			$this->objects = array();

			if ( true !== $this->meta['course_shared_steps_enabled'] ) {
				$steps_query_args = array(
					'post_type'        => $this->steps_post_types,
					'suppress_filters' => 1,
					'posts_per_page'   => -1,
					'post_status'      => $this->get_step_post_statuses(),
					'meta_query'       => array(
						array(
							'key'     => 'course_id',
							'value'   => absint( $this->course_id ),
							'compare' => '=',
						),
					),
				);
			} else {
				if ( empty( $all_steps_ids ) ) {
					$this->objects_loaded = true;
					return;
				}

				$steps_query_args = array(
					'post_type'        => $this->steps_post_types,
					'suppress_filters' => 1,
					'posts_per_page'   => -1,
					'post_status'      => $this->get_step_post_statuses(),
					'post__in'         => $all_steps_ids,
				);
			}

			/**
			 * Filters the $steps_query_args array use for Course Steps Objects Queries.
			 *
			 * @since 3.4.2
			 *
			 * @param array $steps_query_args Array of query args.
			 * @param int   $course_id        Course ID.
			 */
			$steps_query_args = apply_filters( 'learndash_course_steps_objects_query_args', $steps_query_args, $this->course_id );
			if ( ( is_array( $steps_query_args ) ) && ( ! empty( $steps_query_args ) ) ) {
				$steps_query = new WP_Query( $steps_query_args );
				if ( ( $steps_query ) && ( is_a( $steps_query, 'WP_Query' ) ) ) {
					foreach ( $steps_query->posts as $steps_post ) {
						$this->objects[ $steps_post->ID ] = $steps_post;
					}
					$this->objects_loaded = true;
				}
			}

			$all_objects_ids = $this->get_objects_steps_ids();

			/**
			 * If we have loaded some objects we filter through these and remove
			 * the post IDs from all_steps to cut down on queried objects.
			 */
			if ( ( count( $all_objects_ids ) !== count( $all_steps_ids ) ) ) {
				/**
				 * If we are not using Shared Steps we set the dirty
				 * flag and abort. This will cause the process to restart.
				 */
				if ( true !== $this->meta['course_shared_steps_enabled'] ) {
					/**
					 * If here we have a mismatch of the number of steps in the structure
					 * vs. the number of objects queried. So we try and reconcile. Otherwise
					 * this causes the 'dirty' logic to be triggered over and over.
					 * See LEARNDASH-6721 for example.
					 */
					$all_intersect_ids = array_intersect( $all_steps_ids, $all_objects_ids );
					if ( count( $all_intersect_ids ) ) {
						$all_diff_ids = array_diff( $all_objects_ids, $all_intersect_ids );
						if ( ! empty( $all_diff_ids ) ) {
							foreach ( $all_diff_ids as $all_diff_id ) {
								if ( ( isset( $this->objects[ $all_diff_id ] ) ) && ( is_a( $this->objects[ $all_diff_id ], 'WP_Post' ) ) ) {
									$diff_post = $this->objects[ $all_diff_id ];

									/**
									 * We only handle 'topic' items here because a topic needs both the
									 * 'course' and 'lesson' post meta. If a lesson or quiz is missing
									 * the 'course' it will auto-reconcile via the 'dirty' processing.
									 */
									if ( learndash_get_post_type_slug( 'topic' ) === $diff_post->post_type ) {
										$valid_post = true;

										/**
										 * We need to check the post_meta as well as the settings since they are
										 * stored separately. The post_meta can be changed outside of LD logic.
										 */
										$course_id_post_meta = (int) get_post_meta( $diff_post->ID, 'course_id', true );
										$course_id_setting   = (int) learndash_get_setting( $diff_post->ID, 'course' );

										if ( ( empty( $course_id_post_meta ) ) || ( empty( $course_id_setting ) ) || ( $course_id_setting !== $course_id_post_meta ) ) {
											$valid_post = false;
										} else {
											$lesson_id_post_meta = (int) get_post_meta( $diff_post->ID, 'lesson_id', true );
											$lesson_id_setting   = (int) learndash_get_setting( $diff_post->ID, 'lesson' );

											if ( ( empty( $lesson_id_post_meta ) ) || ( empty( $lesson_id_setting ) ) || ( $lesson_id_setting !== $lesson_id_post_meta ) ) {
												$valid_post = false;
											}
										}

										// If we have an invalid post we clear the course and lesson references.
										if ( true !== $valid_post ) {
											learndash_update_setting( $diff_post->ID, 'course', 0 );
											learndash_update_setting( $diff_post->ID, 'lesson', 0 );
										}
									}
								}
							}
						}
					}
					$this->set_steps_dirty();
					$this->objects        = array();
					$this->objects_loaded = false;
					return;
				}

				/**
				 * The following code is left but legacy. If here then Share Course Steps
				 * is enabled. But since we are loading all the known steps object in the
				 * above WP_Query calls there should be no need to compare the arrays of ids.
				 */
				$all_intersect_ids = array_intersect( $all_steps_ids, $all_objects_ids );

				// First remove the items we don't need.
				$all_remove_ids = array_diff( $all_objects_ids, $all_intersect_ids );
				if ( ! empty( $all_remove_ids ) ) {
					foreach ( $all_remove_ids as $id_remove ) {
						if ( isset( $this->objects[ $id_remove ] ) ) {
							unset( $this->objects[ $id_remove ] );
						}
					}
				}

				// Then add the new items.
				$all_add_ids = array_diff( $all_steps_ids, $all_intersect_ids );
				if ( ! empty( $all_add_ids ) ) {

					$all_steps_chunks_ids = array_chunk( $all_add_ids, LEARNDASH_LMS_COURSE_STEPS_LOAD_BATCH_SIZE );
					foreach ( $all_steps_chunks_ids as $steps_chunk_ids ) {
						if ( empty( $steps_chunk_ids ) ) {
							continue;
						}

						$steps_query_args = array(
							'post_type'      => $this->steps_post_types,
							'posts_per_page' => -1,
							'post_status'    => $this->get_step_post_statuses(),
							'post__in'       => $steps_chunk_ids,
						);

						$steps_query = new WP_Query( $steps_query_args );
						if ( ( is_a( $steps_query, 'WP_Query' ) ) && ( property_exists( $steps_query, 'posts' ) ) && ( ! empty( $steps_query->posts ) ) ) {
							foreach ( $steps_query->posts as $steps_post ) {
								$this->objects[ $steps_post->ID ] = $steps_post;
							}
						}
					}
				}
			}
		}

		/**
		 * Get all steps ids
		 *
		 * @since 3.4.0.7
		 */
		protected function get_all_steps_ids() {
			$all_steps_ids = array();

			$steps_by_type = array();
			if ( isset( $this->steps['t'] ) ) {
				$steps_by_type = $this->steps['t'];
			} else {
				if ( isset( $this->steps['h'] ) ) {
					$steps_by_type = $this->steps_grouped_by_type( $this->steps['h'] );
				}
			}

			if ( ! empty( $steps_by_type ) ) {
				if ( isset( $steps_by_type['sfwd-lessons'] ) ) {
					$all_steps_ids = array_merge( $all_steps_ids, $steps_by_type['sfwd-lessons'] );
				}
				if ( isset( $steps_by_type['sfwd-topic'] ) ) {
					$all_steps_ids = array_merge( $all_steps_ids, $steps_by_type['sfwd-topic'] );
				}
				if ( isset( $steps_by_type['sfwd-quiz'] ) ) {
					$all_steps_ids = array_merge( $all_steps_ids, $steps_by_type['sfwd-quiz'] );
				}
			}

			if ( ! empty( $all_steps_ids ) ) {
				$all_steps_ids = array_map( 'absint', $all_steps_ids );
			}

			return array_values( $all_steps_ids );
		}

		/**
		 * Get the post IDs of the objects.
		 *
		 * @since 3.4.0.7
		 *
		 * @return array Array of Post IDs
		 */
		protected function get_objects_steps_ids() {
			$objects_steps_ids = array();

			if ( ! empty( $this->objects ) ) {
				$objects_steps_ids = wp_list_pluck( $this->objects, 'ID' );
			}

			if ( ! empty( $objects_steps_ids ) ) {
				$objects_steps_ids = array_map( 'absint', $objects_steps_ids );
			}

			return array_values( $objects_steps_ids );
		}

		/**
		 * Group Steps reversed keys.
		 *
		 * @since 2.5.0
		 *
		 * @param array $steps Array of Course step nodes and items.
		 * @return array Array of steps.
		 */
		protected function steps_grouped_reverse_keys( $steps = array() ) {
			$steps_reversed = $this->steps_reverse_keys_walk( $steps );
			if ( ! empty( $steps_reversed ) ) {
				foreach ( $steps_reversed as $reversed_key => $reversed_set ) {
					if ( ! empty( $reversed_set ) ) {
						$steps_reversed[ $reversed_key ] = $this->flatten_item_parent_steps( $reversed_set );
					} else {
						$steps_reversed[ $reversed_key ] = array();
					}
				}
			}

			return $steps_reversed;
		}

		/**
		 * Internal utility function to reverse walk the Course steps nodes and items
		 *
		 * @since 2.5.0
		 *
		 * @param array $steps       Array of Course step nodes and items.
		 * @param array $parent_tree Patent array.
		 *
		 * @return array Array of steps.
		 */
		private function steps_reverse_keys_walk( $steps, $parent_tree = array() ) {
			$steps_reversed = array();

			if ( ! empty( $steps ) ) {
				foreach ( $steps as $steps_type => $steps_type_set ) {

					if ( ( is_array( $steps_type_set ) ) && ( ! empty( $steps_type_set ) ) ) {
						foreach ( $steps_type_set as $step_id => $step_id_set ) {
							$steps_parents                                 = array();
							$steps_parents[ $steps_type . ':' . $step_id ] = $parent_tree;

							if ( ( is_array( $step_id_set ) ) && ( ! empty( $step_id_set ) ) ) {
								$sub_steps = $this->steps_reverse_keys_walk( $step_id_set, $steps_parents );
								if ( ! empty( $sub_steps ) ) {
									$steps_parents = array_merge( $steps_parents, $sub_steps );
								}
							}

							if ( ! empty( $steps_parents ) ) {
								$steps_reversed = array_merge( $steps_reversed, $steps_parents );
							}
						}
					}
				}
			}

			return $steps_reversed;
		}

		/**
		 * Internal utility function to reverse parent keys of the Course nodes and items.
		 *
		 * @since 2.5.0
		 *
		 * @param array $steps       Array of Course step nodes and items.
		 *
		 * @return array Array of steps.
		 */
		private function flatten_item_parent_steps( $steps = array() ) {
			$flattened_steps = array();

			if ( ! empty( $steps ) ) {
				foreach ( $steps as $a_step_key => $a_steps ) {
					$flattened_steps[] = $a_step_key;
					$sub_steps         = $this->flatten_item_parent_steps( $a_steps );
					if ( ! empty( $sub_steps ) ) {
						$flattened_steps = array_merge( $flattened_steps, $sub_steps );
					}
				}
			}

			return $flattened_steps;
		}

		/**
		 * Set Course steps.
		 * This is generally called when editing the course and the course steps has been changed.
		 *
		 * @since 2.5.0
		 *
		 * @param array $course_steps Array of Course steps.
		 */
		public function set_steps( $course_steps = array() ) {
			if ( ! empty( $this->course_id ) ) {
				$this->saving_steps = true;

				$this->load_steps_meta();

				if ( isset( $course_steps['section-heading'] ) ) {
					$this->set_section_headings( $course_steps['section-heading'] );
					unset( $course_steps['section-heading'] );
				} else {
					$this->set_section_headings( array() );
				}

				$this->steps['h'] = $course_steps;

				$this->build_steps();

				if ( ! learndash_is_course_shared_steps_enabled() ) {
					$this->set_step_to_course_legacy();
				} else {
					$this->set_step_to_course();
				}

				$this->save_steps_meta();
				$this->set_steps_count_meta();

				$this->saving_steps = false;

				if ( $this->is_steps_dirty() ) {
					$this->clear_steps_dirty();
				}

				$this->steps_loaded = false;
			}
		}

		/**
		 * Set Course Section Headings.
		 *
		 * @since 3.5.0
		 *
		 * @param array $sections Array of Section headings.
		 *
		 * @return bool return from `update_post_status()`.
		 */
		public function set_section_headings( $sections = array() ) {
			// This probably should call the REST endpoint.
			if ( ! empty( $sections ) ) {
				foreach ( $sections as &$section ) {
					if ( ! isset( $section['post_title'] ) ) {
						$section['post_title'] = '';
					} elseif ( ! empty( $section['post_title'] ) ) {
						$section['post_title'] = wp_strip_all_tags( $section['post_title'] );
					}
				}

				$sections_json = wp_slash( wp_json_encode( array_values( $sections ), JSON_UNESCAPED_UNICODE ) );

				return update_post_meta( $this->course_id, 'course_sections', $sections_json );
			} else {
				return delete_post_meta( $this->course_id, 'course_sections' );
			}
		}

		/**
		 * Get Course steps by node type.
		 *
		 * @since 2.5.0
		 *
		 * @param string $steps_type Course Steps node type.
		 *
		 * @return array of Course Step items found in node.
		 */
		public function get_steps( $steps_type = 'h' ) {
			$this->load_steps();

			if ( isset( $this->steps[ $steps_type ] ) ) {
				return $this->steps[ $steps_type ];
			} elseif ( 'all' === $steps_type ) {
				return $this->steps;
			}

			return array();
		}

		/**
		 * This function sets a post_meta association for the various steps within the course.
		 * The new association is 'ld_course_XXX' where 'XXX' is the course ID.
		 *
		 * @since 2.5.0
		 */
		public function set_step_to_course() {
			global $wpdb;

			$course_steps_new = array();

			if ( ( isset( $this->steps['t'] ) ) && ( ! empty( $this->steps['t'] ) ) ) {
				foreach ( $this->steps['t'] as $step_type => $step_type_set ) {
					if ( ! empty( $step_type_set ) ) {
						$course_steps_new = array_merge( $course_steps_new, $step_type_set );
					}
				}
			}
			if ( ! empty( $course_steps_new ) ) {
				sort( $course_steps_new, SORT_NUMERIC );
			}

			$course_steps_old = $wpdb->get_col( $wpdb->prepare( 'SELECT post_id as post_id FROM ' . $wpdb->postmeta . ' WHERE meta_key = %s', 'ld_course_' . $this->course_id ) );
			if ( ! empty( $course_steps_old ) ) {
				sort( $course_steps_old, SORT_NUMERIC );
			}

			$course_steps_intersect = array_intersect( $course_steps_new, $course_steps_old );

			// Add Steps.
			$course_steps_add = array_diff( $course_steps_new, $course_steps_intersect );
			if ( ! empty( $course_steps_add ) ) {
				$course_steps_add_chunks = array_chunk( $course_steps_add, LEARNDASH_LMS_DEFAULT_CB_INSERT_CHUNK_SIZE );
				foreach ( $course_steps_add_chunks as $insert_post_ids ) {
					$insert_sql_str = '';
					foreach ( $insert_post_ids as $post_id ) {
						if ( ! empty( $insert_sql_str ) ) {
							$insert_sql_str .= ',';
						}

						$insert_sql_str .= '(' . $post_id . ", 'ld_course_" . $this->course_id . "'," . $this->course_id . ')';
					}
					if ( ! empty( $insert_sql_str ) ) {
						$insert_sql_str = 'INSERT INTO ' . $wpdb->postmeta . ' (`post_id`, `meta_key`, `meta_value`) VALUES ' . $insert_sql_str;
						//phpcs:disable WordPress.DB.PreparedSQL.NotPrepared
						$wpdb->query( $insert_sql_str );
						// phpcs:enable
					}
				}
			}

			// Remove Steps.
			$course_steps_remove = array_diff( $course_steps_old, $course_steps_intersect );
			if ( ! empty( $course_steps_remove ) ) {
				$wpdb->query(
					$wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s AND post_id IN (" . LDLMS_DB::escape_IN_clause_placeholders( $course_steps_remove ) . ')', array_merge( array( 'ld_course_' . $this->course_id ), LDLMS_DB::escape_IN_clause_values( $course_steps_remove ) ) ) //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				);
			}

			/**
			 * Secondary processing here we need to determine all the primary associations for this course and remove any items no longer associated.
			 * For example prior to v2.5 you may have a course ID #123. The course has a lesson, topic and global quiz. Each of these items will have
			 * a post_meta reference 'course_id'. Now in v2.5 the course steps are stored into a collection or nodes. But if for example the quiz is
			 * remove we need to also remove the legacy 'course_id' association.
			 */
			$course_steps_primary = $wpdb->get_col(
				$wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					"SELECT posts.ID as post_id FROM {$wpdb->posts} as posts INNER JOIN {$wpdb->postmeta} as postmeta ON posts.ID = postmeta.post_id WHERE 1=1 AND postmeta.meta_key = %s AND postmeta.meta_value = %d AND posts.post_type IN (" . LDLMS_DB::escape_IN_clause_placeholders( $this->steps_post_types ) . ')',
					array_merge( array( 'course_id' ), array( $this->course_id ), LDLMS_DB::escape_IN_clause_values( $this->steps_post_types ) )
				)
			);
			if ( ! empty( $course_steps_primary ) ) {
				$course_steps_primary = array_map( 'intval', $course_steps_primary );
			}

			$course_steps_primary_intersect = array_intersect( $course_steps_new, $course_steps_primary );

			$course_steps_primary_remove = array_diff( $course_steps_primary, $course_steps_primary_intersect );
			if ( ! empty( $course_steps_primary_remove ) ) {
				$wpdb->query(
					// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					$wpdb->prepare( "DELETE FROM {$wpdb->postmeta} WHERE meta_key = %s AND post_id IN (" . LDLMS_DB::escape_IN_clause_placeholders( $course_steps_primary_remove ) . ')', array_merge( array( 'course_id' ), LDLMS_DB::escape_IN_clause_values( $course_steps_primary_remove ) ) )
				);
			}
		}

		/**
		 * Set Steps to Course Legacy.
		 * This is used when the Course Option Share Steps is not used.
		 *
		 * @since 2.5.0
		 */
		protected function set_step_to_course_legacy() {
			global $wpdb;

			$course_steps_new = array();

			if ( ( isset( $this->steps['t'] ) ) && ( ! empty( $this->steps['t'] ) ) ) {
				foreach ( $this->steps['t'] as $step_type => $step_type_set ) {
					if ( ! empty( $step_type_set ) ) {
						$this->set_step_to_course_order( $step_type_set );
						$course_steps_new = array_merge( $course_steps_new, $step_type_set );
					}
				}
			}

			// Finally we set the Course order to Menu Order/ASC so we can retain the ordering.
			learndash_update_setting( $this->course_id, 'course_lesson_orderby', 'menu_order' );
			learndash_update_setting( $this->course_id, 'course_lesson_order', 'ASC' );

			if ( ! empty( $course_steps_new ) ) {
				sort( $course_steps_new, SORT_NUMERIC );
			}

			$course_steps_old = $wpdb->get_col(
				$wpdb->prepare( // phpcs:ignore WordPress.DB.PreparedSQLPlaceholders.ReplacementsWrongNumber
					//phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
					"SELECT posts.ID as post_id FROM {$wpdb->posts} as posts INNER JOIN {$wpdb->postmeta} as postmeta ON posts.ID = postmeta.post_id WHERE 1=1 AND postmeta.meta_key = %s AND postmeta.meta_value = %d AND posts.post_type IN (" . LDLMS_DB::escape_IN_clause_placeholders( $this->steps_post_types ) . ')',
					array_merge( array( 'course_id' ), array( $this->course_id ), LDLMS_DB::escape_IN_clause_values( $this->steps_post_types ) )
				)
			);

			if ( ! empty( $course_steps_old ) ) {
				$course_steps_old = array_map( 'intval', $course_steps_old );
			}

			$course_steps_intersect = array_intersect( $course_steps_new, $course_steps_old );

			// Add Steps.
			$course_steps_add = array_diff( $course_steps_new, $course_steps_intersect );
			if ( ! empty( $course_steps_add ) ) {
				foreach ( $course_steps_add as $post_id ) {
					learndash_update_setting( $post_id, 'course', $this->course_id );
				}
			}

			// Remove Steps.
			$course_steps_remove = array_diff( $course_steps_old, $course_steps_intersect );
			if ( ! empty( $course_steps_remove ) ) {
				foreach ( $course_steps_remove as $post_id ) {
					learndash_update_setting( $post_id, 'course', 0 );
					learndash_update_setting( $post_id, 'lesson', 0 );
				}
			}

			if ( ( isset( $this->steps['h'] ) ) && ( ! empty( $this->steps['h'] ) ) ) {
				$this->set_step_to_course_relationship( $this->steps['h'] );
			}
		}

		/**
		 * Internal utility function to update the step postmeta with the parent course_id.
		 *
		 * @since 2.5.0
		 *
		 * @param array $steps       Array of Course step nodes and items.
		 * @param int   $parent_lesson_id Parent Lesson ID.
		 */
		private function set_step_to_course_relationship( $steps = array(), $parent_lesson_id = 0 ) {
			global $wpdb;

			if ( ! empty( $steps ) ) {
				foreach ( $steps as $steps_type => $steps_type_set ) {
					if ( learndash_get_post_type_slug( 'lesson' ) === $steps_type ) {
						// A note about the queries. These should have been run through WP_Query
						// but there is more overhead there than we need.
						$sql_str = $wpdb->prepare(
							'SELECT  DISTINCT posts.ID
									FROM ' . $wpdb->posts . ' as posts
										INNER JOIN ' . $wpdb->postmeta . " as postmeta_course ON posts.ID=postmeta_course.post_id
									WHERE 1=1
										AND posts.post_type = %s
										AND postmeta_course.meta_key = 'course_id' AND postmeta_course.meta_value = %d
									",
							$steps_type,
							$this->course_id
						);
					} elseif ( ( learndash_get_post_type_slug( 'quiz' ) === $steps_type ) && ( 0 === $parent_lesson_id ) ) {

						$sql_str = $wpdb->prepare(
							'SELECT posts.ID FROM ' . $wpdb->posts . ' as posts
								LEFT JOIN ' . $wpdb->postmeta . ' postmeta
									ON ( posts.ID = postmeta.post_id )
								LEFT JOIN ' . $wpdb->postmeta . ' AS mt1
									ON ( posts.ID = mt1.post_id )
								LEFT JOIN ' . $wpdb->postmeta . " AS mt2
									ON (posts.ID = mt2.post_id AND mt2.meta_key = 'lesson_id' )
								WHERE 1=1
								AND (
									( postmeta.meta_key = 'course_id'
										AND CAST(postmeta.meta_value AS SIGNED) = %d )
								AND
									(
									( mt1.meta_key = 'lesson_id' AND CAST(mt1.meta_value AS SIGNED) = '0' )
									OR
									mt2.post_id IS NULL
									)
								)
								AND posts.post_type = %s
								GROUP BY posts.ID ORDER BY posts.post_date DESC ",
							$this->course_id,
							$steps_type
						);

					} elseif ( ! empty( $parent_lesson_id ) ) {
						$sql_str = $wpdb->prepare(
							'SELECT  DISTINCT posts.ID
									FROM ' . $wpdb->posts . ' as posts
										INNER JOIN ' . $wpdb->postmeta . ' as postmeta_course ON posts.ID=postmeta_course.post_id
										INNER JOIN ' . $wpdb->postmeta . " as postmeta_lesson ON posts.ID=postmeta_lesson.post_id
									WHERE 1=1
										AND posts.post_type = %s
										AND postmeta_course.meta_key = 'course_id' AND postmeta_course.meta_value = %d
										AND postmeta_lesson.meta_key = 'lesson_id' AND postmeta_lesson.meta_value = %d
									",
							$steps_type,
							$this->course_id,
							$parent_lesson_id
						);
					}

					if ( ! empty( $sql_str ) ) {
						if ( ( is_array( $steps_type_set ) ) && ( count( $steps_type_set ) ) ) {
							$step_type_ids_new = array_keys( $steps_type_set );
						} else {
							$step_type_ids_new = array();
						}

						$step_type_ids_old = $wpdb->get_col( $sql_str ); //phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared -- prepared SQL in prior lines
						if ( ! empty( $step_type_ids_old ) ) {
							$step_type_ids_old = array_map( 'intval', $step_type_ids_old );
						}
						$step_type_ids_intersect = array_intersect( $step_type_ids_new, $step_type_ids_old );

						$step_type_ids_add = array_diff( $step_type_ids_new, $step_type_ids_intersect );
						if ( ( ! empty( $step_type_ids_add ) ) && ( ! empty( $parent_lesson_id ) ) ) {
							foreach ( $step_type_ids_add as $post_id ) {
								learndash_update_setting( $post_id, 'lesson', $parent_lesson_id );
							}
						}

						$step_type_ids_remove = array_diff( $step_type_ids_old, $step_type_ids_intersect );
						if ( ! empty( $step_type_ids_remove ) ) {
							foreach ( $step_type_ids_remove as $post_id ) {
								learndash_update_setting( $post_id, 'lesson', 0 );
							}
						}
					}

					foreach ( $steps_type_set as $step_id => $step_id_set ) {
						if ( ( is_array( $step_id_set ) ) && ( ! empty( $step_id_set ) ) ) {
							$this->set_step_to_course_relationship( $step_id_set, $step_id );
						}
					}
				}
			}
		}

		/**
		 * Internal utility function to update the step postmeta menu_order value.
		 *
		 * @since 2.5.0
		 *
		 * @param array $steps Array of Course step nodes and items.
		 */
		private function set_step_to_course_order( $steps = array() ) {
			global $wpdb;

			if ( ! empty( $steps ) ) {
				$sql_str = '';

				foreach ( $steps as $step_order => $step_id ) {
					++$step_order;

					if ( ( defined( 'LEARNDASH_BUILDER_STEPS_UPDATE_POST' ) ) && ( true === LEARNDASH_BUILDER_STEPS_UPDATE_POST ) ) {
						$edit_post = array(
							'ID'         => $step_id,
							'menu_order' => $step_order,
						);
						wp_update_post( $edit_post );

					} else {
						$update_ret = $wpdb->update(
							$wpdb->posts,
							array( 'menu_order' => $step_order ),
							array( 'ID' => $step_id ),
							array( '%d' ),
							array( '%d' )
						);

						if ( ( $update_ret ) && ( ! is_wp_error( $update_ret ) ) ) {
							clean_post_cache( $step_id );
						}
					}
				}
			}
		}

		/**
		 * Load Course Steps for legacy setup (non-shared steps).
		 *
		 * @since 2.5.0
		 */
		private function load_steps_legacy() {

			$steps = array();

			$this->objects_loaded = false;
			$this->objects        = array();

			if ( ! empty( $this->course_id ) ) {
				// Set that we loaded the objects to prevent double logic.
				$this->objects_loaded = true;

				/**
				 * If Course Builder is enabled and the meta is not empty we set the
				 * orderby/order instead of using the global settings.
				 * See LEARNDASH-5804
				 */
				if ( ( true === $this->meta['course_builder_enabled'] ) && ( true !== $this->meta['empty'] ) ) {
					$course_lesson_order = array(
						'order'   => 'ASC',
						'orderby' => 'menu_order',
					);
				} else {
					$course_lesson_order = learndash_get_course_lessons_order( $this->course_id );
				}

				// Course > Lessons.
				$lesson_steps_query_args = array(
					'post_type'      => learndash_get_post_type_slug( 'lesson' ),
					'posts_per_page' => -1,
					'post_status'    => $this->get_step_post_statuses(),
					'orderby'        => $course_lesson_order['orderby'],
					'order'          => $course_lesson_order['order'],
					'meta_query'     => array(
						array(
							'key'     => 'course_id',
							'value'   => absint( $this->course_id ),
							'compare' => '=',
						),
					),
				);

				$lesson_steps_query = new WP_Query( $lesson_steps_query_args );
				if ( ( is_a( $lesson_steps_query, 'WP_Query' ) ) && ( property_exists( $lesson_steps_query, 'posts' ) ) && ( ! empty( $lesson_steps_query->posts ) ) ) {

					foreach ( $lesson_steps_query->posts as $lesson ) {
						$steps[ learndash_get_post_type_slug( 'lesson' ) ][ $lesson->ID ] = array();

						$steps[ learndash_get_post_type_slug( 'lesson' ) ][ $lesson->ID ][ learndash_get_post_type_slug( 'topic' ) ] = array();
						$steps[ learndash_get_post_type_slug( 'lesson' ) ][ $lesson->ID ][ learndash_get_post_type_slug( 'quiz' ) ]  = array();

						$this->objects[ $lesson->ID ] = $lesson;

						// Course > Lesson > Topics.
						$topic_steps_query_args = array(
							'post_type'      => learndash_get_post_type_slug( 'topic' ),
							'posts_per_page' => -1,
							'post_status'    => $this->get_step_post_statuses(),
							'orderby'        => $course_lesson_order['orderby'],
							'order'          => $course_lesson_order['order'],
							'meta_query'     => array(
								'relation' => 'AND',
								array(
									'key'     => 'course_id',
									'value'   => absint( $this->course_id ),
									'compare' => '=',
								),
								array(
									'key'     => 'lesson_id',
									'value'   => absint( $lesson->ID ),
									'compare' => '=',
								),
							),
						);

						$topic_steps_query = new WP_Query( $topic_steps_query_args );
						if ( ( is_a( $topic_steps_query, 'WP_Query' ) ) && ( property_exists( $topic_steps_query, 'posts' ) ) && ( ! empty( $topic_steps_query->posts ) ) ) {
							foreach ( $topic_steps_query->posts as $topic ) {
								$steps[ learndash_get_post_type_slug( 'lesson' ) ][ $lesson->ID ][ learndash_get_post_type_slug( 'topic' ) ][ $topic->ID ] = array();
								$steps[ learndash_get_post_type_slug( 'lesson' ) ][ $lesson->ID ][ learndash_get_post_type_slug( 'topic' ) ][ $topic->ID ][ learndash_get_post_type_slug( 'quiz' ) ] = array();

								$this->objects[ $topic->ID ] = $topic;

								// Course > Lesson > Topic > Quizzes.
								$topic_quiz_steps_query_args = array(
									'post_type'      => learndash_get_post_type_slug( 'quiz' ),
									'posts_per_page' => -1,
									'post_status'    => $this->get_step_post_statuses(),
									'orderby'        => $course_lesson_order['orderby'],
									'order'          => $course_lesson_order['order'],
									'meta_query'     => array(
										'relation' => 'AND',
										array(
											'key'     => 'course_id',
											'value'   => absint( $this->course_id ),
											'compare' => '=',
										),
										array(
											'key'     => 'lesson_id',
											'value'   => absint( $topic->ID ),
											'compare' => '=',
										),
									),
								);

								$topic_quiz_steps_query = new WP_Query( $topic_quiz_steps_query_args );
								if ( ( is_a( $topic_quiz_steps_query, 'WP_Query' ) ) && ( property_exists( $topic_quiz_steps_query, 'posts' ) ) && ( ! empty( $topic_quiz_steps_query->posts ) ) ) {
									foreach ( $topic_quiz_steps_query->posts as $quiz ) {
										$steps[ learndash_get_post_type_slug( 'lesson' ) ][ $lesson->ID ][ learndash_get_post_type_slug( 'topic' ) ][ $topic->ID ][ learndash_get_post_type_slug( 'quiz' ) ][ $quiz->ID ] = array();

										$this->objects[ $quiz->ID ] = $quiz;
									}
								}
							}
						}

						// Course > Lesson > Quizzes.
						$lesson_quiz_steps_query_args = array(
							'post_type'      => learndash_get_post_type_slug( 'quiz' ),
							'posts_per_page' => -1,
							'post_status'    => $this->get_step_post_statuses(),
							'orderby'        => $course_lesson_order['orderby'],
							'order'          => $course_lesson_order['order'],
							'meta_query'     => array(
								'relation' => 'AND',
								array(
									'key'     => 'course_id',
									'value'   => absint( $this->course_id ),
									'compare' => '=',
								),
								array(
									'key'     => 'lesson_id',
									'value'   => absint( $lesson->ID ),
									'compare' => '=',
								),
							),
						);

						$lesson_quiz_steps_query = new WP_Query( $lesson_quiz_steps_query_args );
						if ( ( is_a( $lesson_quiz_steps_query, 'WP_Query' ) ) && ( property_exists( $lesson_quiz_steps_query, 'posts' ) ) && ( ! empty( $lesson_quiz_steps_query->posts ) ) ) {
							foreach ( $lesson_quiz_steps_query->posts as $quiz ) {
								$steps[ learndash_get_post_type_slug( 'lesson' ) ][ $lesson->ID ][ learndash_get_post_type_slug( 'quiz' ) ][ $quiz->ID ] = array();

								$this->objects[ $quiz->ID ] = $quiz;
							}
						}
					}
				} else {
					$steps[ learndash_get_post_type_slug( 'lesson' ) ] = array();
				}

				// Course > Quizzes (Global Quizzes).
				$quiz_steps_query_args = array(
					'post_type'      => learndash_get_post_type_slug( 'quiz' ),
					'posts_per_page' => -1,
					'post_status'    => $this->get_step_post_statuses(),
					'orderby'        => $course_lesson_order['orderby'],
					'order'          => $course_lesson_order['order'],
					'meta_query'     => array(
						'relation' => 'AND',
						array(
							'key'     => 'course_id',
							'value'   => absint( $this->course_id ),
							'compare' => '=',
						),
					),
				);

				$quiz_ids = array();
				if ( ! empty( $this->objects ) ) {
					foreach ( $this->objects as $step_object ) {
						if ( learndash_get_post_type_slug( 'quiz' ) === $step_object->post_type ) {
							$quiz_ids[] = $step_object->ID;
						}
					}
				}

				if ( ! empty( $quiz_ids ) ) {
					$quiz_steps_query_args['post__not_in'] = $quiz_ids;
				}

				$quiz_steps_query = new WP_Query( $quiz_steps_query_args );
				if ( ( is_a( $quiz_steps_query, 'WP_Query' ) ) && ( property_exists( $quiz_steps_query, 'posts' ) ) && ( ! empty( $quiz_steps_query->posts ) ) ) {
					foreach ( $quiz_steps_query->posts as $quiz ) {
						$steps[ learndash_get_post_type_slug( 'quiz' ) ][ $quiz->ID ] = array();

						$this->objects[ $quiz->ID ] = $quiz;
					}
				} else {
					$steps[ learndash_get_post_type_slug( 'quiz' ) ] = array();
				}
			}

			return $steps;
		}

		/**
		 * Get Course Step parents.
		 *
		 * @since 2.5.0
		 *
		 * @param int    $post_id Current step post ID.
		 * @param string $post_type Parent step post_type to.
		 */
		public function get_item_parent_steps( $post_id = 0, $post_type = '' ) {
			$item_ancestor_steps = array();

			if ( ! empty( $post_id ) ) {
				if ( empty( $post_type ) ) {
					$post_type = get_post_type( $post_id );
				}

				if ( ! empty( $post_type ) ) {
					$this->load_steps();
					$steps_key = $post_type . ':' . $post_id;
					if ( isset( $this->steps['r'][ $steps_key ] ) ) {
						$item_ancestor_steps = $this->steps['r'][ $steps_key ];
					}
				}
			}

			return $item_ancestor_steps;
		}

		/**
		 * Get Single Course Step parent.
		 *
		 * @since 2.5.0
		 *
		 * @param int    $step_post_id       Current step post ID.
		 * @param string $ancestor_step_type Parent step post_type to return.
		 */
		public function get_parent_step_id( $step_post_id = 0, $ancestor_step_type = '' ) {
			if ( ! empty( $step_post_id ) ) {
				$step_ancestor_item = $this->get_item_parent_steps( $step_post_id );
				if ( ! empty( $step_ancestor_item ) ) {
					foreach ( $step_ancestor_item as $parent_steps_value ) {
						if ( ( is_string( $parent_steps_value ) ) && ( ! empty( $parent_steps_value ) ) ) {
							list( $s_post_type, $s_post_id ) = explode( ':', $parent_steps_value );
							if ( ! empty( $ancestor_step_type ) ) {
								if ( $ancestor_step_type == $s_post_type ) {
									return intval( $s_post_id );
								}
							} else {
								return intval( $s_post_id );
							}
						}
					}
				}
			}
		}

		/**
		 * Get Parent Children steps.
		 *
		 * @since 2.6.0
		 * @since 3.4.0 Added $nested parameter.
		 *
		 * @param int    $parent_post_id   The parent post ID.
		 * @param string $return_post_type Return specific post type.
		 * @param string $return_type      Return type. Default 'ids'. Other values 'objects'.
		 * @param bool   $nested           Wether to traverse substeps. Default false.
		 */
		public function get_children_steps( $parent_post_id = 0, $return_post_type = '', $return_type = 'ids', $nested = false ) {
			$item_children_steps = array();

			if ( ! empty( $parent_post_id ) ) {
				$this->load_steps();
				$steps_h = $this->get_steps( 'h' );

				/**
				 * Here we need to drill through the top-level step (lessons) to get ot our parent_post_type
				 */
				$ancestor_steps = $this->get_item_parent_steps( $parent_post_id, get_post_type( $parent_post_id ) );
				if ( ! empty( $ancestor_steps ) ) {
					$ancestor_steps = array_reverse( $ancestor_steps );
				}
				$ancestor_steps[] = get_post_type( $parent_post_id ) . ':' . $parent_post_id;
				foreach ( $ancestor_steps as $ancestor_step ) {
					if ( ( is_string( $ancestor_step ) ) && ( ! empty( $ancestor_step ) ) ) {
						list( $ancestor_step_post_type, $ancestor_step_post_id ) = explode( ':', $ancestor_step );
						if ( isset( $steps_h[ $ancestor_step_post_type ][ $ancestor_step_post_id ] ) ) {
							$steps_h = $steps_h[ $ancestor_step_post_type ][ $ancestor_step_post_id ];
						}
					}
				}

				if ( ! empty( $steps_h ) ) {
					foreach ( $steps_h as $steps_post_type => $steps_post_set ) {
						if ( ( empty( $return_post_type ) ) || ( $return_post_type == $steps_post_type ) && ( is_array( $steps_post_set ) ) ) {
							$item_children_steps = array_merge( $item_children_steps, array_keys( $steps_post_set ) );
						}

						if ( ( true === $nested ) && ( ! empty( $steps_post_set ) ) ) {
							foreach ( $steps_post_set as $sub_step_id => $sub_steps_post_set ) {
								$sub_item_children_steps = $this->get_children_steps( $sub_step_id, $return_post_type, $return_type, $nested );
								if ( ! empty( $sub_item_children_steps ) ) {
									$item_children_steps = array_merge( $item_children_steps, $sub_item_children_steps );
								}
							}
						}
					}
				}
			}

			if ( 'ids' !== $return_type ) {
				$item_children_objects = array();
				foreach ( $item_children_steps as $step_id ) {
					if ( isset( $this->objects[ $step_id ] ) ) {
						$item_children_objects[ $step_id ] = $this->objects[ $step_id ];
					}
				}
				$item_children_steps = $item_children_objects;
			}

			return $item_children_steps;
		}

		/**
		 * Steps split keys.
		 *
		 * @since 2.5.0
		 *
		 * @param array  $steps       Array of steps.
		 * @param string $parent_type Parent Post Type slug.
		 */
		public static function steps_split_keys( $steps, $parent_type = '' ) {
			if ( learndash_get_post_type_slug( 'lesson' ) === $parent_type ) {
				$course_steps_split_keys = array(
					'sfwd-topic' => array(),
					'sfwd-quiz'  => array(),
				);
			} elseif ( learndash_get_post_type_slug( 'topic' ) === $parent_type ) {
				$course_steps_split_keys = array(
					'sfwd-quiz' => array(),
				);
			} elseif ( learndash_get_post_type_slug( 'quiz' ) === $parent_type ) {
				$course_steps_split_keys = array();
			} elseif ( 'section-heading' === $parent_type ) {
				$course_steps_split_keys = array();
			} elseif ( empty( $parent_type ) ) {
				$course_steps_split_keys = array(
					'sfwd-lessons' => array(),
					'sfwd-quiz'    => array(),
				);
			}

			if ( ! empty( $steps ) ) {
				foreach ( $steps as $step_idx => $step_set ) {
					list( $step_post_type, $step_id ) = explode( ':', $step_idx );
					if ( ( ! empty( $step_post_type ) ) && ( ! empty( $step_id ) ) ) {
						if ( ! isset( $course_steps_split_keys[ $step_post_type ] ) ) {
							$course_steps_split_keys[ $step_post_type ] = array();
						}
						if ( $step_post_type === 'section-heading' ) {
							$course_steps_split_keys[ $step_post_type ][ $step_id ] = $step_set;
						} else {
							$course_steps_split_keys[ $step_post_type ][ $step_id ] = self::steps_split_keys( $step_set, $step_post_type );
						}
					}
				}
			}
			return $course_steps_split_keys;
		}

		/**
		 * Get the array of 'post_stats' keys used for Course Steps queries.
		 *
		 * @since 3.4.0
		 *
		 * @return array Array of post_status keys.
		 */
		protected function get_step_post_statuses() {
			$post_status_keys = array();

			$post_statuses = get_post_stati(
				array(
					'internal' => false,
					'_builtin' => true,
				)
			);
			if ( ! empty( $post_statuses ) ) {
				$post_status_keys = array_keys( $post_statuses );
			}

			/**
			 * Filters the $post_status_keys use for Course Steps Queries.
			 *
			 * @since 3.4.0
			 *
			 * @param array $post_status_keys Array of post_status keys.
			 */
			return apply_filters( 'learndash_course_steps_post_status_keys', $post_status_keys );
		}
	}
}
