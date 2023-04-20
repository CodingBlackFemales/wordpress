<?php
/**
 * LearnDash Admin Quiz Cloning.
 *
 * @since 4.2.0
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'LearnDash_Admin_Cloning' ) && ! class_exists( 'Learndash_Admin_Quiz_Cloning' ) ) {
	/**
	 * Class LearnDash Admin Quiz Cloning.
	 *
	 * @since 4.2.0
	 */
	class Learndash_Admin_Quiz_Cloning extends Learndash_Admin_Cloning {
		/**
		 * Returns the post type slug for cloning.
		 *
		 * @since 4.2.0
		 *
		 * @return string The Quiz post type slug.
		 */
		public function get_cloning_object(): string {
			return 'quiz';
		}

		/**
		 * Clones the Quiz.
		 *
		 * @since 4.2.0
		 *
		 * @param WP_Post $ld_object The LearnDash WP_Post quiz.
		 * @param array   $args      The copy arguments.
		 *
		 * @return int|WP_Error The new quiz ID or WP_Error.
		 */
		public function clone( WP_Post $ld_object, array $args = array() ) {
			$defaults_args = array(
				'copy_name'     => $this->get_default_copy_name( $ld_object ),
				'quiz_exporter' => new WpProQuiz_Helper_Export(),
				'quiz_importer' => new WpProQuiz_Helper_Import(),
				'user_id'       => get_current_user_id(),
			);
			$args          = wp_parse_args( $args, $defaults_args );

			// get the export data.
			$export_obj   = $args['quiz_exporter'];
			$quiz_content = $export_obj->export( array( $ld_object->ID ) );

			// import the new quiz.
			$import_obj = $args['quiz_importer'];
			$import_obj->setImportString( $quiz_content );
			$result = $import_obj->saveImport();

			if ( false === $result ) {
				return new WP_Error(
					'learndash_cloning_quiz_error',
					sprintf(
						// translators: placeholder: Quiz name, Quiz error.
						__( 'Error cloning quiz %1$s: %2$s', 'learndash' ),
						'<b>' . $ld_object->post_title . '</b>',
						$import_obj->getError()
					)
				);
			}

			// change the quiz data.
			$new_quiz_id = $import_obj->import_post_id;
			$quiz_data   = array(
				'ID'             => $new_quiz_id,
				'post_title'     => $args['copy_name'],
				'post_status'    => $ld_object->post_status,
				'post_author'    => $args['user_id'],
				'comment_status' => $ld_object->comment_status,
				'ping_status'    => $ld_object->ping_status,
				'post_excerpt'   => $ld_object->post_excerpt,
				'post_parent'    => $ld_object->post_parent,
				'post_password'  => $ld_object->post_password,
				'to_ping'        => $ld_object->to_ping,
				'menu_order'     => $ld_object->menu_order,
			);
			// future posts.
			if ( 'future' === $ld_object->post_status ) {
				$quiz_data['post_date']     = $ld_object->post_date;
				$quiz_data['post_date_gmt'] = $ld_object->post_date_gmt;
			}
			wp_update_post( $quiz_data );

			// featured image.
			$this->clone_featured_image( $ld_object, $new_quiz_id );

			// quiz taxonomies.
			$this->clone_post_taxonomies( $ld_object, $new_quiz_id );

			// set course/lesson association.
			$settings_option                     = get_post_meta( $new_quiz_id, '_sfwd-quiz', true );
			$settings_option['sfwd-quiz_course'] = ! empty( $args['new_course_id'] ) ? $args['new_course_id'] : 0;
			$settings_option['sfwd-quiz_lesson'] = ! empty( $args['new_lesson_id'] ) ? $args['new_lesson_id'] : 0;
			update_post_meta( $new_quiz_id, '_sfwd-quiz', $settings_option );

			// course_id and lesson_id meta.
			if ( ! empty( $args['new_course_id'] ) ) {
				update_post_meta( $new_quiz_id, 'course_id', $args['new_course_id'] );
			} else {
				delete_post_meta( $new_quiz_id, 'course_id' );
			}

			if ( ! empty( $args['new_lesson_id'] ) ) {
				update_post_meta( $new_quiz_id, 'lesson_id', $args['new_lesson_id'] );
			} else {
				delete_post_meta( $new_quiz_id, 'lesson_id' );
			}

			return $new_quiz_id;
		}
	}
}
