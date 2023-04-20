<?php
/**
 * LearnDash Admin Import Pro Quiz Statistics.
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
	! class_exists( 'Learndash_Admin_Import_Proquiz_Statistics' )
) {
	/**
	 * Class LearnDash Admin Import Pro Quiz Statistics.
	 *
	 * @since 4.3.0
	 */
	class Learndash_Admin_Import_Proquiz_Statistics extends Learndash_Admin_Import {
		use Learndash_Admin_Import_Export_Proquiz;

		/**
		 * Statistic Ref Mapper class instance.
		 *
		 * @since 4.3.0
		 *
		 * @var WpProQuiz_Model_StatisticRefMapper
		 */
		private $statistic_ref_mapper;

		/**
		 * Old user id => new user id hash.
		 *
		 * @since 4.3.0
		 *
		 * @var array
		 */
		private $old_user_id_new_user_id_hash;

		/**
		 * Constructor.
		 *
		 * @since 4.3.0
		 * @since 4.5.0   Changed the $logger param to the `Learndash_Import_Export_Logger` class.
		 *
		 * @param WpProQuiz_Model_StatisticRefMapper  $statistic_ref_mapper Statistic Ref Mapper class instance.
		 * @param string                              $home_url             The previous home url.
		 * @param Learndash_Admin_Import_File_Handler $file_handler         File Handler class instance.
		 * @param Learndash_Import_Export_Logger      $logger               Logger class instance.
		 *
		 * @return void
		 */
		public function __construct(
			WpProQuiz_Model_StatisticRefMapper $statistic_ref_mapper,
			string $home_url,
			Learndash_Admin_Import_File_Handler $file_handler,
			Learndash_Import_Export_Logger $logger
		) {
			$this->statistic_ref_mapper = $statistic_ref_mapper;

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
			$this->old_user_id_new_user_id_hash = $this->get_old_user_id_new_user_id_hash();
			$old_new_statistic_ref_id_hash      = array();

			foreach ( $this->get_file_lines() as $item ) {
				if ( empty( $item['proquiz_statistics'] ) ) {
					continue;
				}

				$this->processed_items_count++;

				foreach ( $item['proquiz_statistics'] as $statistic ) {
					$statistic_ref_model = $this->map_statistic_ref_model(
						$statistic['statistic_ref_model']
					);

					if ( is_null( $statistic_ref_model ) ) {
						continue;
					}

					$ref_id = $this->statistic_ref_mapper->statisticSave(
						$statistic_ref_model,
						$this->map_statistic_values( $statistic['statistic_models'] )
					);

					if ( is_null( $ref_id ) ) {
						continue;
					}

					$old_new_statistic_ref_id_hash[ $statistic_ref_model->getStatisticRefId() ] = $ref_id;

					$this->imported_items_count++;
				}

				Learndash_Admin_Import::clear_wpdb_query_cache();
			}

			set_transient(
				Learndash_Admin_Import::TRANSIENT_KEY_STATISTIC_REF_IDS,
				$old_new_statistic_ref_id_hash
			);
		}

		/**
		 * Creates a WpProQuiz_Model_StatisticRefModel model.
		 *
		 * @since 4.3.0
		 *
		 * @param array $values Array of WpProQuiz_Model_StatisticRefModel values.
		 *
		 * @return WpProQuiz_Model_StatisticRefModel|null
		 */
		protected function map_statistic_ref_model( array $values ): ?WpProQuiz_Model_StatisticRefModel {
			$statistic_ref_model = new WpProQuiz_Model_StatisticRefModel();
			$statistic_ref_model->set_array_to_object( $values );

			$new_user_id = $this->old_user_id_new_user_id_hash[ $statistic_ref_model->getUserId() ] ?? null;

			if ( is_null( $new_user_id ) ) {
				return null;
			}

			$quiz_post_id = $this->get_new_post_id_by_old_post_id(
				$statistic_ref_model->getQuizPostId()
			);

			if ( is_null( $quiz_post_id ) ) {
				return null;
			}

			$course_post_id = $this->get_new_post_id_by_old_post_id(
				$statistic_ref_model->getCoursePostId()
			);

			if ( is_null( $course_post_id ) ) {
				return null;
			}

			$statistic_ref_model->setQuizPostId( $quiz_post_id );
			$statistic_ref_model->setQuizId(
				get_post_meta( $quiz_post_id, 'quiz_pro_id', true )
			);
			$statistic_ref_model->setCoursePostId( $course_post_id );
			$statistic_ref_model->setUserId( $new_user_id );

			return $statistic_ref_model;
		}

		/**
		 * Creates a WpProQuiz_Model_StatisticRefModel model.
		 *
		 * @since 4.3.0
		 *
		 * @param array $statistic_models Statistic models.
		 *
		 * @return WpProQuiz_Model_Statistic[]
		 */
		protected function map_statistic_values( array $statistic_models ): array {
			if ( empty( $statistic_models ) ) {
				return array();
			}

			$result = array();

			foreach ( $statistic_models as $statistic_model_values ) {
				$statistic_model = new WpProQuiz_Model_Statistic();
				$statistic_model->set_array_to_object( $statistic_model_values );

				$question_post_id = $this->get_new_post_id_by_old_post_id( $statistic_model->getQuestionPostId() );

				if ( is_null( $question_post_id ) ) {
					continue;
				}

				$statistic_model->setQuestionPostId( $question_post_id );
				$statistic_model->setQuestionId(
					get_post_meta( $question_post_id, 'question_pro_id', true )
				);

				$result[] = $statistic_model;
			}

			return $result;
		}
	}
}
