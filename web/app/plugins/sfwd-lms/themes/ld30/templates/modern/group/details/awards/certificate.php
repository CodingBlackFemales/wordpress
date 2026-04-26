<?php
/**
 * View: Group Details Awards - Certificate.
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
<div class="ld-details__item">
	<div class="ld-details__icon-wrapper">
		<?php
		$this->template(
			'components/icons/certificate',
			[
				'classes' => [ 'ld-details__icon' ],
			]
		);
		?>
	</div>

	<span class="ld-details__label ld-details__label--certificate">
		<b><?php echo esc_html( learndash_get_custom_label( 'certificate' ) ); ?></b>
	</span>
</div>
