<?php
/**
 * LearnDash LD30 Displays a lesson content (topics and quizzes)
 *
 * Available Variables:
 *
 * $user_id   :   The current user ID
 * $course_id :   The current course ID
 *
 * $lesson    :   The current lesson
 *
 * $topics    :   An array of the associated topics
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

global $course_pager_results;

$lesson_progress = learndash_lesson_progress( $lesson['post'], $course_id );
$is_sample       = learndash_is_sample( $lesson['post'] );
$has_pagination  = ( isset( $course_pager_results[ $lesson['post']->ID ]['pager'] ) ? true : false );
$table_class     = 'ld-table-list ld-topic-list'
					. ( true === $is_sample ? ' is_sample' : '' )
					. ( ! $has_pagination ? ' ld-no-pagination' : '' );

/**
 * Fires before the topic list.
 *
 * @since 3.0.0
 *
 * @param int $lesson_id Lesson ID.
 * @param int $course_id Course ID.
 * @param int $user_id   User ID.
 */
do_action( 'learndash-topic-list-before', $lesson['post']->ID, $course_id, $user_id ); ?>

<div class="
			<?php
			/**
			 * Filters lesson listing table CSS class.
			 *
			 * @since 3.0.0
			 *
			 * @param string $table_class Lesson table CSS class list.
			 */
			echo esc_attr( apply_filters( 'ld-lesson-table-class', $table_class ) );
			?> <?php echo esc_attr( 'ld-expand-' . $lesson['post']->ID ); ?>" id="<?php echo esc_attr( 'ld-expand-' . $lesson['post']->ID ); ?>">

	<div class="ld-table-list-header ld-primary-background">

		<?php
		/**
		 * Fires before the topic listing header.
		 *
		 * @since 3.0.0
		 *
		 * @param int $lesson_id Lesson ID.
		 * @param int $course_id Course ID.
		 * @param int $user_id   User ID.
		 */
		do_action( 'learndash-topic-list-heading-before', $lesson['post']->ID, $course_id, $user_id );
		?>

		<div class="ld-table-list-title">
			<span class="ld-item-icon">
				<span class="ld-icon ld-icon-content"></span>
			</span>
			<span class="ld-text">
				<?php
				// translators: Course Status Label.
				echo sprintf( esc_html_x( '%s Content', 'Lesson Content Label', 'learndash' ), esc_attr( LearnDash_Custom_Label::get_label( 'lesson' ) ) );
				?>
			</span>
		</div> <!--/.ld-tablet-list-title-->
		<div class="ld-table-list-lesson-details">
			<?php
			/**
			 * Fires before the lesson progress stats.
			 *
			 * @since 3.0.0
			 *
			 * @param int $lesson_id Lesson ID.
			 * @param int $course_id Course ID.
			 * @param int $user_id   User ID.
			 */
			do_action( 'learndash-topic-list-progress-before', $lesson['post']->ID, $course_id, $user_id );
			?>

			<?php if ( $lesson_progress ) : ?>
				<?php
				/**
				 * Filters whether to show lesson progress in lesson listing.
				 *
				 * @since 3.0.7.1
				 *
				 * @param boolean $show_progress Whether to show lesson progress.
				 * @param int     $lesson_id     Lesson ID.
				 * @param int     $course_id     Course ID
				 * @param int     $user_id       User ID
				 */
				if ( true === (bool) apply_filters( 'learndash_show_lesson_list_progress', true, $lesson['post']->ID, $course_id, $user_id ) ) {
					?>
					<span class="ld-lesson-list-progress">
					<?php
					echo sprintf(
						// translators: placeholder: Lesson Complete Percentage.
						esc_html_x( '%s%% Complete', 'Lesson Complete Percentage', 'learndash' ),
						esc_html( $lesson_progress['percentage'] )
					);
					?>
					</span>
				<?php } ?>
				<?php
				/**
				 * Filters whether to show lesson steps in lesson listing.
				 *
				 * @since 3.0.7.1
				 *
				 * @param boolean $show_steps Whether to show lesson steps.
				 * @param int     $lesson_id  Lesson ID.
				 * @param int     $course_id  Course ID
				 * @param int     $user_id    User ID
				 */
				if ( true === (bool) apply_filters( 'learndash_show_lesson_list_steps', true, $lesson['post']->ID, $course_id, $user_id ) ) {
					?>
					<span class="ld-lesson-list-steps">
					<?php
					echo sprintf(
						// translators: placeholder: %1$s: Lesson Steps Complete %2$s: Total lesson steps.
						esc_html_x( '%1$d/%2$d Steps', '%1$s: Lesson Steps Complete %2$s: Total lesson steps', 'learndash' ),
						esc_html( $lesson_progress['completed'] ),
						esc_html( $lesson_progress['total'] )
					);
					?>
					</span>
				<?php } ?>
			<?php endif; ?>

			<?php
			/**
			 * Fires after the lesson progress stats.
			 *
			 * @since 3.0.0
			 *
			 * @param int $lesson_id Lesson ID.
			 * @param int $course_id Course ID.
			 * @param int $user_id   User ID.
			 */
			do_action( 'learndash-topic-list-progress-after', $lesson['post']->ID, $course_id, $user_id );
			?>

			<?php if ( 'sfwd-lesson' === get_post_type() ) : ?>
				<span class="ld-expand-button" data-ld-expands="<?php echo esc_attr( 'ld-topic-list-' . $lesson['post']->ID ); ?>">
					<span class="icon-simple-arrow-down ld-icon">
					<span class="ld-text"><?php esc_html_e( 'Expand', 'learndash' ); ?></span>
				</span> <!--/.ld-expand-button-->
			<?php endif; ?>

		</div> <!--/.ld-table-list-lesson-details-->

		<?php
		/**
		 * Fires after topic listing header.
		 *
		 * @since 3.0.0
		 *
		 * @param int $lesson_id Lesson ID.
		 * @param int $course_id Course ID.
		 * @param int $user_id   User ID.
		 */
		do_action( 'learndash-topic-list-heading-after', $lesson['post']->ID, $course_id, $user_id );
		?>

	</div> <!--/.ld-table-list-header-->

	<div class="ld-table-list-items <?php echo esc_attr( 'ld-topic-list-' . $lesson['post']->ID ); ?>" id="<?php echo esc_attr( 'ld-topic-list-' . $lesson['post']->ID ); ?>" data-ld-expand-list>

		<?php
		if ( $topics && ! empty( $topics ) ) :
			foreach ( $topics as $key => $topic ) :
				learndash_get_template_part(
					'topic/partials/row.php',
					array(
						'topic'     => $topic,
						'user_id'   => $user_id,
						'course_id' => $course_id,
					),
					true
				);
			endforeach;
		endif;

		$show_lesson_quizzes = true;
		if ( isset( $course_pager_results[ $lesson['post']->ID ]['pager'] ) && ! empty( $course_pager_results[ $lesson['post']->ID ]['pager'] ) ) :
			$show_lesson_quizzes = ( $course_pager_results[ $lesson['post']->ID ]['pager']['paged'] == $course_pager_results[ $lesson['post']->ID ]['pager']['total_pages'] ? true : false );
		endif;
		/** This filter is documented in themes/ld30/includes/helpers.php */
		$show_lesson_quizzes = apply_filters( 'learndash-show-lesson-quizzes', $show_lesson_quizzes, $lesson['post']->ID, $course_id, $user_id );


		if ( ! empty( $quizzes ) && $show_lesson_quizzes ) :
			foreach ( $quizzes as $quiz ) :
				learndash_get_template_part(
					'quiz/partials/row.php',
					array(
						'quiz'      => $quiz,
						'user_id'   => $user_id,
						'course_id' => $course_id,
						'lesson'    => $lesson,
					    'context'   => 'lesson',
					),
					true
				);
			endforeach;
		endif;
		?>

	</div> <!--/.ld-table-list-items-->

	<div class="ld-table-list-footer">
		<?php
		/**
		 * Fires before the course pagination.
		 *
		 * @since 3.0.0
		 *
		 * @param int $lesson_id Lesson ID.
		 * @param int $course_id Course ID.
		 * @param int $user_id   User ID.
		 */
		do_action( 'learndash-lesson-pagination-before', $lesson['post']->ID, $course_id, $user_id );

		if ( isset( $course_pager_results[ $lesson['post']->ID ]['pager'] ) ) {
			learndash_get_template_part(
				'modules/pagination.php',
				array(
					'pager_results'   => $course_pager_results[ $lesson['post']->ID ]['pager'],
					'pager_context'   => 'course_topics',
					'href_query_arg'  => 'ld-topic-page',
					'lesson_id'       => $lesson['post']->ID,
					'course_id'       => $course_id,
					'href_val_prefix' => $lesson['post']->ID . '-',
				),
				true
			);
		}

		/**
		 * Fires after the lesson pagination.
		 *
		 * @since 3.0.0
		 *
		 * @param int $lesson_id Lesson ID.
		 * @param int $course_id Course ID.
		 * @param int $user_id   User ID.
		 */
		do_action( 'learndash-lesson-pagination-after', $lesson['post']->ID, $course_id, $user_id );
		?>
	</div> <!--/.ld-table-list-footer-->

</div> <!--/.ld-table-list-->

<?php
/**
 * Fires after topic list.
 *
 * @since 3.0.0
 *
 * @param int $lesson_id Lesson ID.
 * @param int $course_id Course ID.
 * @param int $user_id   User ID.
 */
do_action( 'learndash-topic-list-after', $lesson['post']->ID, $course_id, $user_id ); ?>
