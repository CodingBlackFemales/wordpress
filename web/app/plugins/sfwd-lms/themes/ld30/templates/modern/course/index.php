<?php
/**
 * View: Course Page. Modern variant.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var Course   $course       Course model.
 * @var bool     $show_sidebar Whether to show the sidebar.
 * @var bool     $show_header  Whether to show the header.
 * @var Template $this         Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Course;
use LearnDash\Core\Template\Template;

$additional_classes = 'ld-layout';
if ( ! $show_sidebar ) {
	$additional_classes .= ' ld-layout--no-sidebar';
}

if ( ! $show_header ) {
	$additional_classes .= ' ld-layout--no-header';
}

?>
<div
	class="<?php learndash_the_wrapper_class( $course->get_post(), $additional_classes ); ?>"
	data-js="learndash-view"
	data-learndash-breakpoints="<?php echo esc_attr( $this->get_breakpoints_json() ); ?>"
	data-learndash-breakpoint-pointer="<?php echo esc_attr( $this->get_breakpoint_pointer() ); ?>"
>
	<?php if ( $show_header ) : ?>
		<?php $this->template( 'modern/course/header' ); ?>
	<?php endif; ?>

	<?php $this->template( 'modern/course/content' ); ?>

	<?php if ( $show_sidebar ) : ?>
		<?php $this->template( 'modern/course/sidebar' ); ?>
	<?php endif; ?>
</div>

<?php
$this->template( 'components/breakpoints', [ 'is_initial_load' => true ] );
