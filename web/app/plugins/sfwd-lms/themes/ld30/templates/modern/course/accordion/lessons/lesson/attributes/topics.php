<?php
/**
 * View: Course Accordion Lesson Attribute - Topics.
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

$topics_number = $lesson->get_topics_number();

if ( $topics_number <= 0 ) {
	return;
}
?>
<div class="ld-accordion__item-attribute ld-accordion__item-attribute--lesson-topics">
	<?php
	$this->template(
		'components/icons/lesson',
		[
			'classes'        => [ 'ld-accordion__item-attribute-icon' ],
			'is_aria_hidden' => true,
		]
	);
	?>

	<span class="ld-accordion__item-attribute-label">
		<?php
		echo esc_html(
			sprintf(
				// translators: %1$d: Topic Count, %2$s: Topic label singular, %3$s: Topic label plural.
				_n(
					'%1$d %2$s',
					'%1$d %3$s',
					$topics_number,
					'learndash'
				),
				$topics_number,
				learndash_get_custom_label( 'topic' ),
				learndash_get_custom_label( 'topics' )
			)
		);
		?>
	</span>
</div>
