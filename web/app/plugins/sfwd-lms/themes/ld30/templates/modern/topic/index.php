<?php
/**
 * View: Topic Page. Modern variant.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Topic    $topic       Topic model.
 * @var bool     $show_header Whether to show the header.
 * @var Template $this        Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Topic;
use LearnDash\Core\Template\Template;

$additional_classes = 'ld-layout ld-layout--no-sidebar';

if ( ! $show_header ) {
	$additional_classes .= ' ld-layout--no-header';
}
?>
<div
	class="<?php learndash_the_wrapper_class( $topic->get_post(), $additional_classes ); ?>"
	data-js="learndash-view"
	data-learndash-breakpoints="<?php echo esc_attr( $this->get_breakpoints_json() ); ?>"
	data-learndash-breakpoint-pointer="<?php echo esc_attr( $this->get_breakpoint_pointer() ); ?>"
>
	<?php if ( $show_header ) : ?>
		<?php $this->template( 'modern/topic/header' ); ?>
	<?php endif; ?>

	<?php $this->template( 'modern/topic/content' ); ?>
</div>

<?php
$this->template( 'components/breakpoints', [ 'is_initial_load' => true ] );
