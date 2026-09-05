<?php
/**
 * Database access for bulk migration batches.
 *
 * Kept apart from the orchestration so BatchRunner reads as a sequence of
 * decisions rather than a sequence of queries.
 *
 * @class   Bulk\BatchRepository
 * @version 1.0.0
 * @package CodingBlackFemales/SlidesImporter
 */

namespace CodingBlackFemales\SlidesImporter\Bulk;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * BatchRepository class.
 */
final class BatchRepository {

	/** Batch lifecycle states. */
	const STATUS_VALIDATING   = 'validating';
	const STATUS_AWAITING     = 'awaiting_confirmation';
	const STATUS_RUNNING      = 'running';
	const STATUS_DONE         = 'done';
	const STATUS_WITH_ERRORS  = 'completed_with_errors';
	const STATUS_FAILED       = 'failed';
	const STATUS_CANCELLED    = 'cancelled';

	/** States from which no further work will be scheduled. */
	const TERMINAL = array( self::STATUS_DONE, self::STATUS_WITH_ERRORS, self::STATUS_FAILED, self::STATUS_CANCELLED );


	/** Fully-qualified batches table name. */
	public static function table(): string {
		global $wpdb;
		return $wpdb->prefix . 'cbf_slide_import_batches';
	}


	/**
	 * Create a batch.
	 *
	 * @param  array $data Column values.
	 * @return int New batch ID, or 0 on failure.
	 */
	public static function create( array $data ): int {
		global $wpdb;

		$defaults = array(
			'course_id' => 0,
			'overwrite' => 0,
			'csv_name'  => '',
			'row_count' => 0,
			'status'    => self::STATUS_VALIDATING,
		);
		$data     = array_merge( $defaults, array_intersect_key( $data, $defaults ) );

		$row = array(
			'blog_id'   => get_current_blog_id(),
			'user_id'   => get_current_user_id(),
			'course_id' => (int) $data['course_id'],
			'overwrite' => empty( $data['overwrite'] ) ? 0 : 1,
			'csv_name'  => (string) $data['csv_name'],
			'row_count' => (int) $data['row_count'],
			'status'    => (string) $data['status'],
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery
		$inserted = $wpdb->insert( self::table(), $row, array( '%d', '%d', '%d', '%d', '%s', '%d', '%s' ) );

		return $inserted === false ? 0 : (int) $wpdb->insert_id;
	}


	/**
	 * Fetch a batch owned by the current user on the current site.
	 *
	 * @param  int $batch_id Batch ID.
	 * @return array|null
	 */
	public static function find_owned( int $batch_id ): ?array {
		global $wpdb;
		$table = self::table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare(
				"SELECT * FROM {$table} WHERE id = %d AND user_id = %d AND blog_id = %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				$batch_id,
				get_current_user_id(),
				get_current_blog_id()
			),
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}


	/**
	 * Fetch a batch without an ownership check, for background work.
	 *
	 * @param  int $batch_id Batch ID.
	 * @return array|null
	 */
	public static function find( int $batch_id ): ?array {
		global $wpdb;
		$table = self::table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$row = $wpdb->get_row(
			$wpdb->prepare( "SELECT * FROM {$table} WHERE id = %d", $batch_id ), // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			ARRAY_A
		);

		return is_array( $row ) ? $row : null;
	}


	/**
	 * List the current user's batches, newest first.
	 *
	 * @param  int $limit Maximum rows.
	 * @return array
	 */
	public static function list_owned( int $limit = 20 ): array {
		global $wpdb;
		$table = self::table();

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$rows = $wpdb->get_results(
			$wpdb->prepare(
				"SELECT id, course_id, overwrite, csv_name, row_count, status, created_at, updated_at
				 FROM {$table} WHERE blog_id = %d AND user_id = %d ORDER BY id DESC LIMIT %d", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
				get_current_blog_id(),
				get_current_user_id(),
				$limit
			),
			ARRAY_A
		);

		return is_array( $rows ) ? $rows : array();
	}


	/**
	 * Update a batch's columns.
	 *
	 * @param int   $batch_id Batch ID.
	 * @param array $data     Column values; `plan` and `report` are JSON-encoded here.
	 */
	public static function update( int $batch_id, array $data ): void {
		global $wpdb;

		$values = array_merge(
			self::cast( $data, array( 'status', 'csv_name', 'error_message' ), 'strval' ),
			self::cast( $data, array( 'course_id', 'overwrite', 'row_count' ), 'intval' ),
			self::cast( $data, array( 'plan', 'report' ), 'wp_json_encode' )
		);

		if ( $values === array() ) {
			return;
		}

		$formats = array_map(
			static fn( $value ): string => is_int( $value ) ? '%d' : '%s',
			$values
		);

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->update( self::table(), $values, array( 'id' => $batch_id ), array_values( $formats ), array( '%d' ) );
	}


	/**
	 * Pull the named keys out of an update payload, cast for storage.
	 *
	 * @param  array    $data Update payload.
	 * @param  string[] $keys Keys to take.
	 * @param  callable $cast Cast applied to each value.
	 * @return array<string, mixed>
	 */
	private static function cast( array $data, array $keys, callable $cast ): array {
		$values = array();

		foreach ( $keys as $key ) {
			if ( array_key_exists( $key, $data ) ) {
				$values[ $key ] = $cast( $data[ $key ] );
			}
		}

		return $values;
	}


	/**
	 * Decode a JSON column from a batch row.
	 *
	 * @param  array  $batch  Batch row.
	 * @param  string $column `plan` or `report`.
	 * @return array
	 */
	public static function decode( array $batch, string $column ): array {
		$decoded = json_decode( (string) ( $batch[ $column ] ?? '' ), true );

		return is_array( $decoded ) ? $decoded : array();
	}


	/**
	 * Delete every batch belonging to a site, for uninstall.
	 *
	 * @param int $blog_id Site ID.
	 */
	public static function delete_for_blog( int $blog_id ): void {
		global $wpdb;

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching
		$wpdb->delete( self::table(), array( 'blog_id' => $blog_id ), array( '%d' ) );
	}
}
