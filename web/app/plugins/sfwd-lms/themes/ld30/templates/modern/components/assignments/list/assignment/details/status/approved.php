<?php
/**
 * View: Assignment Status Approved badge.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Template $this Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

?>

<div class="ld-assignments__list-item-status ld-assignments__list-item-status--approved">
	<?php
	$this->template(
		'components/icons/check-2',
		[
			'classes' => [
				'ld-assignments__list-item-status-icon',
				'ld-assignments__list-item-status-icon--approved',
			],
		]
	);
	?>

	<?php echo esc_html_x( 'Approved', 'Assignment status approved label', 'learndash' ); ?>
</div>
