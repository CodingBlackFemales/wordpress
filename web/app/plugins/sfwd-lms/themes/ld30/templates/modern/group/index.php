<?php
/**
 * View: Group Page. Modern variant.
 *
 * @since 4.22.0
 * @version 4.22.0
 *
 * @var Group    $group        Group model.
 * @var bool     $show_sidebar Whether to show the sidebar.
 * @var bool     $show_header  Whether to show the header.
 * @var Template $this         Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Models\Group;
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
	class="<?php learndash_the_wrapper_class( $group->get_post(), $additional_classes ); ?>"
	data-js="learndash-view"
	data-learndash-breakpoints="<?php echo esc_attr( $this->get_breakpoints_json() ); ?>"
	data-learndash-breakpoint-pointer="<?php echo esc_attr( $this->get_breakpoint_pointer() ); ?>"
>
	<?php if ( $show_header ) : ?>
		<?php $this->template( 'modern/group/header' ); ?>
	<?php endif; ?>

	<?php $this->template( 'modern/group/content' ); ?>

	<?php if ( $show_sidebar ) : ?>
		<?php $this->template( 'modern/group/sidebar' ); ?>
	<?php endif; ?>
</div>

<?php
$this->template( 'components/breakpoints', [ 'is_initial_load' => true ] );
