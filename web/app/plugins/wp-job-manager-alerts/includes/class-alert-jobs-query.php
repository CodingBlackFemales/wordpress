<?php
/**
 * Query jobs matching an alert.
 *
 * @package wp-job-manager-alerts
 */

namespace WP_Job_Manager_Alerts;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * Query jobs matching an alert.
 *
 * @internal Use `Alert::get_matching_jobs()`.
 *
 * @since 3.0.0
 */
class Alert_Jobs_Query {

	/**
	 * Alert object.
	 *
	 * @var Alert
	 */
	private Alert $alert;

	/**
	 * Construct alert query object.
	 *
	 * @param Alert $alert
	 */
	public function __construct( Alert $alert ) {
		$this->alert = $alert;
	}

	/**
	 * Query jobs matching an alert.
	 *
	 * @param Alert $alert Alert object.
	 * @param bool  $force Ignore alert frequency and cache.
	 *
	 * @return \WP_Query
	 */
	public static function get_matching_jobs( Alert $alert, bool $force = false ) {
		$query = new self( $alert );

		return $query->query_jobs( $force );
	}

	/**
	 * Get jobs matching the alert.
	 *
	 * @param bool $force Ignore alert frequency and cache.
	 *
	 * @return \WP_Query
	 */
	public function query_jobs( $force = false ) {

		$id   = $this->alert->ID;
		$post = $this->alert->get_post();

		if ( ! $force ) {
			add_filter( 'posts_where', [ $this, 'filter_alert_frequency' ] );
			add_filter( 'get_job_listings_cache_results', '__return_false' );
		}

		$search_terms = Post_Types::get_alert_search_terms( $id );

		$job_types  = Post_Types::get_terms( $search_terms['types'], 'slugs' );
		$job_tags   = Post_Types::get_terms( $search_terms['tags'], 'slugs' );
		$categories = ! empty( $search_terms['categories'] ) ? array_map( 'absint', $search_terms['categories'] ) : '';
		$regions    = ! empty( $search_terms['regions'] ) ? array_map( 'absint', $search_terms['regions'] ) : [];

		$jobs = get_job_listings(
		/**
		 * Filter the arguments used to query job listings for a job alert.
		 *
		 * @param array $args Arguments for get_job_listings.
		 */
			apply_filters(
				'job_manager_alerts_get_job_listings_args',
				[
					'search_location'   => $post->alert_location,
					'search_keywords'   => $post->alert_keyword,
					'search_categories' => $categories,
					'search_region'     => $regions,
					'search_tags'       => $job_tags,
					'job_types'         => ! empty( $job_types ) ? $job_types : '',
					'orderby'           => 'date',
					'order'             => 'desc',
					'offset'            => 0,
					'posts_per_page'    => 50,
				],
				$post
			)
		);

		$jobs->get_posts();

		remove_filter( 'posts_where', [ $this, 'filter_alert_frequency' ] );
		remove_filter( 'get_job_listings_cache_results', '__return_false' );

		return $jobs;
	}

	/**
	 * Append filtering by date based on alert frequency to the WHERE clause of the query.
	 *
	 * @param string $where
	 *
	 * @access private
	 *
	 * @return string
	 */
	public function filter_alert_frequency( $where = '' ) {
		$schedule = $this->alert->get_schedule();

		$interval = $schedule['interval'] ?? DAY_IN_SECONDS;

		// phpcs:ignore WordPress.DateTime.RestrictedFunctions -- Using local timezone.
		$where .= " AND post_date >= '" . date( 'Y-m-d', strtotime( '-' . absint( $interval ) . ' seconds' ) ) . "' ";

		return $where;
	}
}
