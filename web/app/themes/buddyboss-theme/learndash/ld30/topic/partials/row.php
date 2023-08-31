<?php
/**
 * LearnDash LD30 Displays a single topic row
 *
 * Available Variables:
 *
 * $user_id   : The current user ID
 * $course_id : The current course ID
 * $lesson    : The current lesson
 * $topic     : The current topic object
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Ajax pagination
 *
 * @var [type]
 */
$topic_id  = (int) ( isset( $_GET['widget_instance']['widget_instance']['current_step_id'] ) ? $_GET['widget_instance']['widget_instance']['current_step_id'] : $topic->ID );
$post_id   = (int) ( isset( $_GET['widget_instance']['widget_instance']['current_step_id'] ) ? $topic->ID : get_the_ID() );
$course_id = (int) $course_id;

/**
 * Filters topic row CSS class. Used while listing a topic row.
 *
 * @since 3.0.0
 *
 * @param string $row_class The list of topic row CSS classes.
 * @param object $topic     The Topic object.
 */
$topic_class = apply_filters(
	'learndash-topic-row-class',
	'ld-table-list-item-preview ld-primary-color-hover ld-topic-row ' .
	( $topic->completed ? 'learndash-complete' : 'learndash-incomplete' )
	. ' ' . ( $post_id == $topic_id ? 'ld-is-current-item' : '' ),
	$topic
);

/**
 * Filters the status of the topic. Used while listing a topic.
 *
 * @since 3.0.0
 *
 * @param string $topic_status The topic status. The value can be completed or not-completed.
 * @param object $topic        The topic object
 * @param int    $course_id    Course ID
 */
$topic_status = apply_filters( 'learndash-topic-status', ( $topic->completed ? 'completed' : 'not-completed' ), $topic, $course_id );

$topic_settings                = learndash_get_setting( $topic );
$lesson_video_enabled          = isset( $topic_settings['lesson_video_enabled'] ) ? $topic_settings['lesson_video_enabled'] : null;
$topic_video_progression_class = ! empty( $lesson_video_enabled ) ? 'ld-topic__video' : '';
$learndash_available_date      = learndash_course_step_available_date( $topic->ID, $course_id, $user_id, true );
$attributes                    = learndash_get_course_step_attributes( $topic->ID, $course_id, $user_id );

/**
 * Fires before a topic row.
 *
 * @since 3.0.0
 *
 * @param int $topic_id  Topic ID.
 * @param int $course_id Course ID.
 * @param int $user_id   User ID.
 */
do_action( 'learndash-topic-row-before', $topic->ID, $course_id, $user_id ); ?>
<div class="ld-table-list-item <?php echo esc_attr( $topic_video_progression_class ); ?> <?php echo ( ! empty( $learndash_available_date ) ) ? 'lms-topic-is-locked' : 'lms-topic-not-locked'; ?>" id="<?php echo esc_attr( 'ld-table-list-item-' . $topic->ID ); ?>">
	<a class="<?php echo esc_attr( $topic_class ); ?>" href="<?php echo esc_url( learndash_get_step_permalink( $topic->ID, $course_id ) ); ?>">
		<?php
		/**
		 * Fires before the topic status.
		 *
		 * @since 3.0.0
		 *
		 * @param int $topic_id  Topic ID.
		 * @param int $course_id Course ID.
		 * @param int $user_id   User ID.
		 */
		do_action( 'learndash-topic-row-status-before', $topic->ID, $course_id, $user_id );
		?>

		<?php learndash_status_icon( $topic_status, get_post_type(), null, true ); ?>

		<?php
		/**
		 * Fires before the topic title.
		 *
		 * @since 3.0.0
		 *
		 * @param int $topic_id  Topic ID.
		 * @param int $course_id Course ID.
		 * @param int $user_id   User ID.
		 */
		do_action( 'learndash-topic-row-title-before', $topic->ID, $course_id, $user_id );
		?>
		<span class="ld-topic-title">
			<?php echo wp_kses_post( apply_filters( 'the_title', $topic->post_title, $topic->ID ) ); ?>
			<?php
			if ( ! empty( $attributes ) ) :
				foreach ( $attributes as $attribute ) :
					if ( $attribute['icon'] == 'ld-icon-calendar' ) :
						?>
						<span class="lms-topic-status-icon" data-balloon-pos="left" data-balloon="<?php echo esc_attr( $attribute['label'] ); ?>"><i class="bb-icon-f bb-icon-lock"></i></span>
						<?php
					endif;
				endforeach;
			endif;
			?>
		</span> <?php // phpcs:ignore WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedHooknameFound ?>
		<?php
		/**
		 * Fires after the topic title.
		 *
		 * @since 3.0.0
		 *
		 * @param int $topic_id  Topic ID.
		 * @param int $course_id Course ID.
		 * @param int $user_id   User ID.
		 */
		do_action( 'learndash-topic-row-title-after', $topic->ID, $course_id, $user_id );
		?>
	</a>
</div> <!--/.ld-table-list-item-->
<?php

/**
 * Fires before a topic quiz row.
 *
 * @since 3.0.0
 *
 * @param int $topic_id  Topic ID.
 * @param int $course_id Course ID.
 * @param int $user_id   User ID.
 */
do_action( 'learndash-topic-quiz-row-before', $topic->ID, $course_id, $user_id );

$topic_quizzes = learndash_get_lesson_quiz_list( $topic, null, $course_id );

if ( $topic_quizzes && ! empty( $topic_quizzes ) ) :
	foreach ( $topic_quizzes as $quiz ) :
		learndash_get_template_part(
			'quiz/partials/row.php',
			array(
				'quiz'      => $quiz,
				'context'   => 'topic',
				'course_id' => $course_id,
				'user_id'   => $user_id,
			),
			true
		);
	endforeach;
endif;

/**
 * Fires after a topic quiz row.
 *
 * @since 3.0.0
 *
 * @param int $topic_id  Topic ID.
 * @param int $course_id Course ID.
 * @param int $user_id   User ID.
 */
do_action( 'learndash-topic-quiz-row-after', $topic->ID, $course_id, $user_id );

/**
 * Fires after topic row.
 *
 * @since 3.0.0
 *
 * @param int $topic_id  Topic ID.
 * @param int $course_id Course ID.
 * @param int $user_id   User ID.
 */
do_action( 'learndash-topic-row-after', $topic->ID, $course_id, $user_id ); ?>
