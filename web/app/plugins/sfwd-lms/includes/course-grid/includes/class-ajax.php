<?php
/**
 * LearnDash Course Grid AJAX class file.
 *
 * @since 4.21.4
 *
 * @package LearnDash\Core
 */

namespace LearnDash\Course_Grid;

use WP_Query;
use WP_Post;
use LearnDash\Course_Grid\Utilities;

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

/**
 * AJAX class.
 *
 * @since 4.21.4
 */
class AJAX {
	/**
	 * Constructor.
	 *
	 * @since 4.21.4
	 */
	public function __construct() {
		add_action( 'wp_ajax_ld_cg_load_more', [ $this, 'load_posts' ] );
		add_action( 'wp_ajax_nopriv_ld_cg_load_more', [ $this, 'load_posts' ] );

		add_action( 'wp_ajax_ld_cg_apply_filter', [ $this, 'load_posts' ] );
		add_action( 'wp_ajax_nopriv_ld_cg_apply_filter', [ $this, 'load_posts' ] );
	}

	/**
	 * Load posts.
	 *
	 * @since 4.21.4
	 *
	 * @return void
	 */
	public function load_posts() {
		if ( ! isset( $_REQUEST['nonce'] ) || ! wp_verify_nonce( $_REQUEST['nonce'], 'ld_cg_load_posts' ) ) {
			wp_die();
		}

		$action_hook = current_filter();
		$hook        = false;
		if ( strpos( $action_hook, 'ld_cg_load_more' ) !== false ) {
			$hook = 'load_more';
		} elseif ( strpos( $action_hook, 'ld_cg_apply_filter' ) !== false ) {
			$hook = 'apply_filter';
		}

		if ( ! $hook ) {
			wp_die();
		}

		$filter = json_decode( stripslashes( $_REQUEST['filter'] ), true );

		/**
		 * Course grid attributes.
		 *
		 * @var array{
		 *     post_type: string,
		 *     page: int,
		 *     per_page: int,
		 *     orderby: string,
		 *     order: string,
		 *     pagination: string,
		 *     card: string,
		 * } $atts The course grid attributes.
		 */
		$atts = json_decode( stripslashes( $_REQUEST['course_grid'] ?? '{}' ), true ); // phpcs:ignore WordPress.Security.ValidatedSanitizedInput.MissingUnslash, WordPress.Security.ValidatedSanitizedInput.InputNotSanitized -- Sanitization is done per attribute.

		$post_type  = sanitize_text_field( $atts['post_type'] );
		$page       = intval( $atts['page'] );
		$per_page   = intval( $atts['per_page'] );
		$offset     = $page * $per_page;
		$orderby    = sanitize_text_field( $atts['orderby'] );
		$order      = sanitize_text_field( $atts['order'] );
		$search     = isset( $filter['search'] ) ? sanitize_text_field( $filter['search'] ) : null;
		$pagination = sanitize_text_field( $atts['pagination'] );
		$price_min  = isset( $filter['price_min'] ) && is_numeric( $filter['price_min'] ) ? floatval( $filter['price_min'] ) : null;
		$price_max  = isset( $filter['price_max'] ) && is_numeric( $filter['price_max'] ) ? floatval( $filter['price_max'] ) : null;

		if ( isset( $filter['search'] ) ) {
			unset( $filter['search'] );
		}

		if ( isset( $filter['price_min'] ) ) {
			unset( $filter['price_min'] );
		}

		if ( isset( $filter['price_max'] ) ) {
			unset( $filter['price_max'] );
		}

		$posts = [];

		$query_args           = Utilities::build_posts_query_args( $atts );
		$query_args['s']      = $search;
		$query_args['offset'] = 0;

		if (
			in_array( $post_type, [ 'sfwd-courses', 'groups' ] )
			&& ( isset( $price_min ) || isset( $price_max ) )
		) {
			$query_args['posts_per_page'] = -1;
		}

		if ( $hook === 'load_more' ) {
			$offset               = $page * $per_page;
			$query_args['offset'] = $offset;

			if ( $per_page == -1 ) {
				echo json_encode(
					[
						'status' => 'success',
						'html'   => '',
						'page'   => 'complete',
					]
				);

				wp_die();
			}
		} elseif ( $hook === 'apply_filter' ) {
			$offset               = 0;
			$query_args['offset'] = $offset;
		}

		$tax_count = 0;
		foreach ( $filter as $taxonomy => $values ) {
			++$tax_count;

			if ( ! empty( $values ) && is_array( $values ) ) {
				$values = array_map(
					function ( $id ) {
						return intval( $id );
					},
					$values
				);

				$query_args['tax_query'][] = [
					'taxonomy' => $taxonomy,
					'field'    => 'term_id',
					'terms'    => $values,
				];

				if ( count( $query_args['tax_query'] ) > 1 ) {
					$query_args['tax_query']['relation'] = 'OR';
				}
			}
		}

		/**
		 * Filters the query arguments for the course grid.
		 *
		 * @since 4.21.4
		 *
		 * @param array<mixed> $query_args The query arguments.
		 * @param array<mixed> $atts       The shortcode attributes.
		 * @param array<mixed> $filter     The filter values.
		 *
		 * @return array<mixed> The returned query arguments.
		 */
		$query_args = apply_filters( 'learndash_course_grid_query_args', $query_args, $atts, $filter );

		$query = new WP_Query( $query_args );

		if ( $query->have_posts() ) {
			$posts = $query->get_posts();
		}

		$html            = '';
		$html_pagination = '';
		if ( ! empty( $posts ) ) {
			$card           = sanitize_text_field( $atts['card'] );
			$template       = Utilities::get_card_layout( $card );
			$has_pagination = false;

			if (
				in_array( $post_type, [ 'sfwd-courses', 'groups' ] )
				&& ( isset( $price_min ) || isset( $price_max ) )
				&& defined( 'LEARNDASH_VERSION' )
			) {
				// Filter posts
				$posts = array_filter(
					$posts,
					function ( $post ) use ( $price_min, $price_max ) {
						$price        = false;
						$price_number = false;
						if ( $post->post_type == 'sfwd-courses' ) {
							$price        = \learndash_get_course_price( $post->ID );
							$price_number = floatval( $price['price'] );
						} elseif ( $post->post_type == 'groups' ) {
							$price        = \learndash_get_group_price( $post->ID );
							$price_number = floatval( $price['price'] );
						}

						$price_min_check = true;
						if ( $price_min === 0 || $price_min > 0 ) {
							$price_min_check = ( $price_number >= $price_min );
						}

						$price_max_check = true;
						if ( isset( $price_max ) ) {
							$price_max_check = ( $price_number <= $price_max ) || ( $price_max == 0 && $price['type'] == 'free' );
						}

						if ( $price && $price_min_check && $price_max_check ) {
							return true;
						} else {
							return false;
						}
					}
				);

				$total_posts   = count( $posts );
				$max_num_pages = ceil( $total_posts / $per_page );

				$posts = array_slice( $posts, $offset, $per_page );
			} else {
				$posts         = $query->get_posts();
				$max_num_pages = $query->max_num_pages;
			}

			if ( $max_num_pages > $page ) {
				$has_pagination = true;
			}

			foreach ( $posts as $post ) {
				if ( ! $post instanceof WP_Post ) {
					continue;
				}

				ob_start();
				learndash_course_grid_load_card_template( $atts, $post );

				$html .= ob_get_clean();
			}

			$pagination_template = Utilities::get_pagination_template( $pagination );

			if ( $pagination_template && $has_pagination ) {
				ob_start();
				include $pagination_template;
				$html_pagination .= ob_get_clean();
			}
		} elseif ( $hook === 'apply_filter' ) {
				$html .= '<p style="text-align: center;">' . __( 'No results found.', 'learndash' ) . '</p>';
		}

		if ( $hook === 'load_more' ) {
			$page = empty( $html ) || ( $page + 2 ) > $max_num_pages ? 'complete' : $page + 1;
		} elseif ( $hook === 'apply_filter' ) {
			$page = 1;
		}

		echo json_encode(
			[
				'status'          => 'success',
				'html'            => $html,
				'html_pagination' => $html_pagination,
				'page'            => $page,
			]
		);

		wp_die();
	}
}
