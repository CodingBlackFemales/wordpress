<?php
/**
 * LearnDash Binary Selector Posts.
 *
 * @since 2.2.1
 * @package LearnDash\Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'Learndash_Binary_Selector_Posts' ) ) && ( class_exists( 'Learndash_Binary_Selector' ) ) ) {

	/**
	 * Class LearnDash Binary Selector Posts.
	 *
	 * @since 2.2.1
	 * @uses Learndash_Binary_Selector
	 */
	class Learndash_Binary_Selector_Posts extends Learndash_Binary_Selector {

		/**
		 * Public constructor for class
		 *
		 * @since 2.2.1
		 *
		 * @param array $args Array of arguments for class.
		 */
		public function __construct( $args = array() ) {

			// Set up the default query args for the Users.
			$defaults = array(
				'paged'               => 1,
				'posts_per_page'      => (int) LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'per_page' ),
				'orderby'             => 'title',
				'order'               => 'ASC',
				'ignore_sticky_posts' => true,
				'search'              => '',
			);

			if ( ( ! isset( $args['posts_per_page'] ) ) && ( isset( $args['number'] ) ) && ( ! empty( $args['number'] ) ) ) {
				$args['posts_per_page'] = $args['number'];
			}

			$args = wp_parse_args( $args, $defaults );

			parent::__construct( $args );

			if ( ( isset( $this->args['included_ids'] ) ) && ( ! empty( $this->args['included_ids'] ) ) ) {
				$this->args['include'] = $this->args['included_ids'];
			}
		}

		/**
		 * Get selector section items.
		 *
		 * @since 2.2.1
		 *
		 * @param string $position Value for 'left' or 'right' position.
		 */
		protected function query_selection_section_items( $position = '' ) {
			if ( $this->is_valid_position( $position ) ) {
				if ( 'left' === $position ) {
					if ( ! empty( $this->args['included_ids'] ) ) {
						$this->args['post__in'] = $this->args['included_ids'];
					}

					if ( true === $this->args['is_search'] ) {
						if ( ( isset( $this->args['selected_ids'] ) ) && ( ! empty( $this->args['selected_ids'] ) ) ) {
							if ( ! isset( $this->args['post__not_in'] ) ) {
								$this->args['post__not_in'] = array();
							}
							$this->args['post__not_in'] = array_merge( $this->args['post__not_in'], $this->args['selected_ids'] );
						}
					}
				} elseif ( 'right' === $position ) {
					if ( ! empty( $this->args['selected_ids'] ) ) {
						$this->args['post__in'] = $this->args['selected_ids'];
					} else {
						$this->args['post__in'] = array( 0 );
					}
				}

				$this->process_query( $this->args, $position );
				if ( isset( $this->args['post__in'] ) ) {
					unset( $this->args['post__in'] );
				}
			}
		}

		/**
		 * Process selector section query.
		 *
		 * @since 2.2.1
		 *
		 * @param array  $query_args Array of query args.
		 * @param string $position   Value for 'left' or 'right' position.
		 */
		protected function process_query( $query_args = array(), $position = '' ) {
			if ( $this->is_valid_position( $position ) ) {
				$query = new WP_Query( $query_args );
				if ( ( $query->have_posts() ) ) {

					$this->element_queries[ $position ] = $query;

					if ( 'left' === $position ) {
						$this->element_data['query_vars'] = $query_args;
					}

					$this->element_items[ $position ] = $query->posts;

					$this->element_data['selector_class'] = $this->selector_class;
					$this->element_data['selector_nonce'] = $this->get_nonce_data();

					$this->element_data[ $position ]['position'] = $position;
					$this->element_data[ $position ]['pager']    = $this->get_pager_data( $position );
				}
			}
		}

		/**
		 * Get selector section pager data.
		 *
		 * @since 2.2.1
		 *
		 * @param string $position Value for 'left' or 'right' position.
		 */
		protected function get_pager_data( $position = '' ) {
			$pager = array();
			if ( $this->is_valid_position( $position ) ) {
				if ( isset( $this->element_queries[ $position ] ) ) {

					if ( isset( $this->element_queries[ $position ]->query_vars['paged'] ) ) {
						$pager['current_page'] = intval( $this->element_queries[ $position ]->query_vars['paged'] );
					} else {
						$pager['current_page'] = 0;
					}

					if ( isset( $this->element_queries[ $position ]->query_vars['posts_per_page'] ) ) {
						$pager['per_page'] = intval( $this->element_queries[ $position ]->query_vars['posts_per_page'] );
					} else {
						$pager['per_page'] = 0;
					}

					if ( isset( $this->element_queries[ $position ]->found_posts ) ) {
						$pager['total_items'] = intval( $this->element_queries[ $position ]->found_posts );
					} else {
						$pager['total_items'] = 0;
					}

					if ( ( ! empty( $pager['per_page'] ) ) && ( ! empty( $pager['total_items'] ) ) ) {
						$pager['total_pages'] = ceil( intval( $pager['total_items'] ) / intval( $pager['per_page'] ) );
					} else {
						$pager['total_pages'] = 0;
					}
				}
			}
			return $pager;
		}

		/**
		 * Build selector section options HTML.
		 *
		 * @since 2.2.1
		 *
		 * @param string $position Value for 'left' or 'right' position.
		 */
		protected function build_options_html( $position = '' ) {
			$options_html = '';
			if ( $this->is_valid_position( $position ) ) {
				if ( ! empty( $this->element_items[ $position ] ) ) {
					foreach ( $this->element_items[ $position ] as $post ) {
						$disabled_class = '';
						$disabled_state = '';

						$item_title = learndash_format_step_post_title_with_status_label( $post );

						/** This filter is documented in includes/admin/classes-binary-selectors/class-learndash-admin-binary-selector-users.php */
						$item_title = apply_filters( 'learndash_binary_selector_item', $item_title, $post, $position, $this->selector_class );

						if ( ( is_array( $this->args['selected_ids'] ) ) && ( ! empty( $this->args['selected_ids'] ) ) ) {
							if ( in_array( absint( $post->ID ), $this->args['selected_ids'], true ) ) {
								$disabled_class = 'learndash-binary-selector-item-disabled';
								if ( 'left' == $position ) {
									$disabled_state = ' disabled="disabled" ';
								}
							}
						}

						$options_html .= '<option class="learndash-binary-selector-item ' . $disabled_class . '" ' . $disabled_state . ' value="' . $post->ID . '" data-value="' . $post->ID . '">' . wp_strip_all_tags( $item_title ) . '</option>';
					}
				}
			}

			return $options_html;
		}

		/**
		 * Load selector section search AJAX.
		 *
		 * @since 2.2.1
		 *
		 * @param string $position Value for 'left' or 'right' position.
		 */
		public function load_search_ajax( $position = '' ) {

			$reply_data = array();

			if ( $this->is_valid_position( $position ) ) {
				if ( ( ! isset( $this->args['s'] ) ) && ( isset( $this->args['search'] ) ) ) {
					$this->args['s'] = $this->args['search'];
					unset( $this->args['search'] );
				}

				if ( ( isset( $this->args['s'] ) ) && ( ! empty( $this->args['s'] ) ) ) {
					$this->args['s'] = '"' . $this->args['s'] . '"';

					add_filter( 'posts_search', array( $this, 'search_filter_by_title' ), 10, 2 );
					$reply_data = parent::load_search_ajax( $position );
					remove_filter( 'posts_search', array( $this, 'search_filter_by_title' ), 10, 2 );
				}
			}
			return $reply_data;
		}

		/**
		 * Search filter by Title.
		 *
		 * @since 2.2.1
		 *
		 * @param string   $search Search pattern.
		 * @param WP_Query $wp_query WP_Query object.
		 */
		public function search_filter_by_title( $search, WP_Query $wp_query ) {
			if ( ! empty( $search ) && ! empty( $wp_query->query_vars['search_terms'] ) ) {
				global $wpdb;

				$q = $wp_query->query_vars;
				$n = ! empty( $q['exact'] ) ? '' : '%';

				$search = array();

				foreach ( (array) $q['search_terms'] as $term ) {
					$search[] = $wpdb->prepare( "$wpdb->posts.post_title LIKE %s", $n . $term . $n );
				}

				if ( ! is_user_logged_in() ) {
					$search[] = "$wpdb->posts.post_password = ''";
				}

				$search = ' AND ' . implode( ' AND ', $search );
			}
			return $search;
		}
	}
}
