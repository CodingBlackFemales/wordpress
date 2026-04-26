<?php
/**
 * View: Tabs List.
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @var array<array{
 *     id: string,
 *     icon: string,
 *     label: string,
 *     content: string,
 *     is_first: bool,
 * }> $tabs Tabs.
 * @var Template $this Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;
?>
<div class="ld-tab-bar__tabs" role="tablist">
	<?php foreach ( $tabs as $tab ) : // phpcs:ignore WordPress.WP.GlobalVariablesOverride.Prohibited -- It's not global in this context. ?>
		<?php $this->template( 'modern/components/tabs/tab', [ 'tab' => $tab ] ); ?>
	<?php endforeach; ?>
</div>
