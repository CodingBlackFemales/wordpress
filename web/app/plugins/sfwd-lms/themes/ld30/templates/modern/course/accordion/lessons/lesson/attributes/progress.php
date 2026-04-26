<?php
/**
 * View: Course Accordion Lesson Attribute - Progress.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var Lesson   $lesson Lesson model object.
 * @var Template $this   Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Lesson;
use LearnDash\Core\Template\Template;

if ( ! $lesson->is_complete() ) {
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
