<?php
/**
 * View: Tabs Item.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var Tabs\Tab $tab  Tab.
 * @var Template $this Template instance.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Tabs;
use LearnDash\Core\Template\Template;
?>
<button
	aria-controls="ld-tab-panel-<?php echo esc_attr( $tab->get_id() ); ?>"
	aria-selected="<?php echo esc_attr( $tab->is_first() ? 'true' : 'false' ); ?>"
	class="ld-tab-bar__tab"
	id="ld-tab-<?php echo esc_attr( $tab->get_id() ); ?>"
	role="tab"
	<?php if ( ! $tab->is_first() ) : ?>
		tabindex="-1"
	<?php endif; ?>
>
	<?php if ( ! empty( $tab->get_icon() ) ) : ?>
		<?php $this->template( 'components/icons/' . $tab->get_icon() ); ?>
	<?php endif; ?>

	<span class="ld-tab-bar__tab-title">
		<?php echo esc_html( $tab->get_label() ); ?>
	</span>
</button>
