<?php
/**
 * Form used when creating a new job listing alert.
 *
 * This template can be overridden by copying it to yourtheme/wp-job-manager-alerts/alert-form.php.
 *
 * @see         https://wpjobmanager.com/document/template-overrides/
 * @author      Automattic
 * @package     WP Job Manager - Alerts
 * @category    Template
 * @version     3.0.0
 *
 * @var int    $alert_id Alert ID.
 * @var string $alert_email Alert e-mail.
 * @var string $alert_name Alert name.
 * @var string $alert_keyword Alert keyword.
 * @var string $alert_location Alert location.
 * @var string $alert_frequency Alert frequency.
 * @var array  $alert_cats Alert categories.
 * @var array  $alert_tags Alert tags.
 * @var array  $alert_job_type Alert job types.
 * @var array  $alert_regions Alert regions.
 * @var array  $alert_permission Alert permission.
 * @var bool   $show_alert_name Whether to show the alert name field.
 */

if ( ! defined( 'ABSPATH' ) ) {
	exit;
}

use WP_Job_Manager\UI\Notice;
use WP_Job_Manager_Alerts\Alert_Form_Fields;
use WP_Job_Manager_Alerts\Shortcodes;

wp_enqueue_script( 'wp-job-manager-term-multiselect' );

if ( ! is_user_logged_in() ) {
	echo Notice::hint(
		[
			'message' => __( 'Sign in to manage your existing alerts.', 'wp-job-manager-alerts' ),
			'buttons' => [
				[
					'label' => __( 'Sign In', 'wp-job-manager-alerts' ),
					'url'   => wp_login_url( Shortcodes::get_page_url() ),
				],
			],
		]
	);
}

$fields = new Alert_Form_Fields();

?>
<form method="post" class="job-manager-form jm-alert-form">
	<section class="jm-form-section">
		<header class="jm-form-section-header">
			<strong class="jm-form-section-header__title">
				<?php _e( 'Alert Details', 'wp-job-manager-alerts' ); ?>
			</strong>
		</header>
		<?php if ( empty( $alert_email ) ) : ?>
			<fieldset>
				<label for="alert_email"><?php _e( 'E-mail', 'wp-job-manager-alerts' ); ?></label>
				<div class="field">
					<input type="email" name="alert_email" id="alert_email" required autocomplete="email" class="input-text"
						placeholder="<?php _e( 'Enter your e-mail address', 'wp-job-manager-alerts' ); ?>" />
				</div>
			</fieldset>
		<?php else: ?>
			<fieldset>
				<label for="alert_email"><?php _e( 'E-mail', 'wp-job-manager-alerts' ); ?></label>
				<div class="field">
					<p><?php echo esc_html( $alert_email ) ?></p>
				</div>
			</fieldset>
		<?php endif; ?>
		<fieldset>
			<label for="alert_frequency"><?php _e( 'E-mail Frequency', 'wp-job-manager-alerts' ); ?></label>
			<div class="field">
				<?php echo $fields->alert_frequency( [ 'selected' => $alert_frequency ] ); ?>
			</div>
		</fieldset>
		<?php if ( $show_alert_name ) : ?>
			<fieldset>
				<label for="alert_name"><?php _e( 'Alert Name', 'wp-job-manager-alerts' ); ?></label>
				<div class="field">
					<input type="text" name="alert_name" value="<?php echo esc_attr( $alert_name ); ?>" id="alert_name"
						class="input-text"
						placeholder="<?php _e( 'Enter a name for your alert', 'wp-job-manager-alerts' ); ?>" />
				</div>
			</fieldset>
		<?php endif; ?>
	</section>
	<section class="jm-form-section">
		<header class="jm-form-section-header">
			<strong class="jm-form-section-header__title">
				<?php _e( 'Search terms', 'wp-job-manager-alerts' ); ?>
			</strong>
			<p class="jm-form-section-header__description">
				<?php _e( 'The alert e-mails will contain new job listings matching these terms. Leave blank to receive all new jobs posted.', 'wp-job-manager-alerts' ); ?>
			</p>
		</header>
		<?php if ( $fields->is_active( 'keywords' ) ) : ?>
			<fieldset>
				<label for="alert_keyword"><?php _e( 'Keyword', 'wp-job-manager-alerts' ); ?></label>
				<div class="field">
					<input type="text" name="alert_keyword" value="<?php echo esc_attr( $alert_keyword ); ?>"
						id="alert_keyword" class="input-text"
						placeholder="<?php _e( 'Optionally add a keyword to match jobs against', 'wp-job-manager-alerts' ); ?>" />
				</div>
			</fieldset>
		<?php endif; ?>
		<?php if ( $fields->is_active( 'regions' ) ) : ?>
			<fieldset>
				<label for="alert_regions"><?php _e( 'Job Region', 'wp-job-manager-alerts' ); ?></label>
				<div class="field">
					<?php echo $fields->alert_regions( $alert_regions ); ?>
				</div>
			</fieldset>
		<?php else : ?>
			<?php if ( $fields->is_active( 'location' ) ) : ?>
				<fieldset>
					<label for="alert_location"><?php _e( 'Location', 'wp-job-manager-alerts' ); ?></label>
					<div class="field">
						<input type="text" name="alert_location" value="<?php echo esc_attr( $alert_location ); ?>"
							id="alert_location" class="input-text"
							placeholder="<?php _e( 'Optionally define a location to search against', 'wp-job-manager-alerts' ); ?>" />
					</div>
				</fieldset>
			<?php endif; ?>
		<?php endif; ?>
		<?php if ( $fields->is_active( 'categories' ) ) : ?>
			<fieldset>
				<label for="alert_cats"><?php _e( 'Categories', 'wp-job-manager-alerts' ); ?></label>
				<div class="field">
					<?php echo $fields->alert_cats( $alert_cats ); ?>
				</div>
			</fieldset>
		<?php endif; ?>
		<?php if ( $fields->is_active( 'tags' ) ) : ?>
			<fieldset>
				<label for="alert_tags"><?php _e( 'Tags', 'wp-job-manager-alerts' ); ?></label>
				<div class="field">
					<?php echo $fields->alert_tags( $alert_tags ); ?>
				</div>
			</fieldset>
		<?php endif; ?>
		<?php if ( $fields->is_active( 'job_type' ) ) : ?>
			<fieldset>
				<label for="alert_job_type"><?php _e( 'Job Type', 'wp-job-manager-alerts' ); ?></label>
				<div class="field">
					<?php echo $fields->alert_job_type( $alert_job_type ); ?>
				</div>
			</fieldset>
		<?php endif; ?>
		<?php if ( empty( $alert_id ) ): ?>
		<fieldset class="fieldset-agreement-checkbox">
			<div class="field full-line-checkbox-field required-field">
				<?php echo $fields->alert_permission( $alert_permission ); ?>
			</div>
		</fieldset>
		<?php endif; ?>
		<p class="jm-form-actions">
			<?php wp_nonce_field( 'job_manager_alert_actions' ); ?>
			<input type="hidden" name="alert_id" value="<?php echo absint( $alert_id ); ?>" />
			<button class="wp-element-button button jm-form-submit--large" type="submit"
				name="submit-job-alert"><?php _e( 'Save alert', 'wp-job-manager-alerts' ); ?></button>
		</p>
	</section>
</form>
