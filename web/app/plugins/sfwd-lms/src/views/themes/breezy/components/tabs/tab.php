<?php
/**
 * View: Tabs Item.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var Tabs\Tab $tab  Tab.
 * @var Template $this Template instance.
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
<button
	class="ld-tab-bar__tab"
	id="ld-tab-<?php echo esc_attr( $tab->get_id() ); ?>"
	role="tab"
	aria-controls="ld-tab-panel-<?php echo esc_attr( $tab->get_id() ); ?>"
	aria-selected="<?php echo esc_attr( $tab->is_first() ? 'true' : 'false' ); ?>"
	<?php if ( ! $tab->is_first() ) : ?>
		tabindex="-1"
	<?php endif; ?>
>
	<?php if ( ! empty( $tab->get_icon() ) ) : ?>
		<?php $this->template( 'components/icons/' . $tab->get_icon(), [ 'classes' => [ 'ld-icon--lg' ] ] ); ?>
	<?php endif; ?>

	<span class="ld-tab-bar__tab-title">
		<?php echo esc_html( $tab->get_label() ); ?>
	</span>
</button>
