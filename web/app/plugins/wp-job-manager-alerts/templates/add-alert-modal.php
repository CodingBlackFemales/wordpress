<?php
/**
 * Content of the modal dialog when adding an alert from the jobs search page.
 *
 * This template can be overridden by copying it to yourtheme/wp-job-manager-alerts/alert-form.php.
 *
 * @see         https://wpjobmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     WP Job Manager - Alerts
 * @category    Template
 * @version     3.1.0
 *
 * @var string $page Alerts page URL.
 * @var string $alert_email The current user's e-mail address.
 */

use WP_Job_Manager_Alerts\Alert_Form_Fields;

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

$fields = new Alert_Form_Fields();

?>

<form method="post" class="jm-form" action="<?php echo esc_attr( $page ) ?>" method="post">
	<?php wp_nonce_field( 'job_manager_alert_actions' ); ?>
	<input type="hidden" name="submit-job-alert" value="1" />
	<div class="jm-form-large-field job-alert-keyword"><?php _e( 'Keyword', 'wp-job-manager-alerts' ); ?></div>
	<div class="job-alert-search-terms" hidden></div>

	<?php if ( empty( $alert_email ) ) : ?>

		<p><?php _e( 'Get e-mails about new jobs matching this search.', 'wp-job-manager-alerts' ); ?></p>
		<div class="jm-form-field">
			<input type="email" name="alert_email" required autocomplete="email"
				placeholder="<?php esc_attr_e( 'Email address', 'wp-job-manager-alerts' ); ?>"
				aria-label="<?php esc_attr_e( 'Email address', 'wp-job-manager-alerts' ); ?>" />
		</div>
	<?php else: ?>
		<p><?php echo wp_kses( sprintf( __( 'Send e-mails to <strong>%s</strong> about new jobs matching this search.', 'wp-job-manager-alerts' ), $alert_email ), [ 'strong' => [] ] ); ?></p>
	<?php endif; ?>


	<div class="">
		<label for="alert_frequency"><?php esc_html_e( 'Frequency: ', 'wp-job-manager-alerts' ); ?></label>
		<?php echo $fields->alert_frequency( [ 'class' => 'jm-form-input--inline' ] ); ?>
	</div>

	<div class="jm-form-field jm-form-fine-print">
			<?php echo $fields->alert_permission(); ?>
	</div>

	<input type="hidden" name="submit-job-alert" value="1" />
	<input type="submit" hidden />

	<div class="jm-ui-actions jm-ui-actions-row">
		<a href="#" class="jm-ui-button"
			<?php if ( ! empty( $alert_email ) ) { echo ' autofocus '; } ?>
			onclick="this.closest('form').querySelector('input[type=submit]').click(); return false;"><span><?php _e( 'Subscribe', 'wp-job-manager-alerts' ); ?></span></a>
		<a href="#" class="jm-ui-button--link"
			onclick="{close}"><?php _e( 'Cancel', 'wp-job-manager-alerts' ); ?></a>
	</div>
</form>
