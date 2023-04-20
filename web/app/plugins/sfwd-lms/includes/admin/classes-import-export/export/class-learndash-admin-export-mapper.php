<?php
/**
 * LearnDash Admin Export Mapper.
 *
 * @since 4.3.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Admin_Export_Mapper' ) ) {
	/**
	 * Class LearnDash Admin Export Mapper.
	 *
	 * @since 4.3.0
	 */
	class Learndash_Admin_Export_Mapper {
		/**
		 * File Handler class instance.
		 *
		 * @since 4.3.0
		 *
		 * @var Learndash_Admin_Export_File_Handler
		 */
		private $file_handler;

		/**
		 * Logger class instance.
		 *
		 * @since 4.3.0
		 * @since 4.5.0   Changed to the `Learndash_Import_Export_Logger` class.
		 *
		 * @var Learndash_Import_Export_Logger
		 */
		private $logger;

		/**
		 * Constructor.
		 *
		 * @since 4.3.0
		 * @since 4.5.0   Changed the $logger param to the `Learndash_Import_Export_Logger` class.
		 *
		 * @param Learndash_Admin_Export_File_Handler $file_handler File Handler class instance.
		 * @param Learndash_Import_Export_Logger      $logger       Logger class instance.
		 */
		public function __construct(
			Learndash_Admin_Export_File_Handler $file_handler,
			Learndash_Import_Export_Logger $logger
		) {
			$this->file_handler = $file_handler;
			$this->logger       = $logger;
		}

		/**
		 * Maps the exporters list.
		 *
		 * @since 4.3.0
		 *
		 * @param array $export_options Export options.
		 *
		 * @return Learndash_Admin_Export[]
		 */
		public function map( array $export_options ): array {
			$default_exporter_args = array(
				$this->file_handler,
				$this->logger,
			);

			$with_progress = ! empty( $export_options['users'] ) && in_array(
				'progress',
				$export_options['users'],
				true
			);

			$exporters = array(
				new Learndash_Admin_Export_Configuration( $export_options, ...$default_exporter_args ),
			);

			if ( ! empty( $export_options['post_types'] ) ) {
				$exporters[] = new Learndash_Admin_Export_Taxonomies(
					$export_options['post_types'],
					...$default_exporter_args
				);
			}

			foreach ( $export_options['post_types'] as $post_type ) {
				$exporters[] = new Learndash_Admin_Export_Posts( $post_type, ...$default_exporter_args );
			}

			if (
				in_array(
					LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::QUIZ ),
					$export_options['post_types'],
					true
				)
			) {
				$exporters[] = new Learndash_Admin_Export_Proquiz(
					$with_progress,
					new WpProQuiz_Model_StatisticRefMapper(),
					new WpProQuiz_Model_StatisticMapper(),
					new WpProQuiz_Helper_Export(),
					...$default_exporter_args
				);
			}

			foreach ( $export_options['post_type_settings'] as $post_type ) {
				$exporters[] = new Learndash_Admin_Export_Post_Type_Settings( $post_type, ...$default_exporter_args );
			}

			if ( ! empty( $export_options['users'] ) ) {
				$exporters[] = new Learndash_Admin_Export_Users(
					$with_progress,
					in_array(
						LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::GROUP ),
						$export_options['post_types'],
						true
					),
					...$default_exporter_args
				);

				if ( $with_progress ) {
					$exporters[] = new Learndash_Admin_Export_User_Activity( ...$default_exporter_args );
				}
			}

			if ( in_array( 'settings', $export_options['other'], true ) ) {
				$exporters[] = new Learndash_Admin_Export_Settings( ...$default_exporter_args );
				$exporters[] = new Learndash_Admin_Export_Pages( ...$default_exporter_args );
			}

			/**
			 * Filters the list of exporters.
			 *
			 * @since 4.3.0
			 *
			 * @param array $exporters      Already added exporters.
			 * @param array $export_options Export options.
			 *
			 * @return array Exporters.
			 */
			$exporters = apply_filters( 'learndash_export_exporters', $exporters, $export_options );

			return array_values(
				array_filter(
					$exporters,
					function( $exporter ) {
						return $exporter instanceof Learndash_Admin_Export;
					}
				)
			);
		}
	}
}
