<?php
/**
 * LearnDash class to handle Search integration
 *
 * @package LearnDash\Search
 * @since 3.1.7
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'LearnDash_Search' ) ) {

	/**
	 * Class for handling the LearnDash Search.
	 */
	class LearnDash_Search {

		/**
		 * Is Search Query.
		 *
		 * @var boolean $is_search_query;
		 */
		private $is_search_query = false;

		/**
		 * User ID used for Search.
		 *
		 * @var integer $user_id;
		 */
		private $user_id = 0;

		/**
		 * User Enrolled Courses.
		 *
		 * @var array $enrolled_courses;
		 */
		private $enrolled_courses = array();

		/**
		 * Searchable LearnDash Post Types.
		 *
		 * @var array $searchable_post_types;
		 */
		private $searchable_post_types = array();

		/**
		 * Changed LearnDash Post Types.
		 *
		 * @var array $changed_post_types;
		 */
		private $changed_post_types = array();

		/**
		 * LearnDash_Search constructor.
		 */
		public function __construct() {

			add_action( 'pre_get_posts', array( $this, 'pre_get_posts' ), 30, 1 );
			add_filter( 'posts_where_request', array( $this, 'posts_where_request' ), 30, 2 );
			add_filter( 'posts_join_request', array( $this, 'posts_join_request' ), 30, 2 );
			add_filter( 'posts_distinct_request', array( $this, 'posts_distinct_request' ), 30, 2 );
		}

		/**
		 * Filter WP_Query instance to add LD search logic.
		 *
		 * @param Object $query WP_Query object.
		 */
		public function pre_get_posts( $query ) {
			global $wp_post_types;

			$this->is_search_query = false;
			if ( ( $query->is_main_query() ) && ( ! is_admin() ) && ( $query->is_search ) ) {

				$in_search_post_types = get_post_types( array( 'exclude_from_search' => false ) );
				$ld_post_types        = learndash_get_post_types( 'course_steps' );

				$ld_post_types = array_intersect( $ld_post_types, $in_search_post_types );
				if ( ! empty( $ld_post_types ) ) {

					if ( is_user_logged_in() ) {
						$this->user_id          = get_current_user_id();
						$this->enrolled_courses = learndash_user_get_enrolled_courses( $this->user_id, array(), true );
						if ( ! empty( $this->enrolled_courses ) ) {
							foreach ( $ld_post_types as $ld_post_type ) {
								if ( isset( $wp_post_types[ $ld_post_type ] ) ) {
									if ( learndash_post_type_search_param( $ld_post_type, 'search_enrolled_only' ) ) {
										$this->searchable_post_types[] = $ld_post_type;
									}
								}
							}

							if ( ! empty( $this->searchable_post_types ) ) {
								$this->is_search_query = true;
							}
						} else {
							foreach ( $ld_post_types as $ld_post_type ) {
								if ( isset( $wp_post_types[ $ld_post_type ] ) ) {
									if ( learndash_post_type_search_param( $ld_post_type, 'search_enrolled_only' ) ) {
										$wp_post_types[ $ld_post_type ]->exclude_from_search = true;
									}
								}
							}
						}
					} else {
						// If we don't have any enrolled courses we remove the LearnDash CPTs from the search.
						foreach ( $ld_post_types as $ld_post_type ) {
							if ( isset( $wp_post_types[ $ld_post_type ] ) ) {
								if ( learndash_post_type_search_param( $ld_post_type, 'search_login_only' ) ) {
									$wp_post_types[ $ld_post_type ]->exclude_from_search = true;
								}
							}
						}
					}
				}
			}
		}

		/**
		 * Filter WP_Query 'where' string to add LD search logic.
		 *
		 * @param string $where SQL where part.
		 * @param Object $query WP_Query object.
		 * @return string $where.
		 */
		public function posts_where_request( $where, $query ) {
			global $wpdb;

			if ( ( true === $this->is_search_query ) && ( ! empty( $this->searchable_post_types ) ) ) {
				if ( ( ! isset( $query->query_vars['meta_query'] ) ) || ( empty( $query->query_vars['meta_query'] ) ) ) {
					$searchable_post_types_str = '';
					if ( ! empty( $this->searchable_post_types ) ) {
						foreach ( $this->searchable_post_types as $post_type ) {
							if ( ! empty( $searchable_post_types_str ) ) {
								$searchable_post_types_str .= ',';
							}
							$searchable_post_types_str .= "'" . esc_sql( $post_type ) . "'";
						}
					}

					$searchable_course_ids        = '';
					$searchable_shared_course_ids = '';
					if ( ! empty( $this->enrolled_courses ) ) {
						foreach ( $this->enrolled_courses as $course_id ) {
							if ( ! empty( $searchable_course_ids ) ) {
								$searchable_course_ids .= ',';
							}
							$searchable_course_ids .= absint( $course_id );
						}

						if ( 'yes' === LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Courses_Builder', 'shared_steps' ) ) {
							foreach ( $this->enrolled_courses as $course_id ) {
								if ( ! empty( $searchable_shared_course_ids ) ) {
									$searchable_shared_course_ids .= ',';
								}
								$searchable_shared_course_ids .= "'ld_course_" . absint( $course_id ) . "'";
							}
						}

						$where_ld = '';
						if ( ! empty( $searchable_post_types_str ) ) {
							$where_ld .= " ( {$wpdb->posts}.post_type NOT IN ( {$searchable_post_types_str} ) ) ";
							$where_ld .= ' OR (';
							$where_ld .= " ( {$wpdb->posts}.post_type IN ( {$searchable_post_types_str} ) ) ";

							if ( ! empty( $searchable_course_ids ) ) {
								$where_ld .= " AND ( ( {$wpdb->postmeta}.meta_key = 'course_id' AND {$wpdb->postmeta}.meta_value IN ( {$searchable_course_ids} ) ) ";
								if ( ! empty( $searchable_shared_course_ids ) ) {
									$where_ld .= " OR ( {$wpdb->postmeta}.meta_key IN ( {$searchable_shared_course_ids} ) ) ";
								}
								$where_ld .= ')';
							}
							$where_ld .= ') ';
						}

						if ( ! empty( $where_ld ) ) {
							$where .= ' AND (' . $where_ld . ')';
						}
					}
				} else {
					$this->is_search_query = false;
				}
			}
			return $where;
		}

		/**
		 * Filter WP_Query 'join' string to add LD search logic.
		 *
		 * @param string $join SQL join part.
		 * @param Object $query WP_Query object.
		 * @return string $join.
		 */
		public function posts_join_request( $join, $query ) {
			global $wpdb;

			if ( ( true === $this->is_search_query ) && ( ! empty( $this->searchable_post_types ) ) ) {
				if ( empty( $join ) ) {
					$join .= " LEFT JOIN {$wpdb->postmeta} as {$wpdb->postmeta} ON {$wpdb->posts}.ID={$wpdb->postmeta}.post_id ";
				}
			}
			return $join;
		}

		/**
		 * Filter WP_Query 'distinct' string to add LD search logic.
		 *
		 * @param string $distinct SQL distinct part.
		 * @param Object $query WP_Query object.
		 * @return string $distinct.
		 */
		public function posts_distinct_request( $distinct, $query ) {
			if ( ( true === $this->is_search_query ) && ( ! empty( $this->searchable_post_types ) ) ) {
				if ( empty( $distinct ) ) {
					$distinct .= ' DISTINCT ';
				}
			}
			return $distinct;
		}

		// End of functions.
	}
	$learndash_search = new LearnDash_Search();
}
