<?php
/**
 * Template file for completed status.
 *
 * @package LearnDash_Settings_Page_Setup
 *
 * @since 4.4.0
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

?>

<div class="status-wrapper">
	<span class="status completed">
		<span class="text"><?php esc_html_e( 'Completed', 'learndash' ); ?></span>
		<span class="icon">
			<?php
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
			$icon_svg = file_get_contents( LEARNDASH_LMS_PLUGIN_DIR . '/assets/images/completed.svg' );

			if ( $icon_svg ) {
				echo wp_kses(
					$icon_svg,
					'svg'
				);
			}
			?>
		</span>
	</span>
</div>
