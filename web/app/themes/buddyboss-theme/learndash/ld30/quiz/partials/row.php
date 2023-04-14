<?php
/**
 * LearnDash LD30 Displays a single quiz row
 *
 * Available Variables:
 *
 * $user_id   :   The current user ID
 * $course_id :   The current course ID
 * $lesson    :   The current lesson
 * $topic     :   The current topic object
 * $quiz      :   The current quiz (array)
 *
 * @since   3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$quiz_classes = learndash_quiz_row_classes( $quiz, $context );
$is_sample    = ( isset( $lesson['sample'] ) ? $lesson['sample'] : false );

/**
 * Filters quiz row attributes. Used while displaying a single quiz row.
 *
 * @since 3.0.0
 *
 * @param string $attribute Quiz row attribute. The value is data-ld-tooltip if a user does not have access to quiz otherwise empty string.
 */
$atts               = apply_filters( 'learndash_quiz_row_atts', ( isset( $has_access ) && ! $has_access && ! $is_sample ? 'data-balloon-pos="up" data-balloon="' . esc_html__( "You don't currently have access to this content", 'buddyboss-theme' ) . '"' : '' ) );
$atts_access_marker = apply_filters( 'learndash_quiz_row_atts', ( isset( $has_access ) && ! $has_access && ! $is_sample ? '<span class="lms-is-locked-ico"><i class="bb-icon-f bb-icon-lock"></i></span>' : '' ) );
$attributes         = learndash_get_course_step_attributes( $quiz['post']->ID, $course_id, $user_id );

/**
 * Fires before the quiz row listing.
 *
 * @since 3.0.0
 *
 * @param int $quiz_id   Quiz ID.
 * @param int $course_id Course ID.
 * @param int $user_id   User ID.
 */
do_action( 'learndash-quiz-row-before', $quiz['post']->ID, $course_id, $user_id ); ?>
	<div id="<?php echo esc_attr( 'ld-table-list-item-' . $quiz['post']->ID ); ?>" class="<?php echo esc_attr( $quiz_classes['wrapper'] ); ?>" <?php echo wp_kses_post( $atts ); ?>>
		<div class="<?php echo esc_attr( $quiz_classes['preview'] ); ?>">
			<a class="<?php echo esc_attr( $quiz_classes['anchor'] ); ?>" href="<?php echo esc_url( learndash_get_step_permalink( $quiz['post']->ID, $course_id ) ); ?>">
				<?php
				/**
				 * Fires before the quiz row status.
				 *
				 * @since 3.0.0
				 *
				 * @param int $quiz_id   Post ID.
				 * @param int $course_id Course ID.
				 * @param int $user_id   User ID.
				 */
				do_action( 'learndash-quiz-row-status-before', $quiz['post']->ID, $course_id, $user_id );

				learndash_status_icon( $quiz['status'], 'sfwd-quiz', null, true );

				/**
				 * Fires before the quiz row title.
				 *
				 * @since 3.0.0
				 *
				 * @param int $quiz_id   Quiz ID.
				 * @param int $course_id Course ID.
				 * @param int $user_id   User ID.
				 */
				do_action( 'learndash-quiz-row-title-before', $quiz['post']->ID, $course_id, $user_id );
				?>

				<div class="ld-item-title">
					<?php
					echo '<span>';

					echo wp_kses_post( apply_filters( 'the_title', $quiz['post']->post_title, $quiz['post']->ID ) );

					echo $atts_access_marker;

					echo '</span>';

					?>
				</div>

				<?php
				if ( ! empty( $attributes ) && empty( $atts ) ) :
					foreach ( $attributes as $attribute ) :
						if ( $attribute['icon'] == 'ld-icon-calendar' ) :
							?>
							<span class="lms-quiz-status-icon" data-balloon-pos="left" data-balloon="<?php echo esc_attr( $attribute['label'] ); ?>"><i class="bb-icon-f bb-icon-lock"></i></span>
							<?php
						endif;
					endforeach;
				endif;
				?>

				<?php
				/**
				 * Fires after the quiz row title.
				 *
				 * @since 3.0.0
				 *
				 * @param int $quiz_id   Quiz ID.
				 * @param int $course_id Course ID.
				 * @param int $user_id   User ID.
				 */
				do_action( 'learndash-quiz-row-title-after', $quiz['post']->ID, $course_id, $user_id );
				?>
			</a>
		</div> <!--/.list-item-preview-->
	</div>
<?php
/**
 * Fires after the quiz row listing.
 *
 * @since 3.0.0
 *
 * @param int $quiz_id   Quiz ID.
 * @param int $course_id Course ID.
 * @param int $user_id   User ID.
 */
do_action( 'learndash-quiz-row-after', $quiz['post']->ID, $course_id, $user_id );
