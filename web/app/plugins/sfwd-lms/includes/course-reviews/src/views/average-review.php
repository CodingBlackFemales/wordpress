<?php
/**
 * Template for showing an Average Review Score.
 *
 * @since 4.25.1
 * @version 1.0.0
 *
 * @var int $course_id Course ID.
 *
 * @package LearnDash\Course_Reviews
 */

defined( 'ABSPATH' ) || die();

$average = learndash_course_reviews_get_average_review_score( $course_id );

if ( is_bool( $average ) ) {
	$average = 0.0;
}

?>

<div class="average-review">

	<b class="average-review-label">
		<?php esc_html_e( 'Average Review Score:', 'learndash' ); ?>
	</b>

	<?php learndash_course_reviews_star_rating( $average ); ?>

</div>
