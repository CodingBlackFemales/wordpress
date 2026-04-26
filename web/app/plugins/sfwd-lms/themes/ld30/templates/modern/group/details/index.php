<?php
/**
 * View: Group Details.
 *
 * @since 4.22.0
 * @version 4.22.0
 *
 * @var Group    $group          Group model.
 * @var int      $courses_number Number of courses.
 * @var Template $this           Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Group;
use LearnDash\Core\Template\Template;

if (
	! $group->get_award_certificate()
	&& $courses_number <= 0
) {
	return;
}
?>
<div class="ld-details">
	<?php $this->template( 'modern/group/details/awards' ); ?>

	<?php $this->template( 'modern/group/details/includes' ); ?>
</div>
