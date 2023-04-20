<?php
/**
 * LearnDash Admin Import/Export Handler.
 *
 * @since   4.3.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Admin_Import_Export_Handler' ) ) {
	/**
	 * Class LearnDash Admin Import/Export Handler.
	 *
	 * @since 4.3.0
	 */
	abstract class Learndash_Admin_Import_Export_Handler {
		const AJAX_ACTION_NAME = 'learndash_import';

		const SCHEDULER_ACTION_NAME = 'learndash_import_action';

		/**
		 * File Handler class instance.
		 *
		 * @since 4.3.0
		 *
		 * @var Learndash_Admin_Import_File_Handler|Learndash_Admin_Export_File_Handler
		 */
		protected $file_handler;

		/**
		 * Action Scheduler class instance.
		 *
		 * @since 4.3.0
		 *
		 * @var Learndash_Admin_Action_Scheduler
		 */
		protected $action_scheduler;

		/**
		 * Logger class instance.
		 *
		 * @since 4.3.0 d
		 * @since TBA   Changed to the `Learndash_Import_Export_Logger` class.
		 *
		 * @var Learndash_Import_Export_Logger
		 */
		protected $logger;

		/**
		 * Constructor.
		 *
		 * @since 4.3.0
		 * @since 4.5.0   Changed the $logger param to the `Learndash_Import_Export_Logger` class.
		 *
		 * @param Learndash_Admin_Import_Export_File_Handler $file_handler     File handler class instance.
		 * @param Learndash_Admin_Action_Scheduler           $action_scheduler Action Scheduler class instance.
		 * @param Learndash_Import_Export_Logger             $logger           Logger class instance.
		 *
		 * @return void
		 */
		public function __construct(
			Learndash_Admin_Import_Export_File_Handler $file_handler,
			Learndash_Admin_Action_Scheduler $action_scheduler,
			Learndash_Import_Export_Logger $logger
		) {
			// @phpstan-ignore-next-line -- Learndash_Admin_Import_Export_File_Handler is the parent class.
			$this->file_handler     = $file_handler;
			$this->action_scheduler = $action_scheduler;
			$this->logger           = $logger;

			add_action(
				'wp_ajax_' . $this->get_ajax_action_name(),
				array( $this, 'handle' )
			);

			$this->action_scheduler->register_callback(
				$this->get_scheduler_action_name(),
				array( $this, 'handle_action' ),
				10,
				3
			);
		}

		/**
		 * Handles Import/Export.
		 *
		 * @since 4.3.0
		 *
		 * @return void
		 */
		abstract public function handle(): void;

		/**
		 * Handles Import/Export Action.
		 *
		 * @since 4.3.0
		 *
		 * @param array $options Options.
		 *
		 * @return void
		 */
		abstract public function handle_action( array $options ): void;

		/**
		 * Returns the ajax action name.
		 *
		 * @since 4.3.0
		 *
		 * @return string
		 */
		abstract protected function get_ajax_action_name(): string;

		/**
		 * Returns the scheduler action name.
		 *
		 * @since 4.3.0
		 *
		 * @return string
		 */
		abstract protected function get_scheduler_action_name(): string;
	}
}
