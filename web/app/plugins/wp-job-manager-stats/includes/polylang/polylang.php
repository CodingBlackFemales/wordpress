<?php
if ( ! defined( 'ABSPATH' ) ) { exit;
}

/* Load Class */
WPJMS_Polylang::get_instance();

/**
 * Polylang Compatibility
 *
 * @since 2.3.0
 */
class WPJMS_Polylang {

	/**
	 * Returns the instance.
	 */
	public static function get_instance() {
		static $instance = null;
		if ( is_null( $instance ) ) { $instance = new self;
		}
		return $instance;
	}

	/**
	 * Constructor.
	 */
	public function __construct() {

		/* Filter raw stats before it's even loaded */
		add_filter( 'wpjms_stats_data_pre_raw_stats', array( $this, 'get_raw_translations_stats' ), 10, 2 );
	}


	/**
	 * Raw Stats Filters
	 *
	 * @since 2.3.0
	 * @param array $datas raw stats datas.
	 * @param array $args stats config
	 */
	public function get_raw_translations_stats( $datas, $args ) {

		/* Post Ids */
		$post_ids = $args['post_ids'];

		/* Get multi dimentional posts of translations */
		$trans_posts = $this->get_translation_posts( $post_ids );

		/* Get all posts including all translations in simple array */
		$trans_ids = $this->get_translation_posts_ids( $trans_posts );

		/* Get raw stats data of all posts including translations */
		$raw_datas = $this->get_all_raw_datas( $trans_ids, $args );

		/* Merge translations stats datas with original datas */
		$new_datas = $this->merge_datas( $raw_datas, $trans_posts );
		return $new_datas;

	}

	/**
	 * Merge translation stats data with original datas
	 *
	 * @since 2.3.0
	 * @link https://stackoverflow.com/questions/42967923/
	 * @param array $data all raw stats data, including all translations entries
	 * @param array $group all translations posts grouped by original post.
	 */
	public function merge_datas( $data, $groups ) {

		/* If no translations group, bail. */
		if ( empty( $groups ) === true ) {
			return $data;
		}

		/* Transform groups into a more useful format */
		$transformed_groups = array();
		foreach ( $groups as $post_id => $aliases ) {
			foreach ( $aliases as $alias ) {
				if ( absint( $post_id ) === absint( $alias ) ) {
					continue;
				}

				$transformed_groups[ absint( $alias ) ] = $post_id;
			}
		}

		/* Replace aliases with the real post id */
		foreach ( $data as $index => $stat ) {
			if ( isset( $transformed_groups[ absint( $stat->post_id ) ] ) === false ) {
				continue;
			}

			$data[ $index ]->post_id = $transformed_groups[ absint( $stat->post_id ) ];
		}

		/* Go through stats and merge those with the same post_id, stat_id, and stat_date */
		$merged_stats = array();
		$index_tracker = 0;
		$stats_hash = array();

		foreach ( $data as $index => $stat ) {
			$hash_key = sprintf(
				'%s-%s-%s',
				$stat->post_id,
				$stat->stat_id,
				$stat->stat_date
			);
			if ( isset( $stats_hash[ $hash_key ] ) === true ) {
				$merged_stats[ $stats_hash[ $hash_key ] ]->stat_value += absint( $stat->stat_value );
				continue;
			}

			$merged_stats[] = $stat;
			$stats_hash[ $hash_key ] = $index_tracker;
			$index_tracker++;
		}

		return $merged_stats;
	}

	/**
	 * Get Translation Posts
	 * Grouped By Main Posts Ids
	 *
	 * @since 2.3.0
	 * @param array $post_ids
	 */
	public function get_translation_posts( $post_ids ) {
		$trans_posts = array();
		foreach ( $post_ids as $pid ) {
			$trans_posts[ $pid ] = pll_get_post_translations( $pid );
		}
		return $trans_posts;
	}

	/**
	 * Get Translation Posts Ids In Simple Array
	 *
	 * @since 2.3.0
	 * @param array $trans_posts translations entry grouped by main posts
	 * @return array simple array of all posts and translations posts
	 */
	public function get_translation_posts_ids( $trans_posts ) {
		$trans_ids = array();
		foreach ( $trans_posts as $tids ) {
			foreach ( $tids as $id ) {
				$trans_ids[] = $id;
			}
		}
		return $trans_ids;
	}


	/**
	 * Get raw data from DB for all posts and translation datas.
	 * This data is formatted the same way as the WPJMS_Stats_Data -> get_raw_stats()
	 *
	 * @since 2.3.0
	 * @param array              $post_ids all posts ids
	 * @param array stats config
	 * @return array of stats object
	 */
	public function get_all_raw_datas( $post_ids, $args ) {
		global $wpdb;
		$where = array();
		if ( $args['stat_ids'] ) {
			$where[] = "AND stat_id IN ( '" . implode( "','", $args['stat_ids'] ) . "' )";
		}
		if ( $post_ids ) {
			$where[] = "AND post_id IN ( '" . implode( "','", $post_ids ) . "' )";
		}
		if ( $args['date_from'] && $args['date_to'] ) {
			$where[] = $wpdb->prepare( 'AND stat_date between %s and %s', $args['date_from'], $args['date_to'] );
		}
		$where = implode( ' ', $where );

		$data = $wpdb->get_results( "SELECT * FROM {$wpdb->prefix}job_manager_stats WHERE 1=1 {$where}" );
		return $data;
	}

}

