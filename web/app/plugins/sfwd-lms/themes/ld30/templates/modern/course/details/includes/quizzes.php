<?php
/**
 * View: Course Details Includes - Quizzes.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var Course   $course Course model.
 * @var Template $this   Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;
use LearnDash\Core\Models\Course;

$quizzes_number = $course->get_quizzes_number();

if ( $quizzes_number <= 0 ) {
	return;
}
?>
<div class="ld-details__item">
	<div class="ld-details__icon-wrapper">
		<?php
		$this->template(
			'components/icons/quiz',
			[
				'classes' => [ 'ld-details__icon' ],
			]
		);
		?>
	</div>

	<span class="ld-details__label ld-details__label--quizzes">
		<?php
		echo esc_html(
			sprintf(
				/* translators: %1$d: Quizzes number, %2$s: Quiz label singular, %3$s: Quiz label plural */
				_n( // phpcs:ignore WordPress.WP.I18n.MismatchedPlaceholders -- It's intentional to allow proper translation.
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
