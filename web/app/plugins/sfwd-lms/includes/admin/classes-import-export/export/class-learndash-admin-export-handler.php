<?php
/**
 * LearnDash Admin Export Handler.
 *
 * @since 4.3.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (
	class_exists( 'Learndash_Admin_Import_Export_Handler' ) &&
	! class_exists( 'Learndash_Admin_Export_Handler' )
) {
	/**
	 * Class LearnDash Admin Export Handler.
	 *
	 * @since 4.3.0
	 */
	class Learndash_Admin_Export_Handler extends Learndash_Admin_Import_Export_Handler {
		const TYPE_ALL      = 'all';
		const TYPE_SELECTED = 'selected';

		const AJAX_ACTION_NAME      = 'learndash_export';
		const SCHEDULER_ACTION_NAME = 'learndash_export_action';

		/**
		 * Returns the export options.
		 *
		 * @since 4.3.0
		 *
		 * @return array Array of export options.
		 */
		public function get_available_export_options(): array {
			return array(
				'post_types'         => LDLMS_Post_Types::get_post_types(),
				'post_type_settings' => LDLMS_Post_Types::get_post_types(),
				'users'              => array( 'profiles', 'progress' ),
				'other'              => array( 'settings' ),
			);
		}

		/**
		 * Handles export.
		 *
		 * @since 4.3.0
		 *
		 * @return void
		 */
		public function handle(): void {
			$this->validate();

			try {
				$this->file_handler->delete_zip_archive();

				$type = sanitize_text_field(
					wp_unslash( $_POST['type'] ) // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.InputNotValidated, WordPress.Security.NonceVerification.Missing
				);

				$this->action_scheduler->enqueue_task(
					$this->get_scheduler_action_name(),
					array(
						'export_options' => $this->map_export_options( $type ),
					),
					$this->get_scheduler_action_name(),
					__(
						'Export is in the processing queue. Please reload this page to see the export status.',
						'learndash'
					),
					__(
						'Export is in progress. It may take a few minutes. Reload this page to see the export status.',
						'learndash'
					)
				);
			} catch ( Exception $e ) {
				wp_send_json_error(
					array(
						'message' => esc_html__( 'Failed to run export.', 'learndash' ),
					)
				);
			}

			/**
			 * Fires after an export task is enqueued.
			 *
			 * @since 4.3.0
			 */
			do_action( 'learndash_export_task_enqueued' );

			wp_send_json_success();
		}

		/**
		 * Exports data.
		 *
		 * @since 4.3.0
		 *
		 * @param array $options Export options.
		 *
		 * @return void
		 */
		public function handle_action( array $options ): void {
			if ( empty( $options ) ) {
				return;
			}

			$this->logger->info( 'Export started.' );

			try {
				$exporters_mapper = new Learndash_Admin_Export_Mapper( $this->file_handler, $this->logger );

				foreach ( $exporters_mapper->map( $options ) as $exporter ) {
					$exporter->generate_export_file();
					$exporter->export_media_files();

					/**
					 * Fires after an exporter had been processed.
					 *
					 * @param Learndash_Admin_Export $exporter The Learndash_Admin_Export instance.
					 *
					 * @since 4.3.0
					 */
					do_action( 'learndash_export_exporter_processed', $exporter );
				}

				$this->file_handler->generate_zip_archive();

				Learndash_Admin_Action_Scheduler::add_admin_notice(
					sprintf(
						// translators: Placeholder: html tag 'a'.
						__( 'Export completed successfully. %1$sDownload file%2$s', 'learndash' ),
						'<a target="_blank" href="' . esc_url( $this->file_handler->get_zip_archive_url() ) . '">',
						'</a>'
					),
					'success',
					$this->get_scheduler_action_name()
				);
			} catch ( Exception $e ) {
				$this->logger->error( 'Export exception: ' . $e->getMessage() );

				Learndash_Admin_Action_Scheduler::add_admin_notice(
					$e->getMessage(),
					'error',
					$this->get_scheduler_action_name()
				);
			} finally {
				$this->logger->info( 'Export finished.' . PHP_EOL );

				/**
				 * Fires after an export task is handled.
				 *
				 * @since 4.3.0
				 */
				do_action( 'learndash_export_task_handled' );
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
		 * Returns mapped export options.
		 *
		 * @since 4.3.0
		 *
		 * @param string $type Export type.
		 *
		 * @return array
		 */
		protected function map_export_options( string $type ): array {
			$export_options = $this->get_available_export_options();

			if ( self::TYPE_SELECTED === $type ) {
				foreach ( $export_options as $export_option => &$export_option_values ) {
					$option_is_valid = (
						isset( $_POST['options'][ $export_option ] ) && // phpcs:ignore WordPress.Security.NonceVerification.Missing
						is_array( $_POST['options'][ $export_option ] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
					);

					if ( $option_is_valid ) {
						$export_option_values = array_intersect(
							$export_option_values,
							array_map(
								'sanitize_text_field',
								(array) wp_unslash( $_POST['options'][ $export_option ] ) // phpcs:ignore WordPress.Security.NonceVerification.Missing
							)
						);

						$export_option_values = array_values( $export_option_values );
					} else {
						$export_option_values = array();
					}
				}
			}

			/**
			 * Filters export options.
			 *
			 * @since 4.3.0
			 *
			 * @param array $export_options Export options.
			 *
			 * @return array Export options.
			 */
			return apply_filters( 'learndash_export_options', $export_options );
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
				! isset( $_POST['type'] ) ||
				! in_array(
					sanitize_text_field( wp_unslash( $_POST['type'] ) ),
					array( self::TYPE_ALL, self::TYPE_SELECTED ),
					true
				) ||
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

			$type = sanitize_text_field( wp_unslash( $_POST['type'] ) );

			// filter export options if user selected specific items.
			if ( self::TYPE_SELECTED === $type ) {
				if ( empty( $_POST['options'] ) ) {
					wp_send_json_error(
						array(
							'message' => esc_html__(
								'Please choose at least one thing to export.',
								'learndash'
							),
						)
					);
				}

				if (
					isset( $_POST['options']['users'] ) &&
					in_array( 'progress', $_POST['options']['users'], true )
				) {
					if ( ! in_array( 'profiles', $_POST['options']['users'], true ) ) {
						wp_send_json_error(
							array(
								'message' => esc_html__(
									'It is not possible to export user progress without user profiles.',
									'learndash'
								),
							)
						);
					}

					$all_required_post_types_are_presented = false;
					if ( isset( $_POST['options']['post_types'] ) ) {
						$post_types_required_with_progress = array(
							LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::COURSE ),
							LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::LESSON ),
							LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::TOPIC ),
							LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::QUIZ ),
						);

						$post_types_presented = array_intersect(
							$post_types_required_with_progress,
							array_map(
								'sanitize_text_field',
								(array) wp_unslash( $_POST['options']['post_types'] )
							)
						);

						$all_required_post_types_are_presented = (
							count( $post_types_presented ) === count( $post_types_required_with_progress )
						);
					}

					if ( ! $all_required_post_types_are_presented ) {
						wp_send_json_error(
							array(
								'message' => sprintf(
									// translators: placeholder: courses, lessons, topics, quizzes.
									esc_html_x(
										'It is not possible to export user progress without %1$s, %2$s, %3$s or %4$s.',
										'placeholder: courses, lessons, topics, quizzes',
										'learndash'
									),
									LearnDash_Custom_Label::label_to_lower( 'courses' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									LearnDash_Custom_Label::label_to_lower( 'lessons' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									LearnDash_Custom_Label::label_to_lower( 'topics' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
									LearnDash_Custom_Label::label_to_lower( 'quizzes' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								),
							)
						);
					}
				}

				$has_quiz_posts = isset( $_POST['options']['post_types'] ) && in_array(
					LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::QUIZ ),
					$_POST['options']['post_types'],
					true
				);

				$has_question_posts = isset( $_POST['options']['post_types'] ) && in_array(
					LDLMS_Post_Types::get_post_type_slug( LDLMS_Post_Types::QUESTION ),
					$_POST['options']['post_types'],
					true
				);

				if (
					isset( $_POST['options']['post_types'] ) &&
					(
						( $has_quiz_posts && ! $has_question_posts ) ||
						( ! $has_quiz_posts && $has_question_posts )
					)
				) {
					wp_send_json_error(
						array(
							'message' => sprintf(
							// translators: placeholder: quizzes, questions.
								esc_html_x(
									'It is not possible to export %1$s without %2$s and vice versa.',
									'placeholder: quizzes, questions',
									'learndash'
								),
								LearnDash_Custom_Label::label_to_lower( 'quizzes' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
								LearnDash_Custom_Label::label_to_lower( 'questions' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
							),
						)
					);
				}
			}
		}
	}
}
