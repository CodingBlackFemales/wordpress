<?php
/**
 * View: Lesson Navigation.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Lesson   $lesson The lesson model.
 * @var WP_User  $user   WP_User object.
 * @var Template $this   Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Lesson;
use LearnDash\Core\Template\Template;

if ( ! $lesson->get_course() ) {
	return;
}

$classes = [
	'ld-navigation',
	'ld-navigation--lesson',
	// If the user is not logged in, we don't show the progress area.
	! $user->exists() ? 'ld-navigation--no-progress' : '',
];

?>
<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
	<?php $this->template( 'modern/lesson/navigation/progress' ); ?>

	<?php $this->template( 'modern/lesson/navigation/previous' ); ?>

	<?php $this->template( 'modern/lesson/navigation/next' ); ?>

	<?php $this->template( 'modern/lesson/navigation/back-to-course' ); ?>
</div>
