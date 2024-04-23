<?php
/**
 * BuddyBoss Groups TutorLMS Group Table.
 *
 * @package BuddyBoss\Groups\TutorLMS
 * @since 2.4.40
 */

// Exit if accessed directly.
defined( 'ABSPATH' ) || exit;

/**
 * Class BB_TutorLMS_Groups
 */
class BB_TutorLMS_Groups {

	/**
	 * The single instance of the class.
	 *
	 * @since 2.4.40
	 *
	 * @access private
	 * @var self
	 */
	private static $instance = null;

	/**
	 * TutorLMS group course Table name.
	 *
	 * @since 2.4.40
	 *
	 * @access public
	 * @var string
	 */
	public static $tutorlms_group_table = '';

	/**
	 * Cache group for TutorLMS group course.
	 *
	 * @since 2.4.40
	 *
	 * @access public
	 * @var string
	 */
	public static $cache_group = 'bb_tutorlms';

	/**
	 * Get the instance of this class.
	 *
	 * @since 2.4.40
	 *
	 * @return Controller|BB_TutorLMS_Groups|null
	 */
	public static function instance() {

		if ( null === self::$instance ) {
			$class_name     = __CLASS__;
			self::$instance = new $class_name();
		}

		return self::$instance;
	}

	/**
	 * Constructor method.
	 *
	 * @since 2.4.40
	 */
	public function __construct() {
		require_once ABSPATH . 'wp-admin/includes/upgrade.php';
		$wpdb            = $GLOBALS['wpdb'];
		$charset_collate = $wpdb->get_charset_collate();

		// User group course table.
		$bp_prefix = bp_core_get_table_prefix();
		// User group course table.
		$bb_tutorlms_groups         = $bp_prefix . 'bb_groups_tutor_courses';
		self::$tutorlms_group_table = $bb_tutorlms_groups;

		// Table already exists, so maybe upgrade instead?
		$group_courses_table_exists = $wpdb->query( "SHOW TABLES LIKE '{$bb_tutorlms_groups}';" ); // phpcs:ignore
		if ( ! $group_courses_table_exists ) {
			$sql = "CREATE TABLE {$bb_tutorlms_groups} (
					id bigint(20) NOT NULL AUTO_INCREMENT,
					group_id bigint(20) NOT NULL,
					course_id bigint(20) NOT NULL,
					date_created datetime NOT NULL,
					PRIMARY KEY (id),
					KEY group_id (group_id),
					KEY course_id (course_id),
					KEY date_created (date_created)
				) {$charset_collate};";

			dbDelta( $sql );
		}

