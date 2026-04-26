<?php
/**
 * View: Course Accordion Topic Quiz - Icon.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var Quiz     $quiz Quiz model object.
 * @var Template $this Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Quiz;
use LearnDash\Core\Template\Template;

?>
<?php if ( $quiz->is_complete() ) : ?>
	<?php
	$this->template(
		'components/icons/quiz-complete',
		[
			'classes' => [
				'ld-accordion__item-icon',
				'ld-accordion__item-icon--progress',
			],
			'label'   => sprintf(
				// translators: %s: Quiz label.
				__( '%s complete', 'learndash' ),
				learndash_get_custom_label( 'quiz' )
			),
		]
	);
	?>
<?php else : ?>
	<?php
	$this->template(
		'components/icons/quiz',
		[
			'classes'        => [
				'ld-accordion__item-icon',
			],
			'is_aria_hidden' => true,
		]
	);
	?>
	<?php
endif;
