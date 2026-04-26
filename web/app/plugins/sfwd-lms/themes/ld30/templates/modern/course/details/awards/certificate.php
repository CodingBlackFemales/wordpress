<?php
/**
 * View: Course Details Awards - Certificate.
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

if ( ! $course->get_award_certificate() ) {
	return;
}
?>
<div class="ld-details__item">
	<div class="ld-details__icon-wrapper">
		<?php
		$this->template(
			'components/icons/certificate',
			[
				'classes' => [ 'ld-details__icon' ],
			]
		);
		?>
	</div>

	<span class="ld-details__label ld-details__label--certificate">
		<b><?php echo esc_html( learndash_get_custom_label( 'certificate' ) ); ?></b>
	</span>
</div>
