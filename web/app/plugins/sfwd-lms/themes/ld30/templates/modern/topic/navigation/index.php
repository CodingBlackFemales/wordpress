<?php
/**
 * View: Topic Navigation.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Topic    $topic The topic model.
 * @var WP_User  $user  WP_User object.
 * @var Template $this  Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Topic;
use LearnDash\Core\Template\Template;

if ( ! $topic->get_course() ) {
	return;
}

$classes = [
	'ld-navigation',
	'ld-navigation--topic',
	// If the user is not logged in, we don't show the progress area.
	! $user->exists() ? 'ld-navigation--no-progress' : '',
];

?>
<div class="<?php echo esc_attr( implode( ' ', $classes ) ); ?>">
	<?php $this->template( 'modern/topic/navigation/progress' ); ?>

	<?php $this->template( 'modern/topic/navigation/previous' ); ?>

	<?php $this->template( 'modern/topic/navigation/next' ); ?>

	<?php $this->template( 'modern/topic/navigation/back-to-course' ); ?>
</div>
