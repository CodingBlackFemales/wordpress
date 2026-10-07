<?php
/**
 * LearnDash Import LearnDash Lesson
 *
 * This file contains functions to handle import of the LearnDash CPT Lesson
 *
 * @package LearnDash\Import
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LearnDash_Import_Lesson' ) ) && ( class_exists( 'LearnDash_Import_Post' ) ) ) {
	/**
	 * Class to import lessons.
	 */
	class LearnDash_Import_Lesson  extends LearnDash_Import_Post {
		/**
		 * Version
		 *
		 * @var string Version.
		 */
		private $version = '1.0';

		/**
		 * Destination Post Type
		 *
		 * @var string $dest_post_type
		 */
		protected $dest_post_type = 'sfwd-lessons';

		/**
		 * Source Post Type
		 *
		 * @var string $source_post_type
		 */
		protected $source_post_type = 'sfwd-lessons';

		/**
		 * Destination Taxonomy
		 *
		 * @var string $dest_taxonomy
		 */
		protected $dest_taxonomy = 'ld_lesson_tag';

		/**
		 * Duplicate post
		 *
		 * @param integer $source_post_id Post ID to copy.
		 * @param boolean $force_copy     Whether to force the copy. Default false.
		 *
		 * @return WP_Post
		 */
		public function duplicate_post( $source_post_id = 0, $force_copy = false ) {
			$new_post = parent::duplicate_post( $source_post_id, $force_copy );

			return $new_post;
		}

		/**
		 * Duplicate Post's taxonomies
		 *
		 * @param WP_Term $source_term    WP_Term to duplicate.
		 * @param boolean $create_parents Whether to create parent taxonomies. Default false.
		 *
		 * @return WP_Term
		 */
		public function duplicate_post_tax_term( $source_term, $create_parents = false ) {
			$new_term = parent::duplicate_post( $source_term, $create_parents );

			return $new_term;
		}

		// End of functions.
	}
}
