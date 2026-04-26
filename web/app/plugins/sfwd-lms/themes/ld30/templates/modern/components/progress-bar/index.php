<?php
/**
 * View: Progress Bar.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var bool         $has_access   Whether the user has access to the rendered element or not.
 * @var WP_User      $user         Current user.
 * @var Progress_Bar $progress_bar Progress Bar.
 * @var Template     $this         Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Progression\Bar as Progress_Bar;
use LearnDash\Core\Template\Template;

if (
	! $has_access
	|| ! $user->exists()
	|| ! $progress_bar->should_show()
) {
	return;
}

?>

<div class="ld-progress-bar">
	<?php $this->template( 'modern/components/progress-bar/label' ); ?>

	<?php $this->template( 'modern/components/progress-bar/meter' ); ?>

	<?php $this->template( 'modern/components/progress-bar/last-activity' ); ?>
</div>
