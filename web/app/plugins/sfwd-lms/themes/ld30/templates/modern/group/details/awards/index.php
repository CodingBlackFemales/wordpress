<?php
/**
 * View: Group Details - Awards.
 *
 * @since 4.22.0
 * @version 4.22.0
 *
 * @var Group    $group Group model.
 * @var Template $this  Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;
use LearnDash\Core\Models\Group;

if ( ! $group->get_award_certificate() ) {
	return;
}
?>
<div class="ld-details__section ld-details__section--awards">
	<span
		aria-level="3"
		class="ld-details__heading"
		role="heading"
	>
		<?php esc_html_e( 'Completion Awards', 'learndash' ); ?>
	</span>

	<?php $this->template( 'modern/group/details/awards/certificate' ); ?>
</div>
