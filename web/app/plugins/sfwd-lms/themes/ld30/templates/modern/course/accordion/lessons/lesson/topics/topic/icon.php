<?php
/**
 * View: Course Accordion Topic - Icon.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var Topic    $topic Topic model object.
 * @var Template $this  Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Topic;
use LearnDash\Core\Template\Template;

?>
<?php if ( $topic->is_complete() ) : ?>
	<?php
	$this->template(
		'components/icons/lesson-complete',
		[
			'classes' => [
				'ld-accordion__item-icon',
				'ld-accordion__item-icon--progress',
			],
			'label'   => sprintf(
				// translators: %s: Topic label.
				__( '%s complete', 'learndash' ),
				learndash_get_custom_label( 'topic' )
			),
		]
	);
	?>
<?php else : ?>
	<?php
	$this->template(
		'components/icons/lesson',
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
