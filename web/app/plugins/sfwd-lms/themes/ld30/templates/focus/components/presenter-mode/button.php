<?php
/**
 * View: Presenter Mode Button.
 *
 * @since 4.23.0
 * @version 4.23.0
 *
 * @var Template $this Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

?>

<button
	aria-expanded="true"
	aria-controls="ld-focus-sidebar ld-focus-header"
	class="ld-presenter-mode__button"
>
	<?php
	$this->template(
		'components/icons/play',
		[
			'classes'        => [
				'ld-presenter-mode__icon',
				'ld-presenter-mode__icon--activate',
			],
			'is_aria_hidden' => true,
		]
	);
	?>

	<?php
	$this->template(
		'components/icons/stop',
		[
			'classes'        => [
				'ld-presenter-mode__icon',
				'ld-presenter-mode__icon--deactivate',
			],
			'is_aria_hidden' => true,
		]
	);
	?>

	<?php $this->template( 'focus/components/presenter-mode/label-inactive' ); ?>

	<?php $this->template( 'focus/components/presenter-mode/label-active' ); ?>
</button>
