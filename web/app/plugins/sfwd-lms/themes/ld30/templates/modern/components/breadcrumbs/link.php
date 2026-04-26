<?php
/**
 * View: Breadcrumbs Item - Link.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Breadcrumb $breadcrumb Breadcrumbs Item.
 * @var Template   $this       Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Breadcrumbs\Breadcrumb;
use LearnDash\Core\Template\Template;
?>
<a
	href="<?php echo esc_url( $breadcrumb->get_url() ); ?>"
	class="ld-breadcrumbs__link"
	<?php if ( $breadcrumb->is_last() ) : ?>
		aria-current="page"
	<?php endif; ?>
>
	<?php echo esc_html( $breadcrumb->get_label() ); ?>
</a>
