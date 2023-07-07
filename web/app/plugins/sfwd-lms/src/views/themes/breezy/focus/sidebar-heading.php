<?php
/**
 * View: Focus Mode Sidebar Heading.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var Course_Step $model Course step model.
 * @var WP_User     $user  User.
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

if ( ! $model->get_course() ) {
	return;
}

// TODO: Here for ld-icon was used echo esc_attr( learndash_30_get_focus_mode_sidebar_arrow_class() );, I think it must be refactored.
?>
<div class="ld-course-navigation-heading">
	<span class="ld-focus-sidebar-trigger">
		<span class="ld-icon"></span>
	</span>

	<h3>
		<a
			href="<?php echo esc_url( (string) get_permalink( $model->get_course()->get_post() ) ); ?>"
			id="ld-focus-mode-course-heading"
		>
			<span class="ld-icon ld-icon-content"></span>

			<?php echo esc_html( $model->get_course()->get_post()->post_title ); ?>
		</a>
	</h3>
</div>
