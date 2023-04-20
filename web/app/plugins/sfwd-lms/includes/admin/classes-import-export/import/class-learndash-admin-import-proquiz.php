<?php
/**
 * LearnDash Admin Import Pro Quiz.
 *
 * @since 4.3.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (
	class_exists( 'Learndash_Admin_Import' ) &&
	trait_exists( 'Learndash_Admin_Import_Export_Proquiz' ) &&
	! class_exists( 'Learndash_Admin_Import_Proquiz' )
) {
	/**
	 * Class LearnDash Admin Import Pro Quiz.
	 *
	 * @since 4.3.0
	 */
	class Learndash_Admin_Import_Proquiz extends Learndash_Admin_Import {
		use Learndash_Admin_Import_Export_Proquiz;

		/**
		 * Quiz Importer class instance.
		 *
		 * @since 4.3.0
		 *
		 * @var WpProQuiz_Helper_Import
		 */
		private $quiz_importer;

		/**
		 * User ID.
		 *
		 * @since 4.3.0
		 *
		 * @var int
		 */
		protected $user_id;

		/**
		 * Constructor.
		 *
		 * @since 4.3.0
		 * @since 4.5.0   Changed the $logger param to the `Learndash_Import_Export_Logger` class.
		 *
		 * @param int                                 $user_id       User ID. All posts are attached to this user.
		 * @param WpProQuiz_Helper_Import             $quiz_importer Quiz Importer class instance.
		 * @param string                              $home_url      The previous home url.
		 * @param Learndash_Admin_Import_File_Handler $file_handler  File Handler class instance.
		 * @param Learndash_Import_Export_Logger      $logger        Logger class instance.
		 *
		 * @return void
		 */
		public function __construct(
			int $user_id,
			WpProQuiz_Helper_Import $quiz_importer,
			string $home_url,
			Learndash_Admin_Import_File_Handler $file_handler,
			Learndash_Import_Export_Logger $logger
		) {
			$this->quiz_importer = $quiz_importer;
			$this->user_id       = $user_id;

			parent::__construct( $home_url, $file_handler, $logger );
		}

		/**
		 * Imports pro quizzes.
		 *
		 * @since 4.3.0
		 *
		 * @return void
		 */
		protected function import(): void {
			foreach ( $this->get_file_lines() as $item ) {
				$this->processed_items_count++;

				$this->quiz_importer->reset();
				$this->quiz_importer->setUserID( $this->user_id );
				$this->quiz_importer->setImportString( $item['proquiz_data'] );
				$this->quiz_importer->saveImport();

				if ( ! empty( $this->quiz_importer->getError() ) ) {
					$this->logger->error(
						'ProQuiz import error occurred: ' . $this->quiz_importer->getError()
					);
				}

				if ( 0 === $this->quiz_importer->import_post_id ) {
					continue;
				}

				$this->imported_items_count++;

				update_post_meta(
					$this->quiz_importer->import_post_id,
					Learndash_Admin_Import::META_KEY_IMPORTED_FROM_POST_ID,
					$item['proquiz_post_id']
				);

				foreach ( $this->quiz_importer->import_questions_old_to_new_ids as $old_id => $new_id ) {
					update_post_meta(
						$new_id,
						Learndash_Admin_Import::META_KEY_IMPORTED_FROM_POST_ID,
						$old_id
					);

					// imports question media.
					$this->import_question_media( $new_id );
				}
			}

			$this->quiz_importer->reset();
		}

		/**
		 * Imports question media.
		 *
		 * @since 4.3.0
		 *
		 * @param integer $question_id Question ID.
		 *
		 * @return void
		 */
		private function import_question_media( int $question_id ): void {
			global $wpdb;

			$question_pro_id = get_post_meta( $question_id, 'question_pro_id', true );

			if ( empty( $question_pro_id ) ) {
				return;
			}

			$question_media_fields = $wpdb->get_row(
				$wpdb->prepare(
					"
					SELECT correct_msg, incorrect_msg, tip_msg, answer_data
					FROM {$wpdb->prefix}learndash_pro_quiz_question
					WHERE id = %d
					",
					$question_pro_id
				),
				ARRAY_A
			);

			if ( empty( $question_media_fields ) ) {
				return;
			}

			$question_media_fields['correct_msg']   = $this->replace_media_from_content(
				$question_media_fields['correct_msg']
			);
			$question_media_fields['incorrect_msg'] = $this->replace_media_from_content(
				$question_media_fields['incorrect_msg']
			);
			$question_media_fields['tip_msg']       = $this->replace_media_from_content(
				$question_media_fields['tip_msg']
			);

			// answer data needs to be unserialized and then serialized again.
			$answer_data = maybe_unserialize( $question_media_fields['answer_data'] );

			if ( ! empty( $answer_data ) ) {
				foreach ( $answer_data as $answer_object ) {
					if ( ! $answer_object instanceof WpProQuiz_Model_AnswerTypes ) {
						continue;
					}

					$answer_object->setAnswer(
						$this->replace_media_from_content(
							$answer_object->getAnswer()
						)
					);
					$answer_object->setSortString(
						$this->replace_media_from_content(
							$answer_object->getSortString()
						)
					);
				}

				$question_media_fields['answer_data'] = maybe_serialize( $answer_data );
			}

			$wpdb->update(
				$wpdb->prefix . 'learndash_pro_quiz_question',
				$question_media_fields,
				array( 'id' => $question_pro_id ),
				array( '%s', '%s', '%s', '%s' ),
				array( '%d' )
			);
		}
	}
}
