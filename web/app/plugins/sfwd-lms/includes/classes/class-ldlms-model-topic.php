<?php
/**
 * Class to extend LDLMS_Model_Post to LDLMS_Model_Topic.
 *
 * @since 2.6.1
 * @package LearnDash\Topic
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( class_exists( 'LDLMS_Model_Post' ) ) && ( ! class_exists( 'LDLMS_Model_Topic' ) ) ) {
	/**
	 * Class for LearnDash Topic.
	 *
	 * @since 2.6.1
	 * @uses LDLMS_Model
	 */
	class LDLMS_Model_Topic extends LDLMS_Model_Post {

		/**
		 * Initialize post.
		 *
		 * @since 2.6.1
		 *
		 * @param int $topic_id Topic Post ID to load.
		 */
		public function __construct( $topic_id = 0 ) {
			$this->post_type = learndash_get_post_type_slug( 'topic' );

			$this->load( $topic_id );
		}

		/**
		 * Load topic
		 *
		 * @param int $topic_id Topic ID.
		 */
		public function load( $topic_id ) {}

		// End of functions.
	}
}
