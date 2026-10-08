<?php
/**
 * LearnDash background export engine.
 *
 * Queues chunked CSV exports for the Reports settings page through the
 * Learndash_Admin_Action_Scheduler wrapper, surfacing progress as admin notices.
 *
 * @since 5.1.6
 *
 * @package LearnDash\Reports
 */

use LearnDash\Core\Utilities\Cast;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( class_exists( 'Learndash_Admin_Background_Export' ) ) {
	return;
}

/**
 * LearnDash background export engine.
 *
 * @since 5.1.6
 */
class Learndash_Admin_Background_Export {
	/**
	 * Action Scheduler hook name used to run a single export chunk.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	private const EXPORT_SCHEDULER_TASK_NAME = 'learndash_report_export_run_chunk';

	/**
	 * Action Scheduler group for the export chunk queue.
	 *
	 * @since 5.1.6
	 *
	 * @var string
	 */
	private const EXPORT_SCHEDULER_GROUP = 'reports-export';

	/**
	 * Action Scheduler wrapper that queues chunks and renders progress notices.
	 *
	 * @since 5.1.6
	 *
	 * @var Learndash_Admin_Action_Scheduler
	 */
	private $scheduler;

	/**
	 * Shared engine instance exposed to the report handlers via get_instance().
	 *
	 * @since 5.1.6
	 *
	 * @var self|null
	 */
	private static $instance = null;

	/**
	 * Registers the chunk dispatcher on the injected Action Scheduler wrapper.
	 *
	 * @since 5.1.6
	 *
	 * @param Learndash_Admin_Action_Scheduler $scheduler Scheduler wrapper for the export group.
	 */
	public function __construct( Learndash_Admin_Action_Scheduler $scheduler ) {
		$this->scheduler = $scheduler;

		$this->scheduler->register_callback(
			self::EXPORT_SCHEDULER_TASK_NAME,
			array( $this, 'handle_export_chunk_task' ),
			10,
			2
		);
	}

	/**
	 * Builds and stores the shared background export engine.
	 *
	 * @since 5.1.6
	 *
	 * @return self
	 */
	public static function init(): self {
		self::$instance = new self( new Learndash_Admin_Action_Scheduler( self::EXPORT_SCHEDULER_GROUP ) );

		return self::$instance;
	}

	/**
	 * Returns the shared engine instance, or null before init() runs.
	 *
	 * @since 5.1.6
	 *
	 * @return self|null Shared engine instance.
	 */
	public static function get_instance(): ?self {
		return self::$instance;
	}

	/**
	 * Returns whether an export chunk is currently queued or running.
	 *
	 * @since 5.1.6
	 *
	 * @return bool True when an export chunk is queued or running, false otherwise.
	 */
	public function is_export_in_progress(): bool {
		return Learndash_Admin_Action_Scheduler::is_task_in_progress(
			self::EXPORT_SCHEDULER_GROUP,
			self::EXPORT_SCHEDULER_TASK_NAME
		);
	}

	/**
	 * Queues a single export chunk through the Action Scheduler wrapper.
	 *
	 * The chunk callback re-enqueues itself until the work drains. The transient key is passed
	 * as the related-object so the wrapper's progress notices are scoped to this export.
	 *
	 * @since 5.1.6
	 * @since 5.1.10 Computed the running completion percentage passed to the progress notice.
	 *
	 * @param string $transient_key Transient key holding the in-progress export state.
	 * @param string $slug          Report action slug.
	 *
	 * @return void
	 */
	public function enqueue_export_chunk_task( string $transient_key, string $slug ): void {
		if (
			empty( $transient_key )
			|| empty( $slug )
		) {
			return;
		}

		$label = $this->get_report_label_for_slug( $slug );

		$handler        = $this->resolve_handler_for_slug( $slug );
		$transient_data = $handler ? $handler->get_transient( $transient_key ) : null;

		$total   = ( is_array( $transient_data ) && isset( $transient_data['total_count'] ) ) ? Cast::to_int( $transient_data['total_count'] ) : 0;
		$offset  = ( is_array( $transient_data ) && isset( $transient_data['offset'] ) ) ? Cast::to_int( $transient_data['offset'] ) : 0;
		$percent = $total > 0 ? min( 99, Cast::to_int( floor( ( $offset / $total ) * 100 ) ) ) : 0;

		$this->scheduler->enqueue_task(
			self::EXPORT_SCHEDULER_TASK_NAME,
			array( $transient_key, $slug ),
			$transient_key,
			$this->get_pending_notice_message( $label ),
			$this->get_progress_notice_message( $label, $percent )
		);
	}