		$this->includes();
	}

	/**
	 * Includes
	 *
	 * @since 2.4.40
	 */
	private function includes() {
		require bb_tutorlms_integration_path() . 'bb-tutorlms-group-functions.php';
	}

	/**
	 * Function to add course data which is associated with the group.
	 *
	 * @since 2.4.40
	 *
	 * @param array $args Array of args.
	 *
	 * @return void
	 */
	public static function add( $args ) {
		global $wpdb;

		$r = bp_parse_args(
			$args,
			array(
				'group_id'  => false,
				'course_id' => false,
			)
		);

		if ( empty( $r['group_id'] ) ) {
			return;
		}

		if ( ! empty( $r['course_id'] ) && ! is_array( $r['course_id'] ) ) {
			$r['course_id'] = array( $r['course_id'] );
		}

		/**
		 * Fires before the add courses to the group.
		 *
		 * @since 2.4.40
		 *
		 * @param array $r Array of args.
		 */
		do_action( 'bb_tutorlms_before_add_group_course', $r );

		// Get existing group course ids.
		$existing_course_data = bb_load_tutorlms_group()->get(
			array(
				'group_id' => $r['group_id'],
				'fields'   => 'course_id',
			)
		);
		$existing_course_ids  = ! empty( $existing_course_data['courses'] ) ? $existing_course_data['courses'] : array();

		// Get existing course ids but only those which will remove.
		$remove_course_ids = ! empty( $existing_course_ids ) ? array_diff( $existing_course_ids, $r['course_id'] ) : '';
		if ( ! empty( $remove_course_ids ) ) {
			bb_load_tutorlms_group()->delete( array(
				'group_id'  => $r['group_id'],
				'course_id' => $remove_course_ids,
			) );
		}

		// Get new course ids.
		$course_ids = ! empty( $existing_course_ids ) ? array_diff( $r['course_id'], $existing_course_ids ) : $r['course_id'];
		if ( ! empty( $course_ids ) ) {
			foreach ( $course_ids as $course_id ) {
				$wpdb->insert(
					self::$tutorlms_group_table,
					array(
						'group_id'     => $r['group_id'],
						'course_id'    => $course_id,
						'date_created' => bp_core_current_time(),
					)
				);
			}
		}

		/**
		 * Fires after the add courses to the group.
		 *
		 * @since 2.4.40
		 *
		 * @param array $r Array of args.
		 */
		do_action( 'bb_tutorlms_after_add_group_course', $r );
	}

	/**
	 * Function to delete group courses.
	 *
	 * @since 2.4.40
	 *
	 * @param array $args Array of args.
	 *
	 * @return bool|int|mysqli_result
	 */
	public static function delete( $args ) {
		global $wpdb;

		$r = bp_parse_args(
			$args,
			array(
				'group_id'  => false,
				'id'        => false,
				'course_id' => false,
			),
		);

		// Setup empty array from where query arguments.
		$where_args = array();

		// id.
		if ( ! empty( $r['id'] ) ) {
			$id               = implode( ',', wp_parse_id_list( $r['id'] ) );
			$where_args['id'] = "id IN ({$id})";
		}

		// course_ids.
		if ( ! empty( $r['course_id'] ) ) {
			$course_id                = implode( ',', wp_parse_id_list( $r['course_id'] ) );
			$where_args['course_ids'] = "course_id IN ({$course_id})";
		}

		// group_id.
		if ( ! empty( $r['group_id'] ) ) {
			$where_args['group_id'] = $wpdb->prepare( 'group_id = %s', $r['group_id'] );
		}

		// Bail if no where arguments.
		if ( empty( $where_args ) ) {
			return false;
		}

		// Join the where arguments for querying.
		$where_sql = 'WHERE ' . join( ' AND ', $where_args );

		/**
		 * Action to allow intercepting group course items to be deleted.
		 *
		 * @since 2.4.40
		 *
		 * @param array $group_courses Array of group courses.
		 * @param array $r             Array of parsed arguments.
		 */
		do_action( 'bb_group_tutorlms_course_before_delete', $where_args, $r );

		// Attempt to delete group_courses from the database.
		$deleted = $wpdb->query( "DELETE FROM " . self::$tutorlms_group_table . " {$where_sql}" );

		// Bail if nothing was deleted.
		if ( empty( $deleted ) ) {
			return false;
		}

		/**
		 * Action to allow intercepting group course items just deleted.
		 *
		 * @since 2.4.40
		 *
		 * @param array $group_courses Array of group courses.
		 * @param array $r             Array of parsed arguments.
		 */
		do_action( 'bb_group_tutorlms_course_after_delete', $deleted, $r );

		return $deleted;
	}

	/**
	 * Query for TutorLMS group courses.
	 *
	 * @since 2.4.40
	 *
	 * @param array $args {
	 * An array of arguments. All items are optional.
	 *
	 * @type int         $group_id    Group id.
	 * @type array       $course_ids  Course ids.
	 * @type string      $order_by    Column to order results by.
	 * @type string      $order       ASC or DESC. Default: 'DESC'.
	 * @type int|bool    $per_page    Number of results per page. Default: 20.
	 * @type int         $paged       Which page of results to fetch. Using page=1 without per_page will result
	 *                                in no pagination. Default: 1.
	 * @type string|bool $count_total If true, an additional DB query is run to count the total video items
	 *                                for the query. Default: false.
	 * @type string      $fields      Which fields to return. Specify 'id' to fetch a list of IDs.
	 *                                Default: 'all' (return BP_Subscription objects).
	 * }
	 *
	 * @return array The array returned has two keys:
	 *                - 'total' is the count of located courses
	 *                - 'courses' is an array of the located courses
	 */
	public static function get( $args ) {
		global $wpdb;

		$r = bp_parse_args(
			$args,
			array(
				'group_id'    => 0,
				'course_ids'  => array(),
				'order_by'    => 'id',
				'order'       => 'ASC',
				'per_page'    => 20,     // Results per page.
				'paged'       => 1,      // Page 1 without a per_page will result in no pagination.
				'count_total' => false,  // Whether to use count_total.
				'fields'      => 'all',  // Fields to include.
				'error_type'  => 'bool',
			),
			'bb_tutorlms_get_group_courses'
		);

		// Select conditions.
		$select_sql = 'SELECT DISTINCT tg.id';

		$from_sql = ' FROM ' . self::$tutorlms_group_table . ' tg';

		$join_sql = '';

		// Where conditions.
		$where_conditions = array();

		// Sorting.
		$sort = bp_esc_sql_order( $r['order'] );
		if ( 'ASC' !== $sort && 'DESC' !== $sort ) {
			$sort = 'DESC';
		}

		switch ( $r['order_by'] ) {
			case 'date_created':
				$r['order_by'] = 'date_created';
				break;

			default:
				$r['order_by'] = 'id';
				break;
		}
		$order_by = 'tg.' . $r['order_by'];

		// id.
		if ( ! empty( $r['id'] ) ) {
			$id_in                  = implode( ',', wp_parse_id_list( $r['id'] ) );
			$where_conditions['id'] = "tg.id IN ({$id_in})";
		}

		// group_id.
		if ( ! empty( $r['group_id'] ) ) {
			$user_id_in                   = implode( ',', wp_parse_id_list( $r['group_id'] ) );
			$where_conditions['group_id'] = "tg.group_id IN ({$user_id_in})";
		}

		// course_id.
		if ( ! empty( $r['course_id'] ) ) {
			$reaction_id_in                = implode( ',', wp_parse_id_list( $r['course_id'] ) );
			$where_conditions['course_id'] = "tg.course_id IN ({$reaction_id_in})";
		}

		/**
		 * Filters the MySQL WHERE conditions for the group course get sql method.
		 *
		 * @since 2.4.40
		 *
		 * @param array  $where_conditions Current conditions for MySQL WHERE statement.
		 * @param array  $r                Parsed arguments passed into method.
		 * @param string $select_sql       Current SELECT MySQL statement at point of execution.
		 * @param string $from_sql         Current FROM MySQL statement at point of execution.
		 * @param string $join_sql         Current INNER JOIN MySQL statement at point of execution.
		 */
		$where_conditions = apply_filters( 'bb_tutorlms_get_group_courses_where_conditions', $where_conditions, $r, $select_sql, $from_sql, $join_sql );

		if ( empty( $where_conditions ) ) {
			$where_conditions[] = '1 = 1';
		}

		// Join the where conditions together.
		$where_sql = 'WHERE ' . join( ' AND ', $where_conditions );

		/**
		 * Filter the MySQL JOIN clause for the main group course query.
		 *
		 * @since 2.4.40
		 *
		 * @param string $join_sql   JOIN clause.
		 * @param array  $r          Method parameters.
		 * @param string $select_sql Current SELECT MySQL statement.
		 * @param string $from_sql   Current FROM MySQL statement.
		 * @param string $where_sql  Current WHERE MySQL statement.
		 */
		$join_sql = apply_filters( 'bb_tutorlms_get_group_courses_join_sql', $join_sql, $r, $select_sql, $from_sql, $where_sql );

		$retval = array(
			'courses' => null,
			'total'   => null,
		);

		// Sanitize page and per_page parameters.
		$page       = absint( $r['paged'] );
		$per_page   = absint( $r['per_page'] );
		$pagination = '';
		if ( ! empty( $per_page ) && ! empty( $page ) && - 1 !== $per_page ) {
			$pagination = $wpdb->prepare( 'LIMIT %d, %d', intval( ( $page - 1 ) * $per_page ), intval( $per_page ) );
		}

		// Query first for group course IDs.
		$paged_group_courses_sql = "{$select_sql} {$from_sql} {$join_sql} {$where_sql} ORDER BY {$order_by} {$sort} {$pagination}";

		/**
		 * Filters the paged group course MySQL statement.
		 *
		 * @since 2.4.40
		 *
		 * @param string $paged_group_courses_sql MySQL's statement used to query for group course IDs.
		 * @param array  $r                       Array of arguments passed into method.
		 */
		$paged_group_courses_sql = apply_filters( 'bb_tutorlms_get_group_courses_paged_sql', $paged_group_courses_sql, $r );

		$cached = bp_core_get_incremented_cache( $paged_group_courses_sql, self::$cache_group );
		if ( false === $cached ) {
			$paged_group_courses_ids = $wpdb->get_col( $paged_group_courses_sql ); // phpcs:ignore
			bp_core_set_incremented_cache( $paged_group_courses_sql, self::$cache_group, $paged_group_courses_ids );
		} else {
			$paged_group_courses_ids = $cached;
		}

		if ( 'id' === $r['fields'] ) {
			// We only want the IDs.
			$paged_group_courses = array_map( 'intval', $paged_group_courses_ids );
		} else {
			$uncached_ids = bp_get_non_cached_ids( $paged_group_courses_ids, self::$cache_group );
			if ( ! empty( $uncached_ids ) ) {
				$uncached_ids_sql = implode( ',', wp_parse_id_list( $uncached_ids ) );

				// phpcs:ignore
				$queried_data = $wpdb->get_results( 'SELECT * FROM ' . self::$tutorlms_group_table . " WHERE id IN ({$uncached_ids_sql})" );

				foreach ( (array) $queried_data as $urdata ) {
					wp_cache_set( $urdata->id, $urdata, self::$cache_group );
				}
			}

			$paged_group_courses = array();
			foreach ( $paged_group_courses_ids as $id ) {
				$group_course = wp_cache_get( $id, self::$cache_group );
				if ( ! empty( $group_course ) ) {
					$paged_group_courses[] = $group_course;
				}
			}

			if ( 'all' !== $r['fields'] ) {
				$paged_group_courses = array_unique( array_column( $paged_group_courses, $r['fields'] ) );
			}
		}

		$retval['courses'] = $paged_group_courses;

		if ( ! empty( $r['count_total'] ) ) {
			/**
			 * Filters the total TutorLMS group course MySQL statement.
			 *
			 * @since 2.4.40
			 *
			 * @param string $sql       MySQL statement used to query for total group course.
			 * @param string $where_sql MySQL WHERE statement portion.
			 * @param string $sort      Sort direction for query.
			 */
			$sql                     = 'SELECT count(DISTINCT tg.id) FROM ' . self::$tutorlms_group_table . " tg {$join_sql} {$where_sql}";
			$total_group_courses_sql = apply_filters( 'bb_tutorlms_get_group_courses_total_sql', $sql, $where_sql, $sort );
			$cached                  = bp_core_get_incremented_cache( $total_group_courses_sql, self::$cache_group );
			if ( false === $cached ) {
				$total_group_courses = $wpdb->get_var( $total_group_courses_sql ); // phpcs:ignore
				bp_core_set_incremented_cache( $total_group_courses_sql, self::$cache_group, $total_group_courses );
			} else {
				$total_group_courses = $cached;
			}

			$retval['total'] = $total_group_courses;
		}

		return $retval;
	}
}
