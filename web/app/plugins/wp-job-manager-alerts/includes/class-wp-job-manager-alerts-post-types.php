<?php
/**
 * WP_Job_Manager_Alerts_Post_Types class.
 */
class WP_Job_Manager_Alerts_Post_Types {

	/**
	 * Constructor
	 */
	public function __construct() {
		add_action( 'init', [ $this, 'register_post_types' ], 20 );
		add_filter( 'post_types_to_delete_with_user', [ $this, 'post_types_to_delete_with_user' ] );
	}

	/**
	 * register_post_types function.
	 */
	public function register_post_types() {
		if ( post_type_exists( "job_alert" ) ) {
			return;
		}

		register_post_type( "job_alert",
			apply_filters( "register_post_type_job_alert", array(
				'public'              => false,
				'show_ui'             => false,
				'capability_type'     => 'post',
				'publicly_queryable'  => false,
				'exclude_from_search' => true,
				'hierarchical'        => false,
				'rewrite'             => false,
				'query_var'           => false,
				'supports'            => false,
				'has_archive'         => false,
				'show_in_nav_menus'   => false
			) )
		);

		if ( taxonomy_exists( 'job_listing_category' ) ) {
			register_taxonomy_for_object_type( 'job_listing_category', 'job_alert' );
		}

		register_taxonomy_for_object_type( 'job_listing_type', 'job_alert' );
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
		$post_types_to_delete[] = 'job_alert';

		return $post_types_to_delete;
	}

	/**
	 * Fetches the terms used in the search query for the alert.
	 *
	 * @since 1.5.0
	 *
	 * @param int $alert_id
	 * @return array
	 */
	public static function get_alert_search_terms( $alert_id ) {
		$base_terms = array(
			'categories' => array(),
			'regions'    => array(),
			'tags'       => array(),
			'types'      => array(),
		);
		if ( metadata_exists( 'post', $alert_id, 'alert_search_terms' ) ) {
			return array_merge( $base_terms, get_metadata( 'post', $alert_id, 'alert_search_terms', true ) );
		}
		return array_merge( $base_terms, self::get_legacy_search_terms( $alert_id ) );
	}

	/**
	 * Prior to 1.5.0, alerts were associated with terms. This attempts to fetch those legacy associations and covert
	 * them to the new meta data format. This also clears the taxonomy associations.
	 *
	 * @since 1.5.0
	 *
	 * @param int $alert_id
	 * @return array
	 */
	private static function get_legacy_search_terms( $alert_id ) {
		$search_terms = array();
		$taxonomy_type_map = array(
			'categories' => 'job_listing_category',
			'regions'    => 'job_listing_region',
			'tags'       => 'job_listing_tag',
			'types'      => 'job_listing_type',
		);
		foreach ( $taxonomy_type_map as $key => $taxonomy_type ) {
			if ( taxonomy_exists( $taxonomy_type ) ) {
				$terms = array_filter( (array) wp_get_post_terms( $alert_id, $taxonomy_type, array( 'fields' => 'ids' ) ) );
				if ( count( $terms ) > 0 ) {
					$search_terms[ $key ] = $terms;
				}

				// Remove legacy post terms.
				wp_set_post_terms( $alert_id, array(), $taxonomy_type );
			}
		}
		update_post_meta( $alert_id, 'alert_search_terms', $search_terms );

		return $search_terms;
	}
}
