<?php

namespace WPForms\Pro\Reports;

use DateTime;
use WPForms\Pro\AntiSpam\SpamEntry;
use WPForms\Admin\Helpers\Datepicker;
use WPForms\SmartTags\SmartTag\Date;

/**
 * Generate form submissions reports.
 *
 * @since 1.5.4
 */
class EntriesCount {

	/**
	 * Get entries count grouped by $param.
	 * Main point of entry to fetch form entry count data from DB.
	 *
	 * @since 1.5.4
	 *
	 * @param string $param        Could be 'date' or 'form'.
	 * @param int    $form_id      Form ID to fetch the data for.
	 * @param int    $days         Timespan (in days) to fetch the data for.
	 * @param string $date_end_str End date of the timespan (PHP DateTime supported string, see http://php.net/manual/en/datetime.formats.php).
	 *
	 * @return array
	 */
	public function get_by( $param, $form_id = 0, $days = 0, $date_end_str = 'yesterday' ) {

		// Validate the $param value.
		if ( ! in_array( $param, [ 'date', 'form', 'form_trends' ], true ) ) {
			// Invalid $param, return early.
			return [];
		}

		// Attempt to create DateTime objects.
		$now            = date_create();
		$utc_date_end   = date_create( $date_end_str );
		$utc_date_start = date_create( $date_end_str );

		// Check if DateTime objects were created successfully.
		if ( ! $now || ! $utc_date_end || ! $utc_date_start ) {
			// If unsuccessful, return early.
			return [];
		}

		// Modify and set time for $utc_date_end.
		$modify_offset = (float) get_option( 'gmt_offset' ) * 60 . ' minutes';
		$now_time      = date_format( $now, 'H:i:s' );
		$utc_date_end  = date_modify( $utc_date_end, $now_time )
			->modify( $modify_offset )
			->setTime( 23, 59, 59 );

		// Modify and set time for $utc_date_start.
		$utc_date_start = date_modify( $utc_date_start, $now_time )
			->modify( $modify_offset )
			->modify( '-' . ( absint( $days ) - 1 ) . ' days' )
			->setTime( 0, 0 );

		// Return the result based on the $param value using a switch statement.
		switch ( $param ) {
			case 'date':
				// If $param is 'date', retrieve results by date.
				return $this->get_by_date_sql( $form_id, $utc_date_start, $utc_date_end );

			case 'form':
				// If $param is 'form', retrieve results by form.
				return $this->get_by_form_sql( $form_id, $utc_date_start, $utc_date_end );

			case 'form_trends':
				// If $param is 'form_trends', retrieve results by form trends.
				return $this->get_by_form_trends_sql( $form_id, $utc_date_start, $utc_date_end );

			default:
				// Return an empty array if $param is not valid.
				return [];
		}
	}

	/**
	 * Get entries count grouped by date.
	 * In most cases it's better to use `get_by( 'date' )` instead.
	 *
	 * Warning! Avoid GTM offsets: we are searching with offset by default.
	 *
	 * @since 1.5.4
	 * @since 1.6.5 Fixed GTM offset.
	 * @since 1.7.6 Count entries only for published forms.
	 *
	 * @param int           $form_id        Form ID to fetch the data for.
	 * @param DateTime|null $utc_date_start Start date for the search. Leave it empty to restrict the starting day.
	 * @param DateTime|null $utc_date_end   End date for the search. Leave it empty to restrict the ending day.
	 *
	 * @return array
	 */
	public function get_by_date_sql( $form_id = 0, $utc_date_start = null, $utc_date_end = null ) {

		global $wpdb;

		$table_name = wpforms()->obj( 'entry' )->table_name;
		$forms      = $this->get_allowed_forms( $form_id );

		$access_obj = wpforms()->obj( 'access' );

		if ( $access_obj ) {
			$forms = $access_obj->filter_forms_by_current_user_capability( $forms, 'view_entries_form_single' );
		}

		if ( empty( $forms ) ) {
			return [];
		}

		$sql = $wpdb->prepare(
			"SELECT CAST(DATE_ADD(date, INTERVAL %d MINUTE) AS DATE) as day, COUNT( entry_id ) as count FROM $table_name", // phpcs:ignore WordPress.DB.PreparedSQL.InterpolatedNotPrepared
			(float) get_option( 'gmt_offset' ) * 60
		);

		$sql .= $this->prepare_where_conditions( $forms, $utc_date_start, $utc_date_end );
		$sql .= ' GROUP BY day ORDER BY day;';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		return (array) $wpdb->get_results( $sql, OBJECT_K );
	}

