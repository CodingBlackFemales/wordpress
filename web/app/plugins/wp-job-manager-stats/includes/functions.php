<?php
/**
 * Functions
 *
 * @since 2.0.0
 **/

/**
 * Stats Script
 *
 * Load Combined Stats JS if Debug is Disabled.
 *
 * @since 2.7.0
 */
function wpjms_stats_scripts() {

	// Only load in singular listing pages.
	if ( is_singular( 'job_listing' ) ) {

		// Single JS to track listings.
		wp_enqueue_script( 'wpjms-stats', WPJMS_URL . 'assets/stats/stats.min.js', array( 'wp-util', 'jquery' ), WPJMS_VERSION, true );
		$data = array(
			'post_id' => intval( get_queried_object_id() ),
			'stats'   => array_values( wp_list_pluck( wpjms_stats(), 'id' ) ),
			'isDebug' => defined( 'SCRIPT_DEBUG' ) && SCRIPT_DEBUG,
		);
		wp_localize_script( 'wpjms-stats', 'wpjmStats', $data );
	}
}
add_action( 'wp_enqueue_scripts', 'wpjms_stats_scripts' );

/**
 * Get Available Stats
 */
function wpjms_stats() {
	return apply_filters( 'wpjms_stats', array() );
}

/**
 * Get Default Stats
 */
function wpjms_stats_default() {
	return apply_filters( 'wpjms_stats_default', 'visits' );
}

/**
 * Get Stats IDs
 */
function wpjms_stat_ids() {
	$stat_ids = array();
	$stats = wpjms_stats();
	foreach ( $stats as $stat_id => $stat_data ) {
		$stat_ids[] = $stat_id;
	}
	return $stat_ids;
}

/**
 * Get Stat Label
 */
function wpjms_stat_label( $stat_id, $default = '' ) {
	$stats = wpjms_stats();
	$default = $default ? $default : __( 'Stats', 'wp-job-manager-stats' );
	return isset( $stats[ $stat_id ]['label'] ) ? $stats[ $stat_id ]['label'] : $default;
}

/**
 * Get Stat Page ID
 */
function wpjms_stat_page_id() {
	$page_id = get_option( 'wp_job_manager_stats_page_id' );

	// Translating stats page ID if WPML is active.
	$page_id = apply_filters( 'wpml_object_id', $page_id, 'page', true );

	if ( $page_id ) {
		return absint( function_exists( 'pll_get_post' ) ? pll_get_post( $page_id ) : $page_id );
	} else {
		return 0;
	}
}

/**
 * Get Job Stat URL
 * (Individual Stat For Job)
 */
function wpjms_job_stat_url( $post_id ) {
	$post = get_post( $post_id );
	$page_url = get_permalink( wpjms_stat_page_id() );
	if ( $page_url && isset( $post->post_type ) && 'job_listing' == $post->post_type ) {
		return esc_url( add_query_arg( 'job_id', $post_id, $page_url ) );
	}
	return false;
}

/*
 Update Stat Functions
------------------------------------------ */

/**
 * Get Stat.
 *
 * Get a statistic from the database. This is based on the Listing post ID,
 * the date, and statistic ID. When that combination does not exist, null will
 * be returned.
 *
 * @since 1.0.0
 *
 * @param    int    $post_id   ID of the listing post.
 * @param    string $date      Date of the statistic.
 * @param    string $stat_id   ID of the statistic. E.g 'views' or 'unique_views'.
 * @param    int    $value     Value of the statistic.
 * @return   int                  Returns the stat value when exists, 0 when it doesn't exist.
 */
function wpjms_get_stat_value( $post_id, $stat_id, $date = false ) {
	global $wpdb;

	/* Default */
	$date = ( false === $date ) ? date_i18n( 'Y-m-d' ) : $date;

	/* Get row data */
	$row = $wpdb->get_row( $wpdb->prepare( "SELECT stat_value FROM {$wpdb->prefix}job_manager_stats WHERE post_id = %s AND stat_date = %s AND stat_id = %s LIMIT 1", absint( $post_id ), date_i18n( 'Y-m-d', strtotime( $date ) ), sanitize_title( $stat_id ) ) );

	if ( is_object( $row ) && isset( $row->stat_value ) ) {
		return intval( $row->stat_value );
	}
	return 0; // default
}


