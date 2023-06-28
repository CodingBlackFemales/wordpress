<?php
/**
 * View: Focus Mode Sidebar.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var Course_Step $model Course step model.
 * @var WP_User     $user  User.
 * @var Template    $this  Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

use LearnDash\Core\Models\Interfaces\Course_Step;
use LearnDash\Core\Template\Template;

if ( ! $model->get_course() ) {
	return;
}

global $course_pager_results; // TODO: Get rid of it.

// TODO: Split more.

// TODO: We need to support sidebar collapsing and position. It was controlled by a wrapper before, with a filer and an additional function. I think it must be here and we need to avoid a function usage.
// How it worked: <div class="ld-focus ld-focus-initial-transition <?php echo esc_attr( apply_filters( 'learndash_focus_mode_collapse_sidebar', false ) ? 'ld-focus-sidebar-collapsed ld-focus-sidebar-filtered' : '' ); echo esc_attr( learndash_30_get_focus_mode_sidebar_position() ); ">.
?>
<div class="ld-focus-sidebar">
	<?php $this->template( 'focus/sidebar-heading' ); ?>

	<div class="ld-focus-sidebar-wrapper">
		<div class="ld-course-navigation">
			<div class="ld-course-navigation-list">
				<div class="ld-lesson-navigation">
					<div class="ld-lesson-items" id="<?php echo esc_attr( 'ld-lesson-list-' . $model->get_course()->get_id() ); ?>">
						<?php
						$this->template(
							'widgets/navigation/rows',
							array( // TODO: Refactor arguments.
								/**
								 * Filters focus mode navigation settings.
								 *
								 * @since 4.6.0
								 *
								 * @param array<string, bool> $args Focus mode navigation settings.
								 */
								'widget_instance'      => apply_filters(
									'learndash_focus_mode_navigation_settings',
									array(
										'show_lesson_quizzes' => true,
										'show_topic_quizzes'  => true,
										'show_course_quizzes' => true,
									)
								),
								'course_id'            => $model->get_course()->get_id(),
								'lessons'              => learndash_get_course_lessons_list(
									$model->get_course()->get_id(),
									$user->ID,
									learndash_focus_mode_lesson_query_args( $model->get_course()->get_id() )
								),
								'has_access'           => $model->get_course()->get_product()->user_has_access( $user ),
								'user_id'              => $user->ID,
								'course_pager_results' => $course_pager_results, // TODO: if it's global, why not define it there.
							)
						);
						?>
					</div>
				</div>
			</div>
		</div>
	</div>
</div>
