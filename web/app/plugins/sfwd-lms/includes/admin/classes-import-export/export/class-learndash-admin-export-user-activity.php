<?php
/**
 * LearnDash Admin Export User Activity.
 *
 * @since 4.3.0
 *
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if (
	class_exists( 'Learndash_Admin_Export_Chunkable' ) &&
	trait_exists( 'Learndash_Admin_Import_Export_User_Activity' ) &&
	! class_exists( 'Learndash_Admin_Export_User_Activity' )
) {
	/**
	 * Class LearnDash Admin Export User Activity.
	 *
	 * @since 4.3.0
	 */
	class Learndash_Admin_Export_User_Activity extends Learndash_Admin_Export_Chunkable {
		use Learndash_Admin_Import_Export_User_Activity;

		/**
		 * Returns data to export by chunks.
		 *
		 * @since 4.3.0
		 *
		 * @return string
		 */
		public function get_data(): string {
			global $wpdb;

			$table_name = esc_sql(
				LDLMS_DB::get_table_name( 'user_activity' )
			);

			$sql = $wpdb->prepare(
				'SELECT * FROM ' . $table_name . ' ORDER BY activity_id ASC LIMIT %d OFFSET %d', // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
				$this->get_chunk_size_rows(),
				$this->offset_rows
			);

			// phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
			$user_activities = $wpdb->get_results( $sql, ARRAY_A );

			if ( empty( $user_activities ) ) {
				return '';
			}

			$result = '';

			foreach ( $user_activities as $user_activity ) {
				$user_activity['activity_meta'] = learndash_get_activity_meta_fields( $user_activity['activity_id'] );
				unset( $user_activity['activity_id'] );

				/**
				 * Filters the user activity object to export.
				 *
				 * @since 4.3.0
				 *
				 * @param array $data User activity object.
				 *
				 * @return array User activity object.
				 */
				$user_activity = apply_filters( 'learndash_export_user_activity_object', $user_activity );

				$result .= wp_json_encode( $user_activity ) . PHP_EOL;
			}

			$this->increment_offset_rows();

			return $result;
		}
	}
}
