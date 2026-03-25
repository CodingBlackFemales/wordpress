<?php
/**
 * BuddyBoss REST MemberpressLMS.
 *
 * @package BuddyBoss\MemberpressLMS
 *
 * @since 2.6.30
 */

use memberpress\courses\helpers\Options;

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BB_MeprLMS_Profile
 */
class BB_MeprLMS_REST {

	/**
	 * Singleton instance.
	 *
	 * @since 2.6.30
	 *
	 * @var null
	 */
	private static $instance = null;

	/**
	 * Your __construct() method will contain configuration options for
	 * your extension.
	 *
	 * @since 2.6.30
	 */
	public function __construct() {
		$this->setup_actions();
	}

	/**
	 * Get the Singleton instance.
	 *
	 * @return object BB_MeprLMS_Profile instance.
	 */
	public static function get_instance() {
		if ( null === self::$instance ) {
			self::$instance = new self();
		}
		return self::$instance;
	}

	/**
	 * Setup actions.
	 *
	 * @since 2.6.30
	 */
	public function setup_actions() {
		add_action( 'bp_rest_api_init', array( $this, 'register_route_fields' ) );
	}

	/**
	 * Register REST API routes.
	 *
	 * @since 2.6.30
	 */
	public function register_route_fields() {
		register_rest_field(
			'bp_groups',
			'course_ids',
			array(
				'get_callback'    => array( $this, 'get_group_courses' ),
				'update_callback' => null,
				'schema'          => null,
			)
		);

		register_rest_field(
			'bp_members',
			'course_ids',
			array(
				'get_callback'    => array( $this, 'get_user_courses' ),
				'update_callback' => null,
				'schema'          => null,
			)
		);
	}

	/**
	 * Callback to retrieve group courses for a specific BuddyBoss group.
	 *
	 * @since 2.6.30
	 *
	 * @param object          $object     Group object data from REST API.
	 * @param string          $field_name Field name.
	 * @param WP_REST_Request $request    REST request object.
	 *
	 * @return array List of course IDs associated with the group.
	 */
	public function get_group_courses( $object, $field_name, $request ) {
		$course_ids = array();

		// Check if MemberPress LMS integration functions and classes are available.
		if ( ! function_exists( 'bb_load_meprlms_group' ) || ! class_exists( 'BB_MeprLMS_Groups' ) || ! method_exists( 'BB_MeprLMS_Groups', 'get' ) ) {
			return $course_ids;
		}

		// Fetch MemberPress course options and pagination settings.
		$options                   = get_option( 'mpcs-options' );
		$paged                     = 0;
		$per_page                  = -1;
		$mpcs_sort_order           = Options::val( $options, 'courses-sort-order', 'alphabetically' );
		$mpcs_sort_order_direction = Options::val( $options, 'courses-sort-order-direction', 'ASC' );

		// Ensure valid order direction.
		$mpcs_sort_order_direction = in_array( $mpcs_sort_order_direction, array( 'ASC', 'DESC' ), true ) ? $mpcs_sort_order_direction : 'ASC';

		// Define sorting options.
		$sort_options = array(
			'alphabetically' => array( 'orderby' => 'title' ),
			'last-updated'   => array( 'orderby' => 'modified' ),
			'publish-date'   => array( 'orderby' => 'date' ),
		);

		// Determine sorting option.
		$sort_option = $sort_options[ $mpcs_sort_order ] ?? $sort_options['alphabetically'];

		// Build arguments for retrieving courses associated with the group.
		$args = array(
			'group_id'    => $object['id'],
			'fields'      => 'course_id',
			'count_total' => true,
			'paged'       => $paged,
			'per_page'    => $per_page,
			'order_by'    => $sort_option['orderby'],
			'order'       => $mpcs_sort_order_direction,
		);

		// Retrieve course IDs for the specified group.
		$bb_meprlms_groups = bb_load_meprlms_group()->get( $args );

		// Map course IDs to integers.
		return array_map( 'intval', $bb_meprlms_groups['courses'] );
	}

	/**
	 * Callback to retrieve user courses for a specific BuddyBoss user.
	 *
	 * @since 2.6.30
	 *
	 * @param object          $object     User object data from REST API.
	 * @param string          $field_name Field name.
	 * @param WP_REST_Request $request    REST request object.
	 *
	 * @return array List of course IDs associated with the group.
	 */
	public function get_user_courses( $object, $field_name, $request ) {
		$course_ids = array();

		// Retrieve course IDs for the specified group.
		$user_courses = bb_meprlms_get_user_courses( $object['id'], 'publish', 1, -1 );

		if ( ! empty( $user_courses->posts ) ) {
			// Extract post IDs from the user courses.
			$course_ids = wp_list_pluck( $user_courses->posts, 'ID' );
		}
		// Ensure IDs are integers.
		return array_map( 'intval', $course_ids );
	}
}

// Create an instance of the Singleton.
BB_MeprLMS_REST::get_instance();
