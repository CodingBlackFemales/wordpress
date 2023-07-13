<?php
/**
 * View: Step Contents.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var Step     $step  Step.
 * @var int      $depth Depth.
 * @var Template $this  Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

use LearnDash\Core\Template\Steps\Step;
use LearnDash\Core\Template\Template;

if ( $depth > 0 ) {
	return;
}
?>
<div class="ld-steps__contents">
	<?php foreach ( $step->get_contents() as $contents_item ) : ?>
		<?php
		$this->template(
			'components/steps/step/contents/item',
			[
				'label' => $contents_item['label'],
				'icon'  => $contents_item['icon'],
			]
		);
		?>
	<?php endforeach; ?>
</div>
