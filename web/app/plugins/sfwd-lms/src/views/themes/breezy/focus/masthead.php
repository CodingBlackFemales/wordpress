<?php
/**
 * View: Focus Mode Masthead.
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

?>
<div class="ld-focus-header">
	<?php $this->template( 'focus/masthead-mobile-menu-trigger' ); ?>

	<?php $this->template( 'focus/logo' ); ?>

	<?php if ( $model->get_course() ) : ?>
		<?php
		if ( $user->ID > 0 ) { // TODO: Move this check to a module/progress maybe?
			// TODO: Add this template.
			$this->template(
				'components/progress',
				array(
					'course_id' => $model->get_course()->get_id(),
				)
			);
		}
		?>

		<?php
		// TODO: Refactor.
		if (
			! learndash_lesson_progression_enabled( $model->get_course()->get_id() )
			|| learndash_can_user_bypass( $user->ID, 'learndash_course_progression' )
		) {
			$can_complete = learndash_user_is_course_children_progress_complete( $user->ID, $model->get_course()->get_id(), $model->get_id() ); // @phpstan-ignore-line -- will be refactored.
		} else {
			$can_complete = learndash_can_complete_step( $user->ID, $model->get_id(), $model->get_course()->get_id(), true ); // @phpstan-ignore-line -- will be refactored.
		}

		$this->template(
			'components/course-steps',
			array(
				'course_id'        => $model->get_course()->get_id(),
				'course_step_post' => $model->get_post(), // @phpstan-ignore-line -- will be refactored.
				'user_id'          => $user->ID,
				'course_settings'  => array(),
				'can_complete'     => $can_complete,
				'context'          => 'focus',
			)
		);
		?>
	<?php endif; ?>

	<?php $this->template( 'focus/masthead-menu' ); ?>
</div>
