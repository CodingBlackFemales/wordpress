<?php

namespace WPForms\Pro\Tasks\Actions;

use WPForms\Tasks\Meta;
use WPForms\Tasks\Task;

/**
 * Class PurgeTemplateEntryTask is responsible for purging the template entry.
 *
 * @since 1.8.8
 */
class PurgeTemplateEntryTask extends Task {

	/**
	 * Action name for this task.
	 *
	 * @since 1.8.8
	 */
	const ACTION = 'wpforms_purge_template_entry';

	/**
	 * Class constructor.
	 *
	 * @since 1.8.8
	 */
	public function __construct() {

		parent::__construct( self::ACTION );

		$this->init();
	}

	/**
	 * Initialize the task.
	 *
	 * @since 1.8.8
	 */
	public function init() {

		$this->hooks();
	}

	/**
	 * Hooks.
	 *
	 * @since 1.8.8
	 */
	public function hooks() {

		add_action( 'wpforms_process_entry_saved', [ $this, 'add_task' ], 10, 4 );
		add_action( self::ACTION, [ $this, 'process' ] );
	}

	/**
	 * Add task to the queue.
	 *
	 * @since 1.8.8
	 *
	 * @param array $fields    Form fields.
	 * @param array $entry     Form entry.
	 * @param array $form_data Form data.
	 * @param int   $entry_id  Entry ID.
	 */
	public function add_task( $fields, $entry, $form_data, $entry_id ) {

		$settings = $form_data['settings'] ?? [];

		if ( ! isset( $settings['template_description'] ) ) {
			return;
		}

		/**
		 * Filters the time interval for the task to be purged in, in seconds.
		 *
		 * @since 1.8.8
		 *
		 * @param int $delay Delay in seconds.
		 */
		$delay = (int) apply_filters( 'wpforms_pro_tasks_actions_purge_template_entry_task_delay', DAY_IN_SECONDS );

		// Determine when the entry should be purged.
		$purge_timestamp = time() + $delay;

		$action_id = wpforms()->obj( 'tasks' )
			->create( self::ACTION )
			->once( $purge_timestamp )
			->params( $entry_id )
			->register();

		wpforms()->obj( 'entry_meta' )->add(
			[
				'entry_id' => $entry_id,
				'form_id'  => $form_data['id'] ?? 0,
				'type'     => 'purge_template_entry_task',
				'data'     => wp_json_encode(
					[
						'task_id'   => $action_id,
						'timestamp' => $purge_timestamp,
					]
				),
			],
			'entry_meta'
		);
	}

	/**
	 * Process the task.
	 *
	 * @since 1.8.8
	 *
	 * @param int $meta_id Meta ID.
	 */
	public function process( $meta_id ) { // phpcs:ignore WPForms.PHP.HooksMethod.InvalidPlaceForAddingHooks

		$task_meta = new Meta();
		$meta      = $task_meta->get( (int) $meta_id );

		if ( empty( $meta ) || empty( $meta->data ) ) {
			return;
		}

		list( $entry_id ) = $meta->data;

		// Allow the cron to delete the entry.
		add_filter( 'wpforms_current_user_can', '__return_true' );

		$this->process_before_delete( $entry_id );

		// Delete the entry.
		wpforms()->obj( 'entry' )->delete( $entry_id );
	}

	/**
	 * Perform any additional actions before deleting the entry.
	 *
	 * For example, this allows us to delete the user created via User Registration addon.
	 * Form Template entries can only be created by logged-in admins with sufficient permissions,
	 * and they are considered test entries. Users created via User Registration addon are
	 * also considered test users and should be automatically deleted too.
	 *
	 * @since 1.8.8
	 *
	 * @param int $entry_id Entry ID.
	 */
	private function process_before_delete( int $entry_id ) {

		$registered_user_id = wpforms()->obj( 'entry_meta' )->get_meta(
			[
				'entry_id' => $entry_id,
				'type'     => 'registered_user_id',
				'number'   => 1,
			]
		);

		$user_id = $registered_user_id[0]->data ?? 0;

		if ( empty( $user_id ) ) {
			return;
		}

		// Delete the user.
		wp_delete_user( $user_id );
	}
}
