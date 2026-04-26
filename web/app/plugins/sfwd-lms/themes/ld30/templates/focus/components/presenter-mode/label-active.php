<?php
/**
 * View: Presenter Mode Inactive Label.
 *
 * @since 4.23.0
 * @version 4.23.0
 *
 * @var Template $this Current Instance of template engine rendering this template.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

?>

<span class="ld-presenter-mode__label ld-presenter-mode__label--active">
	<?php esc_html_e( 'Exit', 'learndash' ); ?>

	<span class="screen-reader-text">
		<?php esc_html_e( 'Presenter Mode', 'learndash' ); ?>
	</span>
</span>
