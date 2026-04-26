<?php
/**
 * View: Order Test Mode indicator.
 *
 * @since 4.19.0
 * @version 4.19.0
 *
 * @var Template $this  Current instance of template engine rendering this template.
 * @var string   $label Test mode label. Defaults to "Test".
 *
 * @package LearnDash\Core
 */

use LearnDash\Core\Template\Template;

if ( empty( $label ) ) {
	$label = __( 'Test', 'learndash' );
}

?>

<span class="ld-order-test-mode-indicator">
	<?php echo esc_html( $label ); ?>
</span>
