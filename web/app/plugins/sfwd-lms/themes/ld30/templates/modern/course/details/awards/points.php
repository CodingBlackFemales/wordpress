<?php
/**
 * View: Course Details Awards - Points.
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

$points = $course->get_award_points();

if ( $points <= 0 ) {
	return;
}
?>
<div class="ld-details__item">
	<div class="ld-details__icon-wrapper">
		<?php
		$this->template(
			'components/icons/points-plus',
			[
				'classes' => [ 'ld-details__icon' ],
			]
		);
		?>
	</div>

	<span class="ld-details__label ld-details__label--points">
		<?php
		printf(
			wp_kses(
				/* translators: %1$s: Points number, %2$s: Course label singular */
				_n(
					'<b>%1$s</b> %2$s Point',
					'<b>%1$s</b> %2$s Points',
					(int) ceil( $points ),
					'learndash'
				),
				[ 'b' => [] ]
			),
			esc_html( (string) $points ),
			esc_html( learndash_get_custom_label( 'course' ) )
		);
		?>
	</span>
</div>
