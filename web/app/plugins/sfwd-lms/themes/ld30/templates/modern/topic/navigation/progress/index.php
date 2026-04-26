<?php
/**
 * View: Topic Navigation Progress area.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Topic    $topic  The topic model.
 * @var WP_User  $user   WP_User object.
 * @var Template $this   Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Topic;
use LearnDash\Core\Template\Template;

// If the user is not logged in, we don't show the progress area.
if ( ! $user->exists() ) {
	return;
}

?>
<div class="ld-navigation__progress">
	<?php
	if ( $topic->is_complete() ) {
		$this->template( 'modern/topic/navigation/progress/completed' );
	} else {
		$this->template( 'modern/topic/navigation/progress/mark-complete' );
	}
	?>
</div>
