<?php
/**
 * View: New badge
 *
 * @since 4.23.0
 * @version 4.23.0
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

?>
<div class="ld-settings__badge ld-settings__badge--new">
	<?php
	Template::show_template(
		'components/icons/bell',
		[
			'is_aria_hidden' => true,
		]
	);
	?>
	<span class="ld-settings__badge-label ld-settings__badge-label--new">
		<?php esc_html_e( 'New!', 'learndash' ); ?>
	</span>
</div>
