<?php
/**
 * Template for showing Reviews if enabled.
 *
 * @since 4.25.1
 * @version 1.0.0
 *
 * @var int $course_id Course ID.
 *
 * @package LearnDash\Course_Reviews
 */

defined( 'ABSPATH' ) || die();

?>

<div class="learndash-course-reviews-container">

	<?php
	/**
	 * Outputs the Course's average Review score.
	 *
	 * @since 4.25.1
	 *
	 * @param int $course_id Course ID.
	 */
	do_action(
		'learndash_course_reviews_average_review',
		$course_id
	);
	?>

	<?php
	/**
	 * Outputs the Course's list of Reviews.
	 *
	 * @since 4.25.1
	 *
	 * @param int $course_id Course ID.
	 */
	do_action(
		'learndash_course_reviews_review_list',
		$course_id
	);
	?>

	<?php
	/**
	 * Outputs the form to leave a Review on a Course.
	 *
	 * @since 4.25.1
	 *
	 * @param int $course_id Course ID.
	 */
	do_action(
		'learndash_course_reviews_review_form',
		$course_id
	);
	?>

	<?php
	if ( is_user_logged_in() ) {
		/**
		 * Outputs the form to leave a reply to a Review.
		 *
		 * @since 4.25.1
		 *
		 * @param int $course_id Course ID.
		 */
		do_action(
			'learndash_course_reviews_review_reply',
			$course_id
		);
	}
	?>

</div>
