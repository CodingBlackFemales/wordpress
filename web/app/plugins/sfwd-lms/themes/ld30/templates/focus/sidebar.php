<?php
/**
 * LearnDash LD30 focus mode sidebar.
 *
 * @since 3.0.0
 *
 * @package LearnDash\Templates\LD30
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
$has_access = sfwd_lms_has_access( $course_id );
global $course_pager_results;

/** This action is documented in themes/ld30/templates/focus/index.php */
do_action( 'learndash-focus-sidebar-before', $course_id, $user_id ); ?>

<div class="ld-focus-sidebar">
	<div class="ld-course-navigation-heading">

		<?php
		/**
		 * Fires before the sidebar trigger wrapper in the focus template.
		 *
		 * @since 3.0.0
		 *
		 * @param int $course_id Course ID.
		 * @param int $user_id   User ID.
		 */
		do_action( 'learndash-focus-sidebar-trigger-wrapper-before', $course_id, $user_id );
		?>

		<span class="ld-focus-sidebar-trigger">
			<?php
			/**
			 * Fires before the sidebar trigger in the focus template.
			 *
			 * @since 3.0.0
			 *
			 * @param int $course_id Course ID.
			 * @param int $user_id   User ID.
			 */
			do_action( 'learndash-focus-sidebar-trigger-before', $course_id, $user_id );
			?>
			<span class="ld-icon <?php echo esc_attr( learndash_30_get_focus_mode_sidebar_arrow_class() ); ?>"></span>
			<?php
			/**
			 * Fires after the sidebar trigger in the focus template.
			 *
			 * @since 3.0.0
			 *
			 * @param int $course_id Course ID.
			 * @param int $user_id   User ID.
			 */
			do_action( 'learndash-focus-sidebar-trigger-after', $course_id, $user_id );
			?>
		</span>

		<?php
		/**
		 * Fires after the sidebar trigger wrapper in the focus template.
		 *
		 * @since 3.0.0
		 *
		 * @param int $course_id Course ID.
		 * @param int $user_id   User ID.
		 */
		do_action( 'learndash-focus-sidebar-trigger-wrapper-after', $course_id, $user_id );
		?>

		<?php
		/**
		 * Fires before the sidebar heading in the focus template.
		 *
		 * @since 3.0.0
		 *
		 * @param int $course_id Course ID.
		 * @param int $user_id   User ID.
		 */
		do_action( 'learndash-focus-sidebar-heading-before', $course_id, $user_id );
		?>

		<h3>
			<a href="<?php echo esc_url( get_the_permalink( $course_id ) ); ?>" id="ld-focus-mode-course-heading">
				<span class="ld-icon ld-icon-content"></span>
				<?php echo esc_html( get_the_title( $course_id ) ); ?>
			</a>
		</h3>
		<?php
		/**
		 * Fires after the sidebar heading in the focus template.
		 *
		 * @since 3.0.0
		 *
		 * @param int $course_id Course ID.
		 * @param int $user_id   User ID.
		 */
		do_action( 'learndash-focus-sidebar-heading-after', $course_id, $user_id );
		?>
	</div>
	<div class="ld-focus-sidebar-wrapper">
		<?php
		/**
		 * Fires inside the sidebar heading navigation in the focus template.
		 *
		 * @since 3.0.0
		 *
		 * @param int $course_id Course ID.
		 * @param int $user_id   User ID.
		 */
		do_action( 'learndash-focus-sidebar-between-heading-navigation', $course_id, $user_id );
		?>
		<div class="ld-course-navigation">
			<div class="ld-course-navigation-list">
				<div class="ld-lesson-navigation">
					<div class="ld-lesson-items" id="<?php echo esc_attr( 'ld-lesson-list-' . $course_id ); ?>">
						<?php
						/**
						 * Fires before the sidebar nav in the focus template.
						 *
						 * @since 3.0.0
						 *
						 * @param int $course_id Course ID.
						 * @param int $user_id   User ID.
						 */
						do_action( 'learndash-focus-sidebar-nav-before', $course_id, $user_id );

						$lessons = learndash_get_course_lessons_list( $course_id, $user_id, learndash_focus_mode_lesson_query_args( $course_id ) );

						/**
						 * Filters focus mode navigation setting arguments.
						 *
						 * @since 3.0.0
						 *
						 * @param array $navigation_setting_args An array of focus mode navigation settings.
						 */
						$widget_instance = apply_filters(
							'ld-focus-mode-navigation-settings',
							array(
								'show_lesson_quizzes' => true,
								'show_topic_quizzes'  => true,
								'show_course_quizzes' => true,
							)
						);

						learndash_get_template_part(
							'widgets/navigation/rows.php',
							array(
								'course_id'            => $course_id,
								'widget_instance'      => $widget_instance,
								'lessons'              => $lessons,
								'has_access'           => $has_access,
								'user_id'              => $user_id,
								'course_pager_results' => $course_pager_results,
							),
							true
						);

						/**
						 * Fires after the sidebar nav in the focus template.
						 *
						 * @since 3.0.0
						 *
						 * @param int $course_id Course ID.
						 * @param int $user_id   User ID.
						 */
						do_action( 'learndash-focus-sidebar-nav-after', $course_id, $user_id );
						?>
					</div> <!--/.ld-lesson-items-->
				</div> <!--/.ld-lesson-navigation-->
			</div> <!--/.ld-course-navigation-list-->
		</div> <!--/.ld-course-navigation-->
		<?php
		/**
		 * Fires after the sidebar nav wrapper in the focus template.
		 *
		 * @since 3.0.0
		 *
		 * @param int $course_id Course ID.
		 * @param int $user_id   User ID.
		 */
		do_action( 'learndash-focus-sidebar-after-nav-wrapper', $course_id, $user_id );
		?>
	</div> <!--/.ld-focus-sidebar-wrapper-->
</div> <!--/.ld-focus-sidebar-->

<?php
/**
 * Fires after the sidebar in the focus template.
 *
 * @since 3.0.0
 *
 * @param int $course_id Course ID.
 * @param int $user_id   User ID.
 */
do_action( 'learndash-focus-sidebar-after', $course_id, $user_id );
?>
