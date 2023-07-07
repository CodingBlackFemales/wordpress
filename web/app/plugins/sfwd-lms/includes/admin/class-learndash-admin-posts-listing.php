<?php
/**
 * LearnDash Posts Listing Abstract.
 *
 * @since 2.6.0
 * @package LearnDash
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ! class_exists( 'Learndash_Admin_Posts_Listing' ) ) {

	/**
	 * Class LearnDash Posts Listing Abstract.
	 *
	 * @since 2.6.0
	 */
	abstract class Learndash_Admin_Posts_Listing {
		/**
		 * Private array of the Classes and related selectors with nonce keys for external access.
		 *
		 * @var array $listing_sets;
		 */
		private static $listing_sets = array();

		/**
		 * Nonce instance for class.
		 *
		 * @var string $listing_nonce.
		 */
		protected $listing_nonce = null;

		/**
		 * Variable to hold the listing post type. This will be set in the sub-classes instances.
		 *
		 * @var string $post_type
		 */
		protected $post_type;

		/**
		 * Array of custom columns to add to the listing.
		 *
		 * Key is the column slug. Value is array of label,
		 * after, and callback function.
		 *
		 * @var array $columns
		 */
		protected $columns = array();

		/**
		 * Array of filter selectors shown at the top of the table listing.
		 *
		 * @var array $selectors Array of selector filter.
		 */
		protected $selectors = array();

		/**
		 * Array of post ids populated by before_delete_post then cleared by deleted_post.
		 *
		 * @var array $posts_to_delete Array of post IDs to delete.
		 */
		protected $posts_to_delete = array();

		/**
		 * Flag set when AJAX Fetch process is running.
		 *
		 * @var bool $doing_ajax_fetch.
		 */
		protected $doing_ajax_fetch = false;

		/**
		 * Flag set when Listing init has been run. This is to prevent processing
		 * the listing init more than once.
		 *
		 * @var bool $listing_init.
		 */
		protected $listing_init_done = false;
		/**
		 * Public constructor for class
		 *
		 * @since 2.6.0
		 */
		public function __construct() {
			add_action( 'load-edit.php', array( $this, 'on_load_listing' ) );
			add_action( 'load-users.php', array( $this, 'on_load_listing' ) );

			add_action( 'wp_ajax_learndash_listing_select2_query', array( $this, 'ajax_listing_select2_query' ), 10 );

			add_filter( 'manage_edit-' . $this->post_type . '_columns', array( $this, 'manage_column_headers' ), 50, 1 );
			add_action(
				'manage_' . $this->post_type . '_posts_custom_column',
				array( $this, 'manage_post_column_rows' ),
				50,
				2
			);
		}

		/**
		 * Common function to check if we are editing a correct post type.
		 *
		 * @since 3.2.3
		 *
		 * @param string $post_type Optional. Post type slug.
		 *
		 * @return boolean true is correct, else false.
		 */
		protected function post_type_check( $post_type = '' ) {
			global $pagenow, $typenow;

			if ( 'edit.php' === $pagenow ) {
				if ( empty( $post_type ) ) {
					if ( ! empty( $typenow ) ) {
						$post_type = $typenow;
					}
				}
				if ( ( ! empty( $post_type ) ) && ( $post_type === $this->post_type ) ) {
					return true;
				}
			} elseif ( 'users.php' === $pagenow ) {
				if ( 'user' === $this->post_type ) {
					return true;
				}
			}

			return false;
		}

		/**
		 * Call via the WordPress load sequence for admin pages.
		 *
		 * @since 3.2.3
		 */
		public function on_load_listing() {
			if ( $this->post_type_check() ) {
				$this->listing_init();

				add_filter( 'manage_taxonomies_for_' . $this->post_type . '_columns', array( $this, 'manage_taxonomies_columns' ), 50, 2 );
				add_filter( 'post_row_actions', array( $this, 'post_row_actions' ), 20, 2 );

				add_filter( 'disable_categories_dropdown', array( $this, 'disable_categories_dropdown' ), 20, 2 );
				add_action( 'restrict_manage_posts', array( $this, 'restrict_manage_posts_selectors' ), 50, 2 );
				add_filter( 'parse_query', array( $this, 'parse_query_table_listing' ), 50, 1 );
			}
		}

		/**
		 * Handler function for Select2 AJAX requests.
		 *
		 * @since 3.2.3
		 */
		public function ajax_listing_select2_query() {
			$result_array = array(
				'items'       => array(),
				'total_items' => 0,
				'page'        => 1,
				'total_pages' => 1,
			);

			if ( ! current_user_can( 'read' ) ) {
				echo wp_json_encode( $result_array );
				wp_die();
			}

			if ( ( ! isset( $_POST['listing_nonce'] ) ) || ( empty( $_POST['listing_nonce'] ) ) ) {
				echo wp_json_encode( $result_array );
				wp_die();
			}

			if ( ( ! isset( $_POST['query_data'] ) ) || ( empty( $_POST['query_data'] ) ) ) {
				echo wp_json_encode( $result_array );
				wp_die();
			}

			$this->listing_init();

			if ( wp_verify_nonce( sanitize_text_field( wp_unslash( $_POST['listing_nonce'] ) ), get_called_class() ) ) {
				if ( ( ! isset( $_POST['query_data']['selector_key'] ) ) || ( empty( $_POST['query_data']['selector_key'] ) ) ) {
					echo wp_json_encode( $result_array );
					wp_die();
				}

				$selector_nonce = sanitize_text_field( wp_unslash( $_POST['query_data']['selector_key'] ) );
				$selector       = $this->get_selector_by_nonce( $selector_nonce );
				if ( ! $selector ) {
					echo wp_json_encode( $result_array );
					wp_die();
				}

				$this->doing_ajax_fetch = true;

				if ( ( isset( $_POST['query_data']['selector_filters'] ) ) && ( ! empty( $_POST['query_data']['selector_filters'] ) ) ) {
					$this->fill_selectors_values_ajax( $_POST['query_data']['selector_filters'] ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized
				}

				if ( ( ! isset( $selector['query_args']['post_type'] ) ) || ( empty( $selector['query_args']['post_type'] ) ) ) {
					if ( ( isset( $selector['post_type'] ) ) && ( ! empty( $selector['post_type'] ) ) ) {
						$selector['query_args']['post_type'] = $selector['post_type'];
					}
				}

				$selector['query_args']['paged'] = 1;
				if ( isset( $_POST['page'] ) ) {
					$selector['query_args']['paged'] = absint( $_POST['page'] );
				}

				if ( ( isset( $_POST['search'] ) ) && ( ! empty( $_POST['search'] ) ) ) {
					$selector['query_args']['s'] = sanitize_text_field( wp_unslash( $_POST['search'] ) );
				} else {
					if ( absint( $selector['query_args']['paged'] ) === 1 ) {
						// We only provide the 'empty' option when not a search.
						$empty_set = $this->get_selector_empty_set( $selector );
						if ( ! empty( $empty_set ) ) {
							foreach ( $empty_set as $empty_val => $empty_label ) {
								$result_array['items'][] = array(
									'id'   => esc_attr( $empty_val ),
									'text' => esc_attr( $empty_label ),
								);
							}
						}
					}
				}

				if ( 'post_type' === $selector['type'] ) {
					remove_filter( 'the_title', 'wptexturize' );
					$selector = $this->build_selector_post_type_options( $selector );
					add_filter( 'the_title', 'wptexturize' );

					if ( ( isset( $selector['options'] ) ) && ( ! empty( $selector['options'] ) ) ) {
						foreach ( $selector['options'] as $id => $title ) {
							$result_array['items'][] = array(
								'id'   => $id,
								'text' => $title,
							);
						}
					}

					if ( isset( $selector['pager_results']['total_items'] ) ) {
						$result_array['total_items'] = absint( $selector['pager_results']['total_items'] );
					}

					if ( isset( $selector['pager_results']['total_pages'] ) ) {
						$result_array['total_pages'] = absint( $selector['pager_results']['total_pages'] );
					}

					$result_array['page'] = absint( $selector['query_args']['paged'] );

				} elseif ( 'user' === $selector['type'] ) {
					$selector = $this->build_user_selector_options( $selector );
					if ( ( isset( $selector['options'] ) ) && ( ! empty( $selector['options'] ) ) ) {
						foreach ( $selector['options'] as $id => $title ) {
							$result_array['items'][] = array(
								'id'   => $id,
								'text' => $title,
							);
						}

						if ( isset( $selector['pager_results']['total_items'] ) ) {
							$result_array['total_items'] = absint( $selector['pager_results']['total_items'] );
						}

						if ( isset( $selector['pager_results']['total_pages'] ) ) {
							$result_array['total_pages'] = absint( $selector['pager_results']['total_pages'] );
						}

						$result_array['page'] = absint( $selector['query_args']['paged'] );

					}
				}

				echo wp_json_encode( $result_array );
				wp_die();
			}
		}

		/**
		 * Initialize the listing.
		 *
		 * @since 3.2.3
		 */
		public function listing_init() {
			if ( true === $this->listing_init_done ) {
				return;
			}

			// Initialize the listing taxonomies.
			$this->listing_taxonomies_init();

			/**
			 * Filters list table selectors
			 *
			 * @since 3.2.3
			 *
			 * @param array  $selectors Array of selectors.
			 * @param string $post_type Post Type for listing table.
			 */
			$this->selectors = apply_filters( 'learndash_listing_selectors', $this->selectors, $this->post_type );

			/**
			 * Filters listing table columns.
			 *
			 * @since 3.2.3
			 *
			 * @param array  $columns   Array of columns.
			 * @param string $post_type Post Type for listing table.
			 */
			$this->columns = apply_filters( 'learndash_listing_columns', $this->columns, $this->post_type );

			$this->register_listing_set();
		}

		/**
		 * Initialize the listing taxonomies.
		 *
		 * @since 3.2.3
		 */
		public function listing_taxonomies_init() {
			$object_taxonomies = get_object_taxonomies( $this->post_type );

			if ( has_filter( 'learndash-admin-taxonomy-filters-display' ) ) {
				/**
				 * Filters admin settings taxonomy filters list.
				 *
				 * @since 2.4.0
				 * @deprecated 3.2.3 Use {@see 'learndash_listing_taxonomies'} instead.
				 *
				 * @param array $object_taxonomies An array of the names or objects of all taxonomies of all the listing post types.
				 * @param array $post_types        An array of listing post types.
				 */
				$object_taxonomies = apply_filters_deprecated(
					'learndash-admin-taxonomy-filters-display',
					array( $object_taxonomies, $this->post_type ),
					'3.2.3',
					'learndash_listing_taxonomies'
				);
			}

			/**
			 * Filters admin listing table taxonomy filters.
			 *
			 * @since 3.2.3
			 *
			 * @param array  $object_taxonomies An array of the names or objects of all taxonomies of all the listing post types.
			 * @param string $post_type         Listing table post type.
			 */
			$object_taxonomies = apply_filters( 'learndash_listing_taxonomies', $object_taxonomies, $this->post_type );

			if ( ( ! empty( $object_taxonomies ) ) && ( is_array( $object_taxonomies ) ) ) {
				foreach ( $object_taxonomies as $taxonomy_slug ) {

					if ( $this->hide_empty_taxonomy( $taxonomy_slug ) ) {
						continue;
					}

					if ( 'category' === $taxonomy_slug ) {
						$query_arg  = 'cat';           // Used for WP_Query filtering.
						$field_name = 'category_name'; // Used for Selector name.
						$field_id   = 'category_name'; // Used for Selector ID.
					} elseif ( 'post_tag' === $taxonomy_slug ) {
						$query_arg  = 'tag_id';        // Used for WP_Query filtering.
						$field_name = 'tag';           // Used for Selector name.
						$field_id   = 'tag_id';        // Used for Selector ID.
					} else {
						$query_arg  = $taxonomy_slug;
						$field_name = $taxonomy_slug;
						$field_id   = $taxonomy_slug;
					}
					$this->selectors[ $taxonomy_slug ] = array(
						'type'                   => 'taxonomy',
						'taxonomy'               => $taxonomy_slug,
						'selected'               => '',
						'field_name'             => $field_name,
						'field_id'               => $field_id,
						'listing_query_function' => array( $this, 'listing_filter_by_taxonomy' ),
					);
				}
			}
		}

		/**
		 * Register the listing set with unique nonce key to be used during AJAX queries.
		 *
		 * @since 3.2.3
		 */
		protected function register_listing_set() {
			$class               = get_called_class();
			$class_nonce         = wp_create_nonce( $class );
			$this->listing_nonce = $class_nonce;
			if ( ! isset( self::$listing_sets[ $class_nonce ] ) ) {
				self::$listing_sets[ $class_nonce ]             = array();
				self::$listing_sets[ $class_nonce ]['class']    = $class;
				self::$listing_sets[ $class_nonce ]['instance'] = $this;

				if ( ! isset( self::$listing_sets[ $class_nonce ]['selectors'] ) ) {
					self::$listing_sets[ $class_nonce ]['selectors'] = array();
				}

				foreach ( $this->selectors as $selector_id => &$selector ) {
					// All Selectors MUST have a type.
					if ( ! isset( $selector['type'] ) ) {
						unset( $this->selectors[ $selector_id ] );
						continue;
					}

					if ( 'post_type' === $selector['type'] ) {
						if ( ( ! isset( $selector['post_type'] ) ) || ( empty( $selector['post_type'] ) ) ) {
							unset( $this->selectors[ $selector_id ] );
							continue;
						}

						$post_type_object = get_post_type_object( $selector['post_type'] );
						if ( ( ! is_a( $post_type_object, 'WP_Post_Type' ) ) || ( $post_type_object->name !== $selector['post_type'] ) ) {
							unset( $this->selectors[ $selector_id ] );
							continue;
						}

						if ( ( ! isset( $selector['field_label'] ) ) || ( empty( $selector['field_label'] ) ) ) {
							$selector['field_label'] = sprintf(
								// translators: placeholder: Post Type singular name.
								esc_html_x( 'Filter by %s', 'placeholder: Post Type singular name', 'learndash' ),
								esc_attr( $post_type_object->labels->singular_name )
							);
						}
					} elseif ( 'taxonomy' === $selector['type'] ) {
						if ( ( ! isset( $selector['taxonomy'] ) ) || ( empty( $selector['taxonomy'] ) ) ) {
							unset( $this->selectors[ $selector_id ] );
							continue;
						}

						$taxonomy_object = get_taxonomy( $selector['taxonomy'] );
						if ( ( ! is_a( $taxonomy_object, 'WP_Taxonomy' ) ) || ( $taxonomy_object->name !== $selector['taxonomy'] ) ) {
							unset( $this->selectors[ $selector_id ] );
							continue;
						}

						if ( ( ! isset( $selector['field_label'] ) ) || ( empty( $selector['field_label'] ) ) ) {
							$selector['field_label'] = sprintf(
								// translators: placeholder: Taxonomy singular name.
								esc_html_x( 'Filter by %s', 'placeholder: Taxonomy singular name', 'learndash' ),
								esc_attr( $taxonomy_object->labels->singular_name )
							);
						}
					}

					if ( ! isset( $selector['field_name'] ) ) {
						$selector['field_name'] = $selector_id;
					}

					if ( ! isset( $selector['field_id'] ) ) {
						$selector['field_id'] = $selector_id;
					}

					if ( ! isset( $selector['selected'] ) ) {
						$selector['selected'] = '';
					}

					if ( ! isset( $selector['display'] ) ) {
						$selector['display'] = '';
					}

					if ( ! isset( $selector['options'] ) ) {
						$selector['options'] = array();
					}

					if ( ! isset( $selector['show_all_value'] ) ) {
						$selector['show_all_value'] = '';
					}

					if ( ! isset( $selector['show_empty_value'] ) ) {
						$selector['show_empty_value'] = '';
					}

					if ( ! isset( $selector['listing_query_function'] ) ) {
						$selector['listing_query_function'] = '';
					}

					if ( ! isset( $selector['selector_filter_function'] ) ) {
						$selector['selector_filter_function'] = '';
					}

					if ( ! isset( $selector['selector_filters'] ) ) {
						$selector['selector_filters'] = '';
					}

					if ( ! isset( $selector['selector_hide_empty'] ) ) {
						$selector['selector_hide_empty'] = true;
					}

					if ( ! isset( $selector['query_args'] ) ) {
						$selector['query_args'] = array();
						if ( ( isset( $selector['type'] ) ) && ( 'post_type' === $selector['type'] ) ) {
							if ( ( isset( $selector['post_type'] ) ) && ( ! empty( $selector['post_type'] ) ) ) {
								$selector['query_args']['post_type'] = $selector['post_type'];
							}
						}
					}

					if ( ! isset( $selector['select2'] ) ) {
						$selector['select2'] = true;
					}

					if ( ! learndash_use_select2_lib() ) {
						$selector['select2'] = false;
					}

					if ( ! isset( $selector['select2_fetch'] ) ) {
						$selector['select2_fetch'] = true;
					}

					if ( ( ! $selector['select2'] ) || ( ! learndash_use_select2_lib_ajax_fetch() ) ) {
						$selector['select2_fetch'] = false;
					}

					$selector_nonce = wp_create_nonce( $selector_id );
					self::$listing_sets[ $class_nonce ]['selectors'][ $selector_nonce ] = $selector_id;

					$selector['nonce'] = $selector_nonce;
				}
			}
		}

		/**
		 * Get the Selector via the Selector nonce.
		 *
		 * @since 3.2.3
		 *
		 * @param string $selector_nonce Selector nonce.
		 */
		protected function get_selector_by_nonce( $selector_nonce = '' ) {
			if ( ! empty( $selector_nonce ) ) {
				if ( ! empty( $this->selectors ) ) {
					foreach ( $this->selectors as $key => $selector ) {
						if ( ( isset( $selector['nonce'] ) ) && ( $selector['nonce'] === $selector_nonce ) ) {
							return $selector;
						}
					}
				}
			}
		}

		/**
		 * Set the selector values from the _GET variables.
		 *
		 * @since 3.2.3
		 */
		protected function fill_selectors_values() {
			if ( ! empty( $this->selectors ) ) {
				foreach ( $this->selectors as $key => &$selector ) {
					if ( ( isset( $selector['field_name'] ) ) && ( ! empty( $selector['field_name'] ) ) ) {
						// phpcs:ignore WordPress.Security.NonceVerification.Recommended
						if ( ( isset( $_GET[ $selector['field_name'] ] ) ) && ( ! empty( $_GET[ $selector['field_name'] ] ) ) ) {

							// phpcs:ignore WordPress.Security.NonceVerification.Recommended
							$selector['selected'] = sanitize_text_field( wp_unslash( $_GET[ $selector['field_name'] ] ) );
							if ( ( isset( $selector['selector_value_function'] ) ) && ( ! empty( $selector['selector_value_function'] ) ) && ( is_callable( $selector['selector_value_function'] ) ) ) {
								$selector['selected'] = call_user_func( $selector['selector_value_function'], $selector['selected'], $selector );
							}

							/**
							 * Filter to allow override of selected value for Selector.
							 *
							 * @since 3.2.3
							 *
							 * @param string $selected_value Selected value for Selector.
							 * @param array $selector       Array for Selector.
							 */
							$selector['selected'] = apply_filters( 'learndash_listing_selector_value', $selector['selected'], $selector );
						}
					}
				}
			}
		}

		/**
		 * Fills the selector values from the AJAX request data.
		 *
		 * @since 3.2.3
		 *
		 * @param array $selector_filters Array of selector nonce keys and values.
		 */
		protected function fill_selectors_values_ajax( $selector_filters = array() ) {
			if ( ! empty( $this->selectors ) ) {
				foreach ( $this->selectors as $key => &$selector ) {
					if ( ( isset( $selector['nonce'] ) ) && ( ! empty( $selector['nonce'] ) ) ) {
						if ( ( isset( $selector_filters[ $selector['nonce'] ] ) ) && ( ! empty( $selector_filters[ $selector['nonce'] ] ) ) ) {
							$selector['selected'] = esc_attr( $selector_filters[ $selector['nonce'] ] );

							if ( ( isset( $selector['selector_value_function'] ) ) && ( ! empty( $selector['selector_value_function'] ) ) && ( is_callable( $selector['selector_value_function'] ) ) ) {
								$selector['selected'] = call_user_func( $selector['selector_value_function'], $selector['selected'], $selector );
							}

							/**
							 * Filter to allow override of selected value for Selector.
							 *
							 * @since 3.2.3
							 *
							 * @param string $selected_value Selected value for Selector.
							 * @param array $selector       Array for Selector.
							 */
							$selector['selected'] = apply_filters( 'learndash_listing_selector_value', $selector['selected'], $selector );
						}
					}
				}
			}
		}

		/**
		 * Validate the selector value for Author.
		 *
		 * @since 3.2.3
		 *
		 * @param int   $user_id Selected User ID.
		 * @param array $selector  Array of attributes used to display the filter selector.
		 */
		protected function selector_value_for_author( $user_id = 0, $selector = array() ) {
			$user_id = absint( $user_id );
			if ( ( learndash_is_group_leader_user( get_current_user_id() ) ) && ( 'advanced' !== learndash_get_group_leader_manage_users() ) ) {
				$gl_user_ids = learndash_get_groups_administrators_users( get_current_user_id() );
				$gl_user_ids = array_map( 'absint', $gl_user_ids );
				if ( ( empty( $gl_user_ids ) ) || ( ! in_array( $user_id, $gl_user_ids, true ) ) ) {
					$user_id = 0;
				}
			}

			return $user_id;
		}

		/**
		 * Validate the selector value for Course.
		 *
		 * @since 3.2.3
		 *
		 * @param int   $group_id  Selected Group ID.
		 * @param array $selector  Array of attributes used to display the filter selector.
		 */
		protected function selector_value_for_group( $group_id = 0, $selector = array() ) {
			if ( ( isset( $selector['show_empty_value'] ) ) && ( $group_id !== $selector['show_empty_value'] ) ) {
				$group_id = absint( $group_id );

				if ( ( learndash_is_group_leader_user( get_current_user_id() ) ) && ( 'advanced' !== learndash_get_group_leader_manage_groups() ) ) {
					$gl_group_ids = learndash_get_administrators_group_ids( get_current_user_id() );
					$gl_group_ids = array_map( 'absint', $gl_group_ids );
					if ( ( empty( $gl_group_ids ) ) || ( ! in_array( $group_id, $gl_group_ids, true ) ) ) {
						$group_id = 0;
					}
				}
			}

			return $group_id;
		}

		/**
		 * Validate the selector value for Course.
		 *
		 * @since 3.2.3
		 *
		 * @param int   $course_id Selected Course ID.
		 * @param array $selector  Array of attributes used to display the filter selector.
		 */
		protected function selector_value_for_course( $course_id = 0, $selector = array() ) {
			if ( ( isset( $selector['show_empty_value'] ) ) && ( $course_id !== $selector['show_empty_value'] ) ) {
				$course_id = absint( $course_id );
				if ( ! empty( $course_id ) ) {
					if ( ( learndash_is_group_leader_user( get_current_user_id() ) ) && ( 'advanced' !== learndash_get_group_leader_manage_courses() ) ) {
						$gl_course_ids = learndash_get_groups_administrators_courses( get_current_user_id() );
						$gl_course_ids = array_map( 'absint', $gl_course_ids );
						if ( ( empty( $gl_course_ids ) ) || ( ! in_array( $course_id, $gl_course_ids, true ) ) ) {
							$course_id = 0;
						}
					}
				}
			}

			return $course_id;
		}

		/**
		 * Validate the selector value for Integer value.
		 *
		 * @since 3.2.3
		 *
		 * @param int   $value    Generic value to be formatted to integer.
		 * @param array $selector Array of attributes used to display the filter selector.
		 */
		protected function selector_value_integer( $value = 0, $selector = array() ) {
			if ( ( isset( $selector['show_empty_value'] ) ) && ( $value !== $selector['show_empty_value'] ) ) {
				$value = absint( $value );
			}

			return $value;
		}

		/**
		 * This function fill filter the table listing items based on filters selected.
		 * Called via 'parse_query' filter from WP.
		 *
		 * @since 3.2.3
		 *
		 * @param object $query WP_Query instance.
		 */
		public function parse_query_table_listing( $query ) {
			if ( ! $query->is_main_query() ) {
				return;
			}

			if ( $this->post_type_check() ) {
				$q_vars = &$query->query_vars;

				// First build a list of the filter values.
				$this->fill_selectors_values();

				if ( ! empty( $this->selectors ) ) {
					foreach ( $this->selectors as $post_type_key => &$selector ) {
						if ( ( isset( $selector['listing_query_function'] ) ) && ( ! empty( $selector['listing_query_function'] ) ) && ( is_callable( $selector['listing_query_function'] ) ) ) {
							$q_vars = call_user_func( $selector['listing_query_function'], $q_vars, $selector );
						}
					}
				}

				/**
				 * Allow external filters to make changes.
				 *
				 * @since 3.2.3
				 *
				 * @param array  $q_vars    Array of query vars.
				 * @param string $post_type Post Type being displayed.
				 * @param object $query     WP_Query instance.
				 */
				$q_vars = apply_filters( 'learndash_listing_table_query_vars_filter', $q_vars, $this->post_type, $query );
			}
		}

		/**
		 * Disable the default WordPress logic to display the post type taxonomies.
		 *
		 * @since 3.2.3
		 *
		 * @param bool   $disable_show_taxonomies Bypass check to not show default taxonomy filters.
		 * @param string $post_type               Post Type being displayed.
		 * @return bool.
		 */
		public function disable_categories_dropdown( $disable_show_taxonomies, $post_type = '' ) {
			if ( $this->post_type_check( $post_type ) ) {
				$disable_show_taxonomies = true;
			}

			return $disable_show_taxonomies;
		}

		/**
		 * Display selector filters above post table listing.
		 *
		 * @since 3.2.3
		 *
		 * @param string $post_type Post Type being displayed.
		 * @param string $location  Location of filter displayed. Will normally be 'top'.
		 */
		public function restrict_manage_posts_selectors( $post_type = '', $location = '' ) {
			if ( ! $this->post_type_check() ) {
				return;
			}

			if ( 'top' !== $location ) {
				return;
			}

			// phpcs:ignore WordPress.Security.NonceVerification.Recommended
			if ( ( isset( $_GET['post_status'] ) ) && ( 'trash' === $_GET['post_status'] ) ) {
				return;
			}

			if ( has_filter( 'learndash-admin-cpt-filters-display' ) ) {
				/**
				 * Filters list of CPT shown for a filter.
				 *
				 * @since 2.3.1
				 * @deprecated 3.2.3 Use {@see 'learndash_listing_selectors'} instead.
				 *
				 * @param array $cpt_filters_shown An array of custom post types shown filter.
				 */
				apply_filters_deprecated(
					'learndash-admin-cpt-filters-display',
					array( array() ),
					'3.2.3',
					'learndash_listing_selectors'
				);
			}

			$this->show_nonce_field();
			$this->show_early_selectors();
			$this->show_user_selectors();
			$this->show_taxonomy_selectors();
			$this->show_post_type_selectors();
			$this->show_late_selectors();
			$this->show_reset_button();
		}

		/**
		 * Utility function to get a Selector by key.
		 *
		 * @since 3.2.3
		 * @since 3.4.1 Added `$selector_field` parameter.
		 *
		 * @param string $selector_key Key for Selector.
		 * @param string $selector_field Optional selector field to
		 * return. If not provided the entire selector will be returned.
		 */
		protected function get_selector( $selector_key = '', $selector_field = '' ) {
			if ( ! empty( $selector_key ) ) {
				if ( isset( $this->selectors[ $selector_key ] ) ) {
					if ( ! empty( $selector_field ) ) {
						if ( isset( $this->selectors[ $selector_key ][ $selector_field ] ) ) {
							return $this->selectors[ $selector_key ][ $selector_field ];
						}
					} else {
						return $this->selectors[ $selector_key ];
					}
				}
			}
		}

		/**
		 * Output custom post column row data
		 *
		 * @since 3.2.3
		 *
		 * @param string  $column_name Column slug or row being displayed.
		 * @param integer $post_id     Post ID of row being displayed.
		 */
		public function manage_post_column_rows( $column_name = '', $post_id = 0 ) {
			$this->listing_init();
			if ( ! empty( $this->columns ) ) {
				foreach ( $this->columns as $column_key => $column ) {
					if ( $column_key === $column_name ) {
						if ( ( isset( $column['display'] ) ) && ( ! empty( $column['display'] ) ) && ( is_callable( $column['display'] ) ) ) {
							call_user_func( $column['display'], $post_id, $column_name );
						}
					}
				}
			}
		}

		/**
		 * Output the the table row actions output.
		 *
		 * This function is similar to the one used by WordPress on the
		 * list table for the post title actions.
		 *
		 * @since 3.2.3
		 *
		 * @param array   $actions        An array of table row actions.
		 * @param boolean $always_visible Optional. Whether the row will be always visible. Default false.
		 *
		 * @return string The table row actions HTML output.
		 */
		protected function list_table_row_actions( $actions, $always_visible = false ) {
			$action_count = count( $actions );
			$i            = 0;

			if ( ! $action_count ) {
				return '';
			}

			$out = '<div class="' . ( $always_visible ? 'row-actions visible' : 'row-actions' ) . '">';
			foreach ( $actions as $action => $link ) {
				++$i;
				( $i == $action_count ) ? $sep = '' : $sep = ' | ';
				$out                          .= "<span class='$action'>$link$sep</span>";
			}
			$out .= '</div>';

			$out .= '<button type="button" class="toggle-row"><span class="screen-reader-text">' . esc_html__( 'Show more details', 'learndash' ) . '</span></button>';

			return $out;
		}

		/**
		 * Manage the Post Taxonomies Columns
		 *
		 * @since 3.2.3
		 *
		 * @param array  $taxonomies Array of Taxonomies for the Post Type.
		 * @param string $post_type  Post Type displayed.
		 *
		 * @return array $taxonomies
		 */
		public function manage_taxonomies_columns( $taxonomies = array(), $post_type = '' ) {
			if ( ( ! empty( $taxonomies ) ) && ( $this->post_type_check( $post_type ) ) ) {
				foreach ( $taxonomies as $taxonomy_slug => $taxonomy ) {
					if ( $this->hide_empty_taxonomy( $taxonomy_slug ) ) {
						unset( $taxonomies[ $taxonomy_slug ] );
					}
				}
			}

			return $taxonomies;
		}

		/**
		 * Add post type column headers.
		 *
		 * @since 2.6.0
		 *
		 * @param array $columns Columns array passed from WordPress.
		 *
		 * @return array $columns Modified array with new columns.
		 */
		public function manage_column_headers( $columns = array() ) {
			$this->listing_init();
			if ( ! empty( $this->columns ) ) {
				foreach ( $this->columns as $column_key => $column ) {
					if ( ( isset( $column['after'] ) ) && ( ! empty( $column['after'] ) ) ) {
						$col_pos = array_search( $column['after'], array_keys( $columns ), true );
						if ( ( false !== $col_pos ) && ( $col_pos <= count( $columns ) ) ) {
							$columns = array_merge(
								array_slice( $columns, 0, $col_pos + 1 ),
								array( $column_key => $column['label'] ),
								array_slice( $columns, $col_pos )
							);
						} else {
							$columns[ $column_key ] = $column['label'];
						}
					} else {
						$columns[ $column_key ] = $column['label'];
					}
				}
			}

			return $columns;
		}

		/**
		 * Show Course column for Step.
		 *
		 * @since 3.2.3
		 *
		 * @param int   $post_id     The Step post ID shown.
		 * @param array $column_meta Array of column meta information.
		 */
		protected function show_column_step_course( $post_id = 0, $column_meta = array() ) {
			if ( ! empty( $post_id ) ) {
				/**
				 * The Course column is only shown when shared steps is not used. So it
				 * is safe to call get_post_meta() for the course_id.
				 */
				$course_id = get_post_meta( $post_id, 'course_id', true );
				if ( ! empty( $course_id ) ) {
					$course_post = get_post( $course_id );
					if ( ( $course_post ) && ( is_a( $course_post, 'WP_Post' ) ) ) {
						$course_title = learndash_format_step_post_title_with_status_label( $course_post );

						$row_actions = array();

						$filter_url = add_query_arg( 'course_id', $course_id, $this->get_clean_filter_url() );

						echo '<a href="' . esc_url( $filter_url ) . '" aria-label="' . esc_attr( $this->get_aria_label_for_post( $course_id, 'filter' ) ) . '">' . wp_kses_post( $course_title ) . '</a>';
						$row_actions['ld-post-filter'] = '<a href="' . esc_url( $filter_url ) . '" aria-label="' . esc_attr( $this->get_aria_label_for_post( $course_id, 'filter' ) ) . '">' . esc_html__( 'filter', 'learndash' ) . '</a>';

						if ( current_user_can( 'edit_post', $course_id ) ) {
							$row_actions['ld-post-edit'] = '<a href="' . esc_url( get_edit_post_link( $course_id ) ) . '" aria-label="' . esc_attr( $this->get_aria_label_for_post( $course_id, 'edit' ) ) . '">' . esc_html__( 'edit', 'learndash' ) . '</a>';
						}

						if ( is_post_type_viewable( get_post_type( $course_id ) ) ) {
							$row_actions['ld-post-view'] = '<a href="' . esc_url( get_permalink( $course_id ) ) . '" aria-label="' . esc_attr( $this->get_aria_label_for_post( $course_id, 'view' ) ) . '">' . esc_html__( 'view', 'learndash' ) . '</a>';
						}
						echo $this->list_table_row_actions( $row_actions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML
					}
				} elseif ( ( isset( $column_meta['required'] ) ) && ( true === $column_meta['required'] ) ) {
					echo '<span class="ld-error dashicons dashicons-warning" title="' . sprintf(
						// translators: placeholder: Course.
						esc_html_x( '%s Required', 'placeholder: Course', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'course' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
					) . '"></span>';
				}
			}
		}

		/**
		 * Show Lesson column for Step.
		 *
		 * @since 3.2.3
		 *
		 * @param int   $post_id     The Step post ID shown.
		 * @param array $column_meta Array of column meta information.
		 */
		protected function show_column_step_lesson( $post_id = 0, $column_meta = array() ) {
			if ( ! empty( $post_id ) ) {
				/**
				 * The Course column is only shown when shared steps is not used. So it
				 * is safe to call get_post_meta() for the lesson_id.
				 */
				$lesson_id = get_post_meta( $post_id, 'lesson_id', true );
				if ( ! empty( $lesson_id ) ) {
					$lesson_post = get_post( $lesson_id );
					if ( ( $lesson_post ) && ( is_a( $lesson_post, 'WP_Post' ) ) ) {
						$lesson_title = learndash_format_step_post_title_with_status_label( $lesson_post );

						$row_actions = array();
						$filter_url  = add_query_arg( 'lesson_id', $lesson_id, $this->get_clean_filter_url() );

						echo '<a href="' . esc_url( $filter_url ) . '" aria-label="' . esc_attr( $this->get_aria_label_for_post( $lesson_id, 'filter' ) ) . '">' . wp_kses_post( $lesson_title ) . '</a>';
						$row_actions['ld-post-filter'] = '<a href="' . esc_url( $filter_url ) . '" aria-label="' . esc_attr( $this->get_aria_label_for_post( $lesson_id, 'filter' ) ) . '">' . esc_html__( 'filter', 'learndash' ) . '</a>';

						$course_id = learndash_get_course_id( $lesson_id );
						if ( current_user_can( 'edit_post', $lesson_id ) ) {
							$edit_url = get_edit_post_link( $lesson_id );

							if ( ! empty( $course_id ) ) {
								$edit_url = add_query_arg( 'course_id', $course_id, $edit_url );
							}
							$row_actions['ld-post-edit'] = '<a href="' . esc_url( $edit_url ) . '" aria-label="' . esc_attr( $this->get_aria_label_for_post( $lesson_id, 'edit' ) ) . '">' . esc_html__( 'edit', 'learndash' ) . '</a>';
						}

						if ( is_post_type_viewable( get_post_type( $lesson_id ) ) ) {
							if ( ! empty( $course_id ) ) {
								$view_url = learndash_get_step_permalink( $lesson_id, $course_id );
							} else {
								$view_url = get_permalink( $lesson_id );
							}

							$row_actions['ld-post-view'] = '<a href="' . esc_url( $view_url ) . '" aria-label="' . esc_attr( $this->get_aria_label_for_post( $lesson_id, 'view' ) ) . '">' . esc_html__( 'view', 'learndash' ) . '</a>';
						}
						echo $this->list_table_row_actions( $row_actions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML
					}
				} elseif ( ( isset( $column_meta['required'] ) ) && ( true === $column_meta['required'] ) ) {
					echo '<span class="ld-error dashicons dashicons-warning" title="' . sprintf(
						// translators: placeholder: Lesson.
						esc_html_x( '%s Required', 'placeholder: Lesson', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'lesson' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
					) . '"></span>';
				}
			}
		}

		/**
		 * Show Topic column for Step.
		 *
		 * @since 3.2.3
		 *
		 * @param int   $post_id     The Step post ID shown.
		 * @param array $column_meta Array of column meta information.
		 */
		protected function show_column_step_topic( $post_id = 0, $column_meta = array() ) {
			if ( ! empty( $post_id ) ) {
				/**
				 * The Course column is only shown when shared steps is not used. So it
				 * is safe to call get_post_meta() for the topic_id.
				 */
				$topic_id = get_post_meta( $post_id, 'lesson_id', true );
				if ( ! empty( $topic_id ) ) {
					if ( learndash_get_post_type_slug( 'topic' ) === get_post_type( $topic_id ) ) {
						$row_actions = array();
						$filter_url  = add_query_arg( 'topic_id', $topic_id, $this->get_clean_filter_url() );

						echo '<a href="' . esc_url( $filter_url ) . '" aria-label="' . esc_attr( $this->get_aria_label_for_post( $topic_id, 'filter' ) ) . '">' . wp_kses_post( get_the_title( $topic_id ) ) . '</a>';
						$row_actions['ld-post-filter'] = '<a href="' . esc_url( $filter_url ) . '" aria-label="' . esc_attr( $this->get_aria_label_for_post( $topic_id, 'filter' ) ) . '">' . esc_html__( 'filter', 'learndash' ) . '</a>';

						$course_id = learndash_get_course_id( $topic_id );
						if ( current_user_can( 'edit_post', $topic_id ) ) {
							$edit_url = get_edit_post_link( $topic_id );

							if ( ! empty( $course_id ) ) {
								$edit_url = add_query_arg( 'course_id', $course_id, $edit_url );
							}
							$row_actions['ld-post-edit'] = '<a href="' . esc_url( $edit_url ) . '" aria-label="' . esc_attr( $this->get_aria_label_for_post( $topic_id, 'edit' ) ) . '">' . esc_html__( 'edit', 'learndash' ) . '</a>';
						}

						if ( is_post_type_viewable( get_post_type( $topic_id ) ) ) {
							if ( ! empty( $course_id ) ) {
								$view_url = learndash_get_step_permalink( $topic_id, $course_id );
							} else {
								$view_url = get_permalink( $topic_id );
							}
							$row_actions['ld-post-view'] = '<a href="' . esc_url( $view_url ) . '" aria-label="' . esc_attr( $this->get_aria_label_for_post( $topic_id, 'view' ) ) . '">' . esc_html__( 'view', 'learndash' ) . '</a>';
						}
						echo $this->list_table_row_actions( $row_actions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML
					}
				}
			} elseif ( ( isset( $column_meta['required'] ) ) && ( true === $column_meta['required'] ) ) {
				echo '<span class="ld-error dashicons dashicons-warning" title="' . sprintf(
					// translators: placeholder: Topic.
					esc_html_x( '%s Required', 'placeholder: Topic', 'learndash' ),
					LearnDash_Custom_Label::get_label( 'topic' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
				) . '"></span>';
			}
		}

		/**
		 * Show Lesson or Topic column for Step.
		 *
		 * @since 3.2.3
		 *
		 * @param int   $post_id     The Step post ID shown.
		 * @param array $column_meta Array of column meta information.
		 */
		protected function show_column_step_lesson_or_topic( $post_id = 0, $column_meta = array() ) {
			if ( ! empty( $post_id ) ) {
				$course_id = 0;
				$lesson_id = 0;
				$topic_id  = 0;

				$course_id = get_post_meta( $post_id, 'course_id', true );
				$lesson_id = get_post_meta( $post_id, 'lesson_id', true );
				if ( ! empty( $lesson_id ) ) {
					if ( learndash_get_post_type_slug( 'topic' ) === get_post_type( $lesson_id ) ) {
						$topic_id  = absint( $lesson_id );
						$lesson_id = 0;
						$lesson_id = get_post_meta( $topic_id, 'lesson_id', true );
						if ( ( empty( $lesson_id ) ) && ( ! empty( $course_id ) ) ) {
							$lesson_id = learndash_course_get_single_parent_step( $course_id, $topic_id );
						}
					}

					if ( ! empty( $lesson_id ) ) {
						$lesson_post = get_post( $lesson_id );
						if ( ( $lesson_post ) && ( is_a( $lesson_post, 'WP_Post' ) ) ) {
							$lesson_title = learndash_format_step_post_title_with_status_label( $lesson_post );

							$lesson_row_actions = array();

							$filter_url = add_query_arg( 'lesson_id', $lesson_id, $this->get_clean_filter_url() );
							$course_id  = learndash_get_course_id( $lesson_id );
							if ( ! empty( $course_id ) ) {
								$filter_url = add_query_arg( 'course_id', $course_id, $filter_url );
							}

							echo sprintf(
								// translators: Placeholders: Lesson label, Lesson Filter Anchor.
								esc_html_x( '%1$s: %2$s', 'Placeholders: Lesson label, Lesson Filter Anchor', 'learndash' ),
								LearnDash_Custom_Label::get_label( 'lesson' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
								'<a href="' . esc_url( $filter_url ) . '" aria-label="' . esc_attr( $this->get_aria_label_for_post( $lesson_id, 'filter' ) ) . '">' . wp_kses_post( $lesson_title ) . '</a>'
							);

							$lesson_row_actions['ld-post-filter'] = '<a href="' . esc_url( $filter_url ) . '" aria-label="' . esc_attr( $this->get_aria_label_for_post( $lesson_id, 'filter' ) ) . '">' . esc_html__( 'filter', 'learndash' ) . '</a>';

							if ( current_user_can( 'edit_post', $lesson_id ) ) {
								$edit_url = get_edit_post_link( $lesson_id );

								if ( ! empty( $course_id ) ) {
									$edit_url = add_query_arg( 'course_id', $course_id, $edit_url );
								}

								$lesson_row_actions['ld-post-edit'] = '<a href="' . esc_url( $edit_url ) . '" aria-label="' . esc_attr( $this->get_aria_label_for_post( $lesson_id, 'edit' ) ) . '">' . esc_html__( 'edit', 'learndash' ) . '</a>';
							}

							if ( is_post_type_viewable( get_post_type( $lesson_id ) ) ) {
								if ( ! empty( $course_id ) ) {
									$view_url = learndash_get_step_permalink( $lesson_id, $course_id );
								} else {
									$view_url = get_permalink( $lesson_id );
								}

								$lesson_row_actions['ld-post-view'] = '<a href="' . esc_url( $view_url ) . '" aria-label="' . esc_attr( $this->get_aria_label_for_post( $lesson_id, 'view' ) ) . '">' . esc_html__( 'view', 'learndash' ) . '</a>';
							}
							echo $this->list_table_row_actions( $lesson_row_actions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML
						}
					}

					if ( ! empty( $topic_id ) ) {
						$topic_post = get_post( $topic_id );
						if ( ( $topic_post ) && ( is_a( $topic_post, 'WP_Post' ) ) ) {
							$topic_title = learndash_format_step_post_title_with_status_label( $topic_post );

							$topic_row_actions = array();

							$filter_url = add_query_arg( 'topic_id', $topic_id, $this->get_clean_filter_url() );
							$course_id  = learndash_get_course_id( $topic_id );
							if ( ! empty( $course_id ) ) {
								$filter_url = add_query_arg( 'course_id', $course_id, $filter_url );
							}
							if ( ! empty( $lesson_id ) ) {
								$filter_url = add_query_arg( 'lesson_id', $lesson_id, $filter_url );
							}

							echo sprintf(
								// translators: Placeholders: Topic label, Topic Filter Anchor.
								esc_html_x( '%1$s: %2$s', 'Placeholders: Topic label, Topic Filter Anchor', 'learndash' ),
								LearnDash_Custom_Label::get_label( 'topic' ), // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
								'<a href="' . esc_url( $filter_url ) . '" aria-label="' . esc_attr( $this->get_aria_label_for_post( $topic_id, 'filter' ) ) . '">' . wp_kses_post( $topic_title ) . '</a>'
							);

							$topic_row_actions['ld-post-filter'] = '<a href="' . esc_url( $filter_url ) . '" aria-label="' . esc_attr( $this->get_aria_label_for_post( $topic_id, 'filter' ) ) . '">' . esc_html__( 'filter', 'learndash' ) . '</a>';

							if ( current_user_can( 'edit_post', $topic_id ) ) {
								$edit_url = get_edit_post_link( $topic_id );

								if ( ! empty( $course_id ) ) {
									$edit_url = add_query_arg( 'course_id', $course_id, $edit_url );
								}

								$topic_row_actions['ld-post-edit'] = '<a href="' . esc_url( $edit_url ) . '" aria-label="' . esc_attr( $this->get_aria_label_for_post( $topic_id, 'edit' ) ) . '">' . esc_html__( 'edit', 'learndash' ) . '</a>';
							}

							if ( is_post_type_viewable( get_post_type( $topic_id ) ) ) {
								if ( ! empty( $course_id ) ) {
									$view_url = learndash_get_step_permalink( $topic_id, $course_id );
								} else {
									$view_url = get_permalink( $topic_id );
								}

								$topic_row_actions['ld-post-view'] = '<a href="' . esc_url( $view_url ) . '" aria-label="' . esc_attr( $this->get_aria_label_for_post( $topic_id, 'view' ) ) . '">' . esc_html__( 'view', 'learndash' ) . '</a>';
							}
							echo $this->list_table_row_actions( $topic_row_actions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML
						}
					}
				}
			}
		}

		/**
		 * Show Quiz column for Step.
		 *
		 * @since 3.2.3
		 *
		 * @param int   $post_id     The Step post ID shown.
		 * @param array $column_meta Array of column meta information.
		 */
		protected function show_column_step_quiz( $post_id = 0, $column_meta = array() ) {
			if ( ! empty( $post_id ) ) {
				$quiz_id = get_post_meta( $post_id, 'quiz_id', true );
				if ( ! empty( $quiz_id ) ) {
					$quiz_post = get_post( $quiz_id );
					if ( ( $quiz_post ) && ( is_a( $quiz_post, 'WP_Post' ) ) ) {
						$quiz_title = learndash_format_step_post_title_with_status_label( $quiz_post );

						$row_actions = array();
						$filter_url  = add_query_arg( 'quiz_id', $quiz_id, $this->get_clean_filter_url() );

						echo '<a href="' . esc_url( $filter_url ) . '" aria-label="' . esc_attr( $this->get_aria_label_for_post( $quiz_id, 'filter' ) ) . '">' . wp_kses_post( $quiz_title ) . '</a>';
						$row_actions['ld-post-filter'] = '<a href="' . esc_url( $filter_url ) . '" aria-label="' . esc_attr( $this->get_aria_label_for_post( $quiz_id, 'filter' ) ) . '">' . esc_html__( 'filter', 'learndash' ) . '</a>';

						$course_id = learndash_get_course_id( $quiz_id );
						if ( current_user_can( 'edit_post', $quiz_id ) ) {
							$edit_url = get_edit_post_link( $quiz_id );

							if ( ! empty( $course_id ) ) {
								$edit_url = add_query_arg( 'course_id', $course_id, $edit_url );
							}
							$row_actions['ld-post-edit'] = '<a href="' . esc_url( $edit_url ) . '" aria-label="' . esc_attr( $this->get_aria_label_for_post( $quiz_id, 'edit' ) ) . '">' . esc_html__( 'edit', 'learndash' ) . '</a>';
						}

						if ( is_post_type_viewable( get_post_type( $quiz_id ) ) ) {
							if ( ! empty( $course_id ) ) {
								$view_url = learndash_get_step_permalink( $quiz_id, $course_id );
							} else {
								$view_url = get_permalink( $quiz_id );
							}
							$row_actions['ld-post-view'] = '<a href="' . esc_url( $view_url ) . '" aria-label="' . esc_attr( $this->get_aria_label_for_post( $quiz_id, 'view' ) ) . '">' . esc_html__( 'view', 'learndash' ) . '</a>';
						}
						echo $this->list_table_row_actions( $row_actions ); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML
					}
				} elseif ( ( isset( $column_meta['required'] ) ) && ( true === $column_meta['required'] ) ) {
					echo '<span class="ld-error dashicons dashicons-warning" title="' . sprintf(
						// translators: placeholder: Quiz.
						esc_html_x( '%s Required', 'placeholder: Quiz', 'learndash' ),
						LearnDash_Custom_Label::get_label( 'quiz' ) // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Method escapes output
					) . '"></span>';
				}
			}
		}

		/***********************************************************
		 * SELECTOR OUTPUTS
		 ***********************************************************/

		/**
		 * Function to show early selectors.
		 *
		 * @since 2.6.0
		 */
		protected function show_early_selectors() {
			if ( $this->post_type_check() ) {
				if ( ! empty( $this->selectors ) ) {
					foreach ( $this->selectors as $selector_slug => $selector ) {
						if ( ( isset( $selector['type'] ) ) && ( 'early' === $selector['type'] ) ) {
							if ( ( isset( $selector['display'] ) ) && ( ! empty( $selector['display'] ) ) && ( is_callable( $selector['display'] ) ) ) {
								call_user_func( $selector['display'], $selector );
							} else {
								$this->show_early_selector( $selector );
							}
						}
					}
				}
			}
		}

		/**
		 * Function to show User selector.
		 *
		 * @since 3.2.3
		 */
		protected function show_user_selectors() {
			if ( $this->post_type_check() ) {
				if ( ! empty( $this->selectors ) ) {
					foreach ( $this->selectors as $selector_slug => $selector ) {
						if ( ( isset( $selector['type'] ) ) && ( 'user' === $selector['type'] ) ) {
							if ( ( isset( $selector['display'] ) ) && ( ! empty( $selector['display'] ) ) && ( is_callable( $selector['display'] ) ) ) {
								call_user_func( $selector['display'], $selector );
							} else {
								$this->show_user_selector( $selector );
							}
						}
					}
				}
			}
		}

		/**
		 * Display taxonomy selectors.
		 *
		 * @since 2.6.0
		 */
		protected function show_taxonomy_selectors() {
			if ( $this->post_type_check() ) {
				if ( ! empty( $this->selectors ) ) {
					foreach ( $this->selectors as $selector_slug => $selector ) {
						if ( ( isset( $selector['type'] ) ) && ( 'taxonomy' === $selector['type'] ) ) {
							if ( ( isset( $selector['display'] ) ) && ( ! empty( $selector['display'] ) ) && ( is_callable( $selector['display'] ) ) ) {
								call_user_func( $selector['display'], $selector );
							} else {
								$this->show_taxonomy_selector( $selector );
							}
						}
					}
				}
			}
		}

		/**
		 * Display post type selectors.
		 *
		 * @since 2.6.0
		 */
		protected function show_post_type_selectors() {
			if ( $this->post_type_check() ) {
				if ( ! empty( $this->selectors ) ) {
					foreach ( $this->selectors as $selector_slug => $selector ) {
						if ( ( isset( $selector['type'] ) ) && ( 'post_type' === $selector['type'] ) ) {
							if ( ( isset( $selector['display'] ) ) && ( ! empty( $selector['display'] ) ) && ( is_callable( $selector['display'] ) ) ) {
								call_user_func( $selector['display'], $selector );
							} else {
								$this->show_post_type_selector( $selector );
							}
						}
					}
				}
			}
		}

		/**
		 * Function to show late selectors.
		 *
		 * @since 2.6.0
		 */
		protected function show_late_selectors() {
			if ( $this->post_type_check() ) {
				if ( ! empty( $this->selectors ) ) {
					foreach ( $this->selectors as $selector_slug => $selector ) {
						if ( ( isset( $selector['type'] ) ) && ( 'late' === $selector['type'] ) ) {
							if ( ( isset( $selector['display'] ) ) && ( ! empty( $selector['display'] ) ) && ( is_callable( $selector['display'] ) ) ) {
								call_user_func( $selector['display'] );
							} else {
								$this->show_early_selector( $selector );
							}
						}
					}
				}
			}
		}

		/**
		 * Function to show the reset button
		 *
		 * @since 3.2.3
		 */
		protected function show_reset_button() {
			$redirect_url = add_query_arg( 'post_type', $this->post_type, admin_url( 'edit.php' ) );
			echo '<a href="' . esc_url( $redirect_url ) . '" class="button button-secondary" id="ld_filter_reset" style="margin: 0 5px 0 0;">' . esc_html__( 'Reset', 'learndash' ) . '</a>';
		}

		/**
		 * Shows early filters above the table listing.
		 *
		 * @since 2.6.0
		 *
		 * @param array $selector Array of attributes used to display the filter selector.
		 */
		protected function show_early_selector( $selector = array() ) {
			if ( $this->post_type_check() ) {
				$this->show_selector_start( $selector );
				$this->show_selector_all_option( $selector );
				$this->show_selector_empty_option( $selector );
				$this->show_selector_options( $selector );
				$this->show_selector_end( $selector );
			}
		}

		/**
		 * Shows taxonomy filter above the table listing.
		 *
		 * @since 2.6.0
		 *
		 * @param array $selector Array of attributes used to display the filter selector.
		 */
		protected function show_taxonomy_selector( $selector = array() ) {
			if ( ( ! isset( $selector['taxonomy'] ) ) || ( empty( $selector['taxonomy'] ) ) ) {
				return false;
			}

			$dropdown_args = array(
				'taxonomy'          => $selector['taxonomy'],
				'name'              => $selector['field_name'],
				'id'                => $selector['field_id'],
				'show_option_none'  => get_taxonomy( $selector['taxonomy'] )->labels->all_items,
				'option_none_value' => '',
				'hide_empty'        => isset( $selector['selector_hide_empty'] ) ? $selector['selector_hide_empty'] : false,
				'hierarchical'      => get_taxonomy( $selector['taxonomy'] )->hierarchical,
				'show_count'        => 0,
				'orderby'           => 'name',
				'value_field'       => 'slug',
				'selected'          => $selector['selected'],
				'echo'              => false,
			);
			/**
			 * Filters the taxonomy selector args.
			 *
			 * See args for wp_dropdown_categories.
			 *
			 * @since 3.2.3
			 *
			 * @param array  $dropdown_args Array of Dropdown args.
			 * @param array  $selector      Array of attributes used to display the filter selector.
			 * @param string $post_type     Listing table post type.
			 */
			$dropdown_args = apply_filters( 'learndash_listing_selector_taxonomy_args', $dropdown_args, $selector, $this->post_type );
			if ( ! empty( $dropdown_args ) ) {
				$dropdown_html = wp_dropdown_categories( $dropdown_args );
				if ( ! empty( $dropdown_html ) ) {

					if ( ( isset( $selector['field_label'] ) ) && ( ! empty( $selector['field_label'] ) ) ) {
						echo '<label class="screen-reader-text" for="' . esc_attr( $selector['field_id'] ) . '">' . esc_attr( $selector['field_label'] ) . '</label>';
					}

					if ( ( isset( $selector['select2'] ) ) && ( true === $selector['select2'] ) ) {
						$dropdown_html = str_replace( '<select ', '<select data-ld-select2="1" ', $dropdown_html );
					}

					echo $dropdown_html; // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Need to output HTML
				}
			}
		}

		/**
		 * Shows post type filter above the table listing.
		 *
		 * @since 3.2.3
		 *
		 * @param array $selector Array of attributes used to display the filter selector.
		 */
		protected function show_user_selector( $selector = array() ) {
			$selector = $this->build_user_selector_options( $selector );

			$this->show_selector_start( $selector );
			$this->show_selector_all_option( $selector );
			$this->show_selector_empty_option( $selector );
			$this->show_selector_options( $selector );
			$this->show_selector_end( $selector );
		}

		/**
		 * Shows post type filter above the table listing.
		 *
		 * @since 2.6.0
		 *
		 * @param array $selector Array of attributes used to display the filter selector.
		 */
		protected function show_post_type_selector( $selector = array() ) {
			if ( learndash_get_total_post_count( $selector['post_type'] ) !== 0 ) {
				$selector = $this->build_selector_post_type_options( $selector );

				$this->show_selector_start( $selector );
				$this->show_selector_all_option( $selector );
				$this->show_selector_empty_option( $selector );
				$this->show_selector_options( $selector );
				$this->show_selector_end( $selector );
			}
		}

		/**
		 * Show the Selector Start.
		 *
		 * @since 3.2.3
		 *
		 * @param array $selector Selector array.
		 */
		protected function show_selector_start( $selector = array() ) {
			if ( ( isset( $selector['field_name'] ) ) && ( ! empty( $selector['field_name'] ) ) ) {

				if ( ( isset( $selector['field_label'] ) ) && ( ! empty( $selector['field_label'] ) ) ) {
					echo '<label class="screen-reader-text" for="' . esc_attr( $selector['field_id'] ) . '">' . esc_attr( $selector['field_label'] ) . '</label>';
				}

				echo '<select name="' . esc_attr( $selector['field_name'] ) . '" ';

				if ( isset( $selector['field_id'] ) ) {
					echo ' id="' . esc_attr( $selector['field_id'] ) . '" ';
				}

				if ( ( isset( $selector['field_class'] ) ) && ( ! empty( $selector['field_class'] ) ) ) {
					$selector['field_class'] = 'postform ' . $selector['field_class'];
				} else {
					$selector['field_class'] = 'postform';
				}
				echo ' class="' . esc_attr( $selector['field_class'] ) . '" ';

				if ( true === $selector['select2'] ) {
					echo ' data-ld-select2="1" ';

					if ( ! isset( $selector['selector_filters'] ) ) {
						$selector['selector_filters'] = array();
					}

					if ( true === $selector['select2_fetch'] ) {
						$selector_filters_keys = array();
						if ( ! empty( $selector['selector_filters'] ) ) {
							foreach ( $selector['selector_filters'] as $sel_key ) {
								$sel_item = $this->get_selector( $sel_key );
								if ( ( $sel_item ) && ( isset( $sel_item['nonce'] ) ) && ( ! empty( $sel_item['nonce'] ) ) ) {
									$selector_filters_keys[] = $sel_item['nonce'];
								}
							}
						}

						$selector_ajax_data_json = $this->build_listing_select2_lib_ajax_fetch_json(
							array(
								'selector_key'     => $selector['nonce'],
								'selector_filters' => $selector_filters_keys,
							)
						);
						// phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
						echo ' data-ld-selector-query-data="' . $selector_ajax_data_json . '" ';
					}
				}
				echo ' data-ld-selector-nonce="' . esc_attr( $selector['nonce'] ) . '" ';
				echo ' >';
			}
		}

		/**
		 * Show the Selector All option.
		 *
		 * @since 3.2.3
		 *
		 * @param array $selector Selector array.
		 */
		protected function show_selector_all_option( $selector = array() ) {
			if ( isset( $selector['show_all_value'] ) ) {
				if ( ( isset( $selector['selected'] ) ) && ( $selector['show_all_value'] === $selector['selected'] ) ) {
					$all_selected = ' selected="selected" ';
				}
				echo '<option value="' . esc_attr( $selector['show_all_value'] ) . '" ' . esc_attr( $selector['show_all_value'] ) . '>' . esc_attr( $selector['show_all_label'] ) . '</option>';
			}
		}

		/**
		 * Show the Selector Empty option.
		 *
		 * @since 3.2.3
		 *
		 * @param array $selector Selector array.
		 */
		protected function show_selector_empty_option( $selector = array() ) {
			$empty_set = $this->get_selector_empty_set( $selector );
			if ( ! empty( $empty_set ) ) {
				$empty_selected = '';
				foreach ( $empty_set as $empty_val => $empty_label ) {
					if ( ( isset( $selector['selected'] ) ) && ( $empty_val === $selector['selected'] ) ) {
						$empty_selected = ' selected="selected" ';
					}
					echo '<option value="' . esc_attr( $empty_val ) . '" ' . esc_attr( $empty_selected ) . '>' . esc_attr( $empty_label ) . '</option>';
					break;
				}
			}
		}

		/**
		 * Get the Selector Empty value and label set.
		 *
		 * @since 3.2.3
		 *
		 * @param array $selector Selector array.
		 *
		 * @return array Array containing empty val and label.
		 */
		protected function get_selector_empty_set( $selector = array() ) {
			$empty_set = array();
			if ( ( isset( $selector['show_empty_value'] ) ) && ( ! empty( $selector['show_empty_value'] ) ) ) {
				if ( ( isset( $selector['show_empty_label'] ) ) && ( ! empty( $selector['show_empty_label'] ) ) ) {
					$empty_set[ esc_attr( $selector['show_empty_value'] ) ] = esc_attr( $selector['show_empty_label'] );
				}
			}

			return $empty_set;
		}

		/**
		 * Show the Selector options.
		 *
		 * @since 3.2.3
		 *
		 * @param array $selector Selector array.
		 */
		protected function show_selector_options( $selector = array() ) {
			if ( ! empty( $selector['options'] ) ) {
				foreach ( $selector['options'] as $key => $val ) {
					echo '<option value="' . esc_attr( $key ) . '" ' . selected( $key, $selector['selected'], false ) . '>' . esc_attr( $val ) . '</option>';
				}
			}
		}

		/**
		 * Show the Selector end.
		 *
		 * @since 3.2.3
		 *
		 * @param array $selector Selector array.
		 */
		protected function show_selector_end( $selector = array() ) {
			echo '</select>';
		}

		/**
		 * Get User Selector query options.
		 *
		 * @since 3.2.3
		 *
		 * @param array $selector Selector array.
		 */
		protected function build_user_selector_options( $selector = array() ) {
			$selector['options']       = array();
			$selector['pager_results'] = array(
				'total_items' => 0,
				'total_pages' => 1,
				'page'        => 1,
			);

			// If we don't have the query_args we don't know what to query. So abort.
			if ( ( ! isset( $selector['query_args'] ) ) || ( ! is_array( $selector['query_args'] ) ) ) {
				return $selector;
			}

			if ( ( true === $selector['select2_fetch'] ) && ( empty( $selector['selected'] ) ) ) {
				if ( ! $this->doing_ajax_fetch ) {
					return $selector;
				}
			}

			if ( ! empty( $selector['selected'] ) ) {
				if ( ! isset( $selector['query_args']['post__in'] ) ) {
					$selector['query_args']['include'] = array( $selector['selected'] );
				} else {
					$selector['query_args']['include'][] = $selector['selected'];
				}
			}

			$selector['query_results'] = $this->get_user_selector_query_results( $selector );
			if ( is_a( $selector['query_results'], 'WP_User_Query' ) ) {
				if ( ! empty( $selector['query_results']->get_results() ) ) {
					foreach ( $selector['query_results']->get_results() as $u ) {
						/**
						 * Filters the post listing items before displaying it to user.
						 *
						 * @since 3.2.3
						 *
						 * @param WP_User $u               WP_User object to be displayed.
						 * @param array   $query_arguments An array of selector query arguments.
						 * @param object  $query_results   WP_Query instance.
						 * @param string  $post_type       The post type of the screen shown.
						 */
						$u = apply_filters( 'learndash_listing_selector_user_option_before', $u, $selector['query_args'], $selector['query_results'], $this->post_type );
						if ( is_a( $u, 'WP_User' ) ) {
							$selector['options'][ absint( $u->ID ) ] = $u->display_name;

							/**
							 * Filters the selector options after.
							 *
							 * @since 3.2.3
							 *
							 * @param array   $options_after   Array of options to show after current option.
							 * @param WP_User $u               WP_User object to be displayed.
							 * @param array   $query_arguments An array of selector query arguments.
							 * @param object  $query_results   WP_Query instance.
							 * @param string  $post_type       The post type of the screen shown.
							 */
							$options_after = apply_filters( 'learndash_listing_selector_user_option_after', array(), $u, $selector['query_args'], $selector['query_results'], $this->post_type );
							if ( ! empty( $options_after ) ) {
								foreach ( $options_after as $after_key => $after_val ) {
									$selector['options'][ absint( $after_key ) ] = esc_attr( $after_val );
								}
							}
						}
					}
				}

				$selector['pager_results']['total_items'] = absint( $selector['query_results']->get_total() );

				if ( ( property_exists( $selector['query_results'], 'query_vars' ) ) && ( isset( $selector['query_results']->query_vars['number'] ) ) ) {
					if ( $selector['query_results']->query_vars['number'] > 0 ) {
						$selector['pager_results']['total_pages'] = ceil( $selector['pager_results']['total_items'] / absint( $selector['query_results']->query_vars['number'] ) );
					}
				}
			}

			return $selector;
		}

		/**
		 * Build Post Type Selector query options.
		 *
		 * @since 3.2.3
		 *
		 * @param array $selector Selector array.
		 *
		 * @return array $selector Selector array containing 'options'.
		 */
		protected function build_selector_post_type_options( $selector = array() ) {
			$selector['options']       = array();
			$selector['pager_results'] = array(
				'total_items' => 0,
				'total_pages' => 1,
				'page'        => 1,
			);

			// If we don't have the query_args we don't know what to query. So abort.
			if ( ( ! isset( $selector['query_args'] ) ) || ( ! is_array( $selector['query_args'] ) ) ) {
				return $selector;
			}

			if ( ( true === $selector['select2_fetch'] ) && ( empty( $selector['selected'] ) ) ) {
				if ( ! $this->doing_ajax_fetch ) {
					return $selector;
				}
			}

			if ( ! empty( $selector['selected'] ) ) {
				if ( ! isset( $selector['query_args']['post__in'] ) ) {
					$selector['query_args']['post__in'] = array( $selector['selected'] );
				} else {
					$selector['query_args']['post__in'][] = $selector['selected'];
				}
			}

			$selector['query_results'] = $this->get_selector_post_type_query_results( $selector );
			if ( is_a( $selector['query_results'], 'WP_Query' ) ) {
				if ( ! empty( $selector['query_results']->posts ) ) {
					foreach ( $selector['query_results']->posts as $p ) {
						if ( has_filter( 'learndash_post_listing_before_option' ) ) {
							/**
							 * Filters the post listing before displaying it to user.
							 *
							 * @since 3.0.0
							 * @deprecated 3.2.3 Use {@see 'learndash_listing_selector_post_type_option_before'} instead.
							 *
							 * @param WP_Post $post            WP_Post object to be displayed.
							 * @param array   $query_arguments An array of selector query arguments.
							 * @param array   $post_type       The post type of the screen shown.
							 */
							$p = apply_filters_deprecated(
								'learndash_post_listing_before_option',
								array( $p, $selector['query_args'], $this->post_type ),
								'3.2.3',
								'learndash_listing_selector_post_type_option_before'
							);
						}

						/**
						 * Filters the post listing before displaying it to user.
						 *
						 * @since 3.2.3
						 *
						 * @param WP_Post $post            WP_Post object to be displayed.
						 * @param array   $query_arguments An array of selector query arguments.
						 * @param object  $query_results   WP_Query instance.
						 * @param string  $post_type       The post type of the screen shown.
						 */
						$p = apply_filters( 'learndash_listing_selector_post_type_option_before', $p, $selector['query_args'], $selector['query_results'], $this->post_type );

						if ( is_a( $p, 'WP_Post' ) ) {
							$selector['options'][ absint( $p->ID ) ] = learndash_format_step_post_title_with_status_label( $p );

							if ( has_action( 'learndash_post_listing_after_option' ) ) {
								/**
								 * Fires after the admin post listing option.
								 *
								 * @since 3.0.0
								 * @deprecated 3.2.3 Use {@see 'learndash_listing_selector_post_type_option_after'} instead.
								 *
								 * @param WP_Post $post            WP_Post object.
								 * @param array   $query_arguments An array of admin post listing query arguments.
								 */
								do_action_deprecated(
									'learndash_post_listing_after_option',
									array( $p, $selector['query_args'], $this->post_type ),
									'3.2.3',
									'learndash_listing_selector_post_type_option_after'
								);
							}

							/**
							 * Filters the selector options after.
							 *
							 * @since 3.2.3
							 *
							 * @param array  $options_after   Array of options to show after current option.
							 * @param object $post            WP_Post object to be displayed.
							 * @param array  $query_arguments An array of selector query arguments.
							 * @param object $query_results   WP_Query instance.
							 * @param string $post_type       The post type of the screen shown.
							 */
							$options_after = apply_filters( 'learndash_listing_selector_post_type_option_after', array(), $p, $selector['query_args'], $selector['query_results'], $this->post_type );
							if ( ! empty( $options_after ) ) {
								foreach ( $options_after as $after_key => $after_val ) {
									$selector['options'][ absint( $after_key ) ] = esc_attr( $after_val );
								}
							}
						}
					}
				}

				if ( property_exists( $selector['query_results'], 'found_posts' ) ) {
					$selector['pager_results']['total_items'] = absint( $selector['query_results']->found_posts );
				}

				if ( property_exists( $selector['query_results'], 'max_num_pages' ) ) {
					$selector['pager_results']['total_pages'] = absint( $selector['query_results']->max_num_pages );
				}
			}

			return $selector;
		}

		/**
		 * Performs the query for the post type selector and returns the query result object.
		 *
		 * @since 3.2.3
		 *
		 * @param array $selector Selector array.
		 *
		 * @return object|bool WP_Query instance.
		 */
		protected function get_selector_post_type_query_results( $selector = array() ) {
			$query_args_default = array(
				'post_type'      => '',
				'post_status'    => 'any',
				'posts_per_page' => -1,
				'orderby'        => 'title',
				'order'          => 'ASC',
				'paged'          => 1,
			);

			$selector['query_args'] = wp_parse_args( $selector['query_args'], $query_args_default );

			if ( empty( $selector['query_args']['post_type'] ) ) {
				if ( ( isset( $selector['post_type'] ) ) && ( ! empty( $selector['post_type'] ) ) ) {
					$selector['query_args']['post_type'] = $selector['post_type'];
				} else {
					return false;
				}
			}

			if ( $this->doing_ajax_fetch ) {
				$selector['query_args']['posts_per_page'] = 10;
			}

			if ( ! $this->check_query_post_type_count( $selector['query_args']['post_type'] ) ) {
				return false;
			}

			if ( has_filter( 'learndash_course_post_options_filter' ) ) {
				/**
				 * Filters course filter query arguments.
				 *
				 * @since 2.2.1
				 * @deprecated 3.2.3 Use {@see 'learndash_listing_filter_query_args'} instead.
				 *
				 * @param array  $query_options_course An array of course filter query arguments.
				 * @param string $post_type            Post type to check.
				 */
				$selector['query_args'] = apply_filters_deprecated(
					'learndash_course_post_options_filter',
					array( $selector['query_args'], $this->post_type ),
					'3.2.3',
					'learndash_listing_filter_query_args'
				);
			}

			if ( has_filter( 'learndash_lesson_post_options_filter' ) ) {
				/**
				 * Filters course filter query arguments.
				 *
				 * @since 2.2.1
				 * @deprecated 3.2.3 Use {@see 'learndash_listing_filter_query_args'} instead.
				 *
				 * @param array  $query_options_topic An array of lesson filter query arguments.
				 * @param string $post_type            Post type to check.
				 */
				$selector['query_args'] = apply_filters_deprecated(
					'learndash_lesson_post_options_filter',
					array( $selector['query_args'], $this->post_type ),
					'3.2.3',
					'learndash_listing_filter_query_args'
				);
			}

			if ( has_filter( 'learndash_show_post_type_selector_filter' ) ) {

				/**
				 * Filters post type selector filter query arguments.
				 *
				 * @since 2.6.0
				 * @deprecated 3.2.3 Use {@see 'learndash_listing_filter_query_args'} instead.
				 *
				 * @param array $query_arguments An array of selector query arguments.
				 * @param array $post_types      An array of listing post types.
				 */
				$selector_args['query_args'] = apply_filters_deprecated(
					'learndash_show_post_type_selector_filter',
					array( $selector['query_args'], $this->post_type ),
					'3.2.3',
					'learndash_listing_filter_query_args'
				);
			}

			$selector['query_args'] = $this->selector_filters( $selector['query_args'], $selector );

			/**
			 * Filters post type selector filter query arguments.
			 *
			 * @since 3.2.3
			 *
			 * @param array  $query_arguments An array of selector query arguments.
			 * @param array  $selector        Selector array.
			 * @param string $post_type       The post type of the screen shown.
			 */
			$selector['query_args'] = apply_filters( 'learndash_listing_selector_post_type_query_args', $selector['query_args'], $selector, $this->post_type );

			$query_results = new WP_Query( $selector['query_args'] );

			if ( has_filter( 'learndash_post_listing_results_posts' ) ) {
				/**
				 * Filters post type listing filter posts results.
				 *
				 * @since 3.0.0
				 * @deprecated 3.2.3 Use {@see 'learndash_listing_selector_post_type_query_results'} instead.
				 *
				 * @param array  $posts           An array of post listing result posts.
				 * @param array  $query_arguments An array of selector query arguments.
				 * @param string $post_type       The post type of the screen shown.
				 */
				$query_results->posts = apply_filters_deprecated(
					'learndash_post_listing_results_posts',
					array( $query_results->posts, $selector['query_args'], $this->post_type ),
					'3.2.3',
					'learndash_listing_selector_post_type_query_results'
				);
			}

			/**
			 * Filters post type listing filter posts results.
			 *
			 * @since 3.2.3
			 *
			 * @param array  $posts           An array of post listing result posts.
			 * @param array  $query_arguments An array of selector query arguments.
			 * @param string $post_type       The post type of the screen shown.
			 */
			$query_results->posts = apply_filters( 'learndash_listing_selector_post_type_query_results', $query_results->posts, $selector['query_args'], $this->post_type );

			return $query_results;
		}

		/**
		 * Performs the query for the user selector and returns the query result object.
		 *
		 * @since 3.2.3
		 *
		 * @param array $selector Selector array.
		 *
		 * @return object WP_Query instance.
		 */
		protected function get_user_selector_query_results( $selector = array() ) {
			$query_args_default = array(
				'orderby'          => 'display_name',
				'order'            => 'ASC',
				'number'           => -1,
				'paged'            => 1,
				'suppress_filters' => true,
				'search_columns'   => array( 'ID', 'user_login', 'user_nicename', 'user_email' ),
			);

			$selector['query_args'] = wp_parse_args( $selector['query_args'], $query_args_default );

			if ( ( isset( $selector['query_args']['s'] ) ) && ( ! empty( $selector['query_args']['s'] ) ) ) {
				$selector['query_args']['search'] = '*' . $selector['query_args']['s'] . '*';
				unset( $selector['query_args']['s'] );
			}

			if ( ( isset( $selector['query_args']['include'] ) ) && ( ! empty( $selector['query_args']['include'] ) ) ) {
				$selector['query_args']['include'] = $selector['query_args']['include'];
			}

			if ( $this->doing_ajax_fetch ) {
				$selector['query_args']['number'] = 10;
			}

			$selector['query_args'] = $this->selector_filters( $selector['query_args'], $selector );

			/**
			 * Filters post type selector filter query arguments.
			 *
			 * @since 3.2.3
			 *
			 * @param array  $query_arguments An array of selector query arguments.
			 * @param array  $selector        Selector array.
			 * @param string $post_type       The post type of the screen shown.
			 */
			$selector['query_args'] = apply_filters( 'learndash_listing_selector_user_selector_query_args', $selector['query_args'], $selector, $this->post_type );
			$query_results          = new WP_User_Query( $selector['query_args'] );

			/**
			 * Filters post type listing filter posts results.
			 *
			 * @since 3.2.3
			 *
			 * @param array  $posts           An array of post listing result posts.
			 * @param array  $query_arguments An array of selector query arguments.
			 * @param string $post_type       The post type of the screen shown.
			 */
			$query_results->results = apply_filters( 'learndash_listing_selector_user_query_results', $query_results->results, $selector['query_args'], $this->post_type ); // @phpstan-ignore-line

			return $query_results;
		}

		/***********************************************************
		 * SELECTOR FILTERS
		 ***********************************************************/

		/**
		 * Call the Selector filter if set.
		 *
		 * @since 3.2.3
		 *
		 * @param array $q_vars   Query Args array.
		 * @param array $selector Selector array.
		 * @return array $q_vars  Query Args array.
		 */
		protected function selector_filters( $q_vars = array(), $selector = array() ) {
			if ( ( isset( $selector['selector_filter_function'] ) ) && ( ! empty( $selector['selector_filter_function'] ) ) && ( is_callable( $selector['selector_filter_function'] ) ) ) {
				$q_vars = call_user_func( $selector['selector_filter_function'], $q_vars, $selector );
			}
			return $q_vars;
		}

		/**
		 * Author Selector Filter.
		 *
		 * @since 3.2.3
		 *
		 * @param array $q_vars   Array of query vars.
		 * @param array $selector Selector array.
		 * @return array $q_vars   Query Args array.
		 */
		protected function selector_filter_for_author( $q_vars = array(), $selector = array() ) {
			if ( ( learndash_is_group_leader_user( get_current_user_id() ) ) && ( 'advanced' !== learndash_get_group_leader_manage_users() ) ) {
				$gl_user_ids = learndash_get_groups_administrators_users( get_current_user_id() );
				if ( ! empty( $gl_user_ids ) ) {
					$q_vars['include'] = $gl_user_ids;
				} else {
					$q_vars['include'] = array( 0 );
				}
			}

			return $q_vars;
		}

		/**
		 * Group Selector Filter.
		 *
		 * @since 3.2.3
		 *
		 * @param array $q_vars   Array of query vars.
		 * @param array $selector Selector array.
		 *
		 * @return array $q_vars  Query Args array.
		 */
		protected function selector_filter_for_group( $q_vars = array(), $selector = array() ) {
			if ( ( learndash_is_group_leader_user( get_current_user_id() ) ) && ( 'advanced' !== learndash_get_group_leader_manage_groups() ) ) {
				$gl_group_ids = learndash_get_administrators_group_ids( get_current_user_id() );
				if ( ! empty( $gl_group_ids ) ) {
					$q_vars['post__in'] = $gl_group_ids;
				} else {
					$q_vars['post__in'] = array( 0 );
				}
			}

			return $q_vars;
		}

		/**
		 * Course Selector Filter.
		 *
		 * @since 3.2.3
		 *
		 * @param array $q_vars   Array of query vars.
		 * @param array $selector Selector array.
		 *
		 * @return array $q_vars  Query Args array.
		 */
		protected function selector_filter_for_course( $q_vars = array(), $selector = array() ) {
			$group_selector = $this->get_selector( 'group_id' );
			if ( ( $group_selector ) && ( isset( $group_selector['selected'] ) ) && ( ! empty( $group_selector['selected'] ) ) ) {
				$group_course_ids = learndash_group_enrolled_courses( absint( $group_selector['selected'] ) );
				$group_course_ids = array_map( 'absint', $group_course_ids );
				if ( ! empty( $group_course_ids ) ) {
					$q_vars['post__in'] = $group_course_ids;
				} else {
					$q_vars['post__in'] = array( 0 );
				}
			} else {
				if ( ( learndash_is_group_leader_user( get_current_user_id() ) ) && ( 'advanced' !== learndash_get_group_leader_manage_courses() ) ) {
					$gl_course_ids = learndash_get_groups_administrators_courses( get_current_user_id() );
					if ( ! empty( $gl_course_ids ) ) {
						$q_vars['post__in'] = $gl_course_ids;
					} else {
						$q_vars['post__in'] = array( 0 );
					}
				}
			}

			return $q_vars;
		}

		/**
		 * Lesson Selector Filter.
		 *
		 * @since 3.2.3
		 *
		 * @param array $q_vars   Array of query vars.
		 * @param array $selector Selector array.
		 *
		 * @return array $q_vars  Query Args array.
		 */
		protected function selector_filter_for_lesson( $q_vars = array(), $selector = array() ) {
			global $sfwd_lms;

			$course_id = (int) $this->get_selector( 'course_id', 'selected' );
			if ( ! empty( $course_id ) ) {
				$lessons_items = $sfwd_lms->select_a_lesson_or_topic( absint( $course_id ), false, false );
				if ( ! empty( $lessons_items ) ) {
					$q_vars['post__in'] = array_keys( $lessons_items );
				} else {
					$q_vars['post__in'] = array( 0 );
				}
			} else {
				$q_vars['post__in'] = array( 0 );
			}

			return $q_vars;
		}

		/**
		 * Topic Selector Filter.
		 *
		 * @since 3.2.3
		 *
		 * @param array $q_vars   Array of query vars.
		 * @param array $selector Selector array.
		 *
		 * @return array $q_vars  Query Args array.
		 */
		protected function selector_filter_for_topic( $q_vars = array(), $selector = array() ) {
			$course_id = (int) $this->get_selector( 'course_id', 'selected' );
			$lesson_id = (int) $this->get_selector( 'lesson_id', 'selected' );
			if ( ( ! empty( $course_id ) ) && ( ! empty( $lesson_id ) ) ) {
				$topics_array = learndash_get_topic_list( $lesson_id, $course_id );
				if ( ! empty( $topics_array ) ) {
					$q_vars['post__in'] = wp_list_pluck( $topics_array, 'ID' );
				} else {
					$q_vars['post__in'] = array( 0 );
				}
			} else {
				$q_vars['post__in'] = array( 0 );
			}

			return $q_vars;
		}

		/**
		 * Filter listing by Taxonomy.
		 *
		 * @since 3.2.3
		 *
		 * @param array $q_vars   Query vars used for the table .
		 * @param array $selector Array of attributes used to display the filter selector.
		 *
		 * @return array $q_vars.
		 */
		protected function listing_filter_by_taxonomy( $q_vars = array(), $selector = array() ) {
			if ( ( isset( $selector['selected'] ) ) && ( ! empty( $selector['selected'] ) ) ) {
				$tax_term = get_term_by( 'slug', $selector['selected'], $selector['taxonomy'] );
				if ( ( ! empty( $tax_term ) ) && ( is_a( $tax_term, 'WP_Term' ) ) ) {
					$q_vars[ $selector['query_arg'] ] = $tax_term->slug;
				}
			}

			return $q_vars;
		}

		/***********************************************************
		 * LISTING FILTERS
		 ***********************************************************/

		/**
		 * Filter the main query listing by the group_id
		 *
		 * @since 3.2.3
		 *
		 * @param  object $q_vars   Query vars used for the table listing.
		 * @param  array  $selector Array of attributes used to display the filter selector.
		 *
		 * @return object $q_vars.
		 */
		protected function listing_filter_by_group( $q_vars, $selector = array() ) {
			if ( ( isset( $selector['selected'] ) ) && ( ! empty( $selector['selected'] ) ) ) {
				if ( ( isset( $selector['show_empty_value'] ) ) && ( $selector['show_empty_value'] === $selector['selected'] ) ) {
					$course_ids = learndash_get_all_courses_with_groups();
					if ( ! empty( $course_ids ) ) {
						$q_vars['post__not_in'] = $course_ids;
					} else {
						$q_vars['post__in'] = array( 0 );
					}
				} else {
					if ( ! isset( $q_vars['meta_query'] ) ) {
						$q_vars['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					}

					$course_ids = learndash_group_enrolled_courses( absint( $selector['selected'] ) );
					if ( ! empty( $course_ids ) ) {
						if ( learndash_get_post_type_slug( 'course' ) === $this->post_type ) {
							$q_vars['post__in'] = $course_ids;
						} else {
							$q_vars['meta_query'][] = array(
								'key'     => 'course_id',
								'compare' => 'IN',
								'value'   => $course_ids,
							);
						}
					}
				}
			}

			return $q_vars;
		}

		/**
		 * Filter the main query listing by the course_id
		 *
		 * @since 3.2.3
		 *
		 * @param  object $q_vars   Query vars used for the table listing.
		 * @param  array  $selector Array of attributes used to display the filter selector.
		 *
		 * @return object $q_vars.
		 */
		protected function listing_filter_by_course( $q_vars, $selector = array() ) {
			if ( ( isset( $selector['selected'] ) ) && ( ! empty( $selector['selected'] ) ) ) {
				if ( ( isset( $selector['show_empty_value'] ) ) && ( $selector['show_empty_value'] === $selector['selected'] ) ) {
					if ( ! isset( $q_vars['meta_query'] ) ) {
						$q_vars['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					}
					$q_vars['meta_query'][] = array(
						'relation' => 'OR',
						array(
							'key'     => 'course_id',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => 'course_id',
							'value'   => '0',
							'compare' => '=',
						),
					);
				} else {
					$course_query_args   = array( 'relation' => 'OR' );
					$course_query_args[] = array(
						'key'     => 'course_id',
						'value'   => absint( $selector['selected'] ),
						'compare' => '=',
					);

					if ( learndash_is_course_shared_steps_enabled() ) {
						$course_query_args[] = array(
							'key'     => 'ld_course_' . absint( $selector['selected'] ),
							'value'   => absint( $selector['selected'] ),
							'compare' => '=',
						);
					}

					if ( ! isset( $q_vars['meta_query'] ) ) {
						$q_vars['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					}
					$q_vars['meta_query'][] = $course_query_args;
				}
			}

			return $q_vars;
		}

		/**
		 * Filter the main query listing by the lesson_id
		 *
		 * @since 3.2.3
		 *
		 * @param  object $q_vars   Query vars used for the table listing.
		 * @param  array  $selector Array of attributes used to display the filter selector.
		 *
		 * @return object $q_vars.
		 */
		protected function listing_filter_by_lesson( $q_vars, $selector = array() ) {
			if ( ( isset( $selector['selected'] ) ) && ( ! empty( $selector['selected'] ) ) ) {
				if ( ( isset( $selector['show_empty_value'] ) ) && ( $selector['show_empty_value'] === $selector['selected'] ) ) {
					if ( ! isset( $q_vars['meta_query'] ) ) {
						$q_vars['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					}
					$q_vars['meta_query'][] = array(
						'relation' => 'OR',
						array(
							'key'     => 'lesson_id',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => 'lesson_id',
							'value'   => '0',
							'compare' => '=',
						),
					);
				} else {
					$course_id       = 0;
					$course_selector = $this->get_selector( 'course_id' );
					if ( ( $course_selector ) && ( isset( $course_selector['selected'] ) ) && ( ! empty( $course_selector['selected'] ) ) ) {
						$course_id = $course_selector['selected'];
					}

					if ( ! empty( $course_id ) ) {
						if ( learndash_is_course_shared_steps_enabled() ) {
							$steps_ids = learndash_course_get_children_of_step( $course_id, $selector['selected'], $this->post_type, 'ids', true );
							if ( ! empty( $steps_ids ) ) {
								if ( ( ! isset( $q_vars['post__in'] ) ) || ( empty( $q_vars['post__in'] ) ) ) {
									$q_vars['post__in'] = $steps_ids;
								} else {
									$q_vars['post__in'] = array_intersect( $q_vars['post__in'], $steps_ids );
									if ( empty( $q_vars['post__in'] ) ) {
										$q_vars['post__in'] = array( 0 );
									}
								}
							} else {
								$q_vars['post__in'] = array( 0 );
							}
						} else {
							/**
							 * For Quiz listing if the Lesson selector is used we can't do a simple query based
							 * on the 'lesson_id' filter. This is because in the post_meta the 'lesson_id' might
							 * reference a Topic post. So we need to include logic to query not just the 'lesson_id'
							 * values but also include all related topics.
							 */
							if ( in_array( $this->post_type, learndash_get_post_type_slug( array( 'quiz', 'essay', 'assignment' ) ), true ) ) {
								// First we get the course lessons and check if the lesson selector value is a valid lesson within the course.
								$course_lessons_ids = array();
								$course_lessons     = learndash_get_lesson_list( $course_selector['selected'] );
								if ( ! empty( $course_lessons ) ) {
									$course_lessons_ids = wp_list_pluck( (array) $course_lessons, 'ID' );
								}

								if ( ( ! empty( $course_lessons_ids ) ) && ( in_array( $selector['selected'], $course_lessons_ids, true ) ) ) {
									if ( ! isset( $q_vars['meta_query'] ) ) {
										$q_vars['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
									}

									$q_vars['meta_query'][] = array(
										'key'   => 'lesson_id',
										'value' => $selector['selected'],
									);
								}

								/**
								 * Within the listing_filter_by_topic() function if the topic selector is not set
								 * then the lesson topic ids will be added to the 'lesson_id' meta_query.
								 */
							} else {
								if ( ! isset( $q_vars['meta_query'] ) ) {
									$q_vars['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
								}

								$q_vars['meta_query'][] = array(
									'key'   => 'lesson_id',
									'value' => $selector['selected'],
								);
							}
						}
					}
				}
			}

			return $q_vars;
		}

		/**
		 * Filter the main query listing by the topic_id
		 *
		 * @since 3.2.3
		 *
		 * @param  object $q_vars   Query vars used for the table listing.
		 * @param  array  $selector Array of attributes used to display the filter selector.
		 *
		 * @return object $q_vars.
		 */
		protected function listing_filter_by_topic( $q_vars, $selector = array() ) {
			if ( ( isset( $selector['selected'] ) ) && ( ! empty( $selector['selected'] ) ) ) {
				$course_id = 0;
				$lesson_id = 0;

				$course_selector = $this->get_selector( 'course_id' );
				if ( ( $course_selector ) && ( isset( $course_selector['selected'] ) ) && ( ! empty( $course_selector['selected'] ) ) ) {
					$course_id = $course_selector['selected'];
				}

				$lesson_selector = $this->get_selector( 'lesson_id' );
				if ( ( $lesson_selector ) && ( isset( $lesson_selector['selected'] ) ) && ( ! empty( $lesson_selector['selected'] ) ) ) {
					$lesson_id = $lesson_selector['selected'];
				}

				if ( ( ! empty( $course_id ) ) && ( ! empty( $lesson_id ) ) ) {
					if ( learndash_is_course_shared_steps_enabled() ) {
						$steps_ids              = learndash_course_get_children_of_step( $course_id, $selector['selected'], $this->post_type, 'ids', true );
						$ld_course_steps_object = LDLMS_Factory_Post::course_steps( $course_selector['selected'] );
						if ( ! empty( $steps_ids ) ) {
							if ( ( ! isset( $q_vars['post__in'] ) ) || ( empty( $q_vars['post__in'] ) ) ) {
								$q_vars['post__in'] = $steps_ids;
							} else {
								$q_vars['post__in'] = array_intersect( $q_vars['post__in'], $steps_ids );
								if ( empty( $q_vars['post__in'] ) ) {
									$q_vars['post__in'] = array( 0 );
								}
							}
						} else {
							$q_vars['post__in'] = array( 0 );
						}
					} else {

						if ( ! isset( $q_vars['meta_query'] ) ) {
							$q_vars['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
						} else {
							$lesson_item_found = false;
							foreach ( $q_vars['meta_query'] as $meta_idx => &$meta_item ) {
								if ( ( isset( $meta_item['key'] ) ) && ( 'lesson_id' === $meta_item['key'] ) ) {
									$lesson_item_found  = true;
									$meta_item['value'] = absint( $selector['selected'] );
									break;
								}
							}
							if ( ! $lesson_item_found ) {
								$q_vars['meta_query'][] = array(
									'key'   => 'lesson_id',
									'value' => absint( $selector['selected'] ),
								);
							}
						}
					}
				}
			} elseif ( in_array( $this->post_type, learndash_get_post_type_slug( array( 'quiz', 'essay', 'assignment' ) ), true ) ) {
				if ( ! learndash_is_course_shared_steps_enabled() ) {
					$course_id = 0;
					$lesson_id = 0;

					$course_selector = $this->get_selector( 'course_id' );
					if ( ( $course_selector ) && ( isset( $course_selector['selected'] ) ) && ( ! empty( $course_selector['selected'] ) ) ) {
						$course_id = $course_selector['selected'];
					}

					$lesson_selector = $this->get_selector( 'lesson_id' );
					if ( ( $lesson_selector ) && ( isset( $lesson_selector['selected'] ) ) && ( ! empty( $lesson_selector['selected'] ) ) ) {
						$lesson_id = $lesson_selector['selected'];
					}

					if ( ( ! empty( $course_id ) ) && ( ! empty( $lesson_id ) ) ) {
						$topics = learndash_course_get_topics( $course_id, $lesson_id );
						if ( ! empty( $topics ) ) {
							$topic_ids = wp_list_pluck( $topics, 'ID' );

							$lesson_item_found = false;
							foreach ( $q_vars['meta_query'] as $meta_idx => &$meta_item ) {
								if ( ( isset( $meta_item['key'] ) ) && ( 'lesson_id' === $meta_item['key'] ) ) {
									$lesson_item_found    = true;
									$meta_item['compare'] = 'in';
									if ( is_array( $meta_item['value'] ) ) {
										$meta_item['value'] = array_merge( $meta_item['value'], $topic_ids );
									} elseif ( ( is_string( $meta_item['value'] ) ) || ( is_int( $meta_item['value'] ) ) ) {
										$meta_item['value'] = array_merge( array( $meta_item['value'] ), $topic_ids );
									}

									break;
								}
							}
							if ( ! $lesson_item_found ) {
								$q_vars['meta_query'][] = array(
									'key'     => 'lesson_id',
									'compare' => 'in',
									'value'   => $topic_ids,
								);
							}
						}
					}
				}
			}

			return $q_vars;
		}

		/**
		 * Filter the main query listing by the quiz_id
		 *
		 * @since 3.2.3
		 *
		 * @param  object $q_vars   Query vars used for the table listing.
		 * @param  array  $selector Array of attributes used to display the filter selector.
		 *
		 * @return object $q_vars.
		 */
		protected function listing_filter_by_quiz( $q_vars, $selector = array() ) {
			if ( ( isset( $selector['selected'] ) ) && ( ! empty( $selector['selected'] ) ) ) {
				if ( ( isset( $selector['show_empty_value'] ) ) && ( $selector['show_empty_value'] === $selector['selected'] ) ) {
					if ( ! isset( $q_vars['meta_query'] ) ) {
						$q_vars['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					}
					$q_vars['meta_query'][] = array(
						'relation' => 'OR',
						array(
							'key'     => 'quiz_id',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => 'quiz_id',
							'value'   => '0',
							'compare' => '=',
						),
					);
				} else {
					if ( ! isset( $q_vars['meta_query'] ) ) {
						$q_vars['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					}

					$q_vars['meta_query'][] = array(
						'key'   => 'quiz_id',
						'value' => absint( $selector['selected'] ),
					);
				}
			}

			return $q_vars;
		}

		/**
		 * Filter the main query listing by the certificate_id
		 *
		 * @since 3.4.2
		 *
		 * @param  object $q_vars   Query vars used for the table listing.
		 * @param  array  $selector Array of attributes used to display the filter selector.
		 *
		 * @return object $q_vars.
		 */
		protected function listing_filter_by_certificate( $q_vars, $selector = array() ) {
			if ( ( isset( $selector['selected'] ) ) && ( ! empty( $selector['selected'] ) ) ) {
				if ( ( isset( $selector['show_empty_value'] ) ) && ( $selector['show_empty_value'] === $selector['selected'] ) ) {
					if ( ! isset( $q_vars['meta_query'] ) ) {
						$q_vars['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					}
					$q_vars['meta_query'][] = array(
						'relation' => 'OR',
						array(
							'key'     => '_ld_certificate',
							'compare' => 'NOT EXISTS',
						),
						array(
							'key'     => '_ld_certificate',
							'value'   => array( '0', '' ),
							'compare' => 'IN',
						),
					);
				} else {
					if ( ! isset( $q_vars['meta_query'] ) ) {
						$q_vars['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					}

					$q_vars['meta_query'][] = array(
						'key'     => '_ld_certificate',
						'value'   => absint( $selector['selected'] ),
						'compare' => '=',
					);
				}
			}

			return $q_vars;
		}

		/**
		 * Check primary course ID for the current step
		 *
		 * @since 3.0.0
		 *
		 * @param array   $row_actions Existing Row actions for course.
		 * @param WP_Post $post        Course Post object for current row.
		 *
		 * @return array $row_actions
		 */
		public function post_row_actions( $row_actions = array(), $post = null ) {
			if ( $this->post_type_check() ) {
				// Set the Primary Course for the post.
				learndash_check_primary_course_for_step( $post->ID );
			}

			return $row_actions;
		}

		/**
		 * Utility function to get a clean URL used for filtering.
		 *
		 * @since 3.2.3
		 */
		protected function get_clean_filter_url() {
			$ignored_params = array( 'action', 'action2', 'filter_action', 'paged', 'ld-post-listing-nonce', 'essay_points' );

			$removed_params = array();
			if ( ( ! empty( $_GET ) ) && ( is_array( $_GET ) ) ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
				foreach ( $_GET as $key => $val ) { // phpcs:ignore WordPress.Security.NonceVerification.Recommended
					$key = trim( $key );
					if ( in_array( $key, $ignored_params, true ) ) {
						$removed_params[] = $key;
						continue;
					}
					if ( '' === $val ) {
						$removed_params[] = $key;
						continue;
					}
					if ( ( 'm' === $key ) && ( empty( $val ) ) ) {
						$removed_params[] = $key;
						continue;
					}
				}
			}

			$url = remove_query_arg( $removed_params );

			return $url;
		}

		/**
		 * Gets the posts count from the `WP_Query` post_type argument.
		 *
		 * @since 3.2.3
		 *
		 * @param string|array $post_type Post type to check.
		 *
		 * @return int Number of posts for a post type.
		 */
		protected function check_query_post_type_count( $post_type = '' ) {
			$total_post_count = 0;
			if ( ! empty( $post_type ) ) {
				if ( is_string( $post_type ) ) {
					$total_post_count += learndash_get_total_post_count( $post_type );
				} elseif ( is_array( $post_type ) ) {
					foreach ( $post_type as $pt ) {
						$total_post_count += learndash_get_total_post_count( $pt );
					}
				}
			}

			return $total_post_count;
		}

		/**
		 * Utility function to check if we can hide empty taxonomy selectors and columns elements.
		 *
		 * @since 3.2.3
		 *
		 * @param string $taxonomy_slug Valid taxonomy slug.
		 *
		 * @return bool true to hide elements.
		 */
		protected function hide_empty_taxonomy( $taxonomy_slug = '' ) {
			$hide_empty = false;

			/**
			 * Filter to hide empty taxonomies.
			 *
			 * @since 3.2.3
			 *
			 * @param bool   $hide_empty    True if the taxonomy selector/column should be shown if empty.
			 * @param string $taxonomy_slug The taxonomy slug.
			 * @param string $post_type     The list table post type.
			 */
			if ( apply_filters( 'learndash_listing_taxonomies_hide_empty', true, $taxonomy_slug, $this->post_type ) ) {
				if ( ! wp_count_terms( $taxonomy_slug, array( 'hide_empty' => true ) ) ) {
					$hide_empty = true;
				}
			}

			return $hide_empty;
		}

		/**
		 * Adds our general use nonce field form field.
		 *
		 * @since 3.2.3
		 */
		protected function show_nonce_field() {
			?><input type="hidden" id="ld-listing-nonce" name="ld-listing-nonce" value="<?php echo esc_attr( $this->listing_nonce ); ?>" data-ld-listing-nonce="<?php echo esc_attr( $this->listing_nonce ); ?>" />
			<?php
		}

		/**
		 * Build the select2 fetch JSON data.
		 *
		 * @since 3.2.3
		 *
		 * @param array $field_settings Array of elements to turn into JSON.
		 *
		 * @return string encoded JSON data.
		 */
		protected function build_listing_select2_lib_ajax_fetch_json( $field_settings = array() ) {
			return htmlspecialchars( wp_json_encode( $field_settings, JSON_FORCE_OBJECT ) );
		}

		/**
		 * Build and return Aria label for post link.
		 *
		 * @since 3.2.3
		 *
		 * @param int    $post_id       Post ID.
		 * @param string $label_context Context for label.
		 *
		 * @return string Label.
		 */
		protected function get_aria_label_for_post( $post_id = 0, $label_context = '' ) {
			$aria_label = '';
			if ( ! empty( $post_id ) ) {
				switch ( $label_context ) {
					case 'filter':
						$aria_label = sprintf(
							// translators: placeholder: Post title.
							esc_html_x( 'Filter listing by "%s"', 'placeholder: Post title', 'learndash' ),
							get_the_title( $post_id )
						);
						break;

					case 'edit':
						$aria_label = sprintf(
							// translators: placeholder: Post title.
							esc_html_x( 'Edit "%s"', 'placeholder: Post title', 'learndash' ),
							get_the_title( $post_id )
						);
						break;

					case 'view':
						$aria_label = sprintf(
							// translators: placeholder: Post title.
							esc_html_x( 'View "%s"', 'placeholder: Post title', 'learndash' ),
							get_the_title( $post_id )
						);

						break;

					default:
						break;
				}
			}

			return $aria_label;
		}


		// End of functions.
	}
}

// Include the LearnDash table listing files here.
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-posts-listings/class-learndash-admin-courses-listing.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-posts-listings/class-learndash-admin-lessons-listing.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-posts-listings/class-learndash-admin-topics-listing.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-posts-listings/class-learndash-admin-quizzes-listing.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-posts-listings/class-learndash-admin-questions-listing.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-posts-listings/class-learndash-admin-groups-listing.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-posts-listings/class-learndash-admin-certificates-listing.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-posts-listings/class-learndash-admin-transactions-listing.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-posts-listings/class-learndash-admin-assignments-listing.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-posts-listings/class-learndash-admin-essays-listing.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-posts-listings/class-learndash-admin-users-listing.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-posts-listings/class-learndash-admin-exams-listing.php';
require_once LEARNDASH_LMS_PLUGIN_DIR . 'includes/admin/classes-posts-listings/class-learndash-admin-coupons-listing.php';
