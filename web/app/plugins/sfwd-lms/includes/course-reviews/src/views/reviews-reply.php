<?php
/**
 * Template for adding a Reply to a Review.
 *
 * @since 4.25.1
 * @version 1.0.2
 *
 * @var int $course_id Course ID.
 *
 * @package LearnDash\Course_Reviews
 */

defined( 'ABSPATH' ) || die();

?>

<div id="learndash-course-reviews-reply" style="display: none">
	<h3 id="learndash-course-reviews-reply-heading" class="learndash-course-reviews-heading">
		<?php esc_html_e( 'Leave a reply', 'learndash' ); ?> <small>
			<a rel="nofollow" id="cancel-comment-reply-link" href="#">
				<?php esc_html_e( 'Cancel reply', 'learndash' ); ?>
			</a>
		</small>
	</h3>

	<form action="" method="post" name="">
		<div class="grid-container full">
			<div class="grid-x">
				<div class="small-12 cell">
					<label for="learndash-course-reviews-review">
						<?php esc_html_e( 'Comment', 'learndash' ); ?> <span class="required">*</span>
					</label>

					<textarea
						id="learndash-course-reviews-reply"
						name="learndash-course-reviews-reply"
						cols="45"
						rows="8"
						aria-required="true"
						required="required"
					></textarea>
				</div>
			</div>

			<div class="grid-x">
				<div class="small-12 cell">
					<input
						type="submit"
						class="button primary expanded"
						value="<?php esc_attr_e( 'Post Reply', 'learndash' ); ?>"
					/>
				</div>
			</div>
		</div>
	</form>
</div>
