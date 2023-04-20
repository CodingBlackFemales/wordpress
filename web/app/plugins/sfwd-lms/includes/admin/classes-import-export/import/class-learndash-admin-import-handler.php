<?php
/**
 * LearnDash Admin Import Handler.
 *
 * @since   4.3.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (
	class_exists( 'Learndash_Admin_Import_Export_Handler' ) &&
	! class_exists( 'Learndash_Admin_Import_Handler' )
) {
	/**
	 * Class LearnDash Admin Import Handler.
	 *
	 * @since 4.3.0
	 */
	class Learndash_Admin_Import_Handler extends Learndash_Admin_Import_Export_Handler {
		const AJAX_ACTION_NAME      = 'learndash_import';
		const SCHEDULER_ACTION_NAME = 'learndash_import_action';

		/**
		 * Handles Import.
		 *
		 * @since 4.3.0
		 *
		 * @return void
		 */
		public function handle(): void {
			$this->validate();

			$file_path = sanitize_text_field(
				// phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.NonceVerification.Missing
				wp_unslash( $_FILES['file']['tmp_name'] )
			);

			$task_enqueued = $this->enqueue_import_task( $file_path );

			if ( is_wp_error( $task_enqueued ) ) {
				wp_send_json_error(
					array(
						'message' => $task_enqueued->get_error_message(),
					)
				);
			}

			/**
			 * Fires after an import task is enqueued.
			 *
			 * @since 4.3.0
			 */
			do_action( 'learndash_import_task_enqueued' );

			wp_send_json_success();
		}

		/**
		 * Handles the import action.
		 *
		 * @since 4.3.0
		 *
		 * @param array  $options           Import options.
		 * @param string $working_directory Working directory.
		 * @param int    $user_id           User ID. All posts except essays and assignments are attached to this user.
		 *
		 * @return void
		 */
		public function handle_action( array $options, string $working_directory = '', int $user_id = 0 ): void {
			if ( empty( $options ) || empty( $working_directory || empty( $user_id ) ) ) {
				return;
			}

			try {
				$this->import( $options, $working_directory, $user_id );
			} catch ( Exception $e ) {
				$this->logger->error( 'Import exception: ' . $e->getMessage() );

				Learndash_Admin_Action_Scheduler::add_admin_notice(
					$e->getMessage(),
					'error',
					$this->get_scheduler_action_name()
				);
			} finally {
				$this->clean( $working_directory );
				$this->logger->info( 'Import finished.' . PHP_EOL );

				/**
				 * Fires after an import task is handled.
				 *
				 * @since 4.3.0
				 */
				do_action( 'learndash_import_task_handled' );
			}
		}

		/**
		 * Returns the ajax action name.
		 *
		 * @since 4.3.0
		 *
		 * @return string
		 */
		protected function get_ajax_action_name(): string {
			return self::AJAX_ACTION_NAME;
		}

		/**
		 * Returns the scheduler action name.
		 *
		 * @since 4.3.0
		 *
		 * @return string
		 */
		protected function get_scheduler_action_name(): string {
			return self::SCHEDULER_ACTION_NAME;
		}

		/**
		 * Deletes metadata that is no longer needed.
		 *
		 * @since 4.3.0
		 *
		 * @param string $working_directory Working directory.
		 *
		 * @return void
		 */
		protected function clean( string $working_directory ): void {
			$this->file_handler->remove_directory_recursively( $working_directory );

			delete_metadata(
				'post',
				0,
				Learndash_Admin_Import::META_KEY_IMPORTED_FROM_POST_ID,
				null,
				true
			);

			delete_metadata(
				'post',
				0,
				Learndash_Admin_Import::META_KEY_IMPORTED_FROM_URL,
				null,
				true
			);

			delete_metadata(
				'post',
				0,
				Learndash_Admin_Import::META_KEY_IMPORTED_FROM_USER_ID,
				null,
				true
			);

			delete_metadata(
				'user',
				0,
				Learndash_Admin_Import::META_KEY_IMPORTED_FROM_USER_ID,
				null,
				true
			);

			delete_metadata(
				'term',
				0,
				Learndash_Admin_Import::META_KEY_IMPORTED_FROM_TERM_ID,
				null,
				true
			);

			delete_transient( Learndash_Admin_Import::TRANSIENT_KEY_STATISTIC_REF_IDS );
		}

		/**
		 * Imports data.
		 *
		 * @since 4.3.0
		 *
		 * @param array  $options           Import options.
		 * @param string $working_directory Working directory.
		 * @param int    $user_id           User ID. All posts except essays and assignments are attached to this user.
		 *
		 * @return void
		 */
		protected function import( array $options, string $working_directory, int $user_id ): void {
			$this->logger->info( 'Import started.' );
			$this->logger->log_options( $options );

			$this->file_handler->set_working_directory( $working_directory );

			$importers_mapper = new Learndash_Admin_Import_Mapper( $this->file_handler, $this->logger );

			foreach ( $importers_mapper->map( $options, $user_id ) as $importer ) {
				$importer->import_data();

				Learndash_Admin_Import::clear_wpdb_query_cache();

				/**
				 * Fires after an importer had been processed.
				 *
				 * @param Learndash_Admin_Import $importer The Learndash_Admin_Import instance.
				 *
				 * @since 4.3.0
				 */
				do_action( 'learndash_import_importer_processed', $importer );
			}

			( new Learndash_Admin_Import_Associations_Handler() )->handle();

			Learndash_Admin_Action_Scheduler::add_admin_notice(
				__( 'Import completed successfully.', 'learndash' ),
				'success',
				$this->get_scheduler_action_name()
			);
		}

		/**
		 * Validates the request.
		 *
		 * @since 4.3.0
		 *
		 * @return void
		 */
		protected function validate(): void {
			if (
				! isset( $_POST['nonce'] ) ||
				! wp_verify_nonce(
					sanitize_text_field( wp_unslash( $_POST['nonce'] ) ),
					self::AJAX_ACTION_NAME
				)
			) {
				wp_send_json_error(
					array(
						'message' => esc_html__( 'Invalid request.', 'learndash' ),
					)
				);
			}

			if (
				! isset( $_FILES['file'] ) ||
				! isset( $_FILES['file']['type'] ) ||
				! in_array( $_FILES['file']['type'], array( 'application/zip', 'application/x-zip-compressed' ), true ) ||
				( isset( $_FILES['file']['error'] ) && UPLOAD_ERR_OK !== $_FILES['file']['error'] )
			) {
				$upload_errors = array(
					UPLOAD_ERR_INI_SIZE   => esc_html__(
						'The uploaded file exceeds the upload_max_filesize directive in php.ini.',
						'learndash'
					),
					UPLOAD_ERR_FORM_SIZE  => esc_html__(
						'The uploaded file exceeds the MAX_FILE_SIZE directive that was specified in the HTML form.',
						'learndash'
					),
					UPLOAD_ERR_PARTIAL    => esc_html__(
						'The uploaded file was only partially uploaded.',
						'learndash'
					),
					UPLOAD_ERR_NO_FILE    => esc_html__(
						'No file was uploaded',
						'learndash'
					),
					UPLOAD_ERR_NO_TMP_DIR => esc_html__(
						'Missing a temporary folder',
						'learndash'
					),
					UPLOAD_ERR_CANT_WRITE => esc_html__(
						'Failed to write file to disk.',
						'learndash'
					),
					UPLOAD_ERR_EXTENSION  => esc_html__(
						'A PHP extension stopped the file upload.',
						'learndash'
					),
				);

				$error_message = esc_html__( 'Uploading of your import archive failed.', 'learndash' );
				if ( UPLOAD_ERR_OK !== $_FILES['file']['error'] ) {
					$error_message .= ' ' . $upload_errors[ absint( wp_unslash( $_FILES['file']['error'] ) ) ];
				}

				wp_send_json_error(
					array(
						'message' => $error_message,
					)
				);
			}
		}

		/**
		 * Enqueues the import task.
		 *
		 * @since 4.3.0
		 *
		 * @param string $file_path Import file path.
		 *
		 * @return bool|WP_Error True on success. WP_Error if an error occurred.
		 */
		public function enqueue_import_task( string $file_path ) {
			$zip_archive = new ZipArchive();
			$zip_archive->open( $file_path );

			$options_file_name = Learndash_Admin_Export_Configuration::FILE_NAME . Learndash_Admin_Import_Export_File_Handler::FILE_EXTENSION;

			$import_options = $zip_archive->getFromName( $options_file_name );

			if ( false === $import_options ) {
				return new WP_Error(
					'ld_import_invalid_archive',
					sprintf(
						// Translators: placeholder: File name.
						__( 'Invalid import archive. Import configuration file "%s" not found.', 'learndash' ),
						$options_file_name
					)
				);
			}

			$import_files_found = false;

			// phpcs:ignore WordPress.NamingConventions.ValidVariableName.UsedPropertyNotSnakeCase
			for ( $i = 0; $i < $zip_archive->numFiles; $i++ ) {
				$file = $zip_archive->statIndex( $i );

				if ( ! $file ) {
					continue;
				}

				$file_name = $file['name'];

				// If a file has .ld extension and is not inside a folder, and it's not a config file - it's a valid LD import file.
				if (
					$file_name !== $options_file_name
					&& Learndash_Admin_Import_Export_File_Handler::FILE_EXTENSION === mb_substr( $file_name, - mb_strlen( Learndash_Admin_Import_Export_File_Handler::FILE_EXTENSION ) )
					&& false === mb_strpos( $file_name, '/' ) // Protection from cases like "__MACOSX/._configuration.ld".
				) {
					$import_files_found = true;

					break;
				}
			}

			if ( ! $import_files_found ) {
				return new WP_Error(
					'ld_import_invalid_archive',
					sprintf(
						// Translators: placeholder: File extension, file name.
						__( 'Invalid import archive. Files with the "%1$s" extension except the configuration file "%2$s" were not found.', 'learndash' ),
						Learndash_Admin_Import_Export_File_Handler::FILE_EXTENSION,
						$options_file_name
					)
				);
			}

			try {
				$working_directory = $this->file_handler->unzip( $zip_archive );

				$zip_archive->close();

				$this->action_scheduler->enqueue_task(
					$this->get_scheduler_action_name(),
					array(
						'import_options'    => json_decode( $import_options, true ),
						'working_directory' => $working_directory,
						'user_id'           => get_current_user_id(),
					),
					$this->get_scheduler_action_name(),
					__(
						'Import is in the processing queue. Please reload this page to see the import status.',
						'learndash'
					),
					__(
						'Import is in progress. It may take a few minutes. Reload this page to see the import status.',
						'learndash'
					)
				);

				return true;
			} catch ( Exception $e ) {
				return new WP_Error(
					'ld_import_failed',
					esc_html__( 'Failed to run import.', 'learndash' )
				);
			}
		}
	}
}
