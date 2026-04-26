<?php
/**
 * View: Topic Content.
 *
 * @since 4.24.0
 * @version 4.25.8
 *
 * @var bool     $is_content_visible Whether the content is visible.
 * @var Topic    $topic              The topic model.
 * @var Template $this               Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Topic;
use LearnDash\Core\Template\Template;

if ( ! $is_content_visible ) {
	return;
}

?>
<main class="ld-layout__content">
	<?php $this->template( 'modern/components/tabs' ); ?>

	<?php $this->template( 'modern/topic/accordion' ); ?>

	<?php
	$this->template(
		'modern/components/assignments',
		[
			'model' => $topic,
		]
	);
	?>

	<?php $this->template( 'modern/topic/navigation' ); ?>
</main>
