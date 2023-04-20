<?php
/**
 * LearnDash Widget Alert Message.
 *
 * @since 4.0.0
 * @package LearnDash\Widgets
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

/**
 * Displays alert message for LearnDash related Appearance->Widgets
 *
 * @since 4.0.0
 * @package LearnDash\Widgets
 */
function learndash_replace_widgets_alert() {
	echo '<p><strong>';
	echo esc_html__( 'Notice: This widget may no longer be supported in future versions of LearnDash or WordPress, please use a block instead.', 'learndash' );
	echo '</strong></p>';
}
