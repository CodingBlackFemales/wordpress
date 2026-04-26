<?php
/**
 * View: Lesson Accordion Topic Attribute - Progress.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Topic    $topic Topic model object.
 * @var Template $this  Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Topic;
use LearnDash\Core\Template\Template;

if ( ! $topic->is_complete() ) {
	return;
}
?>
<div class="ld-accordion__item-attribute ld-accordion__item-attribute--progress">
	<?php
	$this->template(
		'components/icons/check-circle',
		[
			'classes'        => [ 'ld-accordion__item-attribute-icon ld-accordion__item-attribute-icon--progress' ],
			'is_aria_hidden' => true,
		]
	);
	?>

	<span class="ld-accordion__item-attribute-label ld-accordion__item-attribute-label--progress">
		<?php esc_html_e( 'Complete', 'learndash' ); ?>
	</span>
</div>
