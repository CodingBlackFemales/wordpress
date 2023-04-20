<?php
/**
 * LearnDash Admin Import Mapper.
 *
 * @since 4.3.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Admin_Import_Mapper' ) ) {
	/**
	 * Class LearnDash Admin Import Mapper.
	 *
	 * @since 4.3.0
	 */
	class Learndash_Admin_Import_Mapper {
		/**
		 * File Handler class instance.
		 *
		 * @since 4.3.0
		 *
		 * @var Learndash_Admin_Import_File_Handler
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
		 * @param Learndash_Admin_Import_File_Handler $file_handler File Handler class instance.
		 * @param Learndash_Import_Export_Logger      $logger       Logger class instance.
		 */
		public function __construct(
			Learndash_Admin_Import_File_Handler $file_handler,
			Learndash_Import_Export_Logger $logger
		) {
			$this->file_handler = $file_handler;
			$this->logger       = $logger;
		}

		/**
		 * Maps the importers list.
		 *
		 * @since 4.3.0
		 *
		 * @param array $import_options Import options.
		 * @param int   $user_id        User ID.
		 *
		 * @return Learndash_Admin_Import[]
		 */
		public function map( array $import_options, int $user_id ): array {
			$default_importer_args = array(
				$import_options['info']['home_url'],
				$this->file_handler,
				$this->logger,
			);

			$with_progress = (
				! empty( $import_options['users'] ) &&
				in_array( 'progress', $import_options['users'], true )
			);

			$importers = array(
				new Learndash_Admin_Import_Media( ...$default_importer_args ),
			);

			foreach ( $import_options['post_types'] as $post_type ) {
				$importers[] = new Learndash_Admin_Import_Posts(
					$post_type,
					$user_id,
					...$default_importer_args
				);
			}

			foreach ( $import_options['post_type_settings'] as $post_type ) {
				$importers[] = new Learndash_Admin_Import_Post_Type_Settings(
					$post_type,
					...$default_importer_args
				);
			}

			if ( ! empty( $import_options['post_types'] ) ) {
				$importers[] = new Learndash_Admin_Import_Taxonomies( ...$default_importer_args );

				if (
					in_array(
						LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::QUIZ ),
						$import_options['post_types'],
						true
					)
				) {
					$importers[] = new Learndash_Admin_Import_Proquiz(
						$user_id,
						new WpProQuiz_Helper_Import(),
						...$default_importer_args
					);
				}
			}

			if ( ! empty( $import_options['users'] ) ) {
				$importers[] = new Learndash_Admin_Import_Users(
					$import_options['info']['db_prefix'],
					$with_progress,
					...$default_importer_args
				);

				if ( $with_progress ) {
					$importers[] = new Learndash_Admin_Import_Proquiz_Statistics(
						new WpProQuiz_Model_StatisticRefMapper(),
						...$default_importer_args
					);
					$importers[] = new Learndash_Admin_Import_User_Activity( ...$default_importer_args );
				}
			}

			if ( in_array( 'settings', $import_options['other'], true ) ) {
				$importers[] = new Learndash_Admin_Import_Pages( $user_id, ...$default_importer_args );
				$importers[] = new Learndash_Admin_Import_Settings(
					...$default_importer_args
				);
			}

			/**
			 * Filters the list of importers.
			 *
			 * @since 4.3.0
			 *
			 * @param array $importers      Already added importers.
			 * @param array $import_options Import options.
			 *
			 * @return array Importers.
			 */
			$importers = apply_filters( 'learndash_import_importers', $importers, $import_options );

			return array_values(
				array_filter(
					$importers,
					function( $importer ) {
						return $importer instanceof Learndash_Admin_Import;
					}
				)
			);
		}
	}
}
