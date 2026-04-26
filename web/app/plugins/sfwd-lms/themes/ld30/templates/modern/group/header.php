<?php
/**
 * View: Group Header.
 *
 * @since 4.22.0
 * @version 4.24.0
 *
 * @var Template $this Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

?>
<div class="ld-layout__header">
	<?php $this->template( 'modern/components/alerts' ); ?>

	<?php
	$this->template(
		'modern/components/progress-bar',
		[
			'label_steps' => learndash_get_custom_label( 'courses' ),
		]
	);
	?>
</div>
