<?php

// phpcs:ignore Generic.Commenting.DocComment.MissingShort
/** @noinspection PhpUndefinedFunctionInspection */

namespace WPForms\Pro\Tasks\Actions;

use WPForms\Tasks\Task;
use WPForms\Tasks\Tasks;

/**
 * Class Migration190Task.
 *
 * @since 1.9.0
 */
class Migration190Task extends Task {

	/**
	 * Action name for this task.
	 *
	 * @since 1.9.0
	 */
	const ACTION = 'wpforms_process_migration_190';

	/**
	 * Status option name.
	 *
	 * @since 1.9.0
	 */
	const STATUS = 'wpforms_process_migration_190_status';

	/**
	 * Start status.
	 *
	 * @since 1.9.0
	 */
	const START = 'start';

	/**
	 * In progress status.
	 *
	 * @since 1.9.0
	 */
	const IN_PROGRESS = 'in progress';

	/**
	 * Completed status.
	 *
	 * @since 1.9.0
	 */
	const COMPLETED = 'completed';

	/**
	 * DB indices to add.
	 *
	 * @since 1.9.0
	 *
	 * @var array[]
	 */
	private $db_indices;

	/**
	 * Class constructor.
	 *
	 * @since 1.9.0
	 */
	public function __construct() {

		parent::__construct( self::ACTION );
	}

	/**
	 * Initialize the task with all the proper checks.
	 *
	 * @since 1.9.0
	 */
	public function init() {

		$entry_handler        = wpforms()->obj( 'entry' );
		$entry_fields_handler = wpforms()->obj( 'entry_fields' );

		if ( ! $entry_handler || ! $entry_fields_handler ) {
			return;
		}

		$this->db_indices = [
			// Entries table indices.
			[
				'table_name' => $entry_handler->table_name,
				'index_name' => 'date',
				'key_part'   => 'date',
			],
			[
				'table_name' => $entry_handler->table_name,
				'index_name' => 'starred',
				'key_part'   => 'starred',
			],
			[
				'table_name' => $entry_handler->table_name,
				'index_name' => 'status',
				'key_part'   => 'status',
			],
			[
				'table_name' => $entry_handler->table_name,
				'index_name' => 'type',
				'key_part'   => 'type',
			],
			[
				'table_name' => $entry_handler->table_name,
				'index_name' => 'viewed',
				'key_part'   => 'viewed',
			],
			// Entry Fields table indices.
			[
				'table_name' => $entry_fields_handler->table_name,
				'index_name' => 'value',
				'key_part'   => 'value(32)',
			],
			[
				'table_name' => $entry_fields_handler->table_name,
				'index_name' => 'date',
				'key_part'   => 'date',
			],
		];

		// Bail out if migration is not started or completed.
		$status = get_option( self::STATUS );

		if ( ! $status || $status === self::COMPLETED ) {
			return;
		}

		$this->hooks();

		if ( $status === self::START ) {
			// Mark that migration is in progress.
			update_option( self::STATUS, self::IN_PROGRESS );

			// Init migration.
			$this->init_migration();
		}
	}

	/**
	 * Migrate an entry.
	 *
	 * @since 1.9.0
	 *
	 * @param int|mixed $action_index Action index.
	 */
	public function migrate( $action_index ) {

		$action_index = (int) $action_index;

		if ( ! array_key_exists( $action_index, $this->db_indices ) ) {
			return;
		}

		// We create indexes in the background as it could take significant time on a big database.
		$this->add_index(
			$this->db_indices[ $action_index ]['table_name'],
			$this->db_indices[ $action_index ]['index_name'],
			$this->db_indices[ $action_index ]['key_part']
		);
	}

	/**
	 * After process queue action.
	 * Set status as completed.
	 *
	 * @since 1.9.0
	 */
	public function after_process_queue() {

		$tasks = wpforms()->obj( 'tasks' );

		if ( ! $tasks || $tasks->is_scheduled( self::ACTION ) ) {
			return;
		}

		// Mark that migration is finished.
		update_option( self::STATUS, self::COMPLETED );
	}

	/**
	 * Add hooks.
	 *
	 * @since 1.9.0
	 */
	private function hooks() {

		// Register the migrate action.
		add_action( self::ACTION, [ $this, 'migrate' ] );

		// Register after process queue action.
		add_action( 'action_scheduler_after_process_queue', [ $this, 'after_process_queue' ] );
	}

	/**
	 * Init migration.
	 *
	 * @since 1.9.0
	 */
	private function init_migration() {

		foreach ( $this->db_indices as $index => $value ) {
			// We do not use Task class here as we do not need meta. So, we reduce the number of DB requests.
			as_enqueue_async_action( self::ACTION, [ $index ], Tasks::GROUP );
		}
	}

	/**
	 * Add index to a table.
	 *
	 * @since 1.9.0
	 *
	 * @param string $table_name Table.
	 * @param string $index_name Index name.
	 * @param string $key_part   Key part.
	 *
	 * @return void
	 */
	private function add_index( string $table_name, string $index_name, string $key_part ) {

		global $wpdb;

		// Check if the index already exists.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching
		$result = $wpdb->get_var(
			"SELECT COUNT(1) IndexIsThere
					FROM INFORMATION_SCHEMA.STATISTICS
					WHERE table_schema = DATABASE()
      					AND table_name = '$table_name'
          				AND index_name = '$index_name'"
		);

		if ( $result === '1' ) {
			return;
		}
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching

		// Change the column length for the wp_wpforms_entry_meta.type column to 255 and add an index.
		// phpcs:disable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->query( "CREATE INDEX $index_name ON $table_name ( $key_part )" );
		// phpcs:enable WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.SchemaChange, WordPress.DB.PreparedSQL.InterpolatedNotPrepared, WordPress.DB.DirectDatabaseQuery.NoCaching
	}
}
