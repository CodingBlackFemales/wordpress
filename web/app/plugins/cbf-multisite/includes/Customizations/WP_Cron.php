<?php
/**
 * WP Cron integration
 *
 * @package     CodingBlackFemales/Multisite/Customizations
 * @version     1.0.0
 */

namespace CodingBlackFemales\Multisite\Customizations;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Custom WP Cron integration class.
 */
class WP_Cron {
	const EXPORT_EVENT_NAME = 'cbf_airtable_export';

	/**
	 * Export quiz activity from Learndash to Airtable.
	 */
	public static function export_quiz_activity() {
		// Only attempt if Learndash is installed
		if ( defined( 'LEARNDASH_VERSION' ) ) {
			error_log( 'Exported quiz results' );
			$results = LearnDash::get_results();
			Airtable::insert_quiz_activities( $results );
		}
	}

	/**
	 * Hook in methods.
	 */
	public static function hooks() {
		if ( defined( 'ENABLE_CBF_SCHEDULED_EXPORT' ) && ENABLE_CBF_SCHEDULED_EXPORT && get_current_blog_id() === intval( ACADEMY_SITE_ID ) ) {
			add_action( self::EXPORT_EVENT_NAME, array( __CLASS__, 'export_quiz_activity' ) );

			if ( ! wp_next_scheduled( self::EXPORT_EVENT_NAME ) ) {
				wp_schedule_event( strtotime( 'today 8:00am', time() ), 'daily', WP_Cron::EXPORT_EVENT_NAME );
			}
		}
	}
}
