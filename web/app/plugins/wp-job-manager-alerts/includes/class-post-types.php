<?php
/**
 * Job Alerts post types.
 *
 * @package wp-job-manager-alerts
 */

namespace WP_Job_Manager_Alerts;

if ( ! defined( 'ABSPATH' ) ) {
	exit; // Exit if accessed directly
}

/**
 * WP_Job_Manager_Alerts\Post_Types class.
 */
class Post_Types {

	use Singleton;

	/**
	 * Post type name for job alerts.
	 *
	 * @var string
	 */
	const PT_ALERT = 'job_alert';

	/**
	 * Meta key for the alert frequency.
	 *
	 * @var string
	 */
	const META_FREQUENCY = 'alert_frequency';

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_post_types' ], 20 );
		add_filter( 'post_types_to_delete_with_user', [ $this, 'post_types_to_delete_with_user' ] );

	}

	/**
	 * Register 'job_alert' post type.
	 */
	public function register_post_types() {
		if ( post_type_exists( self::PT_ALERT ) ) {
			return;
		}

		register_post_type(
			self::PT_ALERT,
			apply_filters(
				'register_post_type_job_alert',
				[
					'public'              => false,
					'show_ui'             => true,
					'capability_type'     => 'post',
					'publicly_queryable'  => false,
					'exclude_from_search' => true,
					'hierarchical'        => false,
					'rewrite'             => false,
					'query_var'           => false,
					'show_in_menu'        => 'edit.php?post_type=job_listing',
					'show_in_admin_bar'   => false,
					'supports'            => [ 'author', 'title', 'custom-fields' ],
					'has_archive'         => false,
					'show_in_nav_menus'   => false,
					'delete_with_user'    => true,
					'label'               => __( 'Job Alerts', 'wp-job-manager-alerts' ),
					'labels'              => [
						'name'               => __( 'Job Alerts', 'wp-job-manager-alerts' ),
						'add_new'            => __( 'Add New', 'wp-job-manager-alerts' ),
						'add_new_item'       => __( 'Add Job Alert', 'wp-job-manager-alerts' ),
						'singular_name'      => __( 'Job Alert', 'wp-job-manager-alerts' ),
						'search_items'       => __( 'Search Alerts', 'wp-job-manager-alerts' ),
						'edit_item'          => __( 'Edit Alert', 'wp-job-manager-alerts' ),
						'new_item'           => __( 'New Alert', 'wp-job-manager-alerts' ),
						'all_items'          => __( 'Job Alerts', 'wp-job-manager-alerts' ),
						'view'               => __( 'View', 'wp-job-manager-alerts' ),
						'view_item'          => __( 'View Alert', 'wp-job-manager-alerts' ),
						'not_found'          => __( 'No alerts found', 'wp-job-manager-alerts' ),
						'not_found_in_trash' => __( 'No alerts found in Trash', 'wp-job-manager-alerts' ),
					],
					'capabilities'        => [
						'create_posts' => false,
						'edit_post'    => 'manage_job_listings',
						'delete_post'  => 'manage_job_listings',
					],
				]
			)
		);

		if ( taxonomy_exists( 'job_listing_category' ) ) {
			register_taxonomy_for_object_type( 'job_listing_category', self::PT_ALERT );
		}

		register_taxonomy_for_object_type( 'job_listing_type', self::PT_ALERT );
	}

	/**
	 * Filter post types to delete when removing a user to also remove
	 * `job_alert` posts.
	 *
	 * @access private
	 *
	 * @param string[] $post_types_to_delete Post types do delete.
	 *
	 * @return string[] Post types do delete.
	 */
	public function post_types_to_delete_with_user( $post_types_to_delete ) {
		$post_types_to_delete[] = self::PT_ALERT;

		return $post_types_to_delete;
	}

	/**
	 * Fetches the terms used in the search query for the alert.
	 *
	 * @since 1.5.0
	 *
	 * @param int $alert_id
	 *
	 * @return array
	 */
	public static function get_alert_search_terms( $alert_id ) {
		$base_terms = [
			'categories' => [],
			'regions'    => [],
			'tags'       => [],
			'types'      => [],
		];
		if ( metadata_exists( 'post', $alert_id, 'alert_search_terms' ) ) {
			return array_merge( $base_terms, get_metadata( 'post', $alert_id, 'alert_search_terms', true ) );
		}

		return array_merge( $base_terms, self::get_legacy_search_terms( $alert_id ) );
	}

	/**
	 * Fetches the terms, populated with display names, for the search query for the alert.
	 *
	 * @since 2.0.0
	 *
	 * @param int $alert_id
	 *
	 * @return array|array[]
	 */
	public static function get_alert_search_term_names( $alert_id ) {
		$terms = self::get_alert_search_terms( $alert_id );

		foreach ( $terms as $key => $term_ids ) {
			if ( ! empty( $term_ids ) ) {
				$term_names    = self::get_terms( $term_ids );
				$terms[ $key ] = $term_names;
			}
		}

		$keyword = get_post_meta( $alert_id, 'alert_keyword', true );

		if ( empty( $terms['regions'] ) ) {
			unset( $terms['regions'] );
			$location = get_post_meta( $alert_id, 'alert_location', true );
			if ( ! empty( $location ) ) {
				$terms = array_merge( [ 'location' => [ $location ] ], $terms );
			}
			unset( $terms['regions'] );
		}

		if ( ! empty( $keyword ) ) {
			$terms = array_merge( [ 'keywords' => [ $keyword ] ], $terms );
		}

		return $terms;
	}

	/**
	 * Prior to 1.5.0, alerts were associated with terms. This attempts to fetch those legacy associations and covert
	 * them to the new meta data format. This also clears the taxonomy associations.
	 *
	 * @since 1.5.0
	 *
	 * @param int $alert_id
	 *
	 * @return array
	 */
	private static function get_legacy_search_terms( $alert_id ) {
		$search_terms      = [];
		$taxonomy_type_map = [
			'categories' => 'job_listing_category',
			'regions'    => 'job_listing_region',
			'tags'       => 'job_listing_tag',
			'types'      => 'job_listing_type',
		];
		foreach ( $taxonomy_type_map as $key => $taxonomy_type ) {
			if ( taxonomy_exists( $taxonomy_type ) ) {
				$terms = array_filter( (array) wp_get_post_terms( $alert_id, $taxonomy_type, [ 'fields' => 'ids' ] ) );
				if ( count( $terms ) > 0 ) {
					$search_terms[ $key ] = $terms;
				}

				// Remove legacy post terms.
				wp_set_post_terms( $alert_id, [], $taxonomy_type );
			}
		}
		update_post_meta( $alert_id, 'alert_search_terms', $search_terms );

		return $search_terms;
	}

	/**
	 * Resolve term IDs to names or slugs.
	 *
	 * @since 3.0.0
	 *
	 * @param array  $term_ids Array of term IDs.
	 * @param string $fields Field to return, e.g. 'names', 'slugs'.
	 *
	 * @return array
	 */
	public static function get_terms( $term_ids, $fields = 'names' ): array {

		if ( empty( $term_ids ) ) {
			return [];
		}

		return get_terms(
			[
				'fields'     => $fields,
				'include'    => array_map( 'absint', $term_ids ),
				'hide_empty' => false,
			]
		);
	}

	/**
	 * Get the available search term fields for alerts.
	 *
	 * @since 3.0.0
	 *
	 * @return array
	 */
	public static function get_search_fields() {
		$term_rows = [
			'keywords' => [
				'label' => __( 'Keyword', 'wp-job-manager-alerts' ),
			],
		];

		if ( get_option( 'job_manager_enable_categories' ) && wp_count_terms( 'job_listing_category' ) > 0 ) {
			$term_rows['categories'] = [
				'label' => __( 'Category', 'wp-job-manager-alerts' ),
			];
		}

		if ( taxonomy_exists( 'job_listing_tag' ) ) {
			$term_rows['tags'] = [
				'label' => __( 'Tags', 'wp-job-manager-alerts' ),
			];
		}

		if ( get_option( 'job_manager_enable_types' ) && wp_count_terms( 'job_listing_types' ) > 0 ) {
			$term_rows['types'] = [
				'label' => __( 'Type', 'wp-job-manager-alerts' ),
			];
		}

		if ( taxonomy_exists( 'job_listing_region' ) && wp_count_terms( 'job_listing_region' ) > 0 ) {
			$term_rows['regions'] = [
				'label' => __( 'Location', 'wp-job-manager-alerts' ),
			];
		} else {
			$term_rows['location'] = [
				'label' => __( 'Location', 'wp-job-manager-alerts' ),
			];
		}

		return $term_rows;
	}

}
