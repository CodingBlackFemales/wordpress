<?php
/**
 * View: Group Details Includes - Courses.
 *
 * @since 4.22.0
 * @version 4.22.0
 *
 * @var Group    $group          Group model.
 * @var int      $courses_number Number of courses.
 * @var Template $this           Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;
use LearnDash\Core\Models\Group;

if ( $courses_number <= 0 ) {
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

	<span class="ld-details__label ld-details__label--courses">
		<?php
		echo esc_html(
			sprintf(
				/* translators: %1$d: Courses number, %2$s: Course label singular, %3$s: Course label plural */
				_n( // phpcs:ignore WordPress.WP.I18n.MismatchedPlaceholders -- It's intentional to allow proper translation.
					'%1$d %2$s',
					'%1$d %3$s',
					$courses_number,
					'learndash'
				),
				$courses_number,
				learndash_get_custom_label( 'course' ),
				learndash_get_custom_label( 'courses' )
			)
		);
		?>
	</span>
</div>
