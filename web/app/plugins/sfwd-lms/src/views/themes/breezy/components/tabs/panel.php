<?php
/**
 * View: Tab Panel.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var Tabs\Tab $tab Tab.
 * @var Template $this Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

use LearnDash\Core\Template\Tabs;
use LearnDash\Core\Template\Template;
?>
<div
	class="ld-tab-bar__panel"
	id="ld-tab-panel-<?php echo esc_attr( $tab->get_id() ); ?>"
	role="tabpanel"
	aria-labelledby="ld-tab-<?php echo esc_attr( $tab->get_id() ); ?>"
	<?php if ( ! $tab->is_first() ) : ?>
		aria-hidden="true"
	<?php endif; ?>
>
	<?php
	if ( empty( $tab->get_template() ) ) {
		echo $tab->get_content(); // phpcs:ignore WordPress.Security.EscapeOutput.OutputNotEscaped
	} else {
		$this->template( $tab->get_template() );
	}
	?>
</div>
