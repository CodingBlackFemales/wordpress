<?php
/**
 * View: Breadcrumbs Link.
 *
 * @since 4.6.0
 * @version 4.6.0
 *
 * @var Breadcrumb $breadcrumb Breadcrumbs Item.
 * @var Template   $this       Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

/** NOTICE: This code is currently under development and may not be stable.
 *  Its functionality, behavior, and interfaces may change at any time without notice.
 *  Please refrain from using it in production or other critical systems.
 *  By using this code, you assume all risks and liabilities associated with its use.
 *  Thank you for your understanding and cooperation.
 **/

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
