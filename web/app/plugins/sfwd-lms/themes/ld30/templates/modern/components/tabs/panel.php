<?php
/**
 * View: Tab Panel.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var Tabs\Tab $tab Tab.
 * @var Template $this Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Tabs;
use LearnDash\Core\Template\Template;
?>
<div
	aria-labelledby="ld-tab-<?php echo esc_attr( $tab->get_id() ); ?>"
	<?php if ( ! $tab->is_first() ) : ?>
		aria-hidden="true"
	<?php endif; ?>
	class="ld-tab-bar__panel"
	id="ld-tab-panel-<?php echo esc_attr( $tab->get_id() ); ?>"
	role="tabpanel"
>
	<?php
	if ( empty( $tab->get_template() ) ) {
		echo $tab->get_content(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		$this->template( $tab->get_template() );
	}
	?>
</div>
