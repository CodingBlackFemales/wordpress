<?php
/**
 * View: Course Details Includes - Topics.
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

$topics_number = $course->get_topics_number();

if ( $topics_number <= 0 ) {
	return;
}
?>
<div class="ld-details__item">
	<div class="ld-details__icon-wrapper">
		<?php
		$this->template(
			'components/icons/lesson',
			[
				'classes' => [ 'ld-details__icon' ],
			]
		);
		?>
	</div>

	<span class="ld-details__label ld-details__label--topics">
		<?php
		echo esc_html(
			sprintf(
				/* translators: %1$d: Topics number, %2$s: Topic label singular, %3$s: Topic label plural */
				_n( // phpcs:ignore WordPress.WP.I18n.MismatchedPlaceholders -- It's intentional to allow proper translation.
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
