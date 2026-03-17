<?php
/**
 * BuddyBoss Pro Reactions Background Process
 *
 * @package BuddyBossPro
 *
 * @since 2.4.50
 */

defined( 'ABSPATH' ) || exit;

if (
	! class_exists( 'BB_Background_Process' ) &&
	file_exists( buddypress()->plugin_dir . 'bp-core/classes/class-bb-background-process.php' )
) {
	include_once buddypress()->plugin_dir . 'bp-core/classes/class-bb-background-process.php';
}

if ( class_exists( 'BB_Background_Process' ) && ! class_exists( 'BB_Reactions_Background_Process' ) ) {

	/**
	 * BB_Reactions_Background_Process class.
	 */
	class BB_Reactions_Background_Process extends BB_Background_Process {

		/**
		 * Background job queue table name.
		 *
		 * @since 2.4.50
		 *
		 * @var string
		 */
		public static $table_name;

		/**
		 * Initiate new async request.
		 *
		 * @since 2.4.50
		 */
		public function __construct() {
			$this->prefix = 'bb_pro_reactions';

			parent::__construct();

			self::$table_name = parent::$table_name;
		}

		/**
		 * Processes the task.
		 *
		 * @since 2.4.50
		 *
		 * @param array $callback Update callback function.
		 *
		 * @return bool
		 */
		protected function task( $callback ) {
			$result = false;

			$args = array();
			if ( ! is_callable( $callback ) ) {
				$args     = ( ! empty( $callback['args'] ) ) ? $callback['args'] : array();
				$callback = ( ! empty( $callback['callback'] ) ) ? $callback['callback'] : '';
			}

			if ( is_callable( $callback ) ) {
				if ( function_exists( 'bb_error_log' ) ) {
					// phpcs:ignore
					bb_error_log( sprintf( 'Running %s callback', json_encode( $callback ) ) );
				} else {
					// phpcs:ignore
					error_log( sprintf( 'Running %s callback', json_encode( $callback ) ) );
				}


				if ( empty( $args ) ) {
					$result = (bool) call_user_func( $callback, $this );
				} else {
					$result = (bool) call_user_func_array( $callback, $args );
				}

				if ( function_exists( 'bb_error_log' ) ) {
					if ( $result ) {
						// phpcs:ignore
						bb_error_log( sprintf( '%s callback needs to run again', json_encode( $callback ) ) );
					} else {
						// phpcs:ignore
						bb_error_log( sprintf( 'Finished running %s callback', json_encode( $callback ) ) );
					}
				} else {
					if ( $result ) {
						// phpcs:ignore
						error_log( sprintf( '%s callback needs to run again', json_encode( $callback ) ) );
					} else {
						// phpcs:ignore
						error_log( sprintf( 'Finished running %s callback', json_encode( $callback ) ) );
					}
				}
			} else {
				// phpcs:ignore
				error_log( sprintf( 'Could not find %s callback', json_encode( $callback ) ) );
			}

			return $result ? $callback : false;
		}

		/**
		 * Check the reaction background is running or not.
		 *
		 * @since 2.4.50
		 *
		 * @param array $args Array to compare with data.
		 *
		 * @return bool
		 */
		public function is_inprocess( $args = array() ) {
			$r = bp_parse_args(
				$args,
				array(
					'group' => 'bb_pro_migrate_reactions',
				)
			);

			$is_records = $this->fetch_job_records( $r );

			return ! empty( $is_records ) && 0 < count( $is_records );
		}

		/**
		 * Called when background process has completed.
		 *
		 * @since 2.4.50
		 */
		protected function completed() {
			if ( function_exists( 'bb_error_log' ) ) {
				// phpcs:ignore
				bb_error_log( 'Data update completed' );
			} else {
				// phpcs:ignore
				error_log( 'Data update completed' );
			}
			do_action( $this->identifier . '_completed' );

			$is_reaction_migration = (bool) bp_get_option( 'is_reaction_migration' );
			if ( $is_reaction_migration ) {
				bp_update_option( 'bb_pro_reaction_migration_completed', array(
					'success'     => 'yes',
					'expire_time' => time() + ( 7 * DAY_IN_SECONDS ),
				) );

				// Update status in existing migration data.
				if ( function_exists( 'bb_pro_reaction_get_migration_action' ) ) {
					$migration_data           = bb_pro_reaction_get_migration_action();
					$migration_data['status'] = 'completed';
					bb_pro_reaction_update_migration_action( $migration_data );
				}

				// Delete site-wise warning notice.
				bp_delete_option( 'bb_pro_reaction_migration_notice' );
			}

			//reset the variable.
			bp_delete_option( 'is_reaction_migration' );
		}

		/**
		 * Get batches.
		 *
		 * @since 2.4.50
		 *
		 * @param int $limit Number of batches to return, defaults to all.
		 *
		 * @return array of stdClass
		 */
		public function get_batches( $limit = 0 ) {
			global $wpdb;

			if ( empty( $limit ) || ! is_int( $limit ) ) {
				$limit = 0;
			}

			$table                = self::$table_name;
			$blog_id              = get_current_blog_id();
			$id                   = 'id';
			$group                = 'group';
			$type                 = 'type';
			$value_item           = 'data_id';
			$value_secondary_item = 'secondary_data_id';
			$value_column         = 'data';
			$priority             = 'priority';
			$db_blog_id           = 'blog_id';

			$sql = "
			SELECT *
			FROM {$table}
			WHERE blog_id = %d and `group` = 'bb_pro_migrate_reactions'
			ORDER BY priority, id ASC
			";

			$args[] = $blog_id;

			if ( ! empty( $limit ) ) {
				$sql .= ' LIMIT %d';

				$args[] = $limit;
			}

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared, WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
			$items = $wpdb->get_results( $wpdb->prepare( $sql, $args ) );

			$batches = array();

			if ( ! empty( $items ) ) {
				$batches = array_map(
					function ( $item ) use ( $id, $group, $type, $value_item, $value_secondary_item, $value_column, $priority, $db_blog_id ) {
						$batch               = new stdClass();
						$batch->key          = $item->{$id};
						$batch->group        = $item->{$group};
						$batch->type         = $item->{$type};
						$batch->item_id      = $item->{$value_item};
						$batch->secondary_id = $item->{$value_secondary_item};
						$batch->data         = maybe_unserialize( $item->{$value_column} );
						$batch->priority     = $item->{$priority};
						$batch->blog_id      = $item->{$db_blog_id};

						return $batch;
					},
					$items
				);
			}

			return $batches;
		}

		/**
		 * Delete all batches.
		 *
		 * @since 2.4.50
		 *
		 * @return BB_Background_Process
		 */
		public function delete_all_batches() {
			global $wpdb;

			$table   = self::$table_name;
			$blog_id = get_current_blog_id();

			$wpdb->query( "DELETE FROM {$table} WHERE `blog_id` = {$blog_id} AND `group` = 'bb_pro_migrate_reactions' AND `secondary_data_id` != 'delete'" ); // @codingStandardsIgnoreLine.

			return $this;
		}

	}
}

