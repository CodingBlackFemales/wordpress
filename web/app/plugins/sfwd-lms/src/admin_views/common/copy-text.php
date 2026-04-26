<?php
/**
 * HTML for the "Copy Text" button.
 *
 * @since 4.19.0
 * @version 4.19.0
 *
 * @var Template $this            Current instance of template engine rendering this template.
 * @var string   $text            Text to be copied.
 * @var string   $tooltip_default Tooltip text.
 * @var string   $tooltip_success Tooltip text shown on successful copying.
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

if ( empty( $text ) ) {
	return;
}

if ( empty( $tooltip_default ) ) {
	$tooltip_default = __( 'Copy Code', 'learndash' );
}

if ( empty( $tooltip_success ) ) {
	$tooltip_success = __( 'Copied!', 'learndash' );
}
?>

<button
	class="learndash-copy-text"
	data-tooltip="<?php echo esc_attr( $tooltip_default ); ?>"
	data-tooltip-default="<?php echo esc_attr( $tooltip_default ); ?>"
	data-tooltip-success="<?php echo esc_attr( $tooltip_success ); ?>"
	data-text="<?php echo esc_attr( $text ); ?>"
>
	<span class="dashicons dashicons-admin-page" aria-hidden="true"></span>
	<span class="screen-reader-text">
		<?php
		echo esc_html(
			sprintf(
				// Translators: placeholders: The text to be copied to the clipboard.
				__( 'Click to copy "%s" to your clipboard.', 'learndash' ),
				$text
			)
		);
		?>
	</span>
</button>
