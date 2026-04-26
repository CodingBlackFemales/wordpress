<?php
/**
 * View: Assignment Status Pending badge.
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

<div class="ld-assignments__list-item-status ld-assignments__list-item-status--pending">
	<?php
	$this->template(
		'components/icons/clock',
		[
			'classes' => [
				'ld-assignments__list-item-status-icon',
				'ld-assignments__list-item-status-icon--pending',
			],
		]
	);
	?>

	<?php echo esc_html_x( 'Pending', 'Assignment status pending label', 'learndash' ); ?>
</div>
