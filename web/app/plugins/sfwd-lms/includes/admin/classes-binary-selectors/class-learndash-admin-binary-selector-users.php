<?php
/**
 * LearnDash Binary Selector Users.
 *
 * @since 2.2.1
 * @package LearnDash\Settings
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

if ( ( ! class_exists( 'Learndash_Binary_Selector_Users' ) ) && ( class_exists( 'Learndash_Binary_Selector' ) ) ) {

	/**
	 * Class LearnDash Binary Selector Users.
	 *
	 * @since 2.2.1
	 * @uses Learndash_Binary_Selector
	 */
	class Learndash_Binary_Selector_Users extends Learndash_Binary_Selector {

		/**
		 * Public constructor for class.
		 *
		 * @since 2.2.1
		 *
		 * @param array $args Array of arguments for class.
		 */
		public function __construct( $args = array() ) {

			// Set up the default query args for the Users.
			$defaults = array(
				'paged'   => 1,
				'number'  => LearnDash_Settings_Section::get_section_setting( 'LearnDash_Settings_Section_General_Per_Page', 'per_page' ),
				'fields'  => array( 'ID', 'display_name', 'user_login' ),
				'orderby' => 'display_name',
				'order'   => 'ASC',
				'search'  => '',
			);

			if ( ( ! isset( $args['number'] ) ) && ( isset( $args['per_page'] ) ) && ( ! empty( $args['per_page'] ) ) ) {
				$args['number'] = $args['per_page'];
			}

			$args = wp_parse_args( $args, $defaults );

			parent::__construct( $args );

			if ( ( isset( $this->args['included_ids'] ) ) && ( ! empty( $this->args['included_ids'] ) ) ) {
				$this->args['include'] = $this->args['included_ids'];
			}

			if ( ( isset( $this->args['excluded_ids'] ) ) && ( ! empty( $this->args['excluded_ids'] ) ) ) {
				$this->args['exclude'] = $this->args['excluded_ids'];
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

					if ( isset( $this->element_queries[ $position ]->query_vars['number'] ) ) {
						$pager['per_page'] = intval( $this->element_queries[ $position ]->query_vars['number'] );
					} else {
						$pager['per_page'] = 0;
					}

					if ( isset( $this->element_queries[ $position ]->total_users ) ) {
						$pager['total_items'] = intval( $this->element_queries[ $position ]->total_users );
					} else {
						$pager['total_items'] = 0;
					}

					if ( ( ! empty( $pager['per_page'] ) ) && ( ! empty( $pager['total_items'] ) ) ) {
						$pager['total_pages'] = ceil( intval( $this->element_queries[ $position ]->total_users ) / intval( $this->element_queries[ $position ]->query_vars['number'] ) );
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
					foreach ( $this->element_items[ $position ] as $user ) {
						/**
						 * Filters binary selector item.
						 *
						 * @since 2.6.0
						 *
						 * @param string  $selector_item  Binary selector item title.
						 * @param WP_User $user           WP_User object.
						 * @param string  $position       Value for 'left' or 'right' position.
						 * @param string  $selector_class Class reference to selector.
						 */
						$user_name = apply_filters( 'learndash_binary_selector_item', $user->display_name . ' (' . $user->user_login . ')', $user, $position, $this->selector_class );
						if ( ! empty( $user_name ) ) {
							$user_name = wp_strip_all_tags( $user_name );
						} else {
							$user_name = $user->display_name . ' (' . $user->user_login . ')';
						}

						$disabled_class = '';
						$disabled_state = '';

						if ( ( is_array( $this->args['selected_ids'] ) ) && ( ! empty( $this->args['selected_ids'] ) ) ) {
							if ( in_array( absint( $user->ID ), $this->args['selected_ids'], true ) ) {
								$disabled_class = 'learndash-binary-selector-item-disabled';
								if ( 'left' === $position ) {
									$disabled_state = ' disabled="disabled" ';
								}
							}
						}
						$options_html .= '<option class="learndash-binary-selector-item ' . $disabled_class . '" ' . $disabled_state . ' value="' . $user->ID . '" data-value="' . $user->ID . '">' . $user_name . '</option>';
					}
				}
			}

			return $options_html;
		}

		/**
		 * Query selector section items.
		 *
		 * @since 2.2.1
		 *
		 * @param string $position Value for 'left' or 'right' position.
		 */
		protected function query_selection_section_items( $position = '' ) {
			global $wpdb;

			if ( $this->is_valid_position( $position ) ) {
				if ( 'left' === $position ) {
					if ( ! empty( $this->args['included_ids'] ) ) {
						$this->args['include'] = $this->args['included_ids'];
					}

					if ( ( isset( $this->args['excluded_ids'] ) ) && ( ! empty( $this->args['excluded_ids'] ) ) ) {
						$this->args['exclude'] = $this->args['excluded_ids'];
					}

					if ( true === $this->args['is_search'] ) {
						if ( ( isset( $this->args['selected_ids'] ) ) && ( ! empty( $this->args['selected_ids'] ) ) ) {
							if ( ! isset( $this->args['exclude'] ) ) {
								$this->args['exclude'] = array();
							}
							$this->args['exclude'] = array_merge( $this->args['exclude'], $this->args['selected_ids'] );
						}
					}
				} elseif ( 'right' === $position ) {
					if ( ( isset( $this->args['selected_meta_query'] ) ) && ( ! empty( $this->args['selected_meta_query'] ) ) ) {
						$this->args['meta_query'] = $this->args['selected_meta_query']; // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					} elseif ( ! empty( $this->args['selected_ids'] ) ) {
						$this->args['include'] = $this->args['selected_ids'];
					} else {
						$this->args['include'] = array( 0 );
					}

					if ( ( isset( $this->args['excluded_ids'] ) ) && ( ! empty( $this->args['excluded_ids'] ) ) ) {
						$this->args['exclude'] = $this->args['excluded_ids'];
					}
				}

				/**
				 * Filter to exclude users with no role.
				 *
				 * @since 3.2.3
				 *
				 * @param bool   $true           Boolean to exclude no_role users.
				 * @param array  $query_args     Array of current query args.
				 * @param string $position       Position (left/right) for queried items.
				 * @param string $selector_class Class for Binary selector.
				 */
				if ( apply_filters( 'learndash_exclude_user_no_role', true, $this->args, $position, $this->selector_class ) ) {
					$blog_prefix = $wpdb->get_blog_prefix( $wpdb->blogid );
					if ( ! isset( $this->args['meta_query'] ) ) {
						$this->args['meta_query'] = array(); // phpcs:ignore WordPress.DB.SlowDBQuery.slow_db_query_meta_query
					}
					$this->args['meta_query'][] = array(
						'key'     => "{$blog_prefix}capabilities",
						'compare' => 'EXISTS',
					);

					$meta_query_relation_present = false;
					foreach ( $this->args['meta_query'] as $idx => $query_item ) {
						if ( 'relation' === $idx ) {
							$meta_query_relation_present = true;
							break;
						}
					}

					if ( ! $meta_query_relation_present ) {
						$this->args['meta_query']['relation'] = 'AND';
					}
				}
				$this->process_query( $this->args, $position );

				if ( isset( $this->args['include'] ) ) {
					unset( $this->args['include'] );
				}
			}
		}

		/**
		 * Process selector section query.
		 *
		 * @since 2.2.1
		 *
		 * @param array  $query_args Array of query args.
		 * @param string $position Value for 'left' or 'right' position.
		 */
		public function process_query( $query_args = array(), $position = '' ) {
			if ( $this->is_valid_position( $position ) ) {
				$query = new WP_User_Query( $query_args );
				$items = $query->get_results();
				if ( ! empty( $items ) ) {

					$this->element_queries[ $position ] = $query;
					$this->element_items[ $position ]   = $items;

					// We only need to store one reference to the query as the left and right will share this. Plus
					// the query on the right side may/will have the 'include' elements and we store this as 'selected_ids' key.
					if ( 'left' === $position ) {
						$this->element_data['query_vars'] = $query_args;
					}

					$this->element_data['selector_class'] = $this->selector_class;
					$this->element_data['selector_nonce'] = $this->get_nonce_data();

					$this->element_data[ $position ]['position'] = $position;
					$this->element_data[ $position ]['pager']    = $this->get_pager_data( $position );
				}
			}
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
				if ( ( isset( $this->args['search'] ) ) && ( ! empty( $this->args['search'] ) ) ) {

					// For user searching Users we must include the beginning and ending '*' for wildcard matches.
					$this->args['search'] = '*' . $this->args['search'] . '*';

					// Now call the parent function to perform the actual search.
					$reply_data = parent::load_search_ajax( $position );
				}
			}

			return $reply_data;
		}
	}
}
