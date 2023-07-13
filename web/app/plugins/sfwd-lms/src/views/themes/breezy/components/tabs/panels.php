<?php
/**
 * View: Tab Panels.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var array    $tabs Tabs.
 * @var Template $this Current Instance of template engine rendering this template.
 *
 * @phpstan-var array<array{
 *     id: string,
 *     icon: string,
 *     label: string,
 *     content: string,
 *     is_first: bool,
 * }> $tabs
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

use LearnDash\Core\Template\Template;
?>
<div class="ld-tab-bar__panels">
	<?php foreach ( $tabs as $tab ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride ?>
		<?php $this->template( 'components/tabs/panel', [ 'tab' => $tab ] ); ?>
	<?php endforeach; ?>
</div>