	/**
	 * Get entries count grouped by form.
	 * In most cases it's better to use `get_by( 'form' )` instead.
	 *
	 * Warning! Avoid GTM offsets! We are searching with offset by default.
	 *
	 * @since 1.5.4
	 * @since 1.6.5 Fixed GTM offset.
	 * @since 1.7.6 Count entries only for published forms.
	 *
	 * @param int           $form_id        Form ID to fetch the data for.
	 * @param DateTime|null $utc_date_start Start date for the search. Leave it empty to restrict the starting day.
	 * @param DateTime|null $utc_date_end   End date for the search. Leave it empty to restrict the ending day.
	 *
	 * @return array
	 */
	public function get_by_form_sql( $form_id = 0, $utc_date_start = null, $utc_date_end = null ) {

		global $wpdb;

		$table_name = wpforms()->obj( 'entry' )->table_name;
		$forms      = $this->get_allowed_forms( $form_id );

		if ( empty( $forms ) ) {
			return [];
		}

		$sql = "SELECT form_id, COUNT( entry_id ) as count FROM $table_name";

		$sql .= $this->prepare_where_conditions( $forms, $utc_date_start, $utc_date_end );
		$sql .= ' GROUP BY form_id ORDER BY count DESC;';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$results = (array) $wpdb->get_results( $sql, OBJECT_K );

		return $this->fill_forms_list_form_data( $results );
	}

	/**
	 * Get entries count grouped by form entries trends.
	 *
	 * @since 1.8.8
	 *
	 * @param int           $form_id        Form ID to fetch the data for.
	 * @param DateTime|null $utc_date_start Start date for the search. Leave it empty to restrict the starting day.
	 * @param DateTime|null $utc_date_end   End date for the search. Leave it empty to restrict the ending day.
	 *
	 * @return array
	 */
	private function get_by_form_trends_sql( $form_id = 0, $utc_date_start = null, $utc_date_end = null ) {

		// If the time period is not a date object, return an empty array.
		if ( ! ( $utc_date_start instanceof DateTime ) || ! ( $utc_date_end instanceof DateTime ) ) {
			return [];
		}

		// Get allowed forms based on the provided form_id.
		$forms = $this->get_allowed_forms( $form_id );

		if ( empty( $forms ) ) {
			return [];
		}

		// Convert DateTime objects to DateTimeImmutable for consistency.
		$utc_date_start_immutable = date_create_immutable_from_format( Datepicker::DATETIME_FORMAT, $utc_date_start->format( Datepicker::DATETIME_FORMAT ) );
		$utc_date_end_immutable   = date_create_immutable_from_format( Datepicker::DATETIME_FORMAT, $utc_date_end->format( Datepicker::DATETIME_FORMAT ) );

		// Get the previous week's start and end dates.
		$prev_utc_dates = Datepicker::get_prev_timespan_dates( $utc_date_start_immutable, $utc_date_end_immutable, 7 );

		if ( ! $prev_utc_dates ) {
			return [];
		}

		global $wpdb;

		list( $prev_utc_date_start_immutable, $prev_utc_date_end_immutable ) = $prev_utc_dates;

		$table_name = wpforms()->obj( 'entry' )->table_name;

		// Build the SQL query.
		// ! Note that extra spaces are added for readability purposes and are removed before the query is executed.
		$query   = [];
		$query[] = 'WITH WeeklyCounts AS (';
		$query[] = '    SELECT';
		$query[] = '        form_id,';
		$query[] = '        SUM(count_current_week) AS count,';
		$query[] = '        SUM(count_previous_week) AS count_previous_week,';
		$query[] = '        CASE';
		$query[] = '            WHEN SUM(count_previous_week) = 0 THEN 100';
		$query[] = '            WHEN SUM(count_current_week) = 0 THEN -100';
		$query[] = '            WHEN SUM(count_current_week) = SUM(count_previous_week) THEN 0';
		$query[] = '            ELSE ROUND(((SUM(count_current_week) - SUM(count_previous_week)) / NULLIF(SUM(count_previous_week), 1)) * 100)';
		$query[] = '        END AS trends';
		$query[] = '    FROM (';
		$query[] = '        SELECT';
		$query[] = '            form_id,';
		$query[] = '            COUNT(entry_id) AS count_current_week,';
		$query[] = '            0 AS count_previous_week';
		$query[] = "        FROM {$table_name}";
		$query[] = $this->prepare_where_conditions( $forms, $utc_date_start_immutable, $utc_date_end_immutable );
		$query[] = '        GROUP BY form_id';
		$query[] = '        UNION ALL';
		$query[] = '        SELECT';
		$query[] = '            form_id,';
		$query[] = '            0 AS count_current_week,';
		$query[] = '            COUNT(entry_id) AS count_previous_week';
		$query[] = "        FROM {$table_name}";
		$query[] = $this->prepare_where_conditions( $forms, $prev_utc_date_start_immutable, $prev_utc_date_end_immutable );
		$query[] = '        GROUP BY form_id';
		$query[] = '    ) AS WeeklyData';
		$query[] = '    GROUP BY form_id';
		$query[] = ')';
		$query[] = 'SELECT * FROM WeeklyCounts ORDER BY count DESC;';

		// phpcs:ignore WordPress.DB.DirectDatabaseQuery.DirectQuery, WordPress.DB.DirectDatabaseQuery.NoCaching, WordPress.DB.PreparedSQL.NotPrepared
		$results = $wpdb->get_results( implode( ' ', $query ), OBJECT_K );

		// Get results.
		return $this->fill_forms_list_form_data( $results );
	}

