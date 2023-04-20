<?php
/**
 * Class to extend LDLMS_Model_Post to LDLMS_Model_Quiz.
 *
 * @since 2.6.0
 * @package LearnDash\Quiz
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LDLMS_Model_Quiz' ) ) && ( class_exists( 'LDLMS_Model_Post' ) ) ) {
	/**
	 * Class for LearnDash Quiz.
	 *
	 * @since 2.6.0
	 * @uses LDLMS_Model
	 */
	class LDLMS_Model_Quiz extends LDLMS_Model_Post {

		/**
		 * Initialize post.
		 *
		 * @since 3.2.0
		 *
		 * @param int $quiz_id Quiz Post ID to load.
		 */
		public function __construct( $quiz_id = 0 ) {
			$this->post_type = learndash_get_post_type_slug( 'quiz' );
			$this->load( $quiz_id );
		}

		/**
		 * Load quiz
		 *
		 * @param int $quiz_id Quiz ID.
		 */
		public function load( $quiz_id ) {}

		// End of functions.
	}
}
