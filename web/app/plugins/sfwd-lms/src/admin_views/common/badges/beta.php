<?php
/**
 * View: Beta badge
 *
 * @since 4.21.0
 * @version 4.21.0
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

?>
<div class="ld-settings__badge ld-settings__badge--beta">
	<?php Template::show_template( 'components/icons/sparkle' ); ?>
	<span class="ld-settings__badge-label ld-settings__badge-label--beta">
		<?php esc_html_e( 'Beta', 'learndash' ); ?>
	</span>
</div>
