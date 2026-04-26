<?php
/**
 * View: Group Courses.
 *
 * @since 4.22.0
 * @version 4.22.0
 *
 * @var string   $courses_content The rendered courses content (HTML).
 * @var Template $this Current    Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;
use LearnDash\Core\Utilities\Sanitize;

if ( empty( $courses_content ) ) {
	return;
}
?>
<section
	class="ld-group__courses ld-course-grid__wrapper"
	data-js="learndash-view"
	data-learndash-breakpoints="<?php echo esc_attr( $this->get_breakpoints_json() ); ?>"
	data-learndash-breakpoint-pointer="<?php echo esc_attr( $this->get_breakpoint_pointer() ); ?>"
>
	<?php $this->template( 'modern/group/courses/heading' ); ?>

	<?php echo wp_kses( $courses_content, Sanitize::extended_kses() ); ?>
</section>

<?php
$this->template( 'components/breakpoints', [ 'is_initial_load' => true ] );
