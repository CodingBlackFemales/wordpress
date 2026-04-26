<?php
/**
 * View: Tabs.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var bool     $has_access Whether the user has access to the course.
 * @var Tabs     $tabs       Tabs.
 * @var Template $this       Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Tabs\Tabs;
use LearnDash\Core\Template\Template;

if ( $tabs->is_empty() ) {
	return;
}

$multiple_tabs = $tabs->count() > 1;
?>
<div
	class="ld-tab-bar <?php echo esc_attr( $multiple_tabs ? 'ld-tab-bar--multiple' : 'ld-tab-bar--single' ); ?> <?php echo esc_attr( $has_access ? 'ld-tab-bar--has-access' : 'ld-tab-bar--no-access' ); ?>"
	data-js="learndash-view"
	data-learndash-breakpoints="<?php echo esc_attr( $this->get_breakpoints_json() ); ?>"
	data-learndash-breakpoint-pointer="<?php echo esc_attr( $this->get_breakpoint_pointer() ); ?>"
>
	<?php if ( $multiple_tabs ) : ?>
		<?php $this->template( 'modern/components/tabs/tabs' ); ?>

		<?php $this->template( 'modern/components/tabs/panels' ); ?>
	<?php else : ?>
		<?php
		$this->template(
			'modern/components/tabs/single',
			[
				'tab' => $tabs->current(),
			]
		);
		?>
	<?php endif; ?>
</div>

<?php
$this->template( 'components/breakpoints', [ 'is_initial_load' => true ] );
