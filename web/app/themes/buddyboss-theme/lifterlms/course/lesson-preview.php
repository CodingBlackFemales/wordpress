<?php
/**
 * Template for a lesson preview element
 *
 * @author      LifterLMS
 * @package     LifterLMS/Templates
 * @since       1.0.0
 * @version     4.4.0
 */
defined( 'ABSPATH' ) || exit;

$lessonID = $lesson->get( 'id' );

$lesson = new LLMS_Lesson( $lessonID );

$restrictions = llms_page_restricted( $lessonID, get_current_user_id() );


if ( is_singular( 'course' ) ):

	$post_id = get_the_ID();

	$product = new LLMS_Product( $post_id );

	$is_enrolled = llms_is_user_enrolled( get_current_user_id(), $product->get( 'id' ) );

endif;


if ( is_singular( 'lesson' ) ):

	$post_id = buddyboss_theme()->lifterlms_helper()->bb_lifterlms_get_parent_course( $lesson );

	$product = new LLMS_Product( $post_id );

	$is_enrolled = llms_is_user_enrolled( get_current_user_id(), $product->get( 'id' ) );

endif;


$data_msg = $restrictions['is_restricted'] ? ' data-tooltip-msg="' . esc_html( strip_tags( llms_get_restriction_message( $restrictions ) ) ) . '"' : '';


$quiz_id       = $lesson->quiz;
$assignment_id = $lesson->assignment;
$lesson_status = '';

if ( empty( $restrictions['is_restricted'] ) && is_user_logged_in() ):

	$quizz_status_condition      = false;
	$assignment_status_condition = false;

	if ( $quiz_id != 0 ):

		$query = new LLMS_Query_Quiz_Attempt( array(
			'student_id' => get_current_user_id(),
			'quiz_id'    => $quiz_id,
		) );

		$attempts = array();

		$results = buddyboss_theme()->lifterlms_helper()->bb_lifterlms_get_quiz_result( $query );

		foreach ( $results as $result ) {

			$attempts[] = new LLMS_Quiz_Attempt( $result->id );

		}

		$quizz_status = 'is-incomplete';

		foreach ( $attempts as $attempt ) :

			if ( $attempt->l10n( 'status' ) == "Pass" ) {
				$quizz_status           = 'is-complete';
				$quizz_status_condition = true;
			}

		endforeach;

	endif;

	if ( $assignment_id != 0 && class_exists( 'LifterLMS_Assignments' ) ):

		$assignment = llms_lesson_get_assignment( $lessonID );

		$submission = llms_student_get_assignment_submission( $assignment_id );

		$status = $submission->get( 'status' );


		if ( $status == "pass" ) {
			$assignment_status           = 'is-complete';
			$assignment_status_condition = true;
		} else {
			$assignment_status = 'is-incomplete';
		}

	endif;

	$student                   = new LLMS_Student( get_current_user_id() );
	$lesson_complete_condition = $student->is_complete( $lessonID, "lesson" ) ? true : false;


	if ( ( $quizz_status_condition && $assignment_status_condition ) || $lesson_complete_condition ) {
		$lesson_status = "is-complete";
	} else {
		$lesson_status = "is-incomplete";
	}

endif;

?>