	/**
	 * Action Scheduler callback: processes one export chunk, re-enqueues while work remains,
	 * and posts the completion or failure notice.
	 *
	 * @since 5.1.6
	 * @since 5.1.10 Recorded the run's terminal state on the transient.
	 *
	 * @param string $transient_key Transient key holding the in-progress export state.
	 * @param string $slug          Report action slug.
	 *
	 * @return void
	 */
	public function handle_export_chunk_task( string $transient_key = '', string $slug = '' ): void {
		if (
			empty( $transient_key )
			|| empty( $slug )
		) {
			return;
		}

		$handler = $this->resolve_handler_for_slug( $slug );

		if (
			! $handler
			|| ! method_exists( $handler, 'process_export_chunk' )
		) {
			return;
		}

		/*
		 * One chunk per run. Action Scheduler decides how many runs to fire and when, and already
		 * guards its own queue against the host's time and memory limits, so a second budget loop
		 * here would only duplicate that with worse information.
		 */
		try {
			$continue = $handler->process_export_chunk( $transient_key );
		} catch ( Throwable $e ) {
			/*
			 * A failed chunk ends the export rather than retrying it: the chunk is deterministic, so
			 * a repeat would fail the same way, and re-enqueueing would spin. Recording the terminal
			 * state matters as much as the notice — without it the transient still reads as running
			 * and the Reports page poller never stops.
			 */
			$this->mark_export_status( $handler, $transient_key, 'export_failed' );

			// @phpstan-ignore-next-line method.notFound -- Throwable::getMessage() is defined on the interface at runtime; PHPStan stubs miss it.
			$this->notify_export_failed( $transient_key, $e->getMessage() );
			return;
		}

		if ( $continue ) {
			$this->enqueue_export_chunk_task( $transient_key, $slug );
			return;
		}

		$transient_data = $this->mark_export_status( $handler, $transient_key, 'export_complete' );

		$download_link = is_array( $transient_data ) && isset( $transient_data['report_url'] )
			? Cast::to_string( $transient_data['report_url'] )
			: '';

		$this->notify_export_ready( $transient_key, $download_link, $this->get_report_label_for_slug( $slug ) );
	}

	/**
	 * Resolves the report action handler instance registered for a given slug.
	 *
	 * @since 5.1.6
	 *
	 * @param string $slug Report action slug (e.g. user-quizzes, user-courses).
	 *
	 * @return Learndash_Admin_Settings_Data_Reports|null Handler instance or null when no action matches.
	 */
	public function resolve_handler_for_slug( string $slug ) {
		$base = new Learndash_Admin_Settings_Data_Reports();
		$base->init_report_actions();
		$actions = $base->get_report_actions();

		if (
			! isset( $actions[ $slug ]['instance'] )
			|| ! $actions[ $slug ]['instance'] instanceof Learndash_Admin_Settings_Data_Reports
		) {
			return null;
		}

		return $actions[ $slug ]['instance'];
	}

	/**
	 * Posts the "export ready" success notice with the CSV download link.
	 *
	 * @since 5.1.6
	 *
	 * @param string $transient_key Transient key holding the export state.
	 * @param string $download_link CSV download URL stored on the export transient.
	 * @param string $label         Report type label (e.g. the custom Course or Quiz label). Empty for the generic wording.
	 *
	 * @return void
	 */
	private function notify_export_ready( string $transient_key, string $download_link, string $label = '' ): void {
		$download_html = '<a href="' . esc_url( $download_link ) . '">' . esc_html__( 'Download the CSV file.', 'learndash' ) . '</a>';

		if ( '' !== $label ) {
			$message = sprintf(
				// translators: 1: report type label (e.g. Course, Quiz), 2: HTML link to download the exported CSV file.
				__( 'Your %1$s report export is ready. %2$s', 'learndash' ),
				$label,
				$download_html
			);
		} else {
			$message = sprintf(
				// translators: %s: HTML link to download the exported CSV file.
				__( 'Your report export is ready. %s', 'learndash' ),
				$download_html
			);
		}

		Learndash_Admin_Action_Scheduler::add_admin_notice( $message, 'success', $transient_key );
	}

