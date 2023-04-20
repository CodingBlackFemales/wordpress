<?php
/**
 * Template file for incomplete status.
 *
 * @package LearnDash_Settings_Page_Setup
 *
 * @since 4.4.0
 *
 * @var array<string, mixed> $step
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit();
}

?>

<div class="status-wrapper">
	<span class="status time">
		<?php // translators: number of minutes. ?>
		<span class="text"><?php printf( esc_html_x( '%d Minutes', 'Number of minutes', 'learndash' ), esc_html( $step['time_in_minutes'] ) ); ?></span>
		<span class="icon">
			<?php
            // phpcs:ignore WordPress.WP.AlternativeFunctions.file_get_contents_file_get_contents, WordPress.NamingConventions.PrefixAllGlobals.NonPrefixedVariableFound
			$icon_svg = file_get_contents( LEARNDASH_LMS_PLUGIN_DIR . '/assets/images/time.svg' );

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
