<?php
/**
 * Suppress the per-reset administrator email.
 *
 * Core hooks wp_password_change_notification() onto after_password_reset in
 * wp-includes/default-filters.php, so every completed password reset emails
 * admin_email. During a bulk reset that floods the administrator inbox and
 * consumes outbound quota on the configured mailer, which is shared with
 * transactional site mail.
 *
 * Users still receive their own reset link; only the administrator copy is
 * suppressed.
 *
 * mu-plugins load after default-filters.php, so removing the action here is
 * sufficient. Delete this file to restore the default behaviour.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

remove_action( 'after_password_reset', 'wp_password_change_notification' );