	/**
	 * Posts an error admin notice for a failed export.
	 *
	 * @since 5.1.6
	 *
	 * @param string $transient_key Transient key holding the export state.
	 * @param string $message       User-facing error message.
	 *
	 * @return void
	 */
	private function notify_export_failed( string $transient_key, string $message ): void {
		Learndash_Admin_Action_Scheduler::add_admin_notice( $message, 'error', $transient_key );
	}

	/**
	 * Returns the admin-notice message shown while an export chunk is queued.
	 *
	 * @since 5.1.6
	 * @since 5.1.10 Added the $label parameter to name the report type.
	 *
	 * @param string $label Report type label (e.g. the custom Course or Quiz label). Empty for the generic wording.
	 *
	 * @return string Pending-state notice message.
	 */
	private function get_pending_notice_message( string $label = '' ): string {
		if ( '' !== $label ) {
			return sprintf(
				// translators: %s: report type label (e.g. Course, Quiz).
				__( 'Your %s report export is queued. Refresh this page to check progress.', 'learndash' ),
				$label
			);
		}

		return __( 'Your report export is queued. Refresh this page to check progress.', 'learndash' );
	}

	/**
	 * Returns the admin-notice message shown while an export chunk is running.
	 *
	 * @since 5.1.6
	 * @since 5.1.10 Added the $label and $percent parameters to name the report type and its progress.
	 *
	 * @param string $label   Report type label (e.g. the custom Course or Quiz label). Empty for the generic wording.
	 * @param int    $percent Completion percentage (0-99) shown while the export is running.
	 *
	 * @return string Running-state notice message.
	 */
	private function get_progress_notice_message( string $label = '', int $percent = 0 ): string {
		if ( '' !== $label ) {
			return sprintf(
				// translators: 1: report type label (e.g. Course, Quiz), 2: completion percentage.
				__( 'Your %1$s report export is running (%2$d%%). Refresh this page to check progress.', 'learndash' ),
				$label,
				$percent
			);
		}

		return sprintf(
			// translators: %d: completion percentage.
			__( 'Your report export is running (%d%%). Refresh this page to check progress.', 'learndash' ),
			$percent
		);
	}

	/**
	 * Resolves the human-readable report label registered for a given slug.
	 *
	 * @since 5.1.10
	 *
	 * @param string $slug Report action slug (e.g. user-courses, user-quizzes).
	 *
	 * @return string Report type label (e.g. the custom Course or Quiz label), or an empty string when none is registered.
	 */
	private function get_report_label_for_slug( string $slug ): string {
		$base = new Learndash_Admin_Settings_Data_Reports();
		$base->init_report_actions();
		$actions = $base->get_report_actions();

		if ( ! isset( $actions[ $slug ]['label'] ) ) {
			return '';
		}

		return $actions[ $slug ]['label'];
	}

	/**
	 * Records a terminal status on the export transient and returns the updated state.
	 *
	 * The status is what tells the Reports page poller an export has stopped. The CSV file alone
	 * cannot say so — a partial file left by a failed run looks the same as a finished one.
	 *
	 * @since 5.1.10
	 *
	 * @param Learndash_Admin_Settings_Data_Reports $handler       Report action handler owning the transient.
	 * @param string                                $transient_key Transient key holding the export state.
	 * @param string                                $status        Terminal status to record, e.g. export_complete or export_failed.
	 *
	 * @return array<string, mixed>|null Updated export state, or null when the transient is gone. The
	 *                                   shape stays open rather than a concrete `array{…}`: the export
	 *                                   request merges its own `filters` keys into this state, so the
	 *                                   key set is caller-extensible.
	 */
	private function mark_export_status( $handler, string $transient_key, string $status ): ?array {
		/*
		 * The last chunk may have just persisted a fresh offset inside process_export_chunk(), so
		 * re-read the transient rather than reusing a pre-chunk snapshot.
		 */
		$transient_data = $handler->get_transient( $transient_key );

		if ( ! is_array( $transient_data ) ) {
			return null;
		}

		$transient_data['status'] = $status;

		$handler->set_option_cache( $transient_key, $transient_data );

		return $transient_data;
	}
}
