<?php
/**
 * Template for showing a Stars interface.
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

<div class="learndash-course-reviews-stars-input">

	<?php for ( $index = 1; $index <= 5; $index++ ) : ?>
		<div class="review-star">
			<input
				type="radio"
				name="rating"
				id="learndash_course_reviews_star_<?php echo esc_attr( strval( $index ) ); ?>"
				value="<?php echo esc_attr( strval( $index ) ); ?>"
				required
			/>
			<label for="learndash_course_reviews_star_<?php echo esc_attr( strval( $index ) ); ?>">
				&starf;
			</label>
		</div>
	<?php endfor; ?>

</div>
