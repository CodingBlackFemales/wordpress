<?php
/**
 * LearnDash Import Quiz Statistics
 *
 * This file contains functions to handle import of the LearnDash Quiz Statistics
 *
 * @package LearnDash\Import
 * @since 1.0.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'LearnDash_Import_Quiz_Statistics' ) ) && ( class_exists( 'LearnDash_Import_Post' ) ) ) {
	/**
	 * Class to import quiz statistics
	 */
	class LearnDash_Import_Quiz_Statistics extends LearnDash_Import_Post {
		/**
		 * Version
		 *
		 * @var string Version.
		 */
		private $version = '1.0';

		/**
		 * Constructor
		 */
		public function __construct() {
		}

		/**
		 * Get quiz statistics ref model
		 *
		 * @return array
		 */
		public function startQuizStatisticsHeader() {
			$statistic_ref_model = new WpProQuiz_Model_StatisticRefModel();

			return $statistic_ref_model->get_object_as_array();
		}

		/**
		 * Get quiz statistics question
		 *
		 * @return array
		 */
		public function startQuizStatisticsQuestion() {
			$pro_quiz_statistic_import = new WpProQuiz_Model_Statistic();

			return $pro_quiz_statistic_import->get_object_as_array();
		}

		/**
		 * Save quiz statistics set.
		 *
		 * $quiz_statistic_data should be an array of arrays. Each array item represents a single user question response.
		 *
		 * @param array $quiz_statistic_header  Quiz statistics header.
		 * @param array $quiz_statistic_details Quiz statistics details.
		 * @return int|null
		 */
		public function saveQuizStatisticSet( $quiz_statistic_header = array(), $quiz_statistic_details = array() ) {
			if ( ( ! empty( $quiz_statistic_header ) ) && ( ! empty( $quiz_statistic_details ) ) ) {

				$statistic_ref_model = new WpProQuiz_Model_StatisticRefModel();
				$statistic_ref_model->set_array_to_object( $quiz_statistic_header );

				$statistic_values = array();
				foreach ( $quiz_statistic_details as $quiz_statistic_details ) {
					// Called to ensure we have a working Question Set ( WpProQuiz_Model_Question ).
					$pro_quiz_statistic_import = new WpProQuiz_Model_Statistic();
					$pro_quiz_statistic_import->set_array_to_object( $quiz_statistic_details );
					$statistic_values[] = $pro_quiz_statistic_import;
				}

				$statistic_ref_mapper    = new WpProQuiz_Model_StatisticRefMapper();
				$statistic_ref_mapper_id = $statistic_ref_mapper->statisticSave( $statistic_ref_model, $statistic_values );
				return $statistic_ref_mapper_id;
			}

			return null;
		}

		/**
		 * Migrate file upload to essay
		 *
		 * $file_upload_full is the full path to the existing file.
		 * $question_id is needed when building the essay filename.
		 *
		 * @param string $file_upload_full Full path to file.
		 * @param int    $question_id      Question ID.
		 * @return string
		 */
		public function migrate_file_upload_to_essay( $file_upload_full = '', $question_id = 0 ) {
			if ( ! empty( $file_upload_full ) ) {

				// This logic was copied from LD core includes/quiz/ld-quiz-essay.php learndash_essay_fileupload_process().
				$filename = learndash_clean_filename( basename( $file_upload_full ) );

				// get file info.
				// @fixme: wp checks the file extension....
				$filetype   = wp_check_filetype( basename( $filename ), null );
				$file_title = preg_replace( '/\.[^.]+$/', '', basename( $filename ) );
				$filename   = sprintf( 'question_%d_%s.%s', $question_id, $file_title, $filetype['ext'] );
				/**
				 * Filters essay upload file name.
				 *
				 * Used in `migrate_file_upload_to_essay` to migrate existing files to essays.
				 *
				 * @param string $filename    Essay file name.
				 * @param int    $question_id Question ID.
				 * @param string $file_title  File title.
				 * @param string $extension   File extension.
				 */
				$filename        = apply_filters( 'learndash_essay_upload_filename', $filename, $question_id, $file_title, $filetype['ext'] );
				$upload_dir      = wp_upload_dir();
				$upload_dir_base = $upload_dir['basedir'];
				$upload_url_base = $upload_dir['baseurl'];

				/**
				 * Filters essay upload directory base.
				 *
				 * @param string $dir_base   Directory Base.
				 * @param string $filename   Essay file name.
				 * @param array  $upload_dir Uploads directory info.
				 */
				$upload_dir_path = $upload_dir_base . apply_filters(
					'learndash_essay_upload_dirbase',
					'/essays',
					$filename,
					$upload_dir
				);

				/**
				 * Filters essay upload url base.
				 *
				 * @param string $url_base   URL Base.
				 * @param string $filename   Essay file name.
				 * @param array  $upload_dir Uploads directory info.
				 */
				$upload_url_path = $upload_url_base . apply_filters(
					'learndash_essay_upload_urlbase',
					'/essays/',
					$filename,
					$upload_dir
				);

				if ( ! file_exists( $upload_dir_path ) ) {
					mkdir( $upload_dir_path );
				}

				/**
				 * Check if the filename already exist in the directory and rename the
				 * file if necessary
				 */
				$i = 0;

				while ( file_exists( $upload_dir_path . '/' . $filename ) ) {
					$i++;
					$filename = sprintf( 'question_%d_%s_%d.%s', $question_id, $file_title, $i, $filetype['ext'] );
					/**
					 * Filters essay upload duplicate file name.
					 *
					 * Used in `migrate_file_upload_to_essay` function to migrate existing files to essays.
					 *
					 * @param string $filename     Essay file name.
					 * @param int    $question_id Question ID.
					 * @param string $file_title    File title.
					 * @param int    $index       Index of file.
					 * @param string $extension   File extension.
					 */
					$filename = apply_filters( 'learndash_essay_upload_filename_dup', $filename, $question_id, $file_title, $i, $filetype['ext'] );
				}

				$file_dest = $upload_dir_path . '/' . $filename;

				$copy_ret = copy( $file_upload_full, $file_dest );
				if ( true === $copy_ret ) {
					return $upload_url_path . $filename;
				}
			}
			return '';
		}

		/**
		 * Add quiz attempts to user
		 *
		 * @param int   $user_id     User ID.
		 * @param array $quiz_attempt Quiz attempts.
		 */
		public function add_user_quiz_attempt( $user_id = 0, $quiz_attempt = array() ) {
			if ( ( ! empty( $user_id ) ) && ( ! empty( $quiz_attempt ) ) ) {

				$user_quiz_meta = get_user_meta( $user_id, '_sfwd-quizzes', true );
				if ( ! is_array( $user_quiz_meta ) ) {
					$user_quiz_meta = array();
				}

				$user_quiz_meta[] = $quiz_attempt;

				update_user_meta( $user_id, '_sfwd-quizzes', $user_quiz_meta );

			}
		}


		// End of functions.
	}
}
