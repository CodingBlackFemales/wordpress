<?php
/**
 * View: Dashboard Footer.
 *
 * @since 4.9.0
 * @version 4.17.0
 *
 * @var bool     $propanel_is_active Whether ProPanel is installed and active.
 * @var Template $this               Current instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

if ( $propanel_is_active ) {
	return;
}
?>
<div class="ld-dashboard__footer">
	<a href="https://go.learndash.com/ppreports" target="_blank">
		<?php esc_html_e( 'Want advanced course data analytics? Check out ProPanel for more.', 'learndash' ); ?>
	</a>
</div>
