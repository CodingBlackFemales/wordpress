<?php
if ( ! defined( 'ABSPATH' ) ) { exit;
}
/**
 * Stats Data For Charts
 *
 * @since 2.0.0
 */
class WPJMS_Stats_Data {

	/* Var */
	var $config;

	/**
	 * Constructor.
	 */
	public function __construct( $config = array() ) {

		/* Defaults */
		$days = absint( get_option( 'wp_job_manager_stats_default_stat_days', 7 ) );
		$defaults = array(
			'stat_ids'   => array(),
			'post_ids'   => array(),
			'date_from'  => date_i18n( 'Y-m-d', strtotime( '-' . ($days + 1) . 'days' ) ),
			'date_to'    => date_i18n( 'Y-m-d' ),
			'days'       => $days,
		);

		/* Set vars */
		$this->config = apply_filters( 'wpjm_stats_data_config', wp_parse_args( $config, $defaults ) );
	}

	/*
	 Functions
	------------------------------------------ */

	/**
	 * Get Posts Datas Formatted For Chart
	 */
	public function get_posts_data() {
		$data = array(
			'labels'   => $this->get_posts_labels(),
			'datasets' => $this->get_posts_datasets(),
		);
		return $data;
	}

	/**
	 * Get Single Post Datas Formatted For Chart
	 */
	public function get_post_data() {
		$data = array(
			'labels'   => $this->get_post_labels(),
			'datasets' => $this->get_post_datasets(),
		);
		return $data;
	}
	/*
	 Utility
	------------------------------------------ */

	/**
	 * Get Chart Dates (For Labels)
	 *
	 * @link http://stackoverflow.com/a/9225875
	 */
	public function get_posts_labels() {

		$dates = array();
		$date_from = strtotime( $this->config['date_from'] );
		$date_to = strtotime( $this->config['date_to'] );

		while ( $date_from <= $date_to ) {
			$key = date_i18n( 'Y-m-d', $date_from );
			$dates[ $key ] = date_i18n( get_option( 'date_format' ), $date_from );
			$date_from = strtotime( '+1 day', $date_from );
		}

		return $dates;
	}

	/**
	 * Get Chart Datasets
	 */
	public function get_posts_datasets() {
		$stats    = $this->get_raw_stats();
		$dates    = $this->get_posts_labels();
		$datasets = array();

		/* Add post_id as key */
		$stat_datas = array();

		foreach ( $stats as $stat ) {
			$stat_datas[ $stat->post_id ][] = $stat;
		}

		/* Loop each post */
		foreach ( $stat_datas as $post_id => $stats ) {
			$title = get_the_title( $post_id );

			if ( ! $title ) {
				continue;
			}

			/* Add dataset */
			$datasets[ $post_id ] = array(
				'label' => "#{$post_id} {$title}",
				'data'  => array(),
			);

			/* Add each date to the dataset */
			foreach ( $dates as $date => $date_label ) {
				$datasets[ $post_id ]['data'][ $date ] = 0;
			}

			/* Fill in stats for existing dates */
			foreach ( $stats as $stat ) {
				if ( isset( $datasets[ $post_id ]['data'][ $stat->stat_date ] ) ) {
					$datasets[ $post_id ]['data'][ $stat->stat_date ] = $stat->stat_value;
				}
			}

			$datasets[ $post_id ]['data'] = array_values( $datasets[ $post_id ]['data'] );
		}

		return $datasets;
	}

	/**
	 * Get Chart Dates (For Labels)
	 *
	 * @link http://stackoverflow.com/a/9225875
	 */
	public function get_post_labels() {
		return $this->get_posts_labels();
	}

	/**
	 * Get Chart Datasets
	 */
	public function get_post_datasets() {
		$stats    = $this->get_raw_stats();
		$dates    = $this->get_posts_labels();
		$datasets = array();

		/* Add post_id as key */
		$stat_datas = array();

		foreach ( $stats as $stat ) {
			$stat_datas[ $stat->stat_id ][] = $stat;
		}

		/* Loop each post */
		foreach ( $stat_datas as $stat_id => $stat_data ) {
			$stats = wpjms_stats();
			$stat_ids = wpjms_stat_ids();

			if ( ! in_array( $stat_id, $stat_ids ) ) {
				continue;
			}

			/* Add dataset */
			$datasets[ $stat_id ] = array(
				'label' => wpjms_stat_label( $stat_id ),
				'data'  => array(),
			);

			/* Add each date to the dataset */
			foreach ( $dates as $date => $date_label ) {
				$datasets[ $stat_id ]['data'][ $date ] = 0;
			}

			/* Fill in stats for existing dates */
			foreach ( $stat_data as $stat ) {
				if ( isset( $datasets[ $stat_id ]['data'][ $stat->stat_date ] ) ) {
					$datasets[ $stat_id ]['data'][ $stat->stat_date ] = $stat->stat_value;
				}
			}

			$datasets[ $stat_id ]['data'] = array_values( $datasets[ $stat_id ]['data'] );
		}

		return $datasets;
	}
	/*
	 Raw
	------------------------------------------ */

	/**
	 * Query Stats Data From Database in Simple Array
	 */
	public function get_raw_stats() {
		global $wpdb;
		$args = $this->config;

		/* Filter */
		$data = apply_filters( 'wpjms_stats_data_pre_raw_stats', array(), $args );

		if ( ! $data ) {

			/* SQL */
			$where = array();
			if ( $args['stat_ids'] ) {
				$where[] = "AND stat_id IN ( '" . implode( "','", $args['stat_ids'] ) . "' )";
			}
			if ( $args['post_ids'] ) {
				$where[] = "AND post_id IN ( '" . implode( "','", $args['post_ids'] ) . "' )";
			}
			if ( $args['date_from'] && $args['date_to'] ) {
				$where[] = $wpdb->prepare( 'AND stat_date between %s and %s', $args['date_from'], $args['date_to'] );
			}
			$where = implode( ' ', $where );

			$data = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}job_manager_stats WHERE 1=1 {$where}" );
		}

		return apply_filters( 'wpjms_stats_data_raw_stats', $data, $args );
	}

	/**
	 * Get Post ID Stat by Stat ID
	 */
	public function get_all_stats() {
		global $wpdb;

		/* Bail if no Post ID specified */
		$args = $this->config;
		if ( ! isset( $args['post_ids'][0] ) ) { return array();
		}

		/* POst ID */
		$post_id = intval( $args['post_ids'][0] );

		/* Get Database */
		$rows = $wpdb->get_results( $wpdb->prepare( "SELECT stat_id, SUM(stat_value) as stat_total FROM {$wpdb->prefix}job_manager_stats WHERE post_id = %s GROUP BY stat_id, post_id", $post_id ) );

		/* Format Data */
		$data = array();
		foreach ( $rows as $row ) {
			$data[ $row->stat_id ] = $row->stat_total;
		}
		return $data;
	}

}
