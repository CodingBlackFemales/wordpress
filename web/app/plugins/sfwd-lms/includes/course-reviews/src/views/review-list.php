<?php
/**
 * Template for showing all Reviews.
 *
 * @since 4.25.1
 * @version 1.0.0
 *
 * @var int $course_id Course ID.
 *
 * @package LearnDash\Course_Reviews
 */

defined( 'ABSPATH' ) || die();

use LearnDash\Core\Utilities\Cast;

?>

<div class="reviews-list">

	<?php

	$reviews = get_comments(
		array(
			'post_id' => $course_id,
			'type'    => 'ld_review',
			'status'  => 'approve',
		)
	);

	if ( ! is_array( $reviews ) ) {
		$reviews = array();
	}

	$reviews = array_values(
		array_filter(
			$reviews,
			function ( $review ) {
				return $review instanceof WP_Comment;
			}
		)
	);

	$comments_per_page = Cast::to_int( get_option( 'comments_per_page' ) );

	if ( $comments_per_page <= 0 ) {
		$comments_per_page = 1;
	}

	$total_pages = Cast::to_int(
		ceil(
			count( $reviews ) / get_option( 'comments_per_page' )
		)
	);

	$cpage = Cast::to_int( get_query_var( 'cpage' ) );
	$cpage = ( $cpage > 0 ) ? $cpage : 1;

	/**
	 * Fires before outputting the list of Reviews.
	 *
	 * @since 4.25.1
	 *
	 * @param int               $course_id Course ID.
	 * @param array<WP_Comment> $reviews   Array of Comment Objects.
	 */
	do_action(
		'learndash_course_reviews_before_reviews',
		$course_id,
		$reviews
	);

	wp_list_comments(
		/**
		 * Filters the args passed to wp_list_comments() for outputting Reviews.
		 *
		 * @since 4.25.1
		 *
		 * @param array{walker?: Walker, max_depth?: int, callback?: callable, end-callback?: callable, type?: string, page?: int, per_page?: int, avatar_size?: int, reverse_top_level?: bool, reverse_children?: bool, format?: string, short_ping?: bool, echo?: bool} $args wp_list_comment $args.
		 *
		 * @return array{walker?: Walker, max_depth?: int, callback?: callable, end-callback?: callable, type?: string, page?: int, per_page?: int, avatar_size?: int, reverse_top_level?: bool, reverse_children?: bool, format?: string, short_ping?: bool, echo?: bool} wp_list_comment $args.
		 */
		apply_filters(
			'learndash_course_reviews_render_reviews_args',
			array(
				'walker' => new LearnDash_Course_Reviews_Walker(),
				'style'  => 'div',
				'echo'   => true,
			)
		),
		$reviews
	);

	/**
	 * Fires after outputting the list of Reviews.
	 *
	 * @since 4.25.1
	 *
	 * @param int               $course_id Course ID.
	 * @param array<WP_Comment> $reviews   Array of Comment Objects.
	 */
	do_action(
		'learndash_course_reviews_after_reviews',
		$course_id,
		$reviews
	);

	paginate_comments_links(
		array(
			'base'         => add_query_arg( 'cpage', '%#%' ),
			'total'        => $total_pages,
			'current'      => $cpage,
			'echo'         => true,
			'add_fragment' => '#ld-reviews',
		)
	);

	/**
	 * Fires after outputting the Review pagination.
	 *
	 * @since 4.25.1
	 *
	 * @param int               $course_id   Course ID.
	 * @param array<WP_Comment> $reviews     Array of Comment Objects.
	 * @param int               $total_pages Total number of pages.
	 * @param int               $cpage       Current page.
	 */
	do_action(
		'learndash_course_reviews_after_review_pagination',
		$course_id,
		$reviews,
		$total_pages,
		$cpage
	);

	?>

</div>
