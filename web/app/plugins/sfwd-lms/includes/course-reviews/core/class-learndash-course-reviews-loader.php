<?php
/**
 * Provides helper functions.
 *
 * @since 4.25.1
 *
 * @package LearnDash\Course_Reviews
 */

use LearnDash\Core\Utilities\Cast;

if ( ! defined( 'ABSPATH' ) ) {
	die;
}

/**
 * Main LearnDash_Course_Reviews class.
 *
 * @since 4.25.1
 */
final class LearnDash_Course_Reviews_Loader {
	/**
	 * LearnDash_Course_Reviews_Loader Constructor
	 *
	 * @since 4.25.1
	 *
	 * @return void
	 */
	public function __construct() {
		add_action( 'learndash_course_reviews_average_review', array( $this, 'output_average_review_template' ) );

		add_action( 'learndash_course_reviews_review_list', array( $this, 'output_review_list_template' ) );

		add_action( 'learndash_course_reviews_review_form', array( $this, 'output_review_form_template' ) );

		add_action( 'learndash_course_reviews_review_reply', array( $this, 'output_review_reply_template' ) );

		add_filter( 'learndash_content_tabs', array( $this, 'add_reviews_tab' ), 10, 4 );

		add_action( 'wp_enqueue_scripts', array( $this, 'enqueue_scripts' ) );

		add_action( 'parse_comment_query', array( $this, 'remove_reviews_from_general_comments' ) );

		add_filter( 'get_comments_number', array( $this, 'fix_comment_counts' ), 10, 2 );
	}

	/**
	 * Add our output to a new tab in LD 3.0 Templates.
	 *
	 * @since 4.25.1
	 *
	 * @param array<array{id: string, icon:string, label: string, content: string}> $tabs      Tabs to display on the page.
	 * @param string                                                                $context   Context.
	 * @param int                                                                   $course_id Course ID.
	 * @param int                                                                   $user_id   User ID.
	 *
	 * @return array<array{id: string, icon:string, label: string, content: string}> Tabs to display on the page
	 */
	public function add_reviews_tab( $tabs, $context, $course_id, $user_id ) {
		if ( $context !== 'course' ) {
			return $tabs;
		}

		if ( ! learndash_course_reviews_is_review_enabled( $course_id ) ) {
			return $tabs;
		}

		ob_start();
		learndash_course_reviews_locate_template(
			'reviews.php',
			array(
				'course_id' => $course_id,
			)
		);
		$content = strval( ob_get_clean() );

		$tabs[] = array(
			'id'      => 'reviews',
			'icon'    => 'star-blank',
			'label'   => __( 'Reviews', 'learndash' ),
			'content' => $content,
		);

		return $tabs;
	}

	/**
	 * Outputs the Average Review Score.
	 *
	 * @since 4.25.1
	 *
	 * @param int $course_id  Course ID.
	 *
	 * @return void
	 */
	public function output_average_review_template( $course_id ) {
		learndash_course_reviews_locate_template(
			'average-review.php',
			array(
				'course_id' => $course_id,
			)
		);
	}

	/**
	 * Outputs the Review List.
	 *
	 * @since 4.25.1
	 *
	 * @param int $course_id  Course ID.
	 *
	 * @return void
	 */
	public function output_review_list_template( $course_id ) {
		learndash_course_reviews_locate_template(
			'review-list.php',
			array(
				'course_id' => $course_id,
			)
		);
	}

	/**
	 * Output the Review Form.
	 *
	 * @since 4.25.1
	 *
	 * @param int $course_id  Course ID.
	 *
	 * @return void
	 */
	public function output_review_form_template( $course_id ) {
		learndash_course_reviews_locate_template(
			'reviews-form.php',
			array(
				'course_id' => $course_id,
			)
		);
	}

	/**
	 * Output the Review Replace Form.
	 *
	 * @since 4.25.1
	 *
	 * @param int $course_id  Course ID.
	 *
	 * @return void
	 */
	public function output_review_reply_template( $course_id ) {
		learndash_course_reviews_locate_template(
			'reviews-reply.php',
			array(
				'course_id' => $course_id,
			)
		);
	}

	/**
	 * Loads JS and CSS where necessary.
	 *
	 * @since 4.25.1
	 *
	 * @return void
	 */
	public function enqueue_scripts() {
		if ( get_post_type() !== 'sfwd-courses' ) {
			return;
		}

		wp_enqueue_script( 'learndash-course-reviews' );
		wp_enqueue_style( 'learndash-course-reviews' );
	}

	/**
	 * Ensure that Reviews are only output when explicitly asked for.
	 *
	 * @since 4.25.1
	 *
	 * @param WP_Comment_Query $comment_query Comment Query Object.
	 *
	 * @return void
	 */
	public function remove_reviews_from_general_comments( &$comment_query ): void {
		// We want to display Reviews within the Admin Dashboard under Comments.
		if ( is_admin() ) {
			return;
		}

		if (
			! empty( $comment_query->query_vars['type'] )
			&& (
				(
					is_string( $comment_query->query_vars['type'] )
					&& $comment_query->query_vars['type'] !== 'all'
				)
				|| (
					is_array( $comment_query->query_vars['type'] )
					&& ! in_array(
						'all',
						$comment_query->query_vars['type'],
						true
					)
				)
			)
		) {
			return;
		}

		$comment_query->query_vars['type__not_in'] = (array) $comment_query->query_vars['type__not_in'];

		$comment_query->query_vars['type__not_in'][] = 'ld_review';
	}

	/**
	 * Fix the displayed Comment Count for a given Post.
	 *
	 * @since 4.25.1
	 *
	 * @param int|string $comment_count Comment Count, pulled from wp_posts.
	 * @param int        $post_id       Post ID.
	 *
	 * @return int|string               Comment Count. Returns string when > 0, int 0 otherwise, matching WordPress core behavior.
	 */
	public function fix_comment_counts( $comment_count, $post_id ) {
		$comments = get_comments(
			[
				'post_id' => $post_id,
				'fields'  => 'ids',
			]
		);

		if ( ! is_array( $comments ) ) {
			return $comment_count;
		}

		$count = count( $comments );

		// Match WordPress core behavior: return string when > 0, int 0 otherwise.
		if ( $count > 0 ) {
			return (string) $count;
		}

		return 0;
	}
}

$instance = new LearnDash_Course_Reviews_Loader();
