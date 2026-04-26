<?php
/**
 * View: Course Details Includes - Lessons.
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

$lessons_number = $course->get_lessons_number();

if ( $lessons_number <= 0 ) {
	return;
}
?>
<div class="ld-details__item">
	<div class="ld-details__icon-wrapper">
		<?php
		$this->template(
			'components/icons/course',
			[
				'classes' => [ 'ld-details__icon' ],
			]
		);
		?>
	</div>

	<span class="ld-details__label ld-details__label--lessons">
		<?php
		echo esc_html(
			sprintf(
				/* translators: %1$d: Lessons number, %2$s: Lesson label singular, %3$s: Lesson label plural */
				_n( // phpcs:ignore WordPress.WP.I18n.MismatchedPlaceholders -- It's intentional to allow proper translation.
					'%1$d %2$s',
					'%1$d %3$s',
					$lessons_number,
					'learndash'
				),
				$lessons_number,
				learndash_get_custom_label( 'lesson' ),
				learndash_get_custom_label( 'lessons' )
			)
		);
		?>
	</span>
</div>
