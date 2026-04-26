<?php
/**
 * View: Course Accordion Lesson Attribute - Quizzes.
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

$quizzes_number = $lesson->get_quizzes_number();

if ( $quizzes_number <= 0 ) {
	return;
}
?>
<div class="ld-accordion__item-attribute ld-accordion__item-attribute--quizzes">
	<?php
	$this->template(
		'components/icons/quiz',
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
				// translators: %1$d: Quizzes number, %2$s: Quiz label singular, %3$s: Quiz label plural.
				_n(
					'%1$d %2$s',
					'%1$d %3$s',
					$quizzes_number,
					'learndash'
				),
				$quizzes_number,
				learndash_get_custom_label( 'quiz' ),
				learndash_get_custom_label( 'quizzes' )
			)
		);
		?>
	</span>
</div>