/**
 * Add stat.
 *
 * Add a statistic in the database. This is based on the Listing post ID,
 * the date, and statistic ID. When that combination does exist, the existing value
 * will be updated.
 *
 * @since 1.0.0
 *
 * @param   int    $post_id   ID of the listing post.
 * @param   string $date      Date of the statistic, recommended format YYYY-MM-DD.
 * @param   int    $stat_id   ID of the statistic. E.g 'views' or 'unique_views'.
 * @param   mixed  $value     Value of the statistic. False to auto increment from previous value.
 * @return  mixed               Returns row effected when successfully added, or false when failed.
 */
function wpjms_add_stat_value( $post_id, $stat_id, $date = false, $value = false ) {
	global $wpdb;

	/* Check previous value */
	$old_value = wpjms_get_stat_value( $post_id, $stat_id, $date );

	/* Previous value exist, use update function. */
	if ( $old_value ) {
		return wpjms_update_stat_value( $post_id, $stat_id, $date, $value );
	}

	/* Default */
	$date = ( false === $date ) ? date_i18n( 'Y-m-d' ) : $date;
	$value = ( false === $value ) ? 1 : $value;

	/* Insert database row */
	$result = $wpdb->query( $wpdb->prepare( "INSERT INTO `{$wpdb->prefix}job_manager_stats` (`post_id`, `stat_date`, `stat_id`, `stat_value`) VALUES (%s, %s, %s, %s)", absint( $post_id ), date_i18n( 'Y-m-d', strtotime( $date ) ), sanitize_title( $stat_id ), intval( $value ) ) );

	return $result;
}

/**
 * Update stat.
 *
 * Update a statistic in the database. This is based on the Listing post ID,
 * the date, and statistic ID. When that combination does not exist, a new value will
 * be created.
 *
 * @since 1.0.0
 *
 * @param   int    $post_id   ID of the listing post.
 * @param   string $stat_id   ID of the statistic. E.g 'views' or 'unique_views'.
 * @param   string $date      Date of the statistic.
 * @param   int    $value     Value of the statistic.
 * @return  mixed             The number of rows updated, or false on error.
 */
function wpjms_update_stat_value( $post_id, $stat_id, $date = false, $value = false ) {
	global $wpdb;

	/* Check previous value */
	$old_value = wpjms_get_stat_value( $post_id, $stat_id, $date );

	/* Previous value don't exist, add it. */
	if ( ! $old_value ) {
		return wpjms_add_stat_value( $post_id, $stat_id, $date, $value );
	}

	/* Default */
	$date = ( false === $date ) ? date_i18n( 'Y-m-d' ) : $date;
	$value = ( false === $value ) ? $old_value + 1 : $value;

	/* Update database */
	$data = array(
		'stat_value' => intval( $value ),
	);
	$where = array(
		'post_id'    => absint( $post_id ),
		'stat_id'    => sanitize_title( $stat_id ),
		'stat_date'  => date_i18n( 'Y-m-d', strtotime( $date ) ),
	);
	$result = $wpdb->update( $wpdb->prefix . 'job_manager_stats', $data, $where );
	return $result;
}

/*
 Utility Functions
------------------------------------------ */

/**
 * Get stats total of a stat in a post.
 *
 * @since 2.4.0
 *
 * @param int    $post_id Post ID.
 * @param string $stat_id Stat ID.
 * @return int
 */
function wpjms_get_stats_total( $post_id, $stat_id ) {
	global $wpdb;
	$total = $wpdb->get_results( $wpdb->prepare( "SELECT SUM(stat_value) stat_value FROM {$wpdb->prefix}job_manager_stats WHERE post_id = %s AND stat_id = %s", absint( $post_id ), sanitize_title( $stat_id ) ), 'ARRAY_A' );
	if ( isset( $total[0]['stat_value'] ) ) {
		return intval( $total[0]['stat_value'] );
	}
	return 0;
}
