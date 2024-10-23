<?php
/**
 * Airtable integration
 *
 * @package     CodingBlackFemales/Multisite/Customizations
 * @version     1.0.0
 */

// phpcs:disable PHPCompatibility.Classes.NewConstVisibility.Found
namespace CodingBlackFemales\Multisite\Customizations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use TANIOS\Airtable\Airtable as AirtableApi;

/**
 * Custom Airtable integration class.
 */
class Airtable {

	private static $api;

	protected static function get_api() {
		if ( self::$api === null ) {
			self::$api = new AirtableApi(
				array(
					'api_key' => AIRTABLE_API_KEY,
					'base'    => AIRTABLE_REPORTS_BASE,
				)
			);
		}

		return self::$api;
	}

	/**
	 * Gets the ID of the latest quiz activity published to Airtable.
	 *
	 * @return int $latest_activity_id;
	 */
	protected static function get_latest_quiz_activity() {
		$api = self::get_api();
		$params = array(
			'sort' => array(
				array(
					'field' => 'activity_id',
					'direction' => 'desc',
				),
			),
			'maxRecords' => 1,
			'pageSize' => 1,
		);
		$request = $api->getContent( AIRTABLE_REPORTS_TABLE, $params );
		$latest_activity_id = PHP_INT_MAX;
		$response = $request->getResponse();
		$results = $response['records'];

		if ( is_array( $results ) && count( $results ) > 0 ) {
			$latest_activity_id = $results[0]->fields->activity_id;
		}

		return $latest_activity_id;
	}

	/**
	 * Inserts a set of quiz activities in the Airtable reports table.
	 *
	 * @param array $activities The array of all quiz activities.
	 *
	 * @return array $inserted_records;
	 */
	public static function insert_quiz_activities( $activities ) {
		$api = self::get_api();
		$inserted_records = array();
		$latest_activity_id = self::get_latest_quiz_activity();

		// filter out activities that have already been inserted
		$new_activities = array_filter( $activities, fn( $activity ) => $activity['activity_id'] > $latest_activity_id );

		// convert into structure expected by API
		$new_activities = array_map(
			fn ( $result ) => array( 'fields' => $result ),
			$new_activities
		);

		// Batch insert activities to reduce number of API calls
		for ( $i = 0; $i < count( $new_activities ); $i += AIRTABLE_BATCH_SIZE ) {
			$sub_array = array_slice( $new_activities, $i, AIRTABLE_BATCH_SIZE );
			// Save to Airtable
			$records = $api->saveContent( AIRTABLE_REPORTS_TABLE, $sub_array )['records'];

			if ( isset( $records ) && is_array( $records ) ) {
				$inserted_records = array_merge( $inserted_records, $records );
			}
		}

		return $inserted_records;
	}
}
