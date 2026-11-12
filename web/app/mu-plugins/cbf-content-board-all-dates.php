<?php
/**
 * Show posts of any date on the PublishPress Content Board.
 *
 * The Content Board only lists posts whose publish date falls in its date
 * range, which defaults to five weeks either side of today and applies even
 * when no date filter is set. The board tracks workflow status, and a
 * session's publish date is when its post was created, so older sessions in
 * Pending Review never appear, and newer ones drop off as the range moves.
 *
 * The board passes its query args and the active date range through
 * PP_Content_Board_posts_query_args, then adds its date condition in a
 * posts_where filter. This tags the board's query with that range and removes
 * the matching condition afterwards. If PublishPress changes how it writes the
 * condition, nothing matches and the board keeps filtering by date as before.
 *
 * @package CBF
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

add_filter(
	'PP_Content_Board_posts_query_args',
	static function ( $args, $context = '', $enabled_filters = array(), $user_filters = array() ) {
		if ( is_array( $args ) && ! empty( $user_filters['start_date'] ) && ! empty( $user_filters['end_date'] ) ) {
			$args['cbf_content_board_range'] = array( $user_filters['start_date'], $user_filters['end_date'] );
		}

		return $args;
	},
	10,
	4
);

add_filter(
	'posts_where',
	static function ( $where, $query ) {
		global $wpdb;

		$range = $query instanceof WP_Query ? $query->get( 'cbf_content_board_range' ) : null;

		if ( ! is_array( $range ) || count( $range ) !== 2 ) {
			return $where;
		}

		// The same condition PublishPress builds in posts_where_range().
		$condition = $wpdb->prepare(
			" AND ($wpdb->posts.post_date >= %s AND $wpdb->posts.post_date < %s)",
			gmdate( 'Y-m-d', strtotime( $range[0] ) ) . ' 00:00:00',
			gmdate( 'Y-m-d', strtotime( $range[1] ) ) . ' 23:59:59'
		);

		return str_replace( $condition, '', $where );
	},
	// After PublishPress's own posts_where filter, at the default priority.
	11,
	2
);
