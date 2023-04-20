<?php
/**
 * Class for LDLMS_Model_Activity.
 *
 * @package LearnDash\Activity
 * @since 3.5.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LDLMS_Model_Activity' ) ) && ( class_exists( 'LDLMS_Model' ) ) ) {
	/**
	 * Class for LearnDash Model Activity
	 */
	class LDLMS_Model_Activity extends LDLMS_Model {
		/**
		 * Activity ID.
		 *
		 * @since 3.5.0
		 *
		 * @var int Activity row ID.
		 */
		public $activity_id = 0;

		/**
		 * Activity user ID.
		 *
		 * @since 3.5.0
		 *
		 * @var int Activity user ID.
		 */
		public $user_id = 0;

		/**
		 * Activity post/step ID.
		 *
		 * @since 3.5.0
		 *
		 * @var int Activity post/step ID.
		 */
		public $post_id = 0;

		/**
		 * Activity course ID.
		 *
		 * @since 3.5.0
		 *
		 * @var int Activity course ID.
		 */
		public $course_id = 0;

		/**
		 * Activity type.
		 *
		 * @since 3.5.0
		 *
		 * @var string Activity type. 'course', 'lesson', 'topic', 'access', 'group', etc..
		 */
		public $activity_type = '';

		/**
		 * Activity status.
		 *
		 * @since 3.5.0
		 *
		 * @var bool Activity status. Completed is true.
		 */
		public $activity_status = 0;

		/**
		 * Activity started timestamp.
		 *
		 * @since 3.5.0
		 *
		 * @var int Activity started timestamp (GMT).
		 */
		public $activity_started = 0;

		/**
		 * Activity completed timestamp.
		 *
		 * @since 3.5.0
		 *
		 * @var int Activity completed timestamp (GMT).
		 */
		public $activity_completed = 0;

		/**
		 * Activity updated timestamp.
		 *
		 * @since 3.5.0
		 *
		 * @var int Activity update timestamp (GMT).
		 */
		public $activity_updated = 0;

		/**
		 * Activity meta.
		 *
		 * @since 3.5.0
		 *
		 * @var array Activity meta.
		 */
		public $activity_meta = 0;

		/**
		 * Class constructor.
		 *
		 * @since 3.2.0
		 *
		 * @param mixed $activity Activity.
		 */
		public function __construct( $activity = '' ) {
			$this->cast( $activity );
		}

		/**
		 * Cast Activity
		 *
		 * @param mixed $activity Activity.
		 */
		public function cast( $activity = '' ) {

			$this_reflection            = new ReflectionObject( $this );
			$this_reflection_properties = $this_reflection->getProperties();

			if ( is_object( $activity ) ) {
				$activity_reflection = new ReflectionObject( $activity );

				foreach ( $this_reflection_properties as $this_reflection_property ) {
					$name = $this_reflection_property->getName();
					if ( $activity_reflection->hasProperty( $name ) ) {
						$activity_property = $activity_reflection->getProperty( $name );
						$activity_property->setAccessible( true );
						$this->{$name} = $activity_property->getValue( $activity );
					}
				}
			} elseif ( is_array( $activity ) ) {
				foreach ( $this_reflection_properties as $this_reflection_property ) {
					$name = $this_reflection_property->getName();

					if ( isset( $activity[ $name ] ) ) {
						$this->{$name} = $activity[ $name ];
					}
				}
			}

			$this->format_vars();
		}

		/**
		 * Format variables
		 */
		public function format_vars() {

			$this->activity_id        = absint( $this->activity_id );
			$this->user_id            = absint( $this->user_id );
			$this->post_id            = absint( $this->post_id );
			$this->course_id          = absint( $this->course_id );
			$this->activity_type      = esc_attr( $this->activity_type );
			$this->activity_status    = (bool) $this->activity_status;
			$this->activity_started   = absint( $this->activity_started );
			$this->activity_completed = absint( $this->activity_completed );
			$this->activity_updated   = absint( $this->activity_updated );
		}
	}
}