	/**
	 * Fill a forms list with the data needed for a frontend display.
	 *
	 * @since 1.5.4
	 *
	 * @param array $results DB results from `$wpdb->prepare()`.
	 *
	 * @return array
	 */
	public function fill_forms_list_form_data( $results ) {

		if ( ! is_array( $results ) ) {
			return [];
		}

		$processed = [];

		foreach ( $results as $form_id => $result ) {
			$form = wpforms()->obj( 'form' )->get( $form_id );

			if ( empty( $form ) ) {
				continue;
			}

			$edit_url = add_query_arg(
				[
					'page'    => 'wpforms-entries',
					'view'    => 'list',
					'form_id' => absint( $form->ID ),
				],
				admin_url( 'admin.php' )
			);

			$processed[ $form->ID ] = [
				'form_id'  => $form->ID,
				'count'    => isset( $results[ $form->ID ]->count ) ? absint( $results[ $form->ID ]->count ) : 0,
				'title'    => $form->post_title,
				'edit_url' => $edit_url,
			];

			// If $results has the "count_previous_week" property or "trends" property, add them to the processed array.
			$processed = $this->maybe_fill_forms_list_extra_form_data( $form, $results, $processed );
		}

		return $processed;
	}

	/**
	 * Fill a form list with the data needed for a frontend display.
	 *
	 * @since 1.8.8
	 *
	 * @param object $form      Form object.
	 * @param array  $results   DB results from `$wpdb->prepare()`.
	 * @param array  $processed Processed results.
	 *
	 * @return array
	 */
	private function maybe_fill_forms_list_extra_form_data( $form, $results, $processed ) {

		// If $results has the "count_previous_week" property, add it to the processed array.
		if ( isset( $results[ $form->ID ]->count_previous_week ) ) {
			$processed[ $form->ID ]['count_previous_week'] = absint( $results[ $form->ID ]->count_previous_week );
		}

		// If $results has the "trends" property, add it to the processed array.
		if ( isset( $results[ $form->ID ]->trends ) ) {
			// Cast the value to an integer, maintaining the sign.
			$processed[ $form->ID ]['trends'] = (int) $results[ $form->ID ]->trends;
		}

		return $processed;
	}

	/**
	 * Prepare where conditions.
	 *
	 * @since 1.6.5
	 * @since 1.7.6 Changed $form_id argument to the $forms.
	 *
	 * @param array         $forms          List of form IDs.
	 * @param DateTime|null $utc_date_start Start date for the search. Leave it empty to restrict the starting day.
	 * @param DateTime|null $utc_date_end   End date for the search. Leave it empty to restrict the ending day.
	 *
	 * @return string
	 */
	private function prepare_where_conditions( $forms = [], $utc_date_start = null, $utc_date_end = null ) {

		global $wpdb;

		$format        = 'Y-m-d H:i:s';
		$placeholders  = $forms;
		$sql           = ' WHERE form_id IN ( ' . implode( ', ', array_fill( 0, count( $forms ), '%d' ) ) . ' )';
		$modify_offset = (float) get_option( 'gmt_offset' ) * 60 . ' minutes';

		if ( $utc_date_start !== null ) {
			$sql .= ' AND date >= %s';

			$utc_date_start = clone $utc_date_start;

			$utc_date_start->modify( $modify_offset );

			$placeholders[] = $utc_date_start->format( $format );
		}

		if ( $utc_date_end !== null ) {
			$sql .= ' AND date <= %s';

			$utc_date_end = clone $utc_date_end;

			$utc_date_end->modify( $modify_offset );

			$placeholders[] = $utc_date_end->format( $format );
		}

		// Exclude spam entries.
		$sql           .= ' AND status NOT IN ( %s, %s )';
		$placeholders[] = SpamEntry::ENTRY_STATUS;
		$placeholders[] = 'trash';

		return $wpdb->prepare( $sql, $placeholders ); // phpcs:ignore WordPress.DB.PreparedSQL.NotPrepared
	}

	/**
	 * Get list of forms with needed access control capabilities and published post status.
	 *
	 * @since 1.7.6
	 *
	 * @param int $form_id Form ID.
	 *
	 * @return array
	 */
	private function get_allowed_forms( $form_id = 0 ) {

		if ( $form_id ) {
			return wpforms()->obj( 'form' )->get( $form_id ) && get_post_status( $form_id ) === 'publish' ? [ $form_id ] : [];
		}

		return wpforms()->obj( 'form' )->get( '', [ 'fields' => 'ids' ] );
	}
}
