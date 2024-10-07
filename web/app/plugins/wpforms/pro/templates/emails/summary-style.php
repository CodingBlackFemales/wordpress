<?php
/**
 * Email Summary style template.
 *
 * This template can be overridden by copying it to yourtheme/wpforms/emails/summary-style.php.
 *
 * Note: This template overrides the Lite version and is only loaded if the Pro version is active.
 *
 * @since 1.8.8
 *
 * @var string $email_background_color Background color for the email.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

require WPFORMS_PLUGIN_DIR . '/assets/pro/css/emails/summary.min.css';

if ( ! empty( $email_background_color ) ) : ?>
	body, .body {
		background-color: <?php echo sanitize_hex_color( $email_background_color ); ?> !important;
	}
<?php endif; ?>
