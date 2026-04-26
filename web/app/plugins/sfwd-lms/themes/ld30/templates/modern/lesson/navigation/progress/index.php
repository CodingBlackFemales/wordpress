<?php
/**
 * View: Lesson Navigation Progress area.
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

// If the user is not logged in, we don't show the progress area.
if ( ! $user->exists() ) {
	return;
}

?>
<div class="ld-navigation__progress">
	<?php
	if ( $lesson->is_complete() ) {
		$this->template( 'modern/lesson/navigation/progress/completed' );
	} else {
		$this->template( 'modern/lesson/navigation/progress/mark-complete' );
	}
	?>
</div>