<div class="llms-lesson-preview <?php if ( empty( $restrictions['is_restricted'] ) ): echo $lesson_status; endif; ?>">

    <div class="llms-lesson-main">

		<?php

		if ( ! empty( $quiz_id ) || ! empty( $assignment_id ) ): ?>

            <div class="ld-item-details">
                <div class="ld-expand-button ld-button-alternate <?php if ( ( get_the_ID() == $quiz_id ) || ( get_the_ID() == $assignment_id ) ): ?>ld-expanded <?php endif; ?>">
                    <span class="ld-icon-arrow-down ld-icon ld-primary-background"></span>
                </div>
            </div>
		<?php endif; ?>

        <div class="llms-lesson-holder <?php echo apply_filters( 'llms_display_outline_thumbnails',
			true ) && has_post_thumbnail( $lesson->get( 'id' ) ) ? 'llms_has-thumbnail' : '' ?>">

			<?php if ( 'course' === get_post_type( get_the_ID() ) ) : ?>

				<?php if ( apply_filters( 'llms_display_outline_thumbnails',
					true ) ) : ?><?php if ( has_post_thumbnail( $lesson->get( 'id' ) ) ) : ?>
                    <div class="llms-lesson-thumbnail">
						<?php echo get_the_post_thumbnail( $lesson->get( 'id' ) ); ?>
                    </div>
				<?php endif; ?><?php endif; ?>

                <aside class="llms-extra <?php echo ( $restrictions['is_restricted'] ) || ( empty( $is_enrolled ) && $lesson->is_free() ) ? ' llms-extra-locked' : ( ! is_user_logged_in() ? ' llms-extra-logged-out' : '' ); ?>">
                    
					<span class="llms-lesson-counter">
						<?php
						printf(
							/* translators: 1: Lesson Order, 2: Total Lesson. */
							_x(
								'%1$d of %2$d',
								'lesson order within section',
								'buddyboss-theme'
							),
							buddyboss_theme()->lifterlms_helper()->bb_lifterlms_get_lesson_order( $lesson ),
							$total_lessons
						);
						?>
					</span>

					<?php if ( !empty( $lesson->get_preview_icon_html() ) ) {
						echo $lesson->get_preview_icon_html();	
					} else { ?>
						<span class="llms-lesson-complete"><i class="fa fa-check-circle"></i></span>
					<?php } ?>

                </aside>

			<?php endif; ?>

            <a class="llms-lesson-link<?php echo $restrictions['is_restricted'] ? ' llms-lesson-link-locked' : ''; ?> <?php echo ( ( $quiz_id != 0 ) || ( $assignment_id != 0 ) ) ? '' : 'no_quiz_and_assigment'; ?>" href="<?php echo ( ! $restrictions['is_restricted'] ) ? get_permalink( $lesson->get( 'id' ) ) : '#llms-lesson-locked'; ?>"<?php echo $data_msg; ?>>

                <section class="llms-main">
					<?php if ( 'lesson' === get_post_type( get_the_ID() ) ) : ?>
                        <h6 class="llms-pre-text"><?php if ( !empty($pre_text) ) { echo $pre_text; } ?></h6>
					<?php endif; ?>
					<h4 class="llms-h5 llms-lesson-title">
						<?php echo get_the_title( $lesson->get( 'id' ) ); ?>
						<?php if ( $lesson->is_free() ) { ?>
							<span class="llms-lesson-free"><?php _e( 'Free', 'buddyboss-theme' ); ?></span>
						<?php } ?>
					</h4>
					<?php if ( apply_filters( 'llms_show_preview_excerpt',
							true ) && llms_get_excerpt( $lesson->get( 'id' ) ) ) : ?>
                        <div class="llms-lesson-excerpt"><?php echo llms_get_excerpt( $lesson->get( 'id' ) ); ?></div>
					<?php endif; ?>
                </section>

			</a>

			<?php if ( $restrictions['is_restricted'] ) : ?><?php endif; ?>
        </div>


    </div>

	<?php if ( $quiz_id != 0 ): ?>
	<?php if ( ( get_the_ID() == $quiz_id ) || ( get_the_ID() == $assignment_id ) ) : ?>
    <div class="quizzes_section_holder <?php if ( get_the_ID() == $quiz_id ) : ?>current_quizz<?php endif; ?>" style="display:block;">
		<?php else: ?>
        <div class="quizzes_section_holder">
			<?php endif;
			?>
            <div class="llms-lesson-preview llms-quizz-preview <?php if ( is_user_logged_in() && !empty( $quizz_status ) ): echo $quizz_status; endif; ?>">
                <a class="llms-lesson-link<?php echo $restrictions['is_restricted'] ? ' llms-lesson-link-locked' : ''; ?>" href="<?php echo ! $restrictions['is_restricted'] ? get_the_permalink( $quiz_id ) : '#llms-lesson-locked'; ?>"<?php echo $data_msg; ?>>
                    <h5><?php echo get_the_title( $quiz_id ); ?></h5>
                </a>
            </div>
        </div>
		<?php endif;

		if ( ! empty( $assignment_id ) ): ?>
		<?php if ( ( get_the_ID() == $quiz_id ) || ( get_the_ID() == $assignment_id ) ) : ?>
        <div class="assignment_section_holder quizzes_section_holder <?php if ( get_the_ID() == $assignment_id ) : ?>current_quizz<?php endif; ?>" style="display:block;">
			<?php else: ?>
            <div class="assignment_section_holder quizzes_section_holder">
				<?php endif;
				?>
                <div class="llms-lesson-preview llms-quizz-preview <?php if ( is_user_logged_in() ): echo $assignment_status; endif; ?>">
                    <a class="llms-lesson-link<?php echo $restrictions['is_restricted'] ? ' llms-lesson-link-locked' : ''; ?>" href="<?php echo ! $restrictions['is_restricted'] ? get_the_permalink( $assignment_id ) : '#llms-lesson-locked'; ?>"<?php echo $data_msg; ?>>
                        <h5><?php echo get_the_title( $assignment_id ); ?></h5>
                    </a>
                </div>
            </div>
			<?php endif; ?>
        </div>
