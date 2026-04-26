<?php
/**
 * View: Values Dashboard Widget Sub Label.
 *
 * @since 4.9.0
 * @version 4.9.0
 *
 * @var Values_Item $item Item.
 * @var Template    $this Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Dashboards\Widgets\Types\DTO\Values_Item;
use LearnDash\Core\Template\Template;

if ( empty( $item->sub_label ) ) {
	return;
}
?>
<span class="ld-dashboard-widget-values__sub-label">
	<?php echo esc_html( $item->sub_label ); ?>
</span>
