<?php
/**
 * View: Course Accordion Final Quizzes - Heading.
 *
 * @since 4.21.0
 * @version 4.24.0
 *
 * @var Quiz[]   $final_quizzes Array of quiz model objects.
 * @var Template $this          Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Quiz;
use LearnDash\Core\Template\Template;
?>
<span
	aria-level="3"
	class="ld-accordion__subheading ld-accordion__subheading--quizzes"
	role="heading"
>
	<?php
	echo esc_html(
		sprintf(
			/* translators: %1$s: Quiz label singular, %2$s: Quiz label plural */
			_n( // phpcs:ignore WordPress.WP.I18n.MismatchedPlaceholders -- It's intentional to allow proper translation.
				'Final %1$s',
				'Final %2$s',
				count( $final_quizzes ),
				'learndash'
			),
			learndash_get_custom_label( 'quiz' ),
			learndash_get_custom_label( 'quizzes' )
		)
	);
	?>
</span>
