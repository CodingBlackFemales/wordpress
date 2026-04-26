<?php
/**
 * View: Lesson Content.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Models\Lesson $lesson             The lesson model.
 * @var bool          $is_content_visible Whether the content is visible.
 * @var Template      $this               Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models;
use LearnDash\Core\Template\Template;

if ( ! $is_content_visible ) {
	return;
}

?>
<main class="ld-layout__content">
	<?php $this->template( 'modern/components/tabs' ); ?>

	<?php $this->template( 'modern/lesson/accordion' ); ?>

	<?php
	$this->template(
		'modern/components/assignments',
		[
			'model' => $lesson,
		]
	);
	?>

	<?php $this->template( 'modern/lesson/navigation' ); ?>
</main>
