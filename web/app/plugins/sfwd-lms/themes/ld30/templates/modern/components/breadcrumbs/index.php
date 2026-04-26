<?php
/**
 * View: Breadcrumbs.
 *
 * @since 4.24.0
 * @version 4.24.0
 *
 * @var Breadcrumbs $breadcrumbs Breadcrumbs.
 * @var Template    $this        Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Breadcrumbs\Breadcrumbs;
use LearnDash\Core\Template\Template;

if ( $breadcrumbs->is_empty() ) {
	return;
}

?>
<nav
	aria-label="<?php esc_html_e( 'Breadcrumbs', 'learndash' ); ?>"
	class="ld-breadcrumbs ld-breadcrumbs--modern"
>
	<ol class="ld-breadcrumbs__items">
		<?php foreach ( $breadcrumbs as $breadcrumb ) : ?>
			<?php $this->template( 'modern/components/breadcrumbs/breadcrumb', [ 'breadcrumb' => $breadcrumb ] ); ?>
		<?php endforeach; ?>
	</ol>
</nav>
