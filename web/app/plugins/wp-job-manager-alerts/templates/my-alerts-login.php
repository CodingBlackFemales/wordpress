<?php
/**
 * Lists job listing alerts content if user is not logged in.
 *
 * This template can be overridden by copying it to yourtheme/wp-job-manager-alerts/my-alerts-login.php.
 *
 * @see         https://wpjobmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     WP Job Manager - Alerts
 * @category    Template
 * @version     1.5.6
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}
?>

<div id="job-manager-job-alerts">
	<p class="account-sign-in"><?php esc_html_e( 'You need to be signed in to manage your alerts.', 'wp-job-manager-alerts' ); ?> <a class="button" href="<?php echo esc_url( apply_filters( 'job_manager_alerts_login_url', wp_login_url( get_permalink() ) ) ); ?>"><?php esc_html_e( 'Sign in', 'wp-job-manager-alerts' ); ?></a></p>
</div>
