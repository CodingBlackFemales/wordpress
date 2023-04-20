<?php
/**
 * LearnDash Admin Export Pro Quiz.
 *
 * @since 4.3.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (
	class_exists( 'Learndash_Admin_Export_Chunkable' ) &&
	trait_exists( 'Learndash_Admin_Import_Export_Proquiz' ) &&
	interface_exists( 'Learndash_Admin_Export_Has_Media' ) &&
	! class_exists( 'Learndash_Admin_Export_Proquiz' )
) {
	/**
	 * Class LearnDash Admin Export Pro Quiz.
	 *
	 * @since 4.3.0
	 */
	class Learndash_Admin_Export_Proquiz extends Learndash_Admin_Export_Chunkable implements Learndash_Admin_Export_Has_Media {
		use Learndash_Admin_Import_Export_Proquiz;

		const CHUNK_SIZE_ROWS = 5;

		/**
		 * Whether to include the progress.
		 *
		 * @since 4.3.0
		 *
		 * @var bool
		 */
		private $with_progress;

		/**
		 * Quiz exporter class instance.
		 *
		 * @since 4.3.0
		 *
		 * @var WpProQuiz_Helper_Export
		 */
		private $quiz_exporter;

		/**
		 * Statistic Ref Mapper class instance.
		 *
		 * @since 4.3.0
		 *
		 * @var WpProQuiz_Model_StatisticRefMapper
		 */
		private $statistic_ref_mapper;

		/**
		 * Statistic Mapper class instance.
		 *
		 * @since 4.3.0
		 *
		 * @var WpProQuiz_Model_StatisticMapper
		 */
		private $statistic_mapper;

		/**
		 * Constructor.
		 *
		 * @since 4.3.0
		 * @since 4.5.0   Changed the $logger param to the `Learndash_Import_Export_Logger` class.
		 *
		 * @param bool                                $with_progress        The flag to identify if we need to export statistics.
		 * @param WpProQuiz_Model_StatisticRefMapper  $statistic_ref_mapper Statistic Ref Mapper class instance.
		 * @param WpProQuiz_Model_StatisticMapper     $statistic_mapper     Statistic Mapper class instance.
		 * @param WpProQuiz_Helper_Export             $quiz_exporter        Quiz exporter class instance.
		 * @param Learndash_Admin_Export_File_Handler $file_handler         File Handler class instance.
		 * @param Learndash_Import_Export_Logger      $logger               Logger class instance.
		 *
		 * @return void
		 */
		public function __construct(
			bool $with_progress,
			WpProQuiz_Model_StatisticRefMapper $statistic_ref_mapper,
			WpProQuiz_Model_StatisticMapper $statistic_mapper,
			WpProQuiz_Helper_Export $quiz_exporter,
			Learndash_Admin_Export_File_Handler $file_handler,
			Learndash_Import_Export_Logger $logger
		) {
			$this->with_progress        = $with_progress;
			$this->statistic_ref_mapper = $statistic_ref_mapper;
			$this->statistic_mapper     = $statistic_mapper;
			$this->quiz_exporter        = $quiz_exporter;

			parent::__construct( $file_handler, $logger );
		}

		/**
		 * Returns data to export by chunks.
		 *
		 * @since 4.3.0
		 *
		 * @return string
		 */
		public function get_data(): string {
			$query_args = array(
				'fields'         => 'ids',
				'post_type'      => LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::QUIZ ),
				'post_status'    => 'any',
				'posts_per_page' => $this->get_chunk_size_rows(), // phpcs:ignore WordPress.WP.PostsPerPage
				'offset'         => $this->offset_rows,
			);

			/**
			 * Filters export pro quiz query args.
			 *
			 * @since 4.3.0
			 *
			 * @param array $query_args Query args.
			 *
			 * @return array Query args.
			 */
			$query_args = apply_filters( 'learndash_export_proquiz_query_args', $query_args );

			$post_ids = get_posts( $query_args );

			if ( empty( $post_ids ) ) {
				return '';
			}

			$result = '';

			foreach ( $post_ids as $post_id ) {
				/**
				 * Post ID.
				 *
				 * @var int $post_id Post ID.
				 */
				$proquiz_data = array(
					'proquiz_post_id'    => $post_id,
					'proquiz_data'       => $this->quiz_exporter->export(
						array( $post_id )
					),
					'proquiz_statistics' => $this->map_statistics( $post_id ),
				);

				/**
				 * Filters the pro quiz object to export.
				 *
				 * @since 4.3.0
				 *
				 * @param array $proquiz_data Pro quiz object.
				 *
				 * @return array Pro quiz object.
				 */
				$proquiz_data = apply_filters( 'learndash_export_proquiz_object', $proquiz_data );

				$result .= wp_json_encode( $proquiz_data ) . PHP_EOL;
			}

			$this->increment_offset_rows();

			return $result;
		}

		/**
		 * Returns media IDs.
		 *
		 * @since 4.3.0
		 *
		 * @return array.
		 */
		public function get_media(): array {
			global $wpdb;

			$question_media_fields = $wpdb->get_results(
				$wpdb->prepare(
					"
					SELECT correct_msg, incorrect_msg, tip_msg, answer_data
					FROM {$wpdb->prefix}learndash_pro_quiz_question
					WHERE id IN (select meta_value from {$wpdb->postmeta} where meta_key = 'quiz_pro_id')
					ORDER BY id
					LIMIT %d, %d
					",
					$this->offset_media,
					$this->get_chunk_size_media()
				)
			);

			$result = array();

			foreach ( $question_media_fields as $question_media_fields_item ) {
				$media_ids = array_merge(
					$this->get_media_ids_from_string( $question_media_fields_item->correct_msg ),
					$this->get_media_ids_from_string( $question_media_fields_item->incorrect_msg ),
					$this->get_media_ids_from_string( $question_media_fields_item->tip_msg ),
					$this->get_media_ids_from_string( $question_media_fields_item->answer_data )
				);

				$result = array_merge(
					$result,
					array_values(
						array_filter( $media_ids )
					)
				);
			}

			$this->increment_offset_media();

			return $result;
		}

		/**
		 * Maps quiz statistics.
		 *
		 * @since 4.3.0
		 *
		 * @param int $quiz_id Quiz ID.
		 *
		 * @return array
		 */
		public function map_statistics( int $quiz_id ): array {
			if ( ! $this->with_progress ) {
				return array();
			}

			$statistic_refs = $this->statistic_ref_mapper->fetch_all_by_quiz_post_id( $quiz_id );

			if ( empty( $statistic_refs ) ) {
				return array();
			}

			$statistics = $this->statistic_mapper->fetchByRefs(
				array_map(
					function( WpProQuiz_Model_StatisticRefModel $statistic_ref_model ) {
						return $statistic_ref_model->getStatisticRefId();
					},
					$statistic_refs
				)
			);

			$statistics_grouped_by_ref_id = array();
			foreach ( $statistics as $statistic ) {
				$statistics_grouped_by_ref_id[ $statistic->getStatisticRefId() ][] = $statistic->get_object_as_array();
			}

			$result = array();

			foreach ( $statistic_refs as $statistic_ref ) {
				$statistic_ref_id = $statistic_ref->getStatisticRefId();

				$result[] = array(
					'statistic_ref_model' => $statistic_ref->get_object_as_array(),
					'statistic_models'    => $statistics_grouped_by_ref_id[ $statistic_ref_id ] ?? array(),
				);
			}

			return $result;
		}
	}
}
