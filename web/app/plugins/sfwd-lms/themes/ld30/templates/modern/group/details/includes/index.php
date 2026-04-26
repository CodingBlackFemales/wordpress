<?php
/**
 * View: Group Details - Includes.
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

use LearnDash\Core\Template\Template;
use LearnDash\Core\Models\Group;

if ( $courses_number <= 0 ) {
	return;
}
?>
<div class="ld-details__section ld-details__section--includes">
	<span
		aria-level="3"
		class="ld-details__heading"
		role="heading"
	>
		<?php esc_html_e( 'Includes', 'learndash' ); ?>
	</span>

	<?php $this->template( 'modern/group/details/includes/courses' ); ?>
</div>
