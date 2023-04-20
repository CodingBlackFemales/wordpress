<?php
/**
 * LearnDash Admin Action Scheduler class.
 *
 * @since 4.2.0
 *
 * @package LearnDash\Scheduler
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Admin_Action_Scheduler' ) ) {
	/**
	 * LearnDash admin action scheduler class.
	 *
	 * Provides a wrapper for the Action Scheduler library.
	 *
	 * @since 4.2.0
	 */
	class Learndash_Admin_Action_Scheduler {
		const SCHEDULER_NOTICES_OPTION_KEY     = 'learndash_scheduler_notices';
		const SCHEDULER_FATAL_TASKS_OPTION_KEY = 'learndash_scheduler_fatal_tasks';
		const LD_GROUP_NAME_PREFIX             = 'learndash';
		const SCHEDULER_TIMEOUT                = 3600; // 1 hour.


		/**
		 * Name of the action scheduler group.
		 *
		 * @var string
		 */
		private $group_name;

		/**
		 * Registered callbacks for that group.
		 *
		 * @var array
		 */
		private $tasks_callbacks = array();

		/**
		 * Construct.
		 *
		 * @param string $group_name Name of the Scheduler group.
		 */
		public function __construct( string $group_name ) {
			$this->group_name = self::get_full_group_name( $group_name );
			$this->task_handler();
		}

		/**
		 * Returns the full group name.
		 *
		 * @param string $group_name The group name.
		 * @return string The full group name.
		 */
		private static function get_full_group_name( string $group_name ): string {
			return self::LD_GROUP_NAME_PREFIX . '/' . sanitize_key( $group_name );
		}

		/**
		 * Register an action tasks.
		 *
		 * @param string   $task_name Name of the task.
		 * @param callable $callback Callback to run.
		 * @param int      $priority Priority of the task.
		 * @param int      $accepted_args Number of accepted arguments.
		 *
		 * @throws InvalidArgumentException If $callback is not callable.
		 */
		public function register_callback( string $task_name, callable $callback, $priority = 10, $accepted_args = 1 ) {
			if ( ! is_callable( $callback ) ) {
				throw new InvalidArgumentException( __( 'Callback is not callable', 'learndash' ) );
			}
			$this->tasks_callbacks[ $task_name ] = $callback;
			add_action( $task_name, $callback, $priority, $accepted_args );
		}

		/**
		 * Add a task to the queue.
		 *
		 * @param string $task_name Name of the registered task.
		 * @param array  $task_args Task arguments.
		 * @param mixed  $related_object ID of the related object.
		 * @param string $pending_message The pending message.
		 * @param string $progress_message The progress message.
		 *
		 * @throws InvalidArgumentException If $task_name is not registered.
		 *
		 * @return void
		 */
		public function enqueue_task( string $task_name, array $task_args, $related_object = 0, string $pending_message = '', string $progress_message = '' ) {
			if ( ! isset( $this->tasks_callbacks[ $task_name ] ) ) {
				// translators: placeholder: task name.
				throw new InvalidArgumentException( sprintf( __( 'Task "%s" is not registered', 'learndash' ), $task_name ) );
			}

			$task = array(
				'task_group_name'  => $this->group_name,
				'task_action_name' => $task_name,
				'task_args'        => $task_args,
				'related_object'   => $related_object,
				'pending_message'  => $pending_message,
				'progress_message' => $progress_message,
			);

			as_enqueue_async_action(
				$task['task_action_name'],
				array_merge(
					$task['task_args'],
					array(
						'progress_message' => $task['progress_message'],
						'pending_message'  => $task['pending_message'],
						'related_object'   => $task['related_object'],
					)
				),
				$task['task_group_name']
			);
		}

		/**
		 * Init the global scheduler stuff.
		 */
		public static function init_ld_scheduler() {
			// scheduler timeout.
			add_filter(
				'action_scheduler_timeout_period',
				function() {
					return self::SCHEDULER_TIMEOUT;
				}
			);

			// failure timeout.
			add_filter(
				'action_scheduler_failure_period',
				function() {
					return self::SCHEDULER_TIMEOUT;
				}
			);

			// monitoring tasks to catch fatal errors.
			add_action(
				'action_scheduler_begin_execute',
				function( $action_id ) {
					// monitoring tasks to catch fatal errors.
					add_filter(
						'wp_die_handler',
						function() use ( $action_id ) {
							Learndash_Admin_Action_Scheduler::add_fatal_task( $action_id );
							return '_default_wp_die_handler';
						}
					);
				}
			);

			if ( is_admin() ) {
				self::show_processing_notices();
			}
			self::maybe_run_tasks_immediately();
			self::mark_fatal_tasks_as_failed();
		}

		/**
		 * Add a task to the fatal tasks list to be marked as failed.
		 *
		 * @param int $action_id Action ID.
		 */
		private static function add_fatal_task( int $action_id ) {
			$fatal_tasks   = get_option( self::SCHEDULER_FATAL_TASKS_OPTION_KEY, array() );
			$fatal_tasks[] = $action_id;
			update_option( self::SCHEDULER_FATAL_TASKS_OPTION_KEY, $fatal_tasks );
		}

		/**
		 * Mark fatal tasks as failed.
		 *
		 * @return void
		 */
		private static function mark_fatal_tasks_as_failed() {
			add_action(
				'init',
				function() {
					$fatal_tasks = get_option( Learndash_Admin_Action_Scheduler::SCHEDULER_FATAL_TASKS_OPTION_KEY, array() );
					delete_option( Learndash_Admin_Action_Scheduler::SCHEDULER_FATAL_TASKS_OPTION_KEY );
					if ( ! empty( $fatal_tasks ) ) {
						try {
							$store = ActionScheduler_Store::instance();
							foreach ( $fatal_tasks as $action_id ) {
								$store->mark_failure( $action_id );
							}
						} catch ( Throwable $th ) { // phpcs:ignore Generic.CodeAnalysis.EmptyStatement.DetectedCatch
							// Do nothing. The task was removed.
						}
					}
				}
			);
		}

		/**
		 * Run tasks immediately if we have a define.
		 */
		private static function maybe_run_tasks_immediately() {
			// force run tasks (only for development purposes).
			if ( defined( 'LEARNDASH_RUN_TASKS_IMMEDIATELY' ) && LEARNDASH_RUN_TASKS_IMMEDIATELY ) {
				add_action(
					'admin_init',
					function() {
						ActionScheduler::runner()->run();
					},
					99
				);
			}
		}

		/**
		 * Returns if a task is currently in progress (enqueued or running).
		 *
		 * @since 4.3.0
		 *
		 * @param string $group_name The group name.
		 * @param string $task_name The task name.
		 *
		 * @return boolean True if a task is currently in progress, false otherwise.
		 */
		public static function is_task_in_progress( string $group_name, string $task_name ): bool {
			$tasks_in_progress = as_get_scheduled_actions(
				array(
					'group'  => self::get_full_group_name( $group_name ),
					'hook'   => $task_name,
					'status' => array(
						ActionScheduler_Store::STATUS_PENDING,
						ActionScheduler_Store::STATUS_RUNNING,
					),
				)
			);
			return ! empty( $tasks_in_progress );
		}

		/**
		 * Add progress notice.
		 */
		private function task_handler() {
			$that = $this;

			// add pending and in-progress notices.
			add_action(
				'admin_init',
				function() use ( $that ) {
					$tasks_pending = as_get_scheduled_actions(
						array(
							'group'  => $that->group_name,
							'status' => ActionScheduler_Store::STATUS_PENDING,
						)
					);
					foreach ( $tasks_pending as $task ) {
						$args = $task->get_args();
						if ( isset( $args['pending_message'] ) && ! empty( $args['pending_message'] ) ) {
							self::add_admin_notice( $args['pending_message'], 'info', $args['related_object'] ?? '' );
						}
					}

					$tasks_in_progress = as_get_scheduled_actions(
						array(
							'group'  => $that->group_name,
							'status' => ActionScheduler_Store::STATUS_RUNNING,
						)
					);
					foreach ( $tasks_in_progress as $task ) {
						$args = $task->get_args();
						if ( isset( $args['progress_message'] ) && ! empty( $args['progress_message'] ) ) {
							self::add_admin_notice( $args['progress_message'], 'info', $args['related_object'] ?? '' );
						}
					}
				},
				70
			);
		}

		/**
		 * Add an admin notice.
		 *
		 * @param string $message Message to display.
		 * @param string $type Type of the message (WP CSS class). Default 'error'.
		 * @param mixed  $related_object The ID of the object related to the notice. Default 0.
		 * @param string $redirect_url URL to redirect to. Empty to not redirect.
		 */
		public static function add_admin_notice( string $message, string $type = 'error', $related_object = 0, string $redirect_url = '' ) {
			$notices = get_option( self::SCHEDULER_NOTICES_OPTION_KEY, array() );

			$related_object = sanitize_key( $related_object );
			if ( empty( $related_object ) ) {
				$related_object = md5( $message );
			}

			$notices[ $related_object ] = array(
				'message'        => $message,
				'type'           => $type,
				'related_object' => $related_object,
			);
			update_option( self::SCHEDULER_NOTICES_OPTION_KEY, $notices );

			// redirect to the url if provided.
			if ( ! empty( $redirect_url ) ) {
				learndash_safe_redirect( $redirect_url );
			}
		}

		/**
		 * Show admin notices related to the scheduled tasks.
		 */
		private static function show_processing_notices() {
			if ( wp_doing_ajax() || wp_doing_cron() ) {
				return; // bypass ajax and cron requests.
			}

			add_action(
				'admin_notices',
				function() {
					if ( ! learndash_is_admin_user() ) {
						return;
					}

					$notices = get_option( Learndash_Admin_Action_Scheduler::SCHEDULER_NOTICES_OPTION_KEY, array() );
					delete_option( Learndash_Admin_Action_Scheduler::SCHEDULER_NOTICES_OPTION_KEY );
					foreach ( $notices as $notice ) {
						?>
						<div class="notice notice-<?php echo esc_attr( $notice['type'] ); ?> is-dismissible">
							<p><?php echo $notice['message']; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped ?></p>
						</div>
						<?php
					}
				}
			);
		}
	}
}
