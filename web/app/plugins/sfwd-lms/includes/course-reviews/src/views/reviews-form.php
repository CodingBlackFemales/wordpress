<?php
/**
 * Template for showing the form for adding a Review.
 *
 * @since 4.25.1
 * @version 1.0.0
 *
 * @var int $course_id Course ID.
 *
 * @package LearnDash\Course_Reviews
 */

defined( 'ABSPATH' ) || die();

$has_started_course = learndash_course_reviews_user_has_started_course( $course_id );
$has_reviewed       = learndash_course_reviews_get_user_review( $course_id );

/**
 * Filter to show the Review Form on a given Course.
 *
 * @since 4.25.1
 *
 * @param bool      $show               Show or not. Defaults to true.
 * @param int       $course_id          Course ID.
 * @param bool      $has_started_course Whether the User has started the Course.
 * @param int|false $has_reviewed       Whether the User has already reviewed this Course. Comment ID if they have, false if they have not.
 *
 * @return bool
 */
$show_review_form = apply_filters(
	'learndash_course_reviews_show_review_form',
	true,
	$course_id,
	$has_started_course,
	$has_reviewed
);

if ( ! $show_review_form ) {
	return;
}

?>

<div class="learndash-course-reviews-form" id="learndash-course-reviews-respond">

	<?php
	if (
		$has_started_course
		&& ! $has_reviewed
	) :
		?>
		<div class="notices-container"></div>

		<form method="post" name="learndash_course_reviews" action="reviews_process_review">

			<?php wp_nonce_field( 'wp_rest' ); ?>

			<input type="hidden" name="course_id" value="<?php echo esc_attr( strval( $course_id ) ); ?>" />

			<div class="grid-container full">

				<div class="grid-x">
					<div class="small-12 cell">
						<label for="learndash-course-reviews-review-title">
							<?php esc_html_e( 'Review Title', 'learndash' ); ?> <span class="required">*</span>
						</label>
						<input
							type="text"
							id="learndash-course-reviews-review-title"
							name="review_title"
							value=""
							size="30"
							aria-required="true"
							required="required"
						/>
					</div>
				</div>

				<div class="grid-x">
					<div class="small-12 cell">
						<label for="learndash-course-reviews-review">
							<?php esc_html_e( 'Rating', 'learndash' ); ?> <span class="required">*</span>
						</label>
						<?php learndash_course_reviews_stars_input(); ?>
					</div>
				</div>

				<div class="grid-x">
					<div class="small-12 cell">
						<label for="learndash-course-reviews-review">
							<?php esc_html_e( 'Review', 'learndash' ); ?> <span class="required">*</span>
						</label>
						<textarea
							id="learndash-course-reviews-review"
							name="review_content"
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
							value="<?php esc_attr_e( 'Post Review', 'learndash' ); ?>"
							data-saving_text="<?php esc_attr_e( 'Submitting...', 'learndash' ); ?>"
						/>
					</div>
				</div>

			</div>
		</form>
	<?php elseif ( ! $has_started_course ) : ?>
		<?php if ( ! is_user_logged_in() ) : ?>
			<p class="learndash-course-reviews-not-allowed">
				<?php
				echo esc_html(
					/**
					 * Filters the "You must log in and have started this Course ot submit a review" error message.
					 *
					 * @since 4.25.1
					 *
					 * @param string $message Error message.
					 *
					 * @return string Error message.
					 */
					apply_filters(
						'learndash_course_reviews_user_logged_out_message',
						sprintf(
							// translators: Lowercase "Course" label.
							__( 'You must log in and have started this %s to submit a review.', 'learndash' ),
							learndash_get_custom_label_lower( 'course' )
						)
					)
				);
				?>
			</p>
		<?php else : ?>
			<p class="learndash-course-reviews-not-allowed">
				<?php
				echo esc_html(
					/**
					 * Filters the "You must have started this Course ot submit a review" error message.
					 *
					 * @since 4.25.1
					 *
					 * @param string $message Error message.
					 *
					 * @return string Error message.
					 */
					apply_filters(
						'learndash_course_reviews_user_has_not_started_course',
						sprintf(
							// translators: Lowercase "Course" label.
							__( 'You must have started this %s to submit a review.', 'learndash' ),
							learndash_get_custom_label_lower( 'course' )
						)
					)
				);
				?>
			</p>
		<?php endif; ?>
		<?php
		if ( ! is_user_logged_in() ) {
			wp_login_form(
				array(
					'echo' => true,
				)
			);
		}
		?>
	<?php endif; ?>
</div>
