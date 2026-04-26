<?php
/**
 * View: Course Details Requirements - Prerequisites.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var Course   $course Course model.
 * @var Template $this   Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Course;
use LearnDash\Core\Template\Template;

$prerequisites = $course->get_requirement_prerequisites();

if ( is_null( $prerequisites ) ) {
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

	<span class="ld-details__label ld-details__label--prerequisites">
		<?php if ( count( $prerequisites['ids'] ) > 1 ) : ?>
			<?php if ( $prerequisites['type'] === 'any' ) : ?>
				<b><?php esc_html_e( 'Prerequisites (Any 1)', 'learndash' ); ?></b>:
			<?php else : ?>
				<b><?php esc_html_e( 'Prerequisites (All)', 'learndash' ); ?></b>:
			<?php endif; ?>
		<?php else : ?>
			<b><?php esc_html_e( 'Prerequisite', 'learndash' ); ?></b>:
		<?php endif; ?>

		<?php
		echo implode(
			', ',
			array_map( // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped -- Already escaped in the callback.
				function ( $course_id ) {
					return sprintf(
						'<a class="ld-details__link" href="%s">%s</a>',
						esc_url( (string) get_permalink( $course_id ) ),
						wp_kses_post( get_the_title( $course_id ) )
					);
				},
				$prerequisites['ids']
			)
		);
		?>
	</span>
</div>
